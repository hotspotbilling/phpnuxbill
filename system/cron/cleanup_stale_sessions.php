<?php
/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 * by https://t.me/ibnux
 * Used to clean up stale sessions in rad_acct and radacct tables
 * Maintained by: https://t.me/focuslinkstech
 **/

include "../../init.php";

$sessionTimeout = 15 * 60; // 15 minutes of inactivity
$now = time();

// Find stale REST sessions (rad_acct table)
$staleSessionsRest = ORM::for_table('rad_acct')
    ->where('acctstatustype', 'Start')
    ->whereRaw("UNIX_TIMESTAMP(dateAdded) < " . ($now - $sessionTimeout))
    ->find_many();

// Find stale MySQL RADIUS sessions (radacct table)
$staleSessionsMysql = ORM::for_table('radacct')
    ->whereNull('acctstoptime')
    ->whereRaw("UNIX_TIMESTAMP(acctupdatetime) < " . ($now - $sessionTimeout))
    ->find_many();

$count = 0;

// Process REST sessions
foreach ($staleSessionsRest as $session) {
    $session->acctstatustype = 'Stop';
    $session->save();
    _log("Cleaned up stale REST session: {$session['username']} from IP {$session['framedipaddress']}", 'CRON');
    $count++;
}

// Process MySQL RADIUS sessions
foreach ($staleSessionsMysql as $session) {
    $session->acctstoptime = date('Y-m-d H:i:s');
    $session->save();
    _log("Cleaned up stale MySQL session: {$session['username']} from IP {$session['framedipaddress']}", 'CRON');
    $count++;
}

echo "Cleaned up " . $count . " stale sessions\n";
