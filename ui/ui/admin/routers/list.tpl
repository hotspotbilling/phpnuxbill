{include file="sections/header.tpl"}

<div class="ls">
    <div class="ls-top">
        <div></div>
        <div class="ls-actions">
            <a class="ls-btn" title="{Lang::T('Maps')}" href="{Text::url('')}routers/maps"><i class="fa fa-map-marker" aria-hidden="true"></i> {Lang::T('Maps')}</a>
            <a href="{Text::url('')}routers/add" class="ls-btn ls-btn--primary"><i class="fa fa-plus" aria-hidden="true"></i> {Lang::T('New Router')}</a>
        </div>
    </div>

    <div class="ls-card">
        <form id="site-search" class="ls-bar" method="post" action="{Text::url('routers/list/')}">
            <div class="ls-search">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="search" autocomplete="off" name="name" placeholder="{Lang::T('Search by Name')}..." aria-label="{Lang::T('Search by Name')}">
            </div>
            <button class="ls-btn ls-btn--primary" type="submit"><i class="fa fa-search" aria-hidden="true"></i> {Lang::T('Search')}</button>
            <a class="ls-btn" title="{Lang::T('Clear Search Query')}" href="{Text::url('routers/list/')}"><i class="fa fa-undo" aria-hidden="true"></i> {Lang::T('Reset')}</a>
        </form>

        <div class="ls-scroll">
            <table class="ls-table">
                <thead>
                    <tr>
                        <th>{Lang::T('Router Name')}</th>
                        <th>{Lang::T('IP Address')}</th>
                        <th>{Lang::T('Username')}</th>
                        <th>{Lang::T('Description')}</th>
                        {if $_c['router_check']}<th>{Lang::T('Online Status')}</th><th>{Lang::T('Last Seen')}</th>{/if}
                        <th>{Lang::T('Status')}</th>
                        <th>ID</th>
                        <th class="ls-end">{Lang::T('Manage')}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $d as $ds}
                    <tr>
                        <td data-label="{Lang::T('Router Name')}">{if $ds['coordinates']}<a href="https://www.google.com/maps/dir//{$ds['coordinates']|escape:'url'}/" target="_blank" rel="noopener" title="{$ds['coordinates']|escape}"><i class="fa fa-map-marker" aria-hidden="true"></i></a> {/if}<b class="ls-strong">{$ds['name']|escape}</b></td>
                        <td data-label="{Lang::T('IP Address')}"><span class="ls-secret" tabindex="0">{$ds['ip_address']|escape}</span></td>
                        <td data-label="{Lang::T('Username')}"><span class="ls-secret" tabindex="0">{$ds['username']|escape}</span></td>
                        <td data-label="{Lang::T('Description')}" class="ls-dim">{$ds['description']|escape}</td>
                        {if $_c['router_check']}<td data-label="{Lang::T('Online Status')}"><span class="ls-pill {if $ds['status'] == 'Online'}ls-pill--active{else}ls-pill--banned{/if}">{if $ds['status'] == 'Online'}{Lang::T('Online')}{else}{Lang::T('Offline')}{/if}</span></td><td data-label="{Lang::T('Last Seen')}" class="ls-dim ls-when">{$ds['last_seen']|escape}</td>{/if}
                        <td data-label="{Lang::T('Status')}">{if $ds['enabled'] == 1}<span class="ls-pill ls-pill--active">{Lang::T('Enabled')}</span>{else}<span class="ls-pill ls-pill--banned">{Lang::T('Disabled')}</span>{/if}</td>
                        <td data-label="ID" class="ls-dim">{$ds['id']}</td>
                        <td class="ls-end"><div class="ls-row-actions"><a href="{Text::url('routers/edit/', $ds['id'])}" class="ls-btn ls-btn--sm">{Lang::T('Edit')}</a><a href="{Text::url('routers/delete/', $ds['id'])}" class="ls-btn ls-btn--sm ls-btn--icon ls-btn--danger" aria-label="{Lang::T('Delete')}" onclick="return ask(this, '{Lang::T('Delete')}?')"><i class="fa fa-trash" aria-hidden="true"></i></a></div></td>
                    </tr>
                    {foreachelse}
                    <tr class="ls-none"><td colspan="9">
                        <div class="ls-empty"><b>{Lang::T('No routers found')}</b>
                            <a href="{Text::url('routers/add')}">{Lang::T('New Router')}</a>
                        </div>
                    </td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        {include file="pagination.tpl"}
    </div>

    <div class="bs-callout bs-callout-info" id="callout-navbar-role">
        <h4>{Lang::T('Check if Router Online?')}</h4>
        <p>{Lang::T('To check whether the Router is online or not, please visit the following page')} <a href="{Text::url('')}settings/miscellaneous#router_check" target="_blank" class="btn btn-link">{Lang::T('Cek Now')}</a></p>
    </div>
</div>

{include file="sections/footer.tpl"}
