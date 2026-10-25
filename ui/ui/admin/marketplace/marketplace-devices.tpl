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
                <div class="mp-stat"><b>{$stats.categories.Mikrotik|default:0}</b><span>{Lang::T('Mikrotik')}</span></div>
                <div class="mp-stat"><b>{$stats.categories.Radius|default:0}</b><span>{Lang::T('Radius')}</span></div>
                <div class="mp-stat"><b>{$stats.updates}</b><span>{Lang::T('Updates')}</span></div>
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
                title="{Lang::T('Refresh repository and check for updates')}"><i class="fa fa-refresh"></i> {Lang::T('Sync Store')}</a>
            <button class="mp-filter" id="toggle-upload" type="button"><i class="fa fa-upload"></i> {Lang::T('Upload Driver')}</button>
        </div>
    </div>

    {include file="admin/marketplace/_upload.tpl"
        up_title=Lang::T('Upload a Device Driver')
        up_hint=Lang::T('Upload a ZIP package (a device/ folder, a folder with device.json, or a <Class>.php driver), a single .php driver, or install from GitHub.')
        up_action="{Text::url('marketplace/devices/upload')}"
        up_file_name='zip_device'
        up_file_label=Lang::T('Driver Package (.zip or .php)')
        up_accept='application/zip,.zip,.php'
        up_gh=true
        up_disabled=!$writable}

    <div class="mp-filters">
        <button class="mp-filter is-active" data-filter="all">{Lang::T('All')}</button>
        <button class="mp-filter" data-filter="installed">{Lang::T('Installed')} <b>{$stats.total}</b></button>
        <button class="mp-filter" data-filter="mikrotik">{Lang::T('Mikrotik')}</button>
        <button class="mp-filter" data-filter="radius">{Lang::T('Radius')}</button>
        <button class="mp-filter" data-filter="voucher">{Lang::T('Voucher')}</button>
        <button class="mp-filter" data-filter="updates">{Lang::T('Updates')} <b>{$stats.updates}</b></button>
        <button class="mp-filter" data-filter="errors">{Lang::T('Issues')} <b>{$stats.errors}</b></button>
        {if $available}
        <button class="mp-filter" data-filter="available">{Lang::T('Available to Install')} <b>{$available|@count}</b></button>
        <button class="mp-filter" data-filter="free">{Lang::T('Free')}</button>
        <button class="mp-filter" data-filter="paid">{Lang::T('Premium')}</button>
        <button class="mp-filter" data-filter="new">{Lang::T('New')}</button>
        <button class="mp-filter" data-filter="watch"><i class="fa fa-star"></i> {Lang::T('Watchlist')} <b>{$stats.watched}</b></button>
        <label class="mp-sort"><i class="fa fa-sort-amount-asc"></i>
            <select data-mp-sort>
                <option value="default">{Lang::T('Sort: Default')}</option>
                <option value="name">{Lang::T('Sort: Name (A-Z)')}</option>
                <option value="newest">{Lang::T('Sort: Newest')}</option>
            </select>
        </label>
        {/if}
    </div>

    {if $devices}
    <div class="mp-grid">
        {foreach $devices as $device}
        <article class="mp-card mp-card--active"
            data-tags="installed {$device.category|lower|escape}{if $device.update.available} updates{/if}{if $device.health.status eq 'error'} errors{/if}"
            data-search="{$device.name|lower|escape} {$device.slug|lower|escape} {$device.category|lower|escape} {$device.author|lower|escape}"
            data-info="{$device.info_json|escape}">
            <div class="mp-card__accent"></div>
            <header class="mp-card__head">
                <div class="mp-card__title">
                    <h3>{$device.name|escape}</h3>
                    <small>{$device.slug|escape}{if $device.author} &middot; @{$device.author|escape}{/if}</small>
                </div>
                <div class="mp-card__tags">
                    <span class="mp-badge mp-badge--{$device.category|lower|escape}">{$device.category|escape}</span>
                    {if $device.core}<span class="mp-badge mp-badge--theme">{Lang::T('Core')}</span>{/if}
                </div>
            </header>
            <div class="mp-card__body">
                <p>{if $device.description}{$device.description|escape}{else}{Lang::T('No description available.')}{/if}</p>
            </div>
            <div class="mp-card__meta">
                <span class="mp-chip"><i class="fa fa-hdd-o"></i>&nbsp;{$device.size|escape}</span>
                {if $device.plans > 0}<span class="mp-chip" title="{Lang::T('Plans using this driver')}"><i class="fa fa-list"></i>&nbsp;{$device.plans} {Lang::T('plans')}</span>{/if}
                {include file="admin/marketplace/_status.tpl" c=$device}
            </div>
            <footer class="mp-card__actions">
                <button type="button" class="mp-act" data-details><i class="fa fa-info-circle"></i> {Lang::T('Details')}</button>
                {if !$device.core}
                <form method="post" action="{Text::url('marketplace/devices/uninstall/', $device.slug)}" class="mp-act-form mp-act-form--icon">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <button type="button" class="mp-act mp-act--danger" {if !$writable || $device.plans > 0}disabled{/if}
                        title="{if $device.plans > 0}{Lang::T('Plans still use this driver')}{else}{Lang::T('Uninstall')}{/if}"
                        onclick="return ask(this, '{Lang::T('Uninstall this device? A backup is saved first.')|escape:'javascript'}')">
                        <i class="fa fa-trash"></i> {Lang::T('Uninstall')}</button>
                </form>
                {/if}
            </footer>
            {include file="admin/marketplace/_tools.tpl" c=$device route='devices' can_convert=($device.style eq 'flat' && !$device.core) disabled=!$writable}
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
            <p>{Lang::T('More device drivers available from the repository.')}</p>
        </div>
        <span class="mp-chip">{$available|@count} {Lang::T('available')}</span>
    </div>

    <div class="mp-grid" data-sortable>
        {foreach $available as $d}
        <article class="mp-card mp-card--available" data-tags="available {if $d.ispaid}paid{else}free{/if}{if $d.watched} watch{/if}"
            data-date="{$d.last_update|escape}" data-search="{$d.name|lower|escape} {$d.id|lower|escape} {$d.author|lower|escape}" data-info="{$d.info_json|escape}">
            <div class="mp-card__accent"></div>
            <header class="mp-card__head">
                <div class="mp-card__title">
                    <h3>{$d.name|escape}</h3>
                    <small>@{$d.author|escape}{if $d.last_update} &middot; {$d.last_update|escape}{/if}</small>
                </div>
                <div class="mp-card__tags">
                    {include file="admin/marketplace/_watch.tpl" x=$d}
                    {if $d.ispaid}
                    <span class="mp-badge mp-badge--paid">{Lang::T('Premium')}</span>
                    {else}
                    <span class="mp-badge mp-badge--free">{Lang::T('Free')}</span>
                    {/if}
                </div>
            </header>
            <div class="mp-card__body">
                <p title="{$d.description|escape}">{$d.description|escape}</p>
            </div>
            <footer class="mp-card__actions">
                {include file="admin/marketplace/_store_actions.tpl" x=$d route='devices' disabled=!$writable}
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
        <p>{Lang::T('A driver is either one file system/devices/<Class>.php or a folder system/devices/<Class>/ with device.json and <Class>.php. Both work. Drivers implement the device interface (add_customer, add_plan, connect_customer, etc.). Core drivers stay as single files and cannot be removed; a driver that plans still use cannot be uninstalled.')}</p>
    </section>
</div>

{include file="admin/marketplace/_drawer.tpl"}
{include file="admin/marketplace/_scripts.tpl" route='devices'}

{include file="sections/footer.tpl"}
