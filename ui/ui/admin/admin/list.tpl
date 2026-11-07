{include file="sections/header.tpl"}

<div class="ls">
    <div class="ls-top">
        <div></div>
        <div class="ls-actions">
            <a href="{Text::url('settings/users-add')}" class="ls-btn ls-btn--primary"><i class="fa fa-plus" aria-hidden="true"></i> {Lang::T('Add New Administrator')}</a>
        </div>
    </div>

    <div class="ls-card">
        <form id="site-search" class="ls-bar" method="post" action="{Text::url('settings/users/')}">
            <input type="hidden" name="csrf_token" value="{$csrf_token}">
            <div class="ls-search">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="search" autocomplete="off" name="search" value="{$search|escape}" placeholder="{Lang::T('Search by Username')}..." aria-label="{Lang::T('Search by Username')}...">
            </div>
            <button class="ls-btn ls-btn--primary" type="submit"><i class="fa fa-search" aria-hidden="true"></i> {Lang::T('Search')}</button>
            <a class="ls-btn" title="{Lang::T('Clear Search Query')}" href="{Text::url('settings/users/')}"><i class="fa fa-undo" aria-hidden="true"></i> {Lang::T('Reset')}</a>
        </form>

        <div class="ls-scroll">
            <table class="ls-table">
                <thead>
                    <tr>
                        <th>{Lang::T('Username')}</th>
                        <th>{Lang::T('Full Name')}</th>
                        <th>{Lang::T('Phone')}</th>
                        <th>{Lang::T('Email')}</th>
                        <th>{Lang::T('Type')}</th>
                        <th>{Lang::T('Location')}</th>
                        <th>{Lang::T('Agent')}</th>
                        <th>{Lang::T('Last Login')}</th>
                        <th>ID</th>
                        <th class="ls-end">{Lang::T('Manage')}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $d as $ds}
                    <tr>
                        <td data-label="{Lang::T('Username')}"><b class="ls-strong">{$ds['username']|escape}</b>{if $ds['status'] != 'Active'} <span class="ls-pill ls-pill--banned">{Lang::T($ds['status'])}</span>{/if}</td>
                        <td data-label="{Lang::T('Full Name')}">{$ds['fullname']|escape}</td>
                        <td data-label="{Lang::T('Phone')}">{$ds['phone']|escape}</td>
                        <td data-label="{Lang::T('Email')}">{$ds['email']|escape}</td>
                        <td data-label="{Lang::T('Type')}"><em class="ls-chip">{$ds['user_type']|escape}</em></td>
                        <td data-label="{Lang::T('Location')}" class="ls-dim">{$ds['city']|escape}, {$ds['subdistrict']|escape}, {$ds['ward']|escape}</td>
                        <td data-label="{Lang::T('Agent')}">{if $ds['root']}<a href="{Text::url('settings/users-view/', $ds['root'])}">{$admins[$ds['root']]|escape}</a>{/if}</td>
                        <td data-label="{Lang::T('Last Login')}" class="ls-dim ls-when">{if $ds['last_login']}{Lang::timeElapsed($ds['last_login'])}{/if}</td>
                        <td data-label="ID" class="ls-dim">{$ds['id']}</td>
                        <td class="ls-end"><div class="ls-row-actions"><a href="{Text::url('settings/users-view/', $ds['id'])}" class="ls-btn ls-btn--sm">{Lang::T('View')}</a><a href="{Text::url('settings/users-edit/', $ds['id'])}" class="ls-btn ls-btn--sm">{Lang::T('Edit')}</a>{if ($_admin['id']) neq ($ds['id'])}<a href="{Text::url('settings/users-delete/', $ds['id'])}" class="ls-btn ls-btn--sm ls-btn--icon ls-btn--danger" aria-label="{Lang::T('Delete')}" onclick="return ask(this, '{Lang::T('Delete')}?')"><i class="fa fa-trash" aria-hidden="true"></i></a>{/if}</div></td>
                    </tr>
                    {foreachelse}
                    <tr class="ls-none"><td colspan="10">
                        <div class="ls-empty"><b>{Lang::T('No administrators found')}</b>
                            <a href="{Text::url('settings/users-add')}">{Lang::T('Add New Administrator')}</a>
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
