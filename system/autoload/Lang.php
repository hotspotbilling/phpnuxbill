<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 **/

class Lang
{
    public static function T($var)
    {
        return Lang($var);
    }

    public static function htmlspecialchars($var)
    {
        return htmlspecialchars($var);
    }

    public static function moneyFormat($var)
    {
        global $config;
        return $config['currency_code'] . ' ' .number_format($var, 0, $config['dec_point'], $config['thousands_sep']);
    }

    public static function phoneFormat($phone)
    {
        global $config;
        if(Validator::UnsignedNumber($phone) && !empty($config['country_code_phone'])){
            return preg_replace('/^0/',  $config['country_code_phone'], $phone);
        }else{
            return $phone;
        }
    }

    public static function dateFormat($date){
        global $config;
        return date($config['date_format'], strtotime($date));
    }

    public static function dateTimeFormat($date){
        global $config;
        return date($config['date_format']. ' H:i', strtotime($date));
    }

    public static function nl2br($text){
        return nl2br($text);
    }

    public static function arrayCount($arr){
        return count($arr);
    }

    public static function getNotifText($key){
        global $_notifmsg, $_notifmsg_default;
        if(isset($_notifmsg[$key])){
            return $_notifmsg[$key];
        }else{
            return $_notifmsg_default[$key];
        }
    }
}
