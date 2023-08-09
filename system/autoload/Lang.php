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
}
