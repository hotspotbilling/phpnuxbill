<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * Closing RADIUS sessions that stopped sending accounting updates (Settings > General > Clear Stale Sessions), for both
 * kinds of RADIUS: REST (table rad_acct, written by radius.php) and MySQL (table radacct, written by FreeRADIUS's sql module).
 * Run by the main cron (cron.php) on every run, and, optionally, by system/cron/cleanup_stale_sessions.php for a site whose main cron runs less often than every 5 minutes
 * more often. One conditional UPDATE per table: the stale test and the change happen together, so a session that sends an
 * update meanwhile is not closed by mistake, and no row is read into PHP.
 */
class RadiusSessions
{
    /** Longest stale timeout in minutes (7 days): keeps the cutoff arithmetic inside valid date ranges */
    const MAX_STALE_MINUTES = 10080;

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
            return $timeout < 1 ? 10 : min(self::MAX_STALE_MINUTES, $timeout);
        }
        return min(self::MAX_STALE_MINUTES, max(0, (int) ($config['frrest_interim_update'] ?? 0)));
    }

    /**
     * Close the stale sessions: rad_acct always, radacct only with Clear Stale Sessions on (see $includeMysql). Returns ['closed' => number, 'failed' => bool, 'lines' => [text]]; never
     * throws. A table that does not exist (REST without radacct, or the other way round) is skipped, not an error.
     */
    public static function closeStale(int $minutes, ?bool $includeMysql = null): array
    {
        global $config;
        // the MySQL table is only touched when "Clear Stale Sessions" was explicitly set to Yes: the former REST-only
        // Interim-Update minutes (frrest_interim_update) apply to rad_acct alone
        $includeMysql ??= ((string) ($config['clear_stale_sessions'] ?? '') === '1');
        $out = ['closed' => 0, 'failed' => false, 'lines' => []];
        if ($minutes < 1) {
            return $out;
        }
        $seconds = $minutes * 60;

        // REST sessions. Stale = no accounting update since the cutoff; a session that never sent an Interim-Update only has
        // dateAdded, so the router's interim interval must be shorter than the timeout. radius.php writes these dates with
        // PHP date() in the app timezone, so the cutoff is made the same way (MySQL NOW() could be in another timezone).
        $cutoff = date('Y-m-d H:i:s', time() - $seconds);
        // updates still queued (the database was busy and a replay has not written them): an older row does not show that a
        // session is gone, so the REST sessions are left as they are until the queue is written
        $queued = self::hasQueued();
        if ($queued) {
            $out['lines'][] = 'REST: accounting updates are still queued; stale sessions are not closed until they are written';
        }
        $queued || self::close(
            $out,
            'REST',
            'rad_acct',
            null,
            'acctstatustype = ?',
            ['Stop'],
            // a row with receivedAt (epoch microseconds, set by radius.php) is judged by it, not by the date text; an older row by its dates
            "(acctstatustype IN (?, ?) OR acctstatustype IS NULL) AND IF(receivedAt IS NOT NULL AND receivedAt > ?, receivedAt < ?, COALESCE(dateUpdated, dateAdded) > ? AND COALESCE(dateUpdated, dateAdded) < ?)",
            ['Start', 'Interim-Update', 946684800000000, (time() - $seconds) * 1000000, '2000-01-01 00:00:00', $cutoff]
        );

        // MySQL RADIUS sessions, in the separate "radius" database when one is configured. FreeRADIUS fills these dates
        // itself, so the cutoff is computed by MySQL. The stop time is the last update: the session ended when the NAS went quiet.
        if (!$includeMysql) {
            return $out;
        }
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
     * unique (username, acctsessionid, nasipaddress): Acct-Session-Id is only unique per NAS, so two routers can send the
     * same pair. A new session is added, a known one updated, atomically. $p holds the columns of the INSERT below;
     * acctstatustype is null for an unknown type (the stored one is kept). A replayed packet only changes a row it is newer
     * than: "receivedAt" (microseconds since the epoch, taken when radius.php received the packet) orders packets that
     * arrive within one second, which dateUpdated cannot; a row from before that column falls back to dateUpdated.
     * dateAdded only ever moves earlier, so a queued Start replayed after an Interim restores the real start time without
     * touching the newer counters or status. Throws on any failure.
     */
    public static function saveAccounting(array $p, bool $replay = false): void
    {
        $cols = ['acctsessionid', 'username', 'realm', 'nasid', 'nasipaddress', 'nasportid', 'nasporttype', 'framedipaddress',
            'acctsessiontime', 'acctinputoctets', 'acctoutputoctets', 'acctstatustype', 'macaddr', 'dateAdded', 'dateUpdated', 'receivedAt'];
        if (empty($p['receivedAt'])) {
            // a packet queued before the column existed: its date is the best order there is
            $p['receivedAt'] = (int) strtotime((string) ($p['dateUpdated'] ?? '')) * 1000000;
        }
        $p['receivedAt'] = (int) $p['receivedAt'];
        $set = [
            'acctinputoctets' => 'VALUES(acctinputoctets)',
            'acctoutputoctets' => 'VALUES(acctoutputoctets)',
            'acctsessiontime' => 'VALUES(acctsessiontime)',
            'framedipaddress' => 'VALUES(framedipaddress)',
            'acctstatustype' => 'COALESCE(VALUES(acctstatustype), acctstatustype)',
            'dateUpdated' => 'VALUES(dateUpdated)', // after the others: MySQL applies the assignments in order, the test reads the old values
        ];
        if ($replay) {
            // the final counters of a session the NAS has stopped are not replaced by a delayed Interim-Update (only by a Stop)
            foreach (['acctinputoctets', 'acctoutputoctets', 'acctsessiontime'] as $cnt) {
                $set[$cnt] = "IF(acctstatustype = 'Stop' AND COALESCE(VALUES(acctstatustype), '') <> 'Stop', {$cnt}, {$set[$cnt]})";
            }
            // a session already closed (by its Stop or the stale cleanup) is not made live again by an older queued update
            $set['acctstatustype'] = "IF(acctstatustype = 'Stop', acctstatustype, {$set['acctstatustype']})";
        }
        $newer = 'IF(receivedAt IS NOT NULL, VALUES(receivedAt) >= receivedAt, VALUES(dateUpdated) >= COALESCE(dateUpdated, dateAdded))';
        // Live and replayed packets alike only change a row they are newer than (by receivedAt): a Start retried by the NAS or an
        // older packet that arrives late never turns the counters back
        $parts = [];
        foreach ($set as $col => $expr) {
            $parts[] = "{$col} = IF({$newer}, {$expr}, {$col})";
        }
        $parts[] = "receivedAt = IF({$newer}, VALUES(receivedAt), receivedAt)";
        // last: the tests above compare with the row as it was, and the legacy test (a row with no receivedAt) reads dateAdded; the
        // earlier start is restored independently of which packet owns the counters
        $parts[] = 'dateAdded = LEAST(dateAdded, VALUES(dateAdded))';
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

    /** True while accounting packets wait in the retry queue (or its snapshots) */
    public static function hasQueued(): bool
    {
        clearstatcache(true);
        $file = self::queueFile();
        return (int) @filesize($file) > 0 || self::snapshots($file) !== [];
    }

    /**
     * The accounting packets that could not be written, waiting for replayAccounting() (one JSON object per line). It lives
     * in system/cache, which system/cache/.htaccess denies to the web (and sits outside any public directory a deployment
     * that maps only the repository root does not serve: keep that folder denied in other web servers too).
     */
    public static function queueFile(): string
    {
        global $CACHE_PATH;
        return rtrim((string) $CACHE_PATH, '/\\') . DIRECTORY_SEPARATOR . 'radius_acct_retry.jsonl';
    }

    /**
     * Keep a packet that could not be written, so the router can still be answered and nothing is lost. True when it is
     * on disk, complete. Above 50 MB (a long database outage) only Stop packets are kept: they carry a session's final usage,
     * while a Start or Interim-Update is superseded by the next one. Never throws.
     */
    public static function queueAccounting(array $p): bool
    {
        $lock = null;
        try {
            $file = self::queueFile();
            $line = json_encode($p, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            if ($line === false) {
                return false;
            }
            $line .= "\n";
            // the lock file is separate from the queue: the replay swaps files under it, and an append must never go to a
            // file that was just replaced
            $lock = @fopen($file . '.lock', 'c');
            if (!$lock || !flock($lock, LOCK_EX)) {
                return false;
            }
            clearstatcache(true, $file);
            // the size of the spool as a whole: the file being appended to and the snapshots still waiting for the replay
            $spool = (int) @filesize($file);
            foreach (self::snapshots($file) as $snap) {
                $spool += (int) @filesize($snap);
            }
            if (($p['acctstatustype'] ?? null) !== 'Stop' && $spool > 50 * 1024 * 1024) {
                return false;
            }
            $fh = @fopen($file, 'ab');
            if (!$fh) {
                return false;
            }
            $before = (int) (fstat($fh)['size'] ?? 0);
            $written = @fwrite($fh, $line);
            // flushed to the disk, not just to the system's cache: a Stop answered as queued must survive a crash or power loss
            $ok = $written === strlen($line) && fflush($fh) && (!function_exists('fsync') || @fsync($fh));
            if (!$ok) {
                // an incomplete line would corrupt the next one: back to what was there
                @ftruncate($fh, $before);
            }
            fclose($fh);
            return $ok;
        } catch (Throwable $e) {
            return false;
        } finally {
            if (is_resource($lock)) {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
    }

    /**
     * Write the queued accounting packets, oldest first, at most $max per run. Meant for the cron. What radius.php queued is
     * moved, by one rename under the queue's lock, into a snapshot file of its own (never appended to an older one, so an
     * interrupted handoff leaves every accepted record in an intact file); the snapshots are replayed oldest first, each read
     * line by line (never loaded whole). What is not processed stays on disk, in the same order, in its own snapshot for the next
     * run: packets beyond the bound or after a run of failures (streamed on, their sessions not held back), packets that failed
     * (retried with a growing delay, and the later packets of the same session wait behind them, while other sessions go on),
     * and packets queued meanwhile. A line that is not a packet is dropped. Returns ['replayed' => n, 'failed' => n]. Never
     * throws; a second run at the same time returns at once.
     */
    public static function replayAccounting(int $max = 20000): array
    {
        $out = ['replayed' => 0, 'failed' => 0];
        $file = self::queueFile();
        clearstatcache(true);
        if ((int) @filesize($file) === 0 && !self::snapshots($file)) {
            return $out;
        }
        $run = null;
        try {
            $run = @fopen($file . '.run', 'c');
            if (!$run || !flock($run, LOCK_EX | LOCK_NB)) {
                return $out; // another run is working on the snapshots
            }
            if (!self::takeIncoming($file)) {
                return $out;
            }
            $state = ['blocked' => [], 'tried' => 0, 'streak' => 0, 'now' => time(), 'max' => $max, 'stop' => false];
            foreach (self::snapshots($file) as $snap) {
                self::replaySnapshot($snap, $state, $out);
                if ($state['stop']) {
                    break;
                }
            }
            if ($out['replayed'] > 0 || $out['failed'] > 0) {
                try {
                    _log("RADIUS accounting queue: {$out['replayed']} packet(s) written, {$out['failed']} still waiting", 'CRON');
                } catch (Throwable $ignored) {
                }
            }
        } catch (Throwable $e) {
        } finally {
            if (is_resource($run)) {
                flock($run, LOCK_UN);
                fclose($run);
            }
        }
        return $out;
    }

    /** A database error that repeating cannot fix: the data itself is refused (SQLSTATE class 22, out of range / too long / bad value) */
    private static function permanentFailure(Throwable $e): bool
    {
        if (!($e instanceof PDOException)) {
            return false;
        }
        $state = (string) ($e->errorInfo[0] ?? $e->getCode());
        $code = (int) ($e->errorInfo[1] ?? 0);
        return strpos($state, '22') === 0 || in_array($code, [1264, 1265, 1292, 1366, 1406], true);
    }

    /** Appends a packet line to the quarantine file (system/cache, web-denied) for review; true when written and flushed */
    private static function quarantine(string $line): bool
    {
        $fh = @fopen(self::queueFile() . '.quarantine', 'ab');
        if (!$fh) {
            return false;
        }
        $line = rtrim($line, "\r\n") . "\n";
        $ok = @fwrite($fh, $line) === strlen($line) && fflush($fh);
        fclose($fh);
        return $ok;
    }

    /** The snapshot files of the queue, oldest first (a snapshot of the former single-file format first) */
    private static function snapshots(string $file): array
    {
        $dir = dirname($file);
        $base = basename($file);
        $names = @scandir($dir) ?: [];
        $snaps = [];
        foreach ($names as $n) {
            if (strpos($n, $base . '.snap.') === 0 && substr($n, -4) !== '.new') {
                $snaps[] = $n;
            }
        }
        sort($snaps, SORT_STRING); // the names carry the time they were taken
        if (is_file($file . '.replay')) {
            array_unshift($snaps, $base . '.replay');
        }
        return array_map(function ($n) use ($dir) {
            return $dir . DIRECTORY_SEPARATOR . $n;
        }, $snaps);
    }

    /** Replays one snapshot, leaving what was not written in it (see replayAccounting()). $state is shared by all snapshots of the run */
    private static function replaySnapshot(string $snap, array &$state, array &$out): void
    {
        $tmp = $snap . '.new';
        $in = $left = null;
        try {
            $in = @fopen($snap, 'rb');
            $left = @fopen($tmp, 'wb');
            if (!$in || !$left) {
                $state['stop'] = true;
                return;
            }
            $kept = 0;       // bytes written to $left
            $keepOk = true;  // every write to it complete
            $keep = function (string $line) use ($left, &$kept, &$keepOk) {
                $line = rtrim($line, "\r\n") . "\n";
                $n = @fwrite($left, $line);
                if ($n !== strlen($line)) {
                    $keepOk = false;
                }
                $kept += (int) $n;
            };
            while (($line = fgets($in, 1048576)) !== false) {
                if (substr($line, -1) !== "\n" && !feof($in)) {
                    // longer than any packet: not one. Drop it up to its end.
                    while (($rest = fgets($in, 1048576)) !== false && substr($rest, -1) !== "\n") {
                    }
                    continue;
                }
                if (trim($line) === '') {
                    continue;
                }
                $p = json_decode($line, true);
                if (!is_array($p) || !isset($p['username'], $p['acctsessionid']) || (string) $p['username'] === '' || (string) $p['acctsessionid'] === '') {
                    continue;
                }
                // the unique index compares with a case-insensitive collation that ignores trailing spaces (utf8mb4_general_ci): the
                // parts are folded alike and kept as a tuple, so no delimiter inside one can make two sessions look like one
                $fold = function ($v) {
                    return mb_strtolower(rtrim((string) $v, ' '));
                };
                $key = json_encode([$fold($p['nasipaddress'] ?? ''), $fold($p['username']), $fold($p['acctsessionid'])], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                // held back behind an earlier packet of its session that is waiting
                if (isset($state['blocked'][$key])) {
                    $keep($line);
                    continue;
                }
                // over the bound, or the database is most likely down: streamed on unchanged, and its session is not held back
                // (nothing is being replayed any more, so there is no later packet to keep in order)
                if ($state['tried'] >= $state['max'] || $state['streak'] >= 25) {
                    $keep($line);
                    continue;
                }
                if ((int) ($p['_next'] ?? 0) > $state['now']) {
                    $state['blocked'][$key] = true; // still backing off: this session's later packets wait too
                    $keep($line);
                    continue;
                }
                $state['tried']++; // a Stop counts like any other: the bound holds for every packet
                try {
                    self::saveAccounting($p, true);
                    $out['replayed']++;
                    $state['streak'] = 0;
                } catch (Throwable $e) {
                    if (self::permanentFailure($e)) {
                        // a packet the database will never take (bad data): set aside for the operator, so it neither retries for
                        // ever nor holds back stale cleanup. Dropped from the queue only once it is safely in the quarantine file.
                        if (self::quarantine($line)) {
                            continue;
                        }
                    }
                    $out['failed']++;
                    $state['streak']++;
                    $state['blocked'][$key] = true;
                    $p['_tries'] = (int) ($p['_tries'] ?? 0) + 1;
                    $p['_next'] = $state['now'] + min(3600, 60 * (2 ** min(6, $p['_tries'] - 1)));
                    $again = json_encode($p, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                    $keep($again !== false ? $again : $line); // kept, a Stop included
                }
            }
            $complete = feof($in); // false: fgets() stopped on a read error, not at the end of the file
            fclose($in);
            $in = null;
            if (!$complete) {
                // part of the snapshot was never read: it stays as it is (replaying it again is harmless), nothing is replaced
                $state['stop'] = true;
                fclose($left);
                $left = null;
                @unlink($tmp);
                return;
            }
            $synced = $keepOk && @fflush($left) && (!function_exists('fsync') || @fsync($left));
            fclose($left);
            $left = null;
            if (!$synced) {
                $state['stop'] = true;
                @unlink($tmp); // the snapshot stays as it was: its packets are tried again (a replay is harmless to repeat)
                return;
            }
            if ($kept === 0) {
                @unlink($tmp);
                @unlink($snap);
            } elseif (!@rename($tmp, $snap)) {
                $state['stop'] = true;
                @unlink($tmp);
            }
        } finally {
            foreach ([$in, $left] as $h) {
                if (is_resource($h)) {
                    fclose($h);
                }
            }
        }
    }

    /**
     * Move what radius.php queued into a snapshot file of its own, under the queue's lock (so no packet is being appended
     * meanwhile): one rename, so the record file is either still whole in the queue or whole in the snapshot, and later packets
     * start a new queue file at once. Nothing is copied under the lock. False when it could not be done.
     */
    private static function takeIncoming(string $file): bool
    {
        $lock = @fopen($file . '.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX)) {
            return false;
        }
        try {
            clearstatcache(true);
            if ((int) @filesize($file) === 0) {
                return true;
            }
            $snap = $file . '.snap.' . sprintf('%020d', (int) round(microtime(true) * 1000000)) . bin2hex(random_bytes(3));
            return @rename($file, $snap);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
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
