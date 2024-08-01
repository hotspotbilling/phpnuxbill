{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" role="form" action="{$_url}settings/app-post" enctype="multipart/form-data">
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">
                    <div class="btn-group pull-right">
                        <button class="btn btn-primary btn-xs" title="save" type="submit"><span
                                class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span></button>
                    </div>
                    {Lang::T('General Settings')}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Application Name/ Company Name')}</label>
                        <div class="col-md-6">
                            <input type="text" required class="form-control" id="CompanyName" name="CompanyName"
                                value="{$_c['CompanyName']}">
                        </div>
                        <span class="help-block col-md-4">{Lang::T('This Name will be shown on the Title')}</span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Company Logo')}</label>
                        <div class="col-md-6">
                            <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                            <span class="help-block">For PDF Reports | Best size 1078 x 200 | uploaded image will be
                                autosize</span>
                        </div>
                        <span class="help-block col-md-4">
                            <a href="./{$logo}" target="_blank"><img src="./{$logo}" height="48" alt="logo for PDF"></a>
                        </span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Company Footer')}</label>
                        <div class="col-md-6">
                            <input type="text" required class="form-control" id="CompanyFooter" name="CompanyFooter"
                                value="{$_c['CompanyFooter']}">
                        </div>
                        <span class="help-block col-md-4">{Lang::T('Will show below user pages')}</span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Address')}</label>
                        <div class="col-md-6">
                            <textarea class="form-control" id="address" name="address"
                                rows="3">{Lang::htmlspecialchars($_c['address'])}</textarea>
                        </div>
                        <span class="help-block col-md-4">{Lang::T('You can use html tag')}</span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Phone Number')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="phone" name="phone" value="{$_c['phone']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Invoice Footer')}</label>
                        <div class="col-md-6">
                            <textarea class="form-control" id="note" name="note"
                                rows="3">{Lang::htmlspecialchars($_c['note'])}</textarea>
                            <span class="help-block">{Lang::T('You can use html tag')}</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label"><i class="glyphicon glyphicon-print"></i> Print Max
                            Char</label>
                        <div class="col-md-6">
                            <input type="number" required class="form-control" id="printer_cols" placeholder="37"
                                name="printer_cols" value="{$_c['printer_cols']}">
                        </div>
                        <span class="help-block col-md-4">For invoice print using Thermal Printer</span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Theme</label>
                        <div class="col-md-6">
                            <select name="theme" id="theme" class="form-control">
                                <option value="default" {if $_c['theme'] eq 'default' }selected="selected" {/if}>Default
                                </option>
                                {foreach $themes as $theme}
                                    <option value="{$theme}" {if $_c['theme'] eq $theme}selected="selected" {/if}>
                                        {Lang::ucWords($theme)}</option>
                                {/foreach}
                            </select>
                        </div>
                        <p class="help-block col-md-4"><a
                                href="https://github.com/hotspotbilling/phpnuxbill/wiki/Themes" target="_blank">Theme
                                info</a></p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Recharge Using')}</label>
                        <div class="col-md-6">
                            <input type="text" name="payment_usings" class="form-control"
                                value="{$_c['payment_usings']}"
                                placeholder="{Lang::T('Cash')}, {Lang::T('Bank Transfer')}">
                        </div>
                        <p class="help-block col-md-4">This used for admin to select payment in recharge, using comma
                            for every new options</p>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Income reset date')}</label>
                        <div class="col-md-6">
                            <input type="number" required class="form-control" id="reset_day" placeholder="20" min="1"
                                max="28" step="1" name="reset_day" value="{$_c['reset_day']}">
                        </div>
                        <span class="help-block col-md-4">Income will reset every this day</span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">APP URL</label>
                        <div class="col-md-6">
                            <input type="text" readonly class="form-control" value="{$app_url}">
                        </div>
                        <p class="help-block col-md-4">edit at config.php</p>
                    </div>
                </div>
                <div class="panel-heading" id="hide_dashboard_content">
                    <div class="btn-group pull-right">
                        <button class="btn btn-primary btn-xs" title="save" type="submit"><span
                                class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span></button>
                    </div>
                    Hide Dashboard Content
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-3 control-label"><input type="checkbox" name="hide_mrc" value="yes" {if
                                $_c['hide_mrc'] eq 'yes' }checked{/if}>
                            {Lang::T('Monthly Registered Customers')}</label>
                        <label class="col-md-2 control-label"><input type="checkbox" name="hide_tms" value="yes" {if
                                $_c['hide_tms'] eq 'yes' }checked{/if}> {Lang::T('Total Monthly Sales')}</label>
                        <label class="col-md-2 control-label"><input type="checkbox" name="hide_aui" value="yes" {if
                                $_c['hide_aui'] eq 'yes' }checked{/if}> {Lang::T('All Users Insights')}</label>
                        <label class="col-md-2 control-label"><input type="checkbox" name="hide_al" value="yes" {if
                                $_c['hide_al'] eq 'yes' }checked{/if}> {Lang::T('Activity Log')}</label>
                        <label class="col-md-2 control-label"><input type="checkbox" name="hide_uet" value="yes" {if
                                $_c['hide_uet'] eq 'yes' }checked{/if}> {Lang::T('User Expired, Today')}</label>
                        <label class="col-md-2 control-label"><input type="checkbox" name="hide_vs" value="yes" {if
                                $_c['hide_vs'] eq 'yes' }checked{/if}> Vouchers Stock</label>
                        <label class="col-md-2 control-label"><input type="checkbox" name="hide_pg" value="yes" {if
                                $_c['hide_pg'] eq 'yes' }checked{/if}> Payment Gateway</label>
                    </div>
                </div>
                <div class="panel-heading">
                    <div class="btn-group pull-right">
                        <button class="btn btn-primary btn-xs" title="save" type="submit"><span
                                class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span></button>
                    </div>
                    Voucher
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Disable Voucher')}</label>
                        <div class="col-md-6">
                            <select name="disable_voucher" id="disable_voucher" class="form-control">
                                <option value="no" {if $_c['disable_voucher']=='no' }selected="selected" {/if}>No
                                </option>
                                <option value="yes" {if $_c['disable_voucher']=='yes' }selected="selected" {/if}>Yes
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Voucher activation menu will be hidden')}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Voucher Format')}</label>
                        <div class="col-md-6">
                            <select name="voucher_format" id="voucher_format" class="form-control">
                                <option value="up" {if $_c['voucher_format']=='up' }selected="selected" {/if}>UPPERCASE
                                </option>
                                <option value="low" {if $_c['voucher_format']=='low' }selected="selected" {/if}>
                                    lowercase
                                </option>
                                <option value="rand" {if $_c['voucher_format']=='rand' }selected="selected" {/if}>
                                    RaNdoM
                                </option>
                                <option value="numbers" {if $_c['voucher_format'] == 'numbers'}selected="selected"
                                    {/if}>
                                    Numbers
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">UPPERCASE lowercase RaNdoM</p>
                    </div>
                    {if $_c['disable_voucher'] != 'yes'}
                        <div class="form-group">
                            <label class="col-md-2 control-label">{Lang::T('Disable Registration')}</label>
                            <div class="col-md-6">
                                <select name="disable_registration" id="disable_registration" class="form-control">
                                    <option value="no" {if $_c['disable_registration']=='no' }selected="selected" {/if}>No
                                    </option>
                                    <option value="yes" {if $_c['disable_registration']=='yes' }selected="selected" {/if}>
                                        Yes
                                    </option>
                                </select>
                            </div>
                            <p class="help-block col-md-4">
                                {Lang::T('Customer just Login with Phone number and Voucher Code, Voucher will be
                            password')}
                            </p>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">Redirect after Activation</label>
                            <div class="col-md-6">
                                <input type="text" class="form-control" id="voucher_redirect" name="voucher_redirect"
                                    placeholder="https://192.168.88.1/status" value="{$_c['voucher_redirect']}">
                            </div>
                            <p class="help-block col-md-4">
                                {Lang::T('After Customer activate voucher or login, customer will be redirected to this
                            url')}
                            </p>
                        </div>
                    {/if}
                </div>
                <div class="panel-heading">
                    <div class="btn-group pull-right">
                        <button class="btn btn-primary btn-xs" title="save" type="submit"><span
                                class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span></button>
                    </div>
                    FreeRadius
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">Enable Radius</label>
                        <div class="col-md-6">
                            <select name="radius_enable" id="radius_enable" class="form-control text-muted">
                                <option value="0">No</option>
                                <option value="1" {if $_c['radius_enable']}selected="selected" {/if}>Yes</option>
                            </select>
                        </div>
                        <p class="help-block col-md-4"><a
                                href="https://github.com/hotspotbilling/phpnuxbill/wiki/FreeRadius"
                                target="_blank">Radius Instructions</a></p>
                    </div>
                </div>
                <div class="panel-heading">
                    <div class="btn-group pull-right">
                        <button class="btn btn-primary btn-xs" title="save" type="submit"><span
                                class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span></button>
                    </div>
                    {Lang::T('Extend Postpaid Expiration')}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Allow Extend')}</label>
                        <div class="col-md-6">
                            <select name="extend_expired" id="extend_expired" class="form-control text-muted">
                                <option value="1" {if $_c['extend_expired']}selected="selected" {/if}>Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">Customer can request to extend expirations</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Extend Days')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="extend_days" placeholder="3"
                                value="{$_c['extend_days']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Confirmation Message')}</label>
                        <div class="col-md-6">
                            <textarea type="text" rows="4" class="form-control" name="extend_confirmation"
                                placeholder="i agree to extends and will paid full after this">{$_c['extend_confirmation']}</textarea>
                        </div>
                    </div>
                </div>
                <div class="panel-heading">
                    <div class="btn-group pull-right">
                        <button class="btn btn-primary btn-xs" title="save" type="submit"><span
                                class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span></button>
                    </div>
                    {Lang::T('Balance System')}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Enable System')}</label>
                        <div class="col-md-6">
                            <select name="enable_balance" id="enable_balance" class="form-control">
                                <option value="no" {if $_c['enable_balance']=='no' }selected="selected" {/if}>No
                                </option>
                                <option value="yes" {if $_c['enable_balance']=='yes' }selected="selected" {/if}>Yes
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Customer can deposit money to buy voucher')}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Allow Transfer')}</label>
                        <div class="col-md-6">
                            <select name="allow_balance_transfer" id="allow_balance_transfer" class="form-control">
                                <option value="no" {if $_c['allow_balance_transfer']=='no' }selected="selected" {/if}>
                                    No</option>
                                <option value="yes" {if $_c['allow_balance_transfer']=='yes' }selected="selected" {/if}>
                                    Yes</option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Allow balance transfer between customers')}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Minimum Balance Transfer')}</label>
                        <div class="col-md-6">
                            <input type="number" class="form-control" id="minimum_transfer" name="minimum_transfer"
                                value="{$_c['minimum_transfer']}">
                        </div>
                    </div>
                </div>
                <div class="panel-heading">
                    <div class="btn-group pull-right">
                        <a class="btn btn-success btn-xs" style="color: black;" href="javascript:testTg()">Test TG</a>
                        <button class="btn btn-primary btn-xs" title="save" type="submit"><span
                                class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span></button>
                    </div>
                    {Lang::T('Telegram Notification')}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">Telegram Bot Token</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" id="telegram_bot" name="telegram_bot"
                                onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'"
                                value="{$_c['telegram_bot']}" placeholder="123456:asdasgdkuasghddlashdashldhalskdklasd">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Telegram User/Channel/Group ID</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="telegram_target_id" name="telegram_target_id"
                                value="{$_c['telegram_target_id']}" placeholder="12345678">
                        </div>
                    </div>
                    <small id="emailHelp" class="form-text text-muted">You will get Payment and Error
                        notification</small>
                </div>
                <div class="panel-heading">
                    <div class="btn-group pull-right">
                        <a class="btn btn-success btn-xs" style="color: black;" href="javascript:testSms()">Test SMS</a>
                        <button class="btn btn-primary btn-xs" title="save" type="submit"><span
                                class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span></button>
                    </div>
                    {Lang::T('SMS OTP Registration')}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">SMS Server URL</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="sms_url" name="sms_url" value="{$_c['sms_url']}"
                                placeholder="https://domain/?param_number=[number]&param_text=[text]&secret=">
                        </div>
                        <p class="help-block col-md-4">Must include <b>[text]</b> &amp; <b>[number]</b>, it will be
                            replaced.
                        </p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Or use Mikrotik SMS</label>
                        <div class="col-md-6">
                            <select class="form-control"
                                onchange="document.getElementById('sms_url').value = this.value">
                                <option value="">Select Router</option>
                                {foreach $r as $rs}
                                    <option value="{$rs['name']}" {if $rs['name']==$_c['sms_url']}selected{/if}>
                                        {$rs['name']}</option>
                                {/foreach}
                            </select>
                        </div>
                        <p class="help-block col-md-4">Must include <b>[text]</b> &amp; <b>[number]</b>, it will be
                            replaced.
                        </p>
                    </div>
                    <small id="emailHelp" class="form-text text-muted">You can use WhatsApp in here too. <a
                            href="https://wa.nux.my.id/login" target="_blank">Free Server</a></small>
                </div>
                <div class="panel-heading">
                    <div class="btn-group pull-right">
                        <a class="btn btn-success btn-xs" style="color: black;" href="javascript:testWa()">Test WA</a>
                        <button class="btn btn-primary btn-xs" title="save" type="submit"><span
                                class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span></button>
                    </div>
                    {Lang::T('Whatsapp Notification')}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">Whatsapp Server URL</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="wa_url" name="wa_url" value="{$_c['wa_url']}"
                                placeholder="https://domain/?param_number=[number]&param_text=[text]&secret=">
                        </div>
                        <p class="help-block col-md-4">Must include <b>[text]</b> &amp; <b>[number]</b>, it will be
                            replaced.
                        </p>
                    </div>
                    <small id="emailHelp" class="form-text text-muted">You can use WhatsApp in here too. <a
                            href="https://wa.nux.my.id/login" target="_blank">Free Server</a></small>
                </div>
                <div class="panel-heading">
                    <div class="btn-group pull-right">
                        <a class="btn btn-success btn-xs" style="color: black;" href="javascript:testEmail()">Test
                            Email</a>
                        <button class="btn btn-primary btn-xs" title="save" type="submit"><span
                                class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span></button>
                    </div>
                    {Lang::T('Email Notification')}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">SMTP Host : port</label>
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="smtp_host" name="smtp_host"
                                value="{$_c['smtp_host']}" placeholder="smtp.host.tld">
                        </div>
                        <div class="col-md-2">
                            <input type="number" class="form-control" id="smtp_port" name="smtp_port"
                                value="{$_c['smtp_port']}" placeholder="465 587 port">
                        </div>
                        <p class="help-block col-md-4">Empty this to use internal mail() PHP</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">SMTP username</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="smtp_user" name="smtp_user"
                                value="{$_c['smtp_user']}" placeholder="user@host.tld">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">SMTP Password</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" id="smtp_pass" name="smtp_pass"
                                value="{$_c['smtp_pass']}" onmouseleave="this.type = 'password'"
                                onmouseenter="this.type = 'text'">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">SMTP Security</label>
                        <div class="col-md-6">
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
                        <label class="col-md-2 control-label">Mail From</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="mail_from" name="mail_from"
                                value="{$_c['mail_from']}" placeholder="noreply@host.tld">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Mail Reply To</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="mail_reply_to" name="mail_reply_to"
                                value="{$_c['mail_reply_to']}" placeholder="support@host.tld">
                        </div>
                        <p class="help-block col-md-4">Customer will reply email to this address, empty if you want to
                            use From Address</p>
                    </div>
                </div>
                <div class="panel-heading">
                    <div class="btn-group pull-right">
                        <button class="btn btn-primary btn-xs" title="save" type="submit"><span
                                class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span></button>
                    </div>
                    {Lang::T('User Notification')}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Expired Notification')}</label>
                        <div class="col-md-6">
                            <select name="user_notification_expired" id="user_notification_expired"
                                class="form-control">
                                <option value="none">None</option>
                                <option value="wa" {if $_c['user_notification_expired']=='wa' }selected="selected"
                                    {/if}>Whatsapp</option>
                                <option value="sms" {if $_c['user_notification_expired']=='sms' }selected="selected"
                                    {/if}>SMS</option>
                                <option value="email" {if $_c['user_notification_expired']=='email' }selected="selected"
                                    {/if}>Email</option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">{Lang::T('User will get notification when package expired')}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Payment Notification')}</label>
                        <div class="col-md-6">
                            <select name="user_notification_payment" id="user_notification_payment"
                                class="form-control">
                                <option value="none">None</option>
                                <option value="wa" {if $_c['user_notification_payment']=='wa' }selected="selected"
                                    {/if}>Whatsapp</option>
                                <option value="sms" {if $_c['user_notification_payment']=='sms' }selected="selected"
                                    {/if}>SMS</option>
                                <option value="email" {if $_c['user_notification_payment']=='email' }selected="selected"
                                    {/if}>Email</option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">
                            {Lang::T('User will get invoice notification when buy package or package refilled')}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Reminder Notification')}</label>
                        <div class="col-md-6">
                            <select name="user_notification_reminder" id="user_notification_reminder"
                                class="form-control">
                                <option value="none">None</option>
                                <option value="wa" {if $_c['user_notification_reminder']=='wa' }selected="selected"
                                    {/if}>Whatsapp</option>
                                <option value="sms" {if $_c['user_notification_reminder']=='sms' }selected="selected"
                                    {/if}>SMS</option>
                                <option value="sms" {if $_c['user_notification_reminder']=='email' }selected="selected"
                                    {/if}>Email</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="panel-heading">
                    <div class="btn-group pull-right">
                        <button class="btn btn-primary btn-xs" title="save" type="submit"><span
                                class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span></button>
                    </div>
                    {Lang::T('Tawk.to Chat Widget')}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">https://tawk.to/chat/</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="tawkto" name="tawkto" value="{$_c['tawkto']}"
                                placeholder="62f1ca7037898912e961f5/1ga07df">
                        </div>
                        <p class="help-block col-md-4">From Direct Chat Link.</p>
                    </div>
                    <label class="col-md-2"></label>
                    <p class="col-md-6 help-block">/ip hotspot walled-garden<br>
                        add dst-host=tawk.to<br>
                        add dst-host=*.tawk.to</p>
                </div>
                <div class="panel-heading">
                    <div class="btn-group pull-right">
                        <button class="btn btn-primary btn-xs" title="save" type="submit"><span
                                class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span></button>
                    </div>
                    API Key
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">Access Token</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" id="api_key" name="api_key"
                                value="{$_c['api_key']}" placeholder="Empty this to randomly created API key"
                                onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'">
                        </div>
                        <p class="col-md-4 help-block">{Lang::T('This Token will act as SuperAdmin/Admin')}</p>
                    </div>
                </div>
                <div class="panel-heading">
                    <div class="btn-group pull-right">
                        <button class="btn btn-primary btn-xs" title="save" type="submit"><span
                                class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span></button>
                    </div>
                    {Lang::T('Proxy')}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Proxy Server')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="http_proxy" name="http_proxy"
                                value="{$_c['http_proxy']}" placeholder="127.0.0.1:3128">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Proxy Server Login')}</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" id="http_proxyauth" name="http_proxyauth"
                                autocomplete="off" value="{$_c['http_proxyauth']}" placeholder="username:password"
                                onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'">
                        </div>
                    </div>
                </div>
                <div class="panel-heading">
                    <div class="btn-group pull-right">
                        <button class="btn btn-primary btn-xs" title="save" type="submit"><span
                                class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span></button>
                    </div>
                    {Lang::T('Miscellaneous')}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Enable Session Timeout')}</label>
                        <div class="col-md-6">
                            <label class="switch">
                                <input type="checkbox" id="enable_session_timeout" value="1" name="enable_session_timeout" {if $_c['enable_session_timeout']==1}checked{/if}>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Logout Admin if not Available/Online a period of time')}</p>
                    </div>
                    <div class="form-group" id="timeout_duration_input" style="display: none;">
                        <label class="col-md-2 control-label">{Lang::T('Timeout Duration')}</label>
                        <div class="col-md-6">
                            <input type="number" value="{$_c['session_timeout_duration']}" class="form-control" name="session_timeout_duration" id="session_timeout_duration"
                                   placeholder="{Lang::T('Enter the session timeout duration (minutes)')}" min="1">
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Idle Timeout, Logout Admin if Idle for xx minutes')}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('New Version Notification')}</label>
                        <div class="col-md-6">
                            <select name="new_version_notify" id="new_version_notify" class="form-control">
                                <option value="enable" {if $_c['new_version_notify']=='enable' }selected="selected"
                                    {/if}>{Lang::T('Enabled')}
                                </option>
                                <option value="disable" {if $_c['new_version_notify']=='disable' }selected="selected"
                                    {/if}>{Lang::T('Disabled')}
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">{Lang::T('This is to notify you when new updates is available')}
                        </p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('OTP Required')}</label>
                        <div class="col-md-6">
                            <select name="allow_phone_otp" id="allow_phone_otp" class="form-control">
                                <option value="no" {if $_c['allow_phone_otp']=='no' }selected="selected" {/if}>
                                    No</option>
                                <option value="yes" {if $_c['allow_phone_otp']=='yes' }selected="selected" {/if}>Yes
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">{Lang::T('OTP is required when user want to change phone
                            number')}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('OTP Method')}</label>
                        <div class="col-md-6">
                            <select name="phone_otp_type" id="phone_otp_type" class="form-control">
                                <option value="sms" {if $_c['phone_otp_type']=='sms' }selected="selected" {/if}>
                                    {Lang::T('SMS')}
                                <option value="whatsapp" {if $_c['phone_otp_type']=='whatsapp' }selected="selected"
                                    {/if}> {Lang::T('WhatsApp')}
                                <option value="both" {if $_c['phone_otp_type']=='both' }selected="selected" {/if}>
                                    {Lang::T('SMS and WhatsApp')}
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">{Lang::T('The method which OTP will be sent to user')}</p>
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
                            {Lang::T('Hotspot Authentication Method. Make sure you have changed your hotspot login page.')}<br><a
                                href="https://github.com/agstrxyz/phpnuxbill-login-hotspot" target="_blank">Download
                                phpnuxbill-login-hotspot</a>
                        </p>
                    </div>
                </div>

                <div class="panel-heading">
                    <div class="btn-group pull-right">
                        <button class="btn btn-primary btn-xs" title="save" type="submit">
                            <span class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span>
                        </button>
                    </div>
                    {Lang::T('Tax System')}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Enable Tax System')}</label>
                        <div class="col-md-6">
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
                        <label class="col-md-2 control-label">{Lang::T('Tax Rate')}</label>
                        <div class="col-md-6">
                            <select name="tax_rate" id="tax_rate" class="form-control">
                                <option value="0.5" {if $_c['tax_rate']=='0.5' }selected="selected" {/if}>
                                    0.5%
                                </option>
                                <option value="1" {if $_c['tax_rate']=='1' }selected="selected" {/if}>
                                    1%
                                </option>
                                <option value="1.5" {if $_c['tax_rate']=='1.5' }selected="selected" {/if}>
                                    1.5%
                                </option>
                                <option value="2" {if $_c['tax_rate']=='2' }selected="selected" {/if}>
                                    2%
                                </option>
                                <option value="5" {if $_c['tax_rate']=='5' }selected="selected" {/if}>
                                    5%
                                </option>
                                <option value="10" {if $_c['tax_rate']=='10' }selected="selected" {/if}>
                                    10%
                                </option>
                                <!-- Custom tax rate option -->
                                <option value="custom" {if $_c['tax_rate']=='custom' }selected="selected" {/if}>
                                    {Lang::T('Custom')}</option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Tax Rates in percentage')}</p>
                    </div>
                    <!-- Custom tax rate input field (initially hidden) -->
                    <div class="form-group" id="customTaxRate" style="display: none;">
                        <label class="col-md-2 control-label">{Lang::T('Custom Tax Rate')}</label>
                        <div class="col-md-6">
                            <input type="text" value="{$_c['custom_tax_rate']}" class="form-control"
                                name="custom_tax_rate" id="custom_tax_rate"
                                placeholder="{Lang::T('Enter Custom Tax Rate')}">
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Enter the custom tax rate (e.g., 3.75 for 3.75%)')}</p>
                    </div>
                </div>
                <div class="panel-heading" id="Github_Authentication">
                    <div class="btn-group pull-right">
                        <button class="btn btn-primary btn-xs" title="save" type="submit"><span
                                class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span></button>
                    </div>
                    Github Authentication
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">Github Username</label>
                        <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-addon">https://github.com/</span>
                            <input type="text" class="form-control" id="github_username" name="github_username"
                                value="{$_c['github_username']}" placeholder="ibnux">
                        </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Github Token</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" id="github_token" name="github_token"
                                value="{$_c['github_token']}" placeholder="ghp_........"
                                onmouseleave="this.type = 'password'" onmouseenter="this.type = 'text'">
                        </div>
                        <span class="help-block col-md-4"><a href="https://github.com/settings/tokens/new"
                                target="_blank">Create GitHub personal access token (classic)</a>, only need repo scope</span>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-md-offset-2 col-md-8" style="text-align: left;">This will allow
                            you to download
                            plugin from private/paid repository</label>
                    </div>
                </div>
            </div>

            <div class="panel-body">
                <div class="form-group">
                    <button class="btn btn-success btn-block" type="submit">{Lang::T('Save
                        Changes')}</button>
                </div>
            </div>

            <pre>/ip hotspot walled-garden
add dst-host={$_domain}
add dst-host=*.{$_domain}</pre>

            <pre>
# Expired Cronjob Every 5 Minutes
*/5 * * * * cd {$dir} && {$php} cron.php

# Expired Cronjob Every 1 Hour
0 * * * * cd {$dir} && {$php} cron.php
</pre>
            <pre>
# Reminder Cronjob Every 7 AM
0 7 * * * cd {$dir} && {$php} cron_reminder.php
</pre>
        </div>
    </div>
</form>


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
            if (sectionTimeoutCheckbox.checked && (!timeoutDurationField.value || isNaN(timeoutDurationField.value))) {
                event.preventDefault();
                alert('Please enter a valid session timeout duration.');
                timeoutDurationField.focus();
            }
        });
    });
</script>
<script>
    function testWa() {
        var target = prompt("Phone number\nSave First before Test", "");
        if (target != null) {
            window.location.href = '{$_url}settings/app&testWa=' + target;
        }
    }

    function testSms() {
        var target = prompt("Phone number\nSave First before Test", "");
        if (target != null) {
            window.location.href = '{$_url}settings/app&testSms=' + target;
        }
    }


    function testEmail() {
        var target = prompt("Email\nSave First before Test", "");
        if (target != null) {
            window.location.href = '{$_url}settings/app&testEmail=' + target;
        }
    }

    function testTg() {
        window.location.href = '{$_url}settings/app&testTg=test';
    }
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
{include file="sections/footer.tpl"}