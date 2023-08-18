{include file="sections/user-header.tpl"}
<!-- user-orderPlan -->
<div class="row">
    <div class="col-sm-12">
        <div class="box box-solid box-default">
            <div class="box-header">{Lang::T('Order Internet Package')}</div>
        </div>
        {if $_c['enable_balance'] == 'yes'}
            <div class="box box-solid box-primary">
                <div class="box-header">{Lang::T('Balance Plans')}</div>
                <div class="box-body row">
                    {foreach $plans_balance as $plan}
                        <div class="col col-md-4">
                            <div class="box box-solid box-default">
                                <div class="box-header">{$plan['name_plan']}</div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <tbody>
                                            <tr>
                                                <td>{Lang::T('Price')}</td>
                                                <td>{Lang::moneyFormat($plan['price'])}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="box-body">
                                    <a href="{$_url}order/buy/0/{$plan['id']}"
                                        onclick="return confirm('{Lang::T('Buy Balance?')}')"
                                        class="btn btn-sm btn-block btn-primary">Buy</a>
                                </div>
                            </div>
                        </div>
                    {/foreach}
                </div>
            </div>

            <div class="box box-solid box-success">
                <div class="box-header text-center text-bold">{Lang::T('Balance')} {Lang::moneyFormat($_user['balance'])}</div>
            </div>
        {/if}
        {foreach $routers as $router}
            <div class="box box-solid box-info">
                <div class="box-header text-black">{$router['name']}</div>
                {if $router['description'] != ''}
                    <div class="box-body">
                        {$router['description']}
                    </div>
                {/if}
                {if count($plans_hotspot)>0}
                    <div class="box-header">Hotspot</div>
                    <div class="box-body row">
                        {foreach $plans_hotspot as $plan}
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
                                                        <td>{Lang::T('Price')}</td>
                                                        <td>{Lang::moneyFormat($plan['price'])}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>{Lang::T('Validity')}</td>
                                                        <td>{$plan['validity']} {$plan['validity_unit']}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="box-body">
                                            <div class="btn-group btn-group-justified" role="group" aria-label="...">
                                                <a href="{$_url}order/buy/{$router['id']}/{$plan['id']}"
                                                    onclick="return confirm('{Lang::T('Buy this? your active package will be overwrite')}')"
                                                    class="btn btn-sm btn-block btn-warning text-black">Buy</a>
                                                {if $_c['enable_balance'] == 'yes' && $_user['balance']>=$plan['price']}
                                                    <a href="{$_url}order/pay/{$router['id']}/{$plan['id']}"
                                                        onclick="return confirm('{Lang::T('Pay this with Balance? your active package will be overwrite')}')"
                                                        class="btn btn-sm btn-block btn-success">{Lang::T('Pay With Balance')}</a>
                                                {/if}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            {/if}
                        {/foreach}
                    </div>
                {/if}
                {if count($plans_pppoe)>0}
                    <div class="box-header text-sm">PPPOE</div>
                    <div class="box-body row">
                        {foreach $plans_pppoe as $plan}
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
                                                        <td>{Lang::T('Price')}</td>
                                                        <td>{Lang::moneyFormat($plan['price'])}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>{Lang::T('Validity')}</td>
                                                        <td>{$plan['validity']} {$plan['validity_unit']}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="box-body">
                                            <div class="btn-group btn-group-justified" role="group" aria-label="...">
                                                <a href="{$_url}order/buy/{$router['id']}/{$plan['id']}"
                                                    onclick="return confirm('{Lang::T('Buy this? your active package will be overwrite')}')"
                                                    class="btn btn-sm btn-block btn-warning text-black">Buy</a>
                                                {if $_c['enable_balance'] == 'yes' && $_user['balance']>=$plan['price']}
                                                    <a href="{$_url}order/pay/{$router['id']}/{$plan['id']}"
                                                        onclick="return confirm('{Lang::T('Pay this with Balance? your active package will be overwrite')}')"
                                                        class="btn btn-sm btn-block btn-success">{Lang::T('Pay With Balance')}</a>
                                                {/if}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            {/if}
                        {/foreach}
                    </div>
                {/if}
            </div>
        {/foreach}
    </div>
</div>
{include file="sections/user-footer.tpl"}