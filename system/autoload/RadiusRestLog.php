<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * The log of the FreeRADIUS REST endpoint (radius.php): what could not be done while an accounting packet or a login was answered.
 * One JSON line per event in system/logs/ (a cache clean-up does not touch it), so it works without the FreeRADIUS
 * MySQL tables, without waiting for the database (the very thing that may be failing), and without holding the router's answer:
 * appending a line is a single short write. The file is rotated at 5 MB (the older half is kept as the same name with .1).
 * Shown in Logs > Radius REST.
 *
 * Protection: system/logs/.htaccess denies the folder on Apache. Servers that ignore .htaccess (nginx, for one) need the same rule in
 * their own configuration (deny /system/logs/). As a second layer the file name carries a secret part made from the database
 * password, so the address cannot be guessed where the folder is not denied. It is a name, not an access control: a log that
 * must not be readable by anyone on the web belongs outside the web root.
 */
class RadiusRestLog
{
    const MAX_BYTES = 5242880;

    /**
     * Where the log is written: system/logs/ (denied over HTTP, and not touched when the cache is cleared). Where that folder is
     * not writable the cache folder is used, which does not survive a cache clean-up.
     */
    public static function file(): string
    {
        global $CACHE_PATH, $root_path;
        $logs = rtrim((string) $root_path, '/\\') . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'logs';
        if (is_dir($logs) && is_writable($logs)) {
            return $logs . DIRECTORY_SEPARATOR . 'radius_rest.' . self::secret() . '.log';
        }
        return self::legacyFile();
    }

    /** The primary (hashed) log path, whether or not it is the one written to now: kept in the read/clear inventory */
    private static function hashedFile(): string
    {
        global $root_path;
        return rtrim((string) $root_path, '/\\') . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'radius_rest.' . self::secret() . '.log';
    }

    /** The lock every change to the log (append, rotate, clear) is made under: a file of its own, never rotated */
    private static function lockHandle()
    {
        global $CACHE_PATH, $root_path;
        $logs = rtrim((string) $root_path, '/\\') . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'logs';
        $dir = (is_dir($logs) && is_writable($logs)) ? $logs : rtrim((string) $CACHE_PATH, '/\\');
        $h = @fopen($dir . DIRECTORY_SEPARATOR . 'radius_rest.lock', 'c');
        if (!$h) {
            return null;
        }
        if (!flock($h, LOCK_EX)) {
            fclose($h);
            return null;
        }
        return $h;
    }

    /** The secret part of the file name (see the class note): stable for one installation */
    private static function secret(): string
    {
        global $db_pass, $db_user, $db_name;
        return substr(hash('sha256', 'radius_rest|' . $db_pass . '|' . $db_user . '|' . $db_name), 0, 16);
    }

    /** The name used before the secret part existed: still read (and cleared) so nothing written there is lost */
    private static function plainFile(): string
    {
        global $root_path;
        return rtrim((string) $root_path, '/\\') . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'radius_rest.log';
    }

    /** Where earlier versions kept it: still read (and cleared), so nothing written there is lost */
    private static function legacyFile(): string
    {
        global $CACHE_PATH;
        return rtrim((string) $CACHE_PATH, '/\\') . DIRECTORY_SEPARATOR . 'radius_rest.log';
    }

    /** @return string[] every file that can hold entries, newest first */
    private static function files(): array
    {
        $f = self::file();
        $hashed = self::hashedFile();
        $all = [$f, $f . '.1', $hashed, $hashed . '.1', self::plainFile(), self::plainFile() . '.1', self::legacyFile(), self::legacyFile() . '.1'];
        return array_values(array_unique($all));
    }

    /**
     * $level: 'error' or 'info'. $context: any of username, session, nas, status (what the request carried). Never throws.
     */
    public static function write(string $level, string $message, array $context = []): void
    {
        try {
            $file = self::file();
            $row = ['at' => date('Y-m-d H:i:s'), 'level' => $level === 'info' ? 'info' : 'error', 'message' => mb_substr($message, 0, 1000)];
            foreach (['username', 'session', 'nas', 'status'] as $k) {
                if (isset($context[$k]) && (string) $context[$k] !== '') {
                    $row[$k] = mb_substr((string) $context[$k], 0, 128);
                }
            }
            $line = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            if ($line === false) {
                return;
            }
            $lock = self::lockHandle();
            if (!$lock) {
                return;
            }
            try {
                clearstatcache(true, $file);
                if ((int) @filesize($file) > self::MAX_BYTES) {
                    @rename($file, $file . '.1');
                }
                $fh = @fopen($file, 'ab');
                if (!$fh) {
                    return;
                }
                $before = (int) (fstat($fh)['size'] ?? 0);
                $line .= "\n";
                if (@fwrite($fh, $line) !== strlen($line)) {
                    // a partial record would corrupt the next line: back to what was there (not appended to if that fails)
                    @ftruncate($fh, $before);
                } else {
                    fflush($fh);
                }
                fclose($fh);
            } finally {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        } catch (Throwable $e) {
        }
    }

    /**
     * The newest entries first, at most $limit, the ones that contain $q (any field, no matter the case) and, when given, of $level.
     * @return array<int,array<string,string>>
     */
    public static function read(int $limit = 300, string $q = '', string $level = ''): array
    {
        $rows = [];
        foreach (self::files() as $file) {
            if (!is_file($file) || count($rows) >= $limit) {
                continue;
            }
            $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            for ($i = count($lines) - 1; $i >= 0 && count($rows) < $limit; $i--) {
                $r = json_decode($lines[$i], true);
                if (!is_array($r) || !isset($r['at'], $r['message'])) {
                    continue;
                }
                if ($level !== '' && ($r['level'] ?? '') !== $level) {
                    continue;
                }
                if ($q !== '' && mb_stripos(implode(' ', array_map('strval', $r)), $q, 0, 'UTF-8') === false) {
                    continue;
                }
                $rows[] = $r;
            }
        }
        return $rows;
    }

    /** Empties the log (and its rotated half) */
    public static function clear(): bool
    {
        $ok = true;
        $lock = self::lockHandle();
        foreach (self::files() as $file) {
            if (is_file($file) && !@unlink($file)) {
                $ok = false;
            }
        }
        if ($lock) {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
        return $ok;
    }
}
