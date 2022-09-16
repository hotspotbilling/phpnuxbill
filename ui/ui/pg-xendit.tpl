{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" role="form" action="{$_url}paymentgateway/xendit" >
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-default panel-hovered panel-stacked mb30">
                <div class="panel-heading">XENDIT</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">Secret Key</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="xendit_secret_key" name="xendit_secret_key" placeholder="xnd_" value="{$_c['xendit_secret_key']}">
                            <a href="https://dashboard.xendit.co/settings/developers#api-keys" target="_blank" class="help-block">https://dashboard.xendit.co/settings/developers#api-keys</a>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Verification Token</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="xendit_verification_token" name="xendit_verification_token" placeholder="cece1878a4a24754fb193309d3977f4dc0e86e907c4fb188cbccd10d8ef67fd3" value="{$_c['xendit_verification_token']}">
                            <a href="https://dashboard.xendit.co/settings/developers#callbacks" target="_blank" class="help-block">https://dashboard.xendit.co/settings/developers#callbacks</a>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Callback URL</label>
                        <div class="col-md-6">
                            <input type="text" readonly class="form-control" onclick="this.select()" value="{$_url}callback/xendit">
                            <a href="https://dashboard.xendit.co/settings/developers#callbacks" target="_blank" class="help-block">https://dashboard.xendit.co/settings/developers#callbacks</a>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Channels</label>
                        <div class="col-md-6">
                            {foreach $channels as $channel}
                                <label class="checkbox-inline"><input type="checkbox" {if strpos($_c['xendit_channel'], $channel['id']) !== false}checked="true"{/if} id="xendit_channel" name="xendit_channel[]" value="{$channel['id']}"> {$channel['name']}</label>
                            {/foreach}
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-primary waves-effect waves-light" type="submit">{$_L['Save']}</button>
                        </div>
                    </div>
                        <pre>/ip hotspot walled-garden
add dst-host=xendit.co
add dst-host=*.xendit.co</pre>
                </div>
            </div>

        </div>
    </div>
</form>
{include file="sections/footer.tpl"}
