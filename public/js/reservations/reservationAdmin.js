document.addEventListener('DOMContentLoaded', function() {
    // User card selection
    const userCards = document.querySelectorAll('.user-card');
    userCards.forEach(card => {
        card.addEventListener('click', function() {
            userCards.forEach(c => c.classList.remove('active'));
            this.classList.add('active');

            // Update the user name in the header
            const userName = this.querySelector('h4').textContent;
            document.querySelector('.user-name').textContent = userName;
        });
    });

    // Search functionality
    const userSearch = document.getElementById('userSearch');
    userSearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        userCards.forEach(card => {
            const name = card.querySelector('h4').textContent.toLowerCase();
            const email = card.querySelector('p').textContent.toLowerCase();
            if (name.includes(searchTerm) || email.includes(searchTerm)) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    });

    // Status filter functionality
    const statusFilter = document.getElementById('statusFilter');
    const dateSort = document.getElementById('dateSort');
    const reservationCards = document.querySelectorAll('.reservation-card');

    function filterAndSortReservations() {
        const statusValue = statusFilter.value;
        const sortValue = dateSort.value;

        // Filter by status
        reservationCards.forEach(card => {
            const cardStatus = card.getAttribute('data-status');

            if (statusValue === 'all' || cardStatus === statusValue) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });

        // Sort by date
        const visibleCards = Array.from(reservationCards).filter(card =>
            card.style.display !== 'none'
        );

        visibleCards.sort((a, b) => {
            const dateA = new Date(a.getAttribute('data-date'));
            const dateB = new Date(b.getAttribute('data-date'));

            return sortValue === 'newest' ?
                dateB - dateA :
                dateA - dateB;
        });

        // Reorder in DOM
        const container = document.querySelector('.reservations-list');
        visibleCards.forEach(card => {
            container.appendChild(card);
        });
    }

    statusFilter.addEventListener('change', filterAndSortReservations);
    dateSort.addEventListener('change', filterAndSortReservations);
});

// Modal functions
function openReservationModal(reservationId) {
    const modal = document.getElementById('reservationModal');
    const reservationData = window.reservationsData[Object.keys(window.reservationsData)[0]].reservations.find(r => r.id === reservationId);

    if (reservationData) {
        // Populate modal with data
        document.getElementById('modal-user').textContent = window.reservationsData[Object.keys(window.reservationsData)[0]].name;
        document.getElementById('modal-type').textContent =
            reservationData.type === 'voyage' ? 'Voyage' :
                reservationData.type === 'chambre' ? 'Hébergement' :
                    reservationData.type === 'transport' ? 'Transport' : 'Autre';
        document.getElementById('modal-name').textContent = reservationData.name;
        document.getElementById('modal-dates').textContent = reservationData.dates;
        document.getElementById('modal-places').textContent = reservationData.nb_places;
        document.getElementById('modal-status').textContent = reservationData.status;
        document.getElementById('modal-price').textContent = reservationData.price;
        document.getElementById('modal-reference').textContent = reservationData.reference;
        document.getElementById('modal-notes').textContent = reservationData.notes;

        // Show modal
        modal.style.display = 'block';
    }
}

function closeModal() {
    document.getElementById('reservationModal').style.display = 'none';
}

// Close modal when clicking outside of it
window.onclick = function(event) {
    const modal = document.getElementById('reservationModal');
    if (event.target == modal) {
        closeModal();
    }
}