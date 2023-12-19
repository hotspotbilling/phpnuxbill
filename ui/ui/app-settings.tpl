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
                    {$_L['General_Settings']}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['App_Name']}</label>
                        <div class="col-md-6">
                            <input type="text" required class="form-control" id="CompanyName" name="CompanyName"
                                value="{$_c['CompanyName']}">
                        </div>
                        <span class="help-block col-md-4">{$_L['App_Name_Help_Text']}</span>
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
                        <label class="col-md-2 control-label">{$_L['Address']}</label>
                        <div class="col-md-6">
                            <textarea class="form-control" id="address" name="address"
                                rows="3">{Lang::htmlspecialchars($_c['address'])}</textarea>
                        </div>
                        <span class="help-block col-md-4">{$_L['You_can_use_html_tag']}</span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Phone_Number']}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="phone" name="phone" value="{$_c['phone']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Theme</label>
                        <div class="col-md-6">
                            <select name="theme" id="theme" class="form-control">
                                <option value="default" {if $_c['theme'] eq 'default'}selected="selected" {/if}>Default
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
                        <label class="col-md-2 control-label">APP URL</label>
                        <div class="col-md-6">
                            <input type="text" readonly class="form-control" value="{$app_url}">
                        </div>
                        <p class="help-block col-md-4">edit at config.php</p>
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
                                <option value="no" {if $_c['disable_voucher'] == 'no'}selected="selected" {/if}>No
                                </option>
                                <option value="yes" {if $_c['disable_voucher'] == 'yes'}selected="selected" {/if}>Yes
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Voucher activation menu will be hidden')}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Voucher Format')}</label>
                        <div class="col-md-6">
                            <select name="voucher_format" id="voucher_format" class="form-control">
                                <option value="up" {if $_c['voucher_format'] == 'up'}selected="selected" {/if}>UPPERCASE
                                </option>
                                <option value="low" {if $_c['voucher_format'] == 'low'}selected="selected" {/if}>
                                    lowercase
                                </option>
                                <option value="rand" {if $_c['voucher_format'] == 'rand'}selected="selected" {/if}>
                                    RaNdoM
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
                                    <option value="no" {if $_c['disable_registration'] == 'no'}selected="selected" {/if}>No
                                    </option>
                                    <option value="yes" {if $_c['disable_registration'] == 'yes'}selected="selected" {/if}>
                                        Yes
                                    </option>
                                </select>
                            </div>
                            <p class="help-block col-md-4">
                                {Lang::T('Customer just Login with Phone number and Voucher Code, Voucher will be password')}
                            </p>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">Redirect after Activation</label>
                            <div class="col-md-6">
                                <input type="text" class="form-control" id="voucher_redirect" name="voucher_redirect"
                                    placeholder="https://192.168.88.1/status" value="{$voucher_redirect}">
                            </div>
                            <p class="help-block col-md-4">
                                {Lang::T('After Customer activate voucher or login, customer will be redirected to this url')}
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
                    <div class="form-group">
                        <label class="col-md-2 control-label">Radius Client</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="radius_client" value="{$_c['radius_client']}">
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
                                <option value="no" {if $_c['enable_balance'] == 'no'}selected="selected" {/if}>No
                                </option>
                                <option value="yes" {if $_c['enable_balance'] == 'yes'}selected="selected" {/if}>Yes
                                </option>
                            </select>
                        </div>
                        <p class="help-block col-md-4">{Lang::T('Customer can deposit money to buy voucher')}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Allow Transfer')}</label>
                        <div class="col-md-6">
                            <select name="allow_balance_transfer" id="allow_balance_transfer" class="form-control">
                                <option value="no" {if $_c['allow_balance_transfer'] == 'no'}selected="selected" {/if}>
                                    No</option>
                                <option value="yes" {if $_c['allow_balance_transfer'] == 'yes'}selected="selected"
                                    {/if}>Yes</option>
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
                                <option value="wa" {if $_c['user_notification_expired'] == 'wa'}selected="selected"
                                    {/if}>Whatsapp</option>
                                <option value="sms" {if $_c['user_notification_expired'] == 'sms'}selected="selected"
                                    {/if}>SMS</option>
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
                                <option value="wa" {if $_c['user_notification_payment'] == 'wa'}selected="selected"
                                    {/if}>Whatsapp</option>
                                <option value="sms" {if $_c['user_notification_payment'] == 'sms'}selected="selected"
                                    {/if}>SMS</option>
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
                                <option value="wa" {if $_c['user_notification_reminder'] == 'wa'}selected="selected"
                                    {/if}>Whatsapp</option>
                                <option value="sms" {if $_c['user_notification_reminder'] == 'sms'}selected="selected"
                                    {/if}>SMS</option>
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
                    {Lang::T('Invoice')}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Invoice Footer')}</label>
                        <div class="col-md-6">
                            <textarea class="form-control" id="note" name="note"
                                rows="3">{Lang::htmlspecialchars($_c['note'])}</textarea>
                            <span class="help-block">{$_L['You_can_use_html_tag']}</span>
                        </div>
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
            </div>

            <div class="panel-body">
                <div class="form-group">
                    <button class="btn btn-success btn-block waves-effect waves-light"
                        type="submit">{$_L['Save']}</button>
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
{include file="sections/footer.tpl"}