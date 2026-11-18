{include file="sections/header.tpl"}

{include file="admin/routely-vpn-intro.tpl" rl_for="router"}

<div class="ls">
    <div class="ls-top">
        <div></div>
        <div class="ls-actions">
            <a class="ls-btn" title="{Lang::T('Maps')}" href="{Text::url('')}maps/routers"><i class="fa fa-map-marker" aria-hidden="true"></i> {Lang::T('Maps')}</a>
            <a href="{Text::url('')}routers/add" class="ls-btn ls-btn--primary"><i class="fa fa-plus" aria-hidden="true"></i> {Lang::T('New Router')}</a>
        </div>
    </div>

    <div class="ls-card">
        <form id="site-search" class="ls-bar" method="post" action="{Text::url('routers/list/')}">
            <div class="ls-search">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="search" autocomplete="off" name="name" placeholder="{Lang::T('Search by Name')}..." aria-label="{Lang::T('Search by Name')}">
            </div>
            {if $has_site && $sites}
            <label class="ls-field">
                <select name="site" aria-label="{Lang::T('Site / Group')}" onchange="this.form.submit()">
                    <option value="">{Lang::T('All sites')}</option>
                    {foreach $sites as $st}<option value="{$st|escape}" {if $site == $st}selected{/if}>{$st|escape}</option>{/foreach}
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
            {if $health_on}<button type="button" class="ls-btn" id="rtCheckNow"><i class="fa fa-refresh" aria-hidden="true"></i> {Lang::T('Check now')}</button>{/if}
            </span>
        </form>

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
                    {$rt_use = $usage[$ds['name']]|default:['plans' => 0, 'active' => 0, 'expired' => 0]}
                    {$rt_can = $rt_live && $ds['enabled'] == 1 && $ds['ip_address'] != ''}
                    <tr{if $rt_can} data-rt-id="{$ds['id']}"{/if}>
                        <td class="ls-check"><input type="checkbox" name="ids[]" value="{$ds['id']}" form="rtBulkForm" aria-label="{$ds['name']|escape}"></td>
                        <td data-label="{Lang::T('Router Name')}">{if $ds['coordinates']}<a href="https://www.google.com/maps/dir//{$ds['coordinates']|escape:'url'}/" target="_blank" rel="noopener" title="{$ds['coordinates']|escape}"><i class="fa fa-map-marker" aria-hidden="true"></i></a> {/if}<b class="ls-strong">{$ds['name']|escape}</b>{if $has_site && $ds['site']} <span class="ls-pill rt-site">{$ds['site']|escape}</span>{/if}</td>
                        <td data-label="{Lang::T('IP Address')}"><span class="ls-secret" tabindex="0">{$ds['ip_address']|escape}</span>{if isset($routely_vpn['addresses'][$ds['ip_address']])}{$rv = $routely_vpn['addresses'][$ds['ip_address']]} <a class="ls-pill ls-pill--routely{if $rv['state'] != 'up'} is-down{/if}" href="{Text::url('plugin/routely/vpn')}" title="{Lang::T('Reached through Routely VPN')}: {$rv['device']|escape} ({if $rv['state'] == 'up'}{Lang::T('connected')}{else}{Lang::T('not connected')}{/if})"><i class="fa fa-shield" aria-hidden="true"></i> Routely</a>{/if}</td>
                        <td data-label="{Lang::T('Username')}"><span class="ls-secret" tabindex="0">{$ds['username']|escape}</span></td>
                        <td data-label="{Lang::T('Description')}" class="ls-dim">{$ds['description']|escape}</td>
                        {if $_c['router_check']}<td data-label="{Lang::T('Online Status')}"><span class="ls-pill {if $ds['status'] == 'Online'}ls-pill--active{else}ls-pill--banned{/if}">{if $ds['status'] == 'Online'}{Lang::T('Online')}{else}{Lang::T('Offline')}{/if}</span></td><td data-label="{Lang::T('Last Seen')}" class="ls-dim ls-when">{$ds['last_seen']|escape}</td>{/if}
                        {if $health_on}<td data-label="{Lang::T('Health')}" class="rt-health">{if $rt_can}<span class="rt-skel" aria-label="{Lang::T('Loading')}"></span>{else}<span class="ls-dim">&mdash;</span>{/if}</td>{/if}
                        <td data-label="{Lang::T('Customers')}" class="rt-cust"><a href="{Text::url('plan/list&router=', $ds['name']|escape:'url', '&status=on')}" title="{Lang::T('Active customers on this router')}"><b>{$rt_use['active']}</b> {Lang::T('active')}</a><a href="{Text::url('plan/list&router=', $ds['name']|escape:'url', '&status=off')}" class="ls-dim" title="{Lang::T('Expired customers on this router')}">{$rt_use['expired']|default:0} {Lang::T('expired')}</a></td>
                        <td data-label="{Lang::T('Status')}">{if $ds['enabled'] == 1}<span class="ls-pill ls-pill--active">{Lang::T('Enabled')}</span>{else}<span class="ls-pill ls-pill--banned">{Lang::T('Disabled')}</span>{/if}</td>
                        <td data-label="ID" class="ls-dim">{$ds['id']}</td>
                        <td class="ls-end"><div class="ls-row-actions">{if $rt_can}<button type="button" class="ls-btn ls-btn--sm ls-btn--icon rt-more" data-rt-more="{$ds['id']}" aria-expanded="false" aria-controls="rt-detail-{$ds['id']}" aria-label="{Lang::T('Health details')}" title="{Lang::T('Health details')}"><i class="fa fa-chevron-down" aria-hidden="true"></i></button>{/if}<a href="{Text::url('routers/edit/', $ds['id'])}" class="ls-btn ls-btn--sm">{Lang::T('Edit')}</a>{if $rt_use['plans'] > 0 || $rt_use['active'] > 0}<button type="button" class="ls-btn ls-btn--sm ls-btn--icon ls-btn--danger" disabled aria-label="{Lang::T('Delete')}" title="{Lang::T('In use by')} {$rt_use['plans']} {Lang::T('plans')}, {$rt_use['active']} {Lang::T('active customers')}. {Lang::T('Move them to another router first, or disable this router instead.')}"><i class="fa fa-trash" aria-hidden="true"></i></button>{else}<form method="post" action="{Text::url('routers/delete/', $ds['id'])}" class="rt-del"><input type="hidden" name="csrf_token" value="{$csrf_token}"><button type="button" class="ls-btn ls-btn--sm ls-btn--icon ls-btn--danger" aria-label="{Lang::T('Delete')}" onclick="return ask(this, '{Lang::T('Delete')|escape:'javascript'} {$ds['name']|escape:'javascript'|escape}?')"><i class="fa fa-trash" aria-hidden="true"></i></button></form>{/if}</div></td>
                    </tr>
                    {if $rt_can}
                    <tr class="rt-detail" id="rt-detail-{$ds['id']}" hidden><td colspan="{$rt_cols}"><div class="rt-panel" data-rt-panel="{$ds['id']}"></div>
                        {if $_admin['user_type'] == 'SuperAdmin'}
                        <div class="rt-actions">
                            <span class="rt-actions__label">{Lang::T('On this router')}</span>
                            {foreach [
                                ['sync', 'fa-exchange', Lang::T('Sync customers'), Lang::T('Send every active customer of this router to it again?'), ''],
                                ['backup', 'fa-floppy-o', Lang::T('Save a backup'), Lang::T('Save a backup file on the router? It is kept in Files on the router.'), ''],
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
        function boxes() { return document.querySelectorAll('input[name="ids[]"][form="rtBulkForm"]'); }
        function sync() {
            var c = 0, total = 0;
            boxes().forEach(function (b) { total++; if (b.checked) c++; });
            n.textContent = c;
            bar.hidden = c === 0;
            all.checked = total > 0 && c === total;
            all.indeterminate = c > 0 && c < total;
        }
        all.addEventListener('change', function () {
            boxes().forEach(function (b) { b.checked = all.checked; });
            sync();
        });
        boxes().forEach(function (b) { b.addEventListener('change', sync); });
        var check = document.getElementById('rtCheckNow');
        if (check) check.addEventListener('click', function () {
            var ids = [];
            boxes().forEach(function (b) { if (b.checked) ids.push(b.value); });
            if (window.rtHealthRefresh) window.rtHealthRefresh(ids);
        });
    })();
</script>

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
            measuring: '{Lang::T('Measuring, the graph starts with the next readings')|escape:'javascript'}'
        }
    };
</script>
<script src="{$app_url}/ui/ui/scripts/routers-health.js?2026.11.18.4"></script>
{/if}

    <div class="bs-callout bs-callout-info" id="callout-navbar-role">
        <h4>{Lang::T('Check if Router Online?')}</h4>
        <p>{Lang::T('To check whether the Router is online or not, please visit the following page')} <a href="{Text::url('')}settings/miscellaneous#router_check" target="_blank" class="btn btn-link">{Lang::T('Cek Now')}</a></p>
    </div>
</div>

{include file="sections/footer.tpl"}
