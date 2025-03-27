document.addEventListener('DOMContentLoaded', function() {
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

    // Delete confirmation modal
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
    const deleteForm = document.getElementById('deleteForm');

    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const reservationId = this.getAttribute('data-reservation-id');
            const csrfToken = this.getAttribute('data-csrf-token');

            // Update form action with the correct reservation ID
            deleteForm.action = deleteForm.action.replace('RESERVATION_ID', reservationId);
            deleteForm.querySelector('input[name="_token"]').value = csrfToken;

            // Show the modal
            deleteModal.show();
        });
    });

    // Details modal handling
    const detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));

    document.querySelectorAll('.details-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Get all data attributes
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

            // Clear previous carousel items
            const carouselIndicators = document.getElementById('carouselIndicators');
            const carouselImages = document.getElementById('carouselImages');
            carouselIndicators.innerHTML = '';
            carouselImages.innerHTML = '';

            // Add images to carousel
            images.forEach((image, index) => {
                // Create indicator
                const indicator = document.createElement('button');
                indicator.type = 'button';
                indicator.setAttribute('data-bs-target', '#reservationCarousel');
                indicator.setAttribute('data-bs-slide-to', index);
                indicator.setAttribute('aria-label', 'Slide ' + (index + 1));
                if (index === 0) indicator.classList.add('active');
                carouselIndicators.appendChild(indicator);

                // Create carousel item
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

            // Set specific details based on reservation type
            const specificDetail1 = document.getElementById('specificDetail1');
            const specificDetail2 = document.getElementById('specificDetail2');
            const additionalDetails = document.getElementById('additionalDetails');

            if (reservationType === 'voyage') {
                specificDetail1.innerHTML = `<strong>Destination:</strong> ${this.getAttribute('data-destination') || 'N/A'}`;
                specificDetail2.innerHTML = '';
                additionalDetails.innerHTML = `
                    <h5 class="mt-4">Description du voyage</h5>
                    <p>Détails supplémentaires sur ce voyage passionnant...</p>
                `;
            } else if (reservationType === 'chambre') {
                specificDetail1.innerHTML = `<strong>Durée:</strong> ${this.getAttribute('data-duration') || 'N/A'}`;
                specificDetail2.innerHTML = '';
                additionalDetails.innerHTML = `
                    <h5 class="mt-4">Description de l'hébergement</h5>
                    <p>Détails supplémentaires sur cet hébergement confortable...</p>
                `;
            } else if (reservationType === 'transport') {
                specificDetail1.innerHTML = `<strong>Places:</strong> ${places}`;
                specificDetail2.innerHTML = '';
                additionalDetails.innerHTML = `
                    <h5 class="mt-4">Description du transport</h5>
                    <p>Détails supplémentaires sur ce moyen de transport...</p>
                `;
            }

            // Show the modal
            detailsModal.show();
        });
    });
});