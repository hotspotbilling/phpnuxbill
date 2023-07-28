{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" role="form" action="{$_url}settings/app-post">
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">{$_L['General_Settings']}</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['App_Name']}</label>
                        <div class="col-md-6">
                            <input type="text" required class="form-control" id="company" name="company"
                                value="{$_c['CompanyName']}">
                            <span class="help-block">{$_L['App_Name_Help_Text']}</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Company Footer')}</label>
                        <div class="col-md-6">
                            <input type="text" required class="form-control" id="footer" name="footer"
                                value="{$_c['CompanyFooter']}">
                                <span class="help-block">{Lang::T('Will show below user pages')}</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Address']}</label>
                        <div class="col-md-6">
                            <textarea class="form-control" id="address" name="address"
                                rows="3">{Lang::htmlspecialchars($_c['address'])}</textarea>
                            <span class="help-block">{$_L['You_can_use_html_tag']}</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Phone_Number']}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="phone" name="phone" value="{$_c['phone']}">
                        </div>
                    </div>
                    <div class="form-group hidden">
                        <label class="col-md-2 control-label">Radius Mode?</label>
                        <div class="col-md-6">
                            <select name="radius_mode" id="radius_mode" class="form-control">
                                <option value="0">No</option>
                                <option value="1" {if $_c['radius_mode']}selected="selected" {/if}>Yes</option>
                            </select>
                            <p class="help-block">Still on Testing.</p>
                            <p class="help-block">Changing from Radius will not add existing user to Mikrotik Hotspot.
                            </p>
                            <p class="help-block">With Radius user can use Hotspot or PPOE.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">APP URL</label>
                        <div class="col-md-6">
                            <input type="text" readonly class="form-control" value="{$app_url}">
                            <p class="help-block">edit at config.php</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Disable Voucher')}</label>
                        <div class="col-md-6">
                            <select name="disable_voucher" id="disable_voucher" class="form-control">
                                <option value="no" {if $_c['disable_voucher'] == 'no'}selected="selected" {/if}>No</option>
                                <option value="yes" {if $_c['disable_voucher'] == 'yes'}selected="selected" {/if}>Yes</option>
                            </select>
                            <p class="help-block">Voucher activation menu will be hidden</p>
                        </div>
                    </div>
                </div>
                <div class="panel-heading">Telegram Notification</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">Telegram Bot Token</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="telegram_bot" name="telegram_bot"
                                value="{$_c['telegram_bot']}" placeholder="123456:asdasgdkuasghddlashdashldhalskdklasd">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Telegram Target ID</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="telegram_target_id" name="telegram_target_id"
                                value="{$_c['telegram_target_id']}" placeholder="12345678">
                        </div>
                    </div>
                    <small id="emailHelp" class="form-text text-muted">You will get Payment and Error notification</small>
                </div>
                <div class="panel-heading">SMS OTP Registration</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">SMS Server URL</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="sms_url" name="sms_url" value="{$_c['sms_url']}"
                                placeholder="https://domain/?param_number=[number]&param_text=[text]&secret=">
                            <p class="help-block">Must include <b>[text]</b> &amp; <b>[number]</b>, it will be replaced.
                            </p>
                        </div>
                    </div>
                    <small id="emailHelp" class="form-text text-muted">You can use WhatsApp in here too. <a href="https://wa.nux.my.id/login" target="_blank">Free Server</a></small>
                </div>
                <div class="panel-heading">Whatsapp Notification</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">Whatsapp Server URL</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="wa_url" name="wa_url" value="{$_c['wa_url']}"
                                placeholder="https://domain/?param_number=[number]&param_text=[text]&secret=">
                            <p class="help-block">Must include <b>[text]</b> &amp; <b>[number]</b>, it will be replaced.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="panel-heading">{Lang::T('User Notification')}</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Expired Notification')}</label>
                        <div class="col-md-6">
                            <select name="user_notification_expired" id="user_notification_expired" class="form-control">
                                <option value="none">None</option>
                                <option value="wa" {if $_c['user_notification_expired'] == 'wa'}selected="selected" {/if}>Whatsapp</option>
                                <option value="sms" {if $_c['user_notification_expired'] == 'sms'}selected="selected" {/if}>SMS</option>
                            </select>
                            <p class="help-block">{Lang::T('User will get notification when package expired')}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Expired Notification Message')}</label>
                        <div class="col-md-6">
                                <textarea class="form-control" id="user_notification_expired_text" name="user_notification_expired_text" placeholder="Hello [[name]], your internet package [[package]] has been expired" rows="3">{if $_c['user_notification_expired_text']!=''}{Lang::htmlspecialchars($_c['user_notification_expired_text'])}{else}Hello [[name]], your internet package [[package]] has been expired.{/if}</textarea>
                            <p class="help-block">{Lang::T('<b>[[name]]</b> will be replaced with Customer Name. <b>[[package]]</b> will be replaced with Package name.')}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Payment Notification')}</label>
                        <div class="col-md-6">
                            <select name="user_notification_payment" id="user_notification_payment" class="form-control">
                                <option value="none">None</option>
                                <option value="wa" {if $_c['user_notification_payment'] == 'wa'}selected="selected" {/if}>Whatsapp</option>
                                <option value="sms" {if $_c['user_notification_payment'] == 'sms'}selected="selected" {/if}>SMS</option>
                            </select>
                            <p class="help-block">{Lang::T('User will get invoice notification when buy package or package refilled')}</p>
                        </div>
                    </div>
                </div>
                <div class="panel-heading">Tawk.to Chat Widget</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">https://tawk.to/chat/</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="tawkto" name="tawkto" value="{$_c['tawkto']}"
                                placeholder="62f1ca7037898912e961f5/1ga07df">
                            <p class="help-block">From Direct Chat Link.</p>
                            <pre>/ip hotspot walled-garden
add dst-host=tawk.to
add dst-host=*.tawk.to</pre>
                        </div>
                    </div>
                </div>
                <div class="panel-heading">Invoice</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">Note Invoice</label>
                        <div class="col-md-6">
                            <textarea class="form-control" id="note" name="note" rows="3">{Lang::htmlspecialchars($_c['note'])}</textarea>
                            <span class="help-block">{$_L['You_can_use_html_tag']}</span>
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
        </div>
    </div>
</form>
{include file="sections/footer.tpl"}