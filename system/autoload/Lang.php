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
}
