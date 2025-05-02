mapboxgl.accessToken = 'pk.eyJ1IjoicmF5ZW5zb3Vpc3NpIiwiYSI6ImNtOW80N2I0NTBxMjQya3NlZzAwc3Z3ejkifQ.eXvnFdbOmqiC7X5B2_087g'; // <-- Mets ton vrai token ici

let map;

document.addEventListener('DOMContentLoaded', function() {
    const openMapButton = document.getElementById('openMap');
    const mapContainer = document.getElementById('mapContainer');

    if (openMapButton) {
        openMapButton.addEventListener('click', function() {
            mapContainer.style.display = 'block';

            if (!map) {
                map = new mapboxgl.Map({
                    container: 'map',
                    style: 'mapbox://styles/mapbox/streets-v11',
                    center: [10.1658, 36.8065], // Exemple : Tunis
                    zoom: 5
                });

                const geocoder = new MapboxGeocoder({
                    accessToken: mapboxgl.accessToken,
                    mapboxgl: mapboxgl,
                    marker: false,
                    placeholder: 'Rechercher un lieu...'
                });

                document.getElementById('mapSearch').addEventListener('keydown', function(event) {
                    if (event.key === "Enter") {
                        event.preventDefault();
                    }
                });

                map.addControl(geocoder);

                let departureMarker = null;
                let destinationMarker = null;
                let isSelectingDeparture = true;

                map.on('click', function(e) {
                    const lngLat = e.lngLat;

                    if (isSelectingDeparture) {
                        if (departureMarker) departureMarker.remove();

                        departureMarker = new mapboxgl.Marker({ color: 'blue' })
                            .setLngLat([lngLat.lng, lngLat.lat])
                            .addTo(map);

                        const departureInput = document.getElementById('form_departure');
                        if (departureInput) {
                            departureInput.value = `${lngLat.lng},${lngLat.lat}`;
                        }

                        Swal.fire({
                            title: "Point de départ sélectionné",
                            text: "Cliquez à nouveau pour choisir la destination.",
                            icon: "info"
                        });

                        isSelectingDeparture = false;
                    } else {
                        if (destinationMarker) destinationMarker.remove();

                        destinationMarker = new mapboxgl.Marker({ color: 'red' })
                            .setLngLat([lngLat.lng, lngLat.lat])
                            .addTo(map);

                        const destinationInput = document.getElementById('form_destination');
                        if (destinationInput) {
                            destinationInput.value = `${lngLat.lng},${lngLat.lat}`;
                        }

                        Swal.fire({
                            title: "Destination sélectionnée",
                            text: "Les deux points sont sélectionnés !",
                            icon: "success"
                        });

                        isSelectingDeparture = true;
                        mapContainer.scrollIntoView({ behavior: 'smooth' });
                    }
                });

                geocoder.on('result', function(e) {
                    map.flyTo({ center: e.result.center, zoom: 12 });
                });
            }
        });
    }
});
