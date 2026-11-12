{*
    The free community WhatsApp gateway (wa.nux.my.id, by iBNuX) on the SMS and WhatsApp tabs of General Settings.
    $fg_for: 'sms' (the SMS address may point at a WhatsApp gateway, so SMS-channel messages go out by WhatsApp) or 'wa'.
*}
<div class="st-fg">
    <span class="st-fg__mark" aria-hidden="true"><i class="fa fa-gift"></i></span>
    <div class="st-fg__text">
        <b>{if $fg_for == 'sms'}{Lang::T('Send these messages by WhatsApp instead')}{else}{Lang::T('Free WhatsApp gateway')}{/if}
            <span class="ls-pill">{Lang::T('Free')}</span></b>
        <span>{if $fg_for == 'sms'}{Lang::T('The address above can be a WhatsApp gateway too, so messages meant for SMS go out by WhatsApp at no SMS cost.')}{else}{Lang::T('A free community gateway by iBNuX.')}{/if}</span>
        <ol class="st-fg__steps">
            <li>{Lang::T('Open the free server and sign in.')}</li>
            <li>{Lang::T('Link your WhatsApp number by scanning its QR code.')}</li>
            <li>{Lang::T('Copy the address it gives you into the field above, with [number] and [text] where the number and message go, then press the Test button above.')}</li>
        </ol>
    </div>
    <span class="st-fg__act">
        <a class="ls-btn" href="https://wa.nux.my.id/login" target="_blank" rel="noopener"><i class="fa fa-external-link" aria-hidden="true"></i> {Lang::T('Open free server')}</a>
    </span>
</div>
