{include file="sections/user-header.tpl"}
<!-- user-orderPlan -->
<div class="row">
    <div class="col-sm-12">
        <div class="box box-solid box-default">
            <div class="box-header">{Lang::T('Order Internet Package')}</div>
        </div>
        {foreach $routers as $router}
            <div class="box box-solid box-info">
                <div class="box-header">{$router['name']}</div>
                {if $router['description'] != ''}
                    <div class="box-body">
                        {$router['description']}
                    </div>
                {/if}

                <div class="box-body row">
                    {foreach $plans as $plan}
                        {if $router['name'] eq $plan['routers']}
                            <div class="col col-md-4">
                                <div class="box box-solid box-default">
                                    <div class="box-header">{$plan['name_plan']}</div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <tbody>
                                                <tr>
                                                    <td>{Lang::T('Type')}</td>
                                                    <td>{$plan['type']}</td>
                                                </tr>
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
                                    <div class="box-body">
                                        <a href="{$_url}order/buy/{$router['id']}/{$plan['id']}"
                                            onclick="return confirm('{Lang::T('Buy this? your active package will be overwrite')}')"
                                            class="btn btn-sm btn-block btn-primary">Buy</a>
                                    </div>
                                </div>
                            </div>
                        {/if}
                    {/foreach}
                </div>
            </div>
        {/foreach}
    </div>
</div>
{include file="sections/user-footer.tpl"}