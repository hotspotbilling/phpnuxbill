<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{$_title} - {$_c['CompanyName']}</title>
    <link rel="shortcut icon" href="./{$favicon}" type="image/x-icon" />
    <link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css' />
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css' />
    <!-- SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    <!-- SweetAlert JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <style>
        .login-fg .form-container {
            color: #ccc;
            position: relative;
        }

        .login-fg .login {
            min-height: 100vh;
            position: relative;
            display: -webkit-box;
            display: -moz-box;
            display: -ms-flexbox;
            display: -webkit-flex;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px 15px;
        }

        .login-fg .login-section {
            max-width: 370px;
            margin: 0 auto;
            text-align: center;
            width: 100%;
        }

        .login-fg .form-fg {
            width: 100%;
            text-align: center;
        }

        .login-fg .form-container .form-group {
            margin-bottom: 25px;
        }

        .login-fg .form-container .form-fg {
            float: left;
            width: 100%;
            position: relative;
        }

        .login-fg .form-container .input-text {
            font-size: 14px;
            outline: none;
            color: #616161;
            border-radius: 3px;
            font-weight: 500;
            border: 1px solid transparent;
            background: #fff;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
        }

        .login-fg .form-container img {
            margin-bottom: 5px;
            height: 40px;
        }

        .login-fg .form-container .form-fg input {
            float: left;
            width: 100%;
            padding: 11px 45px 11px 20px;
            border-radius: 50px;
        }

        .login-fg .form-container .form-fg i {
            position: absolute;
            top: 13px;
            right: 20px;
            font-size: 19px;
            color: #616161;
        }

        .login-fg .form-container label {
            font-weight: 500;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .login-fg .form-container .forgot {
            margin: 0;
            line-height: 45px;
            color: #535353;
            font-size: 15px;
            float: right;
        }

        .login-fg .bg {
            background: rgba(0, 0, 0, 0.04) repeat;
            background-size: cover;
            top: 0;
            width: 100%;
            bottom: 0;
            opacity: 1;
            z-index: 999;
            min-height: 100vh;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px;
        }

        .login-fg .info h1 {
            font-size: 60px;
            color: #fff;
            font-weight: 700;
            margin-bottom: 15px;
            text-transform: uppercase;
            text-shadow: 2px 0px #000;
        }

        .login-fg .info {
            text-align: center;
        }

        .login-fg .info p {
            margin-bottom: 0;
            color: #fff;
            line-height: 28px;
            text-shadow: 1px 1px #000;
        }

        .login-fg .form-container .btn-md {
            cursor: pointer;
            padding: 10px 30px 9px;
            height: 45px;
            letter-spacing: 1px;
            font-size: 14px;
            font-weight: 400;
            font-family: "Open Sans", sans-serif;
            border-radius: 50px;
            color: #d6d6d6;
        }

        .login-fg .form-container p {
            margin: 0;
            color: #616161;
        }

        .login-fg .form-container p a {
            color: #616161;
        }

        .login-fg .form-container button:focus {
            outline: none;
            outline: 0 auto -webkit-focus-ring-color;
        }

        .login-fg .form-container .btn-fg.focus,
        .btn-fg:focus {
            box-shadow: none;
        }

        .login-fg .form-container .btn-fg {
            background: #0f96f9;
            border: none;
            color: #fff;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
        }

        .login-fg .form-container .btn-fg:hover {
            background: #108ae4;
        }

        .login-fg .logo a {
            font-weight: 700;
            color: #333;
            font-size: 39px;
            text-shadow: 1px 0px #000;
        }

        .login-fg .form-container .checkbox {
            margin-bottom: 25px;
            font-size: 14px;
        }

        .login-fg .form-container .form-check {
            float: left;
            margin-bottom: 0;
        }

        .login-fg .form-container .form-check a {
            color: #d6d6d6;
            float: right;
        }

        .login-fg .form-container .form-check-input {
            position: absolute;
            margin-left: 0;
        }

        .login-fg .form-container .form-check label::before {
            content: "";
            display: inline-block;
            position: absolute;
            width: 18px;
            height: 18px;
            top: 2px;
            margin-left: -25px;
            border: none;
            border-radius: 3px;
            background: #fff;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
        }

        .login-fg .form-container .form-check-label {
            padding-left: 25px;
            margin-bottom: 0;
            font-size: 14px;
            color: #616161;
        }

        .login-fg .form-container .checkbox-fg input[type="checkbox"]:checked+label::before {
            color: #fff;
            background: #0f96f9;
        }

        .login-fg .form-container input[type="checkbox"]:checked+label:before {
            font-weight: 300;
            color: #f3f3f3;
            font-size: 14px;
            content: "\2713";
            line-height: 17px;
        }

        .login-fg .form-container input[type="checkbox"],
        input[type="radio"] {
            margin-top: 4px;
        }

        .login-fg .form-container .checkbox a {
            font-size: 14px;
            color: #616161;
            float: right;
            margin-left: 3px;
        }

        .login-fg .login-section h3 {
            font-size: 20px;
            margin-bottom: 40px;
            font-family: "Open Sans", sans-serif;
            font-weight: 400;
            color: #505050;
        }

        .login-fg .login-section p {
            margin: 25px 0 0;
            font-size: 15px;
            color: #616161;
        }

        .login-fg .login-section p a {
            color: #616161;
        }

        .login-fg .login-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .login-fg .login-section .social li {
            display: inline-block;
            margin-bottom: 5px;
        }

        .login-fg .login-section .social li a {
            font-size: 12px;
            font-weight: 600;
            width: 120px;
            margin: 2px 0 3px;
            height: 35px;
            line-height: 35px;
            border-radius: 20px;
            display: inline-block;
            text-align: center;
            text-decoration: none;
            background: #fff;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
        }

        .login-fg .login-section .social li a i {
            height: 35px;
            width: 35px;
            line-height: 35px;
            float: left;
            color: #fff;
            border-radius: 20px;
        }

        .login-fg .login-section .social li a span {
            margin-right: 7px;
        }

        .login-fg .login-section .or-login {
            float: left;
            width: 100%;
            margin: 20px 0 25px;
            text-align: center;
            position: relative;
        }

        .login-fg .login-section .or-login::before {
            position: absolute;
            left: 0;
            top: 10px;
            width: 100%;
            height: 1px;
            background: #d8dcdc;
            content: "";
        }

        .login-fg .login-section .or-login>span {
            width: auto;
            float: none;
            display: inline-block;
            background: #fff;
            padding: 1px 20px;
            z-index: 1;
            position: relative;
            font-family: Open Sans;
            font-size: 13px;
            color: #616161;
            text-transform: capitalize;
        }

        .login-fg .facebook-i {
            background: #4867aa;
            color: #fff;
        }

        .login-fg .twitter-i {
            background: #3cf;
            color: #fff;
        }

        .login-fg .google-i {
            background: #db4437;
            color: #fff;
        }

        .login-fg .facebook {
            color: #4867aa;
        }

        .login-fg .twitter {
            color: #3cf;
        }

        .login-fg .google {
            color: #db4437;
        }

        @media (max-width: 1200px) {
            .login-fg .info h1 {
                font-size: 45px;
            }
        }

        @media (max-width: 992px) {
            .login-fg .bg {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .login-fg .login-section .social li a {
                width: 100px;
            }

            .login-fg .logo a {
                font-size: 26px;
            }
        }

        footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 14px;
            color: inherit;
            background-color: #f8f8f8;
            padding: 10px;
        }

        input {
            width: 100%;
            padding: 15px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }

        input:focus {
            border-color: #4facfe;
            outline: none;
        }

        .submit-btn {
            background-color: #4facfe;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            box-sizing: border-box;
        }

        .submit-btn:hover {
            background-color: #00f2fe;
        }

        @media (max-width: 768px) {

            input,
            .submit-btn {
                padding: 12px;
                font-size: 15px;
            }
        }

        @media (max-width: 480px) {

            input,
            .submit-btn {
                padding: 10px;
                font-size: 14px;
            }
        }

        @media (max-width: 320px) {

            input,
            .submit-btn {
                padding: 8px;
                font-size: 12px;
            }
        }
    </style>
</head>

<body>
    {if isset($notify)}
    <script>
        // Display SweetAlert toast notification
        document.body.style.overflow = 'hidden';
        Swal.fire({
            icon: '{if $notify_t == "s"}success{else}warning{/if}',
            title: '{if $notify_t == "s"}Success{else}Error{/if}',
            text: '{$notify}',
            backdrop: 'rgba(0, 0, 0, 0.5)',
        }).then(() => {
            document.body.style.overflow = '';
        });
    </script>
    {/if}
    <!-- partial:index.partial.html -->
    <div class="login-fg">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-8 col-lg-7 col-md-12 bg" style="background-image:url('./{$wallpaper}')">
                    <div class="info">
                        <h1>{$_c['login_page_head']}</h1>
                        <p>{$_c['login_page_description']}</p>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-5 col-md-12 login">
                    <div class="login-section">
                        <div class="logo clearfix">
                            <a href="./{$login_logo}" target="_blank"><img src="./{$login_logo}" height="60"
                                    alt="Logo"></a>
                        </div>
                        <br>
                        <h4>{Lang::T('Sign in into your account')}</h4>
                        <!-- <ul class="social">
                            <li><a href="#" class="facebook"><i
                                        class="fa fa-facebook facebook-i"></i><span>Facebook</span></a></li>
                            <li><a href="#" class="twitter"><i
                                        class="fa fa-twitter twitter-i"></i><span>Twitter</span></a></li>
                            <li><a href="#" class="google"><i class="fa fa-google google-i"></i><span>Google</span></a>
                            </li>
                        </ul>
                        <div class="or-login clearfix">
                            <span>Or</span>
                        </div> -->
                        <div class="form-container">
                            <form id="login-form" method="POST" action="{$_url}login/post">
                                <input type="hidden" name="csrf_token" value="{$csrf_token}">
                                <div>
                                    <input type="text" name="username"
                                        placeholder="{if $_c['registration_username'] == 'phone'}{Lang::T('Phone Number')}{elseif $_c['registration_username'] == 'email'}{Lang::T('Email')}{else}{Lang::T('Username')}{/if}"
                                        required>
                                </div>
                                <div>
                                    <input type="password" name="password" placeholder="{Lang::T('Password')}" required>
                                </div>
                                <div class="checkbox clearfix">
                                    <!-- <div class="form-check checkbox-fg">
                                        <input class="form-check-input" type="checkbox" value="" id="remember">
                                        <label class="form-check-label" for="remember">
                                            Remember me
                                        </label>
                                    </div> -->
                                    <a href="{$_url}forgot">{Lang::T('Forgot Password')}?</a>
                                </div>
                                <div class="form-group mt-2">
                                    <button id="login-btn" type="submit" class="submit-btn">
                                        <span id="login-text">{Lang::T('Login')}</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                        {if $_c['disable_registration'] != 'noreg'}
                        <p>{Lang::T('Don\'t have an account?')} <a href="{$_url}register"
                                class="linkButton">{Lang::T('Register')}</a></p>
                        {/if}
                        <footer>
                            © {$smarty.now|date_format:"%Y"} {$_c['CompanyName']}. All rights reserved. <br> <a
                                href="pages/Privacy_Policy.html">Privacy</a> | <a href="pages/Terms_and_Conditions.html">Terms
                                &amp;
                                Conditions</a>
                        </footer>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- partial -->
    <script>
        const loginForm = document.getElementById('login-form');
        const loginBtn = document.getElementById('login-btn');
        const loginText = document.getElementById('login-text');

        loginForm.addEventListener('submit', function (event) {
            loginBtn.classList.add('loading');
            loginText.textContent = 'Please Wait...';
        });
    </script>
    {if $_c['tawkto'] != ''}
    <!--Start of Tawk.to Script-->
    <script type="text/javascript">
        var isLoggedIn = false;
        var Tawk_API = {
            onLoad: function () {
                if (!isLoggedIn) {
                    isLoggedIn = true;
                    window.Tawk_API.login({
                        name: '{$_user['fullname']}',
                        email: '{$_user['email']}',
                        userId: '{$_user['id']}'
                    }, function (error) {
                        //do something if there's an error
                    });
                }
                Tawk_API.setAttributes({
                    'id': '{$_user['id']}',
                    'username': '{$_user['username']}',
                    'service_type': '{$_user['service_type']}',
                    'balance': '{$_user['balance']}',
                    'account_type': '{$_user['account_type']}',
                    'phone': '{$_user['phonenumber']}'
                    }, function (error) { });
            }
        };
        var Tawk_LoadStart = new Date();
        Tawk_API.visitor = {
            name: '{$_user['fullname']}',
            email: '{$_user['email']}',
            userId: '{$_user['id']}'
            };
        (function () {
            var s1 = document.createElement("script"),
                s0 = document.getElementsByTagName("script")[0];
            s1.async = true;
            s1.src = 'https://embed.tawk.to/{$_c['tawkto']}';
            s1.charset = 'UTF-8';
            s1.setAttribute('crossorigin', '*');
            s0.parentNode.insertBefore(s1, s0);
        })();
    </script>
    <!--End of Tawk.to Script-->
    {/if}
</body>

</html>