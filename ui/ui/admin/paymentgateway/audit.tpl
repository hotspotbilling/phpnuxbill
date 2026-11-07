{include file="sections/header.tpl"}

<div class="ls">
    <div class="ls-top">
        <div></div>
        <div class="ls-actions">
            <a href="{Text::url('paymentgateway')}" class="ls-btn"><i class="fa fa-arrow-left" aria-hidden="true"></i> {Lang::T('back')}</a>
        </div>
    </div>

    <div class="ls-card">
        <form id="site-search" class="ls-bar" method="post" action="{Text::url('paymentgateway/audit/')}{$pg}">
            <div class="ls-search">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="search" autocomplete="off" name="q" value="{$q|escape}" placeholder="{Lang::T('Search')}..." aria-label="{Lang::T('Search')}...">
            </div>
            <button class="ls-btn ls-btn--primary" type="submit"><i class="fa fa-search" aria-hidden="true"></i> {Lang::T('Search')}</button>
            <a class="ls-btn" title="{Lang::T('Clear Search Query')}" href="{Text::url('paymentgateway/audit/')}{$pg}"><i class="fa fa-undo" aria-hidden="true"></i> {Lang::T('Reset')}</a>
        </form>

        <div class="ls-scroll">
            <table class="ls-table">
                <thead>
                    <tr>
                        <th>{Lang::T('TRX ID')}</th>
                        <th>{Lang::T('PG ID')}</th>
                        <th>{Lang::T('Username')}</th>
                        <th>{Lang::T('Plan Name')}</th>
                        <th>{Lang::T('Routers')}</th>
                        <th class="ls-num">{Lang::T('Price')}</th>
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
                    {foreach $pgs as $ds}
                    <tr>
                        <td data-label="{Lang::T('TRX ID')}" class="ls-dim">{$ds['id']}</td>
                        <td data-label="{Lang::T('PG ID')}"><a class="ls-strong" href="{Text::url('paymentgateway/audit-view/')}{$ds['id']}">{$ds['gateway_trx_id']|escape}</a></td>
                        <td data-label="{Lang::T('Username')}"><a href="{Text::url('customers/viewu/')}{$ds['username']|escape:'url'}">{$ds['username']|escape}</a></td>
                        <td data-label="{Lang::T('Plan Name')}">{$ds['plan_name']|escape}</td>
                        <td data-label="{Lang::T('Routers')}">{$ds['routers']|escape}</td>
                        <td data-label="{Lang::T('Price')}" class="ls-num">{Lang::moneyFormat($ds['price'])}</td>
                        <td data-label="{Lang::T('Payment Link')}">{if $ds['pg_url_payment']}<a class="ls-btn ls-btn--sm" href="{$ds['pg_url_payment']|escape}" target="_blank" rel="noopener noreferrer">{Lang::T('open')}</a>{/if}</td>
                        <td data-label="{Lang::T('Channel')}" class="ls-dim">{$ds['payment_method']|escape} - {$ds['payment_channel']|escape}</td>
                        <td data-label="{Lang::T('Created')}" class="ls-dim ls-when">{if $ds['created_date'] != null}{Lang::dateTimeFormat($ds['created_date'])}{/if}</td>
                        <td data-label="{Lang::T('Expired')}" class="ls-dim ls-when">{if $ds['expired_date'] != null}{Lang::dateTimeFormat($ds['expired_date'])}{/if}</td>
                        <td data-label="{Lang::T('Paid')}" class="ls-dim ls-when">{if $ds['paid_date'] != null}{Lang::dateTimeFormat($ds['paid_date'])}{/if}</td>
                        <td data-label="{Lang::T('Invoice')}">{if $ds['trx_invoice']}<a href="{Text::url('reports/activation&q=')}{$ds['trx_invoice']|escape:'url'}">{$ds['trx_invoice']|escape}</a>{/if}</td>
                        <td data-label="{Lang::T('Status')}"><span class="ls-pill {if $ds['status'] == 1}ls-pill--inactive{elseif $ds['status'] == 2}ls-pill--active{else}ls-pill--banned{/if}">{if $ds['status'] == 1}UNPAID{elseif $ds['status'] == 2}PAID{elseif $ds['status'] == 3}FAILED{else}CANCELED{/if}</span></td>
                    </tr>
                    {foreachelse}
                    <tr class="ls-none"><td colspan="13">
                        <div class="ls-empty"><b>{Lang::T('No transactions found')}</b>
                        </div>
                    </td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        {include file="pagination.tpl"}
    </div>
</div>

{include file="sections/footer.tpl"}
