{include file="sections/header.tpl"}

<div class="mp-wrap">
    {include file="admin/marketplace/_nav.tpl"}

    <section class="mp-hero mp-hero--devices">
        <div class="mp-hero__row">
            <div>
                <div class="mp-hero__crumb">
                    <a href="{Text::url('marketplace')}"><i class="fa fa-arrow-left"></i> {Lang::T('Marketplace')}</a>
                    <span>&middot;</span>
                    <b>{Lang::T('Device Drivers')}</b>
                </div>
                <h1>{Lang::T('Device Drivers')}</h1>
                <p>{Lang::T('Connectivity drivers that power your billing system.')}</p>
                <div class="mp-search">
                    <i class="fa fa-search"></i>
                    <input type="text" data-mp-search placeholder="{Lang::T('Search devices...')}">
                </div>
            </div>
            <div class="mp-stats">
                <div class="mp-stat"><b>{$stats.total}</b><span>{Lang::T('Drivers')}</span></div>
                <div class="mp-stat"><b>{$stats.categories.Mikrotik|default:0}</b><span>{Lang::T('Mikrotik')}</span>
                </div>
                <div class="mp-stat"><b>{$stats.categories.Radius|default:0}</b><span>{Lang::T('Radius')}</span></div>
                <div class="mp-stat"><b>{$stats.categories.Voucher|default:0}</b><span>{Lang::T('Voucher')}</span></div>
            </div>
        </div>
    </section>

    <div class="mp-section">
        <div>
            <h2>{Lang::T('Installed Drivers')}</h2>
            <p>{Lang::T('Installed drivers for Mikrotik, Radius, Voucher and more.')}</p>
        </div>
        <div class="mp-toolbar">
            <span class="mp-chip">{$stats.total} {Lang::T('installed')}</span>
            <a href="{Text::url('marketplace/sync&from=devices')}" class="mp-filter"
                title="{Lang::T('Refresh repository')}"><i class="fa fa-refresh"></i> {Lang::T('Sync Store')}</a>
            <button class="mp-filter" id="toggle-upload" type="button"><i class="fa fa-upload"></i>
                {Lang::T('Upload Driver')}</button>
        </div>
    </div>

    {include file="admin/marketplace/_upload.tpl"
        up_title=Lang::T('Upload a Device Driver')
        up_hint=Lang::T('Upload a ZIP package containing a "device/" folder.')
        up_action="{Text::url('marketplace/devices/upload')}"
        up_file_name='zip_device'
        up_file_label=Lang::T('Driver Package (.zip)')
        up_accept='application/zip,.zip'
        up_gh=true}

    <div class="mp-filters">
        <button class="mp-filter is-active" data-filter="all">{Lang::T('All')}</button>
        <button class="mp-filter" data-filter="installed">{Lang::T('Installed')} <b>{$stats.total}</b></button>
        <button class="mp-filter" data-filter="mikrotik">{Lang::T('Mikrotik')}</button>
        <button class="mp-filter" data-filter="radius">{Lang::T('Radius')}</button>
        <button class="mp-filter" data-filter="voucher">{Lang::T('Voucher')}</button>
        {if $available}
        <button class="mp-filter" data-filter="available">{Lang::T('Available to Install')}
            <b>{$available|@count}</b></button>
        {/if}
    </div>

    {if $devices}
    <div class="mp-grid">
        {foreach $devices as $device}
        <article class="mp-card mp-card--active" data-tags="installed {$device.category|lower}"
            data-search="{$device.name|lower} {$device.slug|lower} {$device.category|lower} {$device.author|lower}">
            <div class="mp-card__accent"></div>
            <header class="mp-card__head">
                <div class="mp-card__title">
                    <h3>{$device.name}</h3>
                    <small>{$device.slug}{if $device.author} &middot; @{$device.author}{/if}</small>
                </div>
                <div class="mp-card__tags">
                    <span class="mp-badge mp-badge--{$device.category|lower}">{$device.category}</span>
                </div>
            </header>
            <div class="mp-card__body">
                <p>{if $device.description}{$device.description}{else}{Lang::T('No description available.')}{/if}</p>
            </div>
            <footer class="mp-card__actions">
                <span class="mp-chip"><i class="fa fa-hdd"></i>&nbsp;{$device.size}</span>
                <form method="post" action="{Text::url('marketplace/devices/uninstall/', $device.slug)}"
                    class="mp-act-form mp-act-form--icon">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <button type="button" class="mp-act mp-act--danger mp-act--icon"
                        onclick="return ask(this, '{Lang::T('Uninstall this device?')}')"
                        title="{Lang::T('Uninstall')}">
                        <i class="fa fa-trash"></i> {Lang::T('Uninstall')}</button>
                </form>
            </footer>
        </article>
        {/foreach}
    </div>
    {else}
    <div class="mp-empty"><i class="fa fa-server"></i>
        <h4>{Lang::T('No device drivers found.')}</h4>
        <p>{Lang::T('Install one from the store below or upload a package.')}</p>
    </div>
    {/if}

    {if $available}
    <div class="mp-section" data-mp-section>
        <div>
            <h2>{Lang::T('Discover Devices')}</h2>
            <p>{Lang::T('More device drivers available from the official repository.')}</p>
        </div>
        <span class="mp-chip">{$available|@count} {Lang::T('available')}</span>
    </div>

    <div class="mp-grid">
        {foreach $available as $d}
        <article class="mp-card mp-card--available" data-tags="available {if $d.ispaid}paid{else}free{/if}"
            data-search="{$d.name|lower} {$d.id|lower} {$d.author|lower}">
            <div class="mp-card__accent"></div>
            <header class="mp-card__head">
                <div class="mp-card__title">
                    <h3>{$d.name}</h3>
                    <small>@{$d.author}{if $d.last_update} &middot; {$d.last_update}{/if}</small>
                </div>
                <div class="mp-card__tags">
                    {if $d.ispaid}
                    <span class="mp-badge mp-badge--paid">{Lang::T('Premium')}</span>
                    {else}
                    <span class="mp-badge mp-badge--free">{Lang::T('Free')}</span>
                    {/if}
                </div>
            </header>
            <div class="mp-card__body">
                <p title="{$d.description|escape}">{$d.description}</p>
            </div>
            <footer class="mp-card__actions">
                {if $d.url}
                <a href="{$d.url}" target="_blank" rel="noopener noreferrer" class="mp-act"
                    title="{Lang::T('Source')}"><i class="fa fa-github"></i> {Lang::T('Source')}</a>
                {/if}
                <form method="post" action="{Text::url('marketplace/devices/install/', $d.id)}" class="mp-act-form">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <button type="button" class="mp-act mp-act--primary"
                        onclick="return ask(this, '{Lang::T('Install this device? This may take some time.')}')"
                        title="{Lang::T('Install')}">
                        <i class="fa fa-download"></i> {Lang::T('Install')}</button>
                </form>
            </footer>
        </article>
        {/foreach}
    </div>
    {/if}

    <div class="mp-empty mp-noresults" id="mp-noresults"><i class="fa fa-search"></i>
        <h4>{Lang::T('No matching results')}</h4>
        <p>{Lang::T('Try a different search term or filter.')}</p>
    </div>

    <section class="mp-install mp-install--info">
        <h3><i class="fa fa-info-circle"></i> {Lang::T('About Devices')}</h3>
        <p>{Lang::T('Device drivers live in system/devices/ and implement the device interface (add_customer, add_plan, connect_customer, etc.).')}
        </p>
    </section>
</div>

{include file="admin/marketplace/_scripts.tpl"}

{include file="sections/footer.tpl"}
