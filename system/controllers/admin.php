<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Pragma: no-cache");

if (Admin::getID()) {
    r2(getUrl('dashboard'), "s", Lang::T("You are already logged in"));
}

if (isset($routes['1'])) {
    $do = $routes['1'];
} else {
    $do = 'login-display';
}

switch ($do) {
    case 'post':
        $username = _post('username');
        $password = _post('password');
        //csrf token
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            _alert(Lang::T('Invalid or Expired CSRF Token') . ".", 'danger', "admin");
        }
        // Captcha verification
        $captchaResult = Captcha::verify('admin');
        if (!$captchaResult['success']) {
            _alert($captchaResult['message'], 'danger', "admin");
        }
        run_hook('admin_login'); #HOOK
        if ($username != '' and $password != '') {
            // Attempts are counted on the server (not in the visitor's session): per username from one address, per address, and per
            // username overall, so neither one machine nor a spread-out guesser gets unlimited tries.
            $ip = AuthThrottle::clientIp();
            $wait = max(
                AuthThrottle::blocked('admin-user-ip', $username . '|' . $ip, 5, 900),
                AuthThrottle::blocked('admin-ip', $ip, 30, 900),
                AuthThrottle::blocked('admin-user', $username, 25, 900)
            );
            if ($wait > 0) {
                _log('Too many admin login attempts for ' . $username . ' from ' . $ip, 'Security');
                _alert(Lang::T('Too many login attempts. Please try again later.') . ' (' . AuthThrottle::waitText($wait) . ')', 'danger', "admin");
            }
            $d = ORM::for_table('tbl_users')->where('username', $username)->find_one();
            // an unknown username costs the same time as a wrong password
            $ok = $d ? Password::_verify($password, $d['password']) : (Password::_verify($password, '$2y$10$DypQq9WoaMNQk2QXN5haBOqdcI2FmyKO5OjfOyvMVJ4.2nJl2nmIa') && false);
            if ($d && $ok) {
                AuthThrottle::clear('admin-user-ip', $username . '|' . $ip);
                AuthThrottle::clear('admin-user', $username);
                session_regenerate_id(true); // a session id an attacker planted before the login is worthless afterwards
                if (Password::needsRehash($d['password'])) {
                    $d->password = Password::_crypt($password); // old unsalted SHA-1 becomes bcrypt on the first good login
                }
                $_SESSION['aid'] = $d['id'];
                $token = Admin::setCookie($d['id']);
                $d->last_login = date('Y-m-d H:i:s');
                $d->save();
                _log($username . ' ' . Lang::T('Login Successful'), $d['user_type'], $d['id']);
                if ($isApi) {
                    if ($token) {
                        showResult(true, Lang::T('Login Successful'), ['token' => "a." . $token]);
                    } else {
                        showResult(false, Lang::T('Invalid Username or Password'));
                    }
                }
                _alert(Lang::T('Login Successful'), 'success', "dashboard");
            } else {
                AuthThrottle::hit('admin-user-ip', $username . '|' . $ip, 900);
                AuthThrottle::hit('admin-ip', $ip, 900);
                AuthThrottle::hit('admin-user', $username, 900);
                _log($username . ' ' . Lang::T('Failed Login'), $d ? $d['user_type'] : 'Security');
                _alert(Lang::T('Invalid Username or Password') . ".", 'danger', "admin");
            }
        } else {
            _alert(Lang::T('Invalid Username or Password') . "...", 'danger', "admin");
        }

        break;
    default:
        run_hook('view_login'); #HOOK
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->assign('captcha_widget', Captcha::renderWidget('admin'));
        $ui->display('admin/admin/login.tpl');
        break;
}
