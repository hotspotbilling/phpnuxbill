{include file="sections/header.tpl"}

<div class="ls">
    <div class="ls-top">
        <p class="ls-sub">{$counts['on']|number_format} {Lang::T('Active Customers')}{if $counts['off'] > 0}, {$counts['off']|number_format} {Lang::T('Expired')}{/if}</p>
        <div class="ls-actions">
            {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
            <a class="ls-btn" title="{Lang::T('Sync')}" href="{Text::url('')}plan/sync"
                onclick="return ask(this, '{Lang::T('This will sync dan send Customer active package to Mikrotik')}?')"><i class="fa fa-refresh" aria-hidden="true"></i> {Lang::T('Sync')}</a>
            {/if}
            <a href="{Text::url('')}plan/recharge" class="ls-btn ls-btn--primary"><i class="fa fa-plus" aria-hidden="true"></i> {Lang::T('Recharge Account')}</a>
        </div>
    </div>

    <div class="ls-tabs" role="tablist" aria-label="{Lang::T('Status')}">
        {$tabUrl = "&search={$search|escape:'url'}&router={$router|escape:'url'}&plan={$plan|escape:'url'}&status="}
        <a class="ls-tab{if $status eq '' || $status eq '-'} is-on{/if}" href="{Text::url('plan/list', $tabUrl, '-')}" role="tab">{Lang::T('All')} <small>{($counts['on'] + $counts['off'])|number_format}</small></a>
        <a class="ls-tab{if $status eq 'on'} is-on{/if}" href="{Text::url('plan/list', $tabUrl, 'on')}" role="tab">{Lang::T('Active')} <small>{$counts['on']|number_format}</small></a>
        <a class="ls-tab{if $status eq 'off'} is-on{/if}" href="{Text::url('plan/list', $tabUrl, 'off')}" role="tab">{Lang::T('Expired')} <small>{$counts['off']|number_format}</small></a>
    </div>

    <div class="ls-card">
        <form id="site-search" class="ls-bar" method="post" action="{Text::url('')}plan/list/">
            <input type="hidden" name="status" value="{$status|escape}">
            <div class="ls-search">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="search" autocomplete="off" name="search" placeholder="{Lang::T('Search')}..." value="{$search|escape}" aria-label="{Lang::T('Search')}">
            </div>
            <label class="ls-field"><span>{Lang::T('Location')}</span>
                <select id="router" name="router" onchange="this.form.submit()">
                    <option value="">{Lang::T('All')}</option>
                    {foreach $routers as $r}
                    <option value="{$r|escape}" {if $router eq $r}selected{/if}>{$r|escape}</option>
                    {/foreach}
                </select>
            </label>
            <label class="ls-field"><span>{Lang::T('Plan Name')}</span>
                <select id="plan" name="plan" onchange="this.form.submit()">
                    <option value="">{Lang::T('All')}</option>
                    {foreach $plans as $p}
                    <option value="{$p['id']}" {if $plan eq $p['id']}selected{/if}>{$p['name_plan']|escape}</option>
                    {/foreach}
                </select>
            </label>
            <button class="ls-btn ls-btn--primary" type="submit"><i class="fa fa-search" aria-hidden="true"></i> {Lang::T('Search')}</button>
            {if $search neq '' || $router neq '' || $plan neq ''}
            <a class="ls-btn" href="{Text::url('')}plan/list" title="{Lang::T('Clear Search Query')}"><i class="fa fa-times" aria-hidden="true"></i></a>
            {/if}
        </form>

        <div class="ls-scroll">
            <table id="datatable" class="ls-table ls-nocheck table-with-loader">
                <thead>
                    <tr>
                        <th>{Lang::T('Username')}</th>
                        <th>{Lang::T('Plan Name')}</th>
                        <th>{Lang::T('Type')}</th>
                        <th>{Lang::T('Created On')}</th>
                        <th>{Lang::T('Expires On')}</th>
                        <th>{Lang::T('Status')}</th>
                        <th>{Lang::T('Method')}</th>
                        <th><a href="{Text::url('')}routers/list">{Lang::T('Location')}</a></th>
                        <th class="ls-end">{Lang::T('Manage')}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $d as $ds}
                    <tr>
                        <td data-label="{Lang::T('Username')}">
                            {if $ds['customer_id'] == '0'}
                            <a class="ls-strong" href="{Text::url('plan/voucher/&search=')}{$ds['username']|escape:'url'}">{$ds['username']|escape}</a>
                            {else}
                            <a class="ls-strong" href="{Text::url('customers/viewu/')}{$ds['username']|escape:'url'}">{$ds['username']|escape}</a>
                            {/if}
                        </td>
                        <td data-label="{Lang::T('Plan Name')}">
                            <span>
                            {if $ds['type'] == 'Hotspot'}
                            <a href="{Text::url('')}services/edit/{$ds['plan_id']}">{$ds['namebp']|escape}</a>
                            <span api-get-text="{Text::url('')}autoload/customer_is_active/{$ds['username']|escape:'url'}/{$ds['plan_id']}"></span>
                            {elseif $ds['type'] == 'PPPOE'}
                            <a href="{Text::url('')}services/pppoe-edit/{$ds['plan_id']}">{$ds['namebp']|escape}</a>
                            <span api-get-text="{Text::url('')}autoload/customer_is_active/{$ds['username']|escape:'url'}/{$ds['plan_id']}"></span>
                            {elseif $ds['type'] == 'VPN'}
                            <a href="{Text::url('')}services/vpn-edit/{$ds['plan_id']}">{$ds['namebp']|escape}</a>
                            {else}{$ds['namebp']|escape}{/if}
                            </span>
                        </td>
                        <td data-label="{Lang::T('Type')}">{$ds['type']|escape}</td>
                        <td data-label="{Lang::T('Created On')}" class="ls-dim ls-when">{Lang::dateAndTimeFormat($ds['recharged_on'],$ds['recharged_time'])}</td>
                        <td data-label="{Lang::T('Expires On')}" class="ls-dim ls-when">{Lang::dateAndTimeFormat($ds['expiration'],$ds['time'])}</td>
                        <td data-label="{Lang::T('Status')}">
                            {if $ds['status'] == 'off'}<span class="ls-pill ls-pill--banned">{Lang::T('Expired')}</span>
                            {else}<span class="ls-pill ls-pill--active">{Lang::T('Active')}</span>{/if}
                        </td>
                        <td data-label="{Lang::T('Method')}" class="ls-dim">{$ds['method']|escape}</td>
                        <td data-label="{Lang::T('Location')}">{$ds['routers']|escape}</td>
                        <td class="ls-end">
                            <div class="ls-row-actions">
                                <a href="{Text::url('')}plan/edit/{$ds['id']}" class="ls-btn ls-btn--sm">{Lang::T('Edit')}</a>
                                {if $ds['status']=='off' && $_c['extend_expired']}
                                <a href="javascript:extend('{$ds['id']}')" class="ls-btn ls-btn--sm ls-btn--primary">{Lang::T('Extend')}</a>
                                {/if}
                                {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                                <a href="{Text::url('')}plan/delete/{$ds['id']}" id="{$ds['id']}" title="{Lang::T('Delete')}" aria-label="{Lang::T('Delete')}"
                                    onclick="return ask(this, '{Lang::T('Delete')}?')" class="ls-btn ls-btn--sm ls-btn--icon ls-btn--danger"><i class="fa fa-trash" aria-hidden="true"></i></a>
                                {/if}
                            </div>
                        </td>
                    </tr>
                    {foreachelse}
                    <tr class="ls-none"><td colspan="9">
                        <div class="ls-empty"><b>{Lang::T('No customers found')}</b>
                            <a href="{Text::url('plan/recharge')}">{Lang::T('Recharge Account')}</a></div>
                    </td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        {include file="pagination.tpl"}
    </div>
</div>

<script>
    function extend(idP) {
        var res = prompt("Extend for many days?", "3");
        if (res) {
            if (confirm("Extend for " + res + " days?")) {
                window.location.href = "{Text::url('plan/extend/')}" + idP + "/" + res + "{Text::isQA('? or &')}stoken={App::getToken()}";
            }
        }
    }
</script>

{include file="sections/footer.tpl"}
