{*
    Routely VPN on Network, Routers and Radius, NAS: for a router with no public IP. Routely is an optional plugin
    (system/plugin/routely). $routely_vpn (the routers and radius controllers): installed, devices (how many routers it has).
    $rl_for: 'router' or 'nas'.
*}
<div class="ls rl-vpn-intro">
    <div class="ls-card">
        <div class="st-rl{if $routely_vpn['devices']} is-on{/if}">
            <span class="st-rl__mark" aria-hidden="true"><i class="fa fa-shield"></i></span>
            <div class="st-rl__text">
                {if $rl_for == 'nas'}
                <b>{Lang::T('Router without a public IP? Send disconnects through Routely VPN')}{if !$routely_vpn['installed']} <span class="ls-pill st-rl__free">{Lang::T('Free plan')}</span>{/if}</b>
                <span>{Lang::T('PHPNuxBill disconnects expired customers by sending CoA to the router, which a router behind NAT cannot receive. Forward its UDP port 3799 in Routely VPN and put that address in CoA address. The IP address stays the one RADIUS requests come from.')}</span>
                {else}
                <b>{Lang::T('Router without a public IP? Reach it through Routely VPN')}{if !$routely_vpn['installed']} <span class="ls-pill st-rl__free">{Lang::T('Free plan')}</span>{/if}</b>
                <span>{Lang::T('Paste one script into the router, on RouterOS 6 or 7, and forward its API port (8728). Use the address Routely gives, such as vpn1.example.com:34567, as the router\'s IP address here.')}</span>
                {/if}
            </div>
            <span class="st-rl__act">
                {if $routely_vpn['installed']}
                <a class="ls-btn{if !$routely_vpn['devices']} ls-btn--primary{/if}" href="{Text::url('plugin/routely/vpn')}">{if $routely_vpn['devices']}{Lang::T('Open Routely VPN')}{else}{Lang::T('Set up Routely VPN')}{/if}</a>
                {else}
                <a class="ls-btn" href="https://routely.focuslinkstech.com.ng/" target="_blank" rel="noopener">{Lang::T('Learn more')}</a>
                <a class="ls-btn ls-btn--primary" href="{Text::url('marketplace/plugins')}"><i class="fa fa-download" aria-hidden="true"></i> {Lang::T('Get Routely free')}</a>
                {/if}
            </span>
        </div>
    </div>
</div>
