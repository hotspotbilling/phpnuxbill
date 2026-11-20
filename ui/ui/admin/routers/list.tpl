{include file="sections/header.tpl"}

{include file="admin/routely-vpn-intro.tpl" rl_for="router"}

<div class="ls">
    <div class="ls-top">
        <div></div>
        <div class="ls-actions">
            <a class="ls-btn" title="{Lang::T('Maps')}" href="{Text::url('')}maps/routers"><i class="fa fa-map-marker" aria-hidden="true"></i> {Lang::T('Maps')}</a>
            <a class="ls-btn" href="{Text::url('routers/settings')}"><i class="fa fa-cog" aria-hidden="true"></i> {Lang::T('Settings')}</a>
            <a href="{Text::url('')}routers/add" class="ls-btn ls-btn--primary"><i class="fa fa-plus" aria-hidden="true"></i> {Lang::T('New Router')}</a>
        </div>
    </div>

    <div class="ls-card">
        <form id="site-search" class="ls-bar" method="post" action="{Text::url('routers/list/')}">
            <div class="ls-search">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="search" autocomplete="off" name="name" value="{$name|default:''|escape}" placeholder="{Lang::T('Search by Name')}..." aria-label="{Lang::T('Search by Name')}">
            </div>
            {if $has_site && $sites}
            <label class="ls-field">
                <select name="site" aria-label="{Lang::T('Site / Group')}">
                    <option value="">{Lang::T('All sites')}</option>
                    {foreach $sites as $st}<option value="{$st|escape}" {if (string) $site === (string) $st}selected{/if}>{$st|escape}</option>{/foreach}
                </select>
            </label>
            {/if}
            <button class="ls-btn ls-btn--primary" type="submit"><i class="fa fa-search" aria-hidden="true"></i> {Lang::T('Search')}</button>
            <a class="ls-btn" title="{Lang::T('Clear Search Query')}" href="{Text::url('routers/list/')}"><i class="fa fa-undo" aria-hidden="true"></i> {Lang::T('Reset')}</a>
        </form>

        <form id="rtBulkForm" method="post" action="{Text::url('routers/bulk')}" class="ls-bulk" hidden>
            <input type="hidden" name="csrf_token" value="{$csrf_token}">
            <input type="hidden" name="op" value="">
            <span><b id="rtBulkN">0</b> {Lang::T('selected')}</span>
            <span class="rt-bulk-btns">
            <button type="button" class="ls-btn" data-rt-bulk="enable" onclick="this.form.op.value = 'enable'; return ask(this, '{Lang::T('Enable the selected routers?')|escape:'javascript'}')"><i class="fa fa-toggle-on" aria-hidden="true"></i> {Lang::T('Enable')}</button>
            <button type="button" class="ls-btn" data-rt-bulk="disable" onclick="this.form.op.value = 'disable'; return ask(this, '{Lang::T('Disable the selected routers? PHPNuxBill stops using them until they are enabled again.')|escape:'javascript'}')"><i class="fa fa-toggle-off" aria-hidden="true"></i> {Lang::T('Disable')}</button>
            {if $health_on}<button type="button" class="ls-btn" id="rtCheckNow" disabled><i class="fa fa-refresh" aria-hidden="true"></i> {Lang::T('Check now')}</button>{/if}
            </span>
        </form>

        {* on a phone the table's header (and its Select all) is hidden: the same control, here *}
        <label class="ls-selall"><input type="checkbox" id="rtAllNarrow"> {Lang::T('Select all')}</label>
        <div class="ls-scroll">
            <table class="ls-table">
                <thead>
                    <tr>
                        <th class="ls-check"><input type="checkbox" id="rtAll" aria-label="{Lang::T('Select all')}"></th>
                        <th>{Lang::T('Router Name')}</th>
                        <th>{Lang::T('IP Address')}</th>
                        <th>{Lang::T('Username')}</th>
                        <th>{Lang::T('Description')}</th>
                        {if $_c['router_check']}<th>{Lang::T('Online Status')}</th><th>{Lang::T('Last Seen')}</th>{/if}
                        {if $health_on}<th>{Lang::T('Health')}</th>{/if}
                        <th>{Lang::T('Customers')}</th>
                        <th>{Lang::T('Status')}</th>
                        <th>ID</th>
                        <th class="ls-end">{Lang::T('Manage')}</th>
                    </tr>
                </thead>
                {$rt_cols = 9}{if $_c['router_check']}{$rt_cols = $rt_cols + 2}{/if}{if $health_on}{$rt_cols = $rt_cols + 1}{/if}
                {$rt_live = $health_on}
                <tbody>
                    {foreach $d as $ds}
                    {$rt_use = $usage[$ds['id']]|default:['plans' => 0, 'active' => 0, 'expired' => 0]}
                    {$rt_can = $rt_live && $ds['enabled'] == 1 && $ds['ip_address'] != ''}
                    {* the SuperAdmin's router actions do not depend on live health: they get the details row even when it is off *}
                    {$rt_act = $_admin['user_type'] == 'SuperAdmin' && $ds['enabled'] == 1 && $ds['ip_address'] != ''}
                    {* the tabs (users online, interfaces, setup check, updates and backups) are for every enabled router with an address *}
                    {$rt_tools = $ds['enabled'] == 1 && $ds['ip_address'] != ''}
                    <tr{if $rt_can} data-rt-id="{$ds['id']}"{/if}>
                        <td class="ls-check"><input type="checkbox" name="ids[]" value="{$ds['id']}" form="rtBulkForm" aria-label="{$ds['name']|escape}"></td>
                        <td data-label="{Lang::T('Router Name')}">{if $ds['coordinates']}<a href="https://www.google.com/maps/dir//{$ds['coordinates']|escape:'url'}/" target="_blank" rel="noopener" title="{$ds['coordinates']|escape}"><i class="fa fa-map-marker" aria-hidden="true"></i></a> {/if}<b class="ls-strong">{$ds['name']|escape}</b>{if $has_site && $ds['site']} <span class="ls-pill rt-site">{$ds['site']|escape}</span>{/if}</td>
                        <td data-label="{Lang::T('IP Address')}"><span class="ls-secret" tabindex="0">{$ds['ip_address']|escape}</span>{if isset($routely_vpn['addresses'][$ds['ip_address']])}{$rv = $routely_vpn['addresses'][$ds['ip_address']]} <a class="ls-pill ls-pill--routely{if $rv['state'] != 'up'} is-down{/if}" href="{Text::url('plugin/routely/vpn')}" title="{Lang::T('Reached through Routely VPN')}: {$rv['device']|escape} ({if $rv['state'] == 'up'}{Lang::T('connected')}{else}{Lang::T('not connected')}{/if})"><i class="fa fa-shield" aria-hidden="true"></i> Routely</a>{/if}</td>
                        <td data-label="{Lang::T('Username')}"><span class="ls-secret" tabindex="0">{$ds['username']|escape}</span></td>
                        <td data-label="{Lang::T('Description')}" class="ls-dim">{$ds['description']|escape}</td>
                        {if $_c['router_check']}<td data-label="{Lang::T('Online Status')}">{* as on the map: Offline as said; Online only once a check has seen it (last seen), otherwise not checked yet *}{if $ds['status'] == 'Offline'}<span class="ls-pill ls-pill--banned">{Lang::T('Offline')}</span>{elseif $ds['status'] == '' || $ds['last_seen'] == ''}<span class="ls-pill">{Lang::T('Not checked yet')}</span>{else}<span class="ls-pill ls-pill--active">{Lang::T('Online')}</span>{/if}</td><td data-label="{Lang::T('Last Seen')}" class="ls-dim ls-when">{$ds['last_seen']|escape}</td>{/if}
                        {if $health_on}<td data-label="{Lang::T('Health')}" class="rt-health">{if $rt_can}<span class="rt-skel" aria-label="{Lang::T('Loading')}"></span>{else}<span class="ls-dim">&mdash;</span>{/if}</td>{/if}
                        <td data-label="{Lang::T('Customers')}" class="rt-cust"><a href="{Text::url('plan/list&router=', $ds['name']|escape:'url', '&status=on')}" title="{Lang::T('Active customers on this router')}"><b>{$rt_use['active']}</b> {Lang::T('active')}</a><a href="{Text::url('plan/list&router=', $ds['name']|escape:'url', '&status=off')}" class="ls-dim" title="{Lang::T('Expired customers on this router')}">{$rt_use['expired']|default:0} {Lang::T('expired')}</a></td>
                        <td data-label="{Lang::T('Status')}">{if $ds['enabled'] == 1}<span class="ls-pill ls-pill--active">{Lang::T('Enabled')}</span>{else}<span class="ls-pill ls-pill--banned">{Lang::T('Disabled')}</span>{/if}</td>
                        <td data-label="ID" class="ls-dim">{$ds['id']}</td>
                        <td class="ls-end"><div class="ls-row-actions">{if $rt_tools}<button type="button" class="ls-btn ls-btn--sm ls-btn--icon rt-more" data-rt-more="{$ds['id']}" aria-expanded="false" aria-controls="rt-detail-{$ds['id']}" aria-label="{Lang::T('Health details')}" title="{Lang::T('Health details')}"><i class="fa fa-chevron-down" aria-hidden="true"></i></button>{/if}<a href="{Text::url('routers/edit/', $ds['id'])}" class="ls-btn ls-btn--sm">{Lang::T('Edit')}</a>{if $rt_use['plans'] > 0 || $rt_use['active'] > 0}{$rt_why = "{Lang::T('In use by')} {$rt_use['plans']} {Lang::T('plans')}, {$rt_use['active']} {Lang::T('active customers')}. {Lang::T('Move them to another router first, or disable this router instead.')}"}<button type="button" class="ls-btn ls-btn--sm ls-btn--icon ls-btn--danger" disabled aria-label="{Lang::T('Delete')}" aria-describedby="rt-why-{$ds['id']}" title="{$rt_why|escape}"><i class="fa fa-trash" aria-hidden="true"></i></button><span class="rt-why" id="rt-why-{$ds['id']}" tabindex="0" role="note" title="{$rt_why|escape}"><i class="fa fa-info-circle" aria-hidden="true"></i><span class="sr-only">{$rt_why|escape}</span></span>{else}<form method="post" action="{Text::url('routers/delete/', $ds['id'])}" class="rt-del"><input type="hidden" name="csrf_token" value="{$csrf_token}"><button type="button" class="ls-btn ls-btn--sm ls-btn--icon ls-btn--danger" aria-label="{Lang::T('Delete')}" onclick="return ask(this, '{Lang::T('Delete')|escape:'javascript'} {$ds['name']|escape:'javascript'|escape}?')"><i class="fa fa-trash" aria-hidden="true"></i></button></form>{/if}</div></td>
                    </tr>
                    {if $rt_tools}
                    <tr class="rt-detail" id="rt-detail-{$ds['id']}" hidden><td colspan="{$rt_cols}">
                        <div class="rt-tabs" role="tablist" aria-label="{$ds['name']|escape}" data-rt-tabs="{$ds['id']}">
                            {if $rt_can}<button type="button" role="tab" class="rt-tab" data-rt-tab="live" id="rt-tab-{$ds['id']}-live" aria-controls="rt-pane-{$ds['id']}-live" aria-selected="false" tabindex="-1" disabled><i class="fa fa-line-chart" aria-hidden="true"></i> {Lang::T('Live')}</button>{/if}
                            <button type="button" role="tab" class="rt-tab" data-rt-tab="history" id="rt-tab-{$ds['id']}-history" aria-controls="rt-pane-{$ds['id']}-history" aria-selected="false" tabindex="-1"><i class="fa fa-history" aria-hidden="true"></i> {Lang::T('History')}</button>
                            <button type="button" role="tab" class="rt-tab" data-rt-tab="users" id="rt-tab-{$ds['id']}-users" aria-controls="rt-pane-{$ds['id']}-users" aria-selected="false" tabindex="-1"><i class="fa fa-users" aria-hidden="true"></i> {Lang::T('Users online')}</button>
                            <button type="button" role="tab" class="rt-tab" data-rt-tab="ifaces" id="rt-tab-{$ds['id']}-ifaces" aria-controls="rt-pane-{$ds['id']}-ifaces" aria-selected="false" tabindex="-1"><i class="fa fa-plug" aria-hidden="true"></i> {Lang::T('Interfaces')}</button>
                            <button type="button" role="tab" class="rt-tab" data-rt-tab="check" id="rt-tab-{$ds['id']}-check" aria-controls="rt-pane-{$ds['id']}-check" aria-selected="false" tabindex="-1"><i class="fa fa-stethoscope" aria-hidden="true"></i> {Lang::T('Setup check')}</button>
                            <button type="button" role="tab" class="rt-tab" data-rt-tab="updates" id="rt-tab-{$ds['id']}-updates" aria-controls="rt-pane-{$ds['id']}-updates" aria-selected="false" tabindex="-1"><i class="fa fa-cloud-download" aria-hidden="true"></i> {Lang::T('Updates & backups')}</button>
                        </div>
                        {if $rt_can}<div class="rt-pane" role="tabpanel" data-rt-pane="live" id="rt-pane-{$ds['id']}-live" aria-labelledby="rt-tab-{$ds['id']}-live" tabindex="0"><div class="rt-panel" data-rt-panel="{$ds['id']}"></div></div>{/if}
                        <div class="rt-pane" role="tabpanel" data-rt-pane="history" id="rt-pane-{$ds['id']}-history" aria-labelledby="rt-tab-{$ds['id']}-history" tabindex="0" hidden></div>
                        <div class="rt-pane" role="tabpanel" data-rt-pane="users" id="rt-pane-{$ds['id']}-users" aria-labelledby="rt-tab-{$ds['id']}-users" tabindex="0" hidden></div>
                        <div class="rt-pane" role="tabpanel" data-rt-pane="ifaces" id="rt-pane-{$ds['id']}-ifaces" aria-labelledby="rt-tab-{$ds['id']}-ifaces" tabindex="0" hidden></div>
                        <div class="rt-pane" role="tabpanel" data-rt-pane="check" id="rt-pane-{$ds['id']}-check" aria-labelledby="rt-tab-{$ds['id']}-check" tabindex="0" hidden></div>
                        <div class="rt-pane" role="tabpanel" data-rt-pane="updates" id="rt-pane-{$ds['id']}-updates" aria-labelledby="rt-tab-{$ds['id']}-updates" tabindex="0" hidden></div>
                        {if $rt_act}
                        <div class="rt-actions">
                            <span class="rt-actions__label">{Lang::T('On this router')}</span>
                            {foreach [
                                ['sync', 'fa-exchange', Lang::T('Sync customers'), Lang::T('Send every active customer of this router to it again?'), ''],
                                ['backup', 'fa-save', Lang::T('Save a backup'), Lang::T('Save a backup file on the router? It is kept in Files on the router.'), ''],
                                ['kick', 'fa-sign-out', Lang::T('Disconnect hotspot users'), Lang::T('Disconnect everyone signed in to the hotspot? They can sign in again at once.'), ''],
                                ['reboot', 'fa-power-off', Lang::T('Reboot'), Lang::T('Restart the router? Everyone on it loses the connection for a minute or two.'), 'ls-btn--danger']
                            ] as $act}
                            <form method="post" action="{Text::url('routers/action/', $ds['id'])}" class="rt-del">
                                <input type="hidden" name="csrf_token" value="{$csrf_token}">
                                <input type="hidden" name="op" value="{$act[0]}">
                                <button type="button" class="ls-btn ls-btn--sm {$act[4]}" onclick="return ask(this, '{$ds['name']|escape:'javascript'|escape}: {$act[3]|escape:'javascript'|escape}')"><i class="fa {$act[1]}" aria-hidden="true"></i> {$act[2]}</button>
                            </form>
                            {/foreach}
                        </div>
                        {/if}
                    </td></tr>
                    {/if}
                    {foreachelse}
                    <tr class="ls-none"><td colspan="{$rt_cols}">
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

<script>
    // tick routers to enable, disable or check several at once
    (function () {
        var bar = document.getElementById('rtBulkForm'), n = document.getElementById('rtBulkN'), all = document.getElementById('rtAll');
        var alls = [all, document.getElementById('rtAllNarrow')].filter(Boolean); // the header's and the phone one, kept the same
        function boxes() { return document.querySelectorAll('input[name="ids[]"][form="rtBulkForm"]'); }
        function sync() {
            var c = 0, total = 0;
            boxes().forEach(function (b) { total++; if (b.checked) c++; });
            n.textContent = c;
            bar.hidden = c === 0;
            // Check now reads live health, which a disabled router (or one without an address) does not have: off when none of
            // the ticked ones can be checked (they can still be enabled)
            var check = document.getElementById('rtCheckNow');
            if (check) {
                var can = 0;
                boxes().forEach(function (b) { if (b.checked && document.querySelector('tr[data-rt-id="' + b.value + '"]')) can++; });
                // and only once the live-health script is running (it enables the button itself when it starts)
                check.disabled = can === 0 || !window.rtHealthReady;
                check.title = can ? '' : '{Lang::T('None of the selected routers can be checked: they are disabled or have no address.')|escape:'javascript'}';
            }
            alls.forEach(function (a) {
                a.checked = total > 0 && c === total;
                a.indeterminate = c > 0 && c < total;
            });
        }
        alls.forEach(function (a) {
            a.addEventListener('change', function () {
                boxes().forEach(function (b) { b.checked = a.checked; });
                sync();
            });
        });
        boxes().forEach(function (b) { b.addEventListener('change', sync); });
        // the browser can bring ticks back (Back, or a reload that keeps the form): the bar and Select all follow them, now and
        // when the page is shown again from its cache
        sync();
        window.addEventListener('pageshow', sync);
        // with live health off (or its script failed to load), the details row opens and closes here
        document.addEventListener('click', function (e) {
            var more = e.target.closest('[data-rt-more]');
            if (!more || window.rtHealthReady) return;
            var row = document.getElementById('rt-detail-' + more.getAttribute('data-rt-more'));
            var open = row.hidden;
            // neither script loaded: the tabs cannot work, which is said instead of an empty row
            if (open && !window.rtToolsReady && !row.querySelector('.rt-unavail')) {
                row.querySelector('td').insertAdjacentHTML('afterbegin', '<p class="rt-err rt-err--block rt-unavail"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> {Lang::T('The details could not be loaded. Reload the page and try again.')|escape:'javascript'}</p>');
            }
            row.hidden = !open;
            more.setAttribute('aria-expanded', open ? 'true' : 'false');
            more.classList.toggle('is-open', open);
        });
        var check = document.getElementById('rtCheckNow');
        if (check) check.addEventListener('click', function () {
            var ids = [];
            boxes().forEach(function (b) { if (b.checked) ids.push(b.value); });
            if (window.rtHealthRefresh) window.rtHealthRefresh(ids);
        });
    })();
</script>

<script>
    window.rtTools = {
        url: '{Text::url('routers/')}',
        csrf: '{$csrf_token|escape:'javascript'}',
        superadmin: {if $_admin['user_type'] == 'SuperAdmin'}true{else}false{/if},
        t: {
            loading: '{Lang::T('Loading')|escape:'javascript'}', refresh: '{Lang::T('Refresh')|escape:'javascript'}', fail: '{Lang::T('Could not reach the router')|escape:'javascript'}',
            user: '{Lang::T('User')|escape:'javascript'}', address: '{Lang::T('Address')|escape:'javascript'}', mac: '{Lang::T('MAC / Caller ID')|escape:'javascript'}',
            uptime: '{Lang::T('Uptime')|escape:'javascript'}', data: '{Lang::T('Data')|escape:'javascript'}', none: '{Lang::T('Nobody is connected')|escape:'javascript'}',
            notCustomer: '{Lang::T('not a customer')|escape:'javascript'}', drop: '{Lang::T('Disconnect')|escape:'javascript'}',
            dropAsk: '{Lang::T('Disconnect this user? They can sign in again at once.')|escape:'javascript'}', dropped: '{Lang::T('Disconnected')|escape:'javascript'}',
            name: '{Lang::T('Name')|escape:'javascript'}', type: '{Lang::T('Type')|escape:'javascript'}', state: '{Lang::T('State')|escape:'javascript'}',
            up: '{Lang::T('Up')|escape:'javascript'}', down: '{Lang::T('Down')|escape:'javascript'}', disabled: '{Lang::T('Disabled')|escape:'javascript'}',
            traffic: '{Lang::T('Traffic')|escape:'javascript'}', errors: '{Lang::T('Errors / drops')|escape:'javascript'}', linkDowns: '{Lang::T('Link downs')|escape:'javascript'}',
            lastUp: '{Lang::T('Last up')|escape:'javascript'}', runAgain: '{Lang::T('Check again')|escape:'javascript'}', allGood: '{Lang::T('Nothing to fix')|escape:'javascript'}', oneProblem: '{Lang::T('thing to look at')|escape:'javascript'}', problems: '{Lang::T('things to look at')|escape:'javascript'}',
            routeros: 'RouterOS', firmware: '{Lang::T('Firmware')|escape:'javascript'}', channel: '{Lang::T('Channel')|escape:'javascript'}',
            installed: '{Lang::T('Installed')|escape:'javascript'}', latest: '{Lang::T('Latest')|escape:'javascript'}', notChecked: '{Lang::T('not checked yet')|escape:'javascript'}',
            checkUpdates: '{Lang::T('Check for updates')|escape:'javascript'}', newer: '{Lang::T('Update available')|escape:'javascript'}', current: '{Lang::T('Up to date')|escape:'javascript'}',
            updateHow: '{Lang::T('PHPNuxBill never installs updates. Update from Winbox, System > Packages, when it suits you.')|escape:'javascript'}',
            backups: '{Lang::T('Backups on the router')|escape:'javascript'}', noBackups: '{Lang::T('No backup files on the router')|escape:'javascript'}',
            download: '{Lang::T('Download')|escape:'javascript'}', nightly: '{Lang::T('Nightly backups')|escape:'javascript'}', on: '{Lang::T('on')|escape:'javascript'}', off: '{Lang::T('off')|escape:'javascript'}',
            nightlyWhere: '{Lang::T('Routers > Settings')|escape:'javascript'}', auto: '{Lang::T('nightly')|escape:'javascript'}',
            history: '{Lang::T('History')|escape:'javascript'}', uptimePct: '{Lang::T('Online')|escape:'javascript'}', samples: '{Lang::T('readings')|escape:'javascript'}',
            cpuMem: '{Lang::T('CPU and memory')|escape:'javascript'}', cpu: 'CPU', memory: '{Lang::T('Memory')|escape:'javascript'}', usersOnline: '{Lang::T('Users online')|escape:'javascript'}',
            uplink: '{Lang::T('Uplink traffic')|escape:'javascript'}', dl: '{Lang::T('Download')|escape:'javascript'}', ul: '{Lang::T('Upload')|escape:'javascript'}',
            events: '{Lang::T('Events')|escape:'javascript'}', noEvents: '{Lang::T('Nothing happened in this period')|escape:'javascript'}',
            noHistory: '{Lang::T('No readings yet. The cron reads each router about every 5 minutes; turn Router history on in Routers > Settings.')|escape:'javascript'}',
            noMetric: '{Lang::T('No readings for this graph in this period')|escape:'javascript'}',
            chartKeys: '{Lang::T('Use the left and right arrow keys to read each value, Home and End for the first and last.')|escape:'javascript'}',
            partial: '{Lang::T('partial list')|escape:'javascript'}', ev_moved: '{Lang::T('Address changed')|escape:'javascript'}',
            notCollecting: '{Lang::T('Router history is off in Routers > Settings, so no new readings are kept.')|escape:'javascript'}',
            ev_offline: '{Lang::T('Went offline')|escape:'javascript'}', ev_online: '{Lang::T('Back online')|escape:'javascript'}', ev_reboot: '{Lang::T('Restarted')|escape:'javascript'}',
            ev_cpu_high: '{Lang::T('CPU high')|escape:'javascript'}', ev_cpu_ok: '{Lang::T('CPU normal')|escape:'javascript'}', ev_mem_high: '{Lang::T('Memory high')|escape:'javascript'}',
            ev_mem_ok: '{Lang::T('Memory normal')|escape:'javascript'}', ev_disk_high: '{Lang::T('Disk nearly full')|escape:'javascript'}', ev_disk_ok: '{Lang::T('Disk normal')|escape:'javascript'}',
            search: '{Lang::T('Search')|escape:'javascript'}', noMatch: '{Lang::T('Nothing matches')|escape:'javascript'}', of: '{Lang::T('of')|escape:'javascript'}',
            prev: '{Lang::T('Prev')|escape:'javascript'}', next: '{Lang::T('Next')|escape:'javascript'}', shown: '{Lang::T('shown')|escape:'javascript'}',
            online: '{Lang::T('online')|escape:'javascript'}', all: '{Lang::T('All')|escape:'javascript'}', interfaces: '{Lang::T('interfaces')|escape:'javascript'}',
            noIfaces: '{Lang::T('No interfaces found')|escape:'javascript'}', readErr: '{Lang::T('Could not read')|escape:'javascript'}',
            showDynamic: '{Lang::T('Show dynamic')|escape:'javascript'}', capped: '{Lang::T('Only the first %s are listed.')|escape:'javascript'}',
            downloadHow: '{Lang::T('To keep a copy elsewhere, download it from Files in Winbox or WebFig.')|escape:'javascript'}'
        }
    };
</script>
<script src="{$app_url}/ui/ui/scripts/routers-tools.js?2026.11.19.13"></script>

{if $health_on}
<script>
    window.rtHealth = {
        url: '{Text::url('routers/health/')}',
        t: {
            version: 'RouterOS', hotspot: 'Hotspot', pppoe: 'PPPoE',
            cpu: '{Lang::T('CPU')|escape:'javascript'}',
            ram: '{Lang::T('RAM')|escape:'javascript'}',
            memory: '{Lang::T('Memory')|escape:'javascript'}',
            disk: '{Lang::T('Disk')|escape:'javascript'}',
            online: '{Lang::T('Online now')|escape:'javascript'}',
            board: '{Lang::T('Board')|escape:'javascript'}',
            uptime: '{Lang::T('Uptime')|escape:'javascript'}',
            temp: '{Lang::T('Temperature')|escape:'javascript'}',
            core: '{Lang::T('core')|escape:'javascript'}',
            cores: '{Lang::T('cores')|escape:'javascript'}',
            of: '{Lang::T('of')|escape:'javascript'}',
            updated: '{Lang::T('Updated')|escape:'javascript'}',
            now: '{Lang::T('now')|escape:'javascript'}',
            minutes5: '{Lang::T('5 min ago')|escape:'javascript'}',
            fail: '{Lang::T('Could not reach the router')|escape:'javascript'}',
            users: '{Lang::T('users online')|escape:'javascript'}',
            live: '{Lang::T('Live')|escape:'javascript'}',
            paused: '{Lang::T('Paused')|escape:'javascript'}',
            pause: '{Lang::T('Pause')|escape:'javascript'}',
            resume: '{Lang::T('Resume')|escape:'javascript'}',
            cpuMem: '{Lang::T('CPU and memory')|escape:'javascript'}',
            traffic: '{Lang::T('Traffic')|escape:'javascript'}',
            iface: '{Lang::T('Interface')|escape:'javascript'}',
            down: '{Lang::T('Download')|escape:'javascript'}',
            up: '{Lang::T('Upload')|escape:'javascript'}',
            measuring: '{Lang::T('Measuring, the graph starts with the next readings')|escape:'javascript'}',
            chartKeys: '{Lang::T('Use the left and right arrow keys to read each value, Home and End for the first and last.')|escape:'javascript'}'
        }
    };
    // the live-health script did not load: the Health cells say so, and the Live tab and Check now stay off (History and the
    // other tabs do not need it)
    window.rtHealthFailed = function () {
        document.querySelectorAll('.rt-health .rt-skel').forEach(function (s) {
            s.outerHTML = '<span class="ls-dim">{Lang::T('Unavailable')|escape:'javascript'}</span>';
        });
    };
</script>
<script src="{$app_url}/ui/ui/scripts/routers-health.js?2026.11.19.8" onerror="window.rtHealthFailed()"></script>
{/if}

    <div class="bs-callout bs-callout-info" id="callout-navbar-role">
        <h4>{Lang::T('Check if Router Online?')}</h4>
        <p>{Lang::T('Turn on Router Check in')} <a href="{Text::url('routers/settings')}">{Lang::T('Router Settings')}</a>. {Lang::T('Once the cron has run, this list shows the Online Status and Last Seen of each router.')}</p>
    </div>
</div>

{include file="sections/footer.tpl"}
