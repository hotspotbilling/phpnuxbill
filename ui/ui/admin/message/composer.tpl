{*
    Message composer shared by Send Personal Message (single.tpl) and Send Bulk Message (bulk.tpl):
    channels, subject (only when a channel needs one) and the message with clickable placeholders.
    The field names are what message.php reads: sms, wa, email, inbox, push, subject, message.
*}
<fieldset class="mc-block">
    <legend class="mc-label">{Lang::T('Send through')}</legend>
    <div class="cm-chips mc-channels" id="via">
        <label class="cm-chip"><input type="checkbox" id="sms" name="sms" value="1"><span><i class="fa fa-mobile" aria-hidden="true"></i> {Lang::T('SMS')}</span></label>
        <label class="cm-chip"><input type="checkbox" id="wa" name="wa" value="1"><span><i class="fa fa-comment" aria-hidden="true"></i> {Lang::T('WhatsApp')}</span></label>
        <label class="cm-chip"><input type="checkbox" id="email" name="email" value="1"><span><i class="fa fa-envelope" aria-hidden="true"></i> {Lang::T('Email')}</span></label>
        <label class="cm-chip"><input type="checkbox" id="inbox" name="inbox" value="1"><span><i class="fa fa-inbox" aria-hidden="true"></i> {Lang::T('Inbox')}</span></label>
        <label class="cm-chip"><input type="checkbox" id="push" name="push" value="1"><span><i class="fa fa-bell" aria-hidden="true"></i> {Lang::T('Push')}</span></label>
    </div>
    <p class="mc-help">{Lang::T('Email, Inbox and Push also need a subject.')}</p>
</fieldset>

<div class="mc-block" id="mcSubjectBox" hidden>
    <label class="mc-label" for="mcSubject">{Lang::T('Subject')}</label>
    <input type="text" class="mc-input" name="subject" id="mcSubject" autocomplete="off" maxlength="200"
        placeholder="{Lang::T('Enter message subject here')}">
</div>

<div class="mc-block">
    <div class="mc-label-row">
        <label class="mc-label" for="message">{Lang::T('Message')}</label>
        <span class="mc-count" id="mcCount" aria-live="polite">0 {Lang::T('characters')}</span>
    </div>
    <div class="mc-editor">
        <div class="mc-tokens" role="toolbar" aria-label="{Lang::T('Insert a placeholder')}">
            <span class="mc-tokens__lead">{Lang::T('Insert')}</span>
            <button type="button" class="mc-token" data-token="[[name]]" title="{Lang::T('Customer Name')}">{Lang::T('Name')}</button>
            <button type="button" class="mc-token" data-token="[[user_name]]" title="{Lang::T('Customer Username')}">{Lang::T('Username')}</button>
            <button type="button" class="mc-token" data-token="[[phone]]" title="{Lang::T('Customer Phone')}">{Lang::T('Phone')}</button>
            <button type="button" class="mc-token" data-token="[[company_name]]" title="{Lang::T('Your Company Name')}">{Lang::T('Company')}</button>
            <button type="button" class="mc-token" data-token="[[payment_link]]" title="{Lang::T('A link that opens the customer recharge')}">{Lang::T('Payment link')}</button>
        </div>
        <textarea class="mc-input mc-textarea" id="message" name="message" rows="7" required
            placeholder="{Lang::T('Compose your message...')}">{$mc_message|default:''|escape}</textarea>
    </div>
    <p class="mc-help">{Lang::T('Placeholders are replaced for each customer, in the subject too.')}
        <a href="{Text::url('docs')}/#Reminder%20with%20payment%20link" target="_blank" rel="noopener">{Lang::T('About the payment link')}</a></p>
</div>
