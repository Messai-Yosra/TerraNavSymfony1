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
        const saveButton = document.getElementById('saveReservationChanges');

        // Get the dynamically calculated price
        const currentPrice = document.getElementById('editPrix').textContent;
        const priceValue = parseFloat(currentPrice.replace(' TND', ''));
        formData.append('prix', priceValue.toString());

        // Show loading state
        saveButton.disabled = true;
        saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enregistrement...';

        fetch(`/reservation/${reservationId}/update`, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(async response => {
                const contentType = response.headers.get('content-type');

                if (!response.ok) {
                    if (contentType && contentType.includes('application/json')) {
                        const errorData = await response.json();
                        throw new Error(errorData.message || 'Erreur du serveur');
                    } else {
                        const text = await response.text();
                        throw new Error(text || 'Erreur du serveur');
                    }
                }

                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                }

                return response.text();
            })
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Error updating reservation');
                }
            })
            .catch(error => {
                console.error('Error:', error);

                let errorMessage = error.message;
                if (errorMessage.startsWith('<!DOCTYPE') || errorMessage.startsWith('<!--')) {
                    errorMessage = "Une erreur s'est produite. Veuillez réessayer.";
                }

                showNotification(`Erreur: ${errorMessage}`, 'error');
                editModal.show();
            })
            .finally(() => {
                saveButton.disabled = false;
                saveButton.textContent = 'Enregistrer';
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
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                }
                if (response.ok) {
                    return { success: true };
                }
                throw new Error('Network response was not ok');
            })
            .then(data => {
                if (data.success) {
                    deleteModal.hide();
                    showNotification('La réservation a été annulée avec succès', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Delete failed');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Erreur lors de l\'annulation de la réservation: ' + error.message, 'error');
            })
            .finally(() => {
                deleteButton.disabled = false;
                deleteButton.innerHTML = originalText;
            });
    });

    // Notification function
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = 'position-fixed top-0 start-50 translate-middle-x mt-3';
        notification.style.zIndex = '9999';
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.3s ease';

        if (type === 'success') {
            notification.className += ' alert alert-success';
        } else {
            notification.className += ' alert alert-danger';
        }

        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.opacity = '1';
        }, 10);

        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 4000);
    }
});