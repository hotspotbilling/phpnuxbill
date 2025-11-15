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

    /* OTP Register specific layout */
    .otp-form-wrapper {
         position: relative;
        display: flex;
        align-items: center;
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
        color: #667eea;
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
    }

    .form-control:focus {
        background: var(--input-bg-focus);
        border-color: #667eea;
        outline: none;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-text {
        display: block;
        margin-top: 6px;
        font-size: 0.8em;
        color: var(--text-tertiary);
    }

    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .btn {
        padding: 0.6rem 1.2rem;
        border-radius: 10px;
        font-size: 0.9em;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        flex: 1;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    }

    .btn-secondary {
        background: var(--input-bg);
        color: var(--text-primary);
        border: 2px solid var(--input-border);
    }

    .btn-secondary:hover {
        background: var(--input-bg-focus);
        border-color: #667eea;
    }

    .separator {
        color: var(--text-tertiary);
        margin: 0 8px;
    }

    @media (max-width: 768px) {
        .otp-form-wrapper {
            flex-direction: column;
        }
        
        .form-actions {
            flex-direction: column;
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
    <form action="{Text::url('register')}" method="post" class="otp-form-wrapper">
        <div class="login-card">
            <div class="logo-section">
                <div class="brand-name">{$_c['CompanyName']}</div>
                <div class="subtitle">{Lang::T('Register as Member')}</div>
                <br>
                <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                    <i class="fas fa-circle-half-stroke" id="themeIcon"></i>
                </button>
            </div>

            <div class="card-header-section">
                <h3 class="card-title">
                    <i class="fas fa-phone-alt"></i>
                    {Lang::T('Phone Verification')}
                </h3>
            </div>
            <div class="card-body-section">
                <div class="form-group">
                    <label for="phone_number">{Lang::T('Phone Number')}</label>
                    <div class="input-wrapper">
                        <i class="fas fa-phone-alt input-icon"></i>
                        <input type="tel" id="phone_number" class="form-control" name="phone_number"
                            placeholder="{if $_c['country_code_phone']!= ''}{$_c['country_code_phone']} {/if}{Lang::T('Phone Number')}"
                            inputmode="numeric" 
                            pattern="[0-9]+"
                            required
                            aria-label="{Lang::T('Phone Number')}">
                    </div>
                    <small class="form-text">{Lang::T('We will send an OTP code to verify your phone number')}</small>
                </div>

                <div class="form-actions">
                    <a href="{Text::url('login')}" class="btn btn-secondary">{Lang::T('Back to Login')}</a>
                    <button type="submit" class="btn btn-primary">{Lang::T('Request OTP')}</button>
                </div>
            </div>
        </div>
    </form>
</div>

{include file="customer/footer-public.tpl"}