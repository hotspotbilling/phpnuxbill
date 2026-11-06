{include file="customer/header-public.tpl"}

<div class="login-container">
    <div class="login-card legal-card">
        <div class="logo-section">
            <div class="brand-name">{$_c['CompanyName']}</div>
            <div class="subtitle">{$legal_title|escape}</div>
        </div>

        <div class="legal-body">
            {include file="$PAGES_PATH/$legal_file.html"}
        </div>

        <a href="{Text::url('login')}" class="forgot-link" style="display: block; text-align: center; margin-top: 1.5rem;">{Lang::T('Back to Login')}</a>
    </div>
</div>

{include file="customer/footer-public.tpl"}
