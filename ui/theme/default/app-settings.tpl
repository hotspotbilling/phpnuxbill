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
                        <label class="col-md-2 control-label">Theme</label>
                        <div class="col-md-6">
                            <select name="theme" id="theme" class="form-control">
                                <option value="default" {if $_c['theme'] eq 'default'}selected="selected" {/if}>Default</option>
                            </select>
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
                <div class="panel-heading">SMS/Whatsapp Notification</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">Server URL</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="sms_url" name="sms_url" value="{$_c['sms_url']}" placeholder="https://domain/?param_number=[number]&param_text=[text]&secret=">
                            <p class="help-block">Must include <b>[text]</b> &amp; <b>[number]</b>, it will be replaced.</p>
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
            </div>

        </div>
    </div>
</form>
{include file="sections/footer.tpl"}
