<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)


* @copyright	Copyright (C) 2014-2015 PHP Mikrotik Billing
* @license		GNU General Public License version 2 or later; see LICENSE.txt

**/

Class Password{

    public static function _crypt($password) {
        return crypt($password);
    }

    public static function _verify($user_input, $hashed_password){
        if (crypt($user_input, $hashed_password) == $hashed_password) {
            return true;
        }
        return false;
    }
    public static function _uverify($user_input, $hashed_password){
        if ($user_input == $hashed_password) {
            return true;
        }
        return false;
    }
    public static function _gen(){
        $pass = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz@#!123456789', 8)), 0, 8);
        return $pass;
    }

}