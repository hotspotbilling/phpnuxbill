<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
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
        return $config['currency_code'] . ' ' . number_format($var, 0, $config['dec_point'], $config['thousands_sep']);
    }

    public static function phoneFormat($phone)
    {
        global $config;
        if (Validator::UnsignedNumber($phone) && !empty($config['country_code_phone'])) {
            return preg_replace('/^0/',  $config['country_code_phone'], $phone);
        } else {
            return $phone;
        }
    }

    public static function dateFormat($date)
    {
        global $config;
        return date($config['date_format'], strtotime($date));
    }

    public static function dateTimeFormat($date)
    {
        global $config;
        if (strtotime($date) < strtotime("2000-01-01 00:00:00")) {
            return "";
        } else {
            return date($config['date_format'] . ' H:i', strtotime($date));
        }
    }

    public static function dateAndTimeFormat($date, $time)
    {
        global $config;
        return date($config['date_format'] . ' H:i', strtotime("$date $time"));
    }

    public static function nl2br($text)
    {
        return nl2br($text);
    }

    public static function arrayCount($arr)
    {
        if (is_array($arr)) {
            return count($arr);
        } else if (is_object($arr)) {
            return count($arr);
        } else {
            return 0;
        }
    }

    public static function getNotifText($key)
    {
        global $_notifmsg, $_notifmsg_default;
        if (isset($_notifmsg[$key])) {
            return $_notifmsg[$key];
        } else {
            return $_notifmsg_default[$key];
        }
    }

    public static function ucWords($text)
    {
        return ucwords(str_replace('_', ' ', $text));
    }

    public static function randomUpLowCase($text){
        $jml = strlen($text);
        $result = '';
        for($i = 0; $i < $jml;$i++){
            if(rand(0,99)%2){
                $result .= strtolower(substr($text,$i,1));
            }else{
                $result .= substr($text,$i,1);
            }
        }
        return $result;
    }

    /**
     * $pad_type
     * 0 Left
     * 1 right
     * 2 center
     * */
    public static function pad($text, $pad_string = ' ', $pad_type = 0){
        global $config;
        $cols = 37;
        if($config['printer_cols']){
            $cols = $config['printer_cols'];
        }
        return str_pad($text, $cols, $pad_string, $pad_type);
    }

    public static function pads($textLeft, $textRight, $pad_string = ' '){
        global $config;
        $cols = 37;
        if($config['printer_cols']){
            $cols = $config['printer_cols'];
        }
        return $textLeft.str_pad($textRight, $cols-strlen($textLeft), $pad_string, 0);
    }
}
