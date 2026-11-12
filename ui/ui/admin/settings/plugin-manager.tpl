{include file="sections/header.tpl"}
{* Plugin manager. Routes and field names are what pluginmanager.php reads: zip_plugin or gh_url (dlinstall), and the
   install / delete links per item. *}

<div class="ls st">
    {if empty($_c['github_token'])}
    <div class="rf-note" role="note">
        <i class="fa fa-info-circle" aria-hidden="true"></i>
        <div>{Lang::T('To download from private/paid repository')},
            <a href="{Text::url('')}settings/app#GithubAuthentication">{Lang::T('Set your Github Authentication first')}</a></div>
    </div>
    {/if}

    <form method="post" enctype="multipart/form-data" action="{Text::url('')}pluginmanager/dlinstall" class="ls-card st-installer">
        <header class="st-card-head">
            <div><h3><i class="fa fa-cloud-download" aria-hidden="true"></i> {Lang::T('Plugin Installer')}</h3>
                <small>{Lang::T('Upload a zip, or install from a GitHub repository')}</small></div>
            <span class="st-foot-end">
                <a class="ls-btn ls-btn--sm" href="https://github.com/hotspotbilling/phpnuxbill/wiki/Installing-Plugin-or-Payment-Gateway" target="_blank" rel="noopener"><i class="fa fa-info-circle" aria-hidden="true"></i> {Lang::T('Info')}</a>
                <a class="ls-btn ls-btn--sm" href="{Text::url('')}pluginmanager/refresh"><i class="fa fa-refresh" aria-hidden="true"></i> {Lang::T('Refresh')}</a>
            </span>
        </header>
        <div class="st-card-body st-installer__body">
            <div>
                <label class="mc-label" for="stZip">{Lang::T('Upload Zip Plugin/Theme/Device')}</label>
                {* choosing a file installs it, through the same warning as the button *}
                <input type="file" id="stZip" name="zip_plugin" accept="application/zip" class="form-control" onchange="if (this.files.length) { document.getElementById('stInstall').click(); }">
            </div>
            <div>
                <label class="mc-label" for="stGh">{Lang::T('Github url')}</label>
                <input type="url" id="stGh" class="form-control" name="gh_url" placeholder="https://github.com/username/repository">
            </div>
            <button type="submit" id="stInstall" class="ls-btn ls-btn--primary"
                onclick="return ask(this, '{Lang::T('Warning, installing unknown source can damage your server, continue?')|escape:'javascript'}')">
                <i class="fa fa-download" aria-hidden="true"></i> {Lang::T('Install')}</button>
        </div>
    </form>

    <div class="ls-tabs" role="tablist" aria-label="{Lang::T('Type')}">
        <a class="ls-tab is-on" role="tab" href="#plugin" data-st-tab aria-selected="true">{Lang::T('Plugin')} <small>{count($plugins)}</small></a>
        <a class="ls-tab" role="tab" href="#pg" data-st-tab aria-selected="false">{Lang::T('Payment Gateway')} <small>{count($pgs)}</small></a>
        <a class="ls-tab" role="tab" href="#device" data-st-tab aria-selected="false">{Lang::T('Devices')} <small>{count($dvcs)}</small></a>
    </div>

    {foreach [['plugin', $plugins, 'plugin'], ['pg', $pgs, 'payment'], ['device', $dvcs, 'device']] as $tab}
    <div class="st-cards st-pane" id="{$tab[0]}" role="tabpanel" {if $tab[0] != 'plugin'}hidden{/if}>
        {foreach $tab[1] as $p}
        <article class="ls-card st-dev">
            <div class="st-dev__head">
                <span class="st-dev__icon"><i class="fa {if $tab[0] == 'pg'}fa-credit-card{elseif $tab[0] == 'device'}fa-microchip{else}fa-puzzle-piece{/if}" aria-hidden="true"></i></span>
                <div>
                    <b>{$p['name']|escape}</b>
                    <small>@{$p['author']|escape} &middot; {Lang::T('Last update')} {$p['last_update']|escape}</small>
                </div>
                {if $p['ispaid']}<span class="ls-pill ls-pill--inactive st-dev__paid">{Lang::T('Paid')}</span>{/if}
            </div>
            <p class="st-dev__desc">{$p['description']|strip_tags}</p>
            <div class="st-dev__foot">
                <a href="{$p['url']|escape}" target="_blank" rel="noopener" class="st-link"><i class="fa fa-globe" aria-hidden="true"></i> {if $p['ispaid']}{Lang::T('Buy')}{else}{Lang::T('Web')}{/if}</a>
                <a href="{$p['github']|escape}" target="_blank" rel="noopener" class="st-link"><i class="fa fa-code" aria-hidden="true"></i> {Lang::T('Source')}</a>
                <span class="st-foot-end">
                    {if $tab[0] == 'plugin'}
                    <a href="{Text::url('')}pluginmanager/delete/plugin/{$p['id']}" onclick="return ask(this, '{Lang::T('Delete')}?')"
                        class="ls-btn ls-btn--sm ls-btn--icon ls-btn--danger" title="{Lang::T('Delete')}" aria-label="{Lang::T('Delete')}: {$p['name']|escape}"><i class="fa fa-trash" aria-hidden="true"></i></a>
                    {/if}
                    <a {if $zipExt}href="{Text::url('')}pluginmanager/install/{$tab[2]}/{$p['id']}"
                        onclick="return ask(this, 'Installing plugin will take some time to complete, do not close the page while it loading to install the plugin')"
                        {else}href="#" onclick="alert('PHP ZIP extension is not available'); return false;"{/if}
                        class="ls-btn ls-btn--sm ls-btn--primary"><i class="fa fa-download" aria-hidden="true"></i> {Lang::T('Install')}</a>
                </span>
            </div>
        </article>
        {foreachelse}
        <div class="ls-card"><div class="ls-empty"><b>{Lang::T('Nothing to show')}</b>{Lang::T('Refresh to load the list again.')}</div></div>
        {/foreach}
    </div>
    {/foreach}
</div>

<script>
    // the type tabs: show one list at a time
    (function () {
        var tabs = document.querySelectorAll('[data-st-tab]');
        tabs.forEach(function (t) {
            t.addEventListener('click', function (e) {
                e.preventDefault();
                tabs.forEach(function (o) {
                    var on = o === t;
                    o.classList.toggle('is-on', on);
                    o.setAttribute('aria-selected', on ? 'true' : 'false');
                    document.querySelector(o.getAttribute('href')).hidden = !on;
                });
            });
        });
    })();
</script>
{include file="sections/footer.tpl"}
