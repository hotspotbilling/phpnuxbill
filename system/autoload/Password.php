<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

class Password
{

    /**
     * Hash for staff (tbl_users) passwords: bcrypt, salted per password. Customer passwords are NOT hashed with this: they stay in
     * clear text because the hotspot and FreeRADIUS (PAP/CHAP) need the real password to check a login.
     */
    public static function _crypt($password)
    {
        return password_hash((string) $password, PASSWORD_BCRYPT);
    }

    /** Staff password check. Understands bcrypt and the old unsalted SHA-1; use needsRehash() after a good login to upgrade the latter. */
    public static function _verify($user_input, $hashed_password)
    {
        $hashed_password = (string) $hashed_password;
        if (strncmp($hashed_password, '$2y$', 4) === 0) {
            return password_verify((string) $user_input, $hashed_password);
        }
        return hash_equals(strtolower($hashed_password), sha1((string) $user_input));
    }

    /** True when a stored staff hash is the old SHA-1 (or a weaker bcrypt cost) and should be replaced after a successful login. */
    public static function needsRehash($hashed_password)
    {
        $hashed_password = (string) $hashed_password;
        return strncmp($hashed_password, '$2y$', 4) !== 0 || password_needs_rehash($hashed_password, PASSWORD_BCRYPT);
    }

    /** Customer password check (stored in clear text, see _crypt). Exact comparison: "1e3" is not "1000" and "0e1" is not "0e2". */
    public static function _uverify($user_input, $hashed_password)
    {
        return hash_equals((string) $hashed_password, (string) $user_input);
    }

    /**
     * A short fingerprint of a stored password. Sessions and remember-me cookies carry it, so changing or resetting the password
     * ends every session and cookie that was opened with the old one.
     */
    public static function fingerprint($storedPassword)
    {
        global $db_pass;
        return substr(hash_hmac('sha256', (string) $storedPassword, (string) $db_pass), 0, 20);
    }

    /** Simple rules for a new customer password. Returns an error text, or '' when it is fine. */
    public static function policyError($password, $username = '')
    {
        global $config;
        $min = max(4, (int) ($config['min_password_length'] ?? 6));
        $password = (string) $password;
        if (mb_strlen($password) < $min) {
            return str_replace('{n}', (string) $min, Lang::T('Password must be at least {n} characters'));
        }
        if (mb_strlen($password) > 64) {
            return Lang::T('Password is too long');
        }
        if ($username !== '' && strcasecmp($password, (string) $username) === 0) {
            return Lang::T('Password must not be the same as the username');
        }
        if (in_array(strtolower($password), ['password', 'password1', '123456', '1234567', '12345678', '123456789', '1234567890', '111111', '000000', 'qwerty', 'qwerty123', 'abc123', 'iloveyou', 'admin', 'admin123', 'letmein', 'welcome'], true)
            || preg_match('/^(.)\1+$/u', $password)) {
            return Lang::T('That password is too easy to guess');
        }
        return '';
    }

    public static function _gen()
    {
        return self::random(8);
    }

    /** A random password from a cryptographic source, without look-alike characters (0/O, 1/l/I). */
    public static function random($length = 12)
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $out;
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
