{include file="customer/header-public.tpl"}

<div class="login-layout" style="min-height: 100vh; height: auto; align-items: flex-start; overflow-y: auto;">
    <!-- Left Side: Info -->
    <div class="login-intro">
        <div class="login-intro-content">
            <h1>{Lang::T('Join Us')}</h1>
            <p>{Lang::T('Create an account to manage your services, pay bills, and more.')}</p>

            <!-- Registration Info from Page -->
            <div style="margin-top: 2rem; padding: 1.5rem; background: rgba(255,255,255,0.1); border-radius: 1rem; backdrop-filter: blur(10px);">
                 {include file="$_path/../pages/Registration_Info.html"}
            </div>

            <div style="margin-top: 2rem;">
                <a href="{Text::url('login')}" class="btn-fyme btn-fyme-outline" style="color: white; border-color: white; width: auto; display: inline-block;">
                    <i data-feather="arrow-left" style="width: 16px; margin-right: 6px;"></i> {Lang::T('Back to Login')}
                </a>
            </div>
        </div>
    </div>

    <!-- Right Side: Form -->
    <div class="login-form-wrapper" style="flex: 1; max-width: 800px; padding: 2rem;">
        <div class="login-card-header">
            <h2 class="login-title">{Lang::T('Register')}</h2>
            <p class="login-subtitle">{Lang::T('Fill in the details below to create your account')}</p>
        </div>

        <form enctype="multipart/form-data" action="{Text::url('register/post')}" method="post">
            <div class="row">
                <div class="col-md-6">
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
                                placeholder="{if $_c['country_code_phone']!= '' || $_c['registration_username'] == 'phone'}{$_c['country_code_phone']}...{elseif $_c['registration_username'] == 'email'}email@example.com{else}username{/if}">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="fyme-input-group">
                        <label class="fyme-label">{Lang::T('Full Name')}</label>
                        <div class="fyme-input-wrapper">
                             <div class="fyme-input-icon"><i data-feather="smile"></i></div>
                            <input type="text" {if $_c['man_fields_fname'] neq 'no'}required{/if} class="fyme-input"
                                id="fullname" value="{$fullname}" name="fullname" placeholder="John Doe">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="fyme-input-group">
                        <label class="fyme-label">{Lang::T('Email')}</label>
                         <div class="fyme-input-wrapper">
                             <div class="fyme-input-icon"><i data-feather="mail"></i></div>
                            <input type="text" {if $_c['man_fields_email'] neq 'no'}required{/if} class="fyme-input"
                                id="email" placeholder="name@example.com" value="{$email}" name="email">
                        </div>
                    </div>
                </div>
                 <div class="col-md-6">
                    <div class="fyme-input-group">
                        <label class="fyme-label">{Lang::T('Home Address')}</label>
                         <div class="fyme-input-wrapper">
                             <div class="fyme-input-icon"><i data-feather="map-pin"></i></div>
                            <input type="text" {if $_c['man_fields_address'] neq 'no'}required{/if} name="address"
                                id="address" value="{$address}" class="fyme-input" placeholder="123 Main St">
                        </div>
                    </div>
                </div>
            </div>

            {if $_c['photo_register'] == 'yes'}
                <div class="fyme-input-group">
                    <label class="fyme-label">{Lang::T('Photo')}</label>
                     <div class="fyme-input-wrapper">
                        <input type="file" required class="fyme-input" id="photo" name="photo" accept="image/*" style="padding-left: 1rem !important;">
                    </div>
                </div>
            {/if}

             {$customFields}

            <hr style="border-color: var(--border-color); margin: 2rem 0;">

            <div class="row">
                <div class="col-md-6">
                     <div class="fyme-input-group">
                        <label class="fyme-label">{Lang::T('Password')}</label>
                        <div class="fyme-input-wrapper">
                            <div class="fyme-input-icon"><i data-feather="lock"></i></div>
                            <input type="password" required class="fyme-input" id="password" name="password" placeholder="••••••••">
                        </div>
                    </div>
                </div>
                 <div class="col-md-6">
                     <div class="fyme-input-group">
                        <label class="fyme-label">{Lang::T('Confirm Password')}</label>
                         <div class="fyme-input-wrapper">
                            <div class="fyme-input-icon"><i data-feather="lock"></i></div>
                            <input type="password" required class="fyme-input" id="cpassword" name="cpassword" placeholder="••••••••">
                        </div>
                    </div>
                </div>
            </div>

            <div class="fyme-input-group" style="margin-top: 1rem;">
                <button class="btn-fyme btn-fyme-primary" type="submit">{Lang::T('Register')}</button>
            </div>

            <div style="text-align: center; margin-top: 2rem; font-size: 0.8rem; color: var(--text-light);">
                <a href="javascript:showPrivacy()" style="color: inherit;">Privacy</a> &bull;
                <a href="javascript:showTaC()" style="color: inherit;">Terms &amp; Conditions</a>
            </div>
        </form>
    </div>
</div>
{include file="customer/footer-public.tpl"}