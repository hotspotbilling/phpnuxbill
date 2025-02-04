<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/


_auth();
$ui->assign('_title', Lang::T('My Account'));
$ui->assign('_system_menu', 'accounts');

$action = $routes['1'];
$user = User::_info();
$ui->assign('_user', $user);

switch ($action) {

    case 'change-password':
        run_hook('customer_view_change_password'); #HOOK
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->display('customer/change-password.tpl');
        break;

    case 'change-password-post':
        $password = _post('password');
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('accounts/change-password'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        run_hook('customer_change_password'); #HOOK
        if ($password != '') {
            $d_pass = $user['password'];
            $npass = _post('npass');
            $cnpass = _post('cnpass');
            if ($password == $d_pass) {
                if (!Validator::Length($password, 36, 2)) {
                    r2(getUrl('accounts/change-password'), 'e', 'New Password must be 2 to 35 character');
                }
                if ($npass != $cnpass) {
                    r2(getUrl('accounts/change-password'), 'e', 'Both Password should be same');
                }
                $user->password = $npass;
                $turs = ORM::for_table('tbl_user_recharges')->where('customer_id', $user['id'])->find_many();
                foreach ($turs as $tur) {
                    // if has active plan, change the password to devices
                    if ($tur['status'] == 'on') {
                        $p = ORM::for_table('tbl_plans')->where('id', $tur['plan_id'])->find_one();
                        $dvc = Package::getDevice($p);
                        if ($_app_stage != 'demo') {
                            if (file_exists($dvc)) {
                                require_once $dvc;
                                (new $p['device'])->add_customer($user, $p);
                            } else {
                                new Exception(Lang::T("Devices Not Found"));
                            }
                        }
                    }
                }
                $user->save();
                User::removeCookie();
                session_destroy();
                _log('[' . $user['username'] . ']: Password changed successfully', 'User', $user['id']);
                _alert(Lang::T('Password changed successfully, Please login again'), 'success', "login");
            } else {
                r2(getUrl('accounts/change-password'), 'e', Lang::T('Incorrect Current Password'));
            }
        } else {
            r2(getUrl('accounts/change-password'), 'e', Lang::T('Incorrect Current Password'));
        }
        break;

    case 'profile':
        run_hook('customer_view_edit_profile'); #HOOK
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->assign('customFields', User::getFormCustomField($ui, false, $user['id']));
        $ui->display('customer/profile.tpl');
        break;
    case 'edit-profile-post':
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('accounts/profile'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $fullname = _post('fullname');
        $address = _post('address');
        $email = _post('email');
        $phonenumber = _post('phonenumber');
        run_hook('customer_edit_profile'); #HOOK
        $msg = '';
        if (Validator::Length($fullname, 31, 1) == false) {
            $msg .= 'Full Name should be between 1 to 30 characters' . '<br>';
        }
        if (Validator::UnsignedNumber($phonenumber) == false) {
            $msg .= 'Phone Number must be a number' . '<br>';
        }

        if (empty($msg)) {
            if (!empty($_FILES['photo']['name']) && file_exists($_FILES['photo']['tmp_name'])) {
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
                    if (!file_exists($imgPath)) {
                        File::resizeCropImage($_FILES['photo']['tmp_name'], $imgPath, 1600, 1600, 100);
                    }
                    if (!file_exists($imgPath . '.thumb.jpg')) {
                        if (_post('faceDetect') == 'yes') {
                            try {
                                $detector = new svay\FaceDetector();
                                $detector->setTimeout(5000);
                                $detector->faceDetect($imgPath);
                                $detector->cropFaceToJpeg($imgPath . '.thumb.jpg', false);
                            } catch (Exception $e) {
                                File::makeThumb($imgPath, $imgPath . '.thumb.jpg', 200);
                            } catch (Throwable $e) {
                                File::makeThumb($imgPath, $imgPath . '.thumb.jpg', 200);
                            }
                        } else {
                            File::makeThumb($imgPath, $imgPath . '.thumb.jpg', 200);
                        }
                    }
                    if (file_exists($imgPath)) {
                        if ($user['photo'] != '' && strpos($user['photo'], 'default') === false) {
                            if (file_exists($UPLOAD_PATH . $user['photo'])) {
                                unlink($UPLOAD_PATH . $user['photo']);
                                if (file_exists($UPLOAD_PATH . $user['photo'] . '.thumb.jpg')) {
                                    unlink($UPLOAD_PATH . $user['photo'] . '.thumb.jpg');
                                }
                            }
                        }
                        $user->photo = '/photos/' . $subfolder . '/' . $hash . '.jpg';
                    }
                    if (file_exists($_FILES['photo']['tmp_name'])) unlink($_FILES['photo']['tmp_name']);
                } else {
                    r2(getUrl('settings/app'), 'e', 'PHP GD is not installed');
                }
            }

            $user->fullname = $fullname;
            $user->address = $address;
            if ($_c['allow_phone_otp'] != 'yes') {
                $user->phonenumber = $phonenumber;
            }
            if ($_c['allow_email_otp'] != 'yes') {
                $user->email = $email;
            }

            User::setFormCustomField($user['id']);

            $user->save();

            _log('[' . $user['username'] . ']: ' . Lang::T('User Updated Successfully'), 'User', $user['id']);
            r2(getUrl('accounts/profile'), 's', Lang::T('User Updated Successfully'));
        }else{
            r2(getUrl('accounts/profile'), 'e', $msg);
        }
        break;


    case 'phone-update':
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->assign('new_phone', $_SESSION['new_phone']);
        $ui->display('customer/phone-update.tpl');
        break;

    case 'phone-update-otp':
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('accounts/phone-update'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $phone = Lang::phoneFormat(_post('phone'));
        $username = $user['username'];
        $otpPath = $CACHE_PATH . '/sms/';
        $_SESSION['new_phone'] = $phone;
        // Validate the phone number format
        if (!preg_match('/^[0-9]{10,}$/', $phone) || empty($phone)) {
            r2(getUrl('accounts/phone-update'), 'e', Lang::T('Invalid phone number format'));
        }

        if (empty($config['sms_url'])) {
            r2(getUrl('accounts/phone-update'), 'e', Lang::T('SMS server not Available, Please try again later'));
        }

        $d = ORM::for_table('tbl_customers')->whereNotEqual('username', $username)->where('phonenumber', $phone)->find_one();
        if ($d) {
            r2(getUrl('accounts/phone-update'), 'e', Lang::T('Phone number already registered by another customer'));
        }
        if (!file_exists($otpPath)) {
            mkdir($otpPath);
            touch($otpPath . 'index.html');
        }
        $otpFile = $otpPath . sha1($username . $db_pass) . ".txt";
        $phoneFile = $otpPath . sha1($username . $db_pass) . "_phone.txt";

        // expired 10 minutes
        if (file_exists($otpFile) && time() - filemtime($otpFile) < 600) {
            r2(getUrl('accounts/phone-update'), 'e', Lang::T('Please wait ') . (600 - (time() - filemtime($otpFile))) . Lang::T(' seconds before sending another SMS'));
        } else {
            $otp = rand(100000, 999999);
            file_put_contents($otpFile, $otp);
            file_put_contents($phoneFile, $phone);
            // send send OTP to user
            if ($config['phone_otp_type'] === 'sms') {
                Message::sendSMS($phone, $config['CompanyName'] . "\n\n" . Lang::T("Verification code") . "\n$otp");
            } elseif ($config['phone_otp_type'] === 'whatsapp') {
                Message::sendWhatsapp($phone, $config['CompanyName'] . "\n\n" . Lang::T("Verification code") . "\n$otp");
            } elseif ($config['phone_otp_type'] === 'both') {
                Message::sendSMS($phone, $config['CompanyName'] . "\n\n" . Lang::T("Verification code") . "\n$otp");
                Message::sendWhatsapp($phone, $config['CompanyName'] . "\n\n" . Lang::T("Verification code") . "\n$otp");
            }
            //redirect after sending OTP
            r2(getUrl('accounts/phone-update'), 'e', Lang::T('Verification code has been sent to your phone'));
        }

        break;

    case 'phone-update-post':
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('accounts/phone-update'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $phone = Lang::phoneFormat(_post('phone'));
        $otp_code = _post('otp');
        $username = $user['username'];
        $otpPath = $CACHE_PATH . '/sms/';

        // Validate the phone number format
        if (!preg_match('/^[0-9]{10,}$/', $phone)) {
            r2(getUrl('accounts/phone-update'), 'e', Lang::T('Invalid phone number format'));
        }

        if (empty($config['sms_url'])) {
            r2(getUrl('accounts/phone-update'), 'e', Lang::T('SMS server not Available, Please try again later'));
        }

        $otpFile = $otpPath . sha1($username . $db_pass) . ".txt";
        $phoneFile = $otpPath . sha1($username . $db_pass) . "_phone.txt";

        // Check if OTP file exists
        if (!file_exists($otpFile)) {
            r2(getUrl('accounts/phone-update'), 'e', Lang::T('Please request OTP first'));
            exit();
        }

        // expired 10 minutes
        if (time() - filemtime($otpFile) > 1200) {
            unlink($otpFile);
            unlink($phoneFile);
            r2(getUrl('accounts/phone-update'), 'e', Lang::T('Verification code expired'));
            exit();
        } else {
            $code = file_get_contents($otpFile);

            // Check if OTP code matches
            if ($code != $otp_code) {
                r2(getUrl('accounts/phone-update'), 'e', Lang::T('Wrong Verification code'));
                exit();
            }

            // Check if the phone number matches the one that requested the OTP
            $savedPhone = file_get_contents($phoneFile);
            if ($savedPhone !== $phone) {
                r2(getUrl('accounts/phone-update'), 'e', Lang::T('The phone number does not match the one that requested the OTP'));
                exit();
            }

            // OTP verification successful, delete OTP and phone number files
            unlink($otpFile);
            unlink($phoneFile);
        }

        // Update the phone number in the database
        $user->phonenumber = Lang::phoneFormat($phone);
        $user->save();

        r2(getUrl('accounts/profile'), 's', Lang::T('Phone number updated successfully'));
        break;

    case 'email-update':
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->assign('new_email', $_SESSION['new_email']);
        $ui->display('customer/email-update.tpl');
        break;
    case 'email-update-otp':
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('accounts/email-update'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $email = trim(_post('email'));
        $username = $user['username'];
        $otpPath = $CACHE_PATH . '/email/';
        $_SESSION['new_email'] = $email;
        // Validate the phone number format
        if (!Validator::Email($email)) {
            r2(getUrl('accounts/email-update'), 'e', Lang::T('Invalid Email address format'));
        }

        if (empty($config['smtp_host'])) {
            r2(getUrl('accounts/email-update'), 'e', Lang::T('Email server not Available, Please ask admin to configure it'));
        }

        $d = ORM::for_table('tbl_customers')->whereNotEqual('username', $username)->where('email', $email)->find_one();
        if ($d) {
            r2(getUrl('accounts/email-update'), 'e', Lang::T('Email already used by another Customer'));
        }
        if (!file_exists($otpPath)) {
            mkdir($otpPath);
            touch($otpPath . 'index.html');
        }
        $otpFile = $otpPath . sha1($username . $db_pass) . ".txt";
        $emailFile = $otpPath . sha1($username . $db_pass) . "_email.txt";

        // expired 10 minutes
        if (file_exists($otpFile) && time() - filemtime($otpFile) < 600) {
            r2(getUrl('accounts/email-update'), 'e', Lang::T('Please wait ') . (600 - (time() - filemtime($otpFile))) . Lang::T(' seconds before sending another Email'));
        } else {
            $otp = rand(100000, 999999);
            file_put_contents($otpFile, $otp);
            file_put_contents($emailFile, $email);
            // send OTP to user
            $body = Lang::T("Hello") . ' ' . $user['fullname'] . ",\n\n" . Lang::T("Your Email Verification Code is:") . " $otp";
            Message::sendEmail($email, Lang::T('Change Email Verification Code'), $body);
            //redirect after sending OTP
            r2(getUrl('accounts/email-update'), 'e', Lang::T('Verification code has been sent to your email. Check Spam folder if not found.'));
        }

        break;

    case 'email-update-post':
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('accounts/email-update'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $email = trim(_post('email'));
        $otp_code = _post('otp');
        $username = $user['username'];
        $otpPath = $CACHE_PATH . '/email/';
        // Validate the phone number format
        if (!Validator::Email($email)) {
            r2(getUrl('accounts/email-update'), 'e', Lang::T('Invalid Email address format'));
            exit();
        }

        if (empty($config['smtp_host'])) {
            r2(getUrl('accounts/email-update'), 'e', Lang::T('Email server not Available, Please ask admin to configure it'));
        }

        $otpFile = $otpPath . sha1($username . $db_pass) . ".txt";
        $emailFile = $otpPath . sha1($username . $db_pass) . "_email.txt";

        // Check if OTP file exists
        if (!file_exists($otpFile)) {
            r2(getUrl('accounts/email-update'), 'e', Lang::T('Please request OTP first'));
            exit();
        }

        // expired 10 minutes
        if (time() - filemtime($otpFile) > 1200) {
            unlink($otpFile);
            unlink($emailFile);
            r2(getUrl('accounts/email-update'), 'e', Lang::T('Verification code expired'));
            exit();
        } else {
            $code = file_get_contents($otpFile);

            // Check if OTP code matches
            if ($code != $otp_code) {
                r2(getUrl('accounts/email-update'), 'e', Lang::T('Wrong Verification code'));
                exit();
            }

            // Check if the phone number matches the one that requested the OTP
            $savedEmail = file_get_contents($emailFile);
            if ($savedEmail !== $email) {
                r2(getUrl('accounts/email-update'), 'e', Lang::T('The Email Address does not match the one that requested the OTP'));
                exit();
            }

            // OTP verification successful, delete OTP and phone number files
            unlink($otpFile);
            unlink($emailFile);
        }

        $user->email = $email;
        $user->save();

        r2(getUrl('accounts/profile'), 's', Lang::T('Email Address updated successfully'));
        break;

    case 'language-update-post':
        global $root_path;
        $selected_language = _req('lang', 'english');

        $_SESSION['user_language'] = $selected_language;

        $lan_file = $root_path . File::pathFixer('system/lan/' . $selected_language . '.json');

        if (file_exists($lan_file)) {
            $_L = json_decode(file_get_contents($lan_file), true);
        } else {
            $_L['author'] = 'Auto Generated by iBNuX Script';
            file_put_contents($lan_file, json_encode($_L));
        }
        User::setAttribute("Language", $selected_language);
        r2($_SERVER['HTTP_REFERER'], 's', ucwords($selected_language));


        break;

    default:
        $ui->display('admin/404.tpl');
}
