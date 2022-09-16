{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" role="form" action="{$_url}settings/app-post" >
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-default panel-hovered panel-stacked mb30">
                <div class="panel-heading">{$_L['General_Settings']}</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['App_Name']}</label>
                        <div class="col-md-6">
                            <input type="text" required class="form-control" id="company" name="company" value="{$_c['CompanyName']}">
                            <span class="help-block">{$_L['App_Name_Help_Text']}</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">{$_L['Address']}</label>
                        <div class="col-md-6">
                            <textarea class="form-control" id="address" name="address" rows="3">{$_c['address']}</textarea>
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
                            <p class="help-block">Changing from Radius will not add existing user to Mikrotik Hotspot.</p>
                            <p class="help-block">With Radius user can use Hotspot or PPOE.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">APP URL</label>
                        <div class="col-md-6">
                            <input type="text" readonly class="form-control" value="{$app_url}">
                            <p class="help-block">system/config.php</p>
                        </div>
                    </div>
                </div>
                <div class="panel-heading">Telegram Notification</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">Telegram Bot Token</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="telegram_bot" name="telegram_bot" value="{$_c['telegram_bot']}" placeholder="123456:asdasgdkuasghddlashdashldhalskdklasd">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Telegram Target ID</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="telegram_target_id" name="telegram_target_id" value="{$_c['telegram_target_id']}" placeholder="12345678">
                        </div>
                    </div>
                </div>
                <div class="panel-heading">SMS OTP Registration</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">SMS Server URL</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="sms_url" name="sms_url" value="{$_c['sms_url']}" placeholder="https://domain/?param_number=[number]&param_text=[text]&secret=">
                            <p class="help-block">Must include <b>[text]</b> &amp; <b>[number]</b>, it will be replaced.</p>
                        </div>
                    </div>
                </div>
                <div class="panel-heading">Whatsapp Notification</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">Whatsapp Server URL</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="wa_url" name="wa_url" value="{$_c['wa_url']}" placeholder="https://domain/?param_number=[number]&param_text=[text]&secret=">
                            <p class="help-block">Must include <b>[text]</b> &amp; <b>[number]</b>, it will be replaced.</p>
                        </div>
                    </div>
                </div>
                <div class="panel-heading">Tawk.to Chat Widget</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">https://tawk.to/chat/</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="tawkto" name="tawkto" value="{$_c['tawkto']}" placeholder="62f1ca7037898912e961f5/1ga07df">
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
                            <textarea class="form-control" id="note" name="note" rows="3">{$_c['note']}</textarea>
                            <span class="help-block">{$_L['You_can_use_html_tag']}</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-primary waves-effect waves-light" type="submit">{$_L['Save']}</button>
                        </div>
                    </div>
                </div>
                            <pre>/ip hotspot walled-garden
add dst-host={$_domain}
add dst-host=*.{$_domain}</pre>
            </div>
        </div>
    </div>
</form>
{include file="sections/footer.tpl"}
