{include file="sections/header.tpl"}


<form id="site-search" method="post" action="{Text::url('maps/routers')}">
    <div class="input-group">
        <div class="input-group-addon">
            <span class="fa fa-search"></span>
        </div>
        <input type="text" name="name" class="form-control" value="{$name|escape}" placeholder="{Lang::T('Search')}...">
        <div class="input-group-btn">
            <button class="btn btn-success" type="submit">{Lang::T('Search')}</button>
        </div>
    </div>
</form>

<!-- Map container div -->
<div id="map" class="well" style="width: 100%; height: 70vh; margin: 20px auto 8px"></div>
<p class="rt-map-key">
    {if $_c['router_check']}
    <span><i class="rt-map-dot" style="background:#16a34a"></i> {Lang::T('Online')}</span>
    <span><i class="rt-map-dot" style="background:#dc2626"></i> {Lang::T('Offline')}</span>
    {else}
    <span><i class="rt-map-dot" style="background:#2563eb"></i> {Lang::T('Enabled')}</span>
    {/if}
    <span><i class="rt-map-dot" style="background:#94a3b8"></i> {Lang::T('Disabled')}</span>
</p>

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

            // JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT: a closing script tag in a name cannot end this script
            var routers = {/literal}{$d|json_encode:15}{literal};
            var checked = {/literal}{if $_c['router_check']}true{else}false{/if}{literal};
            var words = {/literal}{ldelim}online: '{Lang::T('Online')|escape:'javascript'}', offline: '{Lang::T('Offline')|escape:'javascript'}', disabled: '{Lang::T('Disabled')|escape:'javascript'}', seen: '{Lang::T('Last Seen')|escape:'javascript'}', name: '{Lang::T('Name')|escape:'javascript'}', info: '{Lang::T('Info')|escape:'javascript'}', more: '{Lang::T('More Info')|escape:'javascript'}', way: '{Lang::T('Get Direction')|escape:'javascript'}'{rdelim}{literal};
            function esc(s) {
                return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                });
            }

            L.tileLayer('https://{s}.google.com/vt/lyrs=m&hl=en&x={x}&y={y}&z={z}&s=Ga', {
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                maxZoom: 20
        }).addTo(map);

        routers.forEach(function(router) {
            var coordinates = String(router.coordinates).split(',').map(Number);
            if (coordinates.length !== 2 || !isFinite(coordinates[0]) || !isFinite(coordinates[1])) return;
            // green online, red offline (when Router Check runs), blue enabled (when it does not), grey disabled
            var state = router.enabled != 1 ? 'disabled' : (checked ? (router.status === 'Offline' ? 'offline' : 'online') : 'enabled');
            var color = { online: '#16a34a', offline: '#dc2626', enabled: '#2563eb', disabled: '#94a3b8' }[state];
            var popupContent = '<strong>' + esc(words.name) + '</strong>: ' + esc(router.name) + '<br>'
                + (state !== 'enabled' ? '<strong>' + esc(words[state]) + '</strong>' + (router.last_seen ? ' &middot; ' + esc(words.seen) + ': ' + esc(router.last_seen) : '') + '<br>' : '')
                + (router.description ? '<strong>' + esc(words.info) + '</strong>: ' + esc(router.description) + '<br>' : '')
                + '<a href="{/literal}{Text::url('routers/edit/')}{literal}' + encodeURIComponent(router.id) + '">' + esc(words.more) + '</a> &bull; '
                + '<a href="https://www.google.com/maps/dir//' + encodeURIComponent(router.coordinates) + '" target="maps" rel="noopener">' + esc(words.way) + '</a><br>';
            L.circle(coordinates, (Number(router.coverage) || 0), { color: color, weight: 1, fillOpacity: 0.08 }).addTo(map);
            L.circleMarker(coordinates, { radius: 9, color: '#ffffff', weight: 2, fillColor: color, fillOpacity: 1 }).addTo(group)
                .bindTooltip(esc(router.name), { permanent: true, direction: 'top', offset: [0, -8] }).bindPopup(popupContent);
        });

        if (group.getLayers().length) {
            map.fitBounds(group.getBounds(), { maxZoom: 16 });
        }
        }
        window.onload = function() {
            getLocation();
        }
    </script>
{/literal}
{include file="sections/footer.tpl"}