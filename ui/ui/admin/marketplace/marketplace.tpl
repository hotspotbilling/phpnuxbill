{include file="sections/header.tpl"}

<div class="mp-wrap">
    {include file="admin/marketplace/_nav.tpl"}

    <section class="mp-hero mp-hero--hub">
        <div class="mp-hero__row">
            <div>
                <div class="mp-hero__eyebrow">
                    <i class="fa fa-shopping-bag"></i> {Lang::T('PHPNuxBill Extension Ecosystem')}
                </div>
                <h1>{Lang::T('Marketplace Hub')}</h1>
                <p>{Lang::T('Discover, install, and manage official and community plugins, payment gateways, themes, and device drivers.')}
                </p>
                <div class="mp-hero__actions">
                    <a href="{Text::url('marketplace/sync')}" class="mp-hero-btn">
                        <i class="fa fa-refresh"></i> {Lang::T('Sync & Check Updates')}
                    </a>
                    <span><i class="fa fa-clock"></i> {Lang::T('Last synced')}:
                        <strong>{$diagnostics.last_sync}</strong></span>
                    <span title="{Lang::T('Last update check')}"><i class="fa fa-history"></i> {$diagnostics.checked}</span>
                    <span>
                        {if $diagnostics.repo_online}<i class="fa fa-circle" style="color:#34d399;"></i>
                        {Lang::T('Repository available')} ({$stats.repo_total})
                        {else}<i class="fa fa-circle" style="color:#f87171;"></i> {Lang::T('Repository unreachable')}{/if}
                    </span>
                    {if $diagnostics.repo_stale}
                    <span class="mp-hero-warn"><i class="fa fa-exclamation-triangle"></i>
                        {Lang::T('Store data is over a week old. Sync to get the latest extensions.')}</span>
                    {/if}
                </div>
                <div class="mp-search">
                    <i class="fa fa-search"></i>
                    <input type="text" id="mp-hub-search" autocomplete="off"
                        placeholder="{Lang::T('Search all plugins, gateways, and devices...')}">
                </div>
            </div>
            <div class="mp-stats">
                <div class="mp-stat"><b>{$stats.total_extensions}</b><span>{Lang::T('Installed')}</span></div>
                <div class="mp-stat"><b>{$stats.plugins_active}</b><span>{Lang::T('Active Plugins')}</span></div>
                <div class="mp-stat"><b>{$stats.gateways_active}</b><span>{Lang::T('Active Gateways')}</span></div>
                <div class="mp-stat"><b>{$stats.devices}</b><span>{Lang::T('Device Drivers')}</span></div>
            </div>
        </div>
    </section>

    <section id="mp-results" class="mp-results" hidden>
        <div class="mp-section">
            <div>
                <h2>{Lang::T('Search Results')}</h2>
                <p id="mp-results-count"></p>
            </div>
            <button type="button" class="mp-filter" id="mp-results-clear"><i class="fa fa-times"></i>
                {Lang::T('Clear')}</button>
        </div>
        <div class="mp-grid" id="mp-results-grid"></div>
        <div class="mp-empty" id="mp-results-empty" hidden><i class="fa fa-search"></i>
            <h4>{Lang::T('No matching results')}</h4>
            <p>{Lang::T('Try a different search term or filter.')}</p>
        </div>
    </section>

    <div id="mp-hub-main">
    {if $pending_updates}
    <section class="mp-panel mp-panel--update">
        <div class="mp-panel__head">
            <div>
                <h2><i class="fa fa-arrow-circle-up"></i> {$pending_updates|@count} {Lang::T('update(s) available')}</h2>
                <p>{Lang::T('Each update backs up the current version first, so it can be rolled back from Tools.')}</p>
            </div>
            <form method="post" action="{Text::url('marketplace/updateall')}">
                <input type="hidden" name="csrf_token" value="{$csrf_token}">
                <button type="button" class="mp-filter is-active"
                    onclick="return ask(this, '{Lang::T('Update every extension listed? Each one is backed up first.')|escape:'javascript'}')">
                    <i class="fa fa-download"></i> {Lang::T('Update all')}</button>
            </form>
        </div>
        <ul class="mp-list">
            {foreach $pending_updates as $u}
            <li>
                <span class="mp-badge mp-badge--folder">{$u.type}</span>
                <a href="{Text::url('marketplace/')}{if $u.type eq 'plugin'}plugins{elseif $u.type eq 'gateway'}gateways{elseif $u.type eq 'device'}devices{else}themes{/if}">{$u.name}</a>
                <span class="mp-muted">{if $u.current}{$u.current}{else}?{/if} &rarr; {$u.latest}{if $u.basis eq 'estimated'} ({Lang::T('estimated')}){/if}</span>
            </li>
            {/foreach}
        </ul>
    </section>
    {/if}

    {if $problems}
    <section class="mp-panel mp-panel--error">
        <div class="mp-panel__head">
            <div>
                <h2><i class="fa fa-exclamation-triangle"></i> {$problems|@count} {Lang::T('extension(s) with problems')}</h2>
                <p>{Lang::T('These have a PHP error or failed to load. Fix, update, or restore a previous version from Tools.')}</p>
            </div>
            <a href="{Text::url('marketplace/tools')}" class="mp-filter"><i class="fa fa-wrench"></i> {Lang::T('Open Tools')}</a>
        </div>
        <ul class="mp-list">
            {foreach $problems as $p}
            <li>
                <span class="mp-badge mp-badge--legacy">{$p.type}</span>
                <a href="{Text::url('marketplace/')}{if $p.type eq 'plugin'}plugins{elseif $p.type eq 'gateway'}gateways{elseif $p.type eq 'device'}devices{else}themes{/if}">{$p.name}</a>
                <span class="mp-muted">{$p.message|escape}</span>
            </li>
            {/foreach}
        </ul>
    </section>
    {/if}

    <div class="mp-section">
        <div>
            <h2>{Lang::T('Extension Categories')}</h2>
            <p>{Lang::T('Browse modular subsystems to power up your internet billing operation.')}</p>
        </div>
    </div>

    <div class="mp-hub-grid">
        <article class="mp-hub-card">
            <div class="mp-hub-card__top">
                <div class="mp-hub-icon mp-hub-icon--plugins"><i class="fa fa-puzzle-piece"></i></div>
                <span class="mp-chip">{$stats.plugins_active} / {$stats.plugins} {Lang::T('active')}</span>
            </div>
            <div class="mp-hub-card__body">
                <h3>{Lang::T('Plugins & Addons')}</h3>
                <p>{Lang::T('Extend your billing system with WhatsApp notifications, customer portals, monitoring tools, and automated tasks.')}
                </p>
                <div class="mp-hub-card__metrics">
                    <span class="mp-badge mp-badge--folder">{$stats.plugins_folder} {Lang::T('Modular')}</span>
                    {if $stats.plugins_legacy > 0}
                    <span class="mp-badge mp-badge--legacy">{$stats.plugins_legacy} {Lang::T('Legacy')}</span>
                    {/if}
                    <span class="mp-status mp-status--active">{$stats.plugins_repo} {Lang::T('in Store')}</span>
                </div>
            </div>
            <div class="mp-hub-card__actions">
                <a href="{Text::url('marketplace/plugins')}" class="mp-act mp-act--primary">
                    <i class="fa fa-arrow-right"></i> {Lang::T('Manage Plugins')}
                </a>
            </div>
        </article>

        <article class="mp-hub-card">
            <div class="mp-hub-card__top">
                <div class="mp-hub-icon mp-hub-icon--gateways"><i class="fa fa-credit-card"></i></div>
                <span class="mp-chip{if $stats.gateways_active > 0} mp-chip--ok{/if}">
                    {$stats.gateways_active} {Lang::T('live checkout')}
                </span>
            </div>
            <div class="mp-hub-card__body">
                <h3>{Lang::T('Payment Gateways')}</h3>
                <p>{Lang::T('Accept credit cards, mobile wallets, and instant bank transfers via PayPal, Stripe, Paystack, Duitku, Tripay, and more.')}
                </p>
                <div class="mp-hub-card__metrics">
                    <span class="mp-badge mp-badge--gateway">{$stats.gateways} {Lang::T('Installed')}</span>
                    <span class="mp-status mp-status--active">{$stats.gateways_repo} {Lang::T('in Store')}</span>
                </div>
            </div>
            <div class="mp-hub-card__actions">
                <a href="{Text::url('marketplace/gateways')}" class="mp-act mp-act--success">
                    <i class="fa fa-arrow-right"></i> {Lang::T('Payment Gateways')}
                </a>
                <a href="{Text::url('paymentgateway')}" class="mp-act mp-act--icon"
                    title="{Lang::T('Checkout Settings')}">
                    <i class="fa fa-sliders"></i>
                </a>
            </div>
        </article>

        <article class="mp-hub-card">
            <div class="mp-hub-card__top">
                <div class="mp-hub-icon mp-hub-icon--themes"><i class="fa fa-paint-brush"></i></div>
                <span class="mp-chip">{Lang::T('Active')}: {$stats.active_theme}</span>
            </div>
            <div class="mp-hub-card__body">
                <h3>{Lang::T('Themes & UI Skins')}</h3>
                <p>{Lang::T('Customize the visual appearance and branding of the admin control panel and self-service customer portal.')}
                </p>
                <div class="mp-hub-card__metrics">
                    <span class="mp-badge mp-badge--theme">{$stats.themes + 1} {Lang::T('Themes')}</span>
                    <span class="mp-status mp-status--active"><i class="fa fa-check"></i>&nbsp;{Lang::T('Child Fallback')}</span>
                </div>
            </div>
            <div class="mp-hub-card__actions">
                <a href="{Text::url('marketplace/themes')}" class="mp-act mp-act--theme">
                    <i class="fa fa-arrow-right"></i> {Lang::T('Manage Themes')}
                </a>
            </div>
        </article>

        <article class="mp-hub-card">
            <div class="mp-hub-card__top">
                <div class="mp-hub-icon mp-hub-icon--devices"><i class="fa fa-server"></i></div>
                <span class="mp-chip">{$stats.devices} {Lang::T('drivers')}</span>
            </div>
            <div class="mp-hub-card__body">
                <h3>{Lang::T('Device Drivers')}</h3>
                <p>{Lang::T('Network hardware connectivity drivers powering MikroTik RouterOS API, FreeRADIUS AAA, Voucher, and custom devices.')}
                </p>
                <div class="mp-hub-card__metrics">
                    <span class="mp-badge mp-badge--mikrotik">MikroTik</span>
                    <span class="mp-badge mp-badge--radius">RADIUS</span>
                    <span class="mp-badge mp-badge--voucher">Voucher</span>
                    {if $stats.devices_repo > 0}
                    <span class="mp-status mp-status--active">{$stats.devices_repo} {Lang::T('in Store')}</span>
                    {/if}
                </div>
            </div>
            <div class="mp-hub-card__actions">
                <a href="{Text::url('marketplace/devices')}" class="mp-act mp-act--warn">
                    <i class="fa fa-arrow-right"></i> {Lang::T('View Devices')}
                </a>
            </div>
        </article>
    </div>

    {if $spotlight}
    <div class="mp-section">
        <div>
            <h2>{Lang::T('Featured Extensions from Repository')}</h2>
            <p>{Lang::T('Popular community and official extensions ready for one-click installation.')}</p>
        </div>
        <a href="{Text::url('marketplace/plugins')}" class="mp-link">
            {Lang::T('View All')} <i class="fa fa-angle-right"></i>
        </a>
    </div>

    <div class="mp-grid">
        {foreach $spotlight as $item}
        <article class="mp-card mp-card--available">
            <div class="mp-card__accent"
                {if $item.category_type eq 'Gateway'}style="background:#10b981;" {elseif $item.category_type eq 'Device'}style="background:#0ea5e9;"
                {/if}></div>
            <header class="mp-card__head">
                <div class="mp-card__title">
                    <h3>{$item.name}</h3>
                    <small>@{$item.author}{if $item.last_update} &middot; {$item.last_update}{/if}</small>
                </div>
                <div class="mp-card__tags">
                    {include file="admin/marketplace/_watch.tpl" x=$item}
                    <span class="mp-badge {$item.badge_class}">{$item.category_type}</span>
                    {if $item.ispaid}
                    <span class="mp-badge mp-badge--paid">{Lang::T('Premium')}</span>
                    {else}
                    <span class="mp-badge mp-badge--free">{Lang::T('Free')}</span>
                    {/if}
                </div>
            </header>
            <div class="mp-card__body">
                <p title="{$item.description|escape}">{$item.description}</p>
            </div>
            <footer class="mp-card__actions">
                {if $item.url}
                <a href="{$item.url}" target="_blank" rel="noopener noreferrer" class="mp-act"
                    title="{Lang::T('Provider Portal')}">
                    <i class="fa fa-external-link"></i> {Lang::T('Details')}
                </a>
                {/if}
                <a href="{$item.target_url}" class="mp-act mp-act--primary" title="{Lang::T('Browse Section')}">
                    <i class="fa fa-folderpen"></i> {Lang::T('Browse')}
                </a>
            </footer>
        </article>
        {/foreach}
    </div>
    {/if}

    </div>

    {if $watched}
    <div class="mp-section">
        <div>
            <h2><i class="fa fa-star mp-gold"></i> {Lang::T('Your Watchlist')}</h2>
            <p>{Lang::T('Store items you starred to come back to.')}</p>
        </div>
    </div>
    <div class="mp-grid">
        {foreach $watched as $item}
        <article class="mp-card mp-card--available">
            <div class="mp-card__accent"></div>
            <header class="mp-card__head">
                <div class="mp-card__title">
                    <h3>{$item.name}</h3>
                    <small>@{$item.author}{if $item.last_update} &middot; {$item.last_update}{/if}</small>
                </div>
                <div class="mp-card__tags">
                    {include file="admin/marketplace/_watch.tpl" x=$item}
                    <span class="mp-badge mp-badge--folder">{$item.category_type}</span>
                    {if $item.ispaid}<span class="mp-badge mp-badge--paid">{Lang::T('Premium')}</span>{else}<span class="mp-badge mp-badge--free">{Lang::T('Free')}</span>{/if}
                </div>
            </header>
            <div class="mp-card__body"><p title="{$item.description|escape}">{$item.description}</p></div>
            <footer class="mp-card__actions">
                <a href="{$item.target_url}" class="mp-act mp-act--primary"><i class="fa fa-folder-open"></i> {Lang::T('Browse')}</a>
            </footer>
        </article>
        {/foreach}
    </div>
    {/if}

    <div class="mp-diag-bar">
        <div class="mp-diag-bar__head">
            <strong><i class="fa fa-shield"></i>{Lang::T('Marketplace System Environment & Storage Health')}</strong>
            <span class="mp-chip">PHP {$diagnostics.php_version}</span>
        </div>

        <div class="mp-diag-grid">
            <div class="mp-diag-item">
                <span><i class="fa fa-file-archive"></i> ZIP Extension</span>
                {if $diagnostics.zip_enabled}<span class="badge badge-success">{Lang::T('Enabled')}</span>
                {else}<span class="badge badge-danger">{Lang::T('Disabled')}</span>{/if}
            </div>
            <div class="mp-diag-item">
                <span><i class="fa fa-globe"></i> cURL Library</span>
                {if $diagnostics.curl_enabled}<span class="badge badge-success">{Lang::T('Enabled')}</span>
                {else}<span class="badge badge-danger">{Lang::T('Disabled')}</span>{/if}
            </div>
            <div class="mp-diag-item">
                <span><i class="fa fa-folder"></i> plugin/</span>
                {if $diagnostics.plugin_writable}<span class="badge badge-success">{Lang::T('Writable')}</span>
                {else}<span class="badge badge-danger">{Lang::T('Read-Only')}</span>{/if}
            </div>
            <div class="mp-diag-item">
                <span><i class="fa fa-folder"></i> paymentgateway/</span>
                {if $diagnostics.gateway_writable}<span class="badge badge-success">{Lang::T('Writable')}</span>
                {else}<span class="badge badge-danger">{Lang::T('Read-Only')}</span>{/if}
            </div>
            <div class="mp-diag-item">
                <span><i class="fa fa-folder"></i> devices/</span>
                {if $diagnostics.devices_writable}<span class="badge badge-success">{Lang::T('Writable')}</span>
                {else}<span class="badge badge-danger">{Lang::T('Read-Only')}</span>{/if}
            </div>
            <div class="mp-diag-item">
                <span><i class="fa fa-folder"></i> ui/themes/</span>
                {if $diagnostics.themes_writable}<span class="badge badge-success">{Lang::T('Writable')}</span>
                {else}<span class="badge badge-danger">{Lang::T('Read-Only')}</span>{/if}
            </div>
            <div class="mp-diag-item">
                <span><i class="fa fa-folder"></i> cache/</span>
                {if $diagnostics.cache_writable}<span class="badge badge-success">{Lang::T('Writable')}</span>
                {else}<span class="badge badge-danger">{Lang::T('Read-Only')}</span>{/if}
            </div>
            <div class="mp-diag-item">
                <span><i class="fa fa-github"></i> {Lang::T('GitHub Token')}</span>
                {if $diagnostics.github_token_set}<span class="badge badge-success">{Lang::T('Set')}</span>
                {else}<span class="badge badge-default">{Lang::T('Not set')}</span>{/if}
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="mp-catalog">{$catalog_json nofilter}</script>
<script>
    (function () {
        var input = document.getElementById('mp-hub-search');
        var catalogEl = document.getElementById('mp-catalog');
        if (!input || !catalogEl) return;
        var catalog = [];
        try { catalog = JSON.parse(catalogEl.textContent); } catch (e) { }
        var main = document.getElementById('mp-hub-main');
        var panel = document.getElementById('mp-results');
        var grid = document.getElementById('mp-results-grid');
        var count = document.getElementById('mp-results-count');
        var none = document.getElementById('mp-results-empty');
        var routes = { plugin: 'plugins', gateway: 'gateways', device: 'devices', theme: 'themes' };
        var badge = { plugin: 'mp-badge--folder', gateway: 'mp-badge--gateway', device: 'mp-badge--device', theme: 'mp-badge--theme' };

        function el(tag, cls, text) {
            var n = document.createElement(tag);
            if (cls) n.className = cls;
            if (text !== undefined) n.textContent = text;
            return n;
        }
        function card(it) {
            var a = el('article', 'mp-card mp-card--' + (it.state === 'installed' ? 'active' : 'available') + ' mp-result');
            a.appendChild(el('div', 'mp-card__accent'));
            var head = el('header', 'mp-card__head');
            var title = el('div', 'mp-card__title');
            title.appendChild(el('h3', '', it.name));
            title.appendChild(el('small', '', it.author ? '@' + it.author : ''));
            var tags = el('div', 'mp-card__tags');
            tags.appendChild(el('span', 'mp-badge ' + badge[it.ctype], it.type));
            tags.appendChild(el('span', 'mp-badge ' + (it.state === 'installed' ? 'mp-badge--active' : (it.paid ? 'mp-badge--paid' : 'mp-badge--free')),
                it.state === 'installed' ? '{Lang::T('Installed')|escape:'javascript'}' : (it.paid ? '{Lang::T('Premium')|escape:'javascript'}' : '{Lang::T('Free')|escape:'javascript'}')));
            head.appendChild(title); head.appendChild(tags); a.appendChild(head);
            var body = el('div', 'mp-card__body'); body.appendChild(el('p', '', it.desc || '')); a.appendChild(body);
            var foot = el('footer', 'mp-card__actions');
            var browse = el('a', 'mp-act', '{Lang::T('Browse')|escape:'javascript'}');
            browse.href = it.url; foot.appendChild(browse);
            if (it.state === 'store' && MP.t.writable) {
                var f = el('form', 'mp-act-form'); f.method = 'post';
                f.action = MP.base + routes[it.ctype] + '/install/' + encodeURIComponent(it.id);
                var h = el('input'); h.type = 'hidden'; h.name = 'csrf_token'; h.value = MP.csrf; f.appendChild(h);
                var b = el('button', 'mp-act mp-act--primary'); b.type = 'button';
                var ic = el('i', 'fa fa-download'); b.appendChild(ic); b.appendChild(document.createTextNode(' ' + MP.t.install));
                b.addEventListener('click', function () { return ask(b, MP.t.confirmInstall); });
                f.appendChild(b); foot.appendChild(f);
            }
            a.appendChild(foot);
            return a;
        }
        function run() {
            var q = input.value.toLowerCase().trim();
            if (!q) { panel.hidden = true; main.hidden = false; return; }
            var words = q.split(/\s+/);
            var hits = catalog.filter(function (it) {
                var hay = (it.name + ' ' + it.desc + ' ' + it.author + ' ' + it.type).toLowerCase();
                return words.every(function (w) { return hay.indexOf(w) !== -1; });
            }).sort(function (a, b) { return (a.state === b.state ? 0 : a.state === 'installed' ? -1 : 1); });
            grid.textContent = '';
            hits.slice(0, 60).forEach(function (it) { grid.appendChild(card(it)); });
            count.textContent = hits.length + ' {Lang::T('results')|escape:'javascript'}';
            none.hidden = hits.length > 0;
            main.hidden = true; panel.hidden = false;
        }
        input.addEventListener('input', run);
        document.getElementById('mp-results-clear').addEventListener('click', function () { input.value = ''; run(); input.focus(); });
    })();
</script>

{include file="admin/marketplace/_scripts.tpl"}

{include file="sections/footer.tpl"}
