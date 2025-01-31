<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

if ($_c['disable_registration'] == 'noreg') {
    _alert(Lang::T('Registration Disabled'), 'danger', "login");
}
if (isset($routes['1'])) {
    $do = $routes['1'];
} else {
    $do = 'register-display';
}

$otpPath = $CACHE_PATH . File::pathFixer('/sms/');

switch ($do) {
    case 'post':
        $otp_code = _post('otp_code');
        $username = alphanumeric(_post('username'), "+_.@-");
        $email = _post('email');
        $fullname = _post('fullname');
        $password = _post('password');
        $cpassword = _post('cpassword');
        $address = _post('address');

        // Separate phone number input if OTP is required
        $phone_number = ($config['sms_otp_registration'] == 'yes') ? alphanumeric(_post('phone_number'), "+_.@-") : $username;

        $msg = '';
        if (Validator::Length($username, 35, 2) == false) {
            $msg .= "Username should be between 3 to 55 characters<br>";
        }
        if ($config['man_fields_fname'] == 'yes') {
            if (Validator::Length($fullname, 36, 2) == false) {
                $msg .= "Full Name should be between 3 to 25 characters<br>";
            }
        }
        if (!Validator::Length($password, 35, 2)) {
            $msg .= "Password should be between 3 to 35 characters<br>";
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
            $otpPath .= sha1("$phone_number$db_pass") . ".txt";
            run_hook('validate_otp'); #HOOK
            // Expire after 10 minutes
            if (file_exists($otpPath) && time() - filemtime($otpPath) > 1200) {
                unlink($otpPath);
                r2(getUrl('register'), 's', 'Verification code expired');
            } else if (file_exists($otpPath)) {
                $code = file_get_contents($otpPath);
                if ($code != $otp_code) {
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
                } else {
                    unlink($otpPath);
                }
            } else {
                r2(getUrl('register'), 's', 'No Verification code');
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
                if ($config['photo_register'] == 'yes' && !empty($_FILES['photo']['name']) && file_exists($_FILES['photo']['tmp_name'])) {
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
                if (file_exists($_FILES['photo']['tmp_name'])) unlink($_FILES['photo']['tmp_name']);
                User::setFormCustomField($user);
                run_hook('register_user'); #HOOK
                $msg .= Lang::T('Registration successful') . '<br>';
                if ($config['reg_nofify_admin'] == 'yes') {
                    sendTelegram($config['CompanyName'] . ' - ' . Lang::T('New User Registration') . "\n\nFull Name: " . $fullname . "\nUsername: " . $username . "\nEmail: " . $email . "\nPhone Number: " . $phone_number . "\nAddress: " . $address);
                }
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
            $ui->assign('notify', $msg);
            $ui->assign('notify_t', 'd');
            $ui->assign('_title', Lang::T('Register'));
            // Check if OTP is enabled
            if (!empty($config['sms_url']) && $_c['sms_otp_registration'] == 'yes') {
                // Display register-otp.tpl if OTP is enabled
                $ui->display('customer/register-otp.tpl');
            } else {
                // Display register.tpl if OTP is not enabled
                $ui->display('customer/register.tpl');
            }
        }
        break;

    default:
        if ($_c['sms_otp_registration'] == 'yes') {
            $phone_number = _post('phone_number');
            if (!empty($phone_number)) {
                $d = ORM::for_table('tbl_customers')->where('username', $phone_number)->find_one();
                if ($d) {
                    r2(getUrl('register'), 's', Lang::T('Account already exists'));
                }
                if (!file_exists($otpPath)) {
                    mkdir($otpPath);
                    touch($otpPath . 'index.html');
                }
                $otpPath .= sha1($phone_number . $db_pass) . ".txt";
                if (file_exists($otpPath) && time() - filemtime($otpPath) < 600) {
                    $ui->assign('phone_number', $phone_number);
                    $ui->assign('notify', 'Please wait ' . (600 - (time() - filemtime($otpPath))) . ' seconds before sending another SMS');
                    $ui->assign('notify_t', 'd');
                    $ui->assign('_title', Lang::T('Register'));
                    $ui->display('customer/register-otp.tpl');
                } else {
                    $otp = rand(100000, 999999);
                    file_put_contents($otpPath, $otp);
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
                    $ui->display('customer/register-otp.tpl');
                }
            } else {
                $ui->assign('_title', Lang::T('Register'));
                run_hook('view_otp_register'); #HOOK
                $ui->display('customer/register-rotp.tpl');
            }
        } else {
            $ui->assign('customFields', User::getFormCustomField($ui, true));
            $ui->assign('username', "");
            $ui->assign('fullname', "");
            $ui->assign('address', "");
            $ui->assign('email', "");
            $ui->assign('otp', false);
            $ui->assign('_title', Lang::T('Register'));
            run_hook('view_register'); #HOOK
            $ui->display('customer/register.tpl');
        }
        break;
}
