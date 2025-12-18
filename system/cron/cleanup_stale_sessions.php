<?php
/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 * by https://t.me/ibnux
 * Used to clean up stale sessions in rad_acct and radacct tables
 * Maintained by: https://t.me/focuslinkstech
 **/

include "../../init.php";

// Check if script is run from CLI
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

// Check if freeradius module is enabled
if ($config['radius_enable'] !== '1') {
    die("FreeRadius module is not enabled.");
}

if ($config['clear_stale_sessions'] !== '1') {
    die("Clearing stale sessions is disabled in configuration.");
}

$timeout = isset($config['stale_session_timeout']) ? (int)$config['stale_session_timeout'] : 10; // Default to 10 minutes
$sessionTimeout = $timeout * 60; // Minutes of inactivity
$now = time();

// Find stale REST sessions (rad_acct table)
$staleSessionsRest = ORM::for_table('rad_acct')
    ->whereRaw("BINARY acctstatustype = 'Start' OR acctstatustype = 'Interim-Update' OR acctstatustype IS NULL")
    ->whereRaw("UNIX_TIMESTAMP(dateAdded) < " . ($now - $sessionTimeout))
    ->find_many();

// Find stale MySQL RADIUS sessions (radacct table)
$staleSessionsMysql = ORM::for_table('radacct')
    ->whereNull('acctstoptime')
    ->whereRaw("UNIX_TIMESTAMP(acctupdatetime) < " . ($now - $sessionTimeout))
    ->find_many();

$count = 0;

// Process REST sessions if any
if (!empty($staleSessionsRest)) {
    foreach ($staleSessionsRest as $session) {
        $session->acctstatustype = 'Stop';
        $session->save();
        _log("Cleaned up stale REST session: {$session['username']} from IP {$session['framedipaddress']}", 'CRON');
        $count++;
    }
}

// Process MySQL RADIUS sessions if any
if (!empty($staleSessionsMysql)) {
    foreach ($staleSessionsMysql as $session) {
        $session->acctstoptime = date('Y-m-d H:i:s');
        $session->save();
        _log("Cleaned up stale MySQL session: {$session['username']} from IP {$session['framedipaddress']}", 'CRON');
        $count++;
    }
}

echo "Cleaned up " . $count . " stale sessions\n";
