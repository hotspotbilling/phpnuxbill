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

    /* Register specific layout adjustments */
    .login-container {
        align-items: flex-start;
    }

    .announcement-panel {
        order: -1;
        align-self: flex-start;
    }

    .register-form-wrapper {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
        width: 100%;
        max-width: 880px;
    }

    @media (max-width: 1100px) {
        .login-container {
            flex-direction: column !important;
            align-items: center !important;
        }

        .announcement-panel {
            width: 100% !important;
            max-width: 880px !important;
        }
    }

    @media (max-width: 768px) {
        .register-form-wrapper {
            grid-template-columns: 1fr;
            max-width: 440px;
        }

        .announcement-panel {
            max-width: 440px !important;
        }
    }

    .card-header-section {
        padding: 15px 20px;
        border-bottom: 1px solid var(--card-border);
        background: var(--card-bg);
    }

    .card-title {
        margin: 0;
        font-size: 1em;
        font-weight: 600;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .card-title i {
        color: #2563eb;
        font-size: 1.1em;
    }

    .card-body-section {
        padding: 20px;
    }

    .form-group {
        margin-bottom: 12px;
    }

    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-weight: 500;
        color: var(--text-primary);
        font-size: 0.9em;
    }

    .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .input-icon {
        position: absolute;
        left: 12px;
        color: var(--text-tertiary);
        pointer-events: none;
    }

    .form-control {
        width: 100%;
        padding: 0.5rem 0.5rem 0.5rem 2rem;
        background: var(--input-bg);
        border: 1px solid var(--input-border);
        border-radius: 10px;
        color: var(--text-primary);
        font-size: 0.9em;
        transition: all 0.2s;
        font-family: inherit;
        resize: vertical;
    }


    .form-control:focus {
        background: var(--input-bg-focus);
        border-color: #2563eb;
        outline: none;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .toggle-password {
        position: absolute;
        right: 12px;
        background: none;
        border: none;
        color: var(--text-tertiary);
        cursor: pointer;
        padding: 8px;
        font-size: 1.1em;
        transition: color 0.2s;
        z-index: 10;
    }

    .toggle-password:hover {
        color: #2563eb;
    }

    @media (max-width: 768px) {
        .register-form-wrapper {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="login-container">
    <div class="announcement-panel">
        <div class="announcement-header">
            <div class="announcement-icon">
                <i class="fas fa-bullhorn"></i>
            </div>
            <h3>{Lang::T('Registration Info')}</h3>
        </div>

        <div class="announcements">
            <div class="announcement-content">
                <p>{$InfoPath = "{$PAGES_PATH}/Registration_Info.html"}
                    {if file_exists($InfoPath)}
                    {include file=$InfoPath}
                    {/if}
                </p>
            </div>
        </div>
    </div>

    <form enctype="multipart/form-data" action="{Text::url('register/post')}" method="post"
        class="register-form-wrapper">
        <input type="hidden" name="csrf_token" value="{$csrf_token}">
        <!-- Personal Information Card -->
        <div class="login-card">
            <div class="logo-section">
                <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle theme"
                    aria-pressed="false">
                    <i class="fas fa-sun" id="themeIcon"></i>
                </button>
                <h1 class="brand-name">{$_c['CompanyName']}</h1>
                <p class="subtitle">{Lang::T('Create Your Account')}</p>
            </div>
            <div class="card-header-section">
                <h3 class="card-title">
                    <i class="fas fa-user"></i>
                    {Lang::T('Personal Information')}
                </h3>
            </div>
            <div class="card-body-section">
                <div class="form-group">
                    <label for="username" class="visually-hidden">
                        {if $_c['registration_username'] == 'phone'}{Lang::T('Phone Number')}{elseif
                        $_c['registration_username'] == 'email'}{Lang::T('Email')}{else}{Lang::T('Username')}{/if}
                    </label>
                    <div class="input-wrapper">
                        {if $_c['registration_username'] == 'phone'}
                        <i class="fas fa-phone-alt input-icon"></i>
                        {elseif $_c['registration_username'] == 'email'}
                        <i class="fas fa-envelope input-icon"></i>
                        {else}
                        <i class="fas fa-user input-icon"></i>
                        {/if}
                        <input type="text" id="username" class="form-control" name="username"
                            placeholder="{if $_c['country_code_phone']!= '' || $_c['registration_username'] == 'phone'}{$_c['country_code_phone']} {Lang::T('Phone Number')}{elseif $_c['registration_username'] == 'email'}{Lang::T('Email')}{else}{Lang::T('Username')}{/if}"
                            aria-label="{if $_c['registration_username'] == 'phone'}{Lang::T('Phone Number')}{elseif $_c['registration_username'] == 'email'}{Lang::T('Email')}{else}{Lang::T('Username')}{/if}"
                            required>
                    </div>
                </div>
                {if $_c['photo_register'] == 'yes'}
                <div class="form-group">
                    <label for="photo">{Lang::T('Photo')}</label>
                    <div class="input-wrapper">
                        <i class="fas fa-image input-icon"></i>
                        <input type="file" id="photo" class="form-control" name="photo" accept="image/*" required
                            aria-label="{Lang::T('Photo')}">
                    </div>
                </div>
                {/if}

                <div class="form-group">
                    <label for="fullname">{Lang::T('Full Name')}</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user-tag input-icon"></i>
                        <input type="text" id="fullname" class="form-control" value="{$fullname|escape}" name="fullname" {if
                            $_c['man_fields_fname'] neq 'no' }required{/if} aria-label="{Lang::T('Full Name')}">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">{Lang::T('Email')}</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" class="form-control" placeholder="example@email.com"
                            value="{$email|escape}" name="email" {if $_c['man_fields_email'] neq 'no' }required{/if}
                            aria-label="{Lang::T('Email')}">
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">{Lang::T('Home Address')}</label>
                    <div class="input-wrapper">
                        <i class="fas fa-map-marker-alt input-icon"></i>
                        <textarea id="address" class="form-control" name="address" {if $_c['man_fields_address']
                            neq 'no' }required{/if} aria-label="{Lang::T('Home Address')}">{$address}</textarea>
                    </div>
                </div>
                {if $_c.referral_enabled neq 'no'}
                <div class="form-group">
                    <label for="referral_code">{Lang::T('Referral Code')} <span
                            class="optional">({Lang::T('Optional')})</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-link input-icon"></i>
                        {if isset($referral_code) && !empty($referral_code)}
                        <input type="text" id="referral_code" class="form-control" name="referral_code"
                            value="{$referral_code|escape}" readonly aria-label="{Lang::T('Referral Code')}">
                        {else}
                        <input type="text" id="referral_code" class="form-control" name="referral_code"
                            value="{$referral_code|escape}" placeholder="{Lang::T('Referral Code')} ({Lang::T('Optional')})"
                            aria-label="{Lang::T('Referral Code')}">
                        {/if}
                    </div>
                </div>
                {/if}
                {$customFields}
            </div>
        </div>


        <!-- Password & Submit Card -->
        <div class="login-card">
            <div class="card-header-section">
                <h3 class="card-title">
                    <i class="fas fa-lock"></i>
                    {Lang::T('Set Password')}
                </h3>
            </div>
            <div class="card-body-section">
                <div class="form-group">
                    <label for="password">{Lang::T('Password')}</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" class="form-control" name="password" autocomplete="new-password"
                            placeholder="{Lang::T('Enter password')}" required aria-label="{Lang::T('Password')}">
                        <button type="button" class="toggle-password" id="togglePassword"
                            aria-label="{Lang::T('Show password')}" aria-pressed="false">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>


                <div class="form-group">
                    <label for="cpassword">{Lang::T('Confirm Password')}</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="cpassword" class="form-control" name="cpassword" autocomplete="new-password"
                            placeholder="{Lang::T('Confirm password')}" required
                            aria-label="{Lang::T('Confirm Password')}">
                        <button type="button" class="toggle-password" id="togglePasswordConfirm"
                            aria-label="{Lang::T('Show password')}" aria-pressed="false">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
{if $_c['register_terms'] neq 'no'}
{* Agree to the Terms: the register button stays off until this is ticked (the server checks it too) *}
<div class="form-group terms-agree">
    <label class="checkbox-container terms-check">
        <input type="checkbox" name="agree_terms" value="yes" id="agree_terms">
        <span class="checkmark"></span>
        <span>{Lang::T('I agree to the')} <a href="{Text::url('terms')}" target="_blank" rel="noopener">{Lang::T('Terms and Conditions')}</a></span>
    </label>
</div>
<script>
(function () {
    var box = document.getElementById("agree_terms"), form = box && box.closest("form");
    if (!form) { return; }
    function sync() {
        var list = form.querySelectorAll("button[type=submit],input[type=submit]");
        for (var i = 0; i < list.length; i++) {
            var b = list[i];
            if (!box.checked) {
                b.disabled = true; b.classList.add("terms-blocked"); b.title = '{Lang::T('Agree to the Terms and Conditions first')|escape:'javascript'}';
            } else if (b.classList.contains("terms-blocked")) {
                b.classList.remove("terms-blocked"); b.removeAttribute("title");
                if (!b.classList.contains("captcha-blocked") && !b.classList.contains("loading")) { b.disabled = false; }
            }
        }
    }
    box.addEventListener("change", sync);
    form.addEventListener("submit", function (e) { if (!box.checked) { e.preventDefault(); e.stopImmediatePropagation(); } }, true);
    sync();
    setTimeout(sync, 400); // after the captcha script has had its first look
})();
</script>
{/if}
                {if !empty($captcha_widget)}
                    {$captcha_widget}
                {/if}

                <button type="submit" class="login-btn">
                    <span class="btn-text">{Lang::T('Register Now')}</span>
                    <div class="btn-loader">
                        <div class="spinner"></div>
                    </div>
                </button>

                <div class="divider">
                    <span>{Lang::T('Already have an account?')}</span>
                </div>

                <div class="connection-options">
                    <a href="{Text::url('login')}" class="connection-btn">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>{Lang::T('Login Here')}</span>
                    </a>
                </div>
            </div>
        </div>

    </form>
</div>

{include file="customer/footer-public.tpl"}