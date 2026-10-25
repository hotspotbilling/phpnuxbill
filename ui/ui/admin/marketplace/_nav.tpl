<nav class="mp-tabs" aria-label="{Lang::T('Marketplace')}">
    <a href="{Text::url('marketplace')}" class="mp-tab{if $active_tab eq 'hub'} is-active{/if}">
        <i class="fa fa-th-large"></i> {Lang::T('Overview')}
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
</nav>
