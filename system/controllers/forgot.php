<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/
$step = _req('step', 0);
$otpPath = $CACHE_PATH . File::pathFixer('/forgot/');

if ($step == '-1') {
    $_COOKIE['forgot_username'] = '';
    setcookie('forgot_username', '', time() - 3600, '/');
    $step = 0;
}

if (!empty($_COOKIE['forgot_username']) && in_array($step, [0, 1])) {
    $step = 1;
    $_POST['username'] = $_COOKIE['forgot_username'];
}

if ($step == 1) {
    $username = _post('username');
    if (!empty($username)) {
        $ui->assign('username', $username);
        if (!file_exists($otpPath)) {
            mkdir($otpPath);
        }
        setcookie('forgot_username', $username, time() + 3600, '/');
        $user = ORM::for_table('tbl_customers')->selects(['phonenumber', 'email'])->where('username', $username)->find_one();
        if ($user) {
            $otpPath .= sha1($username . $db_pass) . ".txt";
            if (file_exists($otpPath) && time() - filemtime($otpPath) < 600) {
                $sec = time() - filemtime($otpPath);
                $ui->assign('notify_t', 's');
                $ui->assign('notify', Lang::T("Verification Code already Sent to Your Phone/Email/Whatsapp, please wait")." $sec seconds.");
            } else {
                $via = $config['user_notification_reminder'];
                if ($via == 'email') {
                    $via = 'sms';
                }
                $otp = mt_rand(100000, 999999);
                file_put_contents($otpPath, $otp);
                if ($via == 'sms') {
                    Message::sendSMS($user['phonenumber'], $config['CompanyName'] . " C0de: $otp");
                } else {
                    Message::sendWhatsapp($user['phonenumber'], $config['CompanyName'] . " C0de: $otp");
                }
                Message::sendEmail(
                    $user['email'],
                    $config['CompanyName'] . Lang::T("Your Verification Code") . ' : ' . $otp,
                    Lang::T("Your Verification Code") . ' : <b>' . $otp . '</b>'
                );
                $ui->assign('notify_t', 's');
                $ui->assign('notify', Lang::T("If your Username is found, Verification Code has been Sent to Your Phone/Email/Whatsapp"));
            }
        } else {
            // Username not found
            $ui->assign('notify_t', 's');
            $ui->assign('notify', Lang::T("If your Username is found, Verification Code has been Sent to Your Phone/Email/Whatsapp") . ".");
        }
    } else {
        $step = 0;
    }
} else if ($step == 2) {
    $username = _post('username');
    $otp_code = _post('otp_code');
    if (!empty($username) && !empty($otp_code)) {
        $otpPath .= sha1($username . $db_pass) . ".txt";
        if (file_exists($otpPath) && time() - filemtime($otpPath) <= 600) {
            $otp = file_get_contents($otpPath);
            if ($otp == $otp_code) {
                $pass = mt_rand(10000, 99999);
                $user = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
                $user->password = $pass;
                $user->save();
                $ui->assign('username', $username);
                $ui->assign('passsword', $pass);
                $ui->assign('notify_t', 's');
                $ui->assign('notify', Lang::T("Verification Code Valid"));
                if (file_exists($otpPath)) {
                    unlink($otpPath);
                }
                setcookie('forgot_username', '', time() - 3600, '/');
            } else {
                r2(U . 'forgot&step=1', 'e', Lang::T('Invalid Username or Verification Code'));
            }
        } else {
            if (file_exists($otpPath)) {
                unlink($otpPath);
            }
            r2(U . 'forgot&step=1', 'e', Lang::T('Invalid Username or Verification Code'));
        }
    } else {
        r2(U . 'forgot&step=1', 'e', Lang::T('Invalid Username or Verification Code'));
    }
} else if ($step == 7) {
    $find = _post('find');
    $step = 6;
    if (!empty($find)) {
        $via = $config['user_notification_reminder'];
        if ($via == 'email') {
            $via = 'sms';
        }
        if (!file_exists($otpPath)) {
            mkdir($otpPath);
        }
        $otpPath .= sha1($find . $db_pass) . ".txt";
        $users = ORM::for_table('tbl_customers')->selects(['username', 'phonenumber', 'email'])->where('phonenumber', $find)->find_array();
        if ($users) {
            // prevent flooding only can request every 10 minutes
            if (!file_exists($otpPath) || (file_exists($otpPath) && time() - filemtime($otpPath) >= 600)) {
                $usernames = implode(", ", array_column($users, 'username'));
                if ($via == 'sms') {
                    Message::sendSMS($find, Lang::T("Your username for") . ' ' . $config['CompanyName'] . "\n" . $usernames);
                } else {
                    Message::sendWhatsapp($find, Lang::T("Your username for") . ' ' . $config['CompanyName'] . "\n" . $usernames);
                }
                file_put_contents($otpPath, time());
            }
            $ui->assign('notify_t', 's');
            $ui->assign('notify', Lang::T("Usernames have been sent to your phone/Whatsapp") . " $find");
            $step = 0;
        } else {
            $users = ORM::for_table('tbl_customers')->selects(['username', 'phonenumber', 'email'])->where('email', $find)->find_array();
            if ($users) {
                // prevent flooding only can request every 10 minutes
                if (!file_exists($otpPath) || (file_exists($otpPath) && time() - filemtime($otpPath) >= 600)) {
                    $usernames = implode(", ", array_column($users, 'username'));
                    $phones = [];
                    foreach ($users as $user) {
                        if (!in_array($user['phonenumber'], $phones)) {
                            if ($via == 'sms') {
                                Message::sendSMS($user['phonenumber'], Lang::T("Your username for") . ' ' . $config['CompanyName'] . "\n" . $usernames);
                            } else {
                                Message::sendWhatsapp($user['phonenumber'], Lang::T("Your username for") . ' ' . $config['CompanyName'] . "\n" . $usernames);
                            }
                            $phones[] = $user['phonenumber'];
                        }
                    }
                    Message::sendEmail(
                        $user['email'],
                        Lang::T("Your username for") . ' ' . $config['CompanyName'],
                        Lang::T("Your username for") . ' ' . $config['CompanyName'] . "\n" . $usernames
                    );
                    file_put_contents($otpPath, time());
                }
                $ui->assign('notify_t', 's');
                $ui->assign('notify', Lang::T("Usernames have been sent to your phone/Whatsapp/Email"));
                $step = 0;
            } else {
                $ui->assign('notify_t', 'e');
                $ui->assign('notify', Lang::T("No data found"));
            }
        }
    }
}

// delete old files
$pth = $CACHE_PATH . File::pathFixer('/forgot/');
$fs = scandir($pth);
foreach ($fs as $file) {
    if(is_file($pth.$file) && time() - filemtime($pth.$file) > 3600) {
        unlink($pth.$file);
    }
}

$ui->assign('step', $step);
$ui->assign('_title', Lang::T('Forgot Password'));
$ui->display('customer/forgot.tpl');
