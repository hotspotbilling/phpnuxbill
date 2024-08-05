{include file="sections/header.tpl"}


<form id="site-search" method="post" action="{$_url}routers/maps">
    <input type="hidden" name="_route" value="routers/maps">
    <div class="input-group">
        <div class="input-group-addon">
            <span class="fa fa-search"></span>
        </div>
        <input type="text" name="name" class="form-control" value="{$name}"
            placeholder="{Lang::T('Search')}...">
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

            var routers = {/literal}{$d|json_encode}{literal};

            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/light_all/{z}/{x}/{y}.png', {
            attribution:
                '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
                subdomains: 'abcd',
                maxZoom: 20
            }).addTo(map);

            routers.forEach(function(router) {
                var name = router.name;
                var info = router.description;
                var coordinates = router.coordinates;
                console.log(coordinates.split(","))
                // Create a popup for the marker
                var popupContent = "<strong>Name</strong>: " + name + "<br>" +
                    "<strong>Info</strong>: " + info + "<br>"
                    "<a href='{/literal}{$_url}{literal}routers/edit/"+ router.id +"'>More Info</a> &bull; " +
                    "<a href='https://www.google.com/maps/dir//" + coordinates + "' target='maps'>Get Direction</a><br>";

                // Add marker to map
                if(router.enabled == 1){
                    var circle = L.circle(coordinates.split(","), router.coverage*1, {
                        color: 'blue',
                        fillOpacity: 0.1
                        }).addTo(map);
                }else{
                    var circle = L.circle(coordinates.split(","), router.coverage*1, {
                        color: 'red',
                        fillOpacity: 0.1
                        }).addTo(map);
                }
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