{include file="sections/user-header.tpl"}
<div class="row">
    <div class="col-sm-12">
        <div class="panel mb20 panel-primary panel-hovered">
            <div class="panel-heading">Order PPOE</div>
        </div>
        {foreach $routers as $router}
            <div class="panel mb20 panel-info panel-hovered">
                <div class="panel-heading">{$router['name']}</div>
                <div class="panel-body">
                    {$router['description']}
                </div>
            </div>
            <div class="row">
                <div class="col-sm-3">
                    <div class="panel mb10 panel-default panel-hovered">
                        <div class="panel-heading">Router Name</div>
                        <div class="panel-body">
                            Router Description
                        </div>
                        <div class="panel-footer">
                            <a href="" class="btn btn-sm btn-block btn-primary">Buy</a>
                        </div>
                    </div>
                </div>
            </div>
        {/foreach}
    </div>
</div>
{include file="sections/user-footer.tpl"}
