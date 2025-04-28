document.addEventListener('DOMContentLoaded', function() {
    // Create main toggle button
    const toggleButton = document.createElement('button');
    toggleButton.className = 'stats-toggle-btn';
    toggleButton.innerHTML = '<i class="fas fa-chart-bar"></i> Afficher les statistiques';

    // Create controls container (will be hidden by default)
    const controlsContainer = document.createElement('div');
    controlsContainer.className = 'stats-controls hidden';

    // Create graph type selector
    const graphTypeSelect = document.createElement('select');
    graphTypeSelect.className = 'graph-type-select';
    graphTypeSelect.innerHTML = `
        <option value="total">Total par type</option>
        <option value="evolution">Évolution temporelle</option>
    `;

    // Create stats container
    const statsContainer = document.createElement('div');
    statsContainer.className = 'reservation-stats hidden';
    statsContainer.innerHTML = `
        <h3>Statistiques des Réservations</h3>
        <canvas id="reservationStatsChart"></canvas>
    `;

    // Assemble controls
    controlsContainer.appendChild(graphTypeSelect);

    // Insert into DOM
    const reservationsDisplay = document.querySelector('.reservations-display');
    if (reservationsDisplay) {
        reservationsDisplay.insertBefore(toggleButton, reservationsDisplay.firstChild);
        reservationsDisplay.insertBefore(controlsContainer, reservationsDisplay.children[1]);
        reservationsDisplay.insertBefore(statsContainer, reservationsDisplay.children[2]);
    }

    const ctx = document.getElementById('reservationStatsChart');
    let reservationChart = null;
    let isStatsVisible = false;
    let currentChartType = 'total';

    // Toggle statistics visibility
    toggleButton.addEventListener('click', function() {
        isStatsVisible = !isStatsVisible;
        if (isStatsVisible) {
            statsContainer.classList.remove('hidden');
            controlsContainer.classList.remove('hidden');
            toggleButton.innerHTML = '<i class="fas fa-chart-bar"></i> Masquer les statistiques';
            updateReservationChart();
        } else {
            statsContainer.classList.add('hidden');
            controlsContainer.classList.add('hidden');
            toggleButton.innerHTML = '<i class="fas fa-chart-bar"></i> Afficher les statistiques';
        }
    });

    // Handle graph type change
    graphTypeSelect.addEventListener('change', function() {
        currentChartType = this.value;
        updateReservationChart();
    });

    // User card selection
    const userCards = document.querySelectorAll('.user-card');
    userCards.forEach(card => {
        card.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const userData = reservationsData[userId];

            if (userData) {
                // Update active state
                userCards.forEach(c => c.classList.remove('active'));
                this.classList.add('active');

                // Update the user name in the header
                document.querySelector('.user-name').textContent = userData.name;

                // Update reservations list
                updateReservationsList(userData.reservations);

                // Update statistics chart if visible
                if (isStatsVisible) {
                    updateReservationChart();
                }
            }
        });
    });

    function updateReservationsList(reservations) {
        const reservationsList = document.querySelector('.reservations-list');
        reservationsList.innerHTML = '';

        reservations.forEach(reservation => {
            const iconClass =
                reservation.type === 'voyage' ? 'fa-plane' :
                    reservation.type === 'chambre' ? 'fa-hotel' :
                        reservation.type === 'transport' ? 'fa-car-side' : 'fa-calendar-check';

            const statusClass = reservation.status === 'Confirmée' ? 'confirmed' : 'pending';
            const dateParts = reservation.dates.split(' - ');
            const dateValue = dateParts[0].split('/').reverse().join('-');

            const reservationCard = document.createElement('div');
            reservationCard.className = `reservation-card`;
            reservationCard.setAttribute('data-status', statusClass);
            reservationCard.setAttribute('data-date', dateValue);
            reservationCard.setAttribute('data-reservation-id', reservation.id);

            reservationCard.innerHTML = `
                <div class="reservation-icon">
                    <i class="fas ${iconClass}"></i>
                </div>
                <div class="reservation-details">
                    <h3>${reservation.name}</h3>
                    <p class="reservation-date">${reservation.dates}</p>
                    <p class="reservation-status ${statusClass}">${reservation.status}</p>
                </div>
                <div class="reservation-actions">
                    <button class="action-btn view-btn" onclick="openReservationModal(${reservation.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            `;

            reservationsList.appendChild(reservationCard);
        });
    }

    function updateReservationChart() {
        // Get current user's reservations
        const reservations = userCards.length > 0 ?
            reservationsData[document.querySelector('.user-card.active').getAttribute('data-user-id')].reservations : [];

        // Destroy previous chart if exists
        if (reservationChart) {
            reservationChart.destroy();
        }

        if (currentChartType === 'total') {
            // Create total reservations by type chart
            const typeCounts = {
                'Voyages': 0,
                'Chambres': 0,
                'Transports': 0
            };

            reservations.forEach(reservation => {
                if (reservation.type === 'voyage') typeCounts['Voyages']++;
                else if (reservation.type === 'chambre') typeCounts['Chambres']++;
                else if (reservation.type === 'transport') typeCounts['Transports']++;
            });

            reservationChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: Object.keys(typeCounts),
                    datasets: [{
                        label: 'Nombre de réservations',
                        data: Object.values(typeCounts),
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(75, 192, 192, 0.7)'
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 99, 132, 1)',
                            'rgba(75, 192, 192, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Total des Réservations par Type',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.raw} réservations`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        } else {
            // Create time evolution chart
            const monthlyData = {};
            const types = ['voyage', 'chambre', 'transport'];
            const monthNames = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];

            reservations.forEach(reservation => {
                try {
                    // More robust date parsing
                    const dateStr = reservation.dates.split(' - ')[0];
                    if (!dateStr) return;

                    const [day, month, year] = dateStr.split('/').map(Number);
                    if (isNaN(day) || isNaN(month) || isNaN(year)) return;

                    const date = new Date(year, month - 1, day);
                    if (isNaN(date.getTime())) return;

                    const monthYear = `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}`;

                    if (!monthlyData[monthYear]) {
                        monthlyData[monthYear] = {
                            voyage: 0,
                            chambre: 0,
                            transport: 0
                        };
                    }

                    if (types.includes(reservation.type)) {
                        monthlyData[monthYear][reservation.type]++;
                    }
                } catch (e) {
                    console.error('Erreur de traitement de date:', reservation.dates, e);
                }
            });

            const sortedMonths = Object.keys(monthlyData).sort();
            const monthLabels = sortedMonths.map(month => {
                const [year, monthNum] = month.split('-');
                return `${monthNames[parseInt(monthNum) - 1]} ${year}`;
            });

            const datasets = types.map(type => {
                return {
                    label: type === 'voyage' ? 'Voyages' :
                        type === 'chambre' ? 'Chambres' : 'Transports',
                    data: sortedMonths.map(month => monthlyData[month][type]),
                    backgroundColor: type === 'voyage' ? 'rgba(54, 162, 235, 0.7)' :
                        type === 'chambre' ? 'rgba(255, 99, 132, 0.7)' :
                            'rgba(75, 192, 192, 0.7)',
                    borderColor: type === 'voyage' ? 'rgba(54, 162, 235, 1)' :
                        type === 'chambre' ? 'rgba(255, 99, 132, 1)' :
                            'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                    fill: false,
                    tension: 0.4
                };
            });

            reservationChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: monthLabels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        title: {
                            display: true,
                            text: 'Évolution des Réservations par Type',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }
    }

    // Initialize for first user
    if (userCards.length > 0) {
        const firstUserId = userCards[0].getAttribute('data-user-id');
        const firstUserData = reservationsData[firstUserId];
        if (firstUserData) {
            updateReservationsList(firstUserData.reservations);
        }
    }

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

    function filterAndSortReservations() {
        const statusValue = statusFilter.value;
        const sortValue = dateSort.value;
        const reservationCards = document.querySelectorAll('.reservation-card');

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

    // Close modal when clicking outside of it
    window.onclick = function(event) {
        const modal = document.getElementById('reservationModal');
        if (event.target === modal) {
            closeModal();
        }
    }
});

// Modal functions
function openReservationModal(reservationId) {
    // Find the reservation in our data
    let reservationData = null;
    let userData = null;

    // Search through all users to find this reservation
    for (const userId in reservationsData) {
        const user = reservationsData[userId];
        const reservation = user.reservations.find(r => r.id === reservationId);
        if (reservation) {
            reservationData = reservation;
            userData = user;
            break;
        }
    }

    if (reservationData && userData) {
        // Populate modal with data
        document.getElementById('modal-user').textContent = userData.name;
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
        document.getElementById('reservationModal').style.display = 'block';
    }
}

function closeModal() {
    document.getElementById('reservationModal').style.display = 'none';
}