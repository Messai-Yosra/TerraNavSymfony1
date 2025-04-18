document.addEventListener('DOMContentLoaded', function() {
    // Gestion du curseur de capacité
    const capacitySlider = document.querySelector('.capacity-slider');
    const capacityValue = document.querySelector('.capacity-value');
    
    if (capacitySlider && capacityValue) {
        capacitySlider.addEventListener('input', function() {
            capacityValue.textContent = this.value + ' place' + (this.value > 1 ? 's' : '');
        });
        // Initialisation
        capacityValue.textContent = capacitySlider.value + ' place' + (capacitySlider.value > 1 ? 's' : '');
    }

    // Gestion du type de transport
    const typeSelector = document.querySelectorAll('input[name="type_selector"]');
    const typeField = document.getElementById('typeField');
    const customTypeGroup = document.getElementById('customTypeGroup');
    const customType = document.getElementById('customType');

    function initializeType() {
        const currentType = typeField.value;
        if (currentType) {
            const selector = document.querySelector(`input[name="type_selector"][value="${currentType}"]`);
            if (selector) selector.checked = true;
            if (currentType === 'autre') customTypeGroup.style.display = 'block';
        } else {
            document.getElementById('typeTaxi').checked = true;
            typeField.value = 'taxi';
        }
    }

    initializeType();

    typeSelector.forEach(radio => {
        radio.addEventListener('change', function() {
            typeField.value = this.value;
            customTypeGroup.style.display = this.value === 'autre' ? 'block' : 'none';
        });
    });

    if (customType) {
        customType.addEventListener('input', function() {
            typeField.value = this.value;
        });
    }

    // Gestion de l'upload d'image
    const uploadZone = document.getElementById('uploadZone');
    const imageInput = document.getElementById('imageInput');
    const imagePreview = document.getElementById('imagePreview');
    const previewContainer = document.getElementById('previewContainer');
    const removeImageBtn = document.getElementById('removeImage');
    const uploadContent = document.querySelector('.upload-content');

    if (uploadZone && imageInput) {
        // Fonction pour afficher la prévisualisation
        const showPreview = (file) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                previewContainer.style.display = 'block';
                uploadContent.style.display = 'none';
            };
            reader.readAsDataURL(file);
        };

        // Fonction pour réinitialiser
        const resetPreview = () => {
            imageInput.value = '';
            previewContainer.style.display = 'none';
            uploadContent.style.display = 'flex';
        };

        // Gestion du clic - Version améliorée
        uploadZone.addEventListener('click', function(e) {
            // Ne déclenche que si on clique sur la zone d'upload (pas sur les enfants)
            if (e.target === uploadZone || 
                e.target.classList.contains('upload-content') || 
                e.target.classList.contains('upload-icon') || 
                e.target.classList.contains('upload-text') || 
                e.target.classList.contains('upload-hint')) {
                imageInput.click();
            }
        });

        // Gestion du drag and drop améliorée
        ['dragover', 'dragenter'].forEach(event => {
            uploadZone.addEventListener(event, function(e) {
                e.preventDefault();
                uploadZone.classList.add('dragging');
            });
        });

        ['dragleave', 'dragend'].forEach(event => {
            uploadZone.addEventListener(event, function() {
                uploadZone.classList.remove('dragging');
            });
        });

        uploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadZone.classList.remove('dragging');
            if (e.dataTransfer.files.length) {
                const file = e.dataTransfer.files[0];
                if (file.type.match('image.*')) {
                    showPreview(file);
                    imageInput.files = e.dataTransfer.files; // Important pour le formulaire
                }
            }
        });

        // Gestion de la sélection de fichier
        imageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                showPreview(this.files[0]);
            }
        });

        // Gestion de la suppression d'image
        if (removeImageBtn) {
            removeImageBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                resetPreview();
            });
        }

        // Empêcher les clics sur l'image de déclencher l'upload
        if (imagePreview) {
            imagePreview.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    }
});