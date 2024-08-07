{include file="sections/header.tpl"}
<div class="panel panel-hovered mb20 panel-primary">
    <div class="panel-heading">
        {ucwords($pg)}
    </div>
    <div class="panel-body">
        <form id="site-search" method="post" action="{$_url}paymentgateway/audit/{$pg}">
            <div class="input-group">
                <input type="text" name="q" class="form-control" placeholder="{Lang::T('Search')}..."
                    value="{$q}">
                <div class="input-group-btn">
                    <button type="submit" class="btn btn-success"><span class="glyphicon glyphicon-search"></span></button>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-condensed">
                <thead>
                    <tr>
                        <th>TRX ID</th>
                        <th>PG ID</th>
                        <th>{Lang::T('Username')}</th>
                        <th>{Lang::T('Plan Name')}</th>
                        <th>{Lang::T('Routers')}</th>
                        <th>{Lang::T('Price')}</th>
                        <th>{Lang::T('Payment Link')}</th>
                        <th>{Lang::T('Channel')}</th>
                        <th>{Lang::T('Created')}</th>
                        <th>{Lang::T('Expired')}</th>
                        <th>{Lang::T('Paid')}</th>
                        <th>{Lang::T('Invoice')}</th>
                        <th>{Lang::T('Status')}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $pgs as $pg}
                        <tr class="{if $pg['status'] == 1}warning{elseif $pg['status'] == 2}success{else}danger{/if}">
                            <td>{$pg['id']}</td>
                            <td><a href="{$_url}paymentgateway/audit-view/{$pg['id']}"
                                    class="text-black">{$pg['gateway_trx_id']}</a></td>
                            <td><a href="{$_url}customers/viewu/{$pg['username']}" class="text-black">{$pg['username']}</a>
                            </td>
                            <td>{$pg['plan_name']}</td>
                            <td>{$pg['routers']}</td>
                            <td>{Lang::moneyFormat($pg['price'])}</td>
                            <td>
                                {if $pg['pg_url_payment']}
                                    <a href="{$pg['pg_url_payment']}" target="_blank" class="btn btn-xs btn-default btn-block"
                                        rel="noopener noreferrer">open</a>
                                {/if}
                            </td>
                            <td>{$pg['payment_method']} - {$pg['payment_channel']}</td>
                            <td>{if $pg['created_date'] != null}{Lang::dateTimeFormat($pg['created_date'])}{/if}</td>
                            <td>{if $pg['expired_date'] != null}{Lang::dateTimeFormat($pg['expired_date'])}{/if}</td>
                            <td>{if $pg['paid_date'] != null}{Lang::dateTimeFormat($pg['paid_date'])}{/if}</td>
                            <td>{if $pg['trx_invoice']}<a href="{$_url}reports/activation&q={$pg['trx_invoice']}"
                                    class="text-black">{$pg['trx_invoice']}</a>{/if}</td>
                            <td>{if $pg['status'] == 1}UNPAID{elseif $pg['status'] == 2}PAID{elseif $pg['status'] == 3}FAILED{else}CANCELED{/if}
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        {include file="pagination.tpl"}
        <a href="{$_url}paymentgateway/" class="btn btn-default btn-xs">kembali</a>
    </div>
</div>

{include file="sections/footer.tpl"}