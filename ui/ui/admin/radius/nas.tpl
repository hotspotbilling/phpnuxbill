{include file="sections/header.tpl"}

<div class="ls">
    <div class="ls-top">
        <div></div>
        <div class="ls-actions">
            <a href="{Text::url('')}radius/nas-add" class="ls-btn ls-btn--primary"><i class="fa fa-plus" aria-hidden="true"></i> {Lang::T('New NAS')}</a>
        </div>
    </div>

    <div class="ls-card">
        <form id="site-search" class="ls-bar" method="post" action="{Text::url('radius/nas-list')}">
            <div class="ls-search">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="search" autocomplete="off" name="name" value="{$name|escape}" placeholder="{Lang::T('Search by Name')}..." aria-label="{Lang::T('Search by Name')}">
            </div>
            <button class="ls-btn ls-btn--primary" type="submit"><i class="fa fa-search" aria-hidden="true"></i> {Lang::T('Search')}</button>
            <a class="ls-btn" title="{Lang::T('Clear Search Query')}" href="{Text::url('radius/nas-list')}"><i class="fa fa-undo" aria-hidden="true"></i> {Lang::T('Reset')}</a>
        </form>

        <div class="ls-scroll">
            <table class="ls-table">
                <thead>
                    <tr>
                        <th>{Lang::T('Name')}</th>
                        <th>IP</th>
                        <th>{Lang::T('Type')}</th>
                        <th>Port</th>
                        <th>Server</th>
                        <th>Community</th>
                        <th>Routers</th>
                        <th>ID</th>
                        <th class="ls-end">{Lang::T('Manage')}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $nas as $ds}
                    <tr>
                        <td data-label="{Lang::T('Name')}"><b class="ls-strong">{$ds['shortname']|escape}</b></td>
                        <td data-label="IP">{$ds['nasname']|escape}</td>
                        <td data-label="{Lang::T('Type')}">{$ds['type']|escape}</td>
                        <td data-label="Port">{$ds['ports']|escape}</td>
                        <td data-label="Server">{$ds['server']|escape}</td>
                        <td data-label="Community">{$ds['community']|escape}</td>
                        <td data-label="Routers">{$ds['routers']|escape}</td>
                        <td data-label="ID" class="ls-dim">{$ds['id']}</td>
                        <td class="ls-end"><div class="ls-row-actions"><a href="{Text::url('radius/nas-edit/', $ds['id'])}" class="ls-btn ls-btn--sm">{Lang::T('Edit')}</a><a href="{Text::url('radius/nas-delete/', $ds['id'])}" class="ls-btn ls-btn--sm ls-btn--icon ls-btn--danger" aria-label="{Lang::T('Delete')}" onclick="return ask(this, '{Lang::T('Delete')}?')"><i class="fa fa-trash" aria-hidden="true"></i></a></div></td>
                    </tr>
                    {foreachelse}
                    <tr class="ls-none"><td colspan="9">
                        <div class="ls-empty"><b>{Lang::T('No NAS found')}</b>
                            <a href="{Text::url('radius/nas-add')}">{Lang::T('New NAS')}</a>
                        </div>
                    </td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        {include file="pagination.tpl"}
    </div>

    <div class="bs-callout bs-callout-info" id="callout-navbar-role">
        <h4>RADIUS REST</h4>
        <p>{Lang::T("For Radius REST, you don't need to add NAS, NAS need to add to client.conf manually")}</p>
    </div>
</div>

{include file="sections/footer.tpl"}
