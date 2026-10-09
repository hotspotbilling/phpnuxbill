{include file="sections/header.tpl"}

<!-- TOP COMMAND BAR -->
<div class="row">
    <div class="col-xs-12">
        <div class="customer-view-header">
            <div class="customer-heading-meta">
                <a href="{Text::url('customers/view/', $d['id'])}" class="btn-back-round" title="{Lang::T('Back')}">
                    <i class="fa fa-arrow-left"></i>
                </a>
                <div class="customer-title-block">
                    <h3 class="customer-name-heading">
                        <span>{Lang::T('Edit Customer')}: {$d['fullname']}</span>
                        {if $d['status'] == 'Active'}
                            <span class="c-badge c-badge-success"><span class="c-dot c-dot-success"></span> {Lang::T('Active')}</span>
                        {else}
                            <span class="c-badge c-badge-danger"><span class="c-dot c-dot-danger"></span> {Lang::T($d['status'])}</span>
                        {/if}
                    </h3>
                    <div class="customer-subtitle">
                        <span class="customer-sub-item"><i class="fa fa-user-circle-o"></i> @{$d['username']}</span>
                        {if $d['phonenumber']}
                            <span class="customer-sub-divider">•</span>
                            <span class="customer-sub-item"><i class="fa fa-phone"></i> {$d['phonenumber']}</span>
                        {/if}
                        {if $d['email']}
                            <span class="customer-sub-divider">•</span>
                            <span class="customer-sub-item"><i class="fa fa-envelope-o"></i> {$d['email']}</span>
                        {/if}
                    </div>
                </div>
            </div>

            <div class="customer-action-toolbar">
                <a href="{Text::url('customers/view/', $d['id'])}" class="btn btn-default" title="{Lang::T('View Profile')}">
                    <i class="fa fa-user"></i> <span class="hidden-xs">{Lang::T('View Profile')}</span>
                </a>
                <a href="{Text::url('customers/delete/', $d['id'], '&token=', $csrf_token)}" id="{$d['id']}"
                    class="btn btn-danger"
                    onclick="return ask(this, '{Lang::T('Delete')}?')" title="{Lang::T('Delete')}">
                    <i class="fa fa-trash"></i> <span class="hidden-xs">{Lang::T('Delete')}</span>
                </a>
            </div>
        </div>
    </div>
</div>

<form method="post" enctype="multipart/form-data" role="form" action="{Text::url('customers/edit-post')}">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    <input type="hidden" name="id" value="{$d['id']}">

    <div class="row">
        <!-- LEFT COLUMN: ACCOUNT DETAILS & SECURITY -->
        <div class="col-md-7">
            <!-- Profile & Personal Details -->
            <div class="edit-form-card">
                <div class="edit-card-header">
                    <h4 class="edit-card-title"><i class="fa fa-user text-primary"></i> {Lang::T('Basic Information')}</h4>
                </div>
                <div class="edit-card-body">
                    <!-- Avatar & Upload -->
                    <div class="edit-avatar-section">
                        {if empty($d['photo']) || strpos($d['photo'], 'default') !== false}
                            <img src="{$app_url}/{$UPLOAD_PATH}/user.default.jpg"
                                class="edit-avatar-img"
                                alt="Photo">
                        {else}
                            <img src="{$app_url}/{$UPLOAD_PATH}{$d['photo']}.thumb.jpg"
                                onerror="this.src='{$app_url}/{$UPLOAD_PATH}/user.default.jpg'"
                                class="edit-avatar-img"
                                alt="Photo">
                        {/if}
                        <div class="edit-avatar-controls">
                            <label class="control-label" style="font-size: 13px; font-weight: 600; margin-bottom: 4px;">{Lang::T('Profile Photo')}</label>
                            <input type="file" class="form-control" name="photo" accept="image/*">
                            <div style="margin-top: 8px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                                <label class="checkbox-inline" style="margin: 0; font-size: 12px;">
                                    <input type="checkbox" checked name="faceDetect" value="yes"> {Lang::T("Face Detection")}
                                </label>
                                {if $d['photo'] != '' && strpos($d['photo'], 'default') === false}
                                    <a href="{Text::url('customers/edit/', $d['id'], '/deletePhoto')}"
                                        class="btn btn-danger btn-xs"
                                        onclick="return ask(this, '{Lang::T('Delete photo')}?')"
                                        title="{Lang::T('Delete Photo')}">
                                        <i class="fa fa-trash"></i> {Lang::T('Delete Photo')}
                                    </a>
                                {/if}
                            </div>
                        </div>
                    </div>

                    <!-- Username -->
                    <div class="form-group">
                        <label class="control-label">{Lang::T('Username')} <span class="text-danger">*</span></label>
                        <div class="input-group">
                            {if $_c['country_code_phone']!= ''}
                                <span class="input-group-addon"><i class="glyphicon glyphicon-phone-alt"></i></span>
                            {else}
                                <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                            {/if}
                            <input type="text" class="form-control" name="username" value="{$d['username']}" required
                                placeholder="{if $_c['country_code_phone']!= ''}{$_c['country_code_phone']} {Lang::T('Phone Number')}{else}{Lang::T('Username')}{/if}">
                        </div>
                    </div>

                    <!-- Full Name -->
                    <div class="form-group">
                        <label class="control-label">{Lang::T('Full Name')} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="fullname" name="fullname" value="{$d['fullname']}" required>
                    </div>

                    <!-- Email & Phone Number -->
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="control-label">{Lang::T('Email')}</label>
                                <input type="email" class="form-control" id="email" name="email" value="{$d['email']}">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="control-label">{Lang::T('Phone Number')}</label>
                                <div class="input-group">
                                    {if $_c['country_code_phone']!= ''}
                                        <span class="input-group-addon">+</span>
                                    {else}
                                        <span class="input-group-addon"><i class="glyphicon glyphicon-phone-alt"></i></span>
                                    {/if}
                                    <input type="text" class="form-control" name="phonenumber" value="{$d['phonenumber']}"
                                        placeholder="{if $_c['country_code_phone']!= ''}{$_c['country_code_phone']}{/if} {Lang::T('Phone Number')}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label class="control-label">{Lang::T('Password')}</label>
                        <div class="input-group">
                            <input type="password" autocomplete="off" class="form-control" id="password" name="password"
                                value="{$d['password']}">
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default" onclick="togglePasswordVisibility()" title="{Lang::T('Toggle Visibility')}">
                                    <i class="fa fa-eye" id="passwordEyeIcon"></i>
                                </button>
                                <button type="button" class="btn btn-default" onclick="generateRandomPassword()" title="{Lang::T('Generate Password')}">
                                    <i class="fa fa-refresh"></i> <span class="hidden-xs">{Lang::T('Generate')}</span>
                                </button>
                            </span>
                        </div>
                        <span class="help-block text-muted">{Lang::T('Keep Blank to do not change Password')}</span>
                    </div>

                    <!-- Service Type & Account Type -->
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="control-label">{Lang::T('Service Type')}</label>
                                <select class="form-control" id="service_type" name="service_type">
                                    <option value="Hotspot" {if $d['service_type'] eq 'Hotspot'}selected{/if}>Hotspot</option>
                                    <option value="PPPoE" {if $d['service_type'] eq 'PPPoE'}selected{/if}>PPPoE</option>
                                    <option value="VPN" {if $d['service_type'] eq 'VPN'}selected{/if}>VPN</option>
                                    <option value="Others" {if $d['service_type'] eq 'Others'}selected{/if}>{Lang::T("Other")}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="control-label">{Lang::T('Account Type')}</label>
                                <select class="form-control" id="account_type" name="account_type">
                                    <option value="Personal" {if $d['account_type'] eq 'Personal'}selected{/if}>{Lang::T("Personal")}</option>
                                    <option value="Business" {if $d['account_type'] eq 'Business'}selected{/if}>{Lang::T("Business")}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="form-group">
                        <label class="control-label">{Lang::T('Status')}</label>
                        <select class="form-control" id="status" name="status">
                            {foreach $statuses as $status}
                                <option value="{$status}" {if $d['status'] eq $status}selected{/if}>{Lang::T($status)}</option>
                            {/foreach}
                        </select>
                        <span class="help-block text-muted">
                            <i class="fa fa-info-circle"></i> <strong>{Lang::T('Banned')}:</strong> {Lang::T('Customer cannot login again')}.<br>
                            <strong>{Lang::T('Disabled')}:</strong> {Lang::T('Customer can login but cannot buy internet package, Admin cannot recharge customer')}.
                        </span>
                    </div>
                </div>
            </div>

            <!-- PPPoE Settings -->
            <div class="edit-form-card">
                <div class="edit-card-header">
                    <h4 class="edit-card-title"><i class="fa fa-plug text-primary"></i> {Lang::T('PPPoE Credentials')}</h4>
                </div>
                <div class="edit-card-body">
                    <div class="form-group">
                        <label class="control-label">
                            {Lang::T('Usernames')} <span class="label label-danger" id="warning_username"></span>
                        </label>
                        <input type="text" class="form-control" id="pppoe_username" name="pppoe_username"
                            onkeyup="checkUsername(this, {$d['id']})" value="{$d['pppoe_username']}">
                        <span class="help-block text-muted">{Lang::T('Not Working with Freeradius Mysql')}</span>
                    </div>

                    <div class="form-group">
                        <label class="control-label">{Lang::T('Password')}</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="pppoe_password" name="pppoe_password"
                                value="{$d['pppoe_password']}">
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default" onclick="togglePppoePasswordVisibility()">
                                    <i class="fa fa-eye" id="pppoeEyeIcon"></i>
                                </button>
                            </span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label">
                            Remote IP <span class="label label-danger" id="warning_ip"></span>
                        </label>
                        <input type="text" class="form-control" id="pppoe_ip" name="pppoe_ip"
                            onkeyup="checkIP(this, {$d['id']})" value="{$d['pppoe_ip']}">
                        <span class="help-block text-muted">{Lang::T('Not Working with Freeradius Mysql')}</span>
                    </div>

                    <div class="alert alert-info" style="margin-bottom: 0; padding: 10px 14px; font-size: 12px;">
                        <i class="fa fa-info-circle"></i> {Lang::T('User Cannot change this, only admin. if it Empty it will use Customer Credentials')}
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: ADDRESS, LOCATION & ATTRIBUTES -->
        <div class="col-md-5">
            <!-- Address & Geographic Location (Prominent & Clear!) -->
            <div class="edit-form-card">
                <div class="edit-card-header">
                    <h4 class="edit-card-title"><i class="fa fa-map-marker text-danger"></i> {Lang::T('Address & Geographic Location')}</h4>
                </div>
                <div class="edit-card-body">
                    <div class="form-group">
                        <label class="control-label">{Lang::T('Home / Street Address')}</label>
                        <textarea name="address" id="address" class="form-control" rows="3" placeholder="{Lang::T('Home Address')}">{$d['address']}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="control-label">{Lang::T('City')}</label>
                                <input type="text" class="form-control" id="city" name="city" value="{$d['city']}" placeholder="{Lang::T('City')}">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="control-label">{Lang::T('District')}</label>
                                <input type="text" class="form-control" id="district" name="district" value="{$d['district']}" placeholder="{Lang::T('District')}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="control-label">{Lang::T('State')}</label>
                                <input type="text" class="form-control" id="state" name="state" value="{$d['state']}" placeholder="{Lang::T('State')}">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="control-label">{Lang::T('Zip Code')}</label>
                                <input type="text" class="form-control" id="zip" name="zip" value="{$d['zip']}" placeholder="{Lang::T('Zip Code')}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label">{Lang::T('Coordinates')}</label>
                        <div class="input-group">
                            <input name="coordinates" id="coordinates" class="form-control" value="{$d['coordinates']}" placeholder="Latitude, Longitude">
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default" onclick="getLocation()" title="{Lang::T('Use Current Location')}">
                                    <i class="fa fa-crosshairs text-primary"></i> <span class="hidden-xs">{Lang::T('GPS')}</span>
                                </button>
                            </span>
                        </div>
                        <div id="map" class="edit-map-picker"></div>
                        <span class="help-block text-muted" style="margin-top: 6px;">
                            <i class="fa fa-hand-pointer-o"></i> {Lang::T('Click on the map or drag the pin to set customer coordinates.')}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Custom Attributes -->
            <div class="edit-form-card">
                <div class="edit-card-header">
                    <h4 class="edit-card-title"><i class="fa fa-tags text-primary"></i> {Lang::T('Attributes')}</h4>
                    <button class="btn btn-success btn-xs" type="button" id="add-custom-field">
                        <i class="fa fa-plus"></i> {Lang::T('Add Attribute')}
                    </button>
                </div>
                <div class="edit-card-body">
                    {if $customFields}
                        {foreach $customFields as $customField}
                            <div class="custom-field-row">
                                <div class="cf-name">
                                    <label class="control-label" for="cf_{$customField.field_name}">{$customField.field_name}</label>
                                </div>
                                <div class="cf-val">
                                    <input class="form-control" type="text" id="cf_{$customField.field_name}"
                                        name="custom_fields[{$customField.field_name}]" value="{$customField.field_value}">
                                </div>
                                <div class="cf-del">
                                    <label class="checkbox-inline text-danger" title="{Lang::T('Delete')}">
                                        <input type="checkbox" name="delete_custom_fields[]" value="{$customField.field_name}">
                                        <span class="hidden-xs">{Lang::T('Delete')}</span>
                                    </label>
                                </div>
                            </div>
                        {/foreach}
                    {/if}
                    <div id="custom-fields-container"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- BOTTOM ACTIONS -->
    <div class="row">
        <div class="col-xs-12">
            <div class="customer-edit-actions">
                <a href="{Text::url('customers/view/', $d['id'])}" class="btn btn-default">
                    {Lang::T('Cancel')}
                </a>
                <button class="btn btn-primary" onclick="return ask(this, '{Lang::T("Continue the Customer Data change process?")}')" type="submit">
                    <i class="fa fa-floppy-o"></i> {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>
</form>

<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>

{literal}
<script type="text/javascript">
    // Custom Fields management
    document.addEventListener("DOMContentLoaded", function() {
        var customFieldsContainer = document.getElementById('custom-fields-container');
        var addCustomFieldButton = document.getElementById('add-custom-field');

        if (addCustomFieldButton && customFieldsContainer) {
            addCustomFieldButton.addEventListener('click', function() {
                var newField = document.createElement('div');
                newField.className = 'custom-field-row';
                newField.innerHTML = `
                    <div class="cf-name">
                        <input type="text" class="form-control" name="custom_field_name[]" placeholder="Name">
                    </div>
                    <div class="cf-val">
                        <input type="text" class="form-control" name="custom_field_value[]" placeholder="Value">
                    </div>
                    <div class="cf-del">
                        <button type="button" class="btn btn-danger btn-sm remove-custom-field" title="Remove">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                `;
                customFieldsContainer.appendChild(newField);
            });

            customFieldsContainer.addEventListener('click', function(event) {
                var removeBtn = event.target.closest('.remove-custom-field');
                if (removeBtn) {
                    var fieldRow = removeBtn.closest('.custom-field-row');
                    if (fieldRow) {
                        fieldRow.remove();
                    }
                }
            });
        }

        // Live manual coordinate input listener
        var coordInput = document.getElementById('coordinates');
        if (coordInput) {
            coordInput.addEventListener('change', function() {
                var parts = this.value.split(',');
                if (parts.length === 2) {
                    var lat = parseFloat(parts[0].trim());
                    var lon = parseFloat(parts[1].trim());
                    if (!isNaN(lat) && !isNaN(lon)) {
                        setupMap(lat, lon);
                    }
                }
            });
        }
    });

    // Password helpers
    function togglePasswordVisibility() {
        var pwd = document.getElementById('password');
        var icon = document.getElementById('passwordEyeIcon');
        if (pwd.type === 'password') {
            pwd.type = 'text';
            icon.className = 'fa fa-eye-slash';
        } else {
            pwd.type = 'password';
            icon.className = 'fa fa-eye';
        }
    }

    function togglePppoePasswordVisibility() {
        var pwd = document.getElementById('pppoe_password');
        var icon = document.getElementById('pppoeEyeIcon');
        if (pwd.type === 'password') {
            pwd.type = 'text';
            icon.className = 'fa fa-eye-slash';
        } else {
            pwd.type = 'password';
            icon.className = 'fa fa-eye';
        }
    }

    function generateRandomPassword() {
        var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
        var res = '';
        for (var i = 0; i < 10; i++) {
            res += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        var pwd = document.getElementById('password');
        pwd.value = res;
        pwd.type = 'text';
        var icon = document.getElementById('passwordEyeIcon');
        if (icon) icon.className = 'fa fa-eye-slash';
    }

    // Leaflet Map Logic
    var editMap = null;
    var editMarker = null;

    function getLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                setupMap(position.coords.latitude, position.coords.longitude);
                $('#coordinates').val(position.coords.latitude.toFixed(6) + ', ' + position.coords.longitude.toFixed(6));
            }, function(err) {
                alert('Unable to fetch GPS position: ' + err.message);
            });
        } else {
            alert('Geolocation is not supported by your browser');
        }
    }

    function setupMap(lat, lon) {
        if (typeof L === 'undefined') {
            setTimeout(function() { setupMap(lat, lon); }, 200);
            return;
        }
        lat = parseFloat(lat);
        lon = parseFloat(lon);
        if (isNaN(lat) || isNaN(lon)) {
            lat = 51.505;
            lon = -0.09;
        }

        if (!editMap) {
            editMap = L.map('map').setView([lat, lon], 14);
            L.tileLayer('https://{s}.google.com/vt/lyrs=m&hl=en&x={x}&y={y}&z={z}&s=Ga', {
                subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                maxZoom: 20
            }).addTo(editMap);

            editMarker = L.marker([lat, lon], { draggable: true }).addTo(editMap);

            editMarker.on('dragend', function(e) {
                var pos = editMarker.getLatLng();
                $('#coordinates').val(pos.lat.toFixed(6) + ', ' + pos.lng.toFixed(6));
            });

            editMap.on('click', function(e) {
                var coord = e.latlng;
                editMarker.setLatLng(coord);
                $('#coordinates').val(coord.lat.toFixed(6) + ', ' + coord.lng.toFixed(6));
            });
        } else {
            editMap.setView([lat, lon], 14);
            editMarker.setLatLng([lat, lon]);
        }
    }
</script>
{/literal}

<script>
    window.addEventListener('load', function() {
        {if $d['coordinates']}
            var rawCoords = "{$d['coordinates']}".split(',');
            if (rawCoords.length === 2) {
                setupMap(rawCoords[0].trim(), rawCoords[1].trim());
            } else {
                setupMap(51.505, -0.09);
            }
        {else}
            setupMap(51.505, -0.09);
        {/if}
    });
</script>

{include file="sections/footer.tpl"}
