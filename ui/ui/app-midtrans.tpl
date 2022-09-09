{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" role="form" action="{$_url}paymentgateway/midtrans-post" >
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-default panel-hovered panel-stacked mb30">
                <div class="panel-heading">MIDTRANS</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">ID Merchant</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="midtrans_merchant_id" name="midtrans_merchant_id" placeholder="G" value="{$_c['midtrans_merchant_id']}">
                            <a href="https://dashboard.midtrans.com/settings/config_info" target="_blank" class="help-block">https://dashboard.midtrans.com/settings/config_info</a>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Client Key</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="midtrans_client_key" name="midtrans_client_key" placeholder="Mid-client-XXXXXXXX" value="{$_c['midtrans_client_key']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Server Key</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="midtrans_server_key" name="midtrans_server_key" placeholder="Mid-server-XXXXXXXX" value="{$_c['midtrans_server_key']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Payment Notification URL</label>
                        <div class="col-md-6">
                            <input type="text" readonly class="form-control" onclick="this.select()" value="{$_url}callback/midtrans">
                            <p class="help-block">{Lang::T('Payment Notification URL, Recurring Notification URL, Pay Account Notification URL')}</p>
                            <p class="help-block">Midtrans wajib pake URL Notification</p>
                            <a href="https://dashboard.midtrans.com/settings/vtweb_configuration" target="_blank" class="help-block">https://dashboard.midtrans.com/settings/vtweb_configuration</a>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Finish Redirect URL</label>
                        <div class="col-md-6">
                            <input type="text" readonly class="form-control" onclick="this.select()" value="{$_url}accounts/transaction">
                            <p class="help-block">{Lang::T('Finish Redirect URL, Unfinish Redirect URL, Error Redirect URL')}</p>
                            <a href="https://dashboard.midtrans.com/settings/vtweb_configuration" target="_blank" class="help-block">https://dashboard.midtrans.com/settings/vtweb_configuration</a>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Channels</label>
                        <div class="col-md-6">
                            {foreach $channels as $channel}
                                <label class="checkbox-inline"><input type="checkbox" {if strpos($_c['midtrans_channel'], $channel['id']) !== false}checked="true"{/if} id="midtrans_channel" name="midtrans_channel[]" value="{$channel['id']}"> {$channel['name']}</label>
                            {/foreach}
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-primary waves-effect waves-light" type="submit">{$_L['Save']}</button>
                        </div>
                    </div>
                        <pre>/ip hotspot walled-garden
add dst-host=midtrans.com
add dst-host=*.midtrans.com</pre>
                </div>
            </div>

        </div>
    </div>
</form>
{include file="sections/footer.tpl"}
