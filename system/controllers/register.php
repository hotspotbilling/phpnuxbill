<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

if ($_c['disable_registration'] == 'noreg') {
    _alert(Lang::T('Registration Disabled'), 'danger', "login");
}

$referral_code = null;

if (isset($_SESSION['nux-ref']) && !empty($_SESSION['nux-ref'])) {
   $referral_code = $_SESSION['nux-ref'];
} elseif (isset($_GET['ref']) && !empty($_GET['ref'])) {
    // Import the validate function from index.php if not already available
    if (!function_exists('validateInput')) {
        /**
         * Validate and sanitize input
         * @param string $input The input to validate and sanitize
         * @param string $type The type of input
         * @return string|false Sanitized input or false if invalid
         */
        function validateInput($input, $type) {
            if (empty($input)) return false;
            
            $input = trim($input);
            
            if ($type === 'referral') {
                // Referral codes are alphanumeric between 4-25 characters
                if (preg_match('/^[a-zA-Z0-9]{4,25}$/', $input)) {
                    return $input;
                }
                return false;
            }
            return false;
        }
    }
    
    $sanitized = validateInput($_GET['ref'], 'referral');
    if ($sanitized !== false) {
        $_SESSION['nux-ref'] = $sanitized;
        $referral_code = $sanitized;
    }
}
if (isset($routes['1'])) {
    $do = $routes['1'];
} else {
    $do = 'register-display';
}

$ip = AuthThrottle::clientIp();

switch ($do) {
    case 'post':
        if (!Csrf::check(_post('csrf_token'))) {
            r2(getUrl('register'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
        }
        $csrf_token = Csrf::generateAndStoreToken(); // for the form if it has to be shown again
        $ui->assign('csrf_token', $csrf_token);
        // Verify Captcha if enabled
        // An OTP submission for a phone that already passed CAPTCHA when requesting the OTP needs no second check
        $otpCaptchaDone = ($config['sms_otp_registration'] == 'yes')
            && !empty(_post('otp_code'))
            && !empty($_SESSION['register_captcha_phone'])
            && hash_equals((string) $_SESSION['register_captcha_phone'], (string) alphanumeric(_post('phone_number'), "+_.@-"));
        if (!$otpCaptchaDone) {
            $captchaResult = Captcha::verify('customer');
            if (!$captchaResult['success']) {
                r2(getUrl('register'), 'e', $captchaResult['message']);
            }
        }

        $otp_code = _post('otp_code');
        $username = alphanumeric(_post('username'), "+_.@-");
        $email = _post('email');
        $fullname = _post('fullname');
        $password = _post('password');
        $cpassword = _post('cpassword');
        $address = _post('address');
        $referral_code = _post('referral_code') ?: ($referral_code ?? null);

        // Separate phone number input if OTP is required
        $phone_number = ($config['sms_otp_registration'] == 'yes') ? alphanumeric(_post('phone_number'), "+_.@-") : $username;

        $msg = '';
        if (Validator::Length($username, 35, 2) == false) {
            $msg .= "Username should be between 3 to 35 characters<br>";
        }
        if ($config['man_fields_fname'] == 'yes') {
            if (Validator::Length($fullname, 36, 2) == false) {
                $msg .= "Full Name should be between 3 to 35 characters<br>";
            }
        }
        $policy = Password::policyError($password, $username);
        if ($policy !== '') {
            $msg .= $policy . "<br>";
        }
        // new accounts are limited per address, so the form cannot be used to flood the customer list
        if ($wait = AuthThrottle::blocked('register-ip', $ip, 10, 3600)) {
            $msg .= Lang::T('Too many requests. Please try again later.') . ' (' . AuthThrottle::waitText($wait) . ")<br>";
        }
        if ($config['man_fields_email'] == 'yes') {
            if (!Validator::Email($email)) {
                $msg .= 'Email is not Valid<br>';
            }
        }
        if ($password != $cpassword) {
            $msg .= Lang::T('Passwords does not match') . '<br>';
        }

        // OTP verification if OTP is enabled
        if ($_c['sms_otp_registration'] == 'yes') {
            run_hook('validate_otp'); #HOOK
            $waitIp = AuthThrottle::blocked('register-verify-ip', $ip, 20, 900);
            $waitPhone = AuthThrottle::blocked('register-verify-phone', $phone_number, 20, 3600);
            if ($waitIp > 0 || $waitPhone > 0) {
                r2(getUrl('register'), 'e', Lang::T('Too many requests. Please try again later.') . ' (' . AuthThrottle::waitText(max($waitIp, $waitPhone)) . ')');
            }
            $otpState = Otp::check('register', $phone_number, $otp_code);
            if ($otpState === 'ok') {
                // the code is used up; carry on
            } elseif ($otpState === 'bad') {
                AuthThrottle::hit('register-verify-ip', $ip, 900);
                AuthThrottle::hit('register-verify-phone', $phone_number, 3600);
                $ui->assign('username', $username);
                $ui->assign('fullname', $fullname);
                $ui->assign('address', $address);
                $ui->assign('email', $email);
                $ui->assign('phone_number', $phone_number);
                $ui->assign('notify', 'Wrong Verification code');
                $ui->assign('notify_t', 'd');
                $ui->assign('_title', Lang::T('Register'));
                $ui->display('customer/register-otp.tpl');
                exit();
            } else { // expired, used up by too many wrong tries, or never sent
                AuthThrottle::hit('register-verify-ip', $ip, 900);
                r2(getUrl('register'), 'e', $otpState === 'none' ? Lang::T('No Verification code') : Lang::T('Verification code expired, please ask for a new one'));
            }
        }

        // Check if username already exists
        $d = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
        if ($d) {
            $msg .= Lang::T('Account already exists') . '<br>';
        }

        if ($msg == '') {
            $d = ORM::for_table('tbl_customers')->create();
            $d->username = alphanumeric($username, "+_.@-");
            $d->password = $password;
            $d->fullname = $fullname;
            $d->address = $address;
            $d->email = $email;
            $d->phonenumber = $phone_number;
            if ($d->save()) {
                $user = $d->id();
                if ($config['photo_register'] == 'yes' && !empty($_FILES['photo']['name']) && is_uploaded_file($_FILES['photo']['tmp_name'])) {
                    if (function_exists('imagecreatetruecolor')) {
                        $hash = md5_file($_FILES['photo']['tmp_name']);
                        $subfolder = substr($hash, 0, 2);
                        $folder = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'photos' . DIRECTORY_SEPARATOR;
                        if (!file_exists($folder)) {
                            mkdir($folder);
                        }
                        $folder = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'photos' . DIRECTORY_SEPARATOR . $subfolder . DIRECTORY_SEPARATOR;
                        if (!file_exists($folder)) {
                            mkdir($folder);
                        }
                        $imgPath = $folder . $hash . '.jpg';
                        File::resizeCropImage($_FILES['photo']['tmp_name'], $imgPath, 1600, 1600, 100);
                        $d->photo = '/photos/' . $subfolder . '/' . $hash . '.jpg';
                        $d->save();
                    }
                }
                if (!empty($_FILES['photo']['tmp_name']) && is_uploaded_file($_FILES['photo']['tmp_name']))
                    unlink($_FILES['photo']['tmp_name']);
                AuthThrottle::hit('register-ip', $ip, 3600);
                User::setFormCustomField($user);

                // Process referral code if provided
                if (!empty($referral_code) && Referral::getSetting('referral_enabled', 'yes') === 'yes') {
                    // Sanitize and validate referral code
                    $referral_code = trim($referral_code);
                    
                    // Get the expected code length from settings
                    $code_length = intval(Referral::getSetting('referral_code_length', 8));
                    
                    // Validate the referral code format (alphanumeric and within expected length range)
                    if (preg_match('/^[A-Z0-9]{' . max(4, min(20, $code_length)) . '}$/', $referral_code)) {
                        // Code passes validation, proceed with lookup
                        $referrer = Referral::getReferrerByCode($referral_code);
                        if ($referrer) {
                            Referral::recordReferral($referrer->id, $user, $referral_code);
                        } else {
                            // Invalid referral code - log the attempt
                            _log('Invalid referral code used in registration: ' . $referral_code, 'Warning');
                        }
                    } else {
                        // Log invalid format
                        _log('Invalid referral code format used in registration: ' . $referral_code, 'Warning');
                    }
                }

                run_hook('register_user'); #HOOK
                $msg .= Lang::T('Registration successful') . '<br>';
                if ($config['reg_nofify_admin'] == 'yes') {
                    sendTelegram($config['CompanyName'] . ' - ' . Lang::T('New User Registration') . "\n\nFull Name: " . $fullname . "\nUsername: " . $username . "\nEmail: " . $email . "\nPhone Number: " . $phone_number . "\nAddress: " . $address);
                }
                unset($_SESSION['register_captcha_phone']);
                r2(getUrl('login'), 's', Lang::T('Register Success! You can login now'));
            } else {
                $ui->assign('username', $username);
                $ui->assign('fullname', $fullname);
                $ui->assign('address', $address);
                $ui->assign('email', $email);
                $ui->assign('phone_number', $phone_number);
                $ui->assign('notify', 'Failed to register');
                $ui->assign('notify_t', 'd');
                $ui->assign('_title', Lang::T('Register'));
                run_hook('view_otp_register'); #HOOK
                $ui->display('customer/register-rotp.tpl');
            }
        } else {
            $ui->assign('username', $username);
            $ui->assign('fullname', $fullname);
            $ui->assign('address', $address);
            $ui->assign('email', $email);
            $ui->assign('phone_number', $phone_number);
            $ui->assign('referral_code', isset($referral_code) ? $referral_code : null);
            $ui->assign('notify', $msg);
            $ui->assign('notify_t', 'd');
            $ui->assign('_title', Lang::T('Register'));
            // Check if OTP is enabled
            if (!empty($config['sms_url']) && $_c['sms_otp_registration'] == 'yes') {
                // Display register-otp.tpl if OTP is enabled
                $ui->display('customer/register-otp.tpl');
            } else {
                $UPLOAD_URL_PATH = str_replace($root_path, '', $UPLOAD_PATH);
                if (!empty($config['login_page_logo']) && file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_logo'])) {
                    $login_logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_logo'];
                } elseif (file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'login-logo.png')) {
                    $login_logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'login-logo.png';
                } else {
                    $login_logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'login-logo.default.png';
                }

                if (!empty($config['login_page_wallpaper']) && file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_wallpaper'])) {
                    $wallpaper = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_wallpaper'];
                } elseif (file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'wallpaper.png')) {
                    $wallpaper = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'wallpaper.png';
                } else {
                    $wallpaper = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'wallpaper.default.png';
                }

                if (!empty($config['login_page_favicon']) && file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_favicon'])) {
                    $favicon = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_favicon'];
                } elseif (file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'favicon.png')) {
                    $favicon = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'favicon.png';
                } else {
                    $favicon = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'favicon.default.png';
                }

                $ui->assign('login_logo', $login_logo);
                $ui->assign('wallpaper', $wallpaper);
                $ui->assign('favicon', $favicon);
                $ui->assign('csrf_token', $csrf_token);
                $ui->assign('captcha_widget', Captcha::renderWidget('customer'));
                $ui->assign('_title', Lang::T('Login'));
                $ui->assign('customFields', User::getFormCustomField($ui, true));
                switch ($config['login_page_type']) {
                    case 'custom':
                        $ui->display('customer/reg-login-custom-' . $config['login_Page_template'] . '.tpl');
                        break;
                    default:
                        $ui->display('customer/register.tpl');
                        break;
                }
            }
        }
        break;

    default:
        if ($_c['sms_otp_registration'] == 'yes') {
            $phone_number = _post('phone_number');
            $csrf_token = null;
            if (!empty($phone_number)) {
                if (!Csrf::check(_post('csrf_token'))) {
                    r2(getUrl('register'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
                }
                $captchaResult = Captcha::verify('customer');
                if (!$captchaResult['success']) {
                    r2(getUrl('register'), 'e', $captchaResult['message']);
                }
                // every request counts, so numbers cannot be walked through to run up the SMS bill
                if ($wait = AuthThrottle::blocked('register-otp-ip', $ip, 10, 3600)) {
                    r2(getUrl('register'), 'e', Lang::T('Too many requests. Please try again later.') . ' (' . AuthThrottle::waitText($wait) . ')');
                }
                AuthThrottle::hit('register-otp-ip', $ip, 3600);
                $_SESSION['register_captcha_phone'] = alphanumeric($phone_number, "+_.@-");
                $d = ORM::for_table('tbl_customers')->where('username', $phone_number)->find_one();
                if ($d) {
                    r2(getUrl('register'), 's', Lang::T('Account already exists'));
                }
                $phone_number = alphanumeric($phone_number, "+_.@-");
                $csrf_token = Csrf::generateAndStoreToken();
                $ui->assign('csrf_token', $csrf_token);
                $otpAge = Otp::age('register', $phone_number);
                if ($otpAge >= 0 && $otpAge < Otp::TTL) {
                    $ui->assign('phone_number', $phone_number);
                    $ui->assign('notify', 'Please wait ' . (Otp::TTL - $otpAge) . ' seconds before sending another SMS');
                    $ui->assign('notify_t', 'd');
                    $ui->assign('_title', Lang::T('Register'));
                    $ui->display('customer/register-otp.tpl');
                } else {
                    $otp = Otp::issue('register', $phone_number);
                    if ($config['phone_otp_type'] == 'whatsapp') {
                        Message::sendWhatsapp($phone_number, $config['CompanyName'] . "\n\n" . Lang::T("Registration code") . "\n$otp");
                    } else if ($config['phone_otp_type'] == 'both') {
                        Message::sendWhatsapp($phone_number, $config['CompanyName'] . "\n\n" . Lang::T("Registration code") . "\n$otp");
                        Message::sendSMS($phone_number, $config['CompanyName'] . "\n\n" . Lang::T("Registration code") . "\n$otp");
                    } else {
                        Message::sendSMS($phone_number, $config['CompanyName'] . "\n\n" . Lang::T("Registration code") . "\n$otp");
                    }
                    $ui->assign('phone_number', $phone_number);
                    $ui->assign('notify', 'Registration code has been sent to your phone');
                    $ui->assign('notify_t', 's');
                    $ui->assign('_title', Lang::T('Register'));
                    $ui->assign('customFields', User::getFormCustomField($ui, true));
                    $ui->assign('referral_code', isset($referral_code) ? $referral_code : null);
                    $ui->display('customer/register-otp.tpl');
                }
            } else {
                $ui->assign('csrf_token', Csrf::generateAndStoreToken());
                $ui->assign('captcha_widget', Captcha::renderWidget('customer'));
                $ui->assign('_title', Lang::T('Register'));
                $ui->assign('referral_code', isset($referral_code) ? $referral_code : null);
                run_hook('view_otp_register'); #HOOK
                $ui->display('customer/register-rotp.tpl');
            }
        } else {
            $csrf_token = Csrf::generateAndStoreToken();
            $UPLOAD_URL_PATH = str_replace($root_path, '', $UPLOAD_PATH);
            if (!empty($config['login_page_logo']) && file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_logo'])) {
                $login_logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_logo'];
            } elseif (file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'login-logo.png')) {
                $login_logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'login-logo.png';
            } else {
                $login_logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'login-logo.default.png';
            }

            if (!empty($config['login_page_wallpaper']) && file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_wallpaper'])) {
                $wallpaper = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_wallpaper'];
            } elseif (file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'wallpaper.png')) {
                $wallpaper = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'wallpaper.png';
            } else {
                $wallpaper = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'wallpaper.default.png';
            }

            if (!empty($config['login_page_favicon']) && file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_favicon'])) {
                $favicon = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_favicon'];
            } elseif (file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'favicon.png')) {
                $favicon = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'favicon.png';
            } else {
                $favicon = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'favicon.default.png';
            }

            $ui->assign('login_logo', $login_logo);
            $ui->assign('wallpaper', $wallpaper);
            $ui->assign('favicon', $favicon);
            $ui->assign('csrf_token', $csrf_token);
            $ui->assign('captcha_widget', Captcha::renderWidget('customer'));
            $ui->assign('_title', Lang::T('Login'));
            $ui->assign('customFields', User::getFormCustomField($ui, true));
            $ui->assign('username', "");
            $ui->assign('fullname', "");
            $ui->assign('address', "");
            $ui->assign('email', "");
            $ui->assign('otp', false);
            $ui->assign('referral_code', isset($referral_code) ? $referral_code : null);
            $ui->assign('_title', Lang::T('Register'));
            run_hook('view_register'); #HOOK
            switch ($config['login_page_type']) {
                case 'custom':
                    $ui->display('customer/reg-login-custom-' . $config['login_Page_template'] . '.tpl');
                    break;
                default:
                    $ui->display('customer/register.tpl');
                    break;
            }
        }
        break;
}
