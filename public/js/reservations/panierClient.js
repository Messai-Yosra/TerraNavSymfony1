document.addEventListener('DOMContentLoaded', function() {
    // Initialize all modals from the Twig template
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
    const detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));
    const editModal = new bootstrap.Modal(document.getElementById('editReservationModal'));
    const confirmEditModal = new bootstrap.Modal(document.getElementById('confirmEditModal'));

    const deleteForm = document.getElementById('deleteForm');
    let pendingFormData = null;

    // Toggle sections
    document.querySelectorAll('.toggle-reservations').forEach(button => {
        button.addEventListener('click', () => {
            const target = document.getElementById(button.dataset.target);
            target.classList.toggle('d-none');
            const icon = button.querySelector('i');
            icon.classList.toggle('fa-chevron-down');
            icon.classList.toggle('fa-chevron-up');
        });
    });

    // Delete button handlers
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const reservationId = this.getAttribute('data-reservation-id');
            const csrfToken = this.getAttribute('data-csrf-token');

            deleteForm.action = deleteForm.action.replace('RESERVATION_ID', reservationId);
            deleteForm.querySelector('input[name="_token"]').value = csrfToken;
            deleteModal.show();
        });
    });

    // Details button handlers
    document.querySelectorAll('.details-btn').forEach(button => {
        button.addEventListener('click', function() {
            const reservationId = this.getAttribute('data-reservation-id');
            const reservationType = this.getAttribute('data-reservation-type');
            const images = JSON.parse(this.getAttribute('data-images'));
            const title = this.getAttribute('data-title');
            const price = this.getAttribute('data-price');
            const date = this.getAttribute('data-date');
            const status = this.getAttribute('data-status');
            const places = this.getAttribute('data-places');

            // Set basic information
            document.getElementById('detailsModalLabel').textContent = title;
            document.getElementById('reservationTitle').textContent = title;
            document.getElementById('reservationType').textContent = reservationType.charAt(0).toUpperCase() + reservationType.slice(1);
            document.getElementById('reservationPrice').textContent = price;
            document.getElementById('reservationDate').textContent = date;
            document.getElementById('reservationStatus').textContent = status;
            document.getElementById('reservationPlaces').textContent = places;

            // Clear and rebuild carousel
            const carouselIndicators = document.getElementById('carouselIndicators');
            const carouselImages = document.getElementById('carouselImages');
            carouselIndicators.innerHTML = '';
            carouselImages.innerHTML = '';

            images.forEach((image, index) => {
                const indicator = document.createElement('button');
                indicator.type = 'button';
                indicator.setAttribute('data-bs-target', '#reservationCarousel');
                indicator.setAttribute('data-bs-slide-to', index);
                indicator.setAttribute('aria-label', 'Slide ' + (index + 1));
                if (index === 0) indicator.classList.add('active');
                carouselIndicators.appendChild(indicator);

                const carouselItem = document.createElement('div');
                carouselItem.classList.add('carousel-item');
                if (index === 0) carouselItem.classList.add('active');

                const img = document.createElement('img');
                img.src = image;
                img.classList.add('d-block', 'w-100');
                img.style.objectFit = 'cover';
                img.style.height = '300px';
                img.alt = 'Reservation image ' + (index + 1);

                carouselItem.appendChild(img);
                carouselImages.appendChild(carouselItem);
            });

            // Set type-specific details
            const specificDetail1 = document.getElementById('specificDetail1');
            const specificDetail2 = document.getElementById('specificDetail2');
            const additionalDetails = document.getElementById('additionalDetails');

            if (reservationType === 'voyage') {
                specificDetail1.innerHTML = `<strong>Destination:</strong> ${this.getAttribute('data-destination') || 'N/A'}`;
                additionalDetails.innerHTML = `
                    <h5 class="mt-4">Description du voyage</h5>
                    <p>Détails supplémentaires sur ce voyage passionnant...</p>
                `;
            } else if (reservationType === 'chambre') {
                specificDetail1.innerHTML = `<strong>Durée:</strong> ${this.getAttribute('data-duration') || 'N/A'}`;
                additionalDetails.innerHTML = `
                    <h5 class="mt-4">Description de l'hébergement</h5>
                    <p>Détails supplémentaires sur cet hébergement confortable...</p>
                `;
            } else if (reservationType === 'transport') {
                specificDetail1.innerHTML = `<strong>Places:</strong> ${places}`;
                additionalDetails.innerHTML = `
                    <h5 class="mt-4">Description du transport</h5>
                    <p>Détails supplémentaires sur ce moyen de transport...</p>
                `;
            }
            specificDetail2.innerHTML = '';

            detailsModal.show();
        });
    });

    // Edit button handlers
    document.querySelectorAll('.edit-reservation-btn').forEach(button => {
        button.addEventListener('click', function() {
            const reservationId = this.getAttribute('data-reservation-id');
            const reservationType = this.getAttribute('data-reservation-type');
            const images = JSON.parse(this.getAttribute('data-images') || '[]');
            const title = this.getAttribute('data-title');
            const price = this.getAttribute('data-price');
            const date = this.getAttribute('data-date');
            const status = this.getAttribute('data-status');
            const places = this.getAttribute('data-places');
            const nbJours = this.getAttribute('data-nb-jours');
            const destination = this.getAttribute('data-destination');
            const duration = this.getAttribute('data-duration');

            // Set basic information
            document.getElementById('editReservationModalLabel').textContent = `Modifier ${title}`;
            document.getElementById('editReservationTitle').textContent = title;
            document.getElementById('editReservationType').textContent = reservationType.charAt(0).toUpperCase() + reservationType.slice(1);
            document.getElementById('editPrix').value = price;
            document.getElementById('editDateReservation').value = date;
            document.getElementById('editReservationStatus').textContent = status;
            document.getElementById('editNbPlaces').value = places;
            document.getElementById('editReservationId').value = reservationId;

            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('editDateReservation').min = today;

            // Make price field read-only
            document.getElementById('editPrix').readOnly = true;

            // Clear and rebuild carousel
            const carouselIndicators = document.getElementById('editCarouselIndicators');
            const carouselImages = document.getElementById('editCarouselImages');
            carouselIndicators.innerHTML = '';
            carouselImages.innerHTML = '';

            images.forEach((image, index) => {
                const indicator = document.createElement('button');
                indicator.type = 'button';
                indicator.setAttribute('data-bs-target', '#editReservationCarousel');
                indicator.setAttribute('data-bs-slide-to', index);
                indicator.setAttribute('aria-label', 'Slide ' + (index + 1));
                if (index === 0) indicator.classList.add('active');
                carouselIndicators.appendChild(indicator);

                const carouselItem = document.createElement('div');
                carouselItem.classList.add('carousel-item');
                if (index === 0) carouselItem.classList.add('active');

                const img = document.createElement('img');
                img.src = image;
                img.classList.add('d-block', 'w-100');
                img.style.objectFit = 'cover';
                img.style.height = '300px';
                img.alt = 'Reservation image ' + (index + 1);

                carouselItem.appendChild(img);
                carouselImages.appendChild(carouselItem);
            });

            // Configure fields based on reservation type
            const nbPlacesContainer = document.getElementById('nbPlacesContainer');
            const nbJoursContainer = document.getElementById('editNbJoursContainer');

            if (reservationType === 'voyage') {
                nbPlacesContainer.style.display = 'block';
                nbJoursContainer.style.display = 'none';
                document.getElementById('editSpecificDetail1').innerHTML = `
                    <label class="form-label"><strong>Destination:</strong></label>
                    <span class="form-control-plaintext">${destination || 'N/A'}</span>
                `;
            } else if (reservationType === 'chambre') {
                nbPlacesContainer.style.display = 'none';
                nbJoursContainer.style.display = 'block';
                document.getElementById('editNbJours').value = nbJours;
                document.getElementById('editSpecificDetail1').innerHTML = `
                    <label class="form-label"><strong>Durée:</strong></label>
                    <span class="form-control-plaintext">${duration || 'N/A'}</span>
                `;
            } else if (reservationType === 'transport') {
                nbPlacesContainer.style.display = 'none';
                nbJoursContainer.style.display = 'none';
                document.getElementById('editSpecificDetail1').innerHTML = '';
            }

            document.getElementById('editSpecificDetail2').innerHTML = '';
            document.getElementById('editAdditionalDetails').innerHTML = `
                <h5 class="mt-4">Description</h5>
                <p>Détails supplémentaires sur cette réservation...</p>
            `;

            editModal.show();
        });
    });

    // Save reservation changes
    document.getElementById('saveReservationChanges').addEventListener('click', function() {
        const formData = new FormData(document.getElementById('editReservationForm'));
        const reservationId = document.getElementById('editReservationId').value;
        const reservationType = document.getElementById('editReservationType').textContent.toLowerCase();

        // Validate date
        const dateInput = document.getElementById('editDateReservation');
        const selectedDate = new Date(dateInput.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (selectedDate < today) {
            showNotification('La date doit être supérieure ou égale à aujourd\'hui', 'error');
            return;
        }

        // Validate type-specific fields
        if (reservationType === 'voyage') {
            const nbPlaces = document.getElementById('editNbPlaces').value;
            if (nbPlaces <= 0) {
                showNotification('Le nombre de places doit être positif', 'error');
                return;
            }
        } else if (reservationType === 'chambre') {
            const nbJours = document.getElementById('editNbJours').value;
            if (nbJours <= 0) {
                showNotification('Le nombre de jours doit être positif', 'error');
                return;
            }
        }

        // Store form data for later submission
        pendingFormData = formData;

        // Close edit modal first
        editModal.hide();

        // When edit modal is fully hidden, show confirmation
        document.getElementById('editReservationModal').addEventListener('hidden.bs.modal', function onEditModalHidden() {
            this.removeEventListener('hidden.bs.modal', onEditModalHidden);

            // Update confirmation message if needed
            document.getElementById('confirmEditMessage').textContent =
                'Êtes-vous sûr de vouloir modifier cette réservation ?';

            // Show confirmation modal
            confirmEditModal.show();
        }, {once: true});
    });

    // Handle confirmation button click
    document.getElementById('confirmEditButton').addEventListener('click', function() {
        if (!pendingFormData) return;

        const reservationId = pendingFormData.get('id');
        const saveButton = document.getElementById('saveReservationChanges');

        // Show loading state
        saveButton.disabled = true;
        saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enregistrement...';

        fetch(`/reservation/${reservationId}/update`, {
            method: 'POST',
            body: pendingFormData
        })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'Network response was not ok');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    confirmEditModal.hide();
                    showNotification('La réservation a été modifiée avec succès', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Error updating reservation');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                confirmEditModal.hide();
                showNotification(`Erreur: ${error.message}`, 'error');
                // Reopen edit modal so user can try again
                editModal.show();
            })
            .finally(() => {
                saveButton.disabled = false;
                saveButton.textContent = 'Enregistrer';
                pendingFormData = null;
            });
    });

    // Handle delete form submission
    deleteForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const url = this.action;

        // Show loading state on delete button
        const deleteButton = this.querySelector('button[type="submit"]');
        const originalText = deleteButton.innerHTML;
        deleteButton.disabled = true;
        deleteButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Suppression...';

        fetch(url, {
            method: 'POST',
            body: formData
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response;
            })
            .then(() => {
                // Hide the modal
                deleteModal.hide();

                // Show success notification
                showNotification('La réservation a été annulée avec succès', 'success');

                // Reload the page after a short delay
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            })
            .catch(error => {
                console.error('Error:', error);
                // Show error notification
                showNotification('Erreur lors de l\'annulation de la réservation', 'error');
            })
            .finally(() => {
                deleteButton.disabled = false;
                deleteButton.innerHTML = originalText;
            });
    });

    // Notification function
    function showNotification(message, type = 'success') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'position-fixed top-0 start-50 translate-middle-x mt-3';
        notification.style.zIndex = '9999';
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.3s ease';

        // Set styles based on type
        if (type === 'success') {
            notification.className += ' alert alert-success';
        } else {
            notification.className += ' alert alert-danger';
        }

        notification.textContent = message;

        // Add to body
        document.body.appendChild(notification);

        // Trigger animation
        setTimeout(() => {
            notification.style.opacity = '1';
        }, 10);

        // Auto-remove after 4 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 4000);
    }
});