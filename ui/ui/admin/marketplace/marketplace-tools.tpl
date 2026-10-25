{include file="sections/header.tpl"}

<div class="mp-wrap">
    {include file="admin/marketplace/_nav.tpl"}

    <section class="mp-hero mp-hero--tools">
        <div class="mp-hero__row">
            <div>
                <div class="mp-hero__crumb">
                    <a href="{Text::url('marketplace')}"><i class="fa fa-arrow-left"></i> {Lang::T('Marketplace')}</a>
                    <span>&middot;</span>
                    <b>{Lang::T('Tools')}</b>
                </div>
                <h1>{Lang::T('Marketplace Tools')}</h1>
                <p>{Lang::T('Repositories, automatic update checks, backups and the activity history of every install, update and removal.')}</p>
            </div>
            <div class="mp-stats">
                <div class="mp-stat"><b>{$backups|@count}</b><span>{Lang::T('Backups')}</span></div>
                <div class="mp-stat"><b>{$repo_status|@count}</b><span>{Lang::T('Repositories')}</span></div>
                <div class="mp-stat"><b>{$updates_pending}</b><span>{Lang::T('Updates')}</span></div>
            </div>
        </div>
    </section>

    <div class="mp-two">
        <section class="mp-panel">
            <div class="mp-panel__head"><div>
                <h2><i class="fa fa-cog"></i> {Lang::T('Settings')}</h2>
                <p>{Lang::T('Add your own store next to the official one and choose how updates are announced.')}</p>
            </div></div>
            <form method="post" action="{Text::url('marketplace/tools/settings')}" class="mp-form">
                <input type="hidden" name="csrf_token" value="{$csrf_token}">
                <label class="mp-check"><input type="checkbox" name="marketplace_official" value="yes" {if $official_on}checked{/if}>
                    {Lang::T('Use the official PHPNuxBill repository')}</label>
                <label class="mp-label" for="mp-repos">{Lang::T('Custom repositories (one https:// URL per line)')}</label>
                <textarea id="mp-repos" name="marketplace_repos" rows="4" class="form-control"
                    placeholder="https://example.com/my-repository.json">{$repos_text|escape}</textarea>
                <p class="mp-muted">{Lang::T('A repository is a JSON file with plugins, payment_gateway, devices and themes lists; each entry has id, name, description, ispaid, author, url, github and last_update. Earlier repositories win when two list the same id.')}</p>
                <label class="mp-check"><input type="checkbox" name="marketplace_notify" value="yes" {if $notify_on}checked{/if}>
                    {Lang::T('Send a Telegram message when the scheduled check finds new updates')}</label>
                <label class="mp-label" for="mp-keep">{Lang::T('Backups to keep per extension')}</label>
                <input id="mp-keep" type="number" min="1" max="50" name="marketplace_backup_keep" value="{$backup_keep}" class="form-control mp-narrow">
                <button type="submit" class="mp-filter is-active"><i class="fa fa-save"></i> {Lang::T('Save settings')}</button>
            </form>
        </section>

        <section class="mp-panel">
            <div class="mp-panel__head"><div>
                <h2><i class="fa fa-globe"></i> {Lang::T('Repositories')}</h2>
                <p>{Lang::T('Each repository is cached for 24 hours. A failed download keeps the previous copy.')}</p>
            </div>
            <a href="{Text::url('marketplace/sync&from=tools')}" class="mp-filter"><i class="fa fa-refresh"></i> {Lang::T('Sync & Check Updates')}</a></div>
            <ul class="mp-list">
                {foreach $repo_status as $r}
                <li>
                    <span class="mp-badge {if $r.state eq 'online' || $r.state eq 'cache'}mp-badge--active{elseif $r.state eq 'stale'}mp-badge--legacy{else}mp-badge--inactive{/if}">{$r.state}</span>
                    <span class="mp-grow">{$r.url|escape}{if $r.official} <span class="mp-chip">{Lang::T('official')}</span>{/if}</span>
                    <span class="mp-muted">{$r.count} {Lang::T('items')}{if $r.synced} &middot; {$r.synced|date_format:'%b %e, %H:%M'}{/if}</span>
                </li>
                {foreachelse}
                <li class="mp-muted">{Lang::T('No repository is enabled.')}</li>
                {/foreach}
            </ul>
            <h4 class="mp-sub">{Lang::T('Automatic checks')}</h4>
            <p class="mp-muted">{Lang::T('Add this line to cron to sync the store, detect updates and send the notification:')}</p>
            <pre class="mp-code">0 */6 * * * php /path/to/phpnuxbill/system/cron/marketplace_sync.php</pre>
        </section>
    </div>

    <section class="mp-panel">
        <div class="mp-panel__head"><div>
            <h2><i class="fa fa-archive"></i> {Lang::T('Backups')}</h2>
            <p>{Lang::T('Created automatically before every install, update, conversion and removal. Restoring first snapshots the current version, so it can itself be undone.')}</p>
        </div>
        {if $zipExt}
        <form method="post" action="" class="mp-inline" id="mp-backup-now" data-base="{Text::url('marketplace/tools/backup/')}">
            <input type="hidden" name="csrf_token" value="{$csrf_token}">
            <select id="mp-backup-select" class="form-control mp-narrow">
                {foreach $installed_list as $i}
                <option value="{$i.type|escape}:{$i.slug|escape}">{$i.type|escape} &middot; {$i.name|escape}</option>
                {/foreach}
            </select>
            <button type="submit" class="mp-filter"><i class="fa fa-save"></i> {Lang::T('Back up now')}</button>
        </form>
        {/if}</div>
        {if $backups}
        <div class="mp-tablewrap">
        <table class="mp-table">
            <thead><tr><th>{Lang::T('Extension')}</th><th>{Lang::T('Reason')}</th><th>{Lang::T('Version')}</th><th>{Lang::T('Created')}</th><th>{Lang::T('Size')}</th><th></th></tr></thead>
            <tbody>
            {foreach $backups as $b}
            <tr>
                <td><span class="mp-badge mp-badge--folder">{$b.type|escape}</span> <strong>{$b.slug|escape}</strong></td>
                <td>{$b.reason}</td>
                <td>{if $b.version}v{$b.version|escape}{else}&ndash;{/if}</td>
                <td>{$b.time|date_format:'%Y-%m-%d %H:%M'}</td>
                <td>{($b.size/1024)|string_format:'%.1f'} KB</td>
                <td class="mp-actions-cell">
                    <form method="post" action="{Text::url('marketplace/tools/restore/', $b.file)}" class="mp-inline">
                        <input type="hidden" name="csrf_token" value="{$csrf_token}">
                        <button type="button" class="mp-mini mp-mini--accent"
                            onclick="return ask(this, '{Lang::T('Restore this backup? The current version is saved first.')|escape:'javascript'}')"><i class="fa fa-undo"></i> {Lang::T('Restore')}</button>
                    </form>
                    <a class="mp-mini" href="{Text::url('marketplace/tools/download/', $b.file)}"><i class="fa fa-download"></i> {Lang::T('Download')}</a>
                    <form method="post" action="{Text::url('marketplace/tools/delete/', $b.file)}" class="mp-inline">
                        <input type="hidden" name="csrf_token" value="{$csrf_token}">
                        <button type="button" class="mp-mini mp-mini--danger"
                            onclick="return ask(this, '{Lang::T('Delete this backup permanently?')|escape:'javascript'}')"><i class="fa fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            {/foreach}
            </tbody>
        </table>
        </div>
        {else}
        <div class="mp-empty"><i class="fa fa-archive"></i><h4>{Lang::T('No backups yet')}</h4><p>{Lang::T('They appear here after the first install, update or removal.')}</p></div>
        {/if}
    </section>

    <section class="mp-panel">
        <div class="mp-panel__head"><div>
            <h2><i class="fa fa-history"></i> {Lang::T('Activity')}</h2>
            <p>{Lang::T('The latest marketplace actions. The full history is in Logs, type "Marketplace".')}</p>
        </div></div>
        {if $logs}
        <div class="mp-tablewrap">
        <table class="mp-table">
            <thead><tr><th>{Lang::T('Date')}</th><th>{Lang::T('Action')}</th><th>{Lang::T('IP')}</th></tr></thead>
            <tbody>
            {foreach $logs as $l}
            <tr><td class="mp-nowrap">{$l.date}</td><td>{$l.description|escape}</td><td class="mp-muted">{$l.ip|escape}</td></tr>
            {/foreach}
            </tbody>
        </table>
        </div>
        {else}
        <div class="mp-empty"><i class="fa fa-history"></i><h4>{Lang::T('No activity recorded yet')}</h4></div>
        {/if}
    </section>
</div>

{literal}
<script>
    (function () {
        var f = document.getElementById('mp-backup-now'), s = document.getElementById('mp-backup-select');
        if (f && s) f.addEventListener('submit', function () {
            f.action = f.getAttribute('data-base') + encodeURIComponent(s.value);
        });
    })();
</script>
{/literal}

{include file="sections/footer.tpl"}
