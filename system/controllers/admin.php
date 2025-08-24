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
        run_hook('admin_login'); #HOOK
        $tsEnabled = (!empty($_c['turnstile_admin_enabled']) && $_c['turnstile_admin_enabled'] == '1');
        $secret = $_c['turnstile_secret_key'] ?? '';
        if ($secret === '' && defined('TURNSTILE_SECRET_KEY')) {
            $secret = TURNSTILE_SECRET_KEY;
        }
        if (!$isApi && $tsEnabled && !empty($secret)) {
            $token = _post('cf-turnstile-response');
            if (empty($token)) {
                _alert(Lang::T('Please verify you are human'), 'danger', "admin");
            }
            $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
            $payload = http_build_query([
                'secret'   => $secret,
                'response' => $token,
                // 'remoteip' => $_SERVER['REMOTE_ADDR'], // opsional
            ]);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            ]);
            $resp = curl_exec($ch);
            $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);
            $ok = false;
            if ($http === 200 && $resp) {
                $json = json_decode($resp, true);
                $ok = isset($json['success']) && $json['success'] === true;
            }
            if (!$ok) {
                $msg = Lang::T('Verification failed');
                if (!empty($json['error-codes'])) {
                    $msg .= ' (' . implode(', ', (array)$json['error-codes']) . ')';
                } elseif (!empty($err)) {
                    $msg .= ' (' . $err . ')';
                }
                _log($username . ' ' . Lang::T('Failed Turnstile verification'), 'Admin');
                _alert($msg, 'danger', "admin");
            }
        }   
        if ($username != '' and $password != '') {
            $d = ORM::for_table('tbl_users')->where('username', $username)->find_one();
            if ($d) {
                $d_pass = $d['password'];
                if (Password::_verify($password, $d_pass) == true) {
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
                    _log($username . ' ' . Lang::T('Failed Login'), $d['user_type']);
                    _alert(Lang::T('Invalid Username or Password') . ".", 'danger', "admin");
                }
            } else {
                _alert(Lang::T('Invalid Username or Password') . "..", 'danger', "admin");
            }
        } else {
            _alert(Lang::T('Invalid Username or Password') . "...", 'danger', "admin");
        }

        break;
    default:
        run_hook('view_login'); #HOOK
        $sitekey = $_c['turnstile_site_key'] ?? '';
        if ($sitekey === '' && defined('TURNSTILE_SITE_KEY')) {
            $sitekey = TURNSTILE_SITE_KEY;
        }
        $ui->assign('turnstile_site_key', $sitekey);
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->display('admin/admin/login.tpl');
        break;
}

