<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)


* @copyright	Copyright (C) 2014-2015 PHP Mikrotik Billing
* @license		GNU General Public License version 2 or later; see LICENSE.txt

**/

Class Router{
    public static function _info($name){
		$d = ORM::for_table('tbl_routers')->where('name',$name)->find_one();
        return $d;
    }
}