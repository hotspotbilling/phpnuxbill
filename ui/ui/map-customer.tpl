{include file="sections/header.tpl"}

<!-- Map container div -->
<div id="map" style="width: '100%'; height: 600px; margin: 20px auto"></div>

{literal}
<script>
    window.onload = function() {
        var map = L.map('map').setView([51.505, -0.09], 13);
        var group = L.featureGroup().addTo(map);
        
        var customers = {/literal}{$customers|json_encode}{literal};

        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/light_all/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 20
        }).addTo(map);

        customers.forEach(function(customer) {
            var name = customer.id;
            var name = customer.name;
            var info = customer.info;
            var coordinates = customer.coordinates;
            var balance = customer.balance;
            var address = customer.address;

            // Create a popup for the marker
            var popupContent = "<strong>Customer Name</strong>: " + name + "<br>" +
                               "<strong>Customer Info</strong>: " + info + "<br>" +
                               "<strong>Customer Balance</strong>: " + balance + "<br>" +
                               "<strong>Address</strong>: " + address + "<br>" +
                               "<strong>Coordinates</strong>: " + coordinates + "<br>" +
                               "<a href='{/literal}{$_url}{literal}customers/view/"+ customer.id +"'>More Info</a><br>";

            // Add marker to map
            var marker = L.marker(JSON.parse(coordinates)).addTo(group);
            marker.bindTooltip(name).bindPopup(popupContent);
        });

        map.fitBounds(group.getBounds());
    }
</script>
{/literal}
{include file="sections/footer.tpl"}