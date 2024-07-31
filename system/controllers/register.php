<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

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
        if (!empty($config['sms_url'])) {
            $phonenumber = Lang::phoneFormat($username);
            $username = $phonenumber;
        } else if (strlen($username) < 21) {
            $phonenumber = $username;
        }
        $msg = '';
        if (Validator::Length($username, 35, 2) == false) {
            $msg .= 'Username should be between 3 to 55 characters' . '<br>';
        }
        if (Validator::Length($fullname, 36, 2) == false) {
            $msg .= 'Full Name should be between 3 to 25 characters' . '<br>';
        }
        if (!Validator::Length($password, 35, 2)) {
            $msg .= 'Password should be between 3 to 35 characters' . '<br>';
        }
        if (!Validator::Email($email)) {
            $msg .= 'Email is not Valid<br>';
        }
        if ($password != $cpassword) {
            $msg .= Lang::T('Passwords does not match') . '<br>';
        }

        if (!empty($config['sms_url'])) {
            $otpPath .= sha1($username . $db_pass) . ".txt";
            run_hook('validate_otp'); #HOOK
            //expired 10 minutes
            if (file_exists($otpPath) && time() - filemtime($otpPath) > 1200) {
                unlink($otpPath);
                r2(U . 'register', 's', 'Verification code expired');
            } else if (file_exists($otpPath)) {
                $code = file_get_contents($otpPath);
                if ($code != $otp_code) {
                    $ui->assign('username', $username);
                    $ui->assign('fullname', $fullname);
                    $ui->assign('address', $address);
                    $ui->assign('email', $email);
                    $ui->assign('phonenumber', $phonenumber);
                    $ui->assign('notify', 'Wrong Verification code');
                    $ui->assign('notify_t', 'd');
                    $ui->display('register-otp.tpl');
                    exit();
                } else {
                    unlink($otpPath);
                }
            } else {
                r2(U . 'register', 's', 'No Verification code');
            }
        }
        $d = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
        if ($d) {
            $msg .= Lang::T('Account already axist') . '<br>';
        }
        if ($msg == '') {
            run_hook('register_user'); #HOOK
            $d = ORM::for_table('tbl_customers')->create();
            $d->username = alphanumeric($username, "+_.@-");
            $d->password = $password;
            $d->fullname = $fullname;
            $d->address = $address;
            $d->email = $email;
            $d->phonenumber = $phonenumber;
            if ($d->save()) {
                $user = $d->id();
                r2(U . 'login', 's', Lang::T('Register Success! You can login now'));
            } else {
                $ui->assign('username', $username);
                $ui->assign('fullname', $fullname);
                $ui->assign('address', $address);
                $ui->assign('email', $email);
                $ui->assign('phonenumber', $phonenumber);
                $ui->assign('notify', 'Failed to register');
                $ui->assign('notify_t', 'd');
                run_hook('view_otp_register'); #HOOK
                $ui->display('register-rotp.tpl');
            }
        } else {
            $ui->assign('username', $username);
            $ui->assign('fullname', $fullname);
            $ui->assign('address', $address);
            $ui->assign('email', $email);
            $ui->assign('phonenumber', $phonenumber);
            $ui->assign('notify', $msg);
            $ui->assign('notify_t', 'd');
            $ui->display('register.tpl');
        }
        break;

    default:
        if (!empty($config['sms_url'])) {
            $username = _post('username');
            if (!empty($username)) {
                $d = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
                if ($d) {
                    r2(U . 'register', 's', Lang::T('Account already axist'));
                }
                if (!file_exists($otpPath)) {
                    mkdir($otpPath);
                    touch($otpPath . 'index.html');
                }
                $otpPath .= sha1($username . $db_pass) . ".txt";
                //expired 10 minutes
                if (file_exists($otpPath) && time() - filemtime($otpPath) < 1200) {
                    $ui->assign('username', $username);
                    $ui->assign('notify', 'Please wait ' . (1200 - (time() - filemtime($otpPath))) . ' seconds before sending another SMS');
                    $ui->assign('notify_t', 'd');
                    $ui->display('register-otp.tpl');
                } else {
                    $otp = rand(100000, 999999);
                    file_put_contents($otpPath, $otp);
                    Message::sendSMS($username, $config['CompanyName'] . "\nYour Verification code are: $otp");
                    $ui->assign('username', $username);
                    $ui->assign('notify', 'Verification code has been sent to your phone');
                    $ui->assign('notify_t', 's');
                    $ui->display('register-otp.tpl');
                }
            } else {
                run_hook('view_otp_register'); #HOOK
                $ui->display('register-rotp.tpl');
            }
        } else {
            $ui->assign('username', "");
            $ui->assign('fullname', "");
            $ui->assign('address', "");
            $ui->assign('email', "");
            $ui->assign('otp', false);
            run_hook('view_register'); #HOOK
            $ui->display('register.tpl');
        }
        break;
}
