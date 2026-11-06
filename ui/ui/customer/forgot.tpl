{include file="customer/header-public.tpl"}

<div class="login-container">
    <form action="{Text::url('forgot&step=')}{$step+1}" method="post" style="width: 100%; max-width: 420px;">
            {if $step == 1}
                <div class="login-card">
                    <div class="logo-section">
                        <div class="brand-name">{$_c['CompanyName']}</div>
                        <div class="subtitle">{Lang::T('Verification Code')}</div>
                        <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                            <i class="fas fa-circle-half-stroke" id="themeIcon"></i>
                        </button>
                    </div>

                    <div class="login-form">
                        <div class="form-group">
                            <label class="visually-hidden">{if $_c['country_code_phone']!= ''}{Lang::T('Phone Number')}{else}{Lang::T('Username')}{/if}</label>
                            <div class="input-wrapper">
                                {if $_c['country_code_phone']!= ''}
                                    <i class="fas fa-phone-alt input-icon"></i>
                                {else}
                                    <i class="fas fa-user input-icon"></i>
                                {/if}
                                <input type="text" readonly class="form-control" name="username" value="{$username|escape}"
                                    placeholder="{if $_c['country_code_phone']!= ''}{$_c['country_code_phone']} {Lang::T('Phone Number')}{else}{Lang::T('Username')}{/if}"
                                    aria-label="{if $_c['country_code_phone']!= ''}{Lang::T('Phone Number')}{else}{Lang::T('Username')}{/if}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="visually-hidden">{Lang::T('Verification Code')}</label>
                            <div class="input-wrapper">
                                <i class="fas fa-key input-icon"></i>
                                <input type="text" required class="form-control" id="otp_code" inputmode="numeric" autocomplete="one-time-code" maxlength="6"
                                    placeholder="{Lang::T('Verification Code')}" name="otp_code"
                                    aria-label="{Lang::T('Verification Code')}">
                            </div>
                        </div>

                        <button type="submit" class="login-btn">
                            <span class="btn-text">{Lang::T('Validate')}</span>
                        </button>

                        <div class="divider">
                            <span>{Lang::T('or')}</span>
                        </div>

                        <a href="{Text::url('forgot&step=-1')}" class="forgot-link" style="display: block; text-align: center;">{Lang::T('Cancel')}</a>
                    </div>
                </div>
            {elseif $step == 2}
                <div class="login-card">
                    <div class="logo-section">
                        <div class="brand-name">{$_c['CompanyName']}</div>
                        <div class="subtitle">{Lang::T('Success')}</div>
                        
                        <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                            <i class="fas fa-circle-half-stroke" id="themeIcon"></i>
                        </button>
                    </div>

                    <div class="login-form">
                        <div class="form-group">
                            <label>{if $_c['country_code_phone']!= ''}{Lang::T('Phone Number')}{else}{Lang::T('Username')}{/if}</label>
                            <div class="input-wrapper">
                                {if $_c['country_code_phone']!= ''}
                                    <i class="fas fa-phone-alt input-icon"></i>
                                {else}
                                    <i class="fas fa-user input-icon"></i>
                                {/if}
                                <input type="text" readonly class="form-control" name="username" value="{$username|escape}"
                                    placeholder="{if $_c['country_code_phone']!= ''}{$_c['country_code_phone']} {Lang::T('Phone Number')}{else}{Lang::T('Username')}{/if}"
                                    aria-label="{if $_c['country_code_phone']!= ''}{Lang::T('Phone Number')}{else}{Lang::T('Username')}{/if}">
                            </div>
                        </div>

                        <input type="hidden" name="csrf_token" value="{$csrf_token}">
                        <div class="form-group">
                            <label>{Lang::T('New Password')}</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" required class="form-control" name="npass" minlength="6" maxlength="64"
                                    autocomplete="new-password" placeholder="{Lang::T('New Password')}"
                                    aria-label="{Lang::T('New Password')}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>{Lang::T('Confirm New Password')}</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" required class="form-control" name="cnpass" minlength="6" maxlength="64"
                                    autocomplete="new-password" placeholder="{Lang::T('Confirm New Password')}"
                                    aria-label="{Lang::T('Confirm New Password')}">
                            </div>
                        </div>

                        <button type="submit" class="login-btn">
                            <span class="btn-text">{Lang::T('Change Password')}</span>
                        </button>

                        <div class="divider">
                            <span>{Lang::T('or')}</span>
                        </div>

                        <a href="{Text::url('forgot&step=-1')}" class="forgot-link" style="display: block; text-align: center;">{Lang::T('Cancel')}</a>
                    </div>
                </div>
            {elseif $step == 3}
                <div class="login-card">
                    <div class="logo-section">
                        <div class="brand-name">{$_c['CompanyName']}</div>
                        <div class="subtitle">{Lang::T('Success')}</div>

                        <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                            <i class="fas fa-circle-half-stroke" id="themeIcon"></i>
                        </button>
                    </div>

                    <div class="login-form">
                        <p style="text-align: center;">{Lang::T('Password changed successfully, Please login again')}</p>
                        <a href="{Text::url('login')}" class="login-btn" style="display: block; text-align: center; line-height: 1rem; padding: 1rem;">
                            {Lang::T('Back to Login')}
                        </a>
                    </div>
                </div>
            {elseif $step == 6}
                <div class="login-card">
                    <div class="logo-section">
                        <div class="brand-name">{$_c['CompanyName']}</div>
                        <div class="subtitle">{Lang::T('Forgot Username')}</div>
                        
                        <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                            <i class="fas fa-circle-half-stroke" id="themeIcon"></i>
                        </button>
                    </div>

                    <div class="login-form">
                        <div class="form-group">
                            <label>{Lang::T('Please input your Email or Phone number')}</label>
                            <div class="input-wrapper">
                                <i class="fas fa-search input-icon"></i>
                                <input type="text" name="find" class="form-control" required value=""
                                    placeholder="{Lang::T('Email or Phone number')}"
                                    aria-label="{Lang::T('Email or Phone number')}">
                            </div>
                        </div>

                        {if !empty($captcha_widget)}
                            {$captcha_widget}
                        {/if}

                        <button type="submit" class="login-btn">
                            <span class="btn-text">{Lang::T('Validate')}</span>
                        </button>

                        <div class="divider">
                            <span>{Lang::T('or')}</span>
                        </div>

                        <a href="{Text::url('forgot')}" class="forgot-link" style="display: block; text-align: center;">{Lang::T('Back')}</a>
                    </div>
                </div>
            {else}
                <div class="login-card">
                    <div class="logo-section">
                        <div class="brand-name">{$_c['CompanyName']}</div>
                        <div class="subtitle">{Lang::T('Forgot Password')}</div>
                        
                        <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                            <i class="fas fa-circle-half-stroke" id="themeIcon"></i>
                        </button>
                    </div>

                    <div class="login-form">
                        <div class="form-group">
                            <label>{if $_c['country_code_phone']!= ''}{Lang::T('Phone Number')}{else}{Lang::T('Username')}{/if}</label>
                            <div class="input-wrapper">
                                {if $_c['country_code_phone']!= ''}
                                    <i class="fas fa-phone-alt input-icon"></i>
                                {else}
                                    <i class="fas fa-user input-icon"></i>
                                {/if}
                                <input type="text" class="form-control" name="username" required
                                    placeholder="{if $_c['country_code_phone']!= ''}{$_c['country_code_phone']} {Lang::T('Phone Number')}{else}{Lang::T('Username')}{/if}"
                                    aria-label="{if $_c['country_code_phone']!= ''}{Lang::T('Phone Number')}{else}{Lang::T('Username')}{/if}">
                            </div>
                        </div>

                        {if !empty($captcha_widget)}
                            {$captcha_widget}
                        {/if}

                        <button type="submit" class="login-btn">
                            <span class="btn-text">{Lang::T('Validate')}</span>
                        </button>

                        <div class="divider">
                            <span>{Lang::T('or')}</span>
                        </div>

                        <div class="connection-options" style="flex-direction: column; gap: 0.5rem;">
                            <a href="{Text::url('forgot&step=6')}" class="forgot-link" style="display: block; text-align: center;">
                                {Lang::T('Forgot Username')}
                            </a>
                            <a href="{Text::url('login')}" class="forgot-link" style="display: block; text-align: center;">
                                {Lang::T('Back to Login')}
                            </a>
                        </div>
                    </div>
                </div>
            {/if}
    </form>
</div>

{include file="customer/footer-public.tpl"}