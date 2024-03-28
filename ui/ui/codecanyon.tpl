{include file="sections/header.tpl"}
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-primary panel-hovered">
            <div class="panel-heading">
                <div class="btn-group pull-right">
                    <a class="btn btn-danger btn-xs" href="https://codecanyon.net/category/php-scripts?term=phpnuxbill"
                        target="_blank">Buy Plugin</a>
                </div>
                Plugin Purcashed
            </div>
            <div class="panel-body row">
                {if Lang::arrayCount($plugins) > 0}
                    {foreach $plugins as $plugin}
                        <div class="col-md-4">
                            <div class="box box-hovered mb20 box-primary">
                                <div class="box-header">
                                    <h3 class="box-title text1line">{$plugin['item']['name']}</h3>
                                </div>
                                <div class="box-body"><small><i>@{$plugin['item']['author_username']} &bull; Last update:
                                            {Lang::dateFormat($plugin['item']['updated_at'])}</i></small></div>
                                <div class="box-footer ">
                                    <div class="btn-group btn-group-justified" role="group" aria-label="...">
                                        <a href="{$plugin['item']['author_url']}" target="_blank" class="btn btn-primary"><i
                                                class="ion ion-chatboxes"></i> Author</a>
                                        <a href="{$plugin['item']['url']}" target="_blank" class="btn btn-success"><i
                                                class="ion ion-chatboxes"></i> Product</a>
                                        <a {if $zipExt } href="{$_url}codecanyon/install/{$plugin['item']['id']}"
                                                onclick="return confirm('Installing plugin will take some time to complete, do not close the page while it loading to install the plugin')"
                                            {else} href="#" onclick="alert('PHP ZIP extension is not installed')" 
                                            {/if}
                                            class="btn btn-danger"><i class="ion ion-chatboxes"></i> Install</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    {/foreach}
                {else}
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> No plugins purcashed yet.
                        </div>
                    </div>
                {/if}
            </div>
            <div class="panel-footer">
                {if $chached_until}Cached Until {$chached_until} <a href="{$_url}codecanyon/reload">Force reload</a>
                &bull; {/if}<a
                    href="https://github.com/hotspotbilling/phpnuxbill/wiki/Selling-Paid-Plugin-or-Payment-Gateway"
                    target="_blank"> Sell your own plugin/paymentgateway/theme?</a>
            </div>
        </div>
    </div>
{include file="sections/footer.tpl"}