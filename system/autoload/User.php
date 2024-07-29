<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/


class User
{
    public static function getID()
    {
        global $db_pass;
        if (isset($_SESSION['uid']) && !empty($_SESSION['uid'])) {
            return $_SESSION['uid'];
        } else if (isset($_COOKIE['uid'])) {
            // id.time.sha1
            $tmp = explode('.', $_COOKIE['uid']);
            if (sha1($tmp[0] . '.' . $tmp[1] . '.' . $db_pass) == $tmp[2]) {
                if (time() - $tmp[1] < 86400 * 30) {
                    $_SESSION['uid'] = $tmp[0];
                    return $tmp[0];
                }
            }
        }
        return 0;
    }

    public static function getBills($id = 0)
    {
        if (!$id) {
            $id = User::getID();
            if (!$id) {
                return [];
            }
        }
        $addcost = 0;
        $bills = [];
        $attrs = User::getAttributes('Bill', $id);
        foreach ($attrs as $k => $v) {
            // if has : then its an installment
            if (strpos($v, ":") === false) {
                // Not installment
                $bills[$k] = $v;
                $addcost += $v;
            } else {
                // installment
                list($cost, $rem) = explode(":", $v);
                // :0 installment is done
                if (!empty($rem)) {
                    $bills[$k] = $cost;
                    $addcost += $cost;
                }
            }
        }
        return [$bills, $addcost];
    }

    public static function getBillNames($id = 0)
    {
        if (!$id) {
            $id = User::getID();
            if (!$id) {
                return [];
            }
        }
        $bills = [];
        $attrs = User::getAttributes('Bill', $id);
        foreach ($attrs as $k => $v) {
            $bills[] = str_replace(' Bill', '', $k);
        }
        return $bills;
    }

    public static function billsPaid($bills, $id = 0)
    {
        if (!$id) {
            $id = User::getID();
            if (!$id) {
                return [];
            }
        }
        foreach ($bills as $k => $v) {
            // if has : then its an installment
            $v = User::getAttribute($k, $id);
            if (strpos($v, ":") === false) {
                // Not installment, no need decrement
            } else {
                // installment
                list($cost, $rem) = explode(":", $v);
                // :0 installment is done
                if ($rem != 0) {
                    User::setAttribute($k, "$cost:" . ($rem - 1), $id);
                }
            }
        }
    }

    public static function setAttribute($name, $value, $id = 0)
    {
        if (!$id) {
            $id = User::getID();
            if (!$id) {
                return '';
            }
        }
        $f = ORM::for_table('tbl_customers_fields')->where('field_name', $name)->where('customer_id', $id)->find_one();
        if (!$f) {
            $f = ORM::for_table('tbl_customers_fields')->create();
            $f->customer_id = $id;
            $f->field_name = $name;
            $f->field_value = $value;
            $f->save();
            $result = $f->id();
            if ($result) {
                return $result;
            }
        } else {
            $f->field_value = $value;
            $f->save();
            return $f['id'];
        }
        return 0;
    }

    public static function getAttribute($name, $id = 0)
    {
        if (!$id) {
            $id = User::getID();
            if (!$id) {
                return [];
            }
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
            if (!$id) {
                return [];
            }
        }
        $attrs = [];
        $f = ORM::for_table('tbl_customers_fields')->where_like('field_name', "%$endWith")->where('customer_id', $id)->find_many();
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
        global $db_pass;
        if (isset($uid)) {
            $time = time();
            setcookie('uid', $uid . '.' . $time . '.' . sha1($uid . '.' . $time . '.' . $db_pass), time() + 86400 * 30);
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
        global $config;
        if ($config['maintenance_mode'] == true) {
            if ($config['maintenance_mode_logout'] == true) {
                r2(U . 'logout', 'd', '');
            } else {
                displayMaintenanceMessage();
            }
        }
        if (!$id) {
            $id = User::getID();
        }
        $d = ORM::for_table('tbl_customers')->find_one($id);
        if ($d['status'] == 'Banned') {
            _alert(Lang::T('This account status') . ' : ' . Lang::T($d['status']), 'danger', "logout");
        }
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
            ->selects([
                'customer_id', 'username', 'plan_id', 'namebp', 'recharged_on', 'recharged_time', 'expiration', 'time',
                'status', 'method', 'plan_type',
                ['tbl_user_recharges.routers', 'routers'],
                ['tbl_user_recharges.type', 'type'],
                'admin_id', 'prepaid'
            ])
            ->where('customer_id', $id)
            ->left_outer_join('tbl_plans', array('tbl_plans.id', '=', 'tbl_user_recharges.plan_id'))
            ->find_many();
        return $d;
    }
}
