document.addEventListener('DOMContentLoaded', function() {
    // Handle details button click
    document.querySelectorAll('.details-btn').forEach(button => {
        button.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('reservationDetailsModal'));
            const reservationType = this.dataset.reservationType;

            // Set basic info
            document.getElementById('modalReservationId').textContent =
                reservationType === 'voyage' ? 'VOY-' + this.dataset.reservationId :
                    reservationType === 'chambre' ? 'HEB-' + this.dataset.reservationId :
                        'TRP-' + this.dataset.reservationId;

            document.getElementById('modalReservationDate').textContent = this.dataset.date;
            document.getElementById('modalReservationStatus').textContent = this.dataset.status;
            document.getElementById('modalReservationPrice').textContent = this.dataset.price + '€';
            document.getElementById('modalReservationDescription').textContent = this.dataset.description || 'Aucune description disponible';

            // Clear previous details
            const detailsList = document.getElementById('modalReservationDetails');
            detailsList.innerHTML = '';

            // Add type-specific details
            if (reservationType === 'voyage') {
                addDetail(detailsList, 'Destination', this.dataset.destination);
                addDetail(detailsList, 'Nombre de places', this.dataset.places);
            }
            else if (reservationType === 'chambre') {
                addDetail(detailsList, 'Hébergement', this.dataset.hebergement);
                addDetail(detailsList, 'Durée', this.dataset.duration);
                addDetail(detailsList, 'Nombre de personnes', this.dataset.places);
            }
            else if (reservationType === 'transport') {
                addDetail(detailsList, 'Type', this.dataset.subtitle);
                addDetail(detailsList, 'Point de départ', this.dataset.pointdepart);
                addDetail(detailsList, 'Destination', this.dataset.destination);
                addDetail(detailsList, 'Nombre de places', this.dataset.places);
            }

            modal.show();
        });
    });

    // Print button in modal
    document.getElementById('printModalBtn')?.addEventListener('click', function() {
        const printContents = document.getElementById('reservationDetailsModal').innerHTML;
        const originalContents = document.body.innerHTML;

        document.body.innerHTML = `
            <div class="container mt-4">
                <h2 class="text-center mb-4">Détails de la réservation</h2>
                ${printContents}
            </div>
        `;

        window.print();
        document.body.innerHTML = originalContents;
        location.reload();
    });

    // Search and filter elements
    const searchInput = document.getElementById('searchInput');
    const typeFilter = document.getElementById('typeFilter');
    const statusFilter = document.getElementById('statusFilter');
    const sortOptions = document.querySelectorAll('.sort-option');

    // Debounce function to limit how often we process the search
    let debounceTimer;
    function debounce(callback, delay) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(callback, delay);
    }

    // Event listeners
    searchInput.addEventListener('input', function() {
        debounce(applyFilters, 300); // Wait 300ms after last keystroke
    });
    typeFilter.addEventListener('change', applyFilters);
    statusFilter.addEventListener('change', applyFilters);
    sortOptions.forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const sortType = this.getAttribute('data-sort');
            sortReservations(sortType);
        });
    });

    function applyFilters() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedType = typeFilter.value;
        const selectedStatus = statusFilter.value;

        // Get all reservation sections
        const sections = {
            voyage: document.getElementById('voyagesSection'),
            chambre: document.getElementById('hebergementsSection'),
            transport: document.getElementById('transportsSection')
        };

        // Process each section
        Object.entries(sections).forEach(([type, section]) => {
            if (!section) return;

            // Skip if not the selected type (unless "all" is selected)
            if (selectedType !== 'all' && selectedType !== type) {
                section.style.display = 'none';
                return;
            }

            // Show the section initially
            section.style.display = '';

            // Get all rows in this section
            const table = section.querySelector('table');
            if (!table) {
                section.style.display = 'none';
                return;
            }

            const rows = table.querySelectorAll('tbody tr');
            let visibleCount = 0;

            rows.forEach(row => {
                const matchesSearch = searchTerm === '' ||
                    row.getAttribute('data-search').includes(searchTerm);

                const rowStatus = row.querySelector('td:nth-child(5)').textContent.toLowerCase();
                const matchesStatus = selectedStatus === 'all' ||
                    (selectedStatus === 'confirmed' && rowStatus.includes('confirmé')) ||
                    (selectedStatus === 'pending' && rowStatus.includes('attente'));

                if (matchesSearch && matchesStatus) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Update count badge
            const countBadge = section.querySelector('.badge');
            if (countBadge) {
                countBadge.textContent = `${visibleCount} réservation${visibleCount !== 1 ? 's' : ''}`;
            }

            // Hide section if no visible rows
            if (visibleCount === 0) {
                section.style.display = 'none';
            }
        });
    }

    function sortReservations(sortType) {
        const selectedType = typeFilter.value;
        const sections = [];

        // Determine which sections to sort
        if (selectedType === 'all') {
            sections.push('voyage', 'chambre', 'transport');
        } else {
            sections.push(selectedType);
        }

        sections.forEach(type => {
            const section = document.getElementById(`${type}Section`);
            if (!section) return;

            const table = section.querySelector('table');
            if (!table) return;

            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr:not([style*="display: none"])'));

            rows.sort((a, b) => {
                const priceA = parseFloat(a.querySelector('td:nth-child(4)').getAttribute('data-price'));
                const priceB = parseFloat(b.querySelector('td:nth-child(4)').getAttribute('data-price'));

                const dateA = new Date(a.querySelector('td:nth-child(3)').getAttribute('data-date'));
                const dateB = new Date(b.querySelector('td:nth-child(3)').getAttribute('data-date'));

                switch(sortType) {
                    case 'price-asc': return priceA - priceB;
                    case 'price-desc': return priceB - priceA;
                    case 'date-recent': return dateB - dateA;
                    case 'date-oldest': return dateA - dateB;
                    default: return 0;
                }
            });

            // Re-append sorted rows
            rows.forEach(row => tbody.appendChild(row));
        });
    }

    function addDetail(container, label, value) {
        if (!value || value === 'N/A') return;

        const li = document.createElement('li');
        li.className = 'list-group-item d-flex justify-content-between';

        const spanLabel = document.createElement('span');
        spanLabel.textContent = label;

        const spanValue = document.createElement('span');
        spanValue.className = 'fw-bold';
        spanValue.textContent = value;

        li.appendChild(spanLabel);
        li.appendChild(spanValue);
        container.appendChild(li);
    }

    // Initialize filters on page load
    applyFilters();
});