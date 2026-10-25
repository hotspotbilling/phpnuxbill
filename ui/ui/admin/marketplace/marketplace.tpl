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
                        <i class="fa fa-refresh"></i> {Lang::T('Sync Repository')}
                    </a>
                    <span><i class="fa fa-clock"></i> {Lang::T('Last synced')}:
                        <strong>{$diagnostics.last_sync}</strong></span>
                    <span>
                        {if $diagnostics.repo_online}<i class="fa fa-circle" style="color:#34d399;"></i>
                        {Lang::T('Repository available')} ({$stats.repo_total})
                        {else}<i class="fa fa-circle" style="color:#f87171;"></i> {Lang::T('Repository unreachable')}{/if}
                    </span>
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

{include file="sections/footer.tpl"}
