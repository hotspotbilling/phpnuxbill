{include file="sections/user-header.tpl"}
<div class="row">
    <div class="col-sm-12">
        <div class="panel mb20 panel-default panel-hovered">
            <div class="panel-heading">Order Hotspot</div>
        </div>
        {foreach $routers as $router}
            <div class="panel mb20 panel-info panel-hovered">
                <div class="panel-heading">{$router['name']}</div>
                {if $router['description'] != ''}
                    <div class="panel-body">
                        {$router['description']}
                    </div>
                {/if}
            </div>
            <div class="row">
                {foreach $plans as $plan}
                    {if $router['name'] eq $plan['routers']}
                        <div class="col-sm-3">
                            <div class="panel mb10 panel-default panel-hovered">
                                <div class="panel-heading"> {$plan['name_plan']}</div>
                                <div class="table-responsive">
									<table class="table table-bordered table-striped">
										<tbody>
											<tr>
												<td>Price</td>
												<td>{$plan['price']}</td>
											</tr>
											<tr>
												<td>Validity</td>
												<td>{$plan['validity']} {$plan['validity_unit']}</td>
											</tr>
										</tbody>
									</table>
                                </div>
                                <div class="panel-footer">
                                    <a href="{$_url}order/hotspot-buy/{$router['id']}/{$plan['id']}" class="btn btn-sm btn-block btn-primary">Buy</a>
                                </div>
                            </div>
                        </div>
                    {/if}
                {/foreach}
            </div>
        {/foreach}
    </div>
</div>
{include file="sections/user-footer.tpl"}
