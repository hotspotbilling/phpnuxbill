<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/*
 * Forgot password / forgot username.
 *
 *  step 0  ask for the username                         step 1  ask for the code that was sent
 *  step 2  choose a new password (after a right code)   step 3  done
 *  step 6  forgot username: ask for phone or email      step 7  send the usernames
 *
 * Every answer about whether an account exists is the same either way, a code dies after 5 wrong guesses, and addresses and
 * usernames have their own server-side limits (AuthThrottle), so the flow cannot be used to find accounts, to guess codes
 * or to run up the SMS bill.
 */

$step = _req('step', 0);
$ip = AuthThrottle::clientIp();
$generic = Lang::T("If your Username is found, Verification Code has been Sent to Your Phone/Email/Whatsapp") . ' ' . Lang::T("You can ask for another one in 10 minutes.");

/** Send a message by the channel the site uses for reminders (SMS or WhatsApp), plus email when there is an address. */
function forgot_notify($phone, $email, $text, $subject)
{
    global $config;
    $via = $config['user_notification_reminder'];
    if ($via == 'email') {
        $via = 'sms';
    }
    if (!empty($phone)) {
        if ($via == 'sms') {
            Message::sendSMS($phone, $text);
        } else {
            Message::sendWhatsapp($phone, $text);
        }
    }
    if (!empty($email)) {
        Message::sendEmail($email, $subject, nl2br(htmlspecialchars($text)));
    }
}

if ($step == '-1') {
    unset($_SESSION['forgot_username'], $_SESSION['forgot_ok']);
    $step = 0;
}

if ($step == 1) {
    $username = trim((string) _post('username'));
    if ($username === '' && !empty($_SESSION['forgot_username'])) {
        // coming back to the code page (after a wrong code, say): nothing is sent again
        $ui->assign('username', $_SESSION['forgot_username']);
    } elseif ($username === '') {
        $step = 0;
    } else {
        $captchaResult = Captcha::verify('customer');
        if (!$captchaResult['success']) {
            r2(getUrl('forgot'), 'e', $captchaResult['message']);
        }
        // every request counts, found or not, so scanning usernames or SMS-bombing is capped per address
        $wait = AuthThrottle::blocked('forgot-send-ip', $ip, 10, 3600);
        if ($wait > 0) {
            r2(getUrl('forgot'), 'e', Lang::T('Too many requests. Please try again later.') . ' (' . AuthThrottle::waitText($wait) . ')');
        }
        AuthThrottle::hit('forgot-send-ip', $ip, 3600);
        $_SESSION['forgot_username'] = $username;
        $ui->assign('username', $username);
        $user = ORM::for_table('tbl_customers')->selects(['phonenumber', 'email'])->where('username', $username)->find_one();
        $age = Otp::age('forgot', $username);
        if ($user && ($age < 0 || $age >= Otp::TTL)) {
            $otp = Otp::issue('forgot', $username);
            forgot_notify($user['phonenumber'], $user['email'], $config['CompanyName'] . " C0de: $otp", $config['CompanyName'] . ' ' . Lang::T("Your Verification Code") . ' : ' . $otp);
        }
        $ui->assign('notify_t', 's');
        $ui->assign('notify', $generic);
    }
} else if ($step == 2) {
    $username = trim((string) _post('username'));
    $otp_code = trim((string) _post('otp_code'));
    if ($username === '' || $otp_code === '') {
        r2(getUrl('forgot&step=1'), 'e', Lang::T('Invalid Username or Verification Code'));
    }
    $waitIp = AuthThrottle::blocked('forgot-verify-ip', $ip, 20, 900);
    $waitUser = AuthThrottle::blocked('forgot-verify-user', $username, 20, 3600);
    if ($waitIp > 0 || $waitUser > 0) {
        r2(getUrl('forgot&step=1'), 'e', Lang::T('Too many requests. Please try again later.') . ' (' . AuthThrottle::waitText(max($waitIp, $waitUser)) . ')');
    }
    if (Otp::check('forgot', $username, $otp_code) === 'ok') {
        $_SESSION['forgot_ok'] = ['u' => $username, 't' => time()];
        $ui->assign('username', $username);
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->assign('notify_t', 's');
        $ui->assign('notify', Lang::T("Verification Code Valid") . '. ' . Lang::T('Choose a new password.'));
    } else {
        AuthThrottle::hit('forgot-verify-ip', $ip, 900);
        AuthThrottle::hit('forgot-verify-user', $username, 3600);
        r2(getUrl('forgot&step=1'), 'e', Lang::T('Invalid Username or Verification Code'));
    }
} else if ($step == 3) {
    $username = trim((string) _post('username'));
    $ticket = $_SESSION['forgot_ok'] ?? null;
    if (!$ticket || !hash_equals((string) $ticket['u'], $username) || time() - (int) $ticket['t'] > 900) {
        unset($_SESSION['forgot_ok']);
        r2(getUrl('forgot'), 'e', Lang::T('Invalid Username or Verification Code'));
    }
    if (!Csrf::check(_post('csrf_token'))) {
        unset($_SESSION['forgot_ok']);
        r2(getUrl('forgot'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
    }
    $npass = (string) _post('npass');
    $cnpass = (string) _post('cnpass');
    $error = Password::policyError($npass, $username);
    if ($error === '' && $npass !== $cnpass) {
        $error = Lang::T('Passwords does not match');
    }
    if ($error !== '') {
        $ui->assign('step', 2);
        $ui->assign('username', $username);
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->assign('notify_t', 'e');
        $ui->assign('notify', $error);
        $ui->assign('_title', Lang::T('Forgot Password'));
        $ui->display('customer/forgot.tpl');
        exit;
    }
    $user = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
    $routerFailed = false;
    if ($user) {
        // one password change at a time for this customer (the account page takes the same lock), read again under it
        if (!Package::passwordLock($user['id'])) {
            $ui->assign('step', 2);
            $ui->assign('username', $username);
            $ui->assign('csrf_token', Csrf::generateAndStoreToken());
            $ui->assign('notify_t', 'e');
            $ui->assign('notify', Lang::T('Your password is being changed by another request. Try again in a moment.'));
            $ui->assign('_title', Lang::T('Forgot Password'));
            $ui->display('customer/forgot.tpl');
            exit;
        }
        $user = ORM::for_table('tbl_customers')->find_one($user['id']);
    }
    if ($user) {
        Package::keepDialPassword($user); // a PPPoE / VPN connection keeps the password its router dials with (in memory: saved below)
        $user->password = $npass;
        $user->save();
        // the code is used up once the new password is saved, whatever the devices do next (a lock failure above kept it)
        unset($_SESSION['forgot_ok'], $_SESSION['forgot_username']);
        // the routers sign the customer in with their own copy of the password (a Mikrotik hotspot or PPPoE user): every
        // active plan's device gets the new one, like a password changed from the customer's account page. The new password
        // is saved already: a device that fails is logged, and the reset goes on
        try {
            foreach (Package::sendPassword($user) as $err) {
                $routerFailed = true;
                _log('[' . $username . ']: new password not sent to the router: ' . $err, 'User', $user['id']);
            }
        } catch (Throwable $e) {
            $routerFailed = true;
            _log('[' . $username . ']: new password not sent to the router: ' . $e->getMessage(), 'User', $user['id']);
        }
        Package::passwordLock($user['id'], false);
        if (class_exists('RedisCache')) {
            RedisCache::forgetRadiusCustomer($username);
        }
        User::revokeCookies($user['id']); // every session and remember-me cookie opened with the old password ends
        _log('[' . $username . ']: Password reset with a verification code', 'User', $user['id']);
        forgot_notify($user['phonenumber'], $user['email'], $config['CompanyName'] . ': ' . Lang::T('Your password was just changed. If this was not you, contact us at once.'), $config['CompanyName'] . ' - ' . Lang::T('Your password was changed'));
    } else {
        unset($_SESSION['forgot_ok'], $_SESSION['forgot_username']); // no such account: the code is used up all the same
    }
    AuthThrottle::clear('forgot-verify-user', $username);
    $ui->assign('username', $username);
    $ui->assign('notify_t', 's');
    $ui->assign('notify', Lang::T('Password changed successfully, Please login again')
        . ($routerFailed ? '. ' . Lang::T('Your portal password was changed, but it could not be applied to your internet connection. Your internet service may not accept the new password: please contact support about your network access.') : ''));
} else if ($step == 7) {
    $find = trim((string) _post('find'));
    $step = 6;
    if ($find !== '') {
        $captchaResult = Captcha::verify('customer');
        if (!$captchaResult['success']) {
            r2(getUrl('forgot&step=6'), 'e', $captchaResult['message']);
        }
        $wait = AuthThrottle::blocked('forgot-send-ip', $ip, 10, 3600);
        if ($wait > 0) {
            r2(getUrl('forgot&step=6'), 'e', Lang::T('Too many requests. Please try again later.') . ' (' . AuthThrottle::waitText($wait) . ')');
        }
        AuthThrottle::hit('forgot-send-ip', $ip, 3600);
        // one message per phone/email every 10 minutes, whatever the answer was
        if (AuthThrottle::blocked('forgot-find', $find, 1, 600) === 0) {
            AuthThrottle::hit('forgot-find', $find, 600);
            $users = ORM::for_table('tbl_customers')->selects(['username', 'phonenumber', 'email'])->where('phonenumber', $find)->find_array();
            $byEmail = false;
            if (!$users) {
                $users = ORM::for_table('tbl_customers')->selects(['username', 'phonenumber', 'email'])->where('email', $find)->find_array();
                $byEmail = true;
            }
            if ($users) {
                $text = Lang::T("Your username for") . ' ' . $config['CompanyName'] . "\n" . implode(", ", array_column($users, 'username'));
                $phones = [];
                foreach ($users as $u) {
                    if (!in_array($u['phonenumber'], $phones)) {
                        $phones[] = $u['phonenumber'];
                        forgot_notify($byEmail ? $u['phonenumber'] : $find, '', $text, '');
                        if (!$byEmail) {
                            break;
                        }
                    }
                }
                if ($byEmail) {
                    Message::sendEmail($find, Lang::T("Your username for") . ' ' . $config['CompanyName'], nl2br(htmlspecialchars($text)));
                }
            }
        }
        $ui->assign('notify_t', 's');
        $ui->assign('notify', Lang::T("If an account matches, the usernames have been sent to that phone/Whatsapp/Email") . ' ' . Lang::T("You can ask for another one in 10 minutes."));
        $step = 0;
    }
}

if (random_int(1, 50) === 1) {
    Otp::gc();
}

$ui->assign('captcha_widget', Captcha::renderWidget('customer'));
$ui->assign('step', $step);
$ui->assign('_title', Lang::T('Forgot Password'));
$ui->display('customer/forgot.tpl');
