<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 **/

class Lang {
    public static function T($var) {
        return Lang($var);
    }

    public static function htmlspecialchars($var) {
        return htmlspecialchars($var);
    }
}
