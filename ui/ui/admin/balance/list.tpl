{include file="sections/header.tpl"}

<div class="ls">
    <div class="ls-top">
        <div></div>
        <div class="ls-actions">
            <a href="{Text::url('services/balance-add')}" class="ls-btn ls-btn--primary"><i class="fa fa-plus" aria-hidden="true"></i> {Lang::T('New Service Package')}</a>
        </div>
    </div>

    <div class="ls-card">
        <form id="site-search" class="ls-bar" method="post" action="{Text::url('services/balance/')}">
            <div class="ls-search">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="search" autocomplete="off" name="name" placeholder="{Lang::T('Search by Name')}..." aria-label="{Lang::T('Search by Name')}">
            </div>
            <button class="ls-btn ls-btn--primary" type="submit"><i class="fa fa-search" aria-hidden="true"></i> {Lang::T('Search')}</button>
            <a class="ls-btn" title="{Lang::T('Clear Search Query')}" href="{Text::url('services/balance/')}"><i class="fa fa-undo" aria-hidden="true"></i> {Lang::T('Reset')}</a>
        </form>

        <div class="ls-scroll">
            <table class="ls-table table-with-loader">
                <thead>
                    <tr>
                        <th>{Lang::T('Package Name')}</th>
                        <th class="ls-num">{Lang::T('Package Price')}</th>
                        <th class="ls-end">{Lang::T('Manage')}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $d as $ds}
                    <tr>
                        <td data-label="{Lang::T('Package Name')}"><b class="ls-strong">{$ds['name_plan']|escape}</b>{if $ds['enabled'] != 1} <span class="ls-pill ls-pill--banned">{Lang::T('Disabled')}</span>{/if}</td>
                        <td data-label="{Lang::T('Package Price')}" class="ls-num">{Lang::moneyFormat($ds['price'])}{if !empty($ds['price_old'])} <small class="ls-dim" style="text-decoration: line-through">{Lang::moneyFormat($ds['price_old'])}</small>{/if}</td>
                        <td class="ls-end"><div class="ls-row-actions"><a href="{Text::url('services/balance-edit/', $ds['id'])}" class="ls-btn ls-btn--sm">{Lang::T('Edit')}</a><a href="{Text::url('services/balance-delete/', $ds['id'])}" class="ls-btn ls-btn--sm ls-btn--icon ls-btn--danger" aria-label="{Lang::T('Delete')}" onclick="return ask(this, '{Lang::T('Delete')}?')"><i class="fa fa-trash" aria-hidden="true"></i></a></div></td>
                    </tr>
                    {foreachelse}
                    <tr class="ls-none"><td colspan="3">
                        <div class="ls-empty"><b>{Lang::T('No plans found')}</b>
                            <a href="{Text::url('services/balance-add')}">{Lang::T('New Service Package')}</a>
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
