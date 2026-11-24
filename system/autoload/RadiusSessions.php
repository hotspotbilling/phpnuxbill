<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * Closing RADIUS sessions that stopped sending accounting updates (Settings > General > Clear Stale Sessions), for both
 * kinds of RADIUS: REST (table rad_acct, written by radius.php) and MySQL (table radacct, written by FreeRADIUS's sql module).
 * Run by the main cron (cron.php) on every run, and by system/cron/cleanup_stale_sessions.php for a site that wants it
 * more often. One conditional UPDATE per table: the stale test and the change happen together, so a session that sends an
 * update meanwhile is not closed by mistake, and no row is read into PHP.
 */
class RadiusSessions
{
    /**
     * The minutes of inactivity after which a session is stale, or 0 when closing is off. "Clear Stale Sessions" with its
     * timeout; a site that only had the former REST-only "Interim-Update" minutes set keeps those (the setting was merged
     * into this one, and is replaced the next time the settings are saved).
     */
    public static function staleMinutes(array $config): int
    {
        if ((string) ($config['clear_stale_sessions'] ?? '') === '1') {
            // an empty, zero or negative timeout would make every open session look stale and disconnect everyone
            $timeout = (int) ($config['stale_session_timeout'] ?? 10);
            return $timeout < 1 ? 10 : $timeout;
        }
        return max(0, (int) ($config['frrest_interim_update'] ?? 0));
    }

    /**
     * Close the stale sessions in both tables. Returns ['closed' => number, 'failed' => bool, 'lines' => [text]]; never
     * throws. A table that does not exist (REST without radacct, or the other way round) is skipped, not an error.
     */
    public static function closeStale(int $minutes): array
    {
        $out = ['closed' => 0, 'failed' => false, 'lines' => []];
        if ($minutes < 1) {
            return $out;
        }
        $seconds = $minutes * 60;

        // REST sessions. Stale = no accounting update since the cutoff; a session that never sent an Interim-Update only has
        // dateAdded, so the router's interim interval must be shorter than the timeout. radius.php writes these dates with
        // PHP date() in the app timezone, so the cutoff is made the same way (MySQL NOW() could be in another timezone).
        $cutoff = date('Y-m-d H:i:s', time() - $seconds);
        self::close(
            $out,
            'REST',
            'rad_acct',
            null,
            'acctstatustype = ?',
            ['Stop'],
            "(acctstatustype IN (?, ?) OR acctstatustype IS NULL) AND COALESCE(dateUpdated, dateAdded) > ? AND COALESCE(dateUpdated, dateAdded) < ?",
            ['Start', 'Interim-Update', '2000-01-01 00:00:00', $cutoff]
        );

        // MySQL RADIUS sessions, in the separate "radius" database when one is configured. FreeRADIUS fills these dates
        // itself, so the cutoff is computed by MySQL. The stop time is the last update: the session ended when the NAS went quiet.
        $connection = (class_exists('SystemInfo') && SystemInfo::radiusConnectionConfigured()) ? 'radius' : null;
        self::close(
            $out,
            'MySQL',
            'radacct',
            $connection,
            "acctstoptime = acctupdatetime, acctterminatecause = CASE WHEN acctterminatecause = '' THEN ? ELSE acctterminatecause END",
            ['Lost-Carrier'],
            "acctstoptime IS NULL AND acctupdatetime IS NOT NULL AND acctupdatetime < (NOW() - INTERVAL ? SECOND)",
            [$seconds]
        );
        return $out;
    }

    /**
     * Write one REST accounting packet to rad_acct (radius.php, and the replay of queued ones). One statement keyed on the
     * unique (username, acctsessionid): a new session is added, a known one updated, atomically. $p holds the columns of
     * the INSERT below; acctstatustype is null for an unknown type (the stored one is kept). A replayed packet only
     * changes a row it is newer than (its dateUpdated is the time it was received), so it never rolls back a later update.
     * Throws on any failure.
     */
    public static function saveAccounting(array $p, bool $replay = false): void
    {
        $cols = ['acctsessionid', 'username', 'realm', 'nasid', 'nasipaddress', 'nasportid', 'nasporttype', 'framedipaddress',
            'acctsessiontime', 'acctinputoctets', 'acctoutputoctets', 'acctstatustype', 'macaddr', 'dateAdded', 'dateUpdated'];
        $set = [
            'acctinputoctets' => 'VALUES(acctinputoctets)',
            'acctoutputoctets' => 'VALUES(acctoutputoctets)',
            'acctsessiontime' => 'VALUES(acctsessiontime)',
            'framedipaddress' => 'VALUES(framedipaddress)',
            'acctstatustype' => 'COALESCE(VALUES(acctstatustype), acctstatustype)',
            'dateUpdated' => 'VALUES(dateUpdated)', // last: MySQL applies the assignments in order, the test below reads the old value
        ];
        if ($replay) {
            // a session already closed (by its Stop or the stale cleanup) is not made live again by an older queued update
            $set['acctstatustype'] = "IF(acctstatustype = 'Stop', acctstatustype, {$set['acctstatustype']})";
        }
        $parts = [];
        foreach ($set as $col => $expr) {
            $parts[] = $replay
                ? "{$col} = IF(VALUES(dateUpdated) >= COALESCE(dateUpdated, dateAdded), {$expr}, {$col})"
                : "{$col} = {$expr}";
        }
        $values = [];
        foreach ($cols as $col) {
            $values[] = $p[$col] ?? null;
        }
        $stmt = ORM::get_db()->prepare('INSERT INTO rad_acct (' . implode(', ', $cols) . ') VALUES ('
            . implode(', ', array_fill(0, count($cols), '?')) . ') ON DUPLICATE KEY UPDATE ' . implode(', ', $parts));
        // the connection throws on errors (PDO::ERRMODE_EXCEPTION); a false result is treated the same, never as saved
        if (!$stmt || !$stmt->execute($values)) {
            throw new RuntimeException('rad_acct write failed: ' . implode(' ', $stmt ? $stmt->errorInfo() : ORM::get_db()->errorInfo()));
        }
    }

    /** The accounting packets that could not be written, waiting for replayAccounting() (one JSON object per line) */
    public static function queueFile(): string
    {
        global $CACHE_PATH;
        return rtrim((string) $CACHE_PATH, '/\\') . DIRECTORY_SEPARATOR . 'radius_acct_retry.jsonl';
    }

    /**
     * Keep a packet that could not be written, so the router can still be answered and nothing is lost. True when it is
     * on disk. Above 50 MB (a long database outage) only Stop packets are kept: they carry a session's final usage, while
     * a Start or Interim-Update is superseded by the next one. Never throws.
     */
    public static function queueAccounting(array $p): bool
    {
        try {
            $file = self::queueFile();
            if (($p['acctstatustype'] ?? null) !== 'Stop' && @filesize($file) > 50 * 1024 * 1024) {
                return false;
            }
            $line = json_encode($p, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            if ($line === false) {
                return false;
            }
            $line .= "\n";
            return @file_put_contents($file, $line, FILE_APPEND | LOCK_EX) === strlen($line);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Write the queued accounting packets, oldest first. Meant for the cron. A packet that still fails goes back in the
     * queue (with any written meanwhile); a line that is not a packet is dropped. Returns ['replayed' => n, 'failed' => n].
     * Never throws.
     */
    public static function replayAccounting(int $max = 20000): array
    {
        $out = ['replayed' => 0, 'failed' => 0];
        $file = self::queueFile();
        if (!is_file($file) || (int) @filesize($file) === 0) {
            return $out;
        }
        $work = $file . '.replay';
        try {
            // take the queue as a whole under its lock, so radius.php appends to a new one meanwhile
            $fh = @fopen($file, 'c+');
            if (!$fh) {
                return $out;
            }
            if (!flock($fh, LOCK_EX)) {
                fclose($fh);
                return $out;
            }
            // a replay that stopped halfway left its file: its packets come first
            $data = (is_file($work) ? (string) @file_get_contents($work) : '') . stream_get_contents($fh);
            if (@file_put_contents($work, $data, LOCK_EX) !== strlen($data)) {
                flock($fh, LOCK_UN);
                fclose($fh);
                return $out;
            }
            ftruncate($fh, 0);
            flock($fh, LOCK_UN);
            fclose($fh);

            $retry = '';
            foreach (preg_split('/\r?\n/', $data, -1, PREG_SPLIT_NO_EMPTY) as $i => $line) {
                $p = json_decode($line, true);
                if (!is_array($p) || empty($p['username']) || empty($p['acctsessionid'])) {
                    continue;
                }
                if ($i >= $max) {
                    $retry .= $line . "\n";
                    continue;
                }
                try {
                    self::saveAccounting($p, true);
                    $out['replayed']++;
                } catch (Throwable $e) {
                    $out['failed']++;
                    $retry .= $line . "\n";
                }
            }
            if ($retry !== '') {
                // ahead of anything queued while this ran, to keep the order
                $fh = @fopen($file, 'c+');
                if ($fh && flock($fh, LOCK_EX)) {
                    $newer = stream_get_contents($fh);
                    ftruncate($fh, 0);
                    rewind($fh);
                    fwrite($fh, $retry . $newer);
                    fflush($fh);
                    flock($fh, LOCK_UN);
                    fclose($fh);
                } else {
                    return $out; // the work file stays, and is picked up first next time
                }
            }
            @unlink($work);
            if ($out['replayed'] > 0 || $out['failed'] > 0) {
                try {
                    _log("RADIUS accounting queue: {$out['replayed']} packet(s) written, {$out['failed']} still waiting", 'CRON');
                } catch (Throwable $ignored) {
                }
            }
        } catch (Throwable $e) {
        }
        return $out;
    }

    private static function close(array &$out,string $label, string $table, ?string $connection, string $setSql, array $setParams, string $where, array $params): void
    {
        try {
            $sample = self::sample($table, $connection, $where, $params);
            $db = $connection ? ORM::get_db($connection) : ORM::get_db();
            $stmt = $db->prepare("UPDATE {$table} SET {$setSql} WHERE {$where}");
            $stmt->execute(array_merge($setParams, $params));
            $closed = $stmt->rowCount();
            $out['closed'] += $closed;
            if ($closed > 0) {
                $out['lines'][] = "Closed {$closed} stale {$label} session(s)";
                // one summary line per run, not one per session: the first run after an outage can close thousands
                try {
                    _log("Cleaned up {$closed} stale {$label} session(s)" . ($sample !== '' ? " (e.g. {$sample})" : ''), 'CRON');
                } catch (Throwable $ignored) {
                }
            }
        } catch (Throwable $e) {
            if (self::tableMissing($e)) {
                return;
            }
            // connection refused, access denied, bad column...: a real problem
            $out['failed'] = true;
            $out['lines'][] = "Error cleaning up {$table}: " . $e->getMessage();
            try {
                _log("Error cleaning up stale {$label} sessions: " . $e->getMessage(), 'CRON');
            } catch (Throwable $ignored) {
            }
        }
    }

    /** Only "table does not exist" (MySQL 1146 / SQLSTATE 42S02) means the table was never created */
    private static function tableMissing(Throwable $e): bool
    {
        if (!($e instanceof PDOException)) {
            return false;
        }
        return (isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1146) || (string) $e->getCode() === '42S02';
    }

    /** A few usernames of the sessions about to be closed, for the log line only */
    private static function sample(string $table, ?string $connection, string $where, array $params): string
    {
        try {
            $query = $connection ? ORM::for_table($table, $connection) : ORM::for_table($table);
            $rows = $query->select('username')->where_raw($where, $params)->limit(10)->find_array();
            return implode(', ', array_unique(array_column($rows, 'username')));
        } catch (Throwable $e) {
            return '';
        }
    }
}
