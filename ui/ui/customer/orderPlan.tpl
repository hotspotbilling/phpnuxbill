{include file="customer/header.tpl"}
<!-- user-orderPlan -->
<div class="row">
    <div class="col-sm-12">
        <div class="box box-solid box-default">
            <div class="box-header">{Lang::T('Order Internet Package')}</div>
        </div>
        {if $_c['radius_enable']}
            {if $_user['service_type'] == 'PPPoE'}
                {if Lang::arrayCount($radius_pppoe)>0}
                    <ol class="breadcrumb">
                        <li>{if $_c['radius_plan']==''}Radius Plan{else}{$_c['radius_plan']}{/if}</li>
                        <li>{if $_c['pppoe_plan']==''}PPPOE Plan{else}{$_c['pppoe_plan']}{/if}</li>
                    </ol>
                    <div class="row">
                        {foreach $radius_pppoe as $plan}
                            <div class="col col-md-4">
                                <div class="box box-primary">
                                    <div class="box-header text-bold">{$plan['name_plan']}</div>
                                    <div class="table-responsive">
                                        <div style="margin-left: 5px; margin-right: 5px;">
                                            <table class="table table-bordered table-striped">
                                                <tbody>
                                                    <tr>
                                                        <td>{Lang::T('Type')}</td>
                                                        <td>{$plan['type']}</td>
                                                    </tr>
                                                    {if $_c['show_bandwidth_plan'] == 'yes'}
                                                        <tr>
                                                            <td>{Lang::T('Bandwidth')}</td>
                                                            <td api-get-text="{$_url}autoload_user/bw_name/{$plan['id_bw']}"></td>
                                                        </tr>
                                                    {/if}
                                                    <tr>
                                                        <td>{Lang::T('Price')}</td>
                                                        <td>{Lang::moneyFormat($plan['price'])}
                                                            {if !empty($plan['price_old'])}
                                                                <sup
                                                                    style="text-decoration: line-through; color: red">{Lang::moneyFormat($plan['price_old'])}</sup>
                                                            {/if}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>{Lang::T('Validity')}</td>
                                                        <td>{$plan['validity']} {$plan['validity_unit']}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="box-body">
                                        <a href="{$_url}order/gateway/radius/{$plan['id']}&stoken={App::getToken()}"
                                            onclick="return confirm('{Lang::T('Buy this? your active package will be overwrite')}')"
                                            class="btn btn-sm btn-block btn-warning text-black">{Lang::T('Buy')}</a>
                                        {if $_c['enable_balance'] == 'yes' && $_c['allow_balance_transfer'] == 'yes' && $_user['balance']>=$plan['price']}
                                            <a href="{$_url}order/send/radius/{$plan['id']}&stoken={App::getToken()}"
                                                onclick="return confirm('{Lang::T('Buy this for friend account?')}')"
                                                class="btn btn-sm btn-block btn-primary">{Lang::T('Buy for friend')}</a>
                                        {/if}
                                    </div>
                                </div>
                            </div>
                        {/foreach}
                    </div>
                {/if}
            {elseif $_user['service_type'] == 'Hotspot'}
                {if Lang::arrayCount($radius_hotspot)>0}
                    <ol class="breadcrumb">
                        <li>{if $_c['radius_plan']==''}Radius Plan{else}{$_c['radius_plan']}{/if}</li>
                        <li>{if $_c['hotspot_plan']==''}Hotspot Plan{else}{$_c['hotspot_plan']}{/if}</li>
                    </ol>
                    <div class="row">
                        {foreach $radius_hotspot as $plan}
                            <div class="col col-md-4">
                                <div class="box box-primary">
                                    <div class="box-header text-bold">{$plan['name_plan']}</div>
                                    <div class="table-responsive">
                                        <div style="margin-left: 5px; margin-right: 5px;">
                                            <table class="table table-bordered table-striped">
                                                <tbody>
                                                    <tr>
                                                        <td>{Lang::T('Type')}</td>
                                                        <td>{$plan['type']}</td>
                                                    </tr>
                                                    {if $_c['show_bandwidth_plan'] == 'yes'}
                                                        <tr>
                                                            <td>{Lang::T('Bandwidth')}</td>
                                                            <td api-get-text="{$_url}autoload_user/bw_name/{$plan['id_bw']}"></td>
                                                        </tr>
                                                    {/if}
                                                    <tr>
                                                        <td>{Lang::T('Price')}</td>
                                                        <td>{Lang::moneyFormat($plan['price'])}
                                                            {if !empty($plan['price_old'])}
                                                                <sup
                                                                    style="text-decoration: line-through; color: red">{Lang::moneyFormat($plan['price_old'])}</sup>
                                                            {/if}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>{Lang::T('Validity')}</td>
                                                        <td>{$plan['validity']} {$plan['validity_unit']}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="box-body">
                                        <a href="{$_url}order/gateway/radius/{$plan['id']}&stoken={App::getToken()}"
                                            onclick="return confirm('{Lang::T('Buy this? your active package will be overwrite')}')"
                                            class="btn btn-sm btn-block btn-warning text-black">{Lang::T('Buy')}</a>
                                        {if $_c['enable_balance'] == 'yes' && $_c['allow_balance_transfer'] == 'yes' && $_user['balance']>=$plan['price']}
                                        <a href="{$_url}order/send/radius/{$plan['id']}&stoken={App::getToken()}"
                                            onclick="return confirm('{Lang::T('Buy this for friend account?')}')"
                                            class="btn btn-sm btn-block btn-primary">{Lang::T('Buy for friend')}</a>
                                    {/if}
                                </div>
                            </div>
                        </div>
                    {/foreach}
                </div>
            {/if}
            {elseif $_user['service_type'] == 'Others' || $_user['service_type'] == '' && (Lang::arrayCount($radius_pppoe)>0
                    || Lang::arrayCount($radius_hotspot)>0)}
            <ol class="breadcrumb">
                <li>{if $_c['radius_plan']==''}Radius Plan{else}{$_c['radius_plan']}{/if}</li>
                <li>{if $_c['pppoe_plan']==''}PPPOE Plan{else}{$_c['pppoe_plan']}{/if}</li>
            </ol>
            {if Lang::arrayCount($radius_pppoe)>0}
                <div class="row">
                    {foreach $radius_pppoe as $plan}
                        <div class="col col-md-4">
                            <div class="box box-primary">
                                <div class="box-header text-bold">{$plan['name_plan']}</div>
                                <div class="table-responsive">
                                    <div style="margin-left: 5px; margin-right: 5px;">
                                        <table class="table table-bordered table-striped">
                                            <tbody>
                                                <tr>
                                                    <td>{Lang::T('Type')}</td>
                                                    <td>{$plan['type']}</td>
                                                </tr>
                                                {if $_c['show_bandwidth_plan'] == 'yes'}
                                                    <tr>
                                                        <td>{Lang::T('Bandwidth')}</td>
                                                        <td api-get-text="{$_url}autoload_user/bw_name/{$plan['id_bw']}"></td>
                                                    </tr>
                                                {/if}
                                                <tr>
                                                    <td>{Lang::T('Price')}</td>
                                                    <td>{Lang::moneyFormat($plan['price'])}
                                                        {if !empty($plan['price_old'])}
                                                            <sup
                                                                style="text-decoration: line-through; color: red">{Lang::moneyFormat($plan['price_old'])}</sup>
                                                        {/if}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>{Lang::T('Validity')}</td>
                                                    <td>{$plan['validity']} {$plan['validity_unit']}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="box-body">
                                    <a href="{$_url}order/gateway/pppoe/{$plan['id']}&stoken={App::getToken()}"
                                        onclick="return confirm('{Lang::T('Buy this? your active package will be overwritten')}')"
                                        class="btn btn-sm btn-block btn-warning text-black">{Lang::T('Buy')}</a>
                                    {if $_c['enable_balance'] == 'yes' && $_c['allow_balance_transfer'] == 'yes' && $_user['balance']>=$plan['price']}
                                    <a href="{$_url}order/send/pppoe/{$plan['id']}&stoken={App::getToken()}"
                                        onclick="return confirm('{Lang::T('Buy this for friend account?')}')"
                                        class="btn btn-sm btn-block btn-primary">{Lang::T('Buy for friend')}</a>
                                {/if}
                            </div>
                        </div>
                    </div>
                {/foreach}
            </div>
        {/if}
        {if Lang::arrayCount($radius_hotspot)>0}
            <ol class="breadcrumb">
                <li>{if $_c['radius_plan']==''}Radius Plan{else}{$_c['radius_plan']}{/if}</li>
                <li>{if $_c['hotspot_plan']==''}Hotspot Plan{else}{$_c['hotspot_plan']}{/if}</li>
            </ol>
            <div class="row">
                {foreach $radius_hotspot as $plan}
                    <div class="col col-md-4">
                        <div class="box box-primary">
                            <div class="box-header text-bold">{$plan['name_plan']}</div>
                            <div class="table-responsive">
                                <div style="margin-left: 5px; margin-right: 5px;">
                                    <table class="table table-bordered table-striped">
                                        <tbody>
                                            <tr>
                                                <td>{Lang::T('Type')}</td>
                                                <td>{$plan['type']}</td>
                                            </tr>
                                            {if $_c['show_bandwidth_plan'] == 'yes'}
                                                <tr>
                                                    <td>{Lang::T('Bandwidth')}</td>
                                                    <td api-get-text="{$_url}autoload_user/bw_name/{$plan['id_bw']}"></td>
                                                </tr>
                                            {/if}
                                            <tr>
                                                <td>{Lang::T('Price')}</td>
                                                <td>{Lang::moneyFormat($plan['price'])}
                                                    {if !empty($plan['price_old'])}
                                                        <sup
                                                            style="text-decoration: line-through; color: red">{Lang::moneyFormat($plan['price_old'])}</sup>
                                                    {/if}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>{Lang::T('Validity')}</td>
                                                <td>{$plan['validity']} {$plan['validity_unit']}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="box-body">
                                <a href="{$_url}order/gateway/hotspot/{$plan['id']}&stoken={App::getToken()}"
                                    onclick="return confirm('{Lang::T('Buy this? your active package will be overwritten')}')"
                                    class="btn btn-sm btn-block btn-warning text-black">{Lang::T('Buy')}</a>
                                {if $_c['enable_balance'] == 'yes' && $_c['allow_balance_transfer'] == 'yes' &&
                                                                                                                                        $_user['balance']>=$plan['price']}
                                <a href="{$_url}order/send/hotspot/{$plan['id']}&stoken={App::getToken()}"
                                    onclick="return confirm('{Lang::T('Buy this for friend account?')}')"
                                    class="btn btn-sm btn-block btn-primary">{Lang::T('Buy for friend')}</a>
                            {/if}
                        </div>
                    </div>
                </div>
            {/foreach}
        </div>
        {/if}
        {/if}
        {/if}
        {foreach $routers as $router}
            {if Validator::isRouterHasPlan($plans_hotspot, $router['name']) || Validator::isRouterHasPlan($plans_pppoe,
                                                                $router['name']) || Validator::isRouterHasPlan($plans_vpn,
                                                                $router['name'])}
            <div class="box box-solid box-primary bg-gray">
                <div class="box-header text-white text-bold">{$router['name']}</div>
                {if $router['description'] != ''}
                    <div class="box-body">
                        {$router['description']}
                    </div>
                {/if}
                {if $_user['service_type'] == 'Hotspot' && Validator::countRouterPlan($plans_hotspot, $router['name'])>0}
                    <div class="box-header text-white">{if $_c['hotspot_plan']==''}Hotspot Plan{else}{$_c['hotspot_plan']}{/if}
                    </div>
                    <div class="box-body row">
                        {foreach $plans_hotspot as $plan}
                            {if $router['name'] eq $plan['routers']}
                                <div class="col col-md-4">
                                    <div class="box box-primary">
                                        <div class="box-header text-center text-bold">{$plan['name_plan']}</div>
                                        <div class="table-responsive">
                                            <div style="margin-left: 5px; margin-right: 5px;">
                                                <table class="table table-bordered table-striped">
                                                    <tbody>
                                                        <tr>
                                                            <td>{Lang::T('Type')}</td>
                                                            <td>{$plan['type']}</td>
                                                        </tr>
                                                        {if $_c['show_bandwidth_plan'] == 'yes'}
                                                            <tr>
                                                                <td>{Lang::T('Bandwidth')}</td>
                                                                <td api-get-text="{$_url}autoload_user/bw_name/{$plan['id_bw']}"></td>
                                                            </tr>
                                                        {/if}
                                                        <tr>
                                                            <td>{Lang::T('Price')}</td>
                                                            <td>{Lang::moneyFormat($plan['price'])}
                                                                {if !empty($plan['price_old'])}
                                                                    <sup
                                                                        style="text-decoration: line-through; color: red">{Lang::moneyFormat($plan['price_old'])}</sup>
                                                                {/if}
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>{Lang::T('Validity')}</td>
                                                            <td>{$plan['validity']} {$plan['validity_unit']}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="box-body">
                                            <a href="{$_url}order/gateway/{$router['id']}/{$plan['id']}&stoken={App::getToken()}"
                                                onclick="return confirm('{Lang::T('Buy this? your active package will be overwrite')}')"
                                                class="btn btn-sm btn-block btn-warning text-black">{Lang::T('Buy')}</a>
                                            {if $_c['enable_balance'] == 'yes' && $_c['allow_balance_transfer'] == 'yes' && $_user['balance']>=$plan['price']}
                                            <a href="{$_url}order/send/{$router['id']}/{$plan['id']}&stoken={App::getToken()}"
                                                onclick="return confirm('{Lang::T('Buy this for friend account?')}')"
                                                class="btn btn-sm btn-block btn-primary">{Lang::T('Buy for friend')}</a>
                                        {/if}
                                    </div>
                                </div>
                            </div>
                        {/if}
                    {/foreach}
                </div>
            {/if}
            {if $_user['service_type'] == 'PPPoE' && Validator::countRouterPlan($plans_pppoe,$router['name'])>0}
                <div class="box-header text-white">{if $_c['pppoe_plan']==''}PPPOE Plan{else}{$_c['pppoe_plan']}{/if}</div>
                <div class="box-body row">
                    {foreach $plans_pppoe as $plan}
                        {if $router['name'] eq $plan['routers']}
                            <div class="col col-md-4">
                                <div class="box box- box-primary">
                                    <div class="box-header text-bold text-center">{$plan['name_plan']}</div>
                                    <div class="table-responsive">
                                        <div style="margin-left: 5px; margin-right: 5px;">
                                            <table class="table table-bordered table-striped">
                                                <tbody>
                                                    <tr>
                                                        <td>{Lang::T('Type')}</td>
                                                        <td>{$plan['type']}</td>
                                                    </tr>
                                                    {if $_c['show_bandwidth_plan'] == 'yes'}
                                                        <tr>
                                                            <td>{Lang::T('Bandwidth')}</td>
                                                            <td api-get-text="{$_url}autoload_user/bw_name/{$plan['id_bw']}"></td>
                                                        </tr>
                                                    {/if}
                                                    <tr>
                                                        <td>{Lang::T('Price')}</td>
                                                        <td>{Lang::moneyFormat($plan['price'])}
                                                            {if !empty($plan['price_old'])}
                                                                <sup
                                                                    style="text-decoration: line-through; color: red">{Lang::moneyFormat($plan['price_old'])}</sup>
                                                            {/if}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>{Lang::T('Validity')}</td>
                                                        <td>{$plan['validity']} {$plan['validity_unit']}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="box-body">
                                        <a href="{$_url}order/gateway/{$router['id']}/{$plan['id']}&stoken={App::getToken()}"
                                            onclick="return confirm('{Lang::T('Buy this? your active package will be overwrite')}')"
                                            class="btn btn-sm btn-block btn-warning text-black">{Lang::T('Buy')}</a>
                                        {if $_c['enable_balance'] == 'yes' && $_c['allow_balance_transfer'] == 'yes' && $_user['balance']>=$plan['price']}
                                        <a href="{$_url}order/send/{$router['id']}/{$plan['id']}&stoken={App::getToken()}"
                                            onclick="return confirm('{Lang::T('Buy this for friend account?')}')"
                                            class="btn btn-sm btn-block btn-primary">{Lang::T('Buy for friend')}</a>
                                    {/if}
                                </div>
                            </div>
                        </div>
                    {/if}
                {/foreach}
            </div>
            {/if}
            {if $_user['service_type'] == 'VPN' && Validator::countRouterPlan($plans_vpn,$router['name'])>0}
                <div class="box-header text-white">{if $_c['vpn_plan']==''}VPN Plan{else}{$_c['vpn_plan']}{/if}</div>
                <div class="box-body row">
                    {foreach $plans_vpn as $plan}
                        {if $router['name'] eq $plan['routers']}
                            <div class="col col-md-4">
                                <div class="box box- box-primary">
                                    <div class="box-header text-bold text-center">{$plan['name_plan']}</div>
                                    <div class="table-responsive">
                                        <div style="margin-left: 5px; margin-right: 5px;">
                                            <table class="table table-bordered table-striped">
                                                <tbody>
                                                    <tr>
                                                        <td>{Lang::T('Type')}</td>
                                                        <td>{$plan['type']}</td>
                                                    </tr>
                                                    {if $_c['show_bandwidth_plan'] == 'yes'}
                                                        <tr>
                                                            <td>{Lang::T('Bandwidth')}</td>
                                                            <td api-get-text="{$_url}autoload_user/bw_name/{$plan['id_bw']}"></td>
                                                        </tr>
                                                    {/if}
                                                    <tr>
                                                        <td>{Lang::T('Price')}</td>
                                                        <td>{Lang::moneyFormat($plan['price'])}
                                                            {if !empty($plan['price_old'])}
                                                                <sup
                                                                    style="text-decoration: line-through; color: red">{Lang::moneyFormat($plan['price_old'])}</sup>
                                                            {/if}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>{Lang::T('Validity')}</td>
                                                        <td>{$plan['validity']} {$plan['validity_unit']}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="box-body">
                                        <a href="{$_url}order/gateway/{$router['id']}/{$plan['id']}&stoken={App::getToken()}"
                                            onclick="return confirm('{Lang::T('Buy this? your active package will be overwrite')}')"
                                            class="btn btn-sm btn-block btn-warning text-black">{Lang::T('Buy')}</a>
                                        {if $_c['enable_balance'] == 'yes' && $_c['allow_balance_transfer'] == 'yes' && $_user['balance']>=$plan['price']}
                                        <a href="{$_url}order/send/{$router['id']}/{$plan['id']}&stoken={App::getToken()}"
                                            onclick="return confirm('{Lang::T('Buy this for friend account?')}')"
                                            class="btn btn-sm btn-block btn-primary">{Lang::T('Buy for friend')}</a>
                                    {/if}
                                </div>
                            </div>
                        </div>
                    {/if}
                {/foreach}
            </div>
            {/if}
            {if $_user['service_type'] == 'Others' || $_user['service_type'] == '' &&
            (Validator::countRouterPlan($plans_hotspot, $router['name'])>0 || Validator::countRouterPlan($plans_pppoe,
            $router['name'])>0 || Validator::countRouterPlan($plans_vpn,
            $router['name'])>0)}
            <div class="box-header text-white">{if $_c['hotspot_plan']==''}Hotspot Plan{else}{$_c['hotspot_plan']}{/if}
            </div>
            <div class="box-body row">
                {foreach $plans_hotspot as $plan}
                    {if $router['name'] eq $plan['routers']}
                        <div class="col col-md-4">
                            <div class="box box-primary">
                                <div class="box-header text-center text-bold">{$plan['name_plan']}</div>
                                <div class="table-responsive">
                                    <div style="margin-left: 5px; margin-right: 5px;">
                                        <table class="table table-bordered table-striped">
                                            <tbody>
                                                <tr>
                                                    <td>{Lang::T('Type')}</td>
                                                    <td>{$plan['type']}</td>
                                                </tr>
                                                {if $_c['show_bandwidth_plan'] == 'yes'}
                                                    <tr>
                                                        <td>{Lang::T('Bandwidth')}</td>
                                                        <td api-get-text="{$_url}autoload_user/bw_name/{$plan['id_bw']}"></td>
                                                    </tr>
                                                {/if}
                                                <tr>
                                                    <td>{Lang::T('Price')}</td>
                                                    <td>{Lang::moneyFormat($plan['price'])}
                                                        {if !empty($plan['price_old'])}
                                                            <sup
                                                                style="text-decoration: line-through; color: red">{Lang::moneyFormat($plan['price_old'])}</sup>
                                                        {/if}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>{Lang::T('Validity')}</td>
                                                    <td>{$plan['validity']} {$plan['validity_unit']}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="box-body">
                                    <a href="{$_url}order/gateway/{$router['id']}/{$plan['id']}&stoken={App::getToken()}"
                                        onclick="return confirm('{Lang::T('Buy this? your active package will be overwrite')}')"
                                        class="btn btn-sm btn-block btn-warning text-black">{Lang::T('Buy')}</a>
                                    {if $_c['enable_balance'] == 'yes' && $_c['allow_balance_transfer'] == 'yes' && $_user['balance']>=$plan['price']}
                                    <a href="{$_url}order/send/{$router['id']}/{$plan['id']}&stoken={App::getToken()}"
                                        onclick="return confirm('{Lang::T('Buy this for friend account?')}')"
                                        class="btn btn-sm btn-block btn-primary">{Lang::T('Buy for friend')}</a>
                                {/if}
                            </div>
                        </div>
                    </div>
                {/if}
                {/foreach}
            </div>
            <div class="box-header text-white">{if $_c['pppoe_plan']==''}PPPOE Plan{else}{$_c['pppoe_plan']}{/if}</div>
            <div class="box-body row">
                {foreach $plans_pppoe as $plan}
                    {if $router['name'] eq $plan['routers']}
                        <div class="col col-md-4">
                            <div class="box box- box-primary">
                                <div class="box-header text-bold text-center">{$plan['name_plan']}</div>
                                <div class="table-responsive">
                                    <div style="margin-left: 5px; margin-right: 5px;">
                                        <table class="table table-bordered table-striped">
                                            <tbody>
                                                <tr>
                                                    <td>{Lang::T('Type')}</td>
                                                    <td>{$plan['type']}</td>
                                                </tr>
                                                {if $_c['show_bandwidth_plan'] == 'yes'}
                                                    <tr>
                                                        <td>{Lang::T('Bandwidth')}</td>
                                                        <td api-get-text="{$_url}autoload_user/bw_name/{$plan['id_bw']}"></td>
                                                    </tr>
                                                {/if}
                                                <tr>
                                                    <td>{Lang::T('Price')}</td>
                                                    <td>{Lang::moneyFormat($plan['price'])}
                                                        {if !empty($plan['price_old'])}
                                                            <sup
                                                                style="text-decoration: line-through; color: red">{Lang::moneyFormat($plan['price_old'])}</sup>
                                                        {/if}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>{Lang::T('Validity')}</td>
                                                    <td>{$plan['validity']} {$plan['validity_unit']}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="box-body">
                                    <a href="{$_url}order/gateway/{$router['id']}/{$plan['id']}&stoken={App::getToken()}"
                                        onclick="return confirm('{Lang::T('Buy this? your active package will be overwrite')}')"
                                        class="btn btn-sm btn-block btn-warning text-black">{Lang::T('Buy')}</a>
                                    {if $_c['enable_balance'] == 'yes' && $_c['allow_balance_transfer'] == 'yes' && $_user['balance']>=$plan['price']}
                                    <a href="{$_url}order/send/{$router['id']}/{$plan['id']}&stoken={App::getToken()}"
                                        onclick="return confirm('{Lang::T('Buy this for friend account?')}')"
                                        class="btn btn-sm btn-block btn-primary">{Lang::T('Buy for friend')}</a>
                                {/if}
                            </div>
                        </div>
                    </div>
                {/if}
                {/foreach}
            </div>
            <div class="box-header text-white">{if $_c['vpn_plan']==''}VPN Plan{else}{$_c['vpn_plan']}{/if}</div>
            <div class="box-body row">
                {foreach $plans_vpn as $plan}
                    {if $router['name'] eq $plan['routers']}
                        <div class="col col-md-4">
                            <div class="box box- box-primary">
                                <div class="box-header text-bold text-center">{$plan['name_plan']}</div>
                                <div class="table-responsive">
                                    <div style="margin-left: 5px; margin-right: 5px;">
                                        <table class="table table-bordered table-striped">
                                            <tbody>
                                                <tr>
                                                    <td>{Lang::T('Type')}</td>
                                                    <td>{$plan['type']}</td>
                                                </tr>
                                                {if $_c['show_bandwidth_plan'] == 'yes'}
                                                    <tr>
                                                        <td>{Lang::T('Bandwidth')}</td>
                                                        <td api-get-text="{$_url}autoload_user/bw_name/{$plan['id_bw']}"></td>
                                                    </tr>
                                                {/if}
                                                <tr>
                                                    <td>{Lang::T('Price')}</td>
                                                    <td>{Lang::moneyFormat($plan['price'])}
                                                        {if !empty($plan['price_old'])}
                                                            <sup
                                                                style="text-decoration: line-through; color: red">{Lang::moneyFormat($plan['price_old'])}</sup>
                                                        {/if}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>{Lang::T('Validity')}</td>
                                                    <td>{$plan['validity']} {$plan['validity_unit']}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="box-body">
                                    <a href="{$_url}order/gateway/{$router['id']}/{$plan['id']}&stoken={App::getToken()}"
                                        onclick="return confirm('{Lang::T('Buy this? your active package will be overwrite')}')"
                                        class="btn btn-sm btn-block btn-warning text-black">{Lang::T('Buy')}</a>
                                    {if $_c['enable_balance'] == 'yes' && $_c['allow_balance_transfer'] == 'yes' && $_user['balance']>=$plan['price']}
                                    <a href="{$_url}order/send/{$router['id']}/{$plan['id']}&stoken={App::getToken()}"
                                        onclick="return confirm('{Lang::T('Buy this for friend account?')}')"
                                        class="btn btn-sm btn-block btn-primary">{Lang::T('Buy for friend')}</a>
                                {/if}
                            </div>
                        </div>
                    </div>
                {/if}
                {/foreach}
            </div>
            {/if}
        </div>
        {/if}
        {/foreach}
    </div>
</div>
{include file="customer/footer.tpl"}