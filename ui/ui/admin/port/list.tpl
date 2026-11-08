{include file="sections/header.tpl"}

<div class="ls">
    <div class="ls-top">
        <div></div>
        <div class="ls-actions">
            <a class="ls-btn" title="{Lang::T('Sync')}" href="{Text::url('pool/sync')}" onclick="return ask(this, '{Lang::T('This will sync/send IP port to Mikrotik')}?')"><i class="fa fa-refresh" aria-hidden="true"></i> {Lang::T('Sync')}</a>
            <a href="{Text::url('pool/add-port')}" class="ls-btn ls-btn--primary"><i class="fa fa-plus" aria-hidden="true"></i> {Lang::T('New port')}</a>
        </div>
    </div>

    <div class="ls-card">
        <form id="site-search" class="ls-bar" method="post" action="{Text::url('pool/port/')}">
            <div class="ls-search">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="search" autocomplete="off" name="name" placeholder="{Lang::T('Search by Name')}..." aria-label="{Lang::T('Search by Name')}">
            </div>
            <button class="ls-btn ls-btn--primary" type="submit"><i class="fa fa-search" aria-hidden="true"></i> {Lang::T('Search')}</button>
            <a class="ls-btn" title="{Lang::T('Clear Search Query')}" href="{Text::url('pool/port/')}"><i class="fa fa-undo" aria-hidden="true"></i> {Lang::T('Reset')}</a>
        </form>

        <div class="ls-scroll">
            <table class="ls-table">
                <thead>
                    <tr>
                        <th>{Lang::T('Port Name')}</th>
                        <th>{Lang::T('Public IP')}</th>
                        <th>{Lang::T('Range Port')}</th>
                        <th>{Lang::T('Routers')}</th>
                        <th>ID</th>
                        <th class="ls-end">{Lang::T('Manage')}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $d as $ds}
                    <tr>
                        <td data-label="{Lang::T('Port Name')}"><b class="ls-strong">{$ds['port_name']|escape}</b></td>
                        <td data-label="{Lang::T('Public IP')}">{$ds['public_ip']|escape}</td>
                        <td data-label="{Lang::T('Range Port')}">{$ds['range_port']|escape}</td>
                        <td data-label="{Lang::T('Routers')}">{$ds['routers']|escape}</td>
                        <td data-label="ID" class="ls-dim">{$ds['id']}</td>
                        <td class="ls-end"><div class="ls-row-actions"><a href="{Text::url('pool/edit-port/', $ds['id'])}" class="ls-btn ls-btn--sm">{Lang::T('Edit')}</a><a href="{Text::url('pool/delete-port/', $ds['id'])}" class="ls-btn ls-btn--sm ls-btn--icon ls-btn--danger" aria-label="{Lang::T('Delete')}" onclick="return ask(this, '{Lang::T('Delete')}?')"><i class="fa fa-trash" aria-hidden="true"></i></a></div></td>
                    </tr>
                    {foreachelse}
                    <tr class="ls-none"><td colspan="6">
                        <div class="ls-empty"><b>{Lang::T('No ports found')}</b>
                            <a href="{Text::url('pool/add-port')}">{Lang::T('New port')}</a>
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
