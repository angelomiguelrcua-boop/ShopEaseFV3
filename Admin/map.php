<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supermarket Map</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #map { height: 500px; width: 100%; }
        .map-container {
            position: relative;
        }
        .close-map {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            background: white;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="map-container">
        <h2>Supermarket Navigation</h2>
        <div id="map"></div>
        <div class="close-map" onclick="window.history.back();">Close Map</div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Initialize map
        var map = L.map('map').setView([5, 5], 2);
        
        // Load the store floor plan
        var supermarketMap = L.imageOverlay('supermarket_map.png', [[0, 0], [10, 10]]).addTo(map);
        map.fitBounds([[0, 0], [10, 10]]);
        
        // Get product location from URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const targetAisle = urlParams.get('aisle');
        const productName = urlParams.get('product');
        
        if (targetAisle) {
            // Define aisle coordinates (match these with your Python code)
            const aisleCoordinates = {
                '1': [2, 8],  // Example coordinates - adjust to match your map
                '2': [5, 5],
                '3': [8, 2],
                '4': [8, 8]
            };
            
            // Add target marker
            if (aisleCoordinates[targetAisle]) {
                const targetPos = aisleCoordinates[targetAisle];
                L.marker(targetPos, {
                    icon: L.divIcon({
                        className: 'target-marker',
                        html: '<div style="background:red;color:white;padding:5px;border-radius:50%;">'+(productName||'Target')+'</div>',
                        iconSize: [30, 30]
                    })
                }).addTo(map).bindPopup(productName || 'Target Location');
                
                // Add pulsing effect
                setInterval(() => {
                    const pulsingIcon = L.divIcon({
                        className: 'pulsing-marker',
                        html: '<div style="background:rgba(255,0,0,0.3);border:2px solid red;border-radius:50%;width:30px;height:30px;position:absolute;top:-15px;left:-15px;animation:pulse 2s infinite;"></div>',
                        iconSize: [0, 0]
                    });
                    L.marker(targetPos, {icon: pulsingIcon}).addTo(map);
                }, 2000);
            }
            
            // Check for user position updates
            let userMarker = null;
            let userPath = L.polyline([], {color: 'blue'}).addTo(map);
            
            function updateUserPosition() {
                fetch('get_user_position.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.x && data.y) {
                            const userPos = [data.y/100, data.x/100]; // Scale to map coordinates
                            
                            if (!userMarker) {
                                userMarker = L.marker(userPos, {
                                    icon: L.divIcon({
                                        className: 'user-marker',
                                        html: '<div style="background:blue;color:white;padding:5px;border-radius:50%;">You</div>',
                                        iconSize: [30, 30]
                                    })
                                }).addTo(map);
                            } else {
                                userMarker.setLatLng(userPos);
                            }
                            
                            // Update path
                            const currentPath = userPath.getLatLngs();
                            currentPath.push(userPos);
                            userPath.setLatLngs(currentPath);
                            
                            // Center map on user
                            map.setView(userPos, 2);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
            
            // Update position every 2 seconds
            updateUserPosition();
            setInterval(updateUserPosition, 2000);
        }
        
        // Add CSS for pulsing effect
        const style = document.createElement('style');
        style.innerHTML = `
            @keyframes pulse {
                0% { transform: scale(0.8); opacity: 0.8; }
                70% { transform: scale(1.3); opacity: 0; }
                100% { transform: scale(0.8); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>