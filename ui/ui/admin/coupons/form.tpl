{*
    The coupon form, shared by add.tpl ($nf_edit false, $coupon empty) and edit.tpl ($nf_edit true, $coupon the coupon).
    Field names are what coupons.php reads: code, type (fixed / percent), value, description, max_usage (0: no limit),
    min_order_amount, max_discount_amount (a percent coupon's cap; not used by a fixed one), status (active / inactive),
    start_date, end_date, csrf_token; id on edit. order.php applies it: percent of the price, at most the cap; fixed: the value.
*}
{$nf_pct = ($coupon['type']|default:'fixed') == 'percent'}
<form class="ls st mc nf nf-cp" method="post" role="form" autocomplete="off"
    action="{if $nf_edit}{Text::url('coupons/edit-post')}{else}{Text::url('coupons/add-post')}{/if}"
    data-cur="{$_c['currency_code']|escape}" data-t-off="{Lang::T('off')|escape}" data-t-most="{Lang::T('at most')|escape}"
    data-t-any="{Lang::T('Any order')|escape}" data-t-from="{Lang::T('Orders from')|escape}" data-t-nolimit="{Lang::T('No limit')|escape}"
    data-t-times="{Lang::T('times')|escape}" data-t-used="{Lang::T('used')|escape}" data-t-end="{Lang::T('The end date cannot be before the start date')|escape}">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    {if $nf_edit}<input type="hidden" name="id" value="{$coupon['id']|escape}">{/if}
    <div class="nf__grid">
        <div class="nf__main">
            <section class="ls-card">
                <header class="st-card-head">
                    <div>
                        <h3><i class="fa fa-tags" aria-hidden="true"></i> {if $nf_edit}{Lang::T('Edit Coupon')}{else}{Lang::T('Add Coupon')}{/if}</h3>
                        <small>{Lang::T('A code customers enter when they buy a package, for a discount.')}</small>
                    </div>
                </header>
                <div class="st-card-body">
                    <div class="nf__row">
                        <div class="nf__field">
                            <label class="nf__label" for="code">{Lang::T('Coupon Code')} {if $nf_edit}<small>{Lang::T('Cannot be change after saved')}</small>{/if}</label>
                            {if $nf_edit}
                            <input type="text" class="form-control nf__ro nf-code-input" id="code" name="code" maxlength="50" readonly value="{$coupon['code']|escape}">
                            {else}
                            <div class="nf-input-btn">
                                <input type="text" class="form-control nf-code-input" name="code" id="code" maxlength="50" required
                                    pattern="[A-Za-z0-9]+" title="{Lang::T('Letters and numbers only')}" spellcheck="false" placeholder="SAVE10">
                                <button type="button" class="ls-btn" id="nfRandomCode"><i class="fa fa-random" aria-hidden="true"></i> {Lang::T('Random')}</button>
                            </div>
                            {/if}
                        </div>
                        <div class="nf__field">
                            <span class="nf__label">{Lang::T('Type')}</span>
                            <div class="nf-seg" role="radiogroup" aria-label="{Lang::T('Type')}">
                                <label><input type="radio" name="type" value="fixed" {if !$nf_pct}checked{/if} data-nf-type> <span>{Lang::T('Fixed Discount')}</span></label>
                                <label><input type="radio" name="type" value="percent" {if $nf_pct}checked{/if} data-nf-type> <span>{Lang::T('Percent Discount')}</span></label>
                            </div>
                        </div>
                    </div>
                    <p class="nf__hint nf__hint--tight">{Lang::T('Unique code for the coupon')}.</p>

                    <div class="nf__row nf__row--fit">
                        <div class="nf__field">
                            <label class="nf__label" for="value">{Lang::T('Discount Value')}</label>
                            <div class="nf__money" data-nf-cur><span aria-hidden="true" data-nf-unit>{if $nf_pct}%{else}{$_c['currency_code']|escape}{/if}</span>
                                <input type="number" class="form-control" name="value" id="value" min="0.01" step="0.01" {if $nf_pct}max="100"{/if} required
                                    value="{$coupon['value']|default:''|escape}" placeholder="0" data-nf-sum></div>
                        </div>
                        <div class="nf__field" data-nf-cap {if !$nf_pct}style="display:none"{/if}>
                            <label class="nf__label" for="max_discount_amount">{Lang::T('Max Discount Amount')}</label>
                            <div class="nf__money"><span aria-hidden="true">{$_c['currency_code']|escape}</span>
                                <input type="number" class="form-control" name="max_discount_amount" id="max_discount_amount" min="0.01" step="0.01"
                                    value="{if ($coupon['max_discount_amount']|default:0) > 0}{$coupon['max_discount_amount']|escape}{/if}" placeholder="0" {if !$nf_pct}disabled{else}required{/if} data-nf-sum></div>
                        </div>
                        <div class="nf__field">
                            <label class="nf__label" for="min_order_amount">{Lang::T('Minimum Order Amount')}</label>
                            <div class="nf__money"><span aria-hidden="true">{$_c['currency_code']|escape}</span>
                                <input type="number" class="form-control" name="min_order_amount" id="min_order_amount" min="0" step="0.01" required
                                    value="{$coupon['min_order_amount']|default:0|escape}" placeholder="0" data-nf-sum></div>
                        </div>
                    </div>
                    <p class="nf__hint nf__hint--tight">{Lang::T('A percent coupon takes that share of the price, up to the max discount. Minimum order: 0 for any order.')}</p>

                    <div class="nf__field">
                        <label class="nf__label" for="description">{Lang::T('Description')}</label>
                        <textarea class="form-control" name="description" id="description" rows="2" required
                            placeholder="{Lang::T('Brief explanation of the coupon')}">{$coupon['description']|default:''|escape}</textarea>
                    </div>
                </div>
            </section>

            <section class="ls-card">
                <header class="st-card-head">
                    <div><h3><i class="fa fa-calendar" aria-hidden="true"></i> {Lang::T('When and how often')}</h3></div>
                </header>
                <div class="st-card-body">
                    <div class="nf__row nf__row--fit">
                        <div class="nf__field">
                            <label class="nf__label" for="start_date">{Lang::T('Start Date')}</label>
                            <input type="date" class="form-control" name="start_date" id="start_date" required
                                value="{$coupon['start_date']|default:''|escape}" data-nf-sum>
                        </div>
                        <div class="nf__field">
                            <label class="nf__label" for="end_date">{Lang::T('End Date')}</label>
                            <input type="date" class="form-control" name="end_date" id="end_date" required
                                value="{$coupon['end_date']|default:''|escape}" data-nf-sum>
                        </div>
                        <div class="nf__field">
                            <label class="nf__label" for="max_usage">{Lang::T('Max Usage')} <small>{Lang::T('0 is Unlimited')}</small></label>
                            <input type="number" class="form-control" name="max_usage" id="max_usage" min="0" step="1" required
                                value="{$coupon['max_usage']|default:0|escape}" data-nf-sum>
                        </div>
                    </div>
                    <p class="nf__hint nf__hint--tight">{Lang::T('Maximum number of times this coupon can be used 0 is Unlimited')}{if $nf_edit} &middot; {Lang::T('Used')}: {$coupon['usage_count']|default:0|escape}{/if}</p>
                </div>
            </section>
        </div>

        <aside class="nf__side">
            <section class="ls-card">
                <header class="st-card-head">
                    <div><h3><i class="fa fa-eye" aria-hidden="true"></i> {Lang::T('Summary')}</h3></div>
                </header>
                <div class="mc-rows nf__switches">
                    <input type="hidden" name="status" value="inactive">
                    <label class="mc-row mc-row--switch">
                        <input type="checkbox" name="status" value="active" {if ($coupon['status']|default:'active') == 'active'}checked{/if}>
                        <span class="mc-row__icon"><i class="fa fa-power-off" aria-hidden="true"></i></span>
                        <span class="mc-row__text">
                            <b>{Lang::T('Active')}</b>
                            <small>{Lang::T('Customers can use it between its dates while it is active.')}</small>
                        </span>
                        <span class="mc-switch__track" aria-hidden="true"></span>
                    </label>
                </div>
                <dl class="nf__facts nf-sum" aria-live="polite">
                    <div><dt>{Lang::T('Discount')}</dt><dd data-sum="discount">&ndash;</dd></div>
                    <div><dt>{Lang::T('Orders')}</dt><dd data-sum="orders">&ndash;</dd></div>
                    <div><dt>{Lang::T('Uses')}</dt><dd data-sum="uses">&ndash;</dd></div>
                    <div><dt>{Lang::T('Valid')}</dt><dd data-sum="valid">&ndash;</dd></div>
                </dl>
                <footer class="st-card-foot">
                    <a class="ls-btn" href="{Text::url('coupons')}"><i class="fa fa-times" aria-hidden="true"></i> {Lang::T('Cancel')}</a>
                    {* type=button: the footer script spins every submit button, which fights the confirm dialog; ask() submits the form *}
                    <button type="button" class="ls-btn ls-btn--primary" data-nf-save
                        onclick="return this.form.reportValidity() && ask(this, '{if $nf_edit}{Lang::T('Save the changes to this coupon?')|escape:'javascript'}{else}{Lang::T('Add this coupon?')|escape:'javascript'}{/if}')">
                        <i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save')}</button>
                </footer>
            </section>
        </aside>
    </div>
</form>

{literal}
<script>
(function () {
    var form = document.querySelector('form.nf-cp');
    var $ = function (s) { return form.querySelector(s); };
    var t = function (k) { return form.getAttribute('data-t-' + k); };
    var cur = form.getAttribute('data-cur');
    var money = function (n) { return cur + ' ' + Number(n).toLocaleString(undefined, { maximumFractionDigits: 2 }); };
    var put = function (k, v) { $('[data-sum="' + k + '"]').textContent = v || '–'; };
    var pct = function () { return $('input[name=type][value=percent]').checked; };
    // percent: value up to 100, shown with %, and a cap; fixed: an amount, no cap (order.php does not use it)
    var type = function () {
        var p = pct(), cap = $('#max_discount_amount');
        $('[data-nf-unit]').textContent = p ? '%' : cur;
        if (p) $('#value').max = '100'; else $('#value').removeAttribute('max');
        $('[data-nf-cap]').style.display = p ? '' : 'none';
        cap.disabled = !p; cap.required = p;
        sum();
    };
    // the end cannot be before the start
    var dates = function () {
        var s = $('#start_date').value, e = $('#end_date');
        e.min = s || '';
        e.setCustomValidity(s && e.value && e.value < s ? e.getAttribute('data-t-bad') || ' ' : '');
    };
    var sum = function () {
        var v = parseFloat($('#value').value), cap = parseFloat($('#max_discount_amount').value);
        if (isFinite(v) && v > 0) {
            put('discount', pct() ? v + '% ' + t('off') + (isFinite(cap) && cap > 0 ? ', ' + t('most') + ' ' + money(cap) : '') : money(v) + ' ' + t('off'));
        } else put('discount', '');
        var min = parseFloat($('#min_order_amount').value);
        put('orders', isFinite(min) && min > 0 ? t('from') + ' ' + money(min) : t('any'));
        var u = parseInt($('#max_usage').value, 10);
        put('uses', !isFinite(u) || u <= 0 ? t('nolimit') : u + ' ' + t('times'));
        var s = $('#start_date').value, e = $('#end_date').value;
        put('valid', s || e ? (s || '…') + ' – ' + (e || '…') : '');
    };
    form.querySelectorAll('[data-nf-type]').forEach(function (r) { r.addEventListener('change', type); });
    form.querySelectorAll('[data-nf-sum]').forEach(function (e) { e.addEventListener('input', sum); e.addEventListener('change', sum); });
    $('#start_date').addEventListener('change', dates);
    $('#end_date').addEventListener('change', dates);
    var rnd = document.getElementById('nfRandomCode');
    if (rnd) rnd.addEventListener('click', function () {
        // 8 letters and digits, from the browser's random source
        var abc = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789', a = new Uint32Array(8), out = '';
        (window.crypto || window.msCrypto).getRandomValues(a);
        for (var i = 0; i < a.length; i++) out += abc.charAt(a[i] % abc.length);
        $('#code').value = out;
    });
    $('#end_date').setAttribute('data-t-bad', form.getAttribute('data-t-end') || 'End date is before the start date');
    // Enter in a one-line field saves like the button: the browser's checks, then the confirm (ask() submits the form)
    form.addEventListener('keydown', function (e) {
        var el = e.target;
        if (e.key !== 'Enter' || e.isComposing || !el.matches('input:not([type=checkbox]):not([type=radio]):not([type=button])')) return;
        e.preventDefault();
        var btn = $('[data-nf-save]');
        if (!btn.disabled) btn.click();
    });
    type();
    dates();
})();
</script>
{/literal}
