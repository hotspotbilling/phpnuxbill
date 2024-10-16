{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" role="form" action="" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">
                    <div class="btn-group pull-right">
                        <button class="btn btn-primary btn-xs" title="save" name="save" value="save" type="submit"><span
                                class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span></button>
                    </div>
                    {Lang::T('Miscellaneous')}
                </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="col-md-2 control-label">{Lang::T('Enable Session Timeout')}</label>
                            <div class="col-md-6">
                                <label class="switch">
                                    <input type="checkbox" id="enable_session_timeout" value="1"
                                        name="enable_session_timeout" {if $_c['enable_session_timeout']==1}checked{/if}>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <p class="help-block col-md-4">
                                {Lang::T('Logout Admin if not Available/Online a period of time')}</p>
                        </div>
                        <div class="form-group" id="timeout_duration_input" style="display: none;">
                            <label class="col-md-2 control-label">{Lang::T('Timeout Duration')}</label>
                            <div class="col-md-6">
                                <input type="number" value="{$_c['session_timeout_duration']}" class="form-control"
                                    name="session_timeout_duration" id="session_timeout_duration"
                                    placeholder="{Lang::T('Enter the session timeout duration (minutes)')}" min="1">
                            </div>
                            <p class="help-block col-md-4">{Lang::T('Idle Timeout, Logout Admin if Idle for xx
                                minutes')}
                            </p>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">{Lang::T('New Version Notification')}</label>
                            <div class="col-md-6">
                                <select name="new_version_notify" id="new_version_notify" class="form-control">
                                    <option value="enable" {if $_c['new_version_notify']=='enable' }selected="selected"
                                        {/if}>{Lang::T('Enabled')}
                                    </option>
                                    <option value="disable" {if $_c['new_version_notify']=='disable'
                                        }selected="selected" {/if}>{Lang::T('Disabled')}
                                    </option>
                                </select>
                            </div>
                            <p class="help-block col-md-4">{Lang::T('This is to notify you when new updates is
                                available')}
                            </p>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">{Lang::T('Router Check')}</label>
                            <div class="col-md-6">
                                <select name="router_check" id="router_check" class="form-control">
                                    <option value="0" {if $_c['router_check']=='0' }selected="selected" {/if}>
                                        {Lang::T('Disabled')}
                                    </option>
                                    <option value="1" {if $_c['router_check']=='1' }selected="selected" {/if}>
                                        {Lang::T('Enabled')}
                                    </option>
                                </select>
                            </div>
                            <p class="help-block col-md-4">
                                {Lang::T('If enabled, the system will notify Admin when router goes Offline, If admin
                                have 10 or more router and many customers, it will get overlapping, you can disabled')}
                            </p>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">{Lang::T('Phone OTP Required')}</label>
                            <div class="col-md-6">
                                <select name="allow_phone_otp" id="allow_phone_otp" class="form-control">
                                    <option value="no" {if $_c['allow_phone_otp']=='no' }selected="selected" {/if}>
                                        {Lang::T('No')}</option>
                                    <option value="yes" {if $_c['allow_phone_otp']=='yes' }selected="selected" {/if}>
                                        {Lang::T('Yes')}
                                    </option>
                                </select>
                            </div>
                            <p class="help-block col-md-4">
                                {Lang::T('OTP is required when user want to change phone number and registration')}
                            </p>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">{Lang::T('OTP Method')}</label>
                            <div class="col-md-6">
                                <select name="phone_otp_type" id="phone_otp_type" class="form-control">
                                    <option value="sms" {if $_c['phone_otp_type']=='sms' }selected="selected" {/if}>
                                        {Lang::T('By SMS')}
                                    <option value="whatsapp" {if $_c['phone_otp_type']=='whatsapp' }selected="selected"
                                        {/if}> {Lang::T('by WhatsApp')}
                                    <option value="both" {if $_c['phone_otp_type']=='both' }selected="selected" {/if}>
                                        {Lang::T('By WhatsApp and SMS')}
                                    </option>
                                </select>
                            </div>
                            <p class="help-block col-md-4">{Lang::T('The method which OTP will be sent to user')}<br>
                            {Lang::T('For Registration and Update Phone Number')}</p>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">{Lang::T('Email OTP Required')}</label>
                            <div class="col-md-6">
                                <select name="allow_email_otp" id="allow_email_otp" class="form-control">
                                    <option value="no" {if $_c['allow_email_otp']=='no' }selected="selected" {/if}>
                                        {Lang::T('No')}</option>
                                    <option value="yes" {if $_c['allow_email_otp']=='yes' }selected="selected" {/if}>
                                        {Lang::T('Yes')}
                                    </option>
                                </select>
                            </div>
                            <p class="help-block col-md-4">
                                {Lang::T('OTP is required when user want to change Email Address')}
                            </p>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">{Lang::T('Extend Package Expiry')}</label>
                            <div class="col-md-6">
                                <select name="extend_expiry" id="extend_expiry" class="form-control">
                                    <option value="yes" {if $_c['extend_expiry']!='no' }selected="selected" {/if}>
                                        {Lang::T('Yes')}</option>
                                    <option value="no" {if $_c['extend_expiry']=='no' }selected="selected" {/if}>
                                        {Lang::T('No')}</option>
                                </select>
                            </div>
                            <p class="help-block col-md-4">
                                {Lang::T('If user buy same internet plan, expiry date will extend')}</p>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">{Lang::T('Show Bandwidth Plan')}</label>
                            <div class="col-md-6">
                                <select name="show_bandwidth_plan" id="show_bandwidth_plan" class="form-control">
                                    <option value="no" {if $_c['show_bandwidth_plan']=='no' }selected="selected" {/if}>
                                        {Lang::T('No')}</option>
                                    <option value="yes" {if $_c['show_bandwidth_plan']=='yes' }selected="selected"
                                        {/if}>
                                        {Lang::T('Yes')}</option>
                                </select>
                            </div>
                            <p class="help-block col-md-4">
                                {Lang::T(' for Customer')}</p>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">{Lang::T('Hotspot Auth Method')}</label>
                            <div class="col-md-6">
                                <select name="hs_auth_method" id="auth_method" class="form-control">
                                    <option value="api" {if $_c['hs_auth_method']=='api' }selected="selected" {/if}>
                                        {Lang::T('Api')}
                                    </option>
                                    <option value="hchap" {if $_c['hs_auth_method']=='hchap' }selected="selected" {/if}>
                                        {Lang::T('Http-Chap')}
                                    </option>
                                </select>
                            </div>
                            <p class="help-block col-md-4">
                                {Lang::T('Hotspot Authentication Method. Make sure you have changed your hotspot login
                                page.')}<br><a href="https://github.com/agstrxyz/phpnuxbill-login-hotspot"
                                    target="_blank">Download
                                    phpnuxbill-login-hotspot</a>
                            </p>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">{Lang::T('Check if Customer Online')}</label>
                            <div class="col-md-6">
                                <select name="check_customer_online" id="check_customer_online" class="form-control">
                                    <option value="no">
                                        {Lang::T('No')}
                                    </option>
                                    <option value="yes" {if $_c['check_customer_online']=='yes' }selected="selected"
                                        {/if}>
                                        {Lang::T('Yes')}
                                    </option>
                                </select>
                            </div>
                            <p class="help-block col-md-4">
                                {Lang::T('This will show is Customer currently is online or not')}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="panel-body">
                    <div class="form-group">
                        <button class="btn btn-success btn-block" name="save" value="save" type="submit">
                            {Lang::T('Save Changes')}
                        </button>
                    </div>
                </div>
            </div>
        </div>
</form>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        var sectionTimeoutCheckbox = document.getElementById('enable_session_timeout');
        var timeoutDurationInput = document.getElementById('timeout_duration_input');
        var timeoutDurationField = document.getElementById('session_timeout_duration');

        if (sectionTimeoutCheckbox.checked) {
            timeoutDurationInput.style.display = 'block';
            timeoutDurationField.required = true;
        }

        sectionTimeoutCheckbox.addEventListener('change', function () {
            if (this.checked) {
                timeoutDurationInput.style.display = 'block';
                timeoutDurationField.required = true;
            } else {
                timeoutDurationInput.style.display = 'none';
                timeoutDurationField.required = false;
            }
        });

        document.querySelector('form').addEventListener('submit', function (event) {
            if (sectionTimeoutCheckbox.checked && (!timeoutDurationField.value || isNaN(
                timeoutDurationField.value))) {
                event.preventDefault();
                alert('Please enter a valid session timeout duration.');
                timeoutDurationField.focus();
            }
        });
    });
</script>
{include file="sections/footer.tpl"}