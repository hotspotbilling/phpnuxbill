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
        global $config, $isApi;
        if($config['csrf_enabled'] == 'yes' && !$isApi) {
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
        return true;
    }

    /**
     * The token for this session's forms. boot.php asks for it on every request, including the POST that is about to be checked, so
     * it must hand back the same token while it is still valid: making a new one each time (as it used to) meant a submitted token
     * could never match and the CSRF setting broke every form. A token past its life is replaced.
     */
    public static function generateAndStoreToken()
    {
        if (!empty($_SESSION['csrf_token']) && isset($_SESSION['csrf_token_time']) && time() - $_SESSION['csrf_token_time'] <= self::$tokenExpiration) {
            $_SESSION['csrf_token_time'] = time(); // lives on while the visitor is active
            return $_SESSION['csrf_token'];
        }
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
