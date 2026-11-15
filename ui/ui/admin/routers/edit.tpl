{include file="sections/header.tpl"}
<!-- routers-edit -->

{include file="admin/routely-vpn-intro.tpl" rl_for="router"}

{include file="admin/routers/form.tpl" nf_edit=true}

<script src="{$app_url}/ui/ui/scripts/leaflet/leaflet.js"></script>
{literal}
<script>
    // the router's place on the map: a click moves it and fills Coordinates; Coverage is the circle's radius in meters
    var circle;
    function updateCoverage() {
        if (circle) {
            circle.setRadius(Number(document.getElementById('coverage').value) || 0);
        }
    }
    function setupMap(lat, lon) {
        var map = L.map('map').setView([lat, lon], 13);
        L.tileLayer('https://{s}.google.com/vt/lyrs=m&hl=en&x={x}&y={y}&z={z}&s=Ga', {
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
            maxZoom: 20
        }).addTo(map);
        circle = L.circle([lat, lon], 5, { color: '#4f46e5', fillOpacity: 0.1 }).addTo(map);
        var marker = L.marker([lat, lon]).addTo(map);
        map.on('click', function (e) {
            marker.setLatLng(e.latlng);
            circle.setLatLng(e.latlng);
            document.getElementById('coordinates').value = e.latlng.lat + ',' + e.latlng.lng;
            updateCoverage();
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
    window.addEventListener('load', function () {
        var c = document.getElementById('coordinates').value.split(',').map(function (v) { return parseFloat(v); });
        if (c.length === 2 && isFinite(c[0]) && isFinite(c[1])) {
            setupMap(c[0], c[1]);
        } else {
            getLocation();
        }
    });
</script>
{/literal}
{include file="sections/footer.tpl"}
