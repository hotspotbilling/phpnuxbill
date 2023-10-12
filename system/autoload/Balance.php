<?php
/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/
/**
 *  This script is for managing user balance
 **/

class Balance
{

    public static function plus($id_customer, $amount)
    {
        $c = ORM::for_table('tbl_customers')->where('id', $id_customer)->find_one();
        $c->balance = $amount + $c['balance'];
        $c->save();
    }

    public static function transfer($id_customer, $phoneTarget, $amount)
    {
        global $config;
        if (Balance::min($id_customer, $amount)) {
            return Balance::plusByPhone($phoneTarget, $amount);
        } else {
            return false;
        }
    }

    public static function min($id_customer, $amount)
    {
        $c = ORM::for_table('tbl_customers')->where('id', $id_customer)->find_one();
        if ($c && $c['balance'] >= $amount) {
            $c->balance = $c['balance'] - $amount;
            $c->save();
            return true;
        } else {
            return false;
        }
    }

    public static function plusByPhone($phone_customer, $amount)
    {
        $c = ORM::for_table('tbl_customers')->where('username', $phone_customer)->find_one();
        if ($c) {
            $c->balance = $amount + $c['balance'];
            $c->save();
            return true;
        }
        return false;
    }

    public static function minByPhone($phone_customer, $amount)
    {
        $c = ORM::for_table('tbl_customers')->where('username', $phone_customer)->find_one();
        if ($c && $c['balance'] >= $amount) {
            $c->balance = $c['balance'] - $amount;
            $c->save();
            return true;
        } else {
            return false;
        }
    }
}
