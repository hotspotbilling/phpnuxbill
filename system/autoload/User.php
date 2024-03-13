<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/


class User
{
    public static function getID()
    {
        global $db_password;
        if (isset($_SESSION['uid']) && !empty($_SESSION['uid'])) {
            return $_SESSION['uid'];
        } else if (isset($_COOKIE['uid'])) {
            // id.time.sha1
            $tmp = explode('.', $_COOKIE['uid']);
            if (sha1($tmp[0] . '.' . $tmp[1] . '.' . $db_password) == $tmp[2]) {
                if (time() - $tmp[1] < 86400 * 30) {
                    $_SESSION['uid'] = $tmp[0];
                    return $tmp[0];
                }
            }
        }
        return 0;
    }

    public static function getAttribute($name, $id = 0)
    {
        if (!$id) {
            $id = User::getID();
        }
        if (!$id) {
            return '';
        }
        $f = ORM::for_table('tbl_customers_fields')->where('field_name', $name)->where('customer_id', $id)->find_one();
        if ($f) {
            return $f['field_value'];
        }
        return '';
    }

    public static function getAttributes($endWith, $id = 0)
    {
        if (!$id) {
            $id = User::getID();
        }
        if (!$id) {
            return [];
        }
        $attrs = [];
        $f = ORM::for_table('tbl_customers_fields')->where_like('field_name', $endWith)->where('customer_id', $id)->find_one();
        if ($f) {
            foreach ($f as $k) {
                $attrs[$k['field_name']] = $k['field_value'];
            }
            return $attrs;
        }
        return [];
    }

    public static function setCookie($uid)
    {
        global $db_password;
        if (isset($uid)) {
            $time = time();
            setcookie('uid', $uid . '.' . $time . '.' . sha1($uid . '.' . $time . '.' . $db_password), time() + 86400 * 30);
        }
    }

    public static function removeCookie()
    {
        if (isset($_COOKIE['uid'])) {
            setcookie('uid', '', time() - 86400);
        }
    }

    public static function _info($id = 0)
    {
        if (!$id) {
            $id = User::getID();
        }
        $d = ORM::for_table('tbl_customers')->find_one($id);

        if (empty($d['username'])) {
            r2(U . 'logout', 'd', '');
        }
        return $d;
    }

    public static function _billing($id = 0)
    {
        if (!$id) {
            $id = User::getID();
        }
        $d = ORM::for_table('tbl_user_recharges')
            ->select('tbl_user_recharges.id', 'id')
            ->select('customer_id')
            ->select('username')
            ->select('plan_id')
            ->select('namebp')
            ->select('recharged_on')
            ->select('recharged_time')
            ->select('expiration')
            ->select('time')
            ->select('status')
            ->select('method')
            ->select('tbl_user_recharges.routers', 'routers')
            ->select('tbl_user_recharges.type', 'type')
            ->select('admin_id')
            ->select('prepaid')
            ->where('customer_id', $id)
            ->join('tbl_plans', array('tbl_plans.id', '=', 'tbl_user_recharges.plan_id'))
            ->find_many();
        return $d;
    }
}
