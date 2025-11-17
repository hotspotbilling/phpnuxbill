{include file="sections/header.tpl"}

<div class="row">
	<div class="col-sm-12 col-md-12">
	    <div class="panel panel-primary panel-hovered panel-stacked mb30">
		    <div class="panel-heading">{Lang::T('Edit Optical Distribution Points')}</div>
		    <div class="panel-body">

                <form class="form-horizontal" method="post" role="form" action="{Text::url('')}odp/edit-post" >
                    <input type="hidden" name="id" value="{$d['id']}">
                    <div class="form-group">
						<label class="col-md-2 control-label">{Lang::T('Name')}</label>
						<div class="col-md-6">
							<input type="text" class="form-control" id="name" name="name" value="{$d['name']}" readonly>
						</div>
                    </div>
                    <div class="form-group">
						<label class="col-md-2 control-label">{Lang::T('Port Amount')}</label>
						<div class="col-md-6">
							<input type="number" class="form-control" id="port_amount" name="port_amount" placeholder="8" max="16" value="{$d['port_amount']}">
						</div>
                    </div>
                    <div class="form-group">
						<label class="col-md-2 control-label">{Lang::T('Attenuation')}</label>
						<div class="col-md-6">
							<input type="text" class="form-control" id="attenuation" name="attenuation" placeholder="-18.15" value="{$d['attenuation']}">
						</div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Address')}</label>
                        <div class="col-md-6">
                            <textarea name="address" id="address" class="form-control">{$d['address']}</textarea>
                        </div>
                    </div>
                    <div class="form-group">
						<label class="col-md-2 control-label">{Lang::T('Coordinates')}</label>
						<div class="col-md-6">
							<input type="text" name="coordinates" id="coordinates" class="form-control"
                                   placeholder="6.465422, 3.406448" value="{$d['coordinates']}">
                            <div id="map" class="mt-2" style="width: 100%; height: 200px; min-height: 150px;"></div>
						</div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Coverage (meters)')}</label>
                        <div class="col-md-6">
                            <input type="number" name="coverage" id="coverage" class="form-control"
                                   placeholder="500" value="{$d['coverage']}">
                        </div>
                    </div>
					<div class="form-group">
						<div class="col-lg-offset-2 col-lg-10">
							<button class="btn btn-primary" onclick="return ask(this, '{Lang::T("Continue the ODP addition process?")}')" type="submit">{Lang::T('Save Changes')}</button>
							Or <a href="{Text::url('')}odp/list">{Lang::T('Cancel')}</a>
						</div>
					</div>
                </form>
			</div>
		</div>
	</div>
</div>

{literal}
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
    <script>
        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(showPosition);
            } else {
                setupMap(51.505, -0.09);
            }
        }

        function showPosition(position) {
            setupMap(position.coords.latitude, position.coords.longitude);
            document.getElementById('coordinates').value = position.coords.latitude + ',' + position.coords.longitude;
        }

        function setupMap(lat, lon) {
            var map = L.map('map').setView([lat, lon], 13);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/light_all/{z}/{x}/{y}.png', {
                attribution:
                    '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
                    subdomains: 'abcd',
                    maxZoom: 20
            }).addTo(map);
            var marker = L.marker([lat, lon]).addTo(map);
            map.on('click', function(e){
                var coord = e.latlng;
                var lat = coord.lat;
                var lng = coord.lng;
                var newLatLng = new L.LatLng(lat, lng);
                marker.setLatLng(newLatLng);
                $('#coordinates').val(lat + ',' + lng);
            });
        }
        window.onload = function() {
            {/literal}
            {if $d['coordinates']}
                setupMap({$d['coordinates']});
                document.getElementById('coordinates').value = "{$d['coordinates']}";
            {else}
                getLocation();
            {/if}
            {literal}
        }
    </script>
{/literal}

{include file="sections/footer.tpl"}
