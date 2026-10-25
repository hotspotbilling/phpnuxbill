{include file="sections/header.tpl"}

<div class="mp-wrap">
    {include file="admin/marketplace/_nav.tpl"}

    <section class="mp-hero mp-hero--themes">
        <div class="mp-hero__row">
            <div>
                <div class="mp-hero__crumb">
                    <a href="{Text::url('marketplace')}"><i class="fa fa-arrow-left"></i> {Lang::T('Marketplace')}</a>
                    <span>&middot;</span>
                    <b>{Lang::T('Themes & Skins')}</b>
                </div>
                <h1>{Lang::T('Themes & UI Skins')}</h1>
                <p>{Lang::T('Personalize your billing panel with themes.')}</p>
                <div class="mp-search">
                    <i class="fa fa-search"></i>
                    <input type="text" data-mp-search placeholder="{Lang::T('Search themes...')}">
                </div>
            </div>
            <div class="mp-stats">
                <div class="mp-stat"><b>{$stats.total + 1}</b><span>{Lang::T('Themes')}</span></div>
                <div class="mp-stat"><b>{if $active_theme eq 'default'}Default{else}{$active_theme}{/if}</b><span>{Lang::T('Active')}</span></div>
                <div class="mp-stat"><b>{$stats.child}</b><span>{Lang::T('Child')}</span></div>
                <div class="mp-stat"><b>{$stats.functions}</b><span>{Lang::T('Functions')}</span></div>
            </div>
        </div>
    </section>

    <div class="mp-section">
        <div>
            <h2>{Lang::T('Installed Themes')}</h2>
            <p>{Lang::T('Activate a theme to apply it across your panel.')}</p>
        </div>
        <div class="mp-toolbar">
            <span class="mp-chip mp-chip--ok">{Lang::T('Active')}: {$active_theme}</span>
            <button class="mp-filter" id="toggle-upload" type="button"><i class="fa fa-upload"></i>
                {Lang::T('Upload Theme')}</button>
        </div>
    </div>

    {include file="admin/marketplace/_upload.tpl"
        up_title=Lang::T('Upload a Theme')
        up_hint=Lang::T('Upload a ZIP package containing a "theme/" folder.')
        up_action="{Text::url('marketplace/themes/upload')}"
        up_file_name='zip_theme'
        up_file_label=Lang::T('Theme Package (.zip)')
        up_accept='application/zip,.zip'
        up_gh=true}

    <div class="mp-filters">
        <button class="mp-filter is-active" data-filter="all">{Lang::T('All')} <b>{$stats.total + 1}</b></button>
        <button class="mp-filter" data-filter="active">{Lang::T('Active')}</button>
        <button class="mp-filter" data-filter="child">{Lang::T('Child')} <b>{$stats.child}</b></button>
        <button class="mp-filter" data-filter="functions">{Lang::T('Functions')} <b>{$stats.functions}</b></button>
    </div>

    <div class="mp-grid">
        <article class="mp-card mp-card--{if $active_theme eq 'default'}active{else}inactive{/if}"
            data-tags="builtin {if $active_theme eq 'default'}active{else}inactive{/if}"
            data-search="default theme built-in ui/ui">
            <div class="mp-card__accent"></div>
            <header class="mp-card__head">
                <div class="mp-card__title">
                    <h3>{Lang::T('Default Theme')}</h3>
                    <small>ui/ui</small>
                </div>
                <div class="mp-card__tags">
                    <span class="mp-badge mp-badge--theme">{Lang::T('Built-in')}</span>
                    {if $active_theme eq 'default'}
                    <span class="mp-badge mp-badge--active"><i class="fa fa-check-circle"></i>
                        {Lang::T('Active')}</span>
                    {/if}
                </div>
            </header>
            <div class="mp-card__body">
                <p>{Lang::T('The built-in default theme.')}</p>
            </div>
            <footer class="mp-card__actions">
                {if $active_theme neq 'default'}
                <form method="post" action="{Text::url('marketplace/themes/default')}" class="mp-act-form">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <button type="button" class="mp-act mp-act--primary"
                        onclick="return ask(this, '{Lang::T('Switch to the default theme?')}')"
                        title="{Lang::T('Switch to default')}">
                        <i class="fa fa-undo"></i> {Lang::T('Switch to default')}</button>
                </form>
                {else}
                <button type="button" class="mp-act" disabled><i class="fa fa-check-circle"></i>
                    {Lang::T('Active')}</button>
                {/if}
            </footer>
        </article>

        {foreach $themes as $t}
        <article class="mp-card mp-card--{if $t.active}active{else}inactive{/if}"
            data-tags="{if $t.active}active{else}inactive{/if}{if $t.parent} child{/if}{if $t.has_functions} functions{/if}"
            data-search="{$t.name|lower} {$t.slug|lower} {$t.author|lower}">
            <div class="mp-card__accent"></div>
            <header class="mp-card__head">
                <div class="mp-card__title">
                    <h3>{$t.name}</h3>
                    <small>{$t.slug}{if $t.author} &middot; @{$t.author}{/if}</small>
                </div>
                <div class="mp-card__tags">
                    {if $t.active}
                    <span class="mp-badge mp-badge--active"><i class="fa fa-check-circle"></i>
                        {Lang::T('Active')}</span>
                    {/if}
                </div>
            </header>
            <div class="mp-card__body">
                <p>{if $t.description}{$t.description}{else}{Lang::T('No description provided.')}{/if}</p>
            </div>
            <div class="mp-card__meta">
                <span class="mp-chip">v{$t.version}</span>
                {if $t.parent}
                <span class="mp-badge mp-badge--legacy">{Lang::T('Child of')} {$t.parent}</span>
                {/if}
                {if $t.has_functions}
                <span class="mp-badge mp-badge--folder"><i class="fa fa-code"></i>&nbsp;functions.php</span>
                {/if}
            </div>
            <footer class="mp-card__actions">
                {if !$t.active}
                <form method="post" action="{Text::url('marketplace/themes/activate/', $t.slug)}" class="mp-act-form">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <button type="button" class="mp-act mp-act--success"
                        onclick="return ask(this, '{Lang::T('Activate this theme?')}')" title="{Lang::T('Activate')}">
                        <i class="fa fa-check"></i> {Lang::T('Activate')}</button>
                </form>
                <form method="post" action="{Text::url('marketplace/themes/uninstall/', $t.slug)}"
                    class="mp-act-form">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <button type="button" class="mp-act mp-act--danger"
                        onclick="return ask(this, '{Lang::T('Uninstall this theme? This cannot be undone.')}')"
                        title="{Lang::T('Uninstall')}">
                        <i class="fa fa-trash"></i> {Lang::T('Uninstall')}</button>
                </form>
                {else}
                <button type="button" class="mp-act" disabled><i class="fa fa-check-circle"></i>
                    {Lang::T('Active')}</button>
                {/if}
            </footer>
        </article>
        {/foreach}
    </div>

    <div class="mp-empty mp-noresults" id="mp-noresults"><i class="fa fa-search"></i>
        <h4>{Lang::T('No matching results')}</h4>
        <p>{Lang::T('Try a different search term or filter.')}</p>
    </div>

    <section class="mp-install mp-install--info">
        <h3><i class="fa fa-info-circle"></i> {Lang::T('About Themes')}</h3>
        <p>{Lang::T('Themes live in ui/themes/THEME_NAME/ with a theme.json manifest. Child themes declare a "parent" and fall back to parent templates. functions.php is loaded automatically when the theme is active.')}
        </p>
    </section>
</div>

{include file="admin/marketplace/_scripts.tpl"}

{include file="sections/footer.tpl"}
