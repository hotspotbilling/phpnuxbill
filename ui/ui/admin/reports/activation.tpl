{include file="sections/header.tpl"}
{* Activation History: every transaction, newest first. type= narrows to one service type (tabs), q= finds invoices that
   start with the text typed ("INV-" may be left out). Counts per type are refreshed once a minute. *}

<div class="ls rf rp">

    <div class="ls-tabs" role="tablist" aria-label="{Lang::T('Type')}">
        {$qu = $q|escape:'url'}
        <a class="ls-tab{if $type == ''} is-on{/if}" href="{Text::url('reports/activation&q=', $qu)}">{Lang::T('All')} <small>{array_sum($typeCounts)|number_format}</small></a>
        {foreach $typeCounts as $tp => $n}
        {$tpu = $tp|escape:'url'}
        <a class="ls-tab{if $type == $tp} is-on{/if}" href="{Text::url('reports/activation&type=', $tpu, '&q=', $qu)}">{$tp|escape} <small>{$n|number_format}</small></a>
        {/foreach}
    </div>

    <div class="ls-card">
        <form id="site-search" class="ls-bar" method="get" action="{Text::url('reports/activation')}">
            <input type="hidden" name="_route" value="reports/activation">
            {if $type}<input type="hidden" name="type" value="{$type|escape}">{/if}
            <div class="ls-search">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="search" autocomplete="off" name="q" value="{$q|escape}" placeholder="{Lang::T('Invoice number, e.g. INV-1234')}" aria-label="{Lang::T('Invoice')}">
            </div>
            <button class="ls-btn ls-btn--primary" type="submit"><i class="fa fa-search" aria-hidden="true"></i> {Lang::T('Search')}</button>
            {if $q || $type}<a class="ls-btn" title="{Lang::T('Clear Search Query')}" href="{Text::url('reports/activation')}"><i class="fa fa-undo" aria-hidden="true"></i> {Lang::T('Reset')}</a>{/if}
            <small class="rp-bar__count ls-dim">{$total|number_format} {if $total == 1}{Lang::T('activation')}{else}{Lang::T('activations')}{/if}</small>
        </form>

        <div class="ls-scroll">
            <table class="ls-table">
                <thead>
                    <tr>
                        <th>{Lang::T('Invoice')}</th>
                        <th>{Lang::T('Customer')}</th>
                        <th>{Lang::T('Plan Name')}</th>
                        <th class="ls-num">{Lang::T('Plan Price')}</th>
                        <th>{Lang::T('Created On')}</th>
                        <th>{Lang::T('Expires On')}</th>
                        <th>{Lang::T('Method')}</th>
                        <th>{Lang::T('Routers')}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $activation as $ds}
                    <tr>
                        <td data-label="{Lang::T('Invoice')}"><a class="ls-strong" href="{Text::url('')}plan/view/{$ds['id']}">{$ds['invoice']|escape}</a></td>
                        <td data-label="{Lang::T('Customer')}" class="rp-cust">
                            <a href="{Text::url('')}customers/viewu/{$ds['username']|escape:'url'}">{$ds['username']|escape}</a>
                            {if $ds['fullname']}<small class="ls-dim">{$ds['fullname']|escape}</small>{/if}
                        </td>
                        <td data-label="{Lang::T('Plan Name')}">{$ds['plan_name']|escape} <em class="ls-chip">{$ds['type']|escape}</em></td>
                        <td data-label="{Lang::T('Plan Price')}" class="ls-num">{Lang::moneyFormat($ds['price'])}</td>
                        <td data-label="{Lang::T('Created On')}" class="ls-when">{Lang::dateAndTimeFormat($ds['recharged_on'],$ds['recharged_time'])}</td>
                        <td data-label="{Lang::T('Expires On')}" class="ls-when">
                            {Lang::dateAndTimeFormat($ds['expiration'],$ds['time'])}
                            {if $ds['type'] != 'Balance'}
                            {if $ds['expiration'] >= $today}<span class="ls-pill ls-pill--active">{Lang::T('Active')}</span>{else}<span class="ls-pill">{Lang::T('Expired')}</span>{/if}
                            {/if}
                        </td>
                        <td data-label="{Lang::T('Method')}" class="ls-dim">{$ds['method']|escape}</td>
                        <td data-label="{Lang::T('Routers')}" class="ls-dim">{$ds['routers']|escape}</td>
                    </tr>
                    {foreachelse}
                    <tr class="ls-none"><td colspan="8">
                        <div class="ls-empty"><b>{Lang::T('No activations found')}</b>{if $q}{Lang::T('No invoice starts with')} &ldquo;{$q|escape}&rdquo;.{/if}</div>
                    </td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        <div class="rp-pager">{include file="pagination.tpl"}</div>
    </div>
</div>

{include file="sections/footer.tpl"}
