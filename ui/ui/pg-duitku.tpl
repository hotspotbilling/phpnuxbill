{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" role="form" action="{$_url}paymentgateway/duitku-post" >
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-default panel-hovered panel-stacked mb30">
                <div class="panel-heading">DUITKU</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">Kode Merchant</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="duitku_merchant_id" name="duitku_merchant_id" placeholder="D" value="{$_c['duitku_merchant_id']}">
                            <a href="https://duitku.com/merchant/Project" target="_blank" class="help-block">https://duitku.com/merchant/Project</a>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Merchant/API Key</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="duitku_merchant_key" name="duitku_merchant_key" placeholder="xxxxxxxxxxxxxxxxx" value="{$_c['duitku_merchant_key']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Url Callback Proyek</label>
                        <div class="col-md-6">
                            <input type="text" readonly class="form-control" onclick="this.select()" value="{$_url}callback/duitku">
                            <a href="https://duitku.com/merchant/Project" target="_blank" class="help-block">https://duitku.com/merchant/Project</a>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Channels</label>
                        <div class="col-md-6">
                            {foreach $channels as $channel}
                                <label class="checkbox-inline"><input type="checkbox" {if strpos($_c['duitku_channel'], $channel['id']) !== false}checked="true"{/if} id="duitku_channel" name="duitku_channel[]" value="{$channel['id']}"> {$channel['name']}</label>
                            {/foreach}
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-primary waves-effect waves-light" type="submit">{$_L['Save']}</button>
                        </div>
                    </div>
                        <pre>/ip hotspot walled-garden
add dst-host=duitku.com
add dst-host=*.duitku.com</pre>
                </div>
            </div>

        </div>
    </div>
</form>
{include file="sections/footer.tpl"}
