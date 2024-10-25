<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/


class Admin
{

    public static function getID()
    {
        global $db_pass, $config, $isApi;

        $enable_session_timeout = $config['enable_session_timeout'] == 1;
        $session_timeout_duration = $config['session_timeout_duration'] ? intval($config['session_timeout_duration'] * 60) : intval(60 * 60); // Convert minutes to seconds
        if ($isApi) {
            $enable_session_timeout = false;
        }
        if ($enable_session_timeout && !empty($_SESSION['aid']) && !empty($_SESSION['aid_expiration'])) {
            if ($_SESSION['aid_expiration'] > time()) {
                $isValid = self::validateToken($_SESSION['aid'], $_COOKIE['aid']);
                if (!$isValid) {
                    self::removeCookie();
                    _alert(Lang::T('Token has expired. Please log in again.'), 'danger', "admin");
                    return 0;
                }
                // extend timeout duration
                $_SESSION['aid_expiration'] = time() + $session_timeout_duration;

                return $_SESSION['aid'];
            } else {
                // Session expired, log out the user
                self::removeCookie();
                _alert(Lang::T('Session has expired. Please log in again.'), 'danger', "admin");
                return 0;
            }
        } else if (!empty($_SESSION['aid'])) {
            $isValid = self::validateToken($_SESSION['aid'], $_COOKIE['aid']);
            if (!$isValid) {
                self::removeCookie();
                _alert(Lang::T('Token has expired. Please log in again.') . '.'.$_SESSION['aid'], 'danger', "admin");
                return 0;
            }
            return $_SESSION['aid'];
        }
        // Check if the cookie is set and valid
        elseif (isset($_COOKIE['aid'])) {
            $tmp = explode('.', $_COOKIE['aid']);
            if (sha1("$tmp[0].$tmp[1].$db_pass") == $tmp[2]) {
                // Validate the token in the cookie
                $isValid = self::validateToken($tmp[0], $_COOKIE['aid']);
                if ($isApi) {
                    // For now API need to always return true, next need to add revoke token API
                    $isValid = true;
                }
                if (!empty($_COOKIE['aid']) && !$isValid) {
                    self::removeCookie();
                    _alert(Lang::T('Token has expired. Please log in again.') . '..', 'danger', "admin");
                    return 0;
                } else {
                    if (time() - $tmp[1] < 86400 * 7) {
                        $_SESSION['aid'] = $tmp[0];
                        if ($enable_session_timeout) {
                            $_SESSION['aid_expiration'] = time() + $session_timeout_duration;
                        }
                        return $tmp[0];
                    }
                }
            }
        }

        return 0;
    }

    public static function setCookie($aid)
    {
        global $db_pass, $config;
        $enable_session_timeout = $config['enable_session_timeout'];
        $session_timeout_duration = intval($config['session_timeout_duration']) * 60; // Convert minutes to seconds

        if (isset($aid)) {
            $time = time();
            $token = $aid . '.' . $time . '.' . sha1("$aid.$time.$db_pass");

            // Detect the current protocol
            $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            // Set cookie with security flags
            setcookie('aid', $token, [
                'expires' => time() + 86400 * 7, // 7 days
                'path' => '/',
                'domain' => '',
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax', // or Strict
            ]);

            $_SESSION['aid'] = $aid;

            if ($enable_session_timeout) {
                $_SESSION['aid_expiration'] = $time + $session_timeout_duration;
            }

            self::upsertToken($aid, $token);

            return $token;
        }

        return '';
    }

    public static function removeCookie()
    {
        global $_app_stage;
        if (isset($_COOKIE['aid'])) {
            $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            setcookie('aid', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_destroy();
            session_unset();
            session_start();
            unset($_COOKIE['aid'], $_SESSION['aid']);
        }
    }

    public static function _info($id = 0)
    {
        if (empty($id) && $id == 0) {
            $id = Admin::getID();
        }
        if ($id) {
            return ORM::for_table('tbl_users')->find_one($id);
        } else {
            return null;
        }
    }

    public static function upsertToken($aid, $token)
    {
        $query = ORM::for_table('tbl_users')->findOne($aid);
        $query->login_token = sha1($token);
        $query->save();
    }

    public static function validateToken($aid, $cookieToken)
    {
        global $config;
        $query =  ORM::for_table('tbl_users')->select('login_token')->findOne($aid);
        if ($config['single_session'] != 'yes') {
            return true; // For multi-session, any token is valid
        }
        if (empty($query)) {
            return true;
        }
        return $query->login_token === sha1($cookieToken);
    }
}
