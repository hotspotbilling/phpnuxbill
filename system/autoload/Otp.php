<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * One-time codes (registration, password reset). The code comes from random_int(), only a keyed hash of it is written to disk, it
 * lives for a limited time, and it dies after a few wrong guesses, so it cannot be brute-forced by trying a million numbers.
 *
 *   $code = Otp::issue('forgot', $username);                       // send $code to the owner
 *   $r = Otp::check('forgot', $username, $typedCode);             // 'ok' | 'bad' | 'expired' | 'locked' | 'none'
 *   Otp::age('forgot', $username);                                 // seconds since it was issued, -1 when there is none
 */
class Otp
{
    public const TTL = 600;
    public const MAX_TRIES = 5;

    private static function file(string $ns, string $subject): string
    {
        global $db_pass;
        return Extensions::privateDir('otp') . DIRECTORY_SEPARATOR . hash_hmac('sha256', $ns . '|' . strtolower($subject), (string) $db_pass) . '.json';
    }

    private static function hash(string $ns, string $subject, string $code): string
    {
        global $db_pass;
        return hash_hmac('sha256', $ns . '|' . strtolower($subject) . '|' . $code, (string) $db_pass);
    }

    private static function load(string $file): ?array
    {
        $j = is_file($file) ? json_decode((string) @file_get_contents($file), true) : null;
        return is_array($j) && isset($j['h'], $j['t']) ? $j : null;
    }

    /** Make a fresh 6-digit code (replacing any earlier one) and return it. */
    public static function issue(string $ns, string $subject): string
    {
        $code = (string) random_int(100000, 999999);
        @file_put_contents(self::file($ns, $subject), json_encode(['h' => self::hash($ns, $subject, $code), 't' => time(), 'n' => 0]), LOCK_EX);
        return $code;
    }

    public static function age(string $ns, string $subject): int
    {
        $j = self::load(self::file($ns, $subject));
        return $j ? time() - (int) $j['t'] : -1;
    }

    public static function forget(string $ns, string $subject): void
    {
        @unlink(self::file($ns, $subject));
    }

    /** Check a typed code. A right code is used up; every wrong one counts, and the code is destroyed after MAX_TRIES. */
    public static function check(string $ns, string $subject, string $typed): string
    {
        $file = self::file($ns, $subject);
        $fp = @fopen($file, 'c+');
        if (!$fp) {
            return 'none';
        }
        flock($fp, LOCK_EX);
        $j = self::load($file);
        $result = 'none';
        if ($j) {
            if (time() - (int) $j['t'] > self::TTL) {
                $result = 'expired';
                $drop = true;
            } elseif ((int) $j['n'] >= self::MAX_TRIES) {
                $result = 'locked';
                $drop = true;
            } elseif (hash_equals((string) $j['h'], self::hash($ns, $subject, trim($typed)))) {
                $result = 'ok';
                $drop = true;
            } else {
                $j['n'] = (int) $j['n'] + 1;
                $drop = $j['n'] >= self::MAX_TRIES;
                $result = $drop ? 'locked' : 'bad';
                if (!$drop) {
                    ftruncate($fp, 0);
                    rewind($fp);
                    fwrite($fp, json_encode($j));
                    fflush($fp);
                }
            }
            if (!empty($drop)) {
                ftruncate($fp, 0);
            }
        }
        flock($fp, LOCK_UN);
        fclose($fp);
        if ($result !== 'bad') {
            @unlink($file);
        }
        return $result;
    }

    /** Remove codes older than a day. */
    public static function gc(): void
    {
        foreach (glob(Extensions::privateDir('otp') . DIRECTORY_SEPARATOR . '*.json') ?: [] as $f) {
            if (time() - (int) @filemtime($f) > 86400) {
                @unlink($f);
            }
        }
    }
}
