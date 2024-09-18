<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/


class Admin
{

    public static function getID()
    {
        global $db_pass, $config;
        $enable_session_timeout = $config['enable_session_timeout'];
        $session_timeout_duration = $config['session_timeout_duration'] ? intval($config['session_timeout_duration'] * 60) : intval(60 * 60); // Convert minutes to seconds

        // Check if the session is active and valid
        if (isset($_SESSION['aid']) && isset($_SESSION['aid_expiration'])) {
            if ($_SESSION['aid_expiration'] > time()) {
                if ($enable_session_timeout) {
                    $_SESSION['aid_expiration'] = time() + $session_timeout_duration;
                }
                return $_SESSION['aid'];
            }
            // Session expired, log out the user
            elseif ($enable_session_timeout && $_SESSION['aid_expiration'] <= time()) {
                self::removeCookie();
                session_destroy();
                _alert(Lang::T('Session has expired. Please log in again.'), 'danger', "admin");
                return 0;
            }
        }

        // Check if the cookie is set and valid
        elseif (isset($_COOKIE['aid'])) {
            $tmp = explode('.', $_COOKIE['aid']);
            if (sha1($tmp[0] . '.' . $tmp[1] . '.' . $db_pass) == $tmp[2]) {
                if (time() - $tmp[1] < 86400 * 7) {
                    $_SESSION['aid'] = $tmp[0];
                    if ($enable_session_timeout) {
                        $_SESSION['aid_expiration'] = time() + $session_timeout_duration;
                    }
                    return $tmp[0];
                }
            }
        }

        return 0;
    }

    public static function setCookie($aid)
    {
        global $db_pass, $config;
        $enable_session_timeout = $config['enable_session_timeout'];
        if (isset($aid)) {
            $time = time();
            $token = $aid . '.' . $time . '.' . sha1($aid . '.' . $time . '.' . $db_pass);
            setcookie('aid', $token, time() + 86400 * 7);
            $_SESSION['aid'] = $aid;
            if ($enable_session_timeout) {
                $timeout = 60;
                if ($config['session_timeout_duration']) {
                    $timeout = intval($config['session_timeout_duration']);
                }
                $session_timeout_duration = $timeout * 60; // Convert minutes to seconds
                $_SESSION['aid_expiration'] = $time + $session_timeout_duration;
            }
            return $token;
        }
        return '';
    }

    public static function removeCookie()
    {
        if (isset($_COOKIE['aid'])) {
            setcookie('aid', '', time() - 86400);
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
}
