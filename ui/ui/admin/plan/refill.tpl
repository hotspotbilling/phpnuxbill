{include file="sections/header.tpl"}
<!-- plan-refill -->
{*
    Refill Account: recharge a customer with an unused voucher. plan.php (refill-post) reads id_customer and code;
    csrf_token is assigned by boot.php. #personSelect is turned into a customer search by the controller's xfooter.
*}
<form class="ls st mc nf nf-pay" method="post" role="form" autocomplete="off" action="{Text::url('plan/refill-post')}">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    <div class="nf__grid">
        <div class="nf__main">
            <section class="ls-card">
                <header class="st-card-head">
                    <div>
                        <h3><i class="fa fa-ticket" aria-hidden="true"></i> {Lang::T('Refill Account')}</h3>
                        <small>{Lang::T('Recharge a customer with an unused voucher code. The voucher\'s package is activated on the account.')}</small>
                    </div>
                </header>
                <div class="st-card-body">
                    <div class="nf__row">
                        <div class="nf__field">
                            <label class="nf__label" for="personSelect">{Lang::T('Select Account')}</label>
                            <select id="personSelect" class="form-control select2" name="id_customer" required
                                data-placeholder="{Lang::T('Select a customer')}..."></select>
                        </div>
                        <div class="nf__field">
                            <label class="nf__label" for="code">{Lang::T('Code Voucher')}</label>
                            <input type="text" class="form-control nf-code-input" id="code" name="code" required maxlength="64"
                                placeholder="{Lang::T('Enter voucher code here')}" spellcheck="false" autocapitalize="off">
                        </div>
                    </div>
                    <p class="nf__hint nf__hint--tight">{Lang::T('The code is checked when you continue: it must exist and not be used yet.')}</p>
                </div>
            </section>
        </div>

        <aside class="nf__side">
            <section class="ls-card">
                <header class="st-card-head">
                    <div><h3><i class="fa fa-check-circle" aria-hidden="true"></i> {Lang::T('Recharge')}</h3>
                        <small>{Lang::T('An invoice is shown when it is done.')}</small></div>
                </header>
                <footer class="st-card-foot">
                    <a class="ls-btn" href="{Text::url('customers/list')}"><i class="fa fa-times" aria-hidden="true"></i> {Lang::T('Cancel')}</a>
                    {* type=button: the footer script spins every submit button, which fights the confirm dialog; ask() submits the form *}
                    <button type="button" class="ls-btn ls-btn--primary"
                        onclick="return this.form.reportValidity() && ask(this, '{Lang::T('Continue the Refill process')|escape:'javascript'}?')">
                        <i class="fa fa-bolt" aria-hidden="true"></i> {Lang::T('Recharge')}</button>
                </footer>
            </section>
        </aside>
    </div>
</form>

{include file="sections/footer.tpl"}
