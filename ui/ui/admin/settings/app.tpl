{include file="sections/header.tpl"}

<form class="form-horizontal st" data-t-copy="{Lang::T('Copy')|escape}" data-t-copied="{Lang::T('Copied')|escape}" method="post" role="form" action="{Text::url('')}settings/app-post"
    enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">

    <div class="settings-container">
        <div class="settings-sidebar">
            <ul class="nav nav-pills nav-stacked" role="tablist">
                <li role="presentation" class="nav-header">{Lang::T('General Settings')}</li>
                <li role="presentation" class="active">
                    <a href="#general" role="tab" data-toggle="tab">
                        <i class="glyphicon glyphicon-cog"></i> {Lang::T('General')}
                    </a>
                </li>
                <li role="presentation">
                    <a href="#login" role="tab" data-toggle="tab">
                        <i class="glyphicon glyphicon-log-in"></i> {Lang::T('Login Page')}
                    </a>
                </li>
                <li role="presentation">
                    <a href="#dashboard" role="tab" data-toggle="tab">
                        <i class="glyphicon glyphicon-dashboard"></i> {Lang::T('Dashboard')}
                    </a>
                </li>

                <li role="presentation" class="nav-header">{Lang::T('Customer Settings')}</li>
                <li role="presentation">
                    <a href="#coupons" role="tab" data-toggle="tab">
                        <i class="glyphicon glyphicon-tag"></i> {Lang::T('Coupons')}
                    </a>
                </li>
                <li role="presentation">
                    <a href="#registration" role="tab" data-toggle="tab">
                        <i class="glyphicon glyphicon-user"></i> {Lang::T('Registration')}
                    </a>
                </li>
                <li role="presentation">
                    <a href="#voucher" role="tab" data-toggle="tab">
                        <i class="glyphicon glyphicon-barcode"></i> {Lang::T('Voucher')}
                    </a>
                </li>
                <li role="presentation">
                    <a href="#balance" role="tab" data-toggle="tab">
                        <i class="glyphicon glyphicon-credit-card"></i> {Lang::T('Balance')}
                    </a>
                </li>

                <li role="presentation" class="nav-header">{Lang::T('System Settings')}</li>
                <li role="presentation">
                    <a href="#security" role="tab" data-toggle="tab">
                        <i class="glyphicon glyphicon-lock"></i> {Lang::T('Security')}
                    </a>
                </li>
                <li role="presentation">
                    <a href="#radius" role="tab" data-toggle="tab">
                        <i class="glyphicon glyphicon-globe"></i> FreeRadius
                    </a>
                </li>
                <li role="presentation">
                    <a href="#postpaid" role="tab" data-toggle="tab">
                        <i class="glyphicon glyphicon-calendar"></i> {Lang::T('Postpaid')}
                    </a>
                </li>
                <li role="presentation">
                    <a href="#tax" role="tab" data-toggle="tab">
                        <i class="glyphicon glyphicon-usd"></i> {Lang::T('Tax System')}
                    </a>
                </li>

                <li role="presentation" class="nav-header">{Lang::T('Notifications')}</li>
                <li role="presentation">
                    <a href="#telegram" role="tab" data-toggle="tab">
                        <i class="glyphicon glyphicon-send"></i> Telegram
                    </a>
                </li>
                <li role="presentation">
                    <a href="#sms" role="tab" data-toggle="tab">
                        <i class="glyphicon glyphicon-phone"></i> SMS
                    </a>
                </li>
                <li role="presentation">
                    <a href="#whatsapp" role="tab" data-toggle="tab">
                        <i class="glyphicon glyphicon-comment"></i> WhatsApp
                    </a>
                </li>
                <li role="presentation">
                    <a href="#email" role="tab" data-toggle="tab">
                        <i class="glyphicon glyphicon-envelope"></i> Email
                    </a>
                </li>
                <li role="presentation">
                    <a href="#usernotif" role="tab" data-toggle="tab">
                        <i class="glyphicon glyphicon-bell"></i> {Lang::T('User Notification')}
                    </a>
                </li>

                <li role="presentation" class="nav-header">{Lang::T('Integrations')}</li>
                <li role="presentation">
                    <a href="#tawkto" role="tab" data-toggle="tab">
                        <i class="glyphicon glyphicon-comment"></i> Tawk.to
                    </a>
                </li>
                <li role="presentation">
                    <a href="#apikey" role="tab" data-toggle="tab">
                        <i class="glyphicon glyphicon-certificate"></i> API Key
                    </a>
                </li>
                <li role="presentation">
                    <a href="#proxy" role="tab" data-toggle="tab">
                        <i class="glyphicon glyphicon-transfer"></i> Proxy
                    </a>
                </li>
                <li role="presentation">
                    <a href="#github" role="tab" data-toggle="tab">
                        <i class="glyphicon glyphicon-cloud"></i> GitHub Auth
                    </a>
                </li>
                <li role="presentation">
                    <a href="#cron" role="tab" data-toggle="tab">
                        <i class="glyphicon glyphicon-time"></i> Cron Job
                    </a>
                </li>
            </ul>
        </div>

        <div class="settings-content">
            <div class="tab-content">
                <div role="tabpanel" class="tab-pane active" id="general">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Application Name / Company Name')}</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" id="CompanyName" name="CompanyName"
                                value="{$_c['CompanyName']}">
                        </div>
                        <span class="help-block col-md-4">{Lang::T('This Name will be shown on the Title')}</span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Company Logo')}</label>
                        <div class="col-md-5">
                            <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                            <span class="help-block">{Lang::T('For PDF Reports | Best size 1078 x 200 | uploaded image
                                will
                                be autosize')}</span>
                        </div>
                        <span class="help-block col-md-4">
                            <a href="./{$logo}" target="_blank"><img src="./{$logo}" height="48" alt="logo for PDF"></a>
                        </span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Company Footer')}</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" id="CompanyFooter" name="CompanyFooter"
                                value="{$_c['CompanyFooter']}">
                        </div>
                        <span class="help-block col-md-4">{Lang::T('Will show below user pages')}</span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Address')}</label>
                        <div class="col-md-5">
                            <textarea class="form-control" id="address" name="address"
                                rows="3">{Lang::htmlspecialchars($_c['address'])}</textarea>
                        </div>
                        <span class="help-block col-md-4">{Lang::T('You can use html tag')}</span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Phone Number')}</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" id="phone" name="phone" value="{$_c['phone']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Invoice Footer')}</label>
                        <div class="col-md-5">
                            <textarea class="form-control" id="note" name="note"
                                rows="3">{Lang::htmlspecialchars($_c['note'])}</textarea>
                            <span class="help-block">{Lang::T('You can use html tag')}</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label"><i class="glyphicon glyphicon-print"></i>
                            {Lang::T('Print Max Char')}</label>
                        <div class="col-md-5">
                            <input type="number" class="form-control" id="printer_cols" placeholder="37"
                                name="printer_cols"
                                value="{if empty($_c['printer_cols'])}37{else}{$_c['printer_cols']}{/if}">
                        </div>
                        <span class="help-block col-md-4">{Lang::T('For invoice print using Thermal
                            Printer')}</span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Theme')}</label>
                        <div class="col-md-5">
                            <select name="theme" id="theme" class="form-control">
                                <option value="default" {if $_c['theme'] eq 'default' }selected="selected" {/if}>
                                    {Lang::T('Default')}
                                </option>
                                {foreach $themes as $theme}
                                <option value="{$theme}" {if $_c['theme'] eq $theme}selected="selected" {/if}>
                                    {Lang::ucWords($theme)}</option>
                                {/foreach}
                            </select>
                        </div>
                        <p class="help-block col-md-4"><a
                                href="https://github.com/hotspotbilling/phpnuxbill/wiki/Themes"
                                target="_blank">{Lang::T('Theme Info')}</a></p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Recharge Using')}</label>
                        <div class="col-md-5">
                            <input type="text" name="payment_usings" class="form-control"
                                value="{$_c['payment_usings']}"
                                placeholder="{Lang::T('Cash')}, {Lang::T('Bank Transfer')}">
                        </div>
                        <p class="help-block col-md-4">
                            {Lang::T('This used for admin to select payment in recharge, using comma for every new
                            options')}
                        </p>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Income reset date')}</label>
                        <div class="col-md-5">
                            <input type="number" class="form-control" id="reset_day" placeholder="20" min="1" max="28"
                                step="1" name="reset_day" value="{$_c['reset_day']}">
                        </div>
                        <span class="help-block col-md-4">{Lang::T('Income will reset every this day')}</span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Dashboard Structure')}</label>
                        <div class="col-md-5">
                            <input type="text" name="dashboard_cr" class="form-control" value="{$_c['dashboard_cr']}"
                                placeholder="12.7,5.12">
                        </div>
                        <p class="help-block col-md-4">
                            <a href="{$app_url}/docs/#Dashboard%20Structure" target="_blank">{Lang::T('Read
                                documentation')}</a>
                        </p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Pretty URL')}</label>
                        <div class="col-md-5">
                            <select name="url_canonical" id="url_canonical" class="form-control">
                                <option value="no" {if $_c['url_canonical']=='no' }selected="selected" {/if}>
                                    {Lang::T('No')}
                                </option>
                                <option value="yes" {if $_c['url_canonical']=='yes' }selected="selected" {/if}>
                                    {Lang::T('Yes')}
                                </option>
                            </select>
                            <p class="help-block">
                                <b>?_route=settings/app&foo=bar</b> will be <b>/settings/app?foo=bar</b>
                            </p>
                        </div>
                        <span class="help-block col-md-4">{Lang::T('rename .htaccess_firewall to .htaccess')}</span>
                    </div>
                    <div class="st-savebar">
                        <button class="btn btn-success" name="general" type="submit"><i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="login">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Choose Template')}</label>
                        <div class="col-md-5">
                            <select name="login_page_type" id="login_page_type" class="form-control">
                                <option value="default" {if $_c['login_page_type']=='default' }selected="selected"
                                    {/if}>
                                    {Lang::T('Default')}</option>
                                <option value="custom" {if $_c['login_page_type']=='custom' }selected="selected" {/if}>
                                    {Lang::T('Custom')}</option>
                            </select>
                        </div>
                        <span class="help-block col-md-4"><small>{Lang::T('Select your login template
                                type')}</small></span>
                    </div>
                    <div id="customFields" style="display: none;">
                        <div class="form-group">
                            <label class="col-md-3 control-label">{Lang::T('Select Login Page')}</label>
                            <div class="col-md-5">
                                <select name="login_Page_template" id="login_Page_template" class="form-control">
                                    {foreach $template_files as $template}
                                    <option value="{$template.value|escape}" {if $_c['login_Page_template'] eq
                                        $template.value}selected="selected" {/if}>
                                        {$template.name|escape}</option>
                                    {/foreach}
                                </select>
                            </div>
                            <span class="help-block col-md-4"><small>{Lang::T('Select your preferred login
                                    template')}</small></span>
                        </div>
                        <div class="form-group">
                            <label class="col-md-3 control-label">{Lang::T('Page Heading / Company Name')}</label>
                            <div class="col-md-5">
                                <input type="text" class="form-control" id="login_page_head" name="login_page_head"
                                    value="{$_c['login_page_head']}">
                            </div>
                            <span class="help-block col-md-4"><small>{Lang::T('This Name will be shown on the login
                                    wallpaper')}</small></span>
                        </div>
                        <div class="form-group">
                            <label class="col-md-3 control-label">{Lang::T('Page Description')}</label>
                            <div class="col-md-5">
                                <textarea class="form-control" id="login_page_description" name="login_page_description"
                                    rows="3">{Lang::htmlspecialchars($_c['login_page_description'])}</textarea>
                            </div>
                            <span class="help-block col-md-4"><small>{Lang::T('This will also display on wallpaper, You
                                    can
                                    use html tag')}</small></span>
                        </div>
                        <div class="form-group">
                            <label class="col-md-3 control-label">{Lang::T('Favicon')}</label>
                            <div class="col-md-5">
                                <input type="file" class="form-control" id="login_page_favicon"
                                    name="login_page_favicon" accept="image/*">
                                <span class="help-block"><small>{Lang::T('Best size 30 x 30 | uploaded image will be
                                        autosize')}</small></span>
                            </div>
                            <span class="help-block col-md-4">
                                <a href="./{$favicon}" target="_blank"><img src="./{$favicon}" height="48"
                                        alt="Favicon"></a>
                            </span>
                        </div>
                        <div class="form-group">
                            <label class="col-md-3 control-label">{Lang::T('Login Page Logo')}</label>
                            <div class="col-md-5">
                                <input type="file" class="form-control" id="login_page_logo" name="login_page_logo"
                                    accept="image/*">
                                <span class="help-block"><small>{Lang::T('Best size 300 x 60 | uploaded image will be
                                        autosize')}</small></span>
                            </div>
                            <span class="help-block col-md-4">
                                <a href="./{$login_logo}" target="_blank"><img src="./{$login_logo}" height="48"
                                        alt="Logo"></a>
                            </span>
                        </div>
                        <div class="form-group">
                            <label class="col-md-3 control-label">{Lang::T('Login Page Wallpaper')}</label>
                            <div class="col-md-5">
                                <input type="file" class="form-control" id="login_page_wallpaper"
                                    name="login_page_wallpaper" accept="image/*">
                                <span class="help-block"><small>{Lang::T('Best size 1920 x 1080 | uploaded image will be
                                        autosize')}</small></span>
                            </div>
                            <span class="help-block col-md-4">
                                <a href="./{$wallpaper}" target="_blank"><img src="./{$wallpaper}" height="48"
                                        alt="Wallpaper"></a>
                            </span>
                        </div>
                    </div>

                    <div class="st-savebar">
                        <button class="btn btn-success" name="general" type="submit"><i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="dashboard">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Choose Dashboard Type')}</label>
                        <div class="col-md-5">
                            <select name="dashboard_type" id="dashboard_type" class="form-control">
                                <option value="default" {if $_c['dashboard_type']=='default' }selected="selected" {/if}>
                                    {Lang::T('Classic')}</option>
                                <option value="dynamic" {if $_c['dashboard_type']=='dynamic' }selected="selected" {/if}>
                                    {Lang::T('Dynamic')}</option>
                            </select>
                        </div>
                        <span class="help-block col-md-4"><small>{Lang::T('Select your dashboard type')}</small></span>
                    </div>
                    <div class="st-savebar">
                        <button class="btn btn-success" name="general" type="submit"><i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="coupons">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Enable Coupon')}</label>
                        <div class="col-md-5">
                            <select name="enable_coupons" id="enable_coupons" class="form-control text-muted">
                                <option value="no">{Lang::T('No')}</option>
                                <option value="yes" {if $_c['enable_coupons']=='yes' }selected="selected" {/if}>
                                    {Lang::T('Yes')}
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">
                            <small>{Lang::T('Enable or disable coupons')}</small>
                        </p>
                    </div>
                    <div class="st-savebar">
                        <button class="btn btn-success" name="general" type="submit"><i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="registration">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Allow Registration')}</label>
                        <div class="col-md-5">
                            <select name="disable_registration" id="disable_registration" class="form-control">
                                <option value="no" {if $_c['disable_registration']=='no' }selected="selected" {/if}>
                                    {Lang::T('Yes')}
                                </option>
                                {if $_c['disable_voucher'] != 'yes'}
                                <option value="yes" {if $_c['disable_registration']=='yes' }selected="selected" {/if}>
                                    {Lang::T('Voucher Only')}
                                </option>
                                {/if}
                                <option value="noreg" {if $_c['disable_registration']=='noreg' }selected="selected"
                                    {/if}>
                                    {Lang::T('No Registration')}
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">
                            {Lang::T('Customer just Login with Phone number and Voucher Code, Voucher will be
                            password')}
                        </p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Registration Username')}</label>
                        <div class="col-md-5">
                            <select name="registration_username" id="voucher_format" class="form-control">
                                <option value="username" {if $_c['registration_username']=='username'
                                    }selected="selected" {/if}>Username
                                </option>
                                <option value="email" {if $_c['registration_username']=='email' }selected="selected"
                                    {/if}>
                                    Email
                                </option>
                                <option value="phone" {if $_c['registration_username']=='phone' }selected="selected"
                                    {/if}>
                                    Phone Number
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Photo Required')}</label>
                        <div class="col-md-5">
                            <select name="photo_register" id="photo_register" class="form-control">
                                <option value="no">
                                    {Lang::T('No')}
                                </option>
                                <option value="yes" {if $_c['photo_register']=='yes' }selected="selected" {/if}>
                                    {Lang::T('Yes')}
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">
                            {Lang::T('Customer Registration need to upload their photo')}
                        </p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('SMS OTP Registration')}</label>
                        <div class="col-md-5">
                            <select name="sms_otp_registration" id="sms_otp_registration" class="form-control">
                                <option value="no">
                                    {Lang::T('No')}
                                </option>
                                <option value="yes" {if $_c['sms_otp_registration']=='yes' }selected="selected" {/if}>
                                    {Lang::T('Yes')}
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">
                            {Lang::T('Customer Registration need to validate using OTP')}
                        </p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('OTP Method')}</label>
                        <div class="col-md-5">
                            <select name="phone_otp_type" id="phone_otp_type" class="form-control">
                                <option value="sms" {if $_c['phone_otp_type']=='sms' }selected="selected" {/if}>
                                    {Lang::T('By SMS')}</option>
                                <option value="whatsapp" {if $_c['phone_otp_type']=='whatsapp' }selected="selected"
                                    {/if}>
                                    {Lang::T('by WhatsApp')}</option>
                                <option value="both" {if $_c['phone_otp_type']=='both' }selected="selected" {/if}>
                                    {Lang::T('By WhatsApp and SMS')}
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">{Lang::T('The method which OTP will be sent to user')}<br>
                            {Lang::T('For Registration and Update Phone Number')}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Notify Admin')}</label>
                        <div class="col-md-5">
                            <select name="reg_nofify_admin" id="reg_nofify_admin" class="form-control">
                                <option value="no">
                                    {Lang::T('No')}
                                </option>
                                <option value="yes" {if $_c['reg_nofify_admin']=='yes' }selected="selected" {/if}>
                                    {Lang::T('Yes')}
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">
                            {Lang::T('Notify Admin upon self registration')}
                        </p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Require Terms Agreement')}</label>
                        <div class="col-md-5">
                            <select name="register_terms" id="register_terms" class="form-control">
                                <option value="yes">
                                    {Lang::T('Yes')}
                                </option>
                                <option value="no" {if $_c['register_terms']=='no' }selected="selected" {/if}>
                                    {Lang::T('No')}
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">
                            {Lang::T('The register page shows an "I agree to the Terms and Conditions" box, and the Register button stays off until it is ticked')}
                        </p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Mandatory Fields')}</label>
                        <label class="col-md-1 control-label"><input type="checkbox" name="man_fields_email" value="yes"
                                {if !isset($_c['man_fields_email']) || $_c['man_fields_email'] neq 'no' }checked{/if}>
                            {Lang::T('Email')}</label>
                        <label class="col-md-2 control-label"><input type="checkbox" name="man_fields_fname" value="yes"
                                {if !isset($_c['man_fields_fname']) || $_c['man_fields_fname'] neq 'no' }checked{/if}>
                            {Lang::T('Full Name')}</label>
                        <label class="col-md-2 control-label"><input type="checkbox" name="man_fields_address"
                                value="yes" {if !isset($_c['man_fields_address']) || $_c['man_fields_address'] neq 'no'
                                }checked{/if}>
                            {Lang::T('Address')}</label>
                    </div>
                    <div class="st-savebar">
                        <button class="btn btn-success" name="general" type="submit"><i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="security">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Enable Session Timeout')}</label>
                        <div class="col-md-5">
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
                        <label class="col-md-3 control-label">{Lang::T('Timeout Duration')}</label>
                        <div class="col-md-5">
                            <input type="number" value="{$_c['session_timeout_duration']}" class="form-control"
                                name="session_timeout_duration" id="session_timeout_duration"
                                placeholder="{Lang::T('Enter the session timeout duration (minutes)')}" min="1">
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Idle Timeout, Logout Admin if Idle for xx
                            minutes')}
                        </p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Single Admin Session')}</label>
                        <div class="col-md-5">
                            <select name="single_session" id="single_session" class="form-control">
                                <option value="no">
                                    {Lang::T('No')}</option>
                                <option value="yes" {if $_c['single_session']=='yes' }selected="selected" {/if}>
                                    {Lang::T('Yes')}
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">
                            {Lang::T('Admin can only have single session login, it will logout another session')}
                        </p>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Enable CSRF Validation')}</label>
                        <div class="col-md-5">
                            <select name="csrf_enabled" id="csrf_enabled" class="form-control">
                                <option value="no">
                                    {Lang::T('No')}</option>
                                <option value="yes" {if $_c['csrf_enabled']=='yes' }selected="selected" {/if}>
                                    {Lang::T('Yes')}
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">
                            <a href="https://en.wikipedia.org/wiki/Cross-site_request_forgery"
                                target="_blank">{Lang::T('Cross-site request forgery')}</a>
                        </p>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Plugin Routes')}</label>
                        <div class="col-md-5">
                            <select name="plugin_routes_mode" class="form-control">
                                <option value="warn" {if empty($_c['plugin_routes_mode']) || $_c['plugin_routes_mode']!='enforce'}selected="selected"{/if}>
                                    {Lang::T('Warn: run undeclared plugin functions and log them')}
                                </option>
                                <option value="enforce" {if $_c['plugin_routes_mode']=='enforce'}selected="selected"{/if}>
                                    {Lang::T('Enforce: only declared plugin routes and menus run')}
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">
                            <small>{Lang::T('Plugins declare the functions their URLs may call (register_route or plugin.json routes). Enforce refuses everything else; use Warn while older plugins are being updated.')}</small>
                        </p>
                    </div>

                    <div class="col-md-12" style="margin: 20px 0 15px; padding: 0;">
                        <hr style="border-top: 1px solid rgba(148, 163, 184, 0.35); margin: 15px 0;">
                        <h4 style="font-weight: 600; color: inherit; display: flex; align-items: center; gap: 8px;">
                            <i class="glyphicon glyphicon-shield" style="color: #3b82f6;"></i>
                            {Lang::T('Captcha & Bot Protection')}
                        </h4>
                        <p style="color: inherit; opacity: 0.7; font-size: 13px; margin-bottom: 20px;">
                            {Lang::T('Protect Admin and Member login from automated brute-force attacks and credential stuffing with Cloudflare Turnstile or Google reCAPTCHA.')}
                        </p>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Captcha Provider')}</label>
                        <div class="col-md-5">
                            <select name="captcha_provider" id="captcha_provider" class="form-control">
                                <option value="none" {if empty($_c['captcha_provider']) || $_c['captcha_provider']=='none'}selected="selected"{/if}>
                                    {Lang::T('Disabled')}
                                </option>
                                <option value="turnstile" {if $_c['captcha_provider']=='turnstile'}selected="selected"{/if}>
                                    Cloudflare Turnstile ({Lang::T('Recommended - Privacy Focused')})
                                </option>
                                <option value="recaptcha" {if $_c['captcha_provider']=='recaptcha'}selected="selected"{/if}>
                                    Google reCAPTCHA v2 ({Lang::T('Checkbox')})
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">
                            <small>{Lang::T('Choose the bot protection service to safeguard your login pages.')}</small>
                        </p>
                    </div>

                    <div class="form-group" id="captcha_target_group">
                        <label class="col-md-3 control-label">{Lang::T('Protected Pages')}</label>
                        <div class="col-md-5" style="padding-top: 0;">
                            <label class="checkbox-inline" style="margin-right: 18px;"><input type="checkbox" name="captcha_on_admin" value="yes"
                                    {if Captcha::isContextOn('admin')}checked{/if}> {Lang::T('Admin')}</label>
                            <label class="checkbox-inline" style="margin-right: 18px;"><input type="checkbox" name="captcha_on_customer" value="yes"
                                    {if Captcha::isContextOn('customer')}checked{/if}> {Lang::T('Customer')}</label>
                            <label class="checkbox-inline"><input type="checkbox" name="captcha_on_plugin" value="yes"
                                    {if Captcha::isContextOn('plugin')}checked{/if}> {Lang::T('Plugins')}</label>
                        </div>
                        <p class="help-block col-md-4">
                            <small>{Lang::T('Tick the logins that require verification. Plugins covers logins plugins add, such as the Hotspot merchant portal.')}</small>
                        </p>
                    </div>

                    <!-- Cloudflare Turnstile Keys -->
                    <div id="turnstile_keys_section" style="display: none;">
                        <div class="form-group">
                            <label class="col-md-3 control-label">{Lang::T('Turnstile Site Key')}</label>
                            <div class="col-md-5">
                                <input type="text" class="form-control" name="turnstile_site_key" id="turnstile_site_key"
                                    value="{$_c['turnstile_site_key']}" placeholder="0x4AAAAAA...">
                            </div>
                            <p class="help-block col-md-4">
                                <small>{Lang::T('Obtain from Cloudflare Dashboard')} &rarr; <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank" rel="noopener">Cloudflare Turnstile</a></small>
                            </p>
                        </div>
                        <div class="form-group">
                            <label class="col-md-3 control-label">{Lang::T('Turnstile Secret Key')}</label>
                            <div class="col-md-5">
                                <input type="password" class="form-control" name="turnstile_secret_key" id="turnstile_secret_key"
                                    value="{$_c['turnstile_secret_key']}" placeholder="0x4AAAAAA...">
                            </div>
                            <p class="help-block col-md-4">
                                <small>{Lang::T('Keep your Turnstile secret key confidential.')}</small>
                            </p>
                        </div>
                    </div>

                    <!-- Google reCAPTCHA Keys -->
                    <div id="recaptcha_keys_section" style="display: none;">
                        <div class="form-group">
                            <label class="col-md-3 control-label">{Lang::T('reCAPTCHA Site Key')}</label>
                            <div class="col-md-5">
                                <input type="text" class="form-control" name="recaptcha_site_key" id="recaptcha_site_key"
                                    value="{$_c['recaptcha_site_key']}" placeholder="6LeIxacTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI">
                            </div>
                            <p class="help-block col-md-4">
                                <small>{Lang::T('Obtain from Google reCAPTCHA Admin')} &rarr; <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener">Google Admin Console</a></small>
                            </p>
                        </div>
                        <div class="form-group">
                            <label class="col-md-3 control-label">{Lang::T('reCAPTCHA Secret Key')}</label>
                            <div class="col-md-5">
                                <input type="password" class="form-control" name="recaptcha_secret_key" id="recaptcha_secret_key"
                                    value="{$_c['recaptcha_secret_key']}" placeholder="6LeIxacTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe">
                            </div>
                            <p class="help-block col-md-4">
                                <small>{Lang::T('Keep your reCAPTCHA secret key confidential.')}</small>
                            </p>
                        </div>
                    </div>

                    <div class="st-savebar">
                        <button class="btn btn-success" name="general" type="submit"><i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="voucher">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Disable Voucher')}</label>
                        <div class="col-md-5">
                            <select name="disable_voucher" id="disable_voucher" class="form-control">
                                <option value="no" {if $_c['disable_voucher']=='no' }selected="selected" {/if}>
                                    {Lang::T('No')}
                                </option>
                                <option value="yes" {if $_c['disable_voucher']=='yes' }selected="selected" {/if}>
                                    {Lang::T('Yes')}
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Voucher activation menu will be hidden')}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Voucher Format')}</label>
                        <div class="col-md-5">
                            <select name="voucher_format" id="voucher_format" class="form-control">
                                <option value="up" {if $_c['voucher_format']=='up' }selected="selected" {/if}>UPPERCASE
                                </option>
                                <option value="low" {if $_c['voucher_format']=='low' }selected="selected" {/if}>
                                    lowercase
                                </option>
                                <option value="rand" {if $_c['voucher_format']=='rand' }selected="selected" {/if}>
                                    RaNdoM
                                </option>
                                <option value="numbers" {if $_c['voucher_format']=='numbers' }selected="selected" {/if}>
                                    Numbers
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">UPPERCASE lowercase RaNdoM</p>
                    </div>
                    {if $_c['disable_voucher'] != 'yes'}
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Redirect URL after Activation')}</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" id="voucher_redirect" name="voucher_redirect"
                                placeholder="https://192.168.88.1/status" value="{$_c['voucher_redirect']}">
                        </div>
                        <p class="help-block col-md-4">
                            {Lang::T('After Customer activate voucher or login, customer will be redirected to this
                            url')}
                        </p>
                    </div>
                    {/if}
                    <div class="st-savebar">
                        <button class="btn btn-success" name="general" type="submit"><i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="radius">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Enable Radius')}</label>
                        <div class="col-md-5">
                            <select name="radius_enable" id="radius_enable" class="form-control text-muted">
                                <option value="0">{Lang::T('No')}</option>
                                <option value="1" {if $_c['radius_enable']}selected="selected" {/if}>{Lang::T('Yes')}
                                </option>
                            </select>
                        </div>
                    </div>
                    {* the former REST-only "Radius Rest Interim-Update" minutes, when that was the only one set: shown here as the
                       stale timeout (not selected: it keeps applying to REST only until Yes is saved, which retires the old setting) *}
                    {$rs_legacy = $_c['clear_stale_sessions'] != '1' && (int) $_c['frrest_interim_update'] > 0}
                    <div class="form-group" id="clear_stale_sessions_group">
                        <label class="col-md-3 control-label">{Lang::T('Clear Stale Sessions')}</label>
                        <div class="col-md-5">
                            <select name="clear_stale_sessions" id="clear_stale_sessions" class="form-control text-muted">
                                <option value="0">{Lang::T('No')}</option>
                                <option value="1" {if $_c['clear_stale_sessions'] == '1'}selected="selected" {/if}>{Lang::T('Yes')}
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Close sessions that stopped sending accounting updates, for RADIUS REST (radius.php) and MySQL RADIUS alike. The cron does it on every run.')}</p>
                    </div>
                    <div class="form-group" id="stale_session_timeout_group">
                        <label class="col-md-3 control-label">{Lang::T('Stale Session Timeout (minutes)')}</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" id="stale_session_timeout" name="stale_session_timeout"
                                placeholder="10" value="{if $rs_legacy}{(int) $_c['frrest_interim_update']}{else}{$_c['stale_session_timeout']|escape}{/if}">
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Minutes without an accounting update before a session counts as ended. Keep it longer than the Interim-Update interval set on the router (for example 3 times it), or live sessions are closed between two updates.')}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label" for="frrest_keep_days">{Lang::T('Keep Finished Sessions (days)')}</label>
                        <div class="col-md-5">
                            <input type="number" min="0" max="3650" step="1" class="form-control" id="frrest_keep_days" name="frrest_keep_days"
                                value="{$_c['frrest_keep_days']|default:'0'|escape}">
                        </div>
                        <p class="help-block col-md-4">{Lang::T('For radius.php (REST): the cron removes finished sessions older than this many days, so the accounting table stays small and RADIUS stays fast. Sessions inside a customer\'s active plan are always kept, as limited plans count their usage from them. 0 keeps every session.')}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('REST Secret')}</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" id="radius_rest_secret" name="radius_rest_secret"
                                autocomplete="off" value="{$_c['radius_rest_secret']|escape}">
                        </div>
                        <p class="help-block col-md-4">{Lang::T('When set, radius.php only answers FreeRADIUS requests that send it as the X-Radius-Token header or as ?token= in the REST uri')}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label"></label>
                        <p class="help-block col-md-4"><a
                                href="https://github.com/hotspotbilling/phpnuxbill/wiki/FreeRadius"
                                target="_blank">{Lang::T('Radius Instructions')}</a></p>
                    </div>
                    <div class="st-savebar">
                        <button class="btn btn-success" name="general" type="submit"><i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="postpaid">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Allow Extend')}</label>
                        <div class="col-md-5">
                            <select name="extend_expired" id="extend_expired" class="form-control text-muted">
                                <option value="0">{Lang::T('No')}</option>
                                <option value="1" {if $_c['extend_expired']==1}selected="selected" {/if}>
                                    {Lang::T('Yes')}</option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Customer can request to extend expirations')}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Extend Days')}</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" name="extend_days" placeholder="3"
                                value="{$_c['extend_days']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Confirmation Message')}</label>
                        <div class="col-md-5">
                            <textarea type="text" rows="4" class="form-control" name="extend_confirmation"
                                placeholder="{Lang::T('i agree to extends and will paid full after this')}">{$_c['extend_confirmation']}</textarea>
                        </div>
                    </div>
                    <div class="st-savebar">
                        <button class="btn btn-success" name="general" type="submit"><i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="balance">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Enable System')}</label>
                        <div class="col-md-5">
                            <select name="enable_balance" id="enable_balance" class="form-control">
                                <option value="no" {if $_c['enable_balance']=='no' }selected="selected" {/if}>
                                    {Lang::T('No')}
                                </option>
                                <option value="yes" {if $_c['enable_balance']=='yes' }selected="selected" {/if}>
                                    {Lang::T('Yes')}
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Customer can deposit money to buy voucher')}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Allow Transfer')}</label>
                        <div class="col-md-5">
                            <select name="allow_balance_transfer" id="allow_balance_transfer" class="form-control">
                                <option value="no" {if $_c['allow_balance_transfer']=='no' }selected="selected" {/if}>
                                    {Lang::T('No')}</option>
                                <option value="yes" {if $_c['allow_balance_transfer']=='yes' }selected="selected" {/if}>
                                    {Lang::T('Yes')}</option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Allow balance transfer between customers')}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Minimum Balance Transfer')}</label>
                        <div class="col-md-5">
                            <input type="number" class="form-control" id="minimum_transfer" name="minimum_transfer"
                                value="{$_c['minimum_transfer']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Allow Balance Custom
                            Amount')}</label>
                        <div class="col-md-5">
                            <select name="allow_balance_custom" id="allow_balance_custom" class="form-control">
                                <option value="no">
                                    {Lang::T('No')}
                                </option>
                                <option value="yes" {if $_c['allow_balance_custom']=='yes' }selected="selected" {/if}>
                                    {Lang::T('Yes')}
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4"><small>
                                {Lang::T('Allow Customer buy balance with any amount')}
                            </small>
                        </p>
                    </div>
                    <div class="st-savebar">
                        <button class="btn btn-success" name="general" type="submit"><i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="telegram">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Telegram Bot Token')}</label>
                        <div class="col-md-5">
                            <input type="password" class="form-control" id="telegram_bot" name="telegram_bot"
                                onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'"
                                value="{$_c['telegram_bot']}" placeholder="123456:asdasgdkuasghddlashdashldhalskdklasd">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Telegram User/Channel/Group ID')}</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" id="telegram_target_id" name="telegram_target_id"
                                value="{$_c['telegram_target_id']}" placeholder="12345678">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Test')}</label>
                        <div class="col-md-2">
                            <button class="btn btn-info" type="button" onclick="javascript:testTg();">
                                {Lang::T('Test Telegram Notification')}
                            </button>
                        </div>
                    </div>
                    <small id="emailHelp" class="form-text text-muted">
                        {Lang::T('You will get Payment and Error notification')}
                    </small>
                    <div class="st-savebar">
                        <button class="btn btn-success" name="general" type="submit"><i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="sms">
                    {include file="admin/settings/routely-intro.tpl" rl_for="sms"}
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('SMS Server URL')}</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" id="sms_url" name="sms_url" value="{$_c['sms_url']}"
                                placeholder="https://domain/?param_number=[number]&param_text=[text]&secret=">
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Must include')} <b>[text]</b> &amp; <b>[number]</b>,
                            {Lang::T('it will be replaced.')}
                        </p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Or use Mikrotik SMS')}</label>
                        <div class="col-md-5">
                            <select class="form-control"
                                onchange="document.getElementById('sms_url').value = this.value">
                                <option value="">{Lang::T('Select Router')}</option>
                                {foreach $r as $rs}
                                <option value="{$rs['name']}" {if $rs['name']==$_c['sms_url']}selected{/if}>
                                    {$rs['name']}</option>
                                {/foreach}
                            </select>
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Must include')} <b>[text]</b> &amp; <b>[number]</b>,
                            {Lang::T('it will be replaced.')}
                        </p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Mikrotik SMS Command')}</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" id="mikrotik_sms_command"
                                name="mikrotik_sms_command" value="{$_c['mikrotik_sms_command']}"
                                placeholder="mikrotik_sms_command">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Test SMS')}</label>
                        <div class="col-md-2">
                            <button class="btn btn-info" type="button" onclick="javascript:testSms();">
                                {Lang::T('Send Test SMS')}
                            </button>
                        </div>
                    </div>
                    {include file="admin/settings/free-gateway.tpl" fg_for="sms"}

                    <div class="st-savebar">
                        <button class="btn btn-success" name="general" type="submit"><i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="whatsapp">
                    {include file="admin/settings/routely-intro.tpl" rl_for="wa"}
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('WhatsApp Server URL')}</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" id="wa_url" name="wa_url" value="{$_c['wa_url']}"
                                placeholder="https://domain/?param_number=[number]&param_text=[text]&secret=">
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Must include')} <b>[text]</b> &amp; <b>[number]</b>,
                            {Lang::T('it will be replaced.')}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Test WhatsApp')}</label>
                        <div class="col-md-2">
                            <button class="btn btn-info" type="button" onclick="javascript:testWa();">
                                {Lang::T('Test WhatsApp Notification')}
                            </button>
                        </div>
                    </div>
                    {include file="admin/settings/free-gateway.tpl" fg_for="wa"}
                    <div class="st-savebar">
                        <button class="btn btn-success" name="general" type="submit"><i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="email">
                    <div class="form-group">
                        <label class="col-md-3 control-label">SMTP Host : Port</label>
                        <div class="col-md-3">
                            <input type="text" class="form-control" id="smtp_host" name="smtp_host"
                                value="{$_c['smtp_host']}" placeholder="smtp.host.tld">
                        </div>
                        <div class="col-md-2">
                            <input type="number" class="form-control" id="smtp_port" name="smtp_port"
                                value="{$_c['smtp_port']}" placeholder="465 587 port">
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Empty this to use internal mail() PHP')}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('SMTP Username')}</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" id="smtp_user" name="smtp_user"
                                value="{$_c['smtp_user']}" placeholder="user@host.tld">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('SMTP Password')}</label>
                        <div class="col-md-5">
                            <input type="password" class="form-control" id="smtp_pass" name="smtp_pass"
                                value="{$_c['smtp_pass']}" onmouseleave="this.type = 'password'"
                                onmouseenter="this.type = 'text'">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('SMTP Security')}</label>
                        <div class="col-md-5">
                            <select name="smtp_ssltls" id="smtp_ssltls" class="form-control">
                                <option value="" {if $_c['smtp_ssltls']=='' }selected="selected" {/if}>Not Secure
                                </option>
                                <option value="ssl" {if $_c['smtp_ssltls']=='ssl' }selected="selected" {/if}>SSL
                                </option>
                                <option value="tls" {if $_c['smtp_ssltls']=='tls' }selected="selected" {/if}>TLS
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">UPPERCASE lowercase RaNdoM</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">Mail {Lang::T('From')}</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" id="mail_from" name="mail_from"
                                value="{$_c['mail_from']}" placeholder="noreply@host.tld">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Mail Reply To')}</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" id="mail_reply_to" name="mail_reply_to"
                                value="{$_c['mail_reply_to']}" placeholder="support@host.tld">
                        </div>
                        <p class="help-block col-md-4">
                            {Lang::T('Empty if you want to use From Address')}
                        </p>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Test Email')}</label>
                        <div class="col-md-2">
                            <button class="btn btn-info" type="button" onclick="javascript:testEmail();">
                                {Lang::T('Test Email Notification')}
                            </button>
                        </div>
                    </div>

                    <div class="st-savebar">
                        <button class="btn btn-success" name="general" type="submit"><i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="usernotif">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Expired Notification')}</label>
                        <label class="col-md-1 control-label"><input type="checkbox"
                                name="user_notification_expired_sms" {if $_c['user_notification_expired_sms']=='1'
                                }checked{/if} value="1">
                            {Lang::T('SMS')}</label>
                        <label class="col-md-1 control-label"><input type="checkbox" name="user_notification_expired_wa"
                                {if $_c['user_notification_expired_wa']=='1' }checked{/if} value="1">
                            {Lang::T('WA')}</label>
                        <label class="col-md-1 control-label"><input type="checkbox"
                                name="user_notification_expired_email" {if $_c['user_notification_expired_email']=='1'
                                }checked{/if} value="1">
                            {Lang::T('Email')}</label>
                        <label class="col-md-1 control-label"><input type="checkbox"
                                name="user_notification_expired_inbox" {if $_c['user_notification_expired_inbox']=='1'
                                }checked{/if} value="1">
                            {Lang::T('Inbox')}</label>
                        <label class="col-md-1 control-label"><input type="checkbox"
                                name="user_notification_expired_push" {if $_c['user_notification_expired_push']=='1'
                                }checked{/if} value="1">
                            {Lang::T('Push')}</label>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Payment Notification')}</label>
                        <label class="col-md-1 control-label"><input type="checkbox"
                                name="user_notification_payment_sms" {if $_c['user_notification_payment_sms']=='1'
                                }checked{/if} value="1">
                            {Lang::T('SMS')}</label>
                        <label class="col-md-1 control-label"><input type="checkbox" name="user_notification_payment_wa"
                                {if $_c['user_notification_payment_wa']=='1' }checked{/if} value="1">
                            {Lang::T('WA')}</label>
                        <label class="col-md-1 control-label"><input type="checkbox"
                                name="user_notification_payment_email" {if $_c['user_notification_payment_email']=='1'
                                }checked{/if} value="1">
                            {Lang::T('Email')}</label>
                        <label class="col-md-1 control-label"><input type="checkbox"
                                name="user_notification_payment_inbox" {if $_c['user_notification_payment_inbox']=='1'
                                }checked{/if} value="1">
                            {Lang::T('Inbox')}</label>
                        <label class="col-md-1 control-label"><input type="checkbox"
                                name="user_notification_payment_push" {if $_c['user_notification_payment_push']=='1'
                                }checked{/if} value="1">
                            {Lang::T('Push')}</label>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Reminder Notification')}</label>
                        <label class="col-md-1 control-label"><input type="checkbox"
                                name="user_notification_reminder_sms" {if $_c['user_notification_reminder_sms']=='1'
                                }checked{/if} value="1">
                            {Lang::T('SMS')}</label>
                        <label class="col-md-1 control-label"><input type="checkbox"
                                name="user_notification_reminder_wa" {if $_c['user_notification_reminder_wa']=='1'
                                }checked{/if} value="1">
                            {Lang::T('WA')}</label>
                        <label class="col-md-1 control-label"><input type="checkbox"
                                name="user_notification_reminder_email" {if $_c['user_notification_reminder_email']=='1'
                                }checked{/if} value="1">
                            {Lang::T('Email')}</label>
                        <label class="col-md-1 control-label"><input type="checkbox"
                                name="user_notification_reminder_inbox" {if $_c['user_notification_reminder_inbox']=='1'
                                }checked{/if} value="1">
                            {Lang::T('Inbox')}</label>
                        <label class="col-md-1 control-label"><input type="checkbox"
                                name="user_notification_reminder_push" {if $_c['user_notification_reminder_push']=='1'
                                }checked{/if} value="1">
                            {Lang::T('Push')}</label>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Reminder Notify Intervals')}</label>
                        <label class="col-md-1 control-label"><input type="checkbox" name="notification_reminder_1day"
                                value="yes" {if !isset($_c['notification_reminder_1day']) ||
                                $_c['notification_reminder_1day'] neq 'no' }checked{/if}>
                            {Lang::T('1 Day')}</label>
                        <label class="col-md-1 control-label"><input type="checkbox" name="notification_reminder_3days"
                                value="yes" {if !isset($_c['notification_reminder_3days']) ||
                                $_c['notification_reminder_3days'] neq 'no' }checked{/if}>
                            {Lang::T('3 Days')}</label>
                        <label class="col-md-1 control-label"><input type="checkbox" name="notification_reminder_7days"
                                value="yes" {if !isset($_c['notification_reminder_7days']) ||
                                $_c['notification_reminder_7days'] neq 'no' }checked{/if}>
                            {Lang::T('7 Days')}</label>
                    </div>

                    <div class="st-savebar">
                        <button class="btn btn-success" name="general" type="submit"><i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="tawkto">
                    <div class="form-group">
                        <label class="col-md-3 control-label">https://tawk.to/chat/</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" id="tawkto" name="tawkto" value="{$_c['tawkto']}"
                                placeholder="62f1ca7037898912e961f5/1ga07df">
                        </div>
                        <p class="help-block col-md-4">{Lang::T('From Direct Chat Link.')}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">Tawk.to Javascript API key</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" id="tawkto_api_key" name="tawkto_api_key"
                                value="{$_c['tawkto_api_key']}" placeholder="39e52264cxxxxxxxxxxxxxdd078af5342e8">
                        </div>
                    </div>
                    <div class="st-savebar">
                        <button class="btn btn-success" name="general" type="submit"><i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
                    </div>
                   <br>
                    <p class="col-md-5 help-block">/ip hotspot walled-garden<br>
                        add dst-host=tawk.to<br>
                        add dst-host=*.tawk.to</p>
                        
                    <br>
                </div>

                <div role="tabpanel" class="tab-pane" id="apikey">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Access Token')}</label>
                        <div class="col-md-5">
                            <input type="password" class="form-control" id="api_key" name="api_key"
                                value="{$_c['api_key']}"
                                placeholder="{Lang::T('Empty this to randomly created API key')}"
                                onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'">
                        </div>
                        <p class="col-md-4 help-block">{Lang::T('This Token will act as SuperAdmin/Admin')}</p>
                    </div>
                    <div class="st-savebar">
                        <button class="btn btn-success" name="general" type="submit"><i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="proxy">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Proxy Server')}</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" id="http_proxy" name="http_proxy"
                                value="{$_c['http_proxy']}" placeholder="127.0.0.1:3128">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Proxy Server Login')}</label>
                        <div class="col-md-5">
                            <input type="password" class="form-control" id="http_proxyauth" name="http_proxyauth"
                                autocomplete="off" value="{$_c['http_proxyauth']}" placeholder="username:password"
                                onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'">
                        </div>
                    </div>
                   <div class="st-savebar">
                       <button class="btn btn-success" name="general" type="submit"><i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
                   </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="tax">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Enable Tax System')}</label>
                        <div class="col-md-5">
                            <select name="enable_tax" id="enable_tax" class="form-control">
                                <option value="no" {if $_c['enable_tax']=='no' }selected="selected" {/if}>
                                    {Lang::T('No')}
                                </option>
                                <option value="yes" {if $_c['enable_tax']=='yes' }selected="selected" {/if}>
                                    {Lang::T('Yes')}
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Tax will be calculated in Internet Plan Price')}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Tax Rate')}</label>
                        <div class="col-md-5">
                            <select name="tax_rate" id="tax_rate" class="form-control">
                                <option value="0.5" {if $_c['tax_rate']=='0.5' }selected="selected" {/if}>
                                    0.5
                                </option>
                                <option value="1" {if $_c['tax_rate']=='1' }selected="selected" {/if}>
                                    1
                                </option>
                                <option value="1.5" {if $_c['tax_rate']=='1.5' }selected="selected" {/if}>
                                    1.5
                                </option>
                                <option value="2" {if $_c['tax_rate']=='2' }selected="selected" {/if}>
                                    2
                                </option>
                                <option value="5" {if $_c['tax_rate']=='5' }selected="selected" {/if}>
                                    5
                                </option>
                                <option value="10" {if $_c['tax_rate']=='10' }selected="selected" {/if}>
                                    10
                                </option>
                                <!-- Custom tax rate option -->
                                <option value="custom" {if $_c['tax_rate']=='custom' }selected="selected" {/if}>
                                    {Lang::T('Custome')}</option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Tax Rates by percentage')}</p>
                    </div>
                    <!-- Custom tax rate input field (initially hidden) -->
                    <div class="form-group" id="customTaxRate" style="display: none;">
                        <label class="col-md-3 control-label">{Lang::T('Custome Tax Rate')}</label>
                        <div class="col-md-5">
                            <input type="text" value="{$_c['custom_tax_rate']}" class="form-control"
                                name="custom_tax_rate" id="custom_tax_rate"
                                placeholder="{Lang::T('Enter Custome Tax Rate')}">
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Enter the custom tax rate (e.g., 3.75 for 3.75%)')}</p>
                    </div>

                    <div class="st-savebar">
                        <button class="btn btn-success" name="general" type="submit"><i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="github">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Github Username')}</label>
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-addon">https://github.com/</span>
                                <input type="text" class="form-control" id="github_username" name="github_username"
                                    value="{$_c['github_username']}" placeholder="ibnux">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Github Token')}</label>
                        <div class="col-md-5">
                            <input type="password" class="form-control" id="github_token" name="github_token"
                                value="{$_c['github_token']}" placeholder="ghp_........"
                                onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'">
                        </div>
                        <span class="help-block col-md-4"><a href="https://github.com/settings/tokens/new"
                                target="_blank">{Lang::T('Create GitHub personal access token')} (classic)</a>,
                            {Lang::T('only need repo
                            scope')}</span>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-md-offset-2 col-md-8" style="text-align: left;">{Lang::T('This
                            will allow
                            you to download plugin from private/paid repository')}</label>
                    </div>
                    <div class="st-savebar">
                        <button class="btn btn-success" name="general" type="submit"><i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="cron">
                    <div class="bs-callout bs-callout-info" id="callout-navbar-role">
                        <h4><b>{Lang::T('Settings For Mikrotik')}</b></h4>
                        <p>/ip hotspot walled-garden <br>
                            add dst-host={$_domain} <br>
                            add dst-host=*.{$_domain}
                        </p>
                        <br>
                        <h4><b>{Lang::T('Settings For Cron Expired')}</b></h4>
                        <p>
                            <span class="st-cmd-note">{Lang::T('Expired Cronjob Every 5 Minutes [Recommended]')}</span>
                            <span class="st-cmd"><code>*/5 * * * * cd {$dir} && {$php} cron.php</code></span>
                            <span class="st-cmd-note">{Lang::T('Expired Cronjob Every 1 Hour')}</span>
                            <span class="st-cmd"><code>0 * * * * cd {$dir} && {$php} cron.php</code></span>
                        </p>
                        <br>
                        <h4><b>{Lang::T('Settings For Cron Reminder')}</b></h4>
                        <p>
                            <span class="st-cmd-note">{Lang::T('Reminder Cronjob Every 7 AM')}</span>
                            <span class="st-cmd"><code>0 7 * * * cd {$dir} && {$php} cron_reminder.php</code></span>
                        </p>
                        <br>
                        <h4><b>{Lang::T('Run Cron From Web')}</b></h4>
                        <p>
                            <span class="st-cmd"><code><a href="{$app_url}/system/cron.php?secret={$_c['api_key']}" target="_blank">{$app_url}/system/cron.php?secret={$_c['api_key']}</a></code></span>
                            <span class="st-cmd"><code><a href="{$app_url}/system/cron_reminder.php?secret={$_c['api_key']}" target="_blank">{$app_url}/system/cron_reminder.php?secret={$_c['api_key']}</a></code></span>
                        </p>
                        <br>
                        <h4><b>{Lang::T('Cron Settings to clear expired vouchers older than 30 days')}</b></h4>
                        <p>
                            <span class="st-cmd-note">{Lang::T('Clear expired vouchers cronjob to run every 12AM 0r 00:00HR')}</span>
                            <span class="st-cmd"><code>0 0 * * * cd {$dir}cron && {$php} clean_expired_vouchers.php</code></span>
                            <span class="st-cmd-note">{Lang::T('To check the expired voucher or clear expired voucher manually')}</span>
                            <span class="st-cmd-label">Check</span>
                            <span class="st-cmd"><code><a href="{$app_url}/system/cron/clean_expired_vouchers.php?secret={$_c['api_key']}&dry-run=1" target="_blank">{$app_url}/system/cron/clean_expired_vouchers.php?secret={$_c['api_key']}&dry-run=1</a></code></span>
                            <span class="st-cmd-label">Clear</span>
                            <span class="st-cmd"><code><a href="{$app_url}/system/cron/clean_expired_vouchers.php?secret={$_c['api_key']}" target="_blank">{$app_url}/system/cron/clean_expired_vouchers.php?secret={$_c['api_key']}</a></code></span>
                        </p>
                        <br>
                        <h4><b>{Lang::T('Cron Settings to clear freeradius stale sessions')}</b></h4>
                        <p>
                            <span class="st-cmd-note">{Lang::T('The main cron job (system/cron.php) clears stale FreeRADIUS sessions on every run, but only while Clear Stale Sessions is on with a timeout above 0; running it every 5 minutes or less does not clean up by itself.')}</span>
                            <span class="st-cmd-note">{Lang::T('Optional: if cleanup is on and the main cron job runs less often than every 5 minutes, add this to clear stale sessions sooner. Updates still waiting in the accounting queue delay it until they are written.')}</span>
                            <span class="st-cmd"><code>*/5 * * * * cd {$dir}cron && {$php} cleanup_stale_sessions.php</code></span>
                        </p>
                        <br>
                        <h4><b>{Lang::T('Marketplace automatic checks')}</b></h4>
                        <p>
                            <span class="st-cmd-note">{Lang::T('Sync the Marketplace store, detect plugin, gateway, device and theme updates, and send the Telegram notification. Every 6 hours [Recommended]')}</span>
                            <span class="st-cmd"><code>0 */6 * * * cd {$dir}cron && {$php} marketplace_sync.php</code></span>
                            <small class="text-muted">{Lang::T('Command line only, it cannot be run from the web. Turn the notification on or off in Marketplace > Tools > Settings.')}</small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Toggle Button -->
    <button type="button" class="settings-menu-toggle" id="settingsMenuToggle">
        <i class="glyphicon glyphicon-menu-hamburger"></i>
    </button>

    <!-- Mobile Overlay -->
    <div class="settings-overlay" id="settingsOverlay"></div>
</form>

<script>
    // the floating Save stays above the footer when the theme pins it to the bottom of the screen
    document.addEventListener('DOMContentLoaded', function () {
        var footer = document.querySelector('.main-footer');
        function lift() {
            var pinned = footer && getComputedStyle(footer).position === 'fixed';
            document.documentElement.style.setProperty('--st-savebar-bottom', (pinned ? footer.offsetHeight : 0) + 'px');
        }
        lift();
        if (footer && window.ResizeObserver) {
            new ResizeObserver(lift).observe(footer);
        }
        window.addEventListener('resize', lift);
    });
</script>

<script>
    // Mobile menu functionality
    (function () {
        var menuToggle = document.getElementById('settingsMenuToggle');
        var sidebar = document.querySelector('.settings-sidebar');
        var overlay = document.getElementById('settingsOverlay');
        var menuLinks = document.querySelectorAll('.settings-sidebar .nav-pills li a');

        if (menuToggle && sidebar && overlay) {
            // Toggle menu on button click
            menuToggle.addEventListener('click', function () {
                sidebar.classList.toggle('mobile-open');
                overlay.classList.toggle('active');
            });

            // Close menu on overlay click
            overlay.addEventListener('click', function () {
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('active');
            });

            // Close menu when a menu item is clicked (on mobile)
            menuLinks.forEach(function (link) {
                link.addEventListener('click', function () {
                    if (window.innerWidth <= 768) {
                        sidebar.classList.remove('mobile-open');
                        overlay.classList.remove('active');
                    }
                });
            });

            // Close menu on window resize if screen becomes large
            window.addEventListener('resize', function () {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('mobile-open');
                    overlay.classList.remove('active');
                }
            });
        }
    })();
</script>

<script>
    function testWa() {
        var target = prompt("Phone number\nSave First before Test", "");
        if (target != null) {
            window.location.href = '{Text::url('settings/app&testWa=')}' + target;
        }
    }

    function testSms() {
        var target = prompt("Phone number\nSave First before Test", "");
        if (target != null) {
            window.location.href = '{Text::url('settings/app&testSms=')}' + target;
        }
    }


    function testEmail() {
        var target = prompt("Email\nSave First before Test", "");
        if (target != null) {
            window.location.href = '{Text::url('settings/app&testEmail=')}' + target;
        }
    }

    function testTg() {
        window.location.href = '{Text::url('settings/app&testTg=test')}';
    }
</script>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        var sectionTimeoutCheckbox = document.getElementById('enable_session_timeout');
        var timeoutDurationInput = document.getElementById('timeout_duration_input');
        var timeoutDurationField = document.getElementById('session_timeout_duration');

        if (sectionTimeoutCheckbox.checked) {
            timeoutDurationInput.style.display = ''; // the stylesheet's row layout
            timeoutDurationField.required = true;
        }

        sectionTimeoutCheckbox.addEventListener('change', function () {
            if (this.checked) {
                timeoutDurationInput.style.display = ''; // the stylesheet's row layout
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

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Function to toggle visibility of custom tax rate input field
        function toggleCustomTaxRate() {
            var taxRateSelect = document.getElementById("tax_rate");
            var customTaxRateInput = document.getElementById("customTaxRate");

            if (taxRateSelect.value === "custom") {
                customTaxRateInput.style.display = "";
            } else {
                customTaxRateInput.style.display = "none";
            }
        }

        // Call the function when the page loads
        toggleCustomTaxRate();

        // Call the function whenever the tax rate dropdown value changes
        document.getElementById("tax_rate").addEventListener("change", toggleCustomTaxRate);
    });
</script>
<script>
    document.getElementById('login_page_type').addEventListener('change', function () {
        var selectedValue = this.value;
        var customFields = document.getElementById('customFields');

        if (selectedValue === 'custom') {
            customFields.style.display = '';
        } else {
            customFields.style.display = 'none';
        }
    });
    document.getElementById('login_page_type').dispatchEvent(new Event('change'));
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var radiusEnable = document.getElementById('radius_enable');
        var clearGroup = document.getElementById('clear_stale_sessions_group');
        var clearSelect = document.getElementById('clear_stale_sessions');
        var timeoutGroup = document.getElementById('stale_session_timeout_group');

        function toggleRadiusFields() {
            var radiusOn = radiusEnable && radiusEnable.value === '1';
            if (!radiusOn) {
                if (clearGroup) clearGroup.style.display = 'none';
                if (timeoutGroup) timeoutGroup.style.display = 'none';
                return;
            }

            if (clearGroup) clearGroup.style.display = '';
            var clearOn = clearSelect && clearSelect.value === '1';
            if (timeoutGroup) timeoutGroup.style.display = clearOn ? '' : 'none';
        }

        // an out-of-range retention value sits on the FreeRadius tab: open it before the browser tries to focus the field
        var keepDays = document.getElementById('frrest_keep_days');
        if (keepDays) keepDays.addEventListener('invalid', function () {
            var tabLink = document.querySelector('a[href="#radius"][data-toggle="tab"]');
            if (tabLink && window.jQuery) window.jQuery(tabLink).tab('show');
        });
        if (radiusEnable) radiusEnable.addEventListener('change', toggleRadiusFields);
        if (clearSelect) clearSelect.addEventListener('change', toggleRadiusFields);
        toggleRadiusFields();

        // Captcha provider fields toggle
        var captchaProvider = document.getElementById('captcha_provider');
        var captchaTargetGroup = document.getElementById('captcha_target_group');
        var turnstileSection = document.getElementById('turnstile_keys_section');
        var recaptchaSection = document.getElementById('recaptcha_keys_section');

        function toggleCaptchaFields() {
            var val = captchaProvider ? captchaProvider.value : 'none';
            if (val === 'turnstile') {
                if (captchaTargetGroup) captchaTargetGroup.style.display = '';
                if (turnstileSection) turnstileSection.style.display = '';
                if (recaptchaSection) recaptchaSection.style.display = 'none';
            } else if (val === 'recaptcha') {
                if (captchaTargetGroup) captchaTargetGroup.style.display = '';
                if (turnstileSection) turnstileSection.style.display = 'none';
                if (recaptchaSection) recaptchaSection.style.display = '';
            } else {
                if (captchaTargetGroup) captchaTargetGroup.style.display = 'none';
                if (turnstileSection) turnstileSection.style.display = 'none';
                if (recaptchaSection) recaptchaSection.style.display = 'none';
            }
        }

        if (captchaProvider) {
            captchaProvider.addEventListener('change', toggleCaptchaFields);
            toggleCaptchaFields();
        }
    });
</script>
{include file="sections/footer.tpl"}