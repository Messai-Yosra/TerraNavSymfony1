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
    // In a real application, you would fetch reservation details from your backend
    // Here we're just simulating with different data based on the ID
    const modal = document.getElementById('reservationModal');

    // Sample data - in reality you would get this from your backend
    const reservationData = {
        1: {
            user: "Jean Dupont",
            type: "Hôtel",
            name: "Hôtel Resort Plage",
            dates: "15-20 juin 2023",
            status: "Confirmée",
            price: "€850",
            reference: "RES-2023-1564",
            notes: "Chambre avec vue mer, petit-déjeuner inclus"
        },
        2: {
            user: "Jean Dupont",
            type: "Vol",
            name: "Vol pour Paris",
            dates: "14 juin 2023",
            status: "Confirmée",
            price: "€320",
            reference: "RES-2023-1565",
            notes: "Vol direct, siège fenêtre, bagage en soute inclus"
        },
        3: {
            user: "Jean Dupont",
            type: "Forfait",
            name: "Forfait Vacances d'été",
            dates: "1-10 août 2023",
            status: "En attente",
            price: "€1,200",
            reference: "RES-2023-1566",
            notes: "En attente de confirmation des disponibilités"
        }
    };

    // Populate modal with data
    const data = reservationData[reservationId];
    document.getElementById('modal-user').textContent = data.user;
    document.getElementById('modal-type').textContent = data.type;
    document.getElementById('modal-name').textContent = data.name;
    document.getElementById('modal-dates').textContent = data.dates;
    document.getElementById('modal-status').textContent = data.status;
    document.getElementById('modal-price').textContent = data.price;
    document.getElementById('modal-reference').textContent = data.reference;
    document.getElementById('modal-notes').textContent = data.notes;

    // Show modal
    modal.style.display = 'block';
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