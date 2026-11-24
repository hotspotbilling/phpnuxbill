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

// the same closing the main cron does on every run (RadiusSessions): this script is for a site that wants it more often
$minutes = RadiusSessions::staleMinutes($config);
if ($minutes < 1) {
    die("Clearing stale sessions is disabled in configuration.");
}
$result = RadiusSessions::closeStale($minutes);
foreach ($result['lines'] as $line) {
    echo $line . "\n";
}
echo "Cleaned up " . $result['closed'] . " stale sessions\n";
exit($result['failed'] ? 1 : 0);
