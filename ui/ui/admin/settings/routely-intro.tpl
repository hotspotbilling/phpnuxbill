{*
    Routely on the SMS and WhatsApp tabs of General Settings. Routely is an optional plugin (system/plugin/routely): when it is
    connected it fills in sms_url / wa_url itself. $routely (settings.php): installed, sms, wa. $rl_for: 'sms' or 'wa'.
*}
{$rl_on = ($rl_for == 'sms' && $routely['sms']) || ($rl_for == 'wa' && $routely['wa'])}
{$rl_orphan = ($rl_for == 'sms' && $routely['sms_orphan']) || ($rl_for == 'wa' && $routely['wa_orphan'])}
{if $rl_orphan}
{* the plugin is gone but the address still points at Routely: say so, offer to reinstall or to clear the address *}
<div class="st-rl is-orphan">
    <span class="st-rl__mark" aria-hidden="true"><i class="fa fa-exclamation-triangle"></i></span>
    <div class="st-rl__text">
        <b>{if $rl_for == 'wa'}{Lang::T('WhatsApp is still sent through Routely')}{else}{Lang::T('SMS is still sent through Routely')}{/if}
            <span class="ls-pill ls-pill--inactive">{Lang::T('Plugin not installed')}</span></b>
        <span>{Lang::T('The address below points at your Routely account, but the Routely plugin is not installed, so gateways, limits and history cannot be managed here. Reinstall the plugin to manage it, or clear the address and save to stop using Routely.')}</span>
    </div>
    <span class="st-rl__act">
        <button type="button" class="ls-btn" onclick="var f = document.getElementById('{if $rl_for == 'wa'}wa_url{else}sms_url{/if}'); f.value = ''; f.focus();">{Lang::T('Clear address')}</button>
        <a class="ls-btn ls-btn--primary" href="{Text::url('marketplace/plugins')}"><i class="fa fa-download" aria-hidden="true"></i> {Lang::T('Reinstall Routely')}</a>
    </span>
</div>
{else}
<div class="st-rl{if $rl_on} is-on{/if}">
    <span class="st-rl__mark" aria-hidden="true"><i class="fa {if $rl_for == 'wa'}fa-comments{else}fa-mobile{/if}"></i></span>
    <div class="st-rl__text">
        {if $rl_on}
        <b>{if $rl_for == 'wa'}{Lang::T('WhatsApp is sent through Routely')}{else}{Lang::T('SMS is sent through Routely')}{/if}
            <span class="ls-pill ls-pill--active">{Lang::T('Connected')}</span></b>
        <span>{Lang::T('Routely filled in the address below. Change gateways, routing and limits in Routely; disconnecting there puts your previous address back.')}</span>
        {elseif $rl_for == 'wa'}
        <b>{Lang::T('Send WhatsApp with Routely')} <span class="ls-pill st-rl__free">{Lang::T('Free plan')}</span></b>
        <span>{Lang::T('Link your own WhatsApp numbers to Routely and send reminders, invoices and OTP from them, from the same account as your SMS gateways and VPN tunnels, with delivery reports in its history.')}</span>
        {else}
        <b>{Lang::T('Send SMS with Routely')} <span class="ls-pill st-rl__free">{Lang::T('Free plan')}</span></b>
        <span>{Lang::T('Use your own Android phones or your SMS provider (Termii, Africa\'s Talking, Twilio and more). Routely spreads messages across your gateways with per-gateway limits and shows delivery reports.')}</span>
        {/if}
        {if !$rl_on}<span class="st-rl__note"><i class="fa fa-check" aria-hidden="true"></i> {Lang::T('Start free: no card needed, one gateway with daily and monthly limits. Upgrade any time for more.')}</span>{/if}
    </div>
    <span class="st-rl__act">
        {if $routely['installed']}
        <a class="ls-btn{if !$rl_on} ls-btn--primary{/if}" href="{if $rl_for == 'sms'}{Text::url('plugin/routely/sms')}{else}{Text::url('plugin/routely/whatsapp')}{/if}">
            {if $rl_on}{Lang::T('Open Routely')}{else}{Lang::T('Start free')}{/if}</a>
        {else}
        <a class="ls-btn" href="https://routely.focuslinkstech.com.ng/" target="_blank" rel="noopener">{Lang::T('Learn more')}</a>
        <a class="ls-btn ls-btn--primary" href="{Text::url('marketplace/plugins')}"><i class="fa fa-download" aria-hidden="true"></i> {Lang::T('Get Routely free')}</a>
        {/if}
    </span>
</div>
{/if}
