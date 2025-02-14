{include file="sections/header.tpl"}
<style>
    .panel-title {
        font-weight: bolder;
        font-size: large;
    }
</style>

<form class="form-horizontal" method="post" role="form" action="{Text::url('')}settings/app-post"
    enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    <div class="panel" id="accordion" role="tablist" aria-multiselectable="true">
        <div class="panel-heading" role="tab" id="General">
            <h3 class="panel-title">
                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseGeneral"
                    aria-expanded="true" aria-controls="collapseGeneral">
                    {Lang::T('General')}
                </a>
            </h3>
        </div>
        <div id="collapseGeneral" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
            <div class="panel-body">
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
                        <span
                            class="help-block">{Lang::T('For PDF Reports | Best size 1078 x 200 | uploaded image will be autosize')}</span>
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
                        <input type="number" required class="form-control" id="printer_cols" placeholder="37"
                            name="printer_cols" value="{$_c['printer_cols']}">
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
                    <p class="help-block col-md-4"><a href="https://github.com/hotspotbilling/phpnuxbill/wiki/Themes"
                            target="_blank">{Lang::T('Theme Info')}</a></p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Recharge Using')}</label>
                    <div class="col-md-5">
                        <input type="text" name="payment_usings" class="form-control" value="{$_c['payment_usings']}"
                            placeholder="{Lang::T('Cash')}, {Lang::T('Bank Transfer')}">
                    </div>
                    <p class="help-block col-md-4">
                        {Lang::T('This used for admin to select payment in recharge, using comma for every new options')}
                    </p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Income reset date')}</label>
                    <div class="col-md-5">
                        <input type="number" required class="form-control" id="reset_day" placeholder="20" min="1"
                            max="28" step="1" name="reset_day" value="{$_c['reset_day']}">
                    </div>
                    <span class="help-block col-md-4">{Lang::T('Income will reset every this day')}</span>
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
                <button class="btn btn-success btn-block" name="general" type="submit">
                    {Lang::T('Save Changes')}
                </button>
            </div>

        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="HideDashboardContent">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseHideDashboardContent" aria-expanded="false"
                    aria-controls="collapseHideDashboardContent">
                    {Lang::T('Hide Dashboard Content')}
                </a>
            </h4>
        </div>
        <div id="collapseHideDashboardContent" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-3 control-label"><input type="checkbox" name="hide_mrc" value="yes" {if
                            $_c['hide_mrc'] eq 'yes' }checked{/if}>
                        {Lang::T('Monthly Registered Customers')}</label>
                    <label class="col-md-3 control-label"><input type="checkbox" name="hide_tms" value="yes" {if
                            $_c['hide_tms'] eq 'yes' }checked{/if}> {Lang::T('Total Monthly Sales')}</label>
                    <label class="col-md-3 control-label"><input type="checkbox" name="hide_aui" value="yes" {if
                            $_c['hide_aui'] eq 'yes' }checked{/if}> {Lang::T('All Users Insights')}</label>
                    <label class="col-md-3 control-label"><input type="checkbox" name="hide_al" value="yes" {if
                            $_c['hide_al'] eq 'yes' }checked{/if}> {Lang::T('Activity Log')}</label>
                    <label class="col-md-3 control-label"><input type="checkbox" name="hide_uet" value="yes" {if
                            $_c['hide_uet'] eq 'yes' }checked{/if}> {Lang::T('User Expired, Today')}</label>
                    <label class="col-md-3 control-label"><input type="checkbox" name="hide_vs" value="yes" {if
                            $_c['hide_vs'] eq 'yes' }checked{/if}> Vouchers Stock</label>
                    <label class="col-md-3 control-label"><input type="checkbox" name="hide_pg" value="yes" {if
                            $_c['hide_pg'] eq 'yes' }checked{/if}> Payment Gateway</label>
                </div>
                <button class="btn btn-success btn-block" type="submit">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel" id="accordion" role="tablist" aria-multiselectable="true">
        <div class="panel-heading" role="tab" id="LoginPage">
            <h3 class="panel-title">
                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseLoginPage"
                    aria-expanded="true" aria-controls="collapseLoginPage">
                    {Lang::T('Customer Login Page Settings')}
                </a>
            </h3>
        </div>
        <div id="collapseLoginPage" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Choose Template')}</label>
                    <div class="col-md-5">
                        <select name="login_page_type" id="login_page_type" class="form-control">
                            <option value="default" {if $_c['login_page_type']=='default' }selected="selected" {/if}>
                                {Lang::T('Default')}</option>
                            <option value="custom" {if $_c['login_page_type']=='custom' }selected="selected" {/if}>
                                {Lang::T('Custom')}</option>
                        </select>
                    </div>
                    <span class="help-block col-md-4"><small>{Lang::T('Select your login template type')}</small></span>
                </div>
                <div id="customFields" style="display: none;">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Select Login Page')}</label>
                        <div class="col-md-5">
                            <select name="login_Page_template" id="login_Page_template" class="form-control">
                                {foreach $template_files as $template}
                                    <option value="{$template.value|escape}"
                                        {if $_c['login_Page_template'] eq $template.value}selected="selected" {/if}>
                                        {$template.name|escape}</option>
                                {/foreach}
                            </select>
                        </div>
                        <span
                            class="help-block col-md-4"><small>{Lang::T('Select your preferred login template')}</small></span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Page Heading / Company Name')}</label>
                        <div class="col-md-5">
                            <input type="text" class="form-control" id="login_page_head" name="login_page_head"
                                value="{$_c['login_page_head']}">
                        </div>
                        <span
                            class="help-block col-md-4"><small>{Lang::T('This Name will be shown on the login wallpaper')}</small></span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Page Description')}</label>
                        <div class="col-md-5">
                            <textarea class="form-control" id="login_page_description" name="login_page_description"
                                rows="3">{Lang::htmlspecialchars($_c['login_page_description'])}</textarea>
                        </div>
                        <span
                            class="help-block col-md-4"><small>{Lang::T('This will also display on wallpaper, You can use html tag')}</small></span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Favicon')}</label>
                        <div class="col-md-5">
                            <input type="file" class="form-control" id="login_page_favicon" name="login_page_favicon"
                                accept="image/*">
                            <span
                                class="help-block"><small>{Lang::T('Best size 30 x 30 | uploaded image will be autosize')}</small></span>
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
                            <span
                                class="help-block"><small>{Lang::T('Best size 300 x 60 | uploaded image will be autosize')}</small></span>
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
                            <span
                                class="help-block"><small>{Lang::T('Best size 1920 x 1080 | uploaded image will be autosize')}</small></span>
                        </div>
                        <span class="help-block col-md-4">
                            <a href="./{$wallpaper}" target="_blank"><img src="./{$wallpaper}" height="48"
                                    alt="Wallpaper"></a>
                        </span>
                    </div>
                </div>

                <button class="btn btn-success btn-block" name="general" type="submit">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="Registration">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseRegistration" aria-expanded="false" aria-controls="collapseRegistration">
                    {Lang::T('Registration')}
                </a>
            </h4>
        </div>
        <div id="collapseRegistration" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
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
                            <option value="noreg" {if $_c['disable_registration']=='noreg' }selected="selected" {/if}>
                                {Lang::T('No Registration')}
                            </option>
                        </select>
                    </div>
                    <p class="help-block col-md-4">
                        {Lang::T('Customer just Login with Phone number and Voucher Code, Voucher will be password')}
                    </p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Registration Username')}</label>
                    <div class="col-md-5">
                        <select name="registration_username" id="voucher_format" class="form-control">
                            <option value="username" {if $_c['registration_username']=='username' }selected="selected"
                                {/if}>Username
                            </option>
                            <option value="email" {if $_c['registration_username']=='email' }selected="selected" {/if}>
                                Email
                            </option>
                            <option value="phone" {if $_c['registration_username']=='phone' }selected="selected" {/if}>
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
                            <option value="whatsapp" {if $_c['phone_otp_type']=='whatsapp' }selected="selected" {/if}>
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
                    <label class="col-md-3 control-label">{Lang::T('Mandatory Fields')}:</label><br>
                    <label class="col-md-3 control-label">
                        <input type="checkbox" name="man_fields_email" value="yes"
                            {if !isset($_c['man_fields_email']) || $_c['man_fields_email'] neq 'no'}checked{/if}>
                        {Lang::T('Email')}
                    </label>
                    <label class="col-md-3 control-label">
                        <input type="checkbox" name="man_fields_fname" value="yes"
                            {if !isset($_c['man_fields_fname']) || $_c['man_fields_fname'] neq 'no'}checked{/if}>
                        {Lang::T('Full Name')}
                    </label>
                    <label class="col-md-3 control-label">
                        <input type="checkbox" name="man_fields_address" value="yes"
                            {if !isset($_c['man_fields_address']) || $_c['man_fields_address'] neq 'no'}checked{/if}>
                        {Lang::T('Address')}
                    </label>
                </div>
                <button class="btn btn-success btn-block" type="submit">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>


    <div class="panel">
        <div class="panel-heading" role="tab" id="Security">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseSecurity" aria-expanded="false" aria-controls="collapseSecurity">
                    {Lang::T('Security')}
                </a>
            </h4>
        </div>
        <div id="collapseSecurity" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Enable Session Timeout')}</label>
                    <div class="col-md-5">
                        <label class="switch">
                            <input type="checkbox" id="enable_session_timeout" value="1" name="enable_session_timeout"
                                {if $_c['enable_session_timeout']==1}checked{/if}>
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
                <button class="btn btn-success btn-block" type="submit">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="Voucher">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseVoucher" aria-expanded="false" aria-controls="collapseVoucher">
                    Voucher
                </a>
            </h4>
        </div>
        <div id="collapseVoucher" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
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
                <button class="btn btn-success btn-block" type="submit">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="FreeRadius">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseFreeRadius" aria-expanded="false" aria-controls="collapseFreeRadius">
                    FreeRadius
                </a>
            </h4>
        </div>
        <div id="collapseFreeRadius" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Enable Radius')}</label>
                    <div class="col-md-5">
                        <select name="radius_enable" id="radius_enable" class="form-control text-muted">
                            <option value="0">{Lang::T('No')}</option>
                            <option value="1" {if $_c['radius_enable']}selected="selected" {/if}>{Lang::T('Yes')}
                            </option>
                        </select>
                    </div>
                    <p class="help-block col-md-4"><a
                            href="https://github.com/hotspotbilling/phpnuxbill/wiki/FreeRadius"
                            target="_blank">{Lang::T('Radius Instructions')}</a></p>
                </div>
                <button class="btn btn-success btn-block" type="submit">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="ExtendPostpaidExpiration">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseExtendPostpaidExpiration" aria-expanded="false"
                    aria-controls="collapseExtendPostpaidExpiration">
                    {Lang::T('Extend Postpaid Expiration')}
                </a>
            </h4>
        </div>
        <div id="collapseExtendPostpaidExpiration" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
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
                <button class="btn btn-success btn-block" type="submit">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="CustomerBalanceSystem">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseCustomerBalanceSystem" aria-expanded="false"
                    aria-controls="collapseCustomerBalanceSystem">
                    {Lang::T('Customer Balance System')}
                </a>
            </h4>
        </div>
        <div id="collapseCustomerBalanceSystem" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
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
                <button class="btn btn-success btn-block" type="submit">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="TelegramNotification">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseTelegramNotification" aria-expanded="false"
                    aria-controls="collapseTelegramNotification">
                    {Lang::T('Telegram Notification')}
                    <div class="btn-group pull-right">
                        <a class="btn btn-success btn-xs" style="color: black;" href="javascript:testTg()">Test TG</a>
                    </div>
                </a>
            </h4>
        </div>
        <div id="collapseTelegramNotification" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
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
                <small id="emailHelp" class="form-text text-muted">
                    {Lang::T('You will get Payment and Error notification')}
                </small>
                <button class="btn btn-success btn-block" type="submit">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="SMSNotification">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseSMSNotification" aria-expanded="false" aria-controls="collapseSMSNotification">
                    {Lang::T('SMS Notification')}
                    <div class="btn-group pull-right">
                        <a class="btn btn-success btn-xs" style="color: black;" href="javascript:testSms()">
                            {Lang::T('Test SMS')}
                        </a>
                    </div>
                </a>
            </h4>
        </div>
        <div id="collapseSMSNotification" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
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
                        <select class="form-control" onchange="document.getElementById('sms_url').value = this.value">
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
                        <input type="text" class="form-control" id="mikrotik_sms_command" name="mikrotik_sms_command"
                            value="{$_c['mikrotik_sms_command']}" placeholder="mikrotik_sms_command">
                    </div>
                </div>
                <small id="emailHelp" class="form-text text-muted">{Lang::T('You can use')} WhatsApp
                    {Lang::T('in here too.')} <a href="https://wa.nux.my.id/login" target="_blank">{Lang::T('Free
                        Server')}</a></small>

                <button class="btn btn-success btn-block" type="submit">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="WhatsappNotification">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseWhatsappNotification" aria-expanded="false"
                    aria-controls="collapseWhatsappNotification">
                    {Lang::T('Whatsapp Notification')}
                    <div class="btn-group pull-right">
                        <a class="btn btn-success btn-xs" style="color: black;" href="javascript:testWa()">Test WA</a>
                    </div>
                </a>
            </h4>
        </div>
        <div id="collapseWhatsappNotification" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('WhatsApp Server URL')}</label>
                    <div class="col-md-5">
                        <input type="text" class="form-control" id="wa_url" name="wa_url" value="{$_c['wa_url']}"
                            placeholder="https://domain/?param_number=[number]&param_text=[text]&secret=">
                    </div>
                    <p class="help-block col-md-4">{Lang::T('Must include')} <b>[text]</b> &amp; <b>[number]</b>,
                        {Lang::T('it will be replaced.')}</p>
                </div>
                <small id="emailHelp" class="form-text text-muted">{Lang::T('You can use')} WhatsApp
                    {Lang::T('in here too.')} <a href="https://wa.nux.my.id/login" target="_blank">{Lang::T('Free
                        Server')}</a></small>
                <button class="btn btn-success btn-block" type="submit">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="EmailNotification">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseEmailNotification" aria-expanded="false" aria-controls="collapseEmailNotification">
                    {Lang::T('Email Notification')}
                    <div class="btn-group pull-right">
                        <a class="btn btn-success btn-xs" style="color: black;" href="javascript:testEmail()">Test
                            Email</a>
                    </div>
                </a>
            </h4>
        </div>
        <div id="collapseEmailNotification" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">SMTP Host : Port</label>
                    <div class="col-md-4">
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
                        {Lang::T('Customer will reply email to this address, empty if you want to use From
                        Address')}
                    </p>
                </div>

                <button class="btn btn-success btn-block" type="submit">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="UserNotification">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseUserNotification" aria-expanded="false" aria-controls="collapseUserNotification">
                    {Lang::T('User Notification')}
                </a>
            </h4>
        </div>
        <div id="collapseUserNotification" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Expired Notification')}</label>
                    <div class="col-md-5">
                        <select name="user_notification_expired" id="user_notification_expired" class="form-control">
                            <option value="none">{Lang::T('None')}</option>
                            <option value="wa" {if $_c['user_notification_expired']=='wa' }selected="selected" {/if}>
                                {Lang::T('By WhatsApp')}</option>
                            <option value="sms" {if $_c['user_notification_expired']=='sms' }selected="selected" {/if}>
                                {Lang::T('By SMS')}</option>
                            <option value="email" {if $_c['user_notification_expired']=='email' }selected="selected"
                                {/if}>{Lang::T('By Email')}</option>
                        </select>
                    </div>
                    <p class="help-block col-md-4">{Lang::T('User will get notification when package expired')}</p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Payment Notification')}</label>
                    <div class="col-md-5">
                        <select name="user_notification_payment" id="user_notification_payment" class="form-control">
                            <option value="none">{Lang::T('None')}</option>
                            <option value="wa" {if $_c['user_notification_payment']=='wa' }selected="selected" {/if}>
                                {Lang::T('By WhatsApp')}</option>
                            <option value="sms" {if $_c['user_notification_payment']=='sms' }selected="selected" {/if}>
                                {Lang::T('By SMS')}</option>
                            <option value="email" {if $_c['user_notification_payment']=='email' }selected="selected"
                                {/if}>{Lang::T('By Email')}</option>
                        </select>
                    </div>
                    <p class="help-block col-md-4">
                        {Lang::T('User will get invoice notification when buy package or package refilled')}</p>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Reminder Notification')}</label>
                    <div class="col-md-5">
                        <select name="user_notification_reminder" id="user_notification_reminder" class="form-control">
                            <option value="none">{Lang::T('None')}</option>
                            <option value="wa" {if $_c['user_notification_reminder']=='wa' }selected="selected" {/if}>
                                {Lang::T('By WhatsApp')}</option>
                            <option value="sms" {if $_c['user_notification_reminder']=='sms' }selected="selected" {/if}>
                                {Lang::T('By SMS')}</option>
                            <option value="sms" {if $_c['user_notification_reminder']=='email' }selected="selected"
                                {/if}>{Lang::T('By Email')}</option>
                        </select>
                    </div>
                </div>
                <button class="btn btn-success btn-block" type="submit">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="TawkToChatWidget">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseTawkToChatWidget" aria-expanded="false" aria-controls="collapseTawkToChatWidget">
                    {Lang::T('Tawk.to Chat Widget')}
                </a>
            </h4>
        </div>
        <div id="collapseTawkToChatWidget" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
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
                <label class="col-md-2"></label>
                <p class="col-md-5 help-block">/ip hotspot walled-garden<br>
                    add dst-host=tawk.to<br>
                    add dst-host=*.tawk.to</p>
                <button class="btn btn-success btn-block" type="submit">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="APIKey">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseAPIKey" aria-expanded="false" aria-controls="collapseAPIKey">
                    API Key
                </a>
            </h4>
        </div>
        <div id="collapseAPIKey" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">{Lang::T('Access Token')}</label>
                    <div class="col-md-5">
                        <input type="password" class="form-control" id="api_key" name="api_key" value="{$_c['api_key']}"
                            placeholder="{Lang::T('Empty this to randomly created API key')}"
                            onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'">
                    </div>
                    <p class="col-md-4 help-block">{Lang::T('This Token will act as SuperAdmin/Admin')}</p>
                </div>
                <button class="btn btn-success btn-block" type="submit">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="Proxy">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseProxy"
                    aria-expanded="false" aria-controls="collapseProxy">
                    {Lang::T('Proxy')}
                </a>
            </h4>
        </div>
        <div id="collapseProxy" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
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
                <button class="btn btn-success btn-block" type="submit">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="TaxSystem">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseTaxSystem" aria-expanded="false" aria-controls="collapseTaxSystem">
                    {Lang::T('Tax System')}
                </a>
            </h4>
        </div>
        <div id="collapseTaxSystem" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
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
                        <input type="text" value="{$_c['custom_tax_rate']}" class="form-control" name="custom_tax_rate"
                            id="custom_tax_rate" placeholder="{Lang::T('Enter Custome Tax Rate')}">
                    </div>
                    <p class="help-block col-md-4">{Lang::T('Enter the custom tax rate (e.g., 3.75 for 3.75%)')}</p>
                </div>

                <button class="btn btn-success btn-block" type="submit">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading" role="tab" id="GithubAuthentication">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                    href="#collapseAuthentication" aria-expanded="false" aria-controls="collapseAuthentication">
                    Github {Lang::T('Authentication')}
                </a>
            </h4>
        </div>
        <div id="collapseAuthentication" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
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
                <button class="btn btn-success btn-block" type="submit">
                    {Lang::T('Save Changes')}
                </button>
            </div>
        </div>
    </div>
</form>
<div class="bs-callout bs-callout-info" id="callout-navbar-role">
    <h4><b>{Lang::T('Settings For Mikrotik')}</b></h4>
    <p>/ip hotspot walled-garden <br>
        add dst-host={$_domain} <br>
        add dst-host=*.{$_domain}
    </p>
    <br>
    <h4><b>{Lang::T('Settings For Cron Expired')}</b></h4>
    <p>
        # {Lang::T('Expired Cronjob Every 5 Minutes [Recommended]')}<br>
        */5 * * * * cd {$dir} && {$php} cron.php
        <br><br>
        # {Lang::T('Expired Cronjob Every 1 Hour')}<br>
        0 * * * * cd {$dir} && {$php} cron.php
    </p>
    <br>
    <h4><b>{Lang::T('Settings For Cron Reminder')}</b></h4>
    <p>
        # {Lang::T('Reminder Cronjob Every 7 AM')}<br>
        0 7 * * * cd {$dir} && {$php} cron_reminder.php
    </p>
</div>

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
    document.addEventListener('DOMContentLoaded', function() {
        var sectionTimeoutCheckbox = document.getElementById('enable_session_timeout');
        var timeoutDurationInput = document.getElementById('timeout_duration_input');
        var timeoutDurationField = document.getElementById('session_timeout_duration');

        if (sectionTimeoutCheckbox.checked) {
            timeoutDurationInput.style.display = 'block';
            timeoutDurationField.required = true;
        }

        sectionTimeoutCheckbox.addEventListener('change', function() {
            if (this.checked) {
                timeoutDurationInput.style.display = 'block';
                timeoutDurationField.required = true;
            } else {
                timeoutDurationInput.style.display = 'none';
                timeoutDurationField.required = false;
            }
        });

        document.querySelector('form').addEventListener('submit', function(event) {
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
    document.addEventListener("DOMContentLoaded", function() {
        // Function to toggle visibility of custom tax rate input field
        function toggleCustomTaxRate() {
            var taxRateSelect = document.getElementById("tax_rate");
            var customTaxRateInput = document.getElementById("customTaxRate");

            if (taxRateSelect.value === "custom") {
                customTaxRateInput.style.display = "block";
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
    document.getElementById('login_page_type').addEventListener('change', function() {
        var selectedValue = this.value;
        var customFields = document.getElementById('customFields');

        if (selectedValue === 'custom') {
            customFields.style.display = 'block';
        } else {
            customFields.style.display = 'none';
        }
    });
    document.getElementById('login_page_type').dispatchEvent(new Event('change'));
</script>
{include file="sections/footer.tpl"}