{include file="sections/header.tpl"}

<div class="ls">
    <div class="ls-top">
        <div></div>
        <div class="ls-actions">
            {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}<a class="ls-btn" title="{Lang::T('This will export to CSV')}" href="{Text::url('logs/message-csv')}" onclick="return ask(this, '{Lang::T('This will export to CSV')}?')"><i class="fa fa-download" aria-hidden="true"></i> CSV</a>{/if}
        </div>
    </div>

    <div class="ls-card">
        <form id="site-search" class="ls-bar" method="post" action="{Text::url('logs/message/')}">
            <div class="ls-search">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="search" autocomplete="off" name="q" value="{$q|escape}" placeholder="{Lang::T('Search')}..." aria-label="{Lang::T('Search')}...">
            </div>
            <button class="ls-btn ls-btn--primary" type="submit"><i class="fa fa-search" aria-hidden="true"></i> {Lang::T('Search')}</button>
            <a class="ls-btn" title="{Lang::T('Clear Search Query')}" href="{Text::url('logs/message/')}"><i class="fa fa-undo" aria-hidden="true"></i> {Lang::T('Reset')}</a>
        </form>

        <form class="ls-bar" method="post" action="{Text::url('logs/message/')}">
            <label class="ls-field"><span>{Lang::T('Keep Logs')}</span>
                <input type="hidden" name="csrf_token" value="{$csrf_token}">
                <input type="text" name="keep" value="90" inputmode="numeric" size="4" aria-label="{Lang::T('Keep Logs')}">
                <span>{Lang::T('Days')}</span>
            </label>
            <button type="button" class="ls-btn ls-btn--danger" onclick="return ask(this, '{Lang::T('Clear old logs?')}')"><i class="fa fa-trash" aria-hidden="true"></i> {Lang::T('Clean up Logs')}</button>
        </form>

        <div class="ls-scroll">
            <table class="ls-table">
                <thead>
                    <tr>
                        <th>{Lang::T('ID')}</th>
                        <th>{Lang::T('Date Sent')}</th>
                        <th>{Lang::T('Recipient')}</th>
                        <th>{Lang::T('Type')}</th>
                        <th>{Lang::T('Status')}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $d as $ds}
                    <tr>
                        <td data-label="{Lang::T('ID')}" class="ls-dim">{$ds['id']}</td>
                        <td data-label="{Lang::T('Date Sent')}" class="ls-dim ls-when">{Lang::dateTimeFormat($ds['sent_at'])}</td>
                        <td data-label="{Lang::T('Recipient')}">{if $ds['recipient']}{$ds['recipient']|escape}{else}<span class="ls-dim">&ndash;</span>{/if}</td>
                        <td data-label="{Lang::T('Type')}"><em class="ls-chip">{$ds['message_type']|escape}</em></td>
                        <td data-label="{Lang::T('Status')}"><span class="ls-pill {if $ds['status'] == 'Success'}ls-pill--active{else}ls-pill--banned{/if}">{$ds['status']|escape}</span></td>
                    </tr>
                    {if $ds['message_content']}<tr class="ls-subrow"><td colspan="5">{$ds['message_content']|escape|nl2br}</td></tr>{/if}
                    {if $ds['error_message']}<tr class="ls-subrow ls-subrow--error"><td colspan="5">{$ds['error_message']|escape|nl2br}</td></tr>{/if}
                    {foreachelse}
                    <tr class="ls-none"><td colspan="5">
                        <div class="ls-empty"><b>{Lang::T('No logs found.')}</b>
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
