{include file="sections/header.tpl"}

<div class="ls">
    <div class="ls-top">
        <div></div>
        <div class="ls-actions">
            {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}<a class="ls-btn" title="{Lang::T('This will export to CSV')}" href="{Text::url('logs/radius-csv')}" onclick="return ask(this, '{Lang::T('This will export to CSV')}?')"><i class="fa fa-download" aria-hidden="true"></i> CSV</a>{/if}
        </div>
    </div>

    <div class="ls-card">
        <form id="site-search" class="ls-bar" method="post" action="{Text::url('logs/radius/')}">
            <div class="ls-search">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="search" autocomplete="off" name="q" value="{$q|escape}" placeholder="{Lang::T('Search by Name')}..." aria-label="{Lang::T('Search by Name')}">
            </div>
            <button class="ls-btn ls-btn--primary" type="submit"><i class="fa fa-search" aria-hidden="true"></i> {Lang::T('Search')}</button>
            <a class="ls-btn" title="{Lang::T('Clear Search Query')}" href="{Text::url('logs/radius/')}"><i class="fa fa-undo" aria-hidden="true"></i> {Lang::T('Reset')}</a>
        </form>

        <form class="ls-bar" method="post" action="{Text::url('logs/radius/')}">
            <label class="ls-field"><span>{Lang::T('Keep Logs')}</span>
                <input type="hidden" name="csrf_token" value="{$csrf_token}">
                <input type="text" name="keep" value="90" inputmode="numeric" size="4" aria-label="{Lang::T('Keep Logs')}">
                <span>{Lang::T('Days')}</span>
            </label>
            <button type="button" class="ls-btn ls-btn--danger" onclick="return ask(this, '{Lang::T('Clear old logs?')}')"><i class="fa fa-trash" aria-hidden="true"></i> {Lang::T('Clean Logs')}</button>
        </form>

        <div class="ls-scroll">
            <table class="ls-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>{Lang::T('Date')}</th>
                        <th>{Lang::T('Username')}</th>
                        <th>{Lang::T('Password')}</th>
                        <th>Reply</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $d as $ds}
                    <tr>
                        <td data-label="ID" class="ls-dim">{$ds['id']}</td>
                        <td data-label="{Lang::T('Date')}" class="ls-dim ls-when">{Lang::dateTimeFormat($ds['authdate'])}</td>
                        <td data-label="{Lang::T('Username')}">{$ds['username']|escape}</td>
                        <td data-label="{Lang::T('Password')}"><span class="ls-secret" tabindex="0">{$ds['pass']|escape}</span></td>
                        <td data-label="Reply">{$ds['reply']|escape}</td>
                    </tr>
                    {foreachelse}
                    <tr class="ls-none"><td colspan="5">
                        <div class="ls-empty"><b>{Lang::T('No logs found')}</b>
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
