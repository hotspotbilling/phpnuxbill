<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{$_title} - {$_c['CompanyName']}</title>
    <link rel="shortcut icon" href="./{$favicon}" type="image/x-icon" />
    <link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css' />
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css' />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <style>
        .login-fg .form-container {
            color: #ccc;
            position: relative;
        }

        .login-fg .login {
            min-height: 100vh;
            position: relative;
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

        .login-fg .form-container .btn-fg {
            background: #0f96f9;
            border: none;
            color: #fff;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
        }

        .login-fg .form-container .btn-fg:hover {
            background: #108ae4;
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
            padding: 10px;
            margin: 1px 0;
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

        .submit-btn {
            transition: all 0.3s ease;
            -webkit-tap-highlight-color: transparent;
        }

        .submit-btn:active {
            transform: scale(0.98);
        }

        .login-section {
            max-height: 100vh;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            -ms-overflow-style: -ms-autohiding-scrollbar;
            scrollbar-width: thin;
            scrollbar-color: #888 #f1f1f1;
        }

        .login-section::-webkit-scrollbar {
            width: 8px;
        }

        .login-section::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .login-section::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .login-section::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .login-fg .info h1 {
            font-size: 60px;
            color: #fff;
            font-weight: 700;
            margin-bottom: 15px;
            text-transform: uppercase;
            text-shadow: 2px 0px #000;
        }

        .login-fg .info p {
            margin-bottom: 0;
            color: #fff;
            line-height: 28px;
            text-shadow: 1px 1px #000;
        }

        .login-fg .info {
            text-align: center;
            position: fixed;
            top: 50%;
            left: 33%;
            transform: translate(-50%, -50%);
            z-index: 1;
        }

        @media (max-width: 768px) {

            input,
            .submit-btn {
                padding: 12px;
                font-size: 15px;
            }
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

            input,
            .submit-btn {
                padding: 12px;
                font-size: 15px;
            }

            .login-fg .login-section {
                padding: 15px;
                max-width: 100%;
            }

            .login-fg .logo img {
                max-width: 200px;
                height: auto;
            }

            .login-fg .info h1 {
                font-size: 32px;
                margin-bottom: 10px;
            }

            .login-fg .info p {
                font-size: 14px;
                line-height: 1.5;
            }

            footer {
                position: fixed;
                margin-top: 20px;
                padding: 8px;
                font-size: 12px;
            }

            .login-fg .login-section h4 {
                font-size: 18px;
                margin-bottom: 20px;
            }

            .login-fg .login-section .social li a {
                width: 100%;
                margin: 5px 0;
            }

            .checkbox a {
                display: block;
                text-align: right;
                margin-top: 10px;
            }

            .login-fg .login-section .social li a {
                width: 100px;
            }

            .login-fg .logo a {
                font-size: 26px;
            }
        }

        @media (max-width: 480px) {
            .login-fg .login {
                padding: 15px;
            }

            input,
            .submit-btn {
                padding: 10px;
                font-size: 15px;
            }

            .login-fg .login-section h4 {
                font-size: 16px;
            }

            footer {
                font-size: 11px;
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
    <!-- SweetAlert Notification -->
    {if isset($notify)}
    <script>
        document.body.style.overflow = 'hidden';
        Swal.fire({
            icon: '{if $notify_t == "s"}success{else}warning{/if}',
            title: '{if $notify_t == "s"}Success{else}Error{/if}',
            html: '{$notify}',
            backdrop: 'rgba(0, 0, 0, 0.5)',
        }).then(() => {
            document.body.style.overflow = '';
        });
    </script>
    {/if}

    <div class="login-fg">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-8 col-lg-7 col-md-12 bg"
                    style="background-image:url('./{$wallpaper}'); background-attachment: fixed;">
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
                        <h4>{Lang::T('Create your account')}</h4>
                        <div class="form-container">
                            <form id="register-form" method="POST" action="{Text::url('register/post')}">
                                <input type="hidden" name="csrf_token" value="{$csrf_token}">

                                <!-- Basic Information (Initially Visible) -->
                                <div id="basicFields">
                                    <div class="form-group">
                                        <input type="text" name="username"
                                            placeholder="{if $_c['country_code_phone']!= '' || $_c['registration_username'] == 'phone'}{$_c['country_code_phone']} {Lang::T('Phone Number')}{elseif $_c['registration_username'] == 'email'}{Lang::T('Email')}{else}{Lang::T('Usernames')}{/if}">
                                    </div>
                                    {if $_c['photo_register'] == 'yes'}
                                    <div class="form-group">
                                        <input type="file" required id="photo" name="photo"
                                            accept="image/*">
                                    </div>
                                    {/if}
                                    <div class="form-group">
                                        <input type="text" name="fullname" placeholder="{Lang::T('Full Name')}"
                                        {if $_c['man_fields_fname'] neq 'no'}required{/if} >
                                    </div>
                                    <div class="form-group">
                                        <input type="email" name="email" placeholder="{Lang::T('Email Address')}"
                                        {if $_c['man_fields_email'] neq 'no'}required{/if}>
                                    </div>
                                    <div class="form-group">
                                        <button type="button" onclick="toggleFields()" class="submit-btn">
                                            {Lang::T('Next Step')}
                                        </button>
                                    </div>
                                </div>

                                <!-- Password Fields (Initially Hidden) -->
                                <div id="passwordFields" style="display: none;">
                                    <div class="form-group">
                                        <input type="text" name="address" placeholder="{Lang::T('Home Address')}"
                                        {if $_c['man_fields_address'] neq 'no'}required{/if}>
                                    </div>
                                    <div class="form-group">
                                        <input type="password" name="password" placeholder="{Lang::T('Password')}"
                                            required>
                                    </div>
                                    <div class="form-group">
                                        <input type="password" name="cpassword"
                                            placeholder="{Lang::T('Confirm Password')}" required>
                                    </div>
                                    <div class="form-group">
                                        <button id="register-btn" type="submit" class="submit-btn">
                                            <span id="register-text">{Lang::T('Register')}</span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <p>{Lang::T('Already have an account?')} <a href="{Text::url('login')}"
                                class="linkButton">{Lang::T('Login')}</a></p>
                        <footer>
                            © {$smarty.now|date_format:"%Y"} {$_c['CompanyName']}. All rights reserved. <br> <a
                                href="pages/Privacy_Policy.html">Privacy</a> | <a
                                href="pages/Terms_and_Conditions.html">Terms &amp; Conditions</a>
                        </footer>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const registerForm = document.getElementById('register-form');
        const registerBtn = document.getElementById('register-btn');
        const registerText = document.getElementById('register-text');
        registerForm.addEventListener('submit', function (event) {
            registerBtn.classList.add('loading');
            registerText.textContent = 'Please Wait...';
        });

        function toggleFields() {

            document.getElementById('basicFields').style.display = 'none';
            document.getElementById('passwordFields').style.display = 'block';

            const backButton = document.createElement('button');
            backButton.className = 'submit-btn';
            backButton.textContent = '← Back';
            backButton.style.marginBottom = '15px';
            backButton.onclick = () => {
                document.getElementById('basicFields').style.display = 'block';
                document.getElementById('passwordFields').style.display = 'none';
                backButton.remove();
            };
            document.getElementById('passwordFields').prepend(backButton);
        }
    </script>
</body>

</html>