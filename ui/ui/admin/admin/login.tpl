<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <title>{Lang::T('Login')} - {$_c['CompanyName']}</title>
    <link rel="shortcut icon" href="{$app_url}/ui/ui/images/logo.png" type="image/x-icon" />

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- FontAwesome & Icons -->
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/fontawesome.css">

    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/styles.css?2026.10.27.1">
</head>

<body class="admin-login-body">


    <!-- Main Login Card -->
    <div class="login-wrapper">
        <div class="login-card">
            <div class="card-header-wrap">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                    <div style="width: 36px;"></div>
                    <div class="admin-badge">
                        <i class="fas fa-shield-alt"></i>
                        <span>{Lang::T('Administrator')}</span>
                    </div>
                    <button type="button" class="theme-btn" id="adminThemeToggle" title="Toggle Theme" aria-label="Toggle Dark and Light Mode" style="width: 36px; height: 36px;">
                        <i class="fas fa-moon" id="themeIcon"></i>
                    </button>
                </div>
                <h1 class="brand-title">
                    {$_c['CompanyName']}
                </h1>
                <p class="brand-subtitle">{Lang::T('Enter Admin Area')}</p>
            </div>

            {if isset($notify)}
                <div class="alert-message {if strpos($notify, 'alert-success') !== false}alert-success{else}alert-danger{/if}">
                    <i class="fas fa-info-circle"></i>
                    <div>{$notify}</div>
                </div>
            {/if}

            <form action="{Text::url('admin/post')}" method="post" id="adminLoginForm">
                <input type="hidden" name="csrf_token" value="{$csrf_token}">

                <div class="form-group">
                    <label class="input-label" for="username">{Lang::T('Username')}</label>
                    <div class="input-box">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="username" name="username" class="custom-input"
                            placeholder="{Lang::T('Enter your username')}" required autofocus autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <label class="input-label" for="password">{Lang::T('Password')}</label>
                    <div class="input-box">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="custom-input"
                            placeholder="{Lang::T('Enter your password')}" required autocomplete="current-password">
                        <button type="button" class="password-toggle-btn" id="togglePasswordBtn" aria-label="{Lang::T('Toggle Password Visibility')}">
                            <i class="fas fa-eye" id="passwordToggleIcon"></i>
                        </button>
                    </div>
                </div>

                {if !empty($captcha_widget)}
                    <div class="captcha-wrapper">
                        {$captcha_widget}
                    </div>
                {/if}

                <button type="submit" class="submit-btn" id="submitBtn">
                    <span class="btn-spinner" id="btnSpinner"></span>
                    <span id="btnText">{Lang::T('Login')}</span>
                    <i class="fas fa-arrow-right" id="btnArrow"></i>
                </button>

                <div class="back-link-wrap">
                    <a href="{Text::url('login')}" class="back-link">
                        <i class="fas fa-arrow-left"></i>
                        <span>{Lang::T('Go to Member Portal')}</span>
                    </a>
                </div>
            </form>
        </div>

        <div class="login-footer">
            &copy; {date('Y')} {$_c['CompanyName']}. {Lang::T('All rights reserved.')}
        </div>
    </div>

    <script>
        // Theme Toggle Implementation
        (function() {
            var themeToggleBtn = document.getElementById('adminThemeToggle');
            var themeIcon = document.getElementById('themeIcon');
            var currentTheme = localStorage.getItem('mode') || localStorage.getItem('phpnuxbill_admin_theme');

            if (!currentTheme || currentTheme === 'system') {
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    currentTheme = 'dark';
                } else {
                    currentTheme = 'light';
                }
            }

            function applyTheme(theme) {
                var isDark = (theme === 'dark');
                if (isDark) {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    document.body.setAttribute('data-theme', 'dark');
                    document.body.classList.add('dark-mode');
                    if (themeIcon) themeIcon.className = 'fas fa-sun';
                } else {
                    document.documentElement.removeAttribute('data-theme');
                    document.body.removeAttribute('data-theme');
                    document.body.classList.remove('dark-mode');
                    if (themeIcon) themeIcon.className = 'fas fa-moon';
                }
                localStorage.setItem('mode', theme);
                localStorage.setItem('phpnuxbill_admin_theme', theme);
            }

            applyTheme(currentTheme);

            if (themeToggleBtn) {
                themeToggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var isDark = document.body.classList.contains('dark-mode') || document.documentElement.getAttribute('data-theme') === 'dark';
                    var nextTheme = isDark ? 'light' : 'dark';
                    applyTheme(nextTheme);
                });
            }

            // Password Show/Hide Toggle
            var togglePasswordBtn = document.getElementById('togglePasswordBtn');
            var passwordInput = document.getElementById('password');
            var passwordToggleIcon = document.getElementById('passwordToggleIcon');

            if (togglePasswordBtn && passwordInput && passwordToggleIcon) {
                togglePasswordBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        passwordToggleIcon.className = 'fas fa-eye-slash';
                    } else {
                        passwordInput.type = 'password';
                        passwordToggleIcon.className = 'fas fa-eye';
                    }
                });
            }

            // Form Submit Feedback State
            var loginForm = document.getElementById('adminLoginForm');
            var submitBtn = document.getElementById('submitBtn');
            var btnSpinner = document.getElementById('btnSpinner');
            var btnText = document.getElementById('btnText');
            var btnArrow = document.getElementById('btnArrow');

            if (loginForm && submitBtn) {
                loginForm.addEventListener('submit', function() {
                    if (btnSpinner) btnSpinner.style.display = 'inline-block';
                    if (btnArrow) btnArrow.style.display = 'none';
                    if (btnText) btnText.textContent = '{Lang::T("Authenticating...")}';
                    setTimeout(function() {
                        submitBtn.disabled = true;
                    }, 20);
                });
            }
        })();
    </script>
</body>

</html>

