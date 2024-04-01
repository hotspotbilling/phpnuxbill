<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/


class Admin
{

    public static function getID()
    {
        global $db_password;
        if (isset($_SESSION['aid'])) {
            return $_SESSION['aid'];
        } else if (isset($_COOKIE['aid'])) {
            // id.time.sha1
            $tmp = explode('.', $_COOKIE['aid']);
            if (sha1($tmp[0] . '.' . $tmp[1] . '.' . $db_password) == $tmp[2]) {
                if (time() - $tmp[1] < 86400 * 7) {
                    $_SESSION['aid'] = $tmp[0];
                    return $tmp[0];
                }
            }
        }
        return 0;
    }

    public static function setCookie($aid)
    {
        global $db_password;
        if (isset($aid)) {
            $time = time();
            $token = $aid . '.' . $time . '.' . sha1($aid . '.' . $time . '.' . $db_password);
            setcookie('aid', $token, time() + 86400 * 7);
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
