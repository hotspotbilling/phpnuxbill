{include file="sections/header.tpl"}
<!-- plan-recharge -->
{*
    Recharge Account: pick a customer, a package and how it is paid; plan.php (recharge-confirm) shows what will happen
    before anything is charged. Field names: id_customer, type, server, plan, using. custom.js loads the routers into #server
    when one of #Hot / #POE / #VPN is chosen, and that router's packages into #plan. #personSelect is turned into a customer
    search by the controller's xfooter ($cust: a customer chosen beforehand, from their page).
*}
<form class="ls st mc nf nf-pay" method="post" role="form" autocomplete="off" action="{Text::url('plan/recharge-confirm')}"
    data-t-vouchers="{Lang::T('Select Plans')|escape}">
    <div class="nf__grid">
        <div class="nf__main">
            <section class="ls-card">
                <header class="st-card-head">
                    <div>
                        <h3><i class="fa fa-bolt" aria-hidden="true"></i> {Lang::T('Recharge Account')}</h3>
                        <small>{Lang::T('Give a customer a package. You see what will happen before anything is charged.')}</small>
                    </div>
                </header>
                <div class="st-card-body">
                    <div class="nf__field">
                        <label class="nf__label" for="{if $cust}nfCust{else}personSelect{/if}">{Lang::T('Select Account')}</label>
                        <select {if $cust}id="nfCust"{else}id="personSelect"{/if} class="form-control select2" name="id_customer" required
                            data-placeholder="{Lang::T('Select a customer')}...">
                            {if $cust}
                                <option value="{$cust['id']|escape}">{$cust['username']|escape} &bull; {$cust['fullname']|escape} &bull; {$cust['email']|escape}</option>
                            {/if}
                        </select>
                    </div>

                    <div class="nf__field">
                        <span class="nf__label">{Lang::T('Type')}</span>
                        <div class="nf-seg nf-seg--3" role="radiogroup" aria-label="{Lang::T('Type')}">
                            <label><input type="radio" id="Hot" name="type" value="Hotspot" checked> <span>{Lang::T('Hotspot Plans')}</span></label>
                            <label><input type="radio" id="POE" name="type" value="PPPOE"> <span>{Lang::T('PPPOE Plans')}</span></label>
                            <label><input type="radio" id="VPN" name="type" value="VPN"> <span>{Lang::T('VPN Plans')}</span></label>
                        </div>
                    </div>

                    <div class="nf__row">
                        <div class="nf__field">
                            <label class="nf__label" for="server">{Lang::T('Routers')}</label>
                            <select id="server" data-type="server" name="server" class="form-control select2" required>
                                <option value="">{Lang::T('Select Routers')}</option>
                            </select>
                        </div>
                        <div class="nf__field">
                            <label class="nf__label" for="plan">{Lang::T('Service Plan')}</label>
                            <select id="plan" name="plan" class="form-control select2" required>
                                <option value="">{Lang::T('Select Plans')}</option>
                            </select>
                        </div>
                    </div>
                    <p class="nf__hint nf__hint--tight">{Lang::T('The packages of the chosen type on the chosen router.')}</p>

                    <div class="nf__field">
                        <label class="nf__label" for="using">{Lang::T('Using')}</label>
                        <select id="using" name="using" class="form-control" required>
                            {foreach $usings as $using}
                                <option value="{trim($using)|escape}">{trim(ucWords($using))|escape}</option>
                            {/foreach}
                            {if $_c['enable_balance'] eq 'yes'}
                                <option value="balance">{Lang::T('Customer Balance')}</option>
                            {/if}
                            {if in_array($_admin['user_type'],['SuperAdmin','Admin'])}
                                <option value="zero">{$_c['currency_code']|escape} 0</option>
                            {/if}
                        </select>
                        <p class="nf__hint">{Lang::T('Postpaid Recharge for the first time use')} {$_c['currency_code']|escape} 0</p>
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
                    <div><dt>{Lang::T('Type')}</dt><dd data-sum="type">&ndash;</dd></div>
                    <div><dt>{Lang::T('Routers')}</dt><dd data-sum="server">&ndash;</dd></div>
                    <div><dt>{Lang::T('Service Plan')}</dt><dd data-sum="plan">&ndash;</dd></div>
                    <div><dt>{Lang::T('Using')}</dt><dd data-sum="using">&ndash;</dd></div>
                </dl>
                <footer class="st-card-foot">
                    <a class="ls-btn" href="{Text::url('customers/list')}"><i class="fa fa-times" aria-hidden="true"></i> {Lang::T('Cancel')}</a>
                    {* type=button: the footer script spins every submit button, which fights the confirm dialog; ask() submits the form *}
                    <button type="button" class="ls-btn ls-btn--primary"
                        onclick="return this.form.reportValidity() && ask(this, '{Lang::T('Continue the Recharge process')|escape:'javascript'}?')">
                        <i class="fa fa-bolt" aria-hidden="true"></i> {Lang::T('Recharge')}</button>
                </footer>
            </section>
        </aside>
    </div>
</form>

{literal}
<script>
    // the summary follows the form; custom.js is loaded by the footer, so this waits for the page
    window.addEventListener('load', function () {
        var form = document.querySelector('form.nf-pay'), $ = window.jQuery;
        var text = function (sel) { var e = form.querySelector(sel); return e && e.selectedIndex >= 0 && e.value ? e.options[e.selectedIndex].text.replace(/\s+/g, ' ').trim() : ''; };
        var put = function (k, v) { form.querySelector('[data-sum="' + k + '"]').textContent = v || '–'; };
        var sum = function () {
            put('cust', text('select[name=id_customer]'));
            var t = form.querySelector('input[name=type]:checked');
            put('type', t ? t.closest('label').textContent.trim() : '');
            put('server', text('#server'));
            put('plan', text('#plan'));
            put('using', text('#using'));
        };
        form.addEventListener('change', sum);
        if ($) {
            $(form).find('select').on('change', sum); // select2 fires jQuery's change
            // a type is chosen from the start, so the routers load at once (custom.js listens on the radios)
            $('#Hot:checked, #POE:checked, #VPN:checked').trigger('change');
        }
        sum();
    });
</script>
{/literal}

{include file="sections/footer.tpl"}
