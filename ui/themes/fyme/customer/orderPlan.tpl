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
                            {include file="customer/orderPlan_card.tpl"}
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
                            {include file="customer/orderPlan_card.tpl"}
                        {/foreach}
                    </div>
                {/if}
            {elseif $_user['service_type'] == 'Others' || $_user['service_type'] == '' && (Lang::arrayCount($radius_pppoe)>0 || Lang::arrayCount($radius_hotspot)>0)}
                <ol class="breadcrumb">
                    <li>{if $_c['radius_plan']==''}Radius Plan{else}{$_c['radius_plan']}{/if}</li>
                    <li>{if $_c['pppoe_plan']==''}PPPOE Plan{else}{$_c['pppoe_plan']}{/if}</li>
                </ol>
                {if Lang::arrayCount($radius_pppoe)>0}
                    <div class="row">
                        {foreach $radius_pppoe as $plan}
                            {include file="customer/orderPlan_card.tpl"}
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
                            {include file="customer/orderPlan_card.tpl"}
                        {/foreach}
                    </div>
                {/if}
            {/if}
        {/if}

        {foreach $routers as $router}
            {if Validator::isRouterHasPlan($plans_hotspot, $router['name']) || Validator::isRouterHasPlan($plans_pppoe, $router['name']) || Validator::isRouterHasPlan($plans_vpn, $router['name'])}
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
                                {include file="customer/orderPlan_card.tpl"}
                            {/if}
                        {/foreach}
                    </div>
                {/if}
                {if $_user['service_type'] == 'PPPoE' && Validator::countRouterPlan($plans_pppoe,$router['name'])>0}
                    <div class="box-header text-white">{if $_c['pppoe_plan']==''}PPPOE Plan{else}{$_c['pppoe_plan']}{/if}</div>
                    <div class="box-body row">
                        {foreach $plans_pppoe as $plan}
                            {if $router['name'] eq $plan['routers']}
                                {include file="customer/orderPlan_card.tpl"}
                            {/if}
                        {/foreach}
                    </div>
                {/if}
                {if $_user['service_type'] == 'VPN' && Validator::countRouterPlan($plans_vpn,$router['name'])>0}
                    <div class="box-header text-white">{if $_c['vpn_plan']==''}VPN Plan{else}{$_c['vpn_plan']}{/if}</div>
                    <div class="box-body row">
                        {foreach $plans_vpn as $plan}
                            {if $router['name'] eq $plan['routers']}
                                {include file="customer/orderPlan_card.tpl"}
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
                            {include file="customer/orderPlan_card.tpl"}
                        {/if}
                    {/foreach}
                </div>
                <div class="box-header text-white">{if $_c['pppoe_plan']==''}PPPOE Plan{else}{$_c['pppoe_plan']}{/if}</div>
                <div class="box-body row">
                    {foreach $plans_pppoe as $plan}
                        {if $router['name'] eq $plan['routers']}
                            {include file="customer/orderPlan_card.tpl"}
                        {/if}
                    {/foreach}
                </div>
                <div class="box-header text-white">{if $_c['vpn_plan']==''}VPN Plan{else}{$_c['vpn_plan']}{/if}</div>
                <div class="box-body row">
                    {foreach $plans_vpn as $plan}
                        {if $router['name'] eq $plan['routers']}
                            {include file="customer/orderPlan_card.tpl"}
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