document.addEventListener('DOMContentLoaded', function() {
    // Initialize all modals
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
    const detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));
    const editModal = new bootstrap.Modal(document.getElementById('editReservationModal'));
    const confirmEditModal = new bootstrap.Modal(document.getElementById('confirmEditModal'));
    const paymentModal = new bootstrap.Modal(document.getElementById('paymentConfirmationModal'));

    const deleteForm = document.getElementById('deleteForm');
    const paymentForm = document.getElementById('paymentForm');
    const proceedToPaymentBtn = document.getElementById('proceedToPayment');
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
            const subtitle = this.getAttribute('data-subtitle') || '';
            const price = this.getAttribute('data-price');
            const date = this.getAttribute('data-date');
            const status = this.getAttribute('data-status');
            const places = this.getAttribute('data-places');
            const nbJours = this.getAttribute('data-nb-jours');
            const pointDepart = this.getAttribute('data-pointdepart');
            const destination = this.getAttribute('data-destination');
            const description = this.getAttribute('data-description');

            // Set basic information
            document.getElementById('detailsModalLabel').textContent = title;
            document.getElementById('reservationTitle').textContent = title;
            if (subtitle) {
                document.getElementById('reservationSubtitle').textContent = subtitle;
                document.getElementById('reservationSubtitle').style.display = 'block';
            } else {
                document.getElementById('reservationSubtitle').style.display = 'none';
            }
            document.getElementById('reservationType').textContent = reservationType.charAt(0).toUpperCase() + reservationType.slice(1);
            document.getElementById('reservationPrice').textContent = price;
            document.getElementById('reservationDate').textContent = date;
            document.getElementById('reservationStatus').textContent = status;

            if (reservationType === 'voyage') {
                document.getElementById('reservationPlaces').textContent = places;
            } else if (reservationType === 'chambre') {
                document.getElementById('reservationPlaces').textContent = nbJours;
            }

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
                specificDetail1.innerHTML = `<strong>Destination:</strong> ${destination || 'N/A'}`;
                specificDetail2.innerHTML = '';
                additionalDetails.innerHTML = `
                    <h5 class="mt-4">Description du voyage</h5>
                    <p>${description || 'Aucune description disponible'}</p>
                `;
            } else if (reservationType === 'chambre') {
                specificDetail1.innerHTML = `<strong>Durée:</strong> ${nbJours || '1'} jours`;
                specificDetail2.innerHTML = `<strong>Hébergement:</strong> ${this.getAttribute('data-hebergement') || 'N/A'}`;
                additionalDetails.innerHTML = `
                    <h5 class="mt-4">Description du chambre</h5>
                    <p>${description || 'Aucune description disponible'}</p>
                `;
            } else if (reservationType === 'transport') {
                specificDetail1.innerHTML = `<strong>Point de départ:</strong> ${pointDepart || 'N/A'}`;
                specificDetail2.innerHTML = `<strong>Destination:</strong> ${destination || 'N/A'}`;
                additionalDetails.innerHTML = `
                    <h5 class="mt-4">Description du transport</h5>
                    <p>${description || 'Aucune description disponible'}</p>
                `;
            }

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
            const subtitle = this.getAttribute('data-subtitle') || '';
            const price = this.getAttribute('data-price');
            const date = this.getAttribute('data-date');
            const status = this.getAttribute('data-status');
            const places = this.getAttribute('data-places');
            const nbJours = this.getAttribute('data-nb-jours');
            const voyagePlaces = this.getAttribute('data-voyage-places');
            const pointDepart = this.getAttribute('data-pointdepart');
            const destination = this.getAttribute('data-destination');
            const description = this.getAttribute('data-description');

            // Reset all event listeners to prevent duplicates
            document.getElementById('editNbPlaces').replaceWith(document.getElementById('editNbPlaces').cloneNode(true));
            document.getElementById('editNbJours').replaceWith(document.getElementById('editNbJours').cloneNode(true));

            // Set basic information
            document.getElementById('editReservationModalLabel').textContent = `Modifier ${title}`;
            document.getElementById('editReservationTitle').textContent = title;
            document.getElementById('editReservationTypeDisplay').textContent = reservationType.charAt(0).toUpperCase() + reservationType.slice(1);
            document.getElementById('editPrix').textContent = price + ' TND';
            document.getElementById('editDate').value = date;
            document.getElementById('editReservationStatus').textContent = status;
            document.getElementById('editNbPlaces').value = places;
            document.getElementById('editNbJours').value = nbJours || 1;
            document.getElementById('editReservationId').value = reservationId;
            document.getElementById('editReservationType').value = reservationType;

            if (subtitle) {
                document.getElementById('editReservationSubtitle').textContent = subtitle;
                document.getElementById('editReservationSubtitle').style.display = 'block';
            } else {
                document.getElementById('editReservationSubtitle').style.display = 'none';
            }

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
            const dateContainer = document.getElementById('editDateContainer');

            if (reservationType === 'voyage') {
                nbPlacesContainer.style.display = 'block';
                nbJoursContainer.style.display = 'none';
                dateContainer.style.display = 'none';

                const currentReservationPlaces = parseInt(places) || 1;
                const availablePlaces = parseInt(voyagePlaces) + currentReservationPlaces;

                document.getElementById('availablePlacesInfo').textContent =
                    `(Disponibles: ${availablePlaces}, Actuellement réservées: ${currentReservationPlaces})`;

                document.getElementById('editNbPlaces').max = availablePlaces;
                document.getElementById('editNbPlaces').value = currentReservationPlaces;

                // Calculate and store price per place
                const originalPrice = parseFloat(price);
                const pricePerPlace = originalPrice / currentReservationPlaces;
                document.getElementById('editNbPlaces').setAttribute('data-price-per-place', pricePerPlace);

                // Add event listener for dynamic price calculation
                document.getElementById('editNbPlaces').addEventListener('input', function() {
                    const pricePerPlace = parseFloat(this.getAttribute('data-price-per-place'));
                    const newPlaces = parseInt(this.value) || 0;
                    const newPrice = pricePerPlace * newPlaces;
                    document.getElementById('editPrix').textContent = newPrice.toFixed(2) + ' TND';
                });
            } else if (reservationType === 'chambre') {
                nbPlacesContainer.style.display = 'none';
                nbJoursContainer.style.display = 'block';
                dateContainer.style.display = 'block';

                // Calculate and store price per day
                const originalPrice = parseFloat(price);
                const originalDays = parseInt(nbJours) || 1;
                const pricePerDay = originalPrice / originalDays;
                document.getElementById('editNbJours').setAttribute('data-price-per-day', pricePerDay);

                // Add event listener for dynamic price calculation
                document.getElementById('editNbJours').addEventListener('input', function() {
                    const pricePerDay = parseFloat(this.getAttribute('data-price-per-day'));
                    const newDays = parseInt(this.value) || 0;
                    const newPrice = pricePerDay * newDays;
                    document.getElementById('editPrix').textContent = newPrice.toFixed(2) + ' TND';
                });
            } else if (reservationType === 'transport') {
                nbPlacesContainer.style.display = 'none';
                nbJoursContainer.style.display = 'none';
                dateContainer.style.display = 'block';
            }

            // Set additional details
            let additionalDetailsHTML = '';
            if (reservationType === 'transport') {
                additionalDetailsHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Point de départ:</strong> ${pointDepart || 'N/A'}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Destination:</strong> ${destination || 'N/A'}</p>
                        </div>
                    </div>
                `;
            }
            additionalDetailsHTML += `
                <h5 class="mt-4">Description</h5>
                <p>${description || 'Aucune description disponible'}</p>
            `;
            document.getElementById('editAdditionalDetails').innerHTML = additionalDetailsHTML;

            editModal.show();
        });
    });

    // Save reservation changes
    document.getElementById('saveReservationChanges').addEventListener('click', function() {
        const formData = new FormData(document.getElementById('editReservationForm'));
        const reservationId = document.getElementById('editReservationId').value;
        const reservationType = document.getElementById('editReservationType').value;

        // Validate date for chambre and transport
        if (reservationType === 'chambre' || reservationType === 'transport') {
            const dateValue = document.getElementById('editDate').value;
            if (!dateValue) {
                showNotification('La date est requise', 'error');
                return;
            }

            const selectedDate = new Date(dateValue);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (selectedDate < today) {
                showNotification('La date doit être aujourd\'hui ou dans le futur', 'error');
                return;
            }
        }

        // Validate type-specific fields and update price
        if (reservationType === 'voyage') {
            const nbPlaces = parseInt(document.getElementById('editNbPlaces').value);
            if (nbPlaces <= 0) {
                showNotification('Le nombre de places doit être positif', 'error');
                return;
            }

            // Update price in form data based on current calculation
            const pricePerPlace = parseFloat(document.getElementById('editNbPlaces').getAttribute('data-price-per-place'));
            const newPrice = pricePerPlace * nbPlaces;
            formData.set('prix', newPrice.toString());
        } else if (reservationType === 'chambre') {
            const nbJours = parseInt(document.getElementById('editNbJours').value);
            if (nbJours <= 0) {
                showNotification('Le nombre de jours doit être positif', 'error');
                return;
            }

            // Update price in form data based on current calculation
            const pricePerDay = parseFloat(document.getElementById('editNbJours').getAttribute('data-price-per-day'));
            const newPrice = pricePerDay * nbJours;
            formData.set('prix', newPrice.toString());
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

    // Payment button handler
    proceedToPaymentBtn.addEventListener('click', () => {
        paymentModal.show();
    });

    // Payment form submission
    paymentForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const url = this.action;
        const confirmBtn = this.querySelector('#confirmPaymentButton');
        const originalText = confirmBtn.innerHTML;

        // Show loading state
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Traitement...';

        fetch(url, {
            method: 'POST',
            body: formData
        })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(err.message || 'Payment failed'); });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    paymentModal.hide();
                    showNotification(`Paiement confirmé! ${data.count} réservations mises à jour`, 'success');

                    // Reload the page after a delay to show updated statuses
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    throw new Error(data.message || 'Payment confirmation failed');
                }
            })
            .catch(error => {
                console.error('Payment error:', error);
                showNotification(`Erreur de paiement: ${error.message}`, 'error');
            })
            .finally(() => {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = originalText;
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
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                // First check if the response is JSON
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                }
                // If not JSON but response is ok, return success
                if (response.ok) {
                    return { success: true };
                }
                // Otherwise throw error
                throw new Error('Network response was not ok');
            })
            .then(data => {
                if (data.success) {
                    // Hide the modal
                    deleteModal.hide();

                    // Show success notification
                    showNotification('La réservation a été annulée avec succès', 'success');

                    // Reload the page after a short delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Delete failed');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Show error notification
                showNotification('Erreur lors de l\'annulation de la réservation: ' + error.message, 'error');
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