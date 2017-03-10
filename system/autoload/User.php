<?php
/**
* PHP Mikrotik Billing (www.phpmixbill.com)
* Ismail Marzuqi <iesien22@yahoo.com>
* @version		5.0
* @copyright	Copyright (C) 2014-2015 PHP Mikrotik Billing
* @license		GNU General Public License version 2 or later; see LICENSE.txt
* @donate		PayPal: iesien22@yahoo.com / Bank Mandiri: 130.00.1024957.4
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