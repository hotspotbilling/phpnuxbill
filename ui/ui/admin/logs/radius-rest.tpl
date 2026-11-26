{include file="sections/header.tpl"}
{*
    Logs > Radius REST: what radius.php could not do while it answered FreeRADIUS, read from system/cache/radius_rest.log by
    logs.php "radius-rest". $rows: the newest entries (at, level, message, and when the request had them username, session, nas,
    status); $q, $level: the filters in use. No RADIUS database is needed.
*}
<div class="ls">
    {* filter links, not tabs: they load another page, so no tablist roles; the one in use says so with aria-current *}
    <nav class="ls-tabs" aria-label="{Lang::T('Type')}">
        <a class="ls-tab{if $level eq ''} is-on{/if}" href="{Text::url('logs/radius-rest')}"{if $level eq ''} aria-current="page"{/if}>{Lang::T('All')}</a>
        <a class="ls-tab{if $level eq 'error'} is-on{/if}" href="{Text::url('logs/radius-rest&level=error')}"{if $level eq 'error'} aria-current="page"{/if}>{Lang::T('Errors')}</a>
        <a class="ls-tab{if $level eq 'info'} is-on{/if}" href="{Text::url('logs/radius-rest&level=info')}"{if $level eq 'info'} aria-current="page"{/if}>{Lang::T('Notes')}</a>
    </nav>

    <div class="ls-card">
        <form id="site-search" class="ls-bar" method="get" action="{Text::url('')}">
            <input type="hidden" name="_route" value="logs/radius-rest">
            {if $level neq ''}<input type="hidden" name="level" value="{$level|escape}">{/if}
            <div class="ls-search">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="search" autocomplete="off" name="q" value="{$q|escape}" placeholder="{Lang::T('Search')}..." aria-label="{Lang::T('Search')}...">
            </div>
            <button class="ls-btn ls-btn--primary" type="submit"><i class="fa fa-search" aria-hidden="true"></i> {Lang::T('Search')}</button>
            <a class="ls-btn" title="{Lang::T('Clear Search Query')}" href="{Text::url('logs/radius-rest')}"><i class="fa fa-undo" aria-hidden="true"></i> {Lang::T('Reset')}</a>
        </form>

        {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
        <form class="ls-bar" method="post" action="{Text::url('logs/radius-rest-clear')}">
            <input type="hidden" name="csrf_token" value="{$csrf_token}">
            <button type="button" class="ls-btn ls-btn--danger" onclick="return ask(this, '{Lang::T('Clear the whole log?')}')"><i class="fa fa-trash" aria-hidden="true"></i> {Lang::T('Clear Log')}</button>
        </form>
        {/if}

        <div class="ls-scroll">
            <table class="ls-table">
                <thead>
                    <tr>
                        <th>{Lang::T('Date')}</th>
                        <th>{Lang::T('Type')}</th>
                        <th>{Lang::T('Username')}</th>
                        <th>{Lang::T('Session')}</th>
                        <th>NAS</th>
                        <th>{Lang::T('Message')}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $rows as $r}
                    <tr>
                        <td data-label="{Lang::T('Date')}" class="ls-dim ls-when">{Lang::dateTimeFormat($r['at'])}</td>
                        <td data-label="{Lang::T('Type')}"><span class="ls-pill{if $r['level'] eq 'error'} ls-pill--banned{else} ls-pill--active{/if}">{if $r['level'] eq 'error'}{Lang::T('Error')}{else}{Lang::T('Note')}{/if}</span></td>
                        <td data-label="{Lang::T('Username')}">{if $r['username']|default:'' neq ''}{$r['username']|escape}{else}<span class="ls-dim">&ndash;</span>{/if}</td>
                        <td data-label="{Lang::T('Session')}" class="ls-dim">{$r['session']|default:''|escape}{if $r['status']|default:'' neq ''} <em class="ls-chip">{$r['status']|escape}</em>{/if}</td>
                        <td data-label="NAS" class="ls-dim">{$r['nas']|default:''|escape}</td>
                        <td data-label="{Lang::T('Message')}">{$r['message']|escape|nl2br}</td>
                    </tr>
                    {foreachelse}
                    <tr class="ls-none"><td colspan="6">
                        <div class="ls-empty"><b>{Lang::T('No logs found.')}</b></div>
                    </td></tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        {include file="pagination.tpl"}
    </div>

    <div class="bs-callout bs-callout-info" role="note">
        <h4><i class="fa fa-info-circle" aria-hidden="true"></i> {Lang::T('Accounting is accepted')}</h4>
        <p>{Lang::T('Accounting packets are accepted by radius.php even when something goes wrong while handling them: it is recorded here, and a packet the database could not take is kept in a queue and written later by the cron. The one exception is a Stop that could be neither stored nor queued: it is refused, so the router sends it again.')}</p>
    </div>
</div>

{include file="sections/footer.tpl"}
