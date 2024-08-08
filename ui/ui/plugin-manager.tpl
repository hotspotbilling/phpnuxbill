{include file="sections/header.tpl"}

{if empty($_c['github_token'])}
    <p class="help-block">To download from private/paid repository, <a href="{$_url}settings/app#Github_Authentication">Set
            your Github Authentication first</a></p>
{/if}

<form method="post" enctype="multipart/form-data"
    onsubmit="return confirm('Warning, installing unknown source can damage your server, continue?')"
    action="{$_url}pluginmanager/dlinstall">
    <div class="panel panel-primary panel-hovered">
        <div class="panel-heading">
            {Lang::T('Plugin Installer')}
            <div class="btn-group pull-right">
                <a class="btn btn-warning btn-xs" title="info"
                    href="https://github.com/hotspotbilling/phpnuxbill/wiki/Installing-Plugin-or-Payment-Gateway"
                    target="_blank"><span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span> info</a>
                <a class="btn btn-success btn-xs" title="refresh cache" href="{$_url}pluginmanager/refresh"><span
                        class="glyphicon glyphicon-refresh" aria-hidden="true"></span> refresh</a>
            </div>
        </div>
        <div class="panel-body row">
            <div class="form-group col-md-4">
                <label>Upload Zip Plugin/Theme/Device</label>
                <input type="file" name="zip_plugin" accept="application/zip" onchange="this.submit()">
            </div>
            <div class="form-group col-md-7">
                <label>Github url</label>
                <input type="url" class="form-control" name="gh_url"
                    placeholder="https://github.com/username/repository">
            </div>
            <div class="col-md-1">
                <br>
                <button type="submit" class="btn btn-primary">Install</button>
            </div>
        </div>
    </div>
</form>

<div>
    <!-- Nav tabs -->
    <ul class="nav nav-tabs" role="tablist">
        <li role="presentation" class="active"><a href="#plugin" aria-controls="plugin" role="tab"
                data-toggle="tab">Plugin</a></li>
        <li role="presentation"><a href="#pg" aria-controls="pg" role="tab" data-toggle="tab">Payment Gateway</a>
        </li>
        <li role="presentation"><a href="#device" aria-controls="device" role="tab" data-toggle="tab">Devices</a>
        </li>
    </ul>
    <br>
    <!-- Tab panes -->
    <div class="tab-content">
        <div role="tabpanel" class="tab-pane active" id="plugin">
            <div class="row">
                {foreach $plugins as $plugin}
                    <div class="col-md-4">
                        <div class="box box-hovered mb20 box-primary">
                            <div class="box-header">
                                <h3 class="box-title text1line">{$plugin['name']}</h3>
                            </div>
                            <div class="box-body" style="overflow-y: scroll;">
                                <div style="max-height: 50px; min-height: 50px;">{$plugin['description']}</div>
                            </div>
                            <div class="box-footer ">
                                <center><small><i>@{$plugin['author']} Last update: {$plugin['last_update']}</i></small>
                                </center>
                                <div class="btn-group btn-group-justified" role="group" aria-label="...">
                                    <a href="{$plugin['url']}" target="_blank" style="color: black;"
                                        class="btn btn-{if $plugin['ispaid']}warning{else}primary{/if}"><i
                                            class="glyphicon glyphicon-globe"></i>
                                        {if $plugin['ispaid']}Buy{else}Web{/if}</a>
                                    <a href="{$plugin['github']}" target="_blank" style="color: black;"
                                        class="btn btn-info"><i class="glyphicon glyphicon-align-left"></i> Source</a>
                                </div>
                                <div class="btn-group btn-group-justified" role="group" aria-label="...">
                                    <a href="{$_url}pluginmanager/delete/plugin/{$plugin['id']}"
                                        onclick="return confirm('{Lang::T('Delete')}?')" class="btn btn-danger"><i
                                            class="glyphicon glyphicon-trash"></i> Delete</a>
                                    <a {if $zipExt } href="{$_url}pluginmanager/install/plugin/{$plugin['id']}"
                                            onclick="return confirm('Installing plugin will take some time to complete, do not close the page while it loading to install the plugin')"
                                        {else} href="#" onclick="alert('PHP ZIP extension is not installed')"
                                        {/if}
                                        style="color: black;" class="btn btn-success"><i
                                            class="glyphicon glyphicon-circle-arrow-down"></i> Install</a>
                                </div>
                            </div>
                        </div>
                    </div>
                {/foreach}
            </div>
        </div>
        <div role="tabpanel" class="tab-pane" id="pg">
            <div class="row">
                {foreach $pgs as $pg}
                    <div class="col-md-4">
                        <div class="box box-hovered mb20 box-primary">
                            <div class="box-header">
                                <h3 class="box-title text1line">{$pg['name']}</h3>
                            </div>
                            <div class="box-body" style="overflow-y: scroll;">
                                <div style="max-height: 50px; min-height: 50px;">{$pg['description']}</div>
                            </div>
                            <div class="box-footer ">
                                <center><small><i>@{$pg['author']} Last update: {$pg['last_update']}</i></small>
                                </center>
                                <div class="btn-group btn-group-justified" role="group" aria-label="...">
                                    <a href="{$pg['url']}" target="_blank" style="color: black;"
                                        class="btn btn-{if $pg['ispaid']}warning{else}primary{/if}"><i
                                            class="glyphicon glyphicon-globe"></i>
                                        {if $pg['ispaid']}Buy{else}Web{/if}
                                    </a>
                                    <a href="{$pg['github']}" target="_blank" style="color: black;" class="btn btn-info"><i
                                            class="glyphicon glyphicon-align-left"></i> Source</a>
                                    <a {if $zipExt } href="{$_url}pluginmanager/install/payment/{$pg['id']}"
                                            onclick="return confirm('Installing plugin will take some time to complete, do not close the page while it loading to install the plugin')"
                                        {else} href="#" onclick="alert('PHP ZIP extension is not available')"
                                        {/if}
                                        style="color: black;" class="btn btn-success"><i
                                            class="glyphicon glyphicon-circle-arrow-down"></i> Install</a>
                                </div>
                            </div>
                        </div>
                    </div>
                {/foreach}
            </div>
        </div>
        <div role="tabpanel" class="tab-pane" id="device">
            <div class="row">
                {foreach $dvcs as $dvc}
                    <div class="col-md-4">
                        <div class="box box-hovered mb20 box-primary">
                            <div class="box-header">
                                <h3 class="box-title text1line">{$dvc['name']}</h3>
                            </div>
                            <div class="box-body" style="overflow-y: scroll;">
                                <div style="max-height: 50px; min-height: 50px;">{$dvc['description']}</div>
                            </div>
                            <div class="box-footer ">
                                <center><small><i>@{$dvc['author']} Last update: {$dvc['last_update']}</i></small>
                                </center>
                                <div class="btn-group btn-group-justified" role="group" aria-label="...">
                                    <a href="{$dvc['url']}" target="_blank" style="color: black;"
                                        class="btn btn-{if $dvc['ispaid']}warning{else}primary{/if}"><i
                                            class="glyphicon glyphicon-globe"></i>
                                        {if $dvc['ispaid']}Buy{else}Web{/if}
                                    </a>
                                    <a href="{$dvc['github']}" target="_blank" style="color: black;" class="btn btn-info"><i
                                            class="glyphicon glyphicon-align-left"></i> Source</a>
                                    <a {if $zipExt } href="{$_url}pluginmanager/install/device/{$dvc['id']}"
                                            onclick="return confirm('Installing plugin will take some time to complete, do not close the page while it loading to install the plugin')"
                                        {else} href="#" onclick="alert('PHP ZIP extension is not available')"
                                        {/if}
                                        style="color: black;" class="btn btn-success"><i
                                            class="glyphicon glyphicon-circle-arrow-down"></i> Install</a>
                                </div>
                            </div>
                        </div>
                    </div>
                {/foreach}
            </div>
        </div>
    </div>

</div>
{include file="sections/footer.tpl"}