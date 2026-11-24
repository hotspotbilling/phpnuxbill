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
        $token = md5(uniqid('', true) . random_bytes(8));
        if (!isset($_SESSION['app_tokens'])) {
            $_SESSION['app_tokens'] = [];
        }
        // prune tokens older than 1 hour
        $now = time();
        foreach ($_SESSION['app_tokens'] as $t => $ts) {
            if ($now - $ts > 3600) {
                unset($_SESSION['app_tokens'][$t]);
            }
        }
        // keep the list bounded
        if (count($_SESSION['app_tokens']) > 100) {
            $_SESSION['app_tokens'] = array_slice($_SESSION['app_tokens'], -80, null, true);
        }
        $_SESSION['app_tokens'][$token] = $now;
        return $token;
    }

    /**
     * Validate that a token was actually issued by getToken() and not yet consumed.
     * setToken($token, ...) marks a token as consumed, so replayed links are rejected.
     */
    public static function checkToken($token){
        if (empty($token) || !isset($_SESSION['app_tokens'][$token])) {
            return false;
        }
        return empty($_SESSION[$token]);
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

    public static function getVoucher(){
        return md5(microtime());
    }

    public static function setVoucher($token, $value){
        $_SESSION[$token] = $value;
    }

    /** Takes back a setVoucher() mark (the action it guarded failed), so the same link can be used again */
    public static function clearVoucher($token){
        if (!empty($token)) {
            unset($_SESSION[$token]);
        }
    }

    public static function getVoucherValue($key){
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
