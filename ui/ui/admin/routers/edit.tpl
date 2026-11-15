{include file="sections/header.tpl"}
<!-- routers-edit -->

{include file="admin/routely-vpn-intro.tpl" rl_for="router"}

{include file="admin/routers/form.tpl" nf_edit=true}

<script src="{$app_url}/ui/ui/scripts/leaflet/leaflet.js"></script>
{literal}
<script>
    // the router's place on the map: a click moves it and fills Coordinates, typed coordinates move it on the map;
    // Coverage is the circle's radius in meters. The map, marker and circle are made once and moved after that.
    var map, marker, circle;
    function updateCoverage() {
        if (circle) {
            circle.setRadius(Number(document.getElementById('coverage').value) || 0);
        }
    }
    // "lat, lng" when both are numbers in range, otherwise null
    function readCoordinates(v) {
        var c = String(v).split(',').map(function (x) { return x.trim() === '' ? NaN : Number(x); });
        if (c.length !== 2 || !isFinite(c[0]) || !isFinite(c[1]) || Math.abs(c[0]) > 90 || Math.abs(c[1]) > 180) return null;
        return c;
    }
    function moveTo(lat, lon, pan) {
        marker.setLatLng([lat, lon]);
        circle.setLatLng([lat, lon]);
        if (pan) map.setView([lat, lon], map.getZoom());
        updateCoverage();
    }
    function setupMap(lat, lon) {
        map = L.map('map').setView([lat, lon], 13);
        L.tileLayer('https://{s}.google.com/vt/lyrs=m&hl=en&x={x}&y={y}&z={z}&s=Ga', {
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
            maxZoom: 20
        }).addTo(map);
        circle = L.circle([lat, lon], 5, { color: '#4f46e5', fillOpacity: 0.1 }).addTo(map);
        marker = L.marker([lat, lon]).addTo(map);
        map.on('click', function (e) {
            document.getElementById('coordinates').value = e.latlng.lat + ',' + e.latlng.lng;
            moveTo(e.latlng.lat, e.latlng.lng, false);
        });
        updateCoverage();
    }
    function getLocation() {
        if (window.location.protocol == 'https:' && navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function (p) {
                setupMap(p.coords.latitude, p.coords.longitude);
            }, function () {
                setupMap(51.505, -0.09);
            });
        } else {
            setupMap(51.505, -0.09);
        }
    }
    document.getElementById('coverage').addEventListener('input', updateCoverage);
    document.getElementById('coordinates').addEventListener('change', function () {
        var c = readCoordinates(this.value);
        if (c && map) moveTo(c[0], c[1], true);
    });
    window.addEventListener('load', function () {
        var c = readCoordinates(document.getElementById('coordinates').value);
        if (c) {
            setupMap(c[0], c[1]);
        } else {
            getLocation();
        }
    });
</script>
{/literal}
{include file="sections/footer.tpl"}
