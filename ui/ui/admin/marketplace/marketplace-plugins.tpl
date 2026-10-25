{include file="sections/header.tpl"}

<div class="mp-wrap">
    {include file="admin/marketplace/_nav.tpl"}

    <section class="mp-hero">
        <div class="mp-hero__row">
            <div>
                <div class="mp-hero__crumb">
                    <a href="{Text::url('marketplace')}"><i class="fa fa-arrow-left"></i> {Lang::T('Marketplace')}</a>
                    <span>&middot;</span>
                    <b>{Lang::T('Plugins & Addons')}</b>
                </div>
                <h1>{Lang::T('Plugin Marketplace')}</h1>
                <p>{Lang::T('Discover, activate and manage extensions for your billing system.')}</p>
                <div class="mp-search">
                    <i class="fa fa-search"></i>
                    <input type="text" data-mp-search placeholder="{Lang::T('Search plugins...')}">
                </div>
            </div>
            <div class="mp-stats">
                <div class="mp-stat"><b>{$stats.total}</b><span>{Lang::T('Total')}</span></div>
                <div class="mp-stat"><b>{$stats.active}</b><span>{Lang::T('Active')}</span></div>
                <div class="mp-stat"><b>{$stats.updates}</b><span>{Lang::T('Updates')}</span></div>
                <div class="mp-stat"><b>{$stats.errors}</b><span>{Lang::T('Issues')}</span></div>
            </div>
        </div>
    </section>

    <div class="mp-section">
        <div>
            <h2>{Lang::T('Installed Plugins')}</h2>
            <p>{Lang::T('Manage plugins currently installed on your system.')}</p>
        </div>
        <div class="mp-toolbar">
            <span class="mp-chip">{$stats.total} {Lang::T('installed')}</span>
            <a href="{Text::url('marketplace/sync&from=plugins')}" class="mp-filter"
                title="{Lang::T('Refresh repository and check for updates')}"><i class="fa fa-refresh"></i> {Lang::T('Sync Store')}</a>
            <button class="mp-filter" id="toggle-upload" type="button"><i class="fa fa-upload"></i>
                {Lang::T('Upload Plugin')}</button>
        </div>
    </div>

    {include file="admin/marketplace/_upload.tpl"
        up_title=Lang::T('Install a new plugin')
        up_hint=Lang::T('Upload a ZIP package or paste a GitHub repository URL. The package is checked and any existing copy is backed up first.')
        up_action="{Text::url('marketplace/plugins/upload')}"
        up_file_name='zip_plugin'
        up_file_label=Lang::T('Plugin Package (.zip)')
        up_accept='application/zip,.zip'
        up_gh=true
        up_enabled=$zipExt
        up_disabled=!$writable}

    <div class="mp-filters">
        <button class="mp-filter is-active" data-filter="all">{Lang::T('All')} <b>{$stats.total}</b></button>
        <button class="mp-filter" data-filter="folder">{Lang::T('Folder')} <b>{$stats.folder}</b></button>
        <button class="mp-filter" data-filter="active">{Lang::T('Active')} <b>{$stats.active}</b></button>
        <button class="mp-filter" data-filter="inactive">{Lang::T('Inactive')} <b>{$stats.inactive}</b></button>
        <button class="mp-filter" data-filter="legacy">{Lang::T('Legacy')} <b>{$stats.legacy}</b></button>
        <button class="mp-filter" data-filter="updates">{Lang::T('Updates')} <b>{$stats.updates}</b></button>
        <button class="mp-filter" data-filter="errors">{Lang::T('Issues')} <b>{$stats.errors}</b></button>
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
    </div>

    {if $plugins}
    <div class="mp-grid" id="plugin-grid">
        {foreach $plugins as $plugin}
        <article
            class="mp-card mp-card--{if $plugin.active}active{else}inactive{/if}{if $plugin.type eq 'legacy'} mp-card--legacy{/if}"
            data-tags="{$plugin.type|escape} {if $plugin.active}active{else}inactive{/if}{if $plugin.update.available} updates{/if}{if $plugin.health.status eq 'error'} errors{/if}"
            data-search="{$plugin.name|lower|escape} {$plugin.slug|lower|escape}" data-info="{$plugin.info_json|escape}">
            <div class="mp-card__accent"></div>
            <header class="mp-card__head">
                <div class="mp-card__title">
                    <h3>{$plugin.name|escape}</h3>
                    <small>{$plugin.slug|escape}</small>
                </div>
                <div class="mp-card__tags">
                    {if $plugin.type eq 'folder'}
                    <span class="mp-badge mp-badge--folder">{Lang::T('Installed')}</span>
                    {else}
                    <span class="mp-badge mp-badge--legacy">{Lang::T('Legacy')}</span>
                    {/if}
                </div>
            </header>
            <div class="mp-card__body">
                <p>{if $plugin.description}{$plugin.description|escape}{else}{Lang::T('No description provided.')}{/if}</p>
            </div>
            <div class="mp-card__meta">
                {if $plugin.version}<span class="mp-chip">v{$plugin.version|escape}</span>{/if}
                {if $plugin.active}
                <span class="mp-status mp-status--active"><span class="mp-dot mp-dot--active"></span>{Lang::T('Active')}</span>
                {else}
                <span class="mp-status mp-status--inactive"><span class="mp-dot mp-dot--inactive"></span>{Lang::T('Inactive')}</span>
                {/if}
                {include file="admin/marketplace/_status.tpl" c=$plugin}
            </div>
            <footer class="mp-card__actions">
                {* legacy flat plugins always load; they cannot be paused or activated, only converted or removed *}
                {if $plugin.type neq 'legacy'}
                {if $plugin.active}
                <form method="post" action="{Text::url('marketplace/plugins/deactivate/', $plugin.slug)}" class="mp-act-form">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <button type="button" class="mp-act mp-act--warn"
                        onclick="return ask(this, '{Lang::T('Deactivate this plugin?')|escape:'javascript'}')"
                        title="{Lang::T('Deactivate')}"><i class="fa fa-pause"></i> {Lang::T('Pause')}</button>
                </form>
                {else}
                <form method="post" action="{Text::url('marketplace/plugins/activate/', $plugin.slug)}" class="mp-act-form">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <button type="submit" class="mp-act mp-act--success" title="{Lang::T('Activate')}"><i class="fa fa-play"></i> {Lang::T('Activate')}</button>
                </form>
                {/if}
                {/if}
                <form method="post" action="{Text::url('marketplace/plugins/uninstall/', $plugin.slug)}" class="mp-act-form">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <button type="button" class="mp-act mp-act--danger" {if !$writable}disabled{/if}
                        onclick="return ask(this, '{Lang::T('Uninstall this plugin? A backup is saved first, so it can be restored from Tools.')|escape:'javascript'}')"
                        title="{Lang::T('Uninstall')}"><i class="fa fa-trash"></i> {Lang::T('Uninstall')}</button>
                </form>
            </footer>
            {include file="admin/marketplace/_tools.tpl" c=$plugin route='plugins' can_convert=($plugin.type eq 'legacy') disabled=!$writable}
        </article>
        {/foreach}
    </div>
    {else}
    <div class="mp-empty"><i class="fa fa-puzzle-piece"></i>
        <h4>{Lang::T('No plugins found.')}</h4>
        <p>{Lang::T('Install one from the store below or upload a package.')}</p>
    </div>
    {/if}

    {if $available}
    <div class="mp-section" data-mp-section>
        <div>
            <h2>{Lang::T('Discover Plugins')}</h2>
            <p>{Lang::T('More extensions available from the repository.')}</p>
        </div>
        <span class="mp-chip">{$available|@count} {Lang::T('available')}</span>
    </div>

    <div class="mp-grid" id="available-grid" data-sortable>
        {foreach $available as $plg}
        <article class="mp-card mp-card--available"
            data-tags="available {if $plg.ispaid}paid{else}free{/if}{if $plg.watched} watch{/if}"
            data-date="{$plg.last_update|escape}" data-search="{$plg.name|lower|escape} {$plg.id|lower|escape} {$plg.author|lower|escape}"
            data-info="{$plg.info_json|escape}">
            <div class="mp-card__accent"></div>
            <header class="mp-card__head">
                <div class="mp-card__title">
                    <h3>{$plg.name|escape}</h3>
                    <small>@{$plg.author|escape}{if $plg.last_update} &middot; {$plg.last_update|escape}{/if}</small>
                </div>
                <div class="mp-card__tags">
                    {include file="admin/marketplace/_watch.tpl" x=$plg}
                    {if $plg.ispaid}
                    <span class="mp-badge mp-badge--paid">{Lang::T('Premium')}</span>
                    {else}
                    <span class="mp-badge mp-badge--free">{Lang::T('Free')}</span>
                    {/if}
                </div>
            </header>
            <div class="mp-card__body">
                <p title="{$plg.description|escape}">{$plg.description|escape}</p>
            </div>
            <footer class="mp-card__actions">
                {include file="admin/marketplace/_store_actions.tpl" x=$plg route='plugins' disabled=!$writable}
            </footer>
        </article>
        {/foreach}
    </div>
    {/if}

    <div class="mp-empty mp-noresults" id="mp-noresults"><i class="fa fa-search"></i>
        <h4>{Lang::T('No matching results')}</h4>
        <p>{Lang::T('Try a different search term or filter.')}</p>
    </div>
</div>

{include file="admin/marketplace/_drawer.tpl"}
{include file="admin/marketplace/_scripts.tpl" route='plugins'}

{include file="sections/footer.tpl"}
