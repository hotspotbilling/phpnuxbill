{include file="sections/user-header.tpl"}
<!-- user-orderView -->
<div class="row">
    <div class="col-md-3"></div>
    <div class="col-md-6">
        <div
            class="panel mb20 {if $trx['status']==1}panel-warning{elseif $trx['status']==2}panel-success{elseif $trx['status']==3}panel-danger{elseif $trx['status']==4}panel-danger{else}panel-primary{/if} panel-hovered">
            <div class="panel-footer">Transaction #{$trx['id']}</div>
            {if $trx['routers']!='balance'}
                <div class="panel-body">
                    <div class="panel panel-primary panel-hovered">
                        <div class="panel-heading">{$router['name']}</div>
                        <div class="panel-body">
                            {$router['description']}
                        </div>
                    </div>
                </div>
            {/if}
            <div class="table-responsive">
                {if $trx['pg_url_payment']=='balance'}
                    <table class="table table-bordered table-striped table-bordered">
                        <tbody>
                            <tr>
                                <td>{Lang::T('Type')}</td>
                                <td>{$trx['plan_name']}</td>
                            </tr>
                            <tr>
                                <td>{Lang::T('Paid Date')}</td>
                                <td>{date($_c['date_format'], strtotime($trx['paid_date']))}
                                    {date('H:i', strtotime($trx['paid_date']))} </td>
                            </tr>
                            <tr>
                            {if $trx['plan_name'] == 'Receive Balance'}
                                <td>{Lang::T('From')}</td>
                                {else}
                                    <td>{Lang::T('To')}</td>
                                {/if}
                                <td>{$trx['gateway']}</td>
                            </tr>
                            <tr>
                                <td>{$_L['Balance']}</td>
                                <td>{Lang::moneyFormat($trx['price'])}</td>
                            </tr>
                        </tbody>
                    </table>
                {else}
                    <table class="table table-bordered table-striped table-bordered">
                        <tbody>
                            <tr>
                                <td>{Lang::T('Status')}</td>
                                <td>{if $trx['status']==1}{Lang::T('UNPAID')}{elseif $trx['status']==2}{Lang::T('PAID')}{elseif $trx['status']==3}{Lang::T('FAILED')}{elseif $trx['status']==4}{Lang::T('CANCELED')}{else}{Lang::T('UNKNOWN')}{/if}
                                </td>
                            </tr>
                            <tr>
                                <td>{Lang::T('expired')}</td>
                                <td>{date($_c['date_format'], strtotime($trx['expired_date']))}
                                    {date('H:i', strtotime($trx['expired_date']))} </td>
                            </tr>
                            {if $trx['status']==2}
                                <tr>
                                    <td>{Lang::T('Paid Date')}</td>
                                    <td>{date($_c['date_format'], strtotime($trx['paid_date']))}
                                        {date('H:i', strtotime($trx['paid_date']))} </td>
                                </tr>
                            {/if}
                            <tr>
                                <td>{$_L['Plan_Name']}</td>
                                <td>{$plan['name_plan']}</td>
                            </tr>
                            <tr>
                                <td>{$_L['Plan_Price']}</td>
                                <td>{Lang::moneyFormat($plan['price'])}</td>
                            </tr>
                            <tr>
                                <td>{Lang::T('Type')}</td>
                                <td>{$plan['type']}</td>
                            </tr>
                            {if $plan['type']!='Balance'}
                                {if $plan['type'] eq 'Hotspot'}
                                    <tr>
                                        <td>{Lang::T('Plan_Type')}</td>
                                        <td>{Lang::T($plan['typebp'])}</td>
                                    </tr>
                                    {if $plan['typebp'] eq 'Limited'}
                                        {if $plan['limit_type'] eq 'Time_Limit' or $plan['limit_type'] eq 'Both_Limit'}
                                            <tr>
                                                <td>{Lang::T('Time_Limit')}</td>
                                                <td>{$ds['time_limit']} {$ds['time_unit']}</td>
                                            </tr>
                                        {/if}
                                        {if $plan['limit_type'] eq 'Data_Limit' or $plan['limit_type'] eq 'Both_Limit'}
                                            <tr>
                                                <td>{Lang::T('Data_Limit')}</td>
                                                <td>{$ds['data_limit']} {$ds['data_unit']}</td>
                                            </tr>
                                        {/if}
                                    {/if}
                                {/if}
                                <tr>
                                    <td>{$_L['Plan_Validity']}</td>
                                    <td>{$plan['validity']} {$plan['validity_unit']}</td>
                                </tr>
                                <tr>
                                    <td>{$_L['Bandwidth_Plans']}</td>
                                    <td>{$bandw['name_bw']}<br>{$bandw['rate_down']}{$bandw['rate_down_unit']}/{$bandw['rate_up']}{$bandw['rate_up_unit']}
                                    </td>
                                </tr>
                            {/if}
                        </tbody>
                    </table>
                {/if}
            </div>
            {if $trx['status']==1}
                <div class="panel-footer ">
                    <div class="btn-group btn-group-justified">
                        <a href="{$trx['pg_url_payment']}" {if $trx['gateway']=='midtrans'} target="_blank" {/if}
                            class="btn btn-primary">{Lang::T('PAY NOW')}</a>
                        <a href="{$_url}order/view/{$trx['id']}/check"
                            class="btn btn-info">{Lang::T('Check for Payment')}</a>
                    </div>
                </div>
                <div class="panel-footer ">
                    <a href="{$_url}order/view/{$trx['id']}/cancel" class="btn btn-danger"
                        onclick="return confirm('{Lang::T('Cancel it?')}')">{Lang::T('Cancel')}</a>
                </div>
            {/if}
        </div>
    </div>
</div>
{include file="sections/user-footer.tpl"}