<?php
/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 * by https://t.me/ibnux
 * Used to clean up stale sessions in rad_acct and radacct tables
 * Maintained by: https://t.me/focuslinkstech
 **/

// Check if script is run from CLI (before loading anything)
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

include __DIR__ . "/../../init.php";

// Check if freeradius module is enabled
if (($config['radius_enable'] ?? '') !== '1') {
    die("FreeRadius module is not enabled.");
}

if (($config['clear_stale_sessions'] ?? '') !== '1') {
    die("Clearing stale sessions is disabled in configuration.");
}

// Minutes of inactivity before a session is stale. An empty, zero or negative setting would make every open
// session look stale and disconnect all customers, so anything below 1 minute falls back to the default.
$timeout = (int) ($config['stale_session_timeout'] ?? 10);
if ($timeout < 1) {
    $timeout = 10;
}
$sessionTimeout = $timeout * 60;

$failed = false; // any real query failure makes the script exit with status 1
$count = 0;

/** Only "table does not exist" (MySQL 1146 / SQLSTATE 42S02) means the table was never created; anything else is a real fault */
function stale_sessions_table_missing(Throwable $e): bool
{
    if (!($e instanceof PDOException)) {
        return false;
    }
    return (isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1146) || (string) $e->getCode() === '42S02';
}

/** A few usernames of the sessions about to be closed, for the log line only (the UPDATE itself does not depend on this) */
function stale_sessions_sample(string $table, ?string $connection, string $where, array $params): string
{
    try {
        $query = $connection ? ORM::for_table($table, $connection) : ORM::for_table($table);
        $rows = $query->select('username')->where_raw($where, $params)->limit(10)->find_array();
        return implode(', ', array_unique(array_column($rows, 'username')));
    } catch (Throwable $e) {
        return '';
    }
}

/**
 * Close the stale sessions with ONE conditional UPDATE: the stale test and the change happen together, so a session that
 * sends an update meanwhile is not closed by mistake. Returns the number of sessions closed, or null when it failed.
 */
function stale_sessions_close(string $label, string $table, ?string $connection, string $setSql, array $setParams, string $where, array $params): ?int
{
    global $failed;
    try {
        $sample = stale_sessions_sample($table, $connection, $where, $params);
        $db = $connection ? ORM::get_db($connection) : ORM::get_db();
        $stmt = $db->prepare("UPDATE {$table} SET {$setSql} WHERE {$where}");
        $stmt->execute(array_merge($setParams, $params));
        $closed = $stmt->rowCount();
        if ($closed > 0) {
            // one summary line per run, not one per session: the first run after an outage can close thousands
            try {
                _log("Cleaned up {$closed} stale {$label} session(s)" . ($sample !== '' ? " (e.g. {$sample})" : ''), 'CRON');
            } catch (Throwable $ignored) {
                // the sessions are already closed: a failed log line must not turn that into a failure
            }
        }
        return $closed;
    } catch (Throwable $e) {
        if (stale_sessions_table_missing($e)) {
            echo "No {$table} table, skipping {$label} sessions\n";
            return 0;
        }
        // connection refused, access denied, bad column...: a real problem
        $failed = true;
        echo "Error cleaning up {$table}: " . $e->getMessage() . "\n";
        try {
            _log("Error cleaning up stale {$label} sessions: " . $e->getMessage(), 'CRON');
        } catch (Throwable $ignored) {
            // logging must never hide the failure or change the exit status
        }
        return null;
    }
}

// REST sessions (rad_acct table, written by radius.php). Stale = no accounting update since the cutoff; sessions that
// never sent an Interim-Update only have dateAdded, so the NAS interim interval must be shorter than the timeout.
// radius.php writes these dates with PHP date() in the app timezone, so the cutoff is built the same way here
// (comparing with MySQL NOW() or UNIX_TIMESTAMP() would be off by the PHP/MySQL timezone difference).
$cutoff = date('Y-m-d H:i:s', time() - $sessionTimeout);
$closed = stale_sessions_close(
    'REST',
    'rad_acct',
    null,
    'acctstatustype = ?',
    ['Stop'],
    "(acctstatustype IN (?, ?) OR acctstatustype IS NULL) AND COALESCE(dateUpdated, dateAdded) > ? AND COALESCE(dateUpdated, dateAdded) < ?",
    ['Start', 'Interim-Update', '2000-01-01 00:00:00', $cutoff]
);
$count += (int) $closed;

// MySQL RADIUS sessions (radacct table). It only exists when FreeRADIUS uses the SQL module with install/radius.sql
// imported (in the separate "radius" database when one is configured). A RADIUS REST setup has no radacct table, so
// a missing table is not an error. FreeRADIUS fills these dates itself, so the cutoff is computed by MySQL (NOW()).
// The stop time is the last update, not now: the session really ended when the NAS went quiet.
$radiusConnection = (class_exists('SystemInfo') && SystemInfo::radiusConnectionConfigured()) ? 'radius' : null;
$closed = stale_sessions_close(
    'MySQL',
    'radacct',
    $radiusConnection,
    "acctstoptime = acctupdatetime, acctterminatecause = CASE WHEN acctterminatecause = '' THEN ? ELSE acctterminatecause END",
    ['Lost-Carrier'],
    "acctstoptime IS NULL AND acctupdatetime IS NOT NULL AND acctupdatetime < (NOW() - INTERVAL ? SECOND)",
    [$sessionTimeout]
);
$count += (int) $closed;

echo "Cleaned up " . $count . " stale sessions\n";
exit($failed ? 1 : 0);
