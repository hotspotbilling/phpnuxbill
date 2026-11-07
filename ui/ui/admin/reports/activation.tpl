{include file="sections/header.tpl"}

<div class="ls">

    <div class="ls-card">
        <form id="site-search" class="ls-bar" method="post" action="{Text::url('reports/activation')}">
            <div class="ls-search">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="search" autocomplete="off" name="q" value="{$q|escape}" placeholder="{Lang::T('Invoice')}..." aria-label="{Lang::T('Invoice')}...">
            </div>
            <button class="ls-btn ls-btn--primary" type="submit"><i class="fa fa-search" aria-hidden="true"></i> {Lang::T('Search')}</button>
            <a class="ls-btn" title="{Lang::T('Clear Search Query')}" href="{Text::url('reports/activation')}"><i class="fa fa-undo" aria-hidden="true"></i> {Lang::T('Reset')}</a>
        </form>

        <div class="ls-scroll">
            <table class="ls-table">
                <thead>
                    <tr>
                        <th>{Lang::T('Invoice')}</th>
                        <th>{Lang::T('Username')}</th>
                        <th>{Lang::T('Fullname')}</th>
                        <th>{Lang::T('Plan Name')}</th>
                        <th class="ls-num">{Lang::T('Plan Price')}</th>
                        <th>{Lang::T('Type')}</th>
                        <th>{Lang::T('Created On')}</th>
                        <th>{Lang::T('Expires On')}</th>
                        <th>{Lang::T('Method')}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $activation as $ds}
                    <tr>
                        <td data-label="{Lang::T('Invoice')}"><a class="ls-strong" href="{Text::url('')}plan/view/{$ds['id']}">{$ds['invoice']|escape}</a></td>
                        <td data-label="{Lang::T('Username')}"><a href="{Text::url('')}customers/viewu/{$ds['username']|escape:'url'}">{$ds['username']|escape}</a></td>
                        <td data-label="{Lang::T('Fullname')}">{$ds['fullname']|escape}</td>
                        <td data-label="{Lang::T('Plan Name')}">{$ds['plan_name']|escape}</td>
                        <td data-label="{Lang::T('Plan Price')}" class="ls-num">{Lang::moneyFormat($ds['price'])}</td>
                        <td data-label="{Lang::T('Type')}"><em class="ls-chip">{$ds['type']|escape}</em></td>
                        <td data-label="{Lang::T('Created On')}" class="ls-when text-success">{Lang::dateAndTimeFormat($ds['recharged_on'],$ds['recharged_time'])}</td>
                        <td data-label="{Lang::T('Expires On')}" class="ls-when text-danger">{Lang::dateAndTimeFormat($ds['expiration'],$ds['time'])}</td>
                        <td data-label="{Lang::T('Method')}" class="ls-dim">{$ds['method']|escape}</td>
                    </tr>
                    {foreachelse}
                    <tr class="ls-none"><td colspan="9">
                        <div class="ls-empty"><b>{Lang::T('No activations found')}</b>
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
