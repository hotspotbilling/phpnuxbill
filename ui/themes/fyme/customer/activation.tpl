{include file="customer/header.tpl"}
<!-- user-activation -->

<div class="row">
    <div class="col-md-8">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">{Lang::T('Order Voucher')}</h3>
            </div>
            <div class="box-body">
                {include file="$PAGES_PATH/Order_Voucher.html"}
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">{Lang::T('Voucher Activation')}</h3>
            </div>
            <div class="box-body">
                <form method="post" role="form" action="{Text::url('voucher/activation-post')}">
                    <div class="form-group">
                        <label class="fyme-label">{Lang::T('Voucher Code')}</label>
                        <div class="fyme-input-wrapper">
                             <div class="fyme-input-icon">
                                <i data-feather="tag"></i>
                            </div>
                            <input type="text" class="fyme-input" id="code" name="code" value="{$code|escape:'html'}"
                                placeholder="XXXX-XXXX-XXXX" style="padding-right: 40px !important;">

                            <!-- Scan Button inside input -->
                            <a href="{$app_url|escape:'html'}/scan/?back={urlencode(Text::url('voucher/activation&code='))}"
                               style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);">
                                <i data-feather="maximize"></i>
                            </a>
                        </div>
                    </div>
                    <div class="form-group text-center" style="margin-top: 1.5rem;">
                        <button class="btn-fyme btn-fyme-primary" type="submit">
                            {Lang::T('Recharge')} <i data-feather="zap" style="width: 16px; margin-left: 6px;"></i>
                        </button>
                        <div style="margin-top: 1rem;">
                            <a href="{Text::url('home')}" class="btn-fyme btn-fyme-outline">{Lang::T('Cancel')}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{include file="customer/footer.tpl"}
