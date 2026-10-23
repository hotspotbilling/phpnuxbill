{include file="customer/header-public.tpl"}

<style>
    /* Accessibility: Visually hidden labels for screen readers */
    .visually-hidden {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        padding: 0 !important;
        margin: -1px !important;
        overflow: hidden !important;
        clip: rect(0, 0, 0, 0) !important;
        white-space: nowrap !important;
        border: 0 !important;
    }

    /* QR Scan Button Styling */
    .qr-scan-btn {
        background: none;
        border: none;
        color: inherit;
        cursor: pointer;
        padding: 0 12px;
        font-size: 1.1em;
        transition: color 0.2s ease;
    }

    .qr-scan-btn:hover {
        color: var(--primary-color, #007bff);
    }
</style>

<div class="login-container">
    <div class="announcement-panel">
        <div class="announcement-header">
            <div class="announcement-icon">
                <i class="fas fa-bullhorn"></i>
            </div>
            <h3>{Lang::T('Announcement')}</h3>
        </div>

        <div class="announcements">
            <div class="announcement-content">
                <p>{$Announcement = "{$PAGES_PATH}/Announcement.html"}
                    {if file_exists($Announcement)}
                    {include file=$Announcement}
                    {/if}
                </p>
            </div>
        </div>
    </div>

    <div class="login-card">
        <div class="logo-section">
            <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle theme" aria-pressed="false">
                <i class="fas fa-sun" id="themeIcon"></i>
            </button>
            <h1 class="brand-name">{$_c['CompanyName']}</h1>
            <p class="subtitle">{Lang::T('Voucher Activation')}</p>
        </div>

        <!-- Voucher Activation Form -->
        <form class="login-form" action="{Text::url('login/activation')}" method="post" id="voucherForm" data-csrf-token="{$csrf_token}">
            <input type="hidden" name="csrf_token" value="{$csrf_token}">

            <div class="form-group">
                <label for="username" class="visually-hidden">
                    {if $_c['registration_username'] == 'phone'}{Lang::T('Phone Number')}{elseif $_c['registration_username'] == 'email'}{Lang::T('Email')}{else}{Lang::T('Username')}{/if}
                </label>
                <div class="input-wrapper">
                    {if $_c['registration_username'] == 'phone'}
                    <i class="fas fa-phone-alt input-icon"></i>
                    {elseif $_c['registration_username'] == 'email'}
                    <i class="fas fa-envelope input-icon"></i>
                    {else}
                    <i class="fas fa-user input-icon"></i>
                    {/if}
                    <input type="text" id="username" name="username"
                           placeholder="{if $_c['country_code_phone']!= '' || $_c['registration_username'] == 'phone'}{$_c['country_code_phone']} {Lang::T('Phone Number')}{elseif $_c['registration_username'] == 'email'}{Lang::T('Email')}{else}{Lang::T('Username')}{/if}"
                           aria-label="{if $_c['registration_username'] == 'phone'}{Lang::T('Phone Number')}{elseif $_c['registration_username'] == 'email'}{Lang::T('Email')}{else}{Lang::T('Username')}{/if}">
                </div>
            </div>

            <div class="form-group">
                <label for="voucher" class="visually-hidden">{Lang::T('Enter voucher code here')}</label>
                <div class="input-wrapper">
                    <i class="fas fa-ticket-alt input-icon"></i>
                    <input type="text" id="voucher" name="voucher"
                           placeholder="{Lang::T('Enter voucher code here')}"
                           value="{$code}"
                           aria-label="{Lang::T('Enter voucher code here')}">
                    <button type="button" class="qr-scan-btn" id="qrScanBtn"
                            aria-label="{Lang::T('Scan QR code')}"
                            onclick="window.location.href='{APP_URL}/scan/?back={urlencode(Text::url('login&code='))}'">
                        <i class="fas fa-qrcode"></i>
                    </button>
                </div>
            </div>

            {if !empty($captcha_widget)}
                {$captcha_widget}
            {/if}

            <button type="submit" class="login-btn">
                <span class="btn-text">{Lang::T('Activate Voucher')}</span>
                <div class="btn-loader">
                    <div class="spinner"></div>
                </div>
            </button>
        </form>

        <div class="divider">
            <span>{Lang::T('OR')}</span>
        </div>

        <!-- Voucher Only Form -->
        <form class="login-form" action="{Text::url('login/activation')}" method="post" id="voucherOnlyForm" data-csrf-token="{$csrf_token}">
            <input type="hidden" name="csrf_token" value="{$csrf_token}">

            <div class="form-group">
                <label for="voucher_only" class="visually-hidden">{Lang::T('Enter voucher code here')}</label>
                <div class="input-wrapper">
                    <i class="fas fa-ticket-alt input-icon"></i>
                    <input type="text" id="voucher_only" name="voucher_only"
                           placeholder="{Lang::T('Enter voucher code here')}"
                           value="{$code}"
                           aria-label="{Lang::T('Enter voucher code here')}">
                    <button type="button" class="qr-scan-btn" id="qrScanBtn2"
                            aria-label="{Lang::T('Scan QR code')}"
                            onclick="window.location.href='{APP_URL}/scan/?back={urlencode(Text::url('login&code='))}'">
                        <i class="fas fa-qrcode"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="login-btn">
                <span class="btn-text">{Lang::T('Activate Voucher Only')}</span>
                <div class="btn-loader">
                    <div class="spinner"></div>
                </div>
            </button>
        </form>
    </div>
</div>
<script>
    window.successText = '{Lang::T("Voucher activated successfully")}! {Lang::T("Redirecting...")}';
    window.fillAll = '{Lang::T("Please fill in all fields")}';
    window.invalidCode = '{Lang::T("Invalid voucher code")}';
    window.voucherExpired = '{Lang::T("Voucher has expired")}';
    window.themeChangeText = '{Lang::T("Theme changed to")}';
    window.securityErrorText = '{Lang::T("Security token missing. Refreshing page...")}';
    window.connectionError = '{Lang::T("Connection problem. Please check your internet and try again.")}';
    window.unknownError = '{Lang::T("An unknown error occurred. Please try again later.")}';
</script>

{include file="customer/footer-public.tpl"}