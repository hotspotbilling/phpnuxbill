<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpnuxbill/)
**/

Class User{
    public static function _info(){
        $id = $_SESSION['uid'];
        $d = ORM::for_table('tbl_customers')->find_one($id);
        return $d;
    }
    public static function _billing(){
        $id = $_SESSION['uid'];
        $d = ORM::for_table('tbl_user_recharges')->where('customer_id',$id)->find_one();
        return $d;
    }
}