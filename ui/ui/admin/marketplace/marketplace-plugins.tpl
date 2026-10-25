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
                <div class="mp-stat"><b>{$stats.legacy}</b><span>{Lang::T('Legacy')}</span></div>
                <div class="mp-stat"><b>{$stats.updates}</b><span>{Lang::T('Updates')}</span></div>
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
                title="{Lang::T('Refresh repository')}"><i class="fa fa-refresh"></i> {Lang::T('Sync Store')}</a>
            <button class="mp-filter" id="toggle-upload" type="button"><i class="fa fa-upload"></i>
                {Lang::T('Upload Plugin')}</button>
        </div>
    </div>

    {include file="admin/marketplace/_upload.tpl"
        up_title=Lang::T('Install a new plugin')
        up_hint=Lang::T('Upload a ZIP package or paste a GitHub repository URL.')
        up_action="{Text::url('pluginmanager/dlinstall')}"
        up_file_name='zip_plugin'
        up_file_label=Lang::T('Plugin Package (.zip)')
        up_accept='application/zip,.zip'
        up_gh=true
        up_enabled=$zipExt}

    <div class="mp-filters">
        <button class="mp-filter is-active" data-filter="all">{Lang::T('All')} <b>{$stats.total}</b></button>
        <button class="mp-filter" data-filter="folder">{Lang::T('Installed')} <b>{$stats.folder}</b></button>
        <button class="mp-filter" data-filter="active">{Lang::T('Active')} <b>{$stats.active}</b></button>
        <button class="mp-filter" data-filter="inactive">{Lang::T('Inactive')} <b>{$stats.inactive}</b></button>
        <button class="mp-filter" data-filter="legacy">{Lang::T('Legacy')} <b>{$stats.legacy}</b></button>
        <button class="mp-filter" data-filter="updates">{Lang::T('Updates')} <b>{$stats.updates}</b></button>
    </div>

    {if $plugins}
    <div class="mp-grid" id="plugin-grid">
        {foreach $plugins as $plugin}
        {assign var=has_update value=(isset($update_info[$plugin.slug]) && $update_info[$plugin.slug].update_available)}
        <article
            class="mp-card mp-card--{if $plugin.active}active{else}inactive{/if}{if $plugin.type eq 'legacy'} mp-card--legacy{/if}"
            data-tags="{$plugin.type} {if $plugin.active}active{else}inactive{/if}{if $has_update} updates{/if}"
            data-search="{$plugin.name|lower} {$plugin.slug|lower}">
            <div class="mp-card__accent"></div>
            <header class="mp-card__head">
                <div class="mp-card__title">
                    <h3>{$plugin.name}</h3>
                    <small>{$plugin.slug}</small>
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
                <p>{if $plugin.description}{$plugin.description}{else}{Lang::T('No description provided.')}{/if}</p>
            </div>
            <div class="mp-card__meta">
                {if $plugin.version}<span class="mp-chip">v{$plugin.version}</span>{/if}
                {if $plugin.active}
                <span class="mp-status mp-status--active"><span
                        class="mp-dot mp-dot--active"></span>{Lang::T('Active')}</span>
                {else}
                <span class="mp-status mp-status--inactive"><span
                        class="mp-dot mp-dot--inactive"></span>{Lang::T('Inactive')}</span>
                {/if}
                {if $has_update}
                <span class="mp-status mp-status--update"><span
                        class="mp-dot mp-dot--update"></span>{Lang::T('Update')} v{$update_info[$plugin.slug].latest_version}</span>
                {/if}
            </div>
            <footer class="mp-card__actions">
                {if $plugin.active}
                <form method="post" action="{Text::url('marketplace/plugins/deactivate/', $plugin.slug)}"
                    class="mp-act-form">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <button type="button" class="mp-act mp-act--warn"
                        onclick="return ask(this, '{Lang::T('Deactivate this plugin?')}')"
                        title="{Lang::T('Deactivate')}">
                        <i class="fa fa-pause"></i> {Lang::T('Pause')}</button>
                </form>
                {else}
                <form method="post" action="{Text::url('marketplace/plugins/activate/', $plugin.slug)}"
                    class="mp-act-form">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <button type="submit" class="mp-act mp-act--success" title="{Lang::T('Activate')}">
                        <i class="fa fa-play"></i> {Lang::T('Activate')}</button>
                </form>
                {/if}
                {if $plugin.type eq 'legacy'}
                <form method="post" action="{Text::url('marketplace/plugins/convert/', $plugin.slug)}"
                    class="mp-act-form">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <button type="button" class="mp-act mp-act--primary"
                        onclick="return ask(this, '{Lang::T('Convert this legacy plugin to folder style?')}')"
                        title="{Lang::T('Convert')}">
                        <i class="fa fa-exchange"></i> {Lang::T('Convert')}</button>
                </form>
                {/if}
                <form method="post" action="{Text::url('marketplace/plugins/uninstall/', $plugin.slug)}"
                    class="mp-act-form">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <button type="button" class="mp-act mp-act--danger"
                        onclick="return ask(this, '{Lang::T('Uninstall this plugin? This cannot be undone.')}')"
                        title="{Lang::T('Uninstall')}">
                        <i class="fa fa-trash"></i> {Lang::T('Uninstall')}</button>
                </form>
            </footer>
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
            <p>{Lang::T('More extensions available from the official repository.')}</p>
        </div>
        <span class="mp-chip">{$available|@count} {Lang::T('available')}</span>
    </div>

    <div class="mp-grid" id="available-grid">
        {foreach $available as $plg}
        <article class="mp-card mp-card--available" data-tags="available {if $plg.ispaid}paid{else}free{/if}"
            data-search="{$plg.name|lower} {$plg.id|lower} {$plg.author|lower}">
            <div class="mp-card__accent"></div>
            <header class="mp-card__head">
                <div class="mp-card__title">
                    <h3>{$plg.name}</h3>
                    <small>@{$plg.author}{if $plg.last_update} &middot; {$plg.last_update}{/if}</small>
                </div>
                <div class="mp-card__tags">
                    {if $plg.ispaid}
                    <span class="mp-badge mp-badge--paid">{Lang::T('Premium')}</span>
                    {else}
                    <span class="mp-badge mp-badge--free">{Lang::T('Free')}</span>
                    {/if}
                </div>
            </header>
            <div class="mp-card__body">
                <p title="{$plg.description|escape}">{$plg.description}</p>
            </div>
            <footer class="mp-card__actions">
                {if $plg.url}
                <a href="{$plg.url}" target="_blank" rel="noopener noreferrer" class="mp-act"
                    title="{Lang::T('Website')}"><i class="fa fa-globe"></i> {Lang::T('Web')}</a>
                {/if}
                {if $plg.github}
                <a href="{$plg.github}" target="_blank" rel="noopener noreferrer" class="mp-act"
                    title="{Lang::T('Source')}"><i class="fa fa-github"></i> {Lang::T('Source')}</a>
                {/if}
                <form method="post" action="{Text::url('marketplace/plugins/install/', $plg.id)}" class="mp-act-form">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <button type="button" class="mp-act mp-act--primary"
                        onclick="return ask(this, '{Lang::T('Install this plugin? This may take some time.')}')"
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
</div>

{include file="admin/marketplace/_scripts.tpl"}

{include file="sections/footer.tpl"}
