(function () {
    function initMap(element, data) {
        if (typeof L === 'undefined') {
            element.textContent = (window.igsTourMapStrings && window.igsTourMapStrings.missingLeaflet) || '';
            return;
        }

        var map = L.map(element, { scrollWheelZoom: false, tap: true });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors'
        }).addTo(map);

        var points = [];

        data.points.forEach(function (point, index) {
            if (!point.lat || !point.lon) {
                return;
            }

            var marker = L.marker([point.lat, point.lon], {
                icon: L.divIcon({
                    className: 'igs-tour-map__pin',
                    html: '<span>' + (index + 1) + '</span>',
                    iconSize: [32, 32],
                    popupAnchor: [0, -16]
                })
            }).addTo(map);

            var popup = '<strong>' + (point.name || '') + '</strong>';

            if (point.description) {
                popup += '<br>' + point.description;
            }

            marker.bindPopup(popup, { maxWidth: 260 });
            points.push([point.lat, point.lon]);
        });

        if (points.length) {
            L.polyline(points, { color: '#0c5764', dashArray: '5,10' }).addTo(map);
            map.fitBounds(points, { padding: [20, 20] });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.igs-tour-map').forEach(function (element) {
            var json = element.getAttribute('data-map');
            if (!json) {
                return;
            }

            try {
                var data = JSON.parse(json);
                if (data && Array.isArray(data.points) && data.points.length) {
                    initMap(element, data);
                }
            } catch (error) {
                console.error('Invalid map data', error);
            }
        });
    });
})();
