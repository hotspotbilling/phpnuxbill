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
            <p class="subtitle">{Lang::T('Log in to Member Panel')}</p>
        </div>

        <form class="login-form" id="loginForm" data-csrf-token="{$csrf_token}">
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
                           required
                           aria-label="{if $_c['registration_username'] == 'phone'}{Lang::T('Phone Number')}{elseif $_c['registration_username'] == 'email'}{Lang::T('Email')}{else}{Lang::T('Username')}{/if}">
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="visually-hidden">{Lang::T('Password')}</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" 
                           placeholder="{Lang::T('Password')}" 
                           required
                           aria-label="{Lang::T('Password')}">
                    <button type="button" class="toggle-password" id="togglePassword"
                            aria-label="{Lang::T('Show password')}"
                            aria-pressed="false">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-options">
                <label class="checkbox-container">
                    <input type="checkbox" id="remember">
                    <span class="checkmark"></span>
                    {Lang::T('Remember me')}
                </label>
                <a href="{Text::url('forgot')}" class="forgot-link">{Lang::T('Forgot password?')}</a>
            </div>

            <button type="submit" class="login-btn">
                <span class="btn-text">{Lang::T('Login')}</span>
                <div class="btn-loader">
                    <div class="spinner"></div>
                </div>
            </button>
            {if $_c['disable_registration'] != 'noreg'}
            <div class="divider">
                <span>{Lang::T('OR')}</span>
            </div>

            <div class="connection-options">
                <a href="{Text::url('register')}" class="connection-btn">
                    <i class="fas fa-user-friends"></i>
                    <span>{Lang::T('Register')}</span>
                </a>
            </div>
            {/if}
        </form>
    </div>
</div>

<script>
    // Security: CSRF token moved to form data attribute instead of global window
    window.successText = '{Lang::T("Login successful")}! {Lang::T("Redirecting...")}';
    window.fillAll = '{Lang::T("Please fill in all fields")}';
    window.invalidCredentials = '{Lang::T("Invalid username or password")}';
    window.accountInactive = '{Lang::T("Your account is inactive. Please contact support.")}';
    window.themeChangeText = '{Lang::T("Theme changed to")}';
    window.securityErrorText = '{Lang::T("Security token missing. Refreshing page...")}';
    window.usernameMinLength = '{Lang::T("Username must be at least 3 characters")}';
    window.passwordMinLength = '{Lang::T("Password must be at least 8 characters")}';
    window.tooManyAttempts = '{Lang::T("Too many login attempts. Please wait before trying again.")}';
    window.tokenExpired = '{Lang::T("Security token expired. Please refresh the page and try again.")}';
    window.tokenRefreshed = '{Lang::T("Security token refreshed. Please try again.")}';
    window.tokenRefreshError = '{Lang::T("Failed to refresh security token. Please refresh the page and try again.")}';
    
    // Accessibility: Password toggle labels
    window.showPasswordText = '{Lang::T("Show password")}';
    window.hidePasswordText = '{Lang::T("Hide password")}';
    window.accountSuspended = '{Lang::T("Your account has been suspended. Please contact support.")}';
    window.connectionError = '{Lang::T("Connection problem. Please check your internet and try again.")}';
    window.unknownError = '{Lang::T("An unknown error occurred. Please try again later.")}';
</script>
{include file="customer/footer-public.tpl"}