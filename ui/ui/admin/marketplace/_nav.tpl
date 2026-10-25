<nav class="mp-tabs" aria-label="{Lang::T('Marketplace')}">
    <a href="{Text::url('marketplace')}" class="mp-tab{if $active_tab eq 'hub'} is-active{/if}">
        <i class="fa fa-th-large"></i> {Lang::T('Overview')}
        {if $updates_pending > 0}<span class="mp-tab__count mp-tab__count--alert" title="{Lang::T('Updates available')}">{$updates_pending}</span>{/if}
    </a>
    <a href="{Text::url('marketplace/plugins')}" class="mp-tab{if $active_tab eq 'plugins'} is-active{/if}">
        <i class="fa fa-puzzle-piece"></i> {Lang::T('Plugins & Addons')}
    </a>
    <a href="{Text::url('marketplace/gateways')}" class="mp-tab{if $active_tab eq 'gateways'} is-active{/if}">
        <i class="fa fa-credit-card"></i> {Lang::T('Payment Gateways')}
    </a>
    <a href="{Text::url('marketplace/themes')}" class="mp-tab{if $active_tab eq 'themes'} is-active{/if}">
        <i class="fa fa-paint-brush"></i> {Lang::T('Themes & Skins')}
    </a>
    <a href="{Text::url('marketplace/devices')}" class="mp-tab{if $active_tab eq 'devices'} is-active{/if}">
        <i class="fa fa-server"></i> {Lang::T('Device Drivers')}
    </a>
    <a href="{Text::url('marketplace/tools')}" class="mp-tab{if $active_tab eq 'tools'} is-active{/if}">
        <i class="fa fa-wrench"></i> {Lang::T('Tools')}
    </a>
</nav>
{if isset($writable) && !$writable}
<div class="mp-banner mp-banner--warn">
    <i class="fa fa-lock"></i>
    {Lang::T('The folder for this extension type (or the cache folder) is not writable, so installing, updating and removing are disabled. Fix the permissions and reload.')}
</div>
{/if}
