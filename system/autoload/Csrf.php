<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/


class Csrf {
    public static function generateToken($length = 16) {
        return bin2hex(random_bytes($length));
    }

    public static function validateToken($token, $storedToken) {
        return hash_equals($token, $storedToken);
    }

    public static function check($token) {
        if (isset($_SESSION['csrf_token']) && isset($token)) {
            return self::validateToken($token, $_SESSION['csrf_token']);
        }
        return false;
    }

    public static function generateAndStoreToken() {
        $token = self::generateToken();
        $_SESSION['csrf_token'] = $token;
        return $token;
    }
}
