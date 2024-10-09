<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/


class Csrf
{
    private static $tokenExpiration = 1800; // 30 minutes

    public static function generateToken($length = 16)
    {
        return bin2hex(random_bytes($length));
    }

    public static function validateToken($token, $storedToken)
    {
        return hash_equals($token, $storedToken);
    }

    public static function check($token)
    {
        if (isset($_SESSION['csrf_token'], $_SESSION['csrf_token_time'], $token)) {
            $storedToken = $_SESSION['csrf_token'];
            $tokenTime = $_SESSION['csrf_token_time'];

            if (time() - $tokenTime > self::$tokenExpiration) {
                self::clearToken();
                return false;
            }

            return self::validateToken($token, $storedToken);
        }
        return false;
    }

    public static function generateAndStoreToken()
    {
        $token = self::generateToken();
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        return $token;
    }

    public static function clearToken()
    {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
    }
}
