{*
    Side column of the message composer: a live preview with the placeholders filled in, and the script shared by both send pages
    (subject field, placeholder buttons, character and SMS part counter, preview).
    $mc_sample_name: the name the preview uses until a customer is picked.
*}
<div class="ls-card mc-side-card">
    <div class="mc-side-head">
        <span><i class="fa fa-eye" aria-hidden="true"></i> {Lang::T('Preview')}</span>
        <small id="mcPreviewWho">{Lang::T('Sample customer')}</small>
    </div>
    <div class="mc-preview">
        <div class="mc-bubble">
            <b class="mc-bubble__subject" id="mcPreviewSubject" hidden></b>
            <div class="mc-bubble__text" id="mcPreviewText"><span class="mc-dim">{Lang::T('Your message appears here as the customer will read it.')}</span></div>
        </div>
        <div class="mc-preview__meta" id="mcPreviewMeta"></div>
    </div>
</div>

<div class="ls-card mc-side-card mc-tips">
    <div class="mc-side-head"><span><i class="fa fa-lightbulb" aria-hidden="true"></i> {Lang::T('Tips')}</span></div>
    <ul>
        <li>{Lang::T('One SMS holds 160 characters; emoji and some accented letters lower it to 70.')}</li>
        <li>{Lang::T('A customer without a phone number or email is skipped for that channel.')}</li>
        <li>{Lang::T('Inbox messages show in the customer portal; Push needs the customer to have allowed notifications.')}</li>
    </ul>
</div>

<script>
(function () {
    var T_CHARS = '{Lang::T('characters')|escape:'javascript'}';
    var T_SMS = '{Lang::T('SMS')|escape:'javascript'}';
    var T_PARTS = '{Lang::T('parts')|escape:'javascript'}';
    var T_PART = '{Lang::T('part')|escape:'javascript'}';
    var T_UNICODE = '{Lang::T('Unicode')|escape:'javascript'}';
    var T_SAMPLE = '{Lang::T('Sample customer')|escape:'javascript'}';
    var T_EMPTY = '{Lang::T('Your message appears here as the customer will read it.')|escape:'javascript'}';
    var T_TO = '{Lang::T('Preview for')|escape:'javascript'}';
    var sample = {
        '[[name]]': '{$mc_sample_name|default:'Jane Doe'|escape:'javascript'}',
        '[[user_name]]': 'jane.doe',
        '[[phone]]': '+000 000 0000',
        '[[company_name]]': '{$_c['CompanyName']|escape:'javascript'}',
        // shaped like MessageSender::paymentLink(), so the SMS count is close to the real one
        '[[payment_link]]': '{$app_url|escape:'javascript'}/?_route=home&recharge=1024&uid=4f9c2a7e1b8d6035c9e2f7a14b3d8e60'
    };

    var msg = document.getElementById('message');
    var subj = document.getElementById('mcSubject');
    var subjBox = document.getElementById('mcSubjectBox');
    var count = document.getElementById('mcCount');
    var pText = document.getElementById('mcPreviewText');
    var pSubj = document.getElementById('mcPreviewSubject');
    var pMeta = document.getElementById('mcPreviewMeta');
    var pWho = document.getElementById('mcPreviewWho');
    var chans = ['sms', 'wa', 'email', 'inbox', 'push'].map(function (id) { return document.getElementById(id); });
    if (!msg) { return; }

    // the GSM-7 alphabet an SMS uses for 160-character parts; anything else makes the whole SMS Unicode (70 per part)
    var GSM = '@£$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !"#¤%&\'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà';
    var GSM_EXT = '^{literal}{}{/literal}\\[~]|€';
    function smsParts(text) {
        var len = 0, uni = false, chars = Array.from(text);
        for (var i = 0; i < chars.length; i++) {
            if (GSM.indexOf(chars[i]) !== -1) { len += 1; }
            else if (GSM_EXT.indexOf(chars[i]) !== -1) { len += 2; }
            else { uni = true; break; }
        }
        if (uni) { len = chars.length; }
        var one = uni ? 70 : 160, many = uni ? 67 : 153;
        return { parts: len === 0 ? 0 : (len <= one ? 1 : Math.ceil(len / many)), unicode: uni };
    }
    function fill(text) {
        return text.replace(/\[\[(name|user_name|phone|company_name|payment_link)\]\]/g, function (m) { return sample[m] || m; });
    }
    function on(id) { var el = document.getElementById(id); return !!(el && el.checked); }

    function refresh() {
        var needSubject = on('email') || on('inbox') || on('push');
        subjBox.hidden = !needSubject;
        subj.required = needSubject;

        var text = msg.value;
        var n = Array.from(text).length;
        var sms = smsParts(fill(text));
        var label = n + ' ' + T_CHARS;
        if (on('sms') && n) { label += ' · ' + sms.parts + ' ' + T_SMS + ' ' + (sms.parts === 1 ? T_PART : T_PARTS) + (sms.unicode ? ' (' + T_UNICODE + ')' : ''); }
        count.textContent = label;
        count.classList.toggle('is-warn', on('sms') && sms.parts > 1);

        if (text.trim() === '') {
            pText.innerHTML = '<span class="mc-dim"></span>';
            pText.firstChild.textContent = T_EMPTY;
        } else {
            pText.textContent = fill(text);
        }
        var s = needSubject ? fill(subj.value.trim()) : '';
        pSubj.hidden = s === '';
        pSubj.textContent = s;
        pMeta.innerHTML = '';
        chans.forEach(function (c) {
            if (c && c.checked) {
                var tag = document.createElement('span');
                tag.textContent = c.closest('label').textContent.trim();
                pMeta.appendChild(tag);
            }
        });
    }

    // a placeholder button puts its token where the cursor is (or in the subject when that has focus last)
    var lastField = msg;
    [msg, subj].forEach(function (f) { f.addEventListener('focus', function () { lastField = f; }); });
    document.querySelectorAll('.mc-token').forEach(function (b) {
        b.addEventListener('mousedown', function (e) { e.preventDefault(); }); // keep the cursor where it is
        b.addEventListener('click', function () {
            var f = (lastField === subj && !subjBox.hidden) ? subj : msg;
            var tok = b.getAttribute('data-token'), st = f.selectionStart || 0, en = f.selectionEnd || 0;
            f.value = f.value.slice(0, st) + tok + f.value.slice(en);
            f.focus();
            f.setSelectionRange(st + tok.length, st + tok.length);
            refresh();
        });
    });

    msg.addEventListener('input', refresh);
    subj.addEventListener('input', refresh);
    chans.forEach(function (c) { if (c) { c.addEventListener('change', refresh); } });

    // single page: the preview uses the picked customer's name ("username - full name - email")
    window.mcSetCustomer = function (text) {
        var parts = String(text || '').split(/\s+[-•]\s+/);
        if (parts.length >= 2 && parts[1].trim()) {
            sample['[[name]]'] = parts[1].trim();
            sample['[[user_name]]'] = parts[0].trim();
            pWho.textContent = T_TO + ' ' + parts[1].trim();
        } else {
            pWho.textContent = T_SAMPLE;
        }
        refresh();
    };
    window.mcRefresh = refresh;

    // Select2 puts its open list at the end of <body>, outside .mc and its colours. Tag the list of a select on this page so
    // the stylesheet can style it. A MutationObserver works whichever copy of jQuery opened it (the bulk page loads two).
    if (window.MutationObserver) {
        new MutationObserver(function () {
            if (!document.querySelector('.mc .select2-container--open')) { return; }
            document.querySelectorAll('body > .select2-container--open').forEach(function (d) { d.classList.add('mc-s2'); });
        }).observe(document.body, { childList: true });
    }

    // Confirm before sending. Like ask() in the footer, but SweetAlert's heightAuto is off: with it on, opening the
    // dialog resets the page height and the page jumps to the top.
    var T_SURE = '{Lang::T('Are you sure?')|escape:'javascript'}';
    var T_YES = '{Lang::T('Yes, send it')|escape:'javascript'}';
    var T_NO = '{Lang::T('Cancel')|escape:'javascript'}';
    window.mcConfirm = function (btn, text, onYes) {
        if (!window.Swal) { return window.ask ? window.ask(btn, text, onYes) : false; }
        btn.disabled = true;
        Swal.fire({
            title: T_SURE, text: text, icon: 'warning', showCancelButton: true,
            confirmButtonText: T_YES, cancelButtonText: T_NO,
            heightAuto: false, returnFocus: false
        }).then(function (r) {
            btn.disabled = false;
            if (!r.isConfirmed) { return; }
            if (typeof onYes === 'function') { onYes(); return; }
            btn.disabled = true; // the form is posting: no second click
            var form = btn.closest('form');
            if (form) { form.submit(); }
        });
        return false;
    };
    refresh();
})();
</script>
