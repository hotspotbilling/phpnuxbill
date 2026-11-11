{include file="sections/header.tpl"}
{* Referral settings. Field names and values are what referrals.php saves; nothing else changed. *}
{$rf_on = $_c['referral_enabled'] != 'no'}
{$rf_purchase = $_c['require_purchase'] != 'no'}

<div class="ls mc rf">
    <form class="mc-grid" method="post" action="{Text::url('referrals/settings')}" id="rfForm">
        <input type="hidden" name="csrf_token" value="{$csrf_token}">
        {* a switch that is off sends nothing, so these send "no" first and a checked switch overrides them *}
        <input type="hidden" name="referral_enabled" value="no">
        <input type="hidden" name="require_purchase" value="no">
        <div class="ls-card mc-main">
            {if $schema_update_required}
            <div class="rf-note rf-note--warn" role="alert">
                <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                <div><b>{Lang::T('The referral tables need a database update')}</b>
                    {Lang::T('Percentage rewards and the split between referrer and new customer are not recorded until it runs.')}
                    <a href="{$app_url}/update.php">{Lang::T('Run the update')}</a></div>
            </div>
            {/if}
            {if $_c['enable_balance'] != 'yes'}
            <div class="rf-note" role="note">
                <i class="fa fa-info-circle" aria-hidden="true"></i>
                <div><b>{Lang::T('Customer balance is turned off')}</b>
                    {Lang::T('Rewards are paid as balance, and customers only see the Referrals page while balance is on.')}
                    <a href="{Text::url('settings/app')}">{Lang::T('General Settings')}</a></div>
            </div>
            {/if}

            <fieldset class="mc-block">
                <legend class="mc-label">{Lang::T('Program')}</legend>
                <div class="mc-rows">
                    <label class="mc-row mc-row--switch">
                        <input type="checkbox" id="rfEnabled" name="referral_enabled" value="yes" {if $rf_on}checked{/if}>
                        <span class="mc-row__icon"><i class="fa fa-share-alt" aria-hidden="true"></i></span>
                        <span class="mc-row__text">
                            <b>{Lang::T('Referral program')}</b>
                            <small class="mc-on">{Lang::T('On: customers get a link to share, and codes count at sign-up.')}</small>
                            <small class="mc-off">{Lang::T('Off: no links are shown and codes are ignored at sign-up.')}</small>
                        </span>
                        <span class="mc-switch__track" aria-hidden="true"></span>
                    </label>
                    <label class="mc-row mc-row--switch rf-row--choice">
                        <input type="checkbox" id="rfPurchase" name="require_purchase" value="yes" {if $rf_purchase}checked{/if}>
                        <span class="mc-row__icon"><i class="fa fa-shopping-cart" aria-hidden="true"></i></span>
                        <span class="mc-row__text">
                            <b>{Lang::T('Reward on purchase')}</b>
                            <small class="mc-on">{Lang::T('Rewards are given when the new customer buys a package.')}</small>
                            <small class="mc-off">{Lang::T('Rewards are given as soon as the new customer signs up.')}</small>
                        </span>
                        <span class="mc-switch__track" aria-hidden="true"></span>
                    </label>
                    <div class="mc-row" id="rfMinRow">
                        <span class="mc-row__icon"><i class="fa fa-tag" aria-hidden="true"></i></span>
                        <div class="mc-row__text">
                            <label for="rfMin"><b>{Lang::T('Minimum purchase')}</b></label>
                            <small>{Lang::T('Packages cheaper than this earn no reward. 0 means any package.')}</small>
                        </div>
                        <div class="rf-affix rf-affix--row">
                            <input type="number" class="mc-input" id="rfMin" name="min_purchase_amount" min="0" step="any" inputmode="decimal"
                                value="{$_c['min_purchase_amount']|default:0|escape}">
                            <span>{$_c['currency_code']|escape}</span>
                        </div>
                    </div>
                    <div class="mc-row">
                        <span class="mc-row__icon"><i class="fa fa-key" aria-hidden="true"></i></span>
                        <div class="mc-row__text">
                            <label for="rfLen"><b>{Lang::T('Code length')}</b></label>
                            <small>{Lang::T('3 to 32 characters. Codes that already exist keep their length.')}</small>
                        </div>
                        <input type="number" class="mc-input mc-row__control" id="rfLen" name="referral_code_length" min="3" max="32" step="1"
                            value="{$_c['referral_code_length']|default:8|escape}" required>
                    </div>
                </div>
            </fieldset>

            <fieldset class="mc-block">
                <legend class="mc-label">{Lang::T('Rewards')}</legend>
                <div class="rf-rewards">
                    {foreach [
                        'referrer' => [Lang::T('Referrer'), Lang::T('The customer who shared the link'), 'fa-bullhorn'],
                        'referred' => [Lang::T('New customer'), Lang::T('The customer who signed up with it'), 'fa-user-plus']
                    ] as $who => $txt}
                    {$calc = $_c["{$who}_calculation_type"]|default:'fixed'}
                    <div class="rf-reward" data-who="{$who}">
                        <div class="rf-reward__head">
                            <span class="rf-reward__icon"><i class="fa {$txt[2]}" aria-hidden="true"></i></span>
                            <div><b>{$txt[0]}</b><small>{$txt[1]}</small></div>
                        </div>
                        <input type="hidden" name="{$who}_reward_type" value="balance">
                        <div class="rf-seg" role="radiogroup" aria-label="{Lang::T('Reward Calculation')}">
                            <label><input type="radio" name="{$who}_calculation_type" value="fixed" {if $calc != 'percentage'}checked{/if}><span>{Lang::T('Fixed amount')}</span></label>
                            <label class="rf-seg__pct"><input type="radio" name="{$who}_calculation_type" value="percentage" {if $calc == 'percentage'}checked{/if}><span>{Lang::T('% of purchase')}</span></label>
                        </div>
                        <div class="rf-affix rf-amount" data-for="fixed">
                            <input type="number" class="mc-input" name="{$who}_reward_amount" min="0" step="any" inputmode="decimal"
                                value="{$_c["{$who}_reward_amount"]|default:0|escape}" aria-label="{$txt[0]}: {Lang::T('Fixed amount')}">
                            <span>{$_c['currency_code']|escape}</span>
                        </div>
                        <div class="rf-affix rf-amount" data-for="percentage">
                            <input type="number" class="mc-input" name="{$who}_reward_percentage" min="0" max="100" step="any" inputmode="decimal"
                                value="{$_c["{$who}_reward_percentage"]|default:5|escape}" aria-label="{$txt[0]}: {Lang::T('% of purchase')}">
                            <span>%</span>
                        </div>
                        <small class="rf-reward__foot"><i class="fa fa-credit-card" aria-hidden="true"></i> {Lang::T('Added to the customer balance')}</small>
                    </div>
                    {/foreach}
                </div>
                <p class="mc-help" id="rfPctHelp">{Lang::T('A percentage needs a purchase to work from, so it is only available with Reward on purchase.')}</p>
            </fieldset>

            <div class="mc-foot">
                <a href="{Text::url('referrals/list')}" class="ls-btn"><i class="fa fa-times" aria-hidden="true"></i> {Lang::T('Cancel')}</a>
                <button class="ls-btn ls-btn--primary" type="submit"><i class="fa fa-check" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
            </div>
        </div>

        <aside class="mc-side">
            <div class="ls-card mc-side-card">
                <div class="mc-side-head"><span><i class="fa fa-magic" aria-hidden="true"></i> {Lang::T('How it works')}</span></div>
                <div class="rf-summary" id="rfSummary" aria-live="polite"></div>
            </div>
            <a class="ls-card rf-link" href="{Text::url('referrals/list')}">
                <i class="fa fa-list-ul" aria-hidden="true"></i>
                <span><b>{Lang::T('Referrals')}</b><small>{Lang::T('Who referred whom, and the rewards paid')}</small></span>
                <i class="fa fa-angle-right" aria-hidden="true"></i>
            </a>
        </aside>
    </form>
</div>

<script>
(function () {
    var form = document.getElementById('rfForm');
    var T = {
        off: {Lang::T('The program is off. Customers see no referral link, and codes typed at sign-up are ignored.')|json_encode},
        signup: {Lang::T('When someone signs up with a referral link')|json_encode},
        buy: {Lang::T('Each time a referred customer buys a package')|json_encode},
        min: {Lang::T('of at least')|json_encode},
        skip: {Lang::T('Vouchers and balance top-ups do not count.')|json_encode},
        referrer: {Lang::T('the referrer gets')|json_encode},
        referred: {Lang::T('the new customer gets')|json_encode},
        price: {Lang::T('of the price')|json_encode},
        nothing: {Lang::T('nothing')|json_encode}
    };
    var cur = {$_c['currency_code']|json_encode};
    var $ = function (s) { return form.querySelector(s); };
    var num = function (v) { var n = parseFloat(v); return isNaN(n) ? 0 : n; };
    var money = function (v) { return cur + ' ' + num(v).toLocaleString(); };
    function add(parent, tag, text, cls) { var e = document.createElement(tag); e.textContent = text; if (cls) { e.className = cls; } parent.appendChild(e); return e; }

    function refresh() {
        var on = $('#rfEnabled').checked, purchase = $('#rfPurchase').checked;
        form.classList.toggle('rf-is-off', !on);
        $('#rfMinRow').classList.toggle('is-muted', !purchase);
        $('#rfPctHelp').hidden = purchase;

        var gives = {};
        form.querySelectorAll('.rf-reward').forEach(function (card) {
            var who = card.getAttribute('data-who');
            var pct = card.querySelector('.rf-seg__pct input');
            pct.disabled = !purchase;
            if (!purchase && pct.checked) { card.querySelector('input[value="fixed"]').checked = true; }
            var mode = card.querySelector('.rf-seg input:checked').value;
            card.querySelectorAll('.rf-amount').forEach(function (a) {
                var off = a.getAttribute('data-for') !== mode, input = a.querySelector('input');
                a.hidden = off;
                // a hidden field cannot show an error, so it is left out of validation: a read-only field is not checked
                // (range or unreadable text) but still posts its value, which the settings handler saves (a disabled
                // field would post nothing and reset that setting to 0)
                input.readOnly = off;
            });
            var v = card.querySelector('.rf-amount:not([hidden]) input').value;
            gives[who] = num(v) <= 0 ? T.nothing : (mode === 'percentage' ? num(v) + '% ' + T.price : money(v));
        });

        // the plain-language summary
        var box = document.getElementById('rfSummary');
        box.innerHTML = '';
        if (!on) { add(box, 'p', T.off, 'rf-summary__off'); return; }
        var lead = purchase ? T.buy + (num($('#rfMin').value) > 0 ? ' ' + T.min + ' ' + money($('#rfMin').value) : '') : T.signup;
        add(box, 'p', lead + ':', 'rf-summary__lead');
        var ul = add(box, 'ul', '');
        [['referrer', 'fa-bullhorn'], ['referred', 'fa-user-plus']].forEach(function (x) {
            var li = add(ul, 'li', '');
            var i = add(li, 'i', ''); i.className = 'fa ' + x[1]; i.setAttribute('aria-hidden', 'true');
            add(li, 'span', T[x[0]] + ' ');
            add(li, 'b', gives[x[0]]);
        });
        if (purchase) { add(box, 'p', T.skip, 'rf-summary__note'); }
    }
    form.addEventListener('input', refresh);
    form.addEventListener('change', refresh);
    refresh();
})();
</script>

{include file="sections/footer.tpl"}
