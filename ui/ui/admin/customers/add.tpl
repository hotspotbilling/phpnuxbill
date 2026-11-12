{include file="sections/header.tpl"}

<!-- TOP COMMAND BAR -->
<div class="row">
    <div class="col-xs-12">
        <div class="customer-view-header">
            <div class="customer-heading-meta">
                <a href="{Text::url('customers/list')}" class="btn-back-round" title="{Lang::T('Back')}">
                    <i class="fa fa-arrow-left"></i>
                </a>
                <div class="customer-title-block">
                    <h3 class="customer-name-heading">
                        <span>{Lang::T('Add New Customer')}</span>
                        <span class="c-badge c-badge-primary"><span class="c-dot c-dot-success"></span> {Lang::T('New')}</span>
                    </h3>
                    <div class="customer-subtitle">
                        <span class="customer-sub-item">{Lang::T('Register and onboard a new subscriber')}</span>
                    </div>
                </div>
            </div>

            <div class="customer-action-toolbar">
                <a href="{Text::url('customers/list')}" class="btn btn-default">
                    <i class="fa fa-times"></i> <span class="hidden-xs">{Lang::T('Cancel')}</span>
                </a>
                <button type="submit" form="customerAddForm" class="btn btn-primary">
                    <i class="fa fa-check"></i> <span>{Lang::T('Save Customer')}</span>
                </button>
            </div>
        </div>
    </div>
</div>

<form id="customerAddForm" method="post" role="form" action="{Text::url('customers/add-post')}">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">

    <div class="row">
        <!-- LEFT COLUMN: ACCOUNT CREDENTIALS & PPPOE -->
        <div class="col-md-7">
            <!-- Basic Information Card -->
            <div class="edit-form-card">
                <div class="edit-card-header">
                    <h4 class="edit-card-title"><i class="fa fa-user text-primary"></i> {Lang::T('Basic Information')}</h4>
                </div>
                <div class="edit-card-body">
                    <!-- Username -->
                    <div class="form-group">
                        <label class="control-label">{Lang::T('Username')} <span class="text-danger">*</span></label>
                        <div class="input-group">
                            {if $_c['country_code_phone'] != ''}
                                <span class="input-group-addon"><i class="glyphicon glyphicon-phone-alt"></i></span>
                            {else}
                                <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                            {/if}
                            <input type="text" class="form-control" name="username" required
                                placeholder="{if $_c['country_code_phone']!= ''}{$_c['country_code_phone']} {Lang::T('Phone Number')}{else}{Lang::T('Usernames')}{/if}">
                        </div>
                    </div>

                    <!-- Full Name -->
                    <div class="form-group">
                        <label class="control-label">{Lang::T('Full Name')} <span class="text-danger">*</span></label>
                        <input type="text" required class="form-control" id="fullname" name="fullname" placeholder="{Lang::T('Full Name')}">
                    </div>

                    <!-- Email & Phone (Grid) -->
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="control-label">{Lang::T('Email')}</label>
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com">
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="control-label">{Lang::T('Phone Number')}</label>
                                <div class="input-group">
                                    {if $_c['country_code_phone'] != ''}
                                        <span class="input-group-addon">+</span>
                                    {else}
                                        <span class="input-group-addon"><i class="glyphicon glyphicon-phone-alt"></i></span>
                                    {/if}
                                    <input type="text" class="form-control" name="phonenumber"
                                        placeholder="{if $_c['country_code_phone']!= ''}{$_c['country_code_phone']}{/if} {Lang::T('Phone Number')}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Password with Generator and Toggle -->
                    <div class="form-group">
                        <label class="control-label">{Lang::T('Password')} <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" autocomplete="off" required id="password"
                                value="{rand(000000,999999)}" name="password">
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default" onclick="togglePassword('password', 'passwordEyeIcon')" title="{Lang::T('Show / Hide Password')}">
                                    <i class="fa fa-eye" id="passwordEyeIcon"></i>
                                </button>
                                <button type="button" class="btn btn-default" onclick="generateRandomPassword()" title="{Lang::T('Generate Random Password')}">
                                    <i class="fa fa-random text-primary"></i> <span class="hidden-xs">{Lang::T('Generate')}</span>
                                </button>
                            </span>
                        </div>
                        <span class="help-block text-muted">{Lang::T('Leave default random password or set a custom secret.')}</span>
                    </div>

                    <!-- Service Type & Account Type (Grid) -->
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="control-label">{Lang::T('Service Type')}</label>
                                <select class="form-control" id="service_type" name="service_type">
                                    <option value="Hotspot">Hotspot</option>
                                    <option value="PPPoE">PPPoE</option>
                                    <option value="VPN">VPN</option>
                                    <option value="Others">{Lang::T('Other')}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="control-label">{Lang::T('Account Type')}</label>
                                <select class="form-control" id="account_type" name="account_type">
                                    <option value="Personal">{Lang::T('Personal')}</option>
                                    <option value="Business">{Lang::T('Business')}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PPPoE Settings Card -->
            <div class="edit-form-card">
                <div class="edit-card-header">
                    <h4 class="edit-card-title"><i class="fa fa-sliders text-info"></i> PPPoE {Lang::T('Configuration')}</h4>
                    <span class="label label-default">{Lang::T('Optional')}</span>
                </div>
                <div class="edit-card-body">
                    <!-- PPPoE Username -->
                    <div class="form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                            <label class="control-label" style="margin: 0;">{Lang::T('PPPoE Username')}</label>
                            <span class="label label-danger" id="warning_username"></span>
                        </div>
                        <input type="text" class="form-control" id="pppoe_username" name="pppoe_username"
                            placeholder="{Lang::T('Leave blank to use main customer username')}"
                            onkeyup="checkUsername(this, '0')">
                        <span class="help-block text-muted">{Lang::T('Not Working for freeradius')}</span>
                    </div>

                    <!-- PPPoE Password -->
                    <div class="form-group">
                        <label class="control-label">{Lang::T('PPPoE Password')}</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="pppoe_password" name="pppoe_password"
                                placeholder="{Lang::T('Leave blank to use main customer password')}">
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default" onclick="togglePassword('pppoe_password', 'pppoeEyeIcon')" title="{Lang::T('Show / Hide Password')}">
                                    <i class="fa fa-eye" id="pppoeEyeIcon"></i>
                                </button>
                            </span>
                        </div>
                    </div>

                    <!-- Remote IP -->
                    <div class="form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                            <label class="control-label" style="margin: 0;">Remote IP</label>
                            <span class="label label-danger" id="warning_ip"></span>
                        </div>
                        <input type="text" class="form-control" id="pppoe_ip" name="pppoe_ip"
                            placeholder="e.g. 10.10.10.25"
                            onkeyup="checkIP(this, '0')">
                        <span class="help-block text-muted">{Lang::T('Also Working for freeradius')}</span>
                    </div>

                    <div class="alert alert-info" style="margin-bottom: 0; font-size: 12px; padding: 10px 12px;">
                        <i class="fa fa-info-circle"></i> {Lang::T('User Cannot change this, only admin. if it Empty it will use Customer Credentials')}
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: LOCATION, NOTIFICATIONS & ATTRIBUTES -->
        <div class="col-md-5">
            <!-- Address & Geographic Location Card -->
            <div class="edit-form-card">
                <div class="edit-card-header">
                    <h4 class="edit-card-title"><i class="fa fa-map-marker text-danger"></i> {Lang::T('Address & Geographic Location')}</h4>
                </div>
                <div class="edit-card-body">
                    <div class="form-group">
                        <label class="control-label">{Lang::T('Home / Street Address')}</label>
                        <textarea name="address" id="address" class="form-control" rows="2" placeholder="{Lang::T('Home Address')}"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="control-label">{Lang::T('City')}</label>
                                <input type="text" class="form-control" id="city" name="city" placeholder="{Lang::T('City of Resident')}">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="control-label">{Lang::T('District')}</label>
                                <input type="text" class="form-control" id="district" name="district" placeholder="{Lang::T('District')}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="control-label">{Lang::T('State')}</label>
                                <input type="text" class="form-control" id="state" name="state" placeholder="{Lang::T('State of Resident')}">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="control-label">{Lang::T('Zip Code')}</label>
                                <input type="text" class="form-control" id="zip" name="zip" placeholder="{Lang::T('Zip Code')}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label">{Lang::T('Coordinates')}</label>
                        <div class="input-group">
                            <input name="coordinates" id="coordinates" class="form-control" value="" placeholder="-6.465422, 3.406448">
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default" onclick="getLocation()" title="{Lang::T('Use Current Location')}">
                                    <i class="fa fa-crosshairs text-primary"></i> <span class="hidden-xs">{Lang::T('GPS')}</span>
                                </button>
                            </span>
                        </div>
                        <div id="map" class="edit-map-picker"></div>
                        <span class="help-block text-muted" style="margin-top: 6px;">
                            <i class="fa fa-map-marker"></i> {Lang::T('Click on the map or drag the pin to set customer coordinates.')}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Welcome Notification Card -->
            <div class="edit-form-card">
                <div class="edit-card-header">
                    <h4 class="edit-card-title"><i class="fa fa-paper-plane text-warning"></i> {Lang::T('Welcome Notification')}</h4>
                </div>
                <div class="edit-card-body">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                        <div>
                            <strong style="display: block; font-size: 13px;">{Lang::T('Send welcome message')}</strong>
                            <small class="text-muted">{Lang::T('Notify subscriber with their login credentials')}</small>
                        </div>
                        <label class="switch" style="margin: 0;">
                            <input type="checkbox" id="send_welcome_message" value="1" name="send_welcome_message">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div id="method" style="display: none; padding-top: 10px; border-top: 1px dashed var(--cv-border, #e2e8f0);">
                        <label class="control-label" style="margin-bottom: 8px;">{Lang::T('Notification via')}:</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 14px;">
                            <label class="checkbox-inline" style="margin: 0; font-weight: 500;">
                                <input type="checkbox" name="sms" value="1"> <i class="fa fa-comment"></i> {Lang::T('SMS')}
                            </label>
                            <label class="checkbox-inline" style="margin: 0; font-weight: 500;">
                                <input type="checkbox" name="wa" value="1"> <i class="fa fa-whatsapp text-success"></i> {Lang::T('WA')}
                            </label>
                            <label class="checkbox-inline" style="margin: 0; font-weight: 500;">
                                <input type="checkbox" name="mail" value="1"> <i class="fa fa-envelope text-primary"></i> {Lang::T('Email')}
                            </label>
                            <label class="checkbox-inline" style="margin: 0; font-weight: 500;">
                                <input type="checkbox" name="inbox" value="1"> <i class="fa fa-inbox text-info"></i> {Lang::T('Inbox')}
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Custom Attributes Card -->
            <div class="edit-form-card">
                <div class="edit-card-header">
                    <h4 class="edit-card-title"><i class="fa fa-tags text-primary"></i> {Lang::T('Attributes')}</h4>
                    <button class="btn btn-success btn-xs" type="button" id="add-custom-field">
                        <i class="fa fa-plus"></i> {Lang::T('Add Attribute')}
                    </button>
                </div>
                <div class="edit-card-body">
                    <div id="custom-fields-container">
                        <!-- Dynamic Custom Fields appended here -->
                    </div>
                    <span class="help-block text-muted" id="no-attributes-hint" style="margin-bottom: 0;">
                        <i class="fa fa-info-circle"></i> {Lang::T('Add custom key-value pairs (e.g. ONT Serial, Port, VLAN).')}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- BOTTOM ACTION BAR -->
    <div class="customer-edit-actions">
        <a href="{Text::url('customers/list')}" class="ls-btn">
            <i class="fa fa-times" aria-hidden="true"></i> {Lang::T('Cancel')}
        </a>
        <button class="ls-btn ls-btn--primary" type="submit"
            onclick="return ask(this, '{Lang::T("Continue the process of adding Customer Data?")}')">
            <i class="fa fa-check" aria-hidden="true"></i> {Lang::T('Save Customer')}
        </button>
    </div>
</form>

<script src="{$app_url}/ui/ui/scripts/leaflet/leaflet.js"></script>

{literal}
<script type="text/javascript">
    // Password toggles and generator
    function togglePassword(inputId, iconId) {
        var pwd = document.getElementById(inputId);
        var icon = document.getElementById(iconId);
        if (!pwd) return;
        if (pwd.type === 'password') {
            pwd.type = 'text';
            if (icon) icon.className = 'fa fa-eye-slash';
        } else {
            pwd.type = 'password';
            if (icon) icon.className = 'fa fa-eye';
        }
    }

    function generateRandomPassword() {
        var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
        var res = '';
        for (var i = 0; i < 10; i++) {
            res += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        var pwd = document.getElementById('password');
        if (pwd) {
            pwd.value = res;
            pwd.type = 'text';
            var icon = document.getElementById('passwordEyeIcon');
            if (icon) icon.className = 'fa fa-eye-slash';
        }
    }

    // Welcome message toggle handler
    document.addEventListener('DOMContentLoaded', function() {
        var sendWelcomeCheckbox = document.getElementById('send_welcome_message');
        var methodSection = document.getElementById('method');

        function toggleMethodSection() {
            if (sendWelcomeCheckbox && methodSection) {
                methodSection.style.display = sendWelcomeCheckbox.checked ? 'block' : 'none';
            }
        }

        if (sendWelcomeCheckbox) {
            toggleMethodSection();
            sendWelcomeCheckbox.addEventListener('change', toggleMethodSection);
        }

        var form = document.getElementById('customerAddForm');
        if (form) {
            form.addEventListener('submit', function(event) {
                if (sendWelcomeCheckbox && sendWelcomeCheckbox.checked) {
                    var methodCheckboxes = methodSection.querySelectorAll('input[type="checkbox"]');
                    var oneChecked = Array.from(methodCheckboxes).some(function(cb) {
                        return cb.checked;
                    });
                    if (!oneChecked) {
                        event.preventDefault();
                        alert('Please choose at least one notification channel.');
                        if (methodSection) methodSection.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            });
        }
    });

    // Custom Fields Dynamic Manager
    document.addEventListener("DOMContentLoaded", function() {
        var container = document.getElementById('custom-fields-container');
        var addBtn = document.getElementById('add-custom-field');
        var hint = document.getElementById('no-attributes-hint');

        if (addBtn && container) {
            addBtn.addEventListener('click', function() {
                if (hint) hint.style.display = 'none';
                var row = document.createElement('div');
                row.className = 'custom-field-row';
                row.style.display = 'flex';
                row.style.gap = '8px';
                row.style.alignItems = 'center';
                row.style.marginBottom = '10px';
                row.innerHTML = `
                    <div style="flex: 2;">
                        <input type="text" class="form-control input-sm" name="custom_field_name[]" placeholder="Attribute Name" required>
                    </div>
                    <div style="flex: 3;">
                        <input type="text" class="form-control input-sm" name="custom_field_value[]" placeholder="Attribute Value" required>
                    </div>
                    <div style="flex: 0 0 auto;">
                        <button type="button" class="btn btn-danger btn-sm remove-custom-field" title="Remove">&times;</button>
                    </div>
                `;
                container.appendChild(row);
            });

            container.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('remove-custom-field')) {
                    var row = e.target.closest('.custom-field-row');
                    if (row) {
                        row.remove();
                        if (container.children.length === 0 && hint) {
                            hint.style.display = 'block';
                        }
                    }
                }
            });
        }
    });

    // Leaflet Interactive Map
    var addMap = null;
    var addMarker = null;

    function getLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                setupMap(pos.coords.latitude, pos.coords.longitude);
                $('#coordinates').val(pos.coords.latitude.toFixed(6) + ', ' + pos.coords.longitude.toFixed(6));
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

        if (!addMap) {
            var mapEl = document.getElementById('map');
            if (!mapEl) return;
            addMap = L.map('map').setView([lat, lon], 13);
            L.tileLayer('https://{s}.google.com/vt/lyrs=m&hl=en&x={x}&y={y}&z={z}&s=Ga', {
                subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                maxZoom: 20
            }).addTo(addMap);

            addMarker = L.marker([lat, lon], { draggable: true }).addTo(addMap);

            addMarker.on('dragend', function() {
                var pos = addMarker.getLatLng();
                $('#coordinates').val(pos.lat.toFixed(6) + ', ' + pos.lng.toFixed(6));
            });

            addMap.on('click', function(e) {
                var coord = e.latlng;
                addMarker.setLatLng(coord);
                $('#coordinates').val(coord.lat.toFixed(6) + ', ' + coord.lng.toFixed(6));
            });
        } else {
            addMap.setView([lat, lon], 13);
            if (addMarker) addMarker.setLatLng([lat, lon]);
        }
    }

    // Coordinates manual input sync
    document.addEventListener('DOMContentLoaded', function() {
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

    window.addEventListener('load', function() {
        if (navigator.geolocation && window.location.protocol === "https:") {
            navigator.geolocation.getCurrentPosition(function(pos) {
                setupMap(pos.coords.latitude, pos.coords.longitude);
                $('#coordinates').val(pos.coords.latitude.toFixed(6) + ', ' + pos.coords.longitude.toFixed(6));
            }, function() {
                setupMap(51.505, -0.09);
            });
        } else {
            setupMap(51.505, -0.09);
        }
    });
</script>
{/literal}

{include file="sections/footer.tpl"}