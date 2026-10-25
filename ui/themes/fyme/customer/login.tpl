{include file="customer/header-public.tpl"}

<div class="login-layout">
    <!-- Left Side: Intro/Branding -->
    <div class="login-intro">
        <div class="login-intro-content">
            <h1>{$_c['CompanyName']}</h1>
            <p>{Lang::T('Welcome to our customer portal. Manage your account, pay bills, and check usage with ease.')}</p>

            <!-- Optional Announcement Area if needed -->
            {$Announcement = "{$PAGES_PATH}/Announcement.html"}
            {if file_exists($Announcement)}
                <div style="margin-top: 2rem; padding: 1.5rem; background: rgba(255,255,255,0.1); border-radius: 1rem; backdrop-filter: blur(10px);">
                    {include file=$Announcement}
                </div>
            {/if}
        </div>
    </div>

    <!-- Right Side: Login Form -->
    <div class="login-form-wrapper">
        <div class="login-card-header">
            <div class="login-brand">{$_c['CompanyName']}</div>
            <h2 class="login-title">{Lang::T('Welcome Back')}</h2>
            <p class="login-subtitle">{Lang::T('Please enter your details to sign in')}</p>
        </div>

        <form action="{Text::url('login/post')}" method="post">
            <input type="hidden" name="csrf_token" value="{$csrf_token}">

            <!-- Username Field -->
            <div class="fyme-input-group">
                <label class="fyme-label">
                    {if $_c['registration_username'] == 'phone'}
                        {Lang::T('Phone Number')}
                    {elseif $_c['registration_username'] == 'email'}
                        {Lang::T('Email')}
                    {else}
                        {Lang::T('Username')}
                    {/if}
                </label>
                <div class="fyme-input-wrapper">
                    <div class="fyme-input-icon">
                        <i data-feather="user"></i>
                    </div>
                    <input type="text" class="fyme-input" name="username" required
                        placeholder="{if $_c['country_code_phone']!= '' || $_c['registration_username'] == 'phone'}{$_c['country_code_phone']} 8123...{elseif $_c['registration_username'] == 'email'}name@example.com{else}your_username{/if}">
                </div>
            </div>

            <!-- Password Field -->
            <div class="fyme-input-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <label class="fyme-label" style="margin-bottom: 0;">{Lang::T('Password')}</label>
                    <a href="{Text::url('forgot')}" style="font-size: 0.85rem; color: var(--primary);">{Lang::T('Forgot Password')}?</a>
                </div>
                <div class="fyme-input-wrapper">
                    <div class="fyme-input-icon">
                        <i data-feather="lock"></i>
                    </div>
                    <input type="password" class="fyme-input" name="password" required placeholder="••••••••">
                </div>
            </div>

            <div class="clearfix hidden">
                <div class="{$app_url}/ui-checkbox ui-checkbox-primary right">
                    <label>
                        <input type="checkbox">
                        <span>Remember me</span>
                    </label>
                </div>
            </div>

            <div class="fyme-input-group" style="margin-top: 2rem;">
                <button type="submit" class="btn-fyme btn-fyme-primary">
                    {Lang::T('Login')} <i data-feather="arrow-right" style="width: 18px; margin-left: 8px;"></i>
                </button>
            </div>

            {if $_c['disable_registration'] != 'noreg'}
                <div style="text-align: center; margin-top: 1.5rem;">
                    <span style="color: var(--text-secondary);">{Lang::T('Don\'t have an account?')}</span>
                    <a href="{Text::url('register')}" style="font-weight: 600; margin-left: 4px;">{Lang::T('Register')}</a>
                </div>
            {/if}

            <div style="text-align: center; margin-top: 2rem; font-size: 0.8rem; color: var(--text-light);">
                <a href="javascript:showPrivacy()" style="color: inherit;">Privacy</a> &bull;
                <a href="javascript:showTaC()" style="color: inherit;">Terms</a>
            </div>
        </form>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="HTMLModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content" style="border: none; border-radius: 1rem;">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" id="HTMLModal_konten"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">&times;</button>
            </div>
        </div>
    </div>
</div>

{include file="customer/footer-public.tpl"}