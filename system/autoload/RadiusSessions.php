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

    private static function close(array &$out, string $label, string $table, ?string $connection, string $setSql, array $setParams, string $where, array $params): void
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
