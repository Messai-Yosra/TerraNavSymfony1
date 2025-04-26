import os
import time
import json
import uuid
import base64
import logging
import face_recognition
import numpy as np
from flask import Flask, request, jsonify
from flask_cors import CORS
from io import BytesIO
from PIL import Image
import cv2
import jwt

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Initialize Flask app
app = Flask(__name__)
CORS(app)

# Configuration
FACE_DATA_DIR = os.environ.get('FACE_DATA_DIR', os.path.join(os.path.dirname(__file__), 'faces'))
JWT_SECRET = os.environ.get('JWT_SECRET', 'terranav_facial_auth_secret_key')
TOKEN_EXPIRY = int(os.environ.get('TOKEN_EXPIRY', 3600))  # 1 hour default
FACE_RECOGNITION_THRESHOLD = float(os.environ.get('FACE_RECOGNITION_THRESHOLD', 0.6))

# Ensure faces directory exists
os.makedirs(FACE_DATA_DIR, exist_ok=True)

# In-memory cache of face encodings
face_encodings_cache = {}

def load_face_encodings():
    """Load all face encodings from disk to memory."""
    start_time = time.time()
    encodings_count = 0
    
    for filename in os.listdir(FACE_DATA_DIR):
        if filename.endswith('.json'):
            user_id = filename.split('.')[0]
            file_path = os.path.join(FACE_DATA_DIR, filename)
            
            try:
                with open(file_path, 'r') as f:
                    data = json.load(f)
                    # Store encodings in memory
                    face_encodings_cache[user_id] = {
                        'encodings': [np.array(enc) for enc in data['encodings']],
                        'name': data.get('name', 'Unknown')
                    }
                    encodings_count += len(data['encodings'])
            except Exception as e:
                logger.error(f"Error loading face encodings for {user_id}: {str(e)}")
    
    logger.info(f"Loaded {encodings_count} face encodings for {len(face_encodings_cache)} users in {time.time() - start_time:.2f}s")

# Load face encodings on startup
load_face_encodings()

def base64_to_image(base64_string):
    """Convert a base64 string to a PIL Image."""
    if ',' in base64_string:
        base64_string = base64_string.split(',')[1]
    
    image_data = base64.b64decode(base64_string)
    image = Image.open(BytesIO(image_data))
    return image

def image_to_cv2(image):
    """Convert a PIL Image to an OpenCV image."""
    return cv2.cvtColor(np.array(image), cv2.COLOR_RGB2BGR)

def process_face_image(image_data):
    """Process a face image for encoding."""
    try:
        # Convert base64 to image
        if isinstance(image_data, str):
            image = base64_to_image(image_data)
        else:
            image = Image.open(BytesIO(image_data))
        
        # Convert to CV2 format for face detection
        cv2_image = image_to_cv2(image)
        
        # Convert to RGB for face_recognition
        rgb_image = cv2.cvtColor(cv2_image, cv2.COLOR_BGR2RGB)
        
        # Find faces in image
        face_locations = face_recognition.face_locations(rgb_image)
        
        if not face_locations:
            return None, "No faces detected in the image"
        
        if len(face_locations) > 1:
            return None, "Multiple faces detected, please provide an image with a single face"
        
        # Encode face
        face_encoding = face_recognition.face_encodings(rgb_image, face_locations)[0]
        
        return face_encoding, None
    except Exception as e:
        logger.error(f"Error processing face image: {str(e)}")
        return None, str(e)

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint."""
    return jsonify({
        'status': 'ok',
        'message': 'Facial authentication service is running',
        'users_count': len(face_encodings_cache),
    })

@app.route('/enroll', methods=['POST'])
def enroll():
    """Enroll a new face or update existing face data."""
    try:
        data = request.json
        
        if not data:
            return jsonify({'success': False, 'message': 'No data provided'}), 400
        
        user_id = data.get('user_id')
        user_name = data.get('user_name', 'Unknown')
        face_image = data.get('face_image')
        
        if not user_id:
            return jsonify({'success': False, 'message': 'User ID is required'}), 400
            
        if not face_image:
            return jsonify({'success': False, 'message': 'Face image is required'}), 400
        
        # Process face image
        face_encoding, error = process_face_image(face_image)
        
        if error:
            return jsonify({'success': False, 'message': error}), 400
        
        # Load existing data if available
        file_path = os.path.join(FACE_DATA_DIR, f"{user_id}.json")
        encodings = []
        
        if os.path.exists(file_path):
            try:
                with open(file_path, 'r') as f:
                    existing_data = json.load(f)
                    encodings = existing_data.get('encodings', [])
            except Exception as e:
                logger.error(f"Error reading existing face data for user {user_id}: {str(e)}")
        
        # Add new encoding
        encodings.append(face_encoding.tolist())
        
        # Save updated data
        with open(file_path, 'w') as f:
            json.dump({
                'name': user_name,
                'encodings': encodings,
                'updated_at': time.time()
            }, f)
        
        # Update cache
        face_encodings_cache[user_id] = {
            'encodings': [face_encoding] if len(encodings) == 1 else [np.array(enc) for enc in encodings[:-1]] + [face_encoding],
            'name': user_name
        }
        
        return jsonify({
            'success': True, 
            'message': 'Face enrolled successfully',
            'user_id': user_id,
            'encodings_count': len(encodings)
        }), 200
        
    except Exception as e:
        logger.error(f"Enrollment error: {str(e)}")
        return jsonify({'success': False, 'message': f'Error enrolling face: {str(e)}'}), 500

@app.route('/verify', methods=['POST'])
def verify():
    """Verify a face against enrolled faces."""
    try:
        data = request.json
        
        if not data:
            return jsonify({'success': False, 'message': 'No data provided'}), 400
        
        face_image = data.get('face_image')
        user_id = data.get('user_id')  # Optional, to verify against specific user
        
        if not face_image:
            return jsonify({'success': False, 'message': 'Face image is required'}), 400
        
        # Process face image
        face_encoding, error = process_face_image(face_image)
        
        if error:
            return jsonify({'success': False, 'message': error}), 400
        
        best_match = None
        best_match_distance = float('inf')
        
        # If user_id is provided, only compare against that user's faces
        if user_id:
            if user_id not in face_encodings_cache:
                return jsonify({'success': False, 'message': 'User not enrolled'}), 404
                
            user_encodings = face_encodings_cache[user_id]['encodings']
            user_name = face_encodings_cache[user_id]['name']
            
            # Compare against all encodings for this user
            for encoding in user_encodings:
                face_distance = face_recognition.face_distance([encoding], face_encoding)[0]
                
                if face_distance < FACE_RECOGNITION_THRESHOLD and face_distance < best_match_distance:
                    best_match = user_id
                    best_match_distance = face_distance
        
        else:
            # Compare against all enrolled faces
            for uid, data in face_encodings_cache.items():
                for encoding in data['encodings']:
                    face_distance = face_recognition.face_distance([encoding], face_encoding)[0]
                    
                    if face_distance < FACE_RECOGNITION_THRESHOLD and face_distance < best_match_distance:
                        best_match = uid
                        best_match_distance = face_distance
                        user_name = data['name']
        
        if best_match:
            # Generate JWT token for successful authentication
            payload = {
                'user_id': best_match,
                'verification_method': 'facial',
                'exp': int(time.time()) + TOKEN_EXPIRY
            }
            token = jwt.encode(payload, JWT_SECRET, algorithm='HS256')
            
            return jsonify({
                'success': True,
                'message': 'Face verification successful',
                'user_id': best_match,
                'name': user_name,
                'confidence': round((1 - best_match_distance) * 100, 2),
                'token': token
            }), 200
        else:
            return jsonify({
                'success': False,
                'message': 'Face verification failed, no match found',
                'threshold': FACE_RECOGNITION_THRESHOLD
            }), 401
            
    except Exception as e:
        logger.error(f"Verification error: {str(e)}")
        return jsonify({'success': False, 'message': f'Error verifying face: {str(e)}'}), 500

@app.route('/remove/<user_id>', methods=['DELETE'])
def remove_face(user_id):
    """Remove enrolled face data for a user."""
    try:
        file_path = os.path.join(FACE_DATA_DIR, f"{user_id}.json")
        
        if not os.path.exists(file_path):
            return jsonify({'success': False, 'message': 'User not found'}), 404
            
        os.remove(file_path)
        
        if user_id in face_encodings_cache:
            del face_encodings_cache[user_id]
            
        return jsonify({
            'success': True,
            'message': 'Face data removed successfully',
            'user_id': user_id
        }), 200
        
    except Exception as e:
        logger.error(f"Error removing face data: {str(e)}")
        return jsonify({'success': False, 'message': f'Error removing face data: {str(e)}'}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5001, debug=os.environ.get('DEBUG', 'False').lower() == 'true')