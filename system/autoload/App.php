<?php
/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/


class App{
    public static function _run(){
        return true;
    }

    public static function getToken(){
        return md5(microtime());
    }

    public static function setToken($token, $value){
        $_SESSION[$token] = $value;
    }

    public static function getTokenValue($key){
        if(empty($key)){
            return "";
        }
        if(isset($_SESSION[$key])){
            return $_SESSION[$key];
        }else{
            return "";
        }
    }

}