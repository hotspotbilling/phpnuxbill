{*
    The balance package form, shared by add.tpl ($nf_edit false, $d empty) and edit.tpl ($nf_edit true, $d the package).
    Field names are what services.php reads: balance-add-post: name, price, enabled; balance-edit-post: id, name, price,
    price_old, enabled. csrf_token is assigned by boot.php.
*}
{$nf_tax = 0}
{if $_c['enable_tax'] == 'yes'}{if $_c['tax_rate'] == 'custom'}{$nf_tax = floatval($_c['custom_tax_rate'])}{else}{$nf_tax = floatval($_c['tax_rate']) * 100}{/if}{/if}
<form class="ls st mc nf nf-bal" method="post" role="form" autocomplete="off"
    action="{if $nf_edit}{Text::url('services/balance-edit-post')}{else}{Text::url('services/balance-add-post')}{/if}"
    data-tax="{$nf_tax}" data-cur="{$_c['currency_code']|escape}" data-t-tax="{Lang::T('incl. tax')|escape}" data-t-off="{Lang::T('off')|escape}">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    {if $nf_edit}<input type="hidden" name="id" value="{$d['id']|escape}">{/if}

    <div class="nf__grid">
        <div class="nf__main">
            <section class="ls-card">
                <header class="st-card-head">
                    <div>
                        <h3><i class="fa fa-credit-card" aria-hidden="true"></i> {if $nf_edit}{Lang::T('Edit Service Package')}{else}{Lang::T('Add Service Package')}{/if}</h3>
                        <small>{Lang::T('A balance top-up customers can buy.')}</small>
                    </div>
                </header>
                <div class="st-card-body">
                    {* one row: name and price (and the price before a discount on edit) *}
                    <div class="nf__row{if $nf_edit} nf__row--3{/if}">
                        <div class="nf__field">
                            <label class="nf__label" for="name">{Lang::T('Package Name')}</label>
                            <input type="text" class="form-control" id="name" name="name" maxlength="40" required
                                value="{$d['name_plan']|default:''|escape}" placeholder="{Lang::T('Topup')} 100">
                        </div>
                        <div class="nf__field">
                            <label class="nf__label" for="price">{Lang::T('Package Price')}
                                {if $nf_tax > 0}<small>{number_format($nf_tax, 2)} % {Lang::T('Tax Rates will be added')}</small>{/if}</label>
                            <div class="nf__money"><span aria-hidden="true">{$_c['currency_code']|escape}</span>
                                <input type="number" class="form-control" id="price" name="price" min="0" step="any" required
                                    value="{$d['price']|default:''|escape}" placeholder="0" data-nf-sum></div>
                        </div>
                        {if $nf_edit}
                        <div class="nf__field">
                            <label class="nf__label" for="price_old">{Lang::T('Price Before Discount')} <small>{Lang::T('Optional')}</small></label>
                            <div class="nf__money"><span aria-hidden="true">{$_c['currency_code']|escape}</span>
                                <input type="number" class="form-control" id="price_old" name="price_old" min="0" step="any"
                                    value="{$d['price_old']|default:''|escape}" placeholder="0" data-nf-sum></div>
                        </div>
                        {/if}
                    </div>
                    {if $nf_edit}<p class="nf__hint nf__hint--tight">{Lang::T('For Discount Rate, this is price before get discount, must be more expensive with real price')}</p>{/if}
                </div>
            </section>
        </div>

        <aside class="nf__side">
            <section class="ls-card">
                <header class="st-card-head">
                    <div><h3><i class="fa fa-eye" aria-hidden="true"></i> {Lang::T('Summary')}</h3></div>
                </header>
                <div class="mc-rows nf__switches">
                    <input type="hidden" name="enabled" value="0">
                    <label class="mc-row mc-row--switch">
                        <input type="checkbox" name="enabled" value="1" {if ($d['enabled']|default:1) == 1}checked{/if}>
                        <span class="mc-row__icon"><i class="fa fa-power-off" aria-hidden="true"></i></span>
                        <span class="mc-row__text">
                            <b>{Lang::T('Active')}</b>
                            <small>{Lang::T('Customers can buy it while it is active.')}</small>
                        </span>
                        <span class="mc-switch__track" aria-hidden="true"></span>
                    </label>
                </div>
                <dl class="nf__facts nf-sum" aria-live="polite">
                    <div><dt>{Lang::T('Price')}</dt><dd data-sum="price">&ndash;</dd></div>
                </dl>
                <footer class="st-card-foot">
                    <a class="ls-btn" href="{Text::url('services/balance')}"><i class="fa fa-times" aria-hidden="true"></i> {Lang::T('Cancel')}</a>
                    {* type=button: the footer script spins every submit button, which fights the confirm dialog; ask() submits the form *}
                    <button type="button" class="ls-btn ls-btn--primary"
                        onclick="return this.form.reportValidity() && ask(this, '{if $nf_edit}{Lang::T('Continue the process of changing the balance contents?')|escape:'javascript'}{else}{Lang::T('Continue the balance top-up process?')|escape:'javascript'}{/if}')">
                        <i class="fa fa-save" aria-hidden="true"></i> {Lang::T('Save Changes')}</button>
                </footer>
            </section>
        </aside>
    </div>
</form>

{literal}
<script>
(function () {
    var form = document.querySelector('form.nf-bal');
    var cur = form.getAttribute('data-cur'), tax = parseFloat(form.getAttribute('data-tax')) || 0;
    var out = form.querySelector('[data-sum="price"]');
    var sum = function () {
        var price = parseFloat(form.querySelector('#price').value), o = form.querySelector('#price_old'), old = o ? parseFloat(o.value) : NaN;
        if (!isFinite(price)) { out.textContent = '–'; return; }
        var s = cur + ' ' + (tax > 0 ? price * (1 + tax / 100) : price).toLocaleString(undefined, { maximumFractionDigits: 2 });
        if (tax > 0) s += ' (' + form.getAttribute('data-t-tax') + ')';
        if (isFinite(old) && old > price && old > 0) s += ' · ' + Math.round((1 - price / old) * 100) + '% ' + form.getAttribute('data-t-off');
        out.textContent = s;
    };
    form.querySelectorAll('[data-nf-sum]').forEach(function (e) { e.addEventListener('input', sum); });
    sum();
})();
</script>
{/literal}
