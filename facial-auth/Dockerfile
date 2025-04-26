FROM python:3.9-slim

# Install required system packages including build dependencies for dlib
RUN apt-get update && apt-get install -y \
    build-essential \
    cmake \
    git \
    libgl1-mesa-glx \
    libglib2.0-0 \
    libsm6 \
    libxrender1 \
    libxext6 \
    libx11-6 \
    --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /app

# Copy requirements file
COPY ./app/requirements.txt /app/requirements.txt

# Install Python dependencies
RUN pip install --no-cache-dir -r requirements.txt

# Copy the rest of the application
COPY ./app /app

# Create directory for face data
RUN mkdir -p /app/faces

# Expose port for the Flask API
EXPOSE 5001

# Run the application
CMD ["python", "app.py"]