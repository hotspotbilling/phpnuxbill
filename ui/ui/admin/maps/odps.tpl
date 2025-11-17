{include file="sections/header.tpl"}


<form id="site-search" method="post" action="{Text::url('')}odp/maps">
    <input type="hidden" name="_route" value="odp/maps">
    <div class="input-group">
        <div class="input-group-addon">
            <span class="fa fa-search"></span>
        </div>
        <input type="text" name="name" class="form-control" value="{$name}" placeholder="{Lang::T('Search')}...">
        <div class="input-group-btn">
            <button class="btn btn-success" type="submit">{Lang::T('Search')}</button>
        </div>
    </div>
</form>

<!-- Map container div -->
<div id="map" class="well" style="width: '100%'; height: 70vh; margin: 20px auto"></div>

{include file="pagination.tpl"}

{literal}
    <script>
        function getLocation() {
            if (window.location.protocol == "https:" && navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(showPosition);
            } else {
                setupMap(51.505, -0.09);
            }
        }

        function showPosition(position) {
            setupMap(position.coords.latitude, position.coords.longitude);
        }

        function setupMap(lat, lon) {
            var map = L.map('map').setView([lat, lon], 9);
            var group = L.featureGroup().addTo(map);

            var odps = {/literal}{$d|json_encode}{literal};

            L.tileLayer('https://{s}.google.com/vt/lyrs=m&hl=en&x={x}&y={y}&z={z}&s=Ga', {
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                maxZoom: 20
        }).addTo(map);

        odps.forEach(function(odp) {
            var name = odp.name;
            var port_amount = odp.port_amount;
            var attenuation = odp.attenuation;
            var coordinates = odp.coordinates;
            var address = odp.address;
            var coverage = odp.coverage;
            console.log(coordinates.split(","))
            // Create a popup for the marker
            var popupContent = "<strong>Name</strong>: " + name + "<br>" +
                "<strong>Port Amount</strong>: " + port_amount + "<br>" +
                "<strong>Attenuation</strong>: " + attenuation + "<br>" +
                "<strong>Address</strong>: " + address + "<br>" +
                "<strong>Coverage</strong>: " + coverage + " meters<br>" +
                "<strong>Coordinates</strong>: " + coordinates + "<br>" +
                "<a href='{/literal}{Text::url('odp/edit/')}{literal}"+ odp.id +"'>More Info</a> &bull; " +
                "<a href='https://www.google.com/maps/dir//" + coordinates +
                "' target='maps'>Get Direction</a><br>";

            var circle = L.circle(coordinates.split(","), odp.coverage * 1, {
                color: 'blue',
                fillOpacity: 0.1
            }).addTo(map);

            var marker = L.marker(coordinates.split(",")).addTo(group);
            marker.bindTooltip(name, { permanent: true }).bindPopup(popupContent);
        });

        map.fitBounds(group.getBounds());
        }
        window.onload = function() {
            getLocation();
        }
    </script>
{/literal}
{include file="sections/footer.tpl"}