<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpnuxbill/)
**/

Class Admin{
    public static function _info(){
        $id = $_SESSION['aid'];
        $d = ORM::for_table('tbl_users')->find_one($id);
        return $d;
    }
}