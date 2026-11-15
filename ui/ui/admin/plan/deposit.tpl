{include file="sections/header.tpl"}
<!-- plan-deposit -->
{*
    Refill Balance: add to a customer's balance, from a balance package or a custom amount. plan.php (deposit-post) reads
    id_customer, id_plan, amount (a custom amount wins over the package), note and svoucher: a one-time token, so the invoice
    page can be reloaded without adding the balance again. Only the chosen one of id_plan / amount is enabled, so only it is
    sent. csrf_token is assigned by boot.php. #personSelect is turned into a customer search by the controller's xfooter.
*}
<form class="ls st mc nf nf-pay nf-dep" method="post" role="form" autocomplete="off" action="{Text::url('plan/deposit-post')}"
    data-balance-url="{Text::url('autoload/balance/')}" data-cur="{$_c['currency_code']|escape}">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    <input type="hidden" name="svoucher" value="{App::getToken()}">
    <div class="nf__grid">
        <div class="nf__main">
            <section class="ls-card">
                <header class="st-card-head">
                    <div>
                        <h3><i class="fa fa-credit-card" aria-hidden="true"></i> {Lang::T('Refill Balance')}</h3>
                        <small>{Lang::T('Add to a customer\'s balance, from a balance package or any amount.')}</small>
                    </div>
                </header>
                <div class="st-card-body">
                    <div class="nf__field">
                        <label class="nf__label" for="personSelect">{Lang::T('Select Account')}
                            <small data-nf-bal hidden>{Lang::T('Balance')}: <b data-nf-bal-value>&ndash;</b></small></label>
                        <select id="personSelect" class="form-control select2" name="id_customer" required
                            data-placeholder="{Lang::T('Select a customer')}..."></select>
                    </div>

                    <div class="nf__field">
                        <span class="nf__label">{Lang::T('Select Balance Package or Custom Amount')}</span>
                        <div class="nf-seg" role="radiogroup" aria-label="{Lang::T('Select Balance Package or Custom Amount')}">
                            <label><input type="radio" name="nf_from" value="plan" {if $p}checked{/if} data-nf-from> <span>{Lang::T('Balance Package')}</span></label>
                            <label><input type="radio" name="nf_from" value="amount" {if !$p}checked{/if} data-nf-from> <span>{Lang::T('Custom Amount')}</span></label>
                        </div>
                    </div>

                    <div class="nf__field" data-nf-from-plan>
                        <label class="nf__label" for="planSelect">{Lang::T('Balance Package')} <small><a href="{Text::url('services/balance')}">{Lang::T('Balance Plans')}</a></small></label>
                        <select id="planSelect" class="form-control select2" name="id_plan" data-placeholder="{Lang::T('Select Plans')}...">
                            <option value=""></option>
                            {foreach $p as $pl}
                                <option value="{$pl['id']|escape}" data-price="{$pl['price']|escape}">{if $pl['enabled'] neq 1}{Lang::T('Disabled')} &bull; {/if}{$pl['name_plan']|escape} - {Lang::moneyFormat($pl['price'])}</option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="nf__field" data-nf-from-amount>
                        <label class="nf__label" for="amount">{Lang::T('Balance Amount')}</label>
                        <div class="nf__money"><span aria-hidden="true">{$_c['currency_code']|escape}</span>
                            <input type="number" class="form-control" id="amount" name="amount" min="0.01" step="any" placeholder="0"></div>
                    </div>

                    <div class="nf__field">
                        <label class="nf__label" for="note">{Lang::T('Note')} <small>{Lang::T('Optional')}</small></label>
                        <textarea class="form-control" id="note" name="note" rows="2" maxlength="255"></textarea>
                    </div>
                </div>
            </section>
        </div>

        <aside class="nf__side">
            <section class="ls-card">
                <header class="st-card-head">
                    <div><h3><i class="fa fa-eye" aria-hidden="true"></i> {Lang::T('Summary')}</h3></div>
                </header>
                <dl class="nf__facts nf-sum" aria-live="polite">
                    <div><dt>{Lang::T('Customer')}</dt><dd data-sum="cust">&ndash;</dd></div>
                    <div><dt>{Lang::T('Balance')}</dt><dd data-sum="balance">&ndash;</dd></div>
                    <div><dt>{Lang::T('Adds')}</dt><dd data-sum="adds">&ndash;</dd></div>
                </dl>
                <footer class="st-card-foot">
                    <a class="ls-btn" href="{Text::url('customers/list')}"><i class="fa fa-times" aria-hidden="true"></i> {Lang::T('Cancel')}</a>
                    {* type=button: the footer script spins every submit button, which fights the confirm dialog; ask() submits the form *}
                    <button type="button" class="ls-btn ls-btn--primary"
                        onclick="return this.form.reportValidity() && ask(this, '{Lang::T('Continue the Customer Balance top-up process')|escape:'javascript'}?')">
                        <i class="fa fa-plus" aria-hidden="true"></i> {Lang::T('Recharge')}</button>
                </footer>
            </section>
        </aside>
    </div>
</form>

{literal}
<script>
    window.addEventListener('load', function () {
        var form = document.querySelector('form.nf-dep'), $ = window.jQuery;
        var cur = form.getAttribute('data-cur');
        var plan = form.querySelector('#planSelect'), amount = form.querySelector('#amount');
        var put = function (k, v) { form.querySelector('[data-sum="' + k + '"]').textContent = v || '–'; };
        var money = function (n) { return cur + ' ' + Number(n).toLocaleString(undefined, { maximumFractionDigits: 2 }); };
        // only the chosen source is enabled, so only it is sent and checked
        var from = function () {
            var usePlan = form.querySelector('input[name=nf_from]:checked').value === 'plan';
            form.querySelector('[data-nf-from-plan]').style.display = usePlan ? '' : 'none';
            form.querySelector('[data-nf-from-amount]').style.display = usePlan ? 'none' : '';
            plan.disabled = !usePlan; plan.required = usePlan;
            amount.disabled = usePlan; amount.required = !usePlan;
            sum();
        };
        var sum = function () {
            var c = form.querySelector('select[name=id_customer]');
            put('cust', c.selectedIndex >= 0 && c.value ? c.options[c.selectedIndex].text.replace(/\s+/g, ' ').trim() : '');
            if (!plan.disabled) {
                var o = plan.options[plan.selectedIndex];
                put('adds', o && o.value ? money(o.getAttribute('data-price')) : '');
            } else {
                var a = parseFloat(amount.value);
                put('adds', isFinite(a) && a > 0 ? money(a) : '');
            }
        };
        // the customer's balance now, from autoload/balance/{id}/1 (already formatted)
        var balance = function () {
            var c = form.querySelector('select[name=id_customer]').value;
            sum();
            if (!c || !$) return;
            $.get(form.getAttribute('data-balance-url') + encodeURIComponent(c) + '/1', function (data) {
                var v = $('<div>').html(data).text().trim();
                put('balance', v);
                form.querySelector('[data-nf-bal-value]').textContent = v;
                form.querySelector('[data-nf-bal]').hidden = false;
            });
        };
        form.querySelectorAll('[data-nf-from]').forEach(function (r) { r.addEventListener('change', from); });
        amount.addEventListener('input', sum);
        if ($) {
            $('#personSelect').on('change', balance); // select2 fires jQuery's change
            $(plan).on('change', sum);
        }
        from();
    });
</script>
{/literal}

{include file="sections/footer.tpl"}
