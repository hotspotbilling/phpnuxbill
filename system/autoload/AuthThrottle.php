<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * Server-side attempt limiter for sign-in, one-time codes and similar guessable inputs.
 *
 * The count lives in a small file under system/cache/throttle/ (blocked over HTTP), not in the visitor's session, so a script that
 * throws its cookies away on every request does not get a fresh allowance. Each bucket has a name ("login-user"), a key
 * (the username, IP address, ...) and a time window; a hit is remembered for that window.
 *
 *   if ($wait = AuthThrottle::blocked('login-ip', $ip, 30, 900)) { ...refuse, tell them to try again in $wait seconds... }
 *   AuthThrottle::hit('login-ip', $ip, 900);      // after a failure
 *   AuthThrottle::clear('login-user', $name);     // after a success
 */
class AuthThrottle
{
    private static function dir(): string
    {
        return Extensions::privateDir('throttle');
    }

    private static function file(string $bucket, string $key): string
    {
        global $db_pass;
        return self::dir() . DIRECTORY_SEPARATOR . hash_hmac('sha256', $bucket . '|' . strtolower($key), (string) $db_pass) . '.json';
    }

    /** Timestamps of the hits still inside the window. */
    private static function read(string $file, int $window): array
    {
        $raw = is_file($file) ? @file_get_contents($file) : '';
        $list = json_decode((string) $raw, true);
        if (!is_array($list)) {
            return [];
        }
        $from = time() - $window;
        return array_values(array_filter($list, fn($t) => is_int($t) && $t > $from));
    }

    /** Seconds until another attempt is allowed, or 0 when under the limit. */
    public static function blocked(string $bucket, string $key, int $max, int $window): int
    {
        $list = self::read(self::file($bucket, $key), $window);
        if (count($list) < $max) {
            return 0;
        }
        sort($list);
        return max(1, $list[count($list) - $max] + $window - time());
    }

    /** Remember one attempt. Returns how many are now counted. */
    public static function hit(string $bucket, string $key, int $window): int
    {
        $file = self::file($bucket, $key);
        $fp = @fopen($file, 'c+');
        if (!$fp) {
            return 0;
        }
        flock($fp, LOCK_EX);
        $list = self::read($file, $window);
        $list[] = time();
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($list));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        if (random_int(1, 200) === 1) {
            self::gc();
        }
        return count($list);
    }

    public static function clear(string $bucket, string $key): void
    {
        @unlink(self::file($bucket, $key));
    }

    /** Remove counters nobody has touched for a day. */
    public static function gc(): void
    {
        foreach (glob(self::dir() . DIRECTORY_SEPARATOR . '*.json') ?: [] as $f) {
            if (time() - (int) @filemtime($f) > 86400) {
                @unlink($f);
            }
        }
    }

    /** "5 minutes" / "40 seconds", for the message shown to the visitor. */
    public static function waitText(int $seconds): string
    {
        return $seconds >= 90 ? ceil($seconds / 60) . ' ' . Lang::T('minutes') : $seconds . ' ' . Lang::T('seconds');
    }

    // ----------------------------------------------------------------- the visitor's address

    /**
     * The address a visitor really comes from. Forwarding headers are believed only when the connection itself comes from a proxy the
     * administrator listed in the setting "trusted_proxies" (addresses or CIDR ranges, comma separated; behind nginx on the same
     * machine that is 127.0.0.1, behind Cloudflare also its ranges). Private and loopback addresses are NOT trusted on their own: on a
     * hotspot the visitors themselves are on private addresses and could write anything into a header to dodge every limit.
     * Without the setting every visitor behind a reverse proxy looks like the proxy, so set it there.
     *
     * Order: the X-Forwarded-For chain from the nearest hop outwards (the first address that is not one of our own proxies), then
     * CF-Connecting-IP, then X-Real-IP, and when none gives an address the connection's own.
     */
    public static function clientIp(): string
    {
        $peer = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        if (!filter_var($peer, FILTER_VALIDATE_IP)) {
            return '0.0.0.0';
        }
        if (!self::trusted($peer)) {
            return $peer;
        }
        $chain = array_reverse(array_map('trim', explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''))));
        foreach ($chain as $ip) { // from the nearest hop outwards: the first one that is not one of our own proxies
            if (filter_var($ip, FILTER_VALIDATE_IP) && !self::trusted($ip)) {
                return $ip;
            }
        }
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP'] as $h) { // set by the listed proxy itself
            $v = trim((string) ($_SERVER[$h] ?? ''));
            if ($v !== '' && filter_var($v, FILTER_VALIDATE_IP)) {
                return $v;
            }
        }
        return $peer;
    }

    private static function trusted(string $ip): bool
    {
        global $config;
        foreach (preg_split('/[\s,]+/', (string) ($config['trusted_proxies'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) as $rule) {
            if (self::inRange($ip, $rule)) {
                return true;
            }
        }
        return false;
    }

    private static function inRange(string $ip, string $rule): bool
    {
        [$net, $bits] = array_pad(explode('/', $rule, 2), 2, null);
        $a = @inet_pton($ip);
        $b = @inet_pton((string) $net);
        if ($a === false || $b === false || strlen($a) !== strlen($b)) {
            return false;
        }
        $bits = $bits === null ? strlen($a) * 8 : max(0, min(strlen($a) * 8, (int) $bits));
        $bytes = intdiv($bits, 8);
        if (substr($a, 0, $bytes) !== substr($b, 0, $bytes)) {
            return false;
        }
        $rest = $bits % 8;
        if ($rest === 0) {
            return true;
        }
        $mask = (0xFF << (8 - $rest)) & 0xFF;
        return (ord($a[$bytes]) & $mask) === (ord($b[$bytes]) & $mask);
    }
}
