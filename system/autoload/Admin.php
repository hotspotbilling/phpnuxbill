<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)


* @copyright	Copyright (C) 2014-2015 PHP Mikrotik Billing
* @license		GNU General Public License version 2 or later; see LICENSE.txt

**/

Class Admin{
    public static function _info(){
        $id = $_SESSION['aid'];
        $d = ORM::for_table('tbl_users')->find_one($id);
        return $d;
    }
}