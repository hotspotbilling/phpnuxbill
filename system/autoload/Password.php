<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

class Password
{

    public static function _crypt($password)
    {
        return sha1($password);
    }

    public static function _verify($user_input, $hashed_password)
    {
        if (sha1($user_input) == $hashed_password) {
            return true;
        }
        return false;
    }
    public static function _uverify($user_input, $hashed_password)
    {
        if ($user_input == $hashed_password) {
            return true;
        }
        return false;
    }
    public static function _gen()
    {
        $pass = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz@#!123456789', 8)), 0, 8);
        return $pass;
    }

    /**
     * Check a CHAP response (id + md5(id . password . challenge)) against a known password.
     * CHAPassword and CHAPChallenge come from FreeRADIUS as "0x" + hex. True only when they match.
     * @param string $realPassword
     * @param string $CHAPassword
     * @param string $CHAPChallenge
     * @return bool
     */
    public static function chap_verify($realPassword, $CHAPassword, $CHAPChallenge)
    {
        $strip = function ($v) {
            $v = (string) $v;
            return strncasecmp($v, '0x', 2) === 0 ? substr($v, 2) : $v;
        };
        $response = $strip($CHAPassword);
        $challenge = $strip($CHAPChallenge);
        if (strlen($response) !== 34 || !ctype_xdigit($response) || $challenge === '' || strlen($challenge) % 2 !== 0 || !ctype_xdigit($challenge)) {
            return false;
        }
        $chapid = substr($response, 0, 2);
        $expected = $chapid . md5(hex2bin($chapid) . $realPassword . hex2bin($challenge));
        return hash_equals(strtolower($expected), strtolower($response));
    }
}
