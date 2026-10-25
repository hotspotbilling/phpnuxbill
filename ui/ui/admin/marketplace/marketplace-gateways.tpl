{include file="sections/header.tpl"}

<div class="mp-wrap">
    {include file="admin/marketplace/_nav.tpl"}

    <section class="mp-hero mp-hero--gateways">
        <div class="mp-hero__row">
            <div>
                <div class="mp-hero__crumb">
                    <a href="{Text::url('marketplace')}"><i class="fa fa-arrow-left"></i> {Lang::T('Marketplace')}</a>
                    <span>&middot;</span>
                    <b>{Lang::T('Payment Gateways')}</b>
                </div>
                <h1>{Lang::T('Payment Gateways')}</h1>
                <p>{Lang::T('Install, configure and manage payment processors for customer checkout and wallet top-ups.')}</p>
                <div class="mp-search">
                    <i class="fa fa-search"></i>
                    <input type="text" data-mp-search
                        placeholder="{Lang::T('Search payment gateways by name, country, or provider...')}">
                </div>
            </div>
            <div class="mp-stats">
                <div class="mp-stat"><b>{$stats.total}</b><span>{Lang::T('Installed')}</span></div>
                <div class="mp-stat"><b>{$stats.active}</b><span>{Lang::T('Active')}</span></div>
                <div class="mp-stat"><b>{$stats.updates}</b><span>{Lang::T('Updates')}</span></div>
                <div class="mp-stat"><b>{$stats.available}</b><span>{Lang::T('In Store')}</span></div>
            </div>
        </div>
    </section>

    <div class="mp-section">
        <div>
            <h2>{Lang::T('Installed Payment Gateways')}</h2>
            <p>{Lang::T('Gateways active here will be offered to customers during invoice payment.')}</p>
        </div>
        <div class="mp-toolbar">
            <a href="{Text::url('paymentgateway')}" class="mp-filter"><i class="fa fa-sliders"></i> {Lang::T('Checkout Settings')}</a>
            <a href="{Text::url('marketplace/sync&from=gateways')}" class="mp-filter"
                title="{Lang::T('Refresh repository and check for updates')}"><i class="fa fa-refresh"></i> {Lang::T('Sync Store')}</a>
            <button class="mp-filter" id="toggle-upload" type="button"><i class="fa fa-upload"></i> {Lang::T('Upload Gateway')}</button>
        </div>
    </div>

    {include file="admin/marketplace/_upload.tpl"
        up_title=Lang::T('Add New Payment Gateway')
        up_hint=Lang::T('Upload a driver (.php file or ZIP package) or install directly from a GitHub repository. Folder-style packages (gateway.json) and single-file drivers are both supported.')
        up_action="{Text::url('marketplace/gateways/upload')}"
        up_file_name='gateway_file'
        up_file_label=Lang::T('Driver File (.php or .zip)')
        up_accept='.php,.zip'
        up_gh=true
        up_disabled=!$writable}

    <div class="mp-filters">
        <button class="mp-filter is-active" data-filter="all">{Lang::T('All Gateways')} <b>{$stats.total + $stats.available}</b></button>
        <button class="mp-filter" data-filter="installed">{Lang::T('Installed')} <b>{$stats.total}</b></button>
        <button class="mp-filter" data-filter="active">{Lang::T('Active in Checkout')} <b>{$stats.active}</b></button>
        <button class="mp-filter" data-filter="available">{Lang::T('Available to Install')} <b>{$stats.available}</b></button>
        <button class="mp-filter" data-filter="updates">{Lang::T('Updates')} <b>{$stats.updates}</b></button>
        <button class="mp-filter" data-filter="errors">{Lang::T('Issues')} <b>{$stats.errors}</b></button>
        <button class="mp-filter" data-filter="free">{Lang::T('Free')}</button>
        <button class="mp-filter" data-filter="paid">{Lang::T('Premium')}</button>
        <button class="mp-filter" data-filter="new">{Lang::T('New')}</button>
        <button class="mp-filter" data-filter="watch"><i class="fa fa-star"></i> {Lang::T('Watchlist')} <b>{$stats.watched}</b></button>
        <button class="mp-filter" data-filter="indonesia">{Lang::T('Indonesia')}</button>
        <button class="mp-filter" data-filter="africa">{Lang::T('Africa')}</button>
        <button class="mp-filter" data-filter="india">{Lang::T('India')}</button>
        <button class="mp-filter" data-filter="global">{Lang::T('Global')}</button>
        <label class="mp-sort"><i class="fa fa-sort-amount-asc"></i>
            <select data-mp-sort>
                <option value="default">{Lang::T('Sort: Default')}</option>
                <option value="name">{Lang::T('Sort: Name (A-Z)')}</option>
                <option value="newest">{Lang::T('Sort: Newest')}</option>
            </select>
        </label>
    </div>

    {if $gateways}
    <div class="mp-grid" id="installed-grid">
        {foreach $gateways as $g}
        <article class="mp-card mp-card--{if $g.active}active{else}inactive{/if}"
            data-tags="installed {if $g.active}active{else}inactive{/if} {$g.region|lower|replace:' ':''}{if $g.update.available} updates{/if}{if $g.health.status eq 'error'} errors{/if}"
            data-search="{$g.name|lower} {$g.slug|lower} {$g.region|lower} {$g.author|lower}" data-info="{$g.info_json|escape}">
            <div class="mp-card__accent"
                {if !$g.active}style="background:{if $g.configured}#0ea5e9{else}#94a3b8{/if};" {/if}></div>
            <header class="mp-card__head">
                <div class="mp-card__title">
                    <h3>{$g.name}</h3>
                    <small>{$g.slug} &middot; @{$g.author}</small>
                </div>
                <div class="mp-card__tags">
                    <span class="mp-badge mp-badge--{$g.region|lower|replace:' ':''}">{$g.region}</span>
                    {if $g.active}
                    <span class="mp-badge mp-badge--active"><i class="fa fa-check-circle"></i> {Lang::T('Active')}</span>
                    {else}
                    <span class="mp-badge mp-badge--inactive">{Lang::T('Inactive')}</span>
                    {/if}
                </div>
            </header>
            <div class="mp-card__body">
                <p title="{$g.description|escape}">{$g.description}</p>
            </div>
            <div class="mp-card__meta">
                <span class="mp-chip"><i class="fa fa-hdd"></i>&nbsp;{$g.file_size}</span>
                {if $g.configured}
                <span class="mp-status mp-status--active"><i class="fa fa-cog"></i>&nbsp;{Lang::T('Configured')}</span>
                {else}
                <span class="mp-status mp-status--inactive"><i class="fa fa-exclamation-circle"></i>&nbsp;{Lang::T('Not Configured')}</span>
                {/if}
                {if $g.has_ui}
                <span class="mp-chip" title="{Lang::T('Custom Smarty UI template present')}"><i class="fa fa-television"></i>&nbsp;UI</span>
                {/if}
                {include file="admin/marketplace/_status.tpl" c=$g}
            </div>
            <footer class="mp-card__actions">
                <form method="post" action="{Text::url('marketplace/gateways/toggle/', $g.slug)}" class="mp-act-form">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    {if $g.active}
                    <button type="submit" class="mp-act mp-act--warn" title="{Lang::T('Pause in customer checkout')}"><i class="fa fa-pause"></i> {Lang::T('Pause')}</button>
                    {else}
                    <button type="submit" class="mp-act mp-act--success" title="{Lang::T('Enable in customer checkout')}"><i class="fa fa-play"></i> {Lang::T('Enable')}</button>
                    {/if}
                </form>
                <a href="{Text::url('paymentgateway/', $g.slug)}" class="mp-act mp-act--primary" title="{Lang::T('Configure API Keys and Settings')}"><i class="fa fa-cog"></i> {Lang::T('Config')}</a>
                <a href="{Text::url('paymentgateway/audit/', $g.slug)}" class="mp-act" title="{Lang::T('View Transaction Audit Logs')}"><i class="fa fa-list"></i> {Lang::T('Audit')}</a>
                <form method="post" action="{Text::url('marketplace/gateways/uninstall/', $g.slug)}" class="mp-act-form mp-act-form--icon">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <button type="button" class="mp-act mp-act--danger mp-act--icon" {if !$writable}disabled{/if}
                        onclick="return ask(this, '{Lang::T('Uninstall and remove this payment gateway driver? A backup is saved first.')|escape:'javascript'}')"
                        title="{Lang::T('Delete driver')}"><i class="fa fa-trash"></i></button>
                </form>
            </footer>
            {include file="admin/marketplace/_tools.tpl" c=$g route='gateways' can_convert=($g.style eq 'flat') disabled=!$writable}
        </article>
        {/foreach}
    </div>
    {else}
    <div class="mp-empty">
        <i class="fa fa-credit-card"></i>
        <h4>{Lang::T('No payment gateways currently installed')}</h4>
        <p>{Lang::T('Choose a payment gateway from the repository below or upload a driver file to get started.')}</p>
    </div>
    {/if}

    {if $available}
    <div class="mp-section" data-mp-section>
        <div>
            <h2>{Lang::T('Discover & Install Payment Gateways')}</h2>
            <p>{Lang::T('Ready-to-use payment integrations from the repository.')}</p>
        </div>
        <span class="mp-chip">{$available|@count} {Lang::T('available in store')}</span>
    </div>

    <div class="mp-grid" id="available-grid" data-sortable>
        {foreach $available as $rg}
        <article class="mp-card mp-card--available"
            data-tags="available {if $rg.ispaid}paid{else}free{/if} {$rg.region|lower|replace:' ':''}{if $rg.watched} watch{/if}"
            data-date="{$rg.last_update}" data-search="{$rg.name|lower} {$rg.id|lower} {$rg.region|lower} {$rg.author|lower}"
            data-info="{$rg.info_json|escape}">
            <div class="mp-card__accent"></div>
            <header class="mp-card__head">
                <div class="mp-card__title">
                    <h3>{$rg.name}</h3>
                    <small>@{$rg.author}{if $rg.last_update} &middot; {$rg.last_update}{/if}</small>
                </div>
                <div class="mp-card__tags">
                    {include file="admin/marketplace/_watch.tpl" x=$rg}
                    <span class="mp-badge mp-badge--{$rg.region|lower|replace:' ':''}">{$rg.region}</span>
                    {if $rg.ispaid}
                    <span class="mp-badge mp-badge--paid">{Lang::T('Premium')}</span>
                    {else}
                    <span class="mp-badge mp-badge--free">{Lang::T('Free')}</span>
                    {/if}
                </div>
            </header>
            <div class="mp-card__body">
                <p title="{$rg.description|escape}">{$rg.description}</p>
            </div>
            <footer class="mp-card__actions">
                {include file="admin/marketplace/_store_actions.tpl" x=$rg route='gateways' disabled=!$writable}
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
        <h3><i class="fa fa-code"></i> {Lang::T('Payment Gateway Architecture & Custom Drivers')}</h3>
        <p>{Lang::T('A gateway is either one PHP driver in system/paymentgateway/ (with templates in system/paymentgateway/ui/) or a folder system/paymentgateway/<name>/ holding gateway.json, <name>.php and an optional ui/ folder. Both layouts work; folder style keeps everything together and supports versioning, requirements and updates. Drivers implement {gateway}_validate_config(), {gateway}_show_config(), {gateway}_save_config(), {gateway}_create_transaction() and {gateway}_payment_notification(). Templates are loaded with "pg:name.tpl".')}</p>
    </section>
</div>

{include file="admin/marketplace/_drawer.tpl"}
{include file="admin/marketplace/_scripts.tpl" route='gateways'}

{include file="sections/footer.tpl"}
