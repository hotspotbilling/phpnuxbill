{include file="sections/header.tpl"}

<div class="panel panel-primary">
    <div class="panel-heading">
        Installed Devices
    </div>
    <div class="panel-body">
        <div class="row">
            {foreach $devices  as $d}
                <div class="col-md-3">
                    <div class="panel panel-primary">
                        <div class="panel-heading"><b>{$d['title']}</b> <small>by {$d['author']}</small></div>
                        <div class="panel-body" style="overflow-y: scroll;">
                            <div style="max-height: 50px; min-height: 50px;">{nl2br(strip_tags($d['description']))}</div>
                        </div>
                        <div class="panel-footer" style="overflow-y: scroll;">
                            <center style="max-height: 40px; min-height: 40px;">
                                <span class="label label-default">{$d['file']}</span>
                                {foreach $d['url']  as $name => $url}
                                    <a href="{$url}" target="_blank" class="label label-primary">{$name}</a>
                                {/foreach}
                            </center>
                        </div>
                    </div>
                </div>
            {/foreach}
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}