document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Handle details button click
    document.querySelectorAll('.btn-primary').forEach(button => {
        if (button.textContent.trim() === 'Détails') {
            button.addEventListener('click', function() {
                var modal = new bootstrap.Modal(document.getElementById('reservationDetailsModal'));
                modal.show();
            });
        }
    });

    // Handle price sorting
    document.querySelectorAll('.sortable a').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const sortDirection = this.getAttribute('data-sort');
            const th = this.closest('th');
            const table = th.closest('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));

            // Remove active class from all sort links
            th.querySelectorAll('a').forEach(a => a.classList.remove('active'));

            // Add active class to clicked link
            this.classList.add('active');

            // Sort rows based on price
            rows.sort((a, b) => {
                const priceA = parseFloat(a.querySelector('td:nth-child(4)').textContent.replace('€', ''));
                const priceB = parseFloat(b.querySelector('td:nth-child(4)').textContent.replace('€', ''));
                return sortDirection === 'asc' ? priceA - priceB : priceB - priceA;
            });

            // Reappend sorted rows
            rows.forEach(row => tbody.appendChild(row));

            // Update the sort indicator in the card header
            const cardHeader = table.closest('.card').querySelector('.card-header');
            const sortBadge = cardHeader.querySelector('.badge.bg-light');
            if (sortBadge) {
                sortBadge.innerHTML = `<i class="fa fa-sort-amount-${sortDirection === 'asc' ? 'up' : 'down'} me-1"></i> Prix ${sortDirection === 'asc' ? 'croissant' : 'décroissant'}`;
            }
        });
    });
});