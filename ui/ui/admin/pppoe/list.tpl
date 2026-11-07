{include file="sections/header.tpl"}

<div class="ls">
    <div class="ls-top">
        <div></div>
        <div class="ls-actions">
            <a class="ls-btn" title="{Lang::T('Sync')}" href="{Text::url('services/sync/pppoe')}"
                onclick="return ask(this, '{Lang::T('This will sync/send PPPOE plan to Mikrotik')}?')"><i class="fa fa-refresh" aria-hidden="true"></i> {Lang::T('Sync')}</a>
            <a href="{Text::url('services/pppoe-add')}" class="ls-btn ls-btn--primary" title="{Lang::T('New Service Plan')}"><i class="fa fa-plus" aria-hidden="true"></i> {Lang::T('Add')}</a>
        </div>
    </div>

    <div class="ls-card">
        <form id="site-search" class="ls-bar" method="post" action="{Text::url('services/pppoe')}">
            <div class="ls-search">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="search" autocomplete="off" name="name" placeholder="{Lang::T('Search by Name')}..." aria-label="{Lang::T('Search by Name')}">
            </div>
            <label class="ls-field"><span>{Lang::T('Prepaid')}</span>
                <select id="type1" name="type1">
                    <option value="">{Lang::T('Prepaid')} &amp; {Lang::T('Postpaid')}</option>
                    <option value="yes" {if $type1 eq 'yes' }selected{/if}>{Lang::T('Prepaid')}</option>
                    <option value="no" {if $type1 eq 'no' }selected{/if}>{Lang::T('Postpaid')}</option>
                </select>
            </label>
            <label class="ls-field"><span>{Lang::T('Type')}</span>
                <select id="type2" name="type2">
                    <option value="">{Lang::T('All')}</option>
                    {foreach $type2s as $t}
                    <option value="{$t|escape}" {if $type2 eq $t }selected{/if}>{Lang::T($t)}</option>
                    {/foreach}
                </select>
            </label>
            <label class="ls-field"><span>{Lang::T('Bandwidth')}</span>
                <select id="bandwidth" name="bandwidth">
                    <option value="">{Lang::T('All')}</option>
                    {foreach $bws as $b}
                    <option value="{$b['id']}" {if $bandwidth eq $b['id'] }selected{/if}>{$b['name_bw']|escape}</option>
                    {/foreach}
                </select>
            </label>
            <label class="ls-field"><span>{Lang::T('Category')}</span>
                <select id="type3" name="type3">
                    <option value="">{Lang::T('All')}</option>
                    {foreach $type3s as $t}
                    <option value="{$t|escape}" {if $type3 eq $t }selected{/if}>{$t|escape}</option>
                    {/foreach}
                </select>
            </label>
            <label class="ls-field"><span>{Lang::T('Validity')}</span>
                <select id="valid" name="valid">
                    <option value="">{Lang::T('All')}</option>
                    {foreach $valids as $v}
                    <option value="{$v|escape}" {if $valid eq $v }selected{/if}>{$v|escape}</option>
                    {/foreach}
                </select>
            </label>
            <label class="ls-field"><span>{Lang::T('Location')}</span>
                <select id="router" name="router">
                    <option value="">{Lang::T('All')}</option>
                    {foreach $routers as $r}
                    <option value="{$r|escape}" {if $router eq $r }selected{/if}>{$r|escape}</option>
                    {/foreach}
                    <option value="radius" {if $router eq 'radius' }selected{/if}>Radius</option>
                </select>
            </label>
            <label class="ls-field"><span>{Lang::T('Device')}</span>
                <select id="device" name="device">
                    <option value="">{Lang::T('All')}</option>
                    {foreach $devices as $r}
                    <option value="{$r|escape}" {if $device eq $r }selected{/if}>{$r|escape}</option>
                    {/foreach}
                </select>
            </label>
            <label class="ls-field"><span>{Lang::T('Status')}</span>
                <select id="status" name="status">
                    <option value="-">{Lang::T('All')}</option>
                    <option value="1" {if $status eq '1' }selected{/if}>{Lang::T('Enabled')}</option>
                    <option value="0" {if $status eq '0' }selected{/if}>{Lang::T('Disable')}</option>
                </select>
            </label>
            <button class="ls-btn ls-btn--primary" type="submit"><i class="fa fa-search" aria-hidden="true"></i> {Lang::T('Search')}</button>
            <a class="ls-btn" title="{Lang::T('Clear Search Query')}" href="{Text::url('services/pppoe')}"><i class="fa fa-undo" aria-hidden="true"></i> {Lang::T('Reset')}</a>
        </form>

        <div class="ls-scroll">
            <table class="ls-table table-with-loader">
                <thead>
                    <tr>
                        <th>{Lang::T('Name')}</th>
                        <th>{Lang::T('Type')}</th>
                        <th><a href="{Text::url('bandwidth/list')}">{Lang::T('Bandwidth')}</a></th>
                        <th class="ls-num">{Lang::T('Price')}</th>
                        <th>{Lang::T('Validity')}</th>
                        <th><a href="{Text::url('pool/list')}">{Lang::T('IP Pool')}</a></th>
                        <th>{Lang::T('Internet Plan')}</th>
                        <th>{Lang::T('Date')}</th>
                        <th><a href="{Text::url('routers/list')}">{Lang::T('Location')}</a></th>
                        <th>{Lang::T('Device')}</th>
                        <th>{Lang::T('ID')}</th>
                        <th class="ls-end">{Lang::T('Manage')}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $d as $ds}
                    <tr>
                        <td data-label="{Lang::T('Name')}"><b class="ls-strong">{$ds['name_plan']|escape}</b>{if $ds['enabled'] != 1} <span class="ls-pill ls-pill--banned">{Lang::T('Disabled')}</span>{/if}</td>
                        <td data-label="{Lang::T('Type')}">{$ds['plan_type']|escape} {if $ds['prepaid'] != 'yes'}<em class="ls-chip">{Lang::T('Postpaid')}</em>{else}<span class="ls-dim">{Lang::T('Prepaid')}</span>{/if}</td>
                        <td data-label="{Lang::T('Bandwidth')}">{$ds['name_bw']|escape}</td>
                        <td data-label="{Lang::T('Price')}" class="ls-num">{Lang::moneyFormat($ds['price'])}{if !empty($ds['price_old'])} <small class="ls-dim" style="text-decoration: line-through">{Lang::moneyFormat($ds['price_old'])}</small>{/if}</td>
                        <td data-label="{Lang::T('Validity')}" class="ls-when">{$ds['validity']} {$ds['validity_unit']}</td>
                        <td data-label="{Lang::T('IP Pool')}">{$ds['pool']|escape}</td>
                        <td data-label="{Lang::T('Internet Plan')}">{if $ds['plan_expired']}<a href="{Text::url('services/pppoe-edit/', $ds['plan_expired'])}">{Lang::T('Yes')}</a>{else}<span class="ls-dim">{Lang::T('No')}</span>{/if}</td>
                        <td data-label="{Lang::T('Date')}" class="ls-dim ls-when">{if $ds['prepaid'] == 'no'}{$ds['expired_date']}{/if}</td>
                        <td data-label="{Lang::T('Location')}">{if $ds['is_radius']}<span class="ls-pill">RADIUS</span>{elseif $ds['routers'] != ''}<a href="{Text::url('routers/edit/0&name=')}{$ds['routers']|escape:'url'}">{$ds['routers']|escape}</a>{/if}</td>
                        <td data-label="{Lang::T('Device')}">{$ds['device']|escape}</td>
                        <td data-label="ID" class="ls-dim">{$ds['id']}</td>
                        <td class="ls-end"><div class="ls-row-actions"><a href="{Text::url('services/pppoe-edit/', $ds['id'])}" class="ls-btn ls-btn--sm">{Lang::T('Edit')}</a><a href="{Text::url('services/pppoe-delete/', $ds['id'])}" class="ls-btn ls-btn--sm ls-btn--icon ls-btn--danger" aria-label="{Lang::T('Delete')}" onclick="return ask(this, '{Lang::T('Delete')}?')"><i class="fa fa-trash" aria-hidden="true"></i></a></div></td>
                    </tr>
                    {foreachelse}
                    <tr class="ls-none"><td colspan="12">
                        <div class="ls-empty"><b>{Lang::T('No plans found')}</b>
                            <a href="{Text::url('services/pppoe-add')}">{Lang::T('Add')}</a>
                        </div>
                    </td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        {include file="pagination.tpl"}
    </div>

    <div class="bs-callout bs-callout-info" id="callout-navbar-role">
        <h4>{Lang::T('Create expired Internet Plan')}</h4>
        <p>{Lang::T('When customer expired, you can move it to Expired Internet Plan')}</p>
    </div>
</div>

{include file="sections/footer.tpl"}
