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
// A session is stale if it hasn't been updated in more than $sessionTimeout seconds
// Use dateUpdated if available (for sessions with interim updates), otherwise fall back to dateAdded
$staleSessionsRest = ORM::for_table('rad_acct')
    ->where_raw("(acctstatustype = ? OR acctstatustype = ? OR acctstatustype IS NULL)", ['Start', 'Interim-Update'])
    ->where_not_null('dateAdded')
    ->where_raw("UNIX_TIMESTAMP(COALESCE(dateUpdated, dateAdded)) > 0 AND UNIX_TIMESTAMP(COALESCE(dateUpdated, dateAdded)) < ?", [$now - $sessionTimeout])
    ->find_many();

// Find stale MySQL RADIUS sessions (radacct table)
$staleSessionsMysql = ORM::for_table('radacct')
    ->where_null('acctstoptime')
    ->where_not_null('acctupdatetime')
    ->where_raw("UNIX_TIMESTAMP(acctupdatetime) < ?", [$now - $sessionTimeout])
    ->find_many();

$count = 0;

// Process REST sessions if any - use raw UPDATE for efficiency and to avoid ORM primary key issues
if (!empty($staleSessionsRest)) {
    // Get IDs and details for logging before updating
    $sessionIds = [];
    $sessionDetails = [];
    foreach ($staleSessionsRest as $session) {
        $sessionIds[] = $session->id;
        $sessionDetails[] = [
            'id' => $session->id,
            'username' => $session->username,
            'framedipaddress' => $session->framedipaddress
        ];
    }
    
    if (!empty($sessionIds)) {
        try {
            // Use prepared statement for consistency and security
            $ids = implode(',', array_map('intval', $sessionIds));
            $db = ORM::get_db();
            $stmt = $db->prepare("UPDATE rad_acct SET acctstatustype = ? WHERE id IN ($ids)");
            $stmt->execute(['Stop']);
            
            // Log each session
            foreach ($sessionDetails as $detail) {
                _log("Cleaned up stale REST session: {$detail['username']} from IP {$detail['framedipaddress']}", 'CRON');
                $count++;
            }
        } catch (Exception $e) {
            _log("Error cleaning up stale REST sessions: " . $e->getMessage(), 'CRON', 'ERROR');
        }
    }
}

// Process MySQL RADIUS sessions if any - use raw UPDATE for efficiency
if (!empty($staleSessionsMysql)) {
    // Get IDs and details for logging before updating
    $sessionIds = [];
    $sessionDetails = [];
    foreach ($staleSessionsMysql as $session) {
        $sessionIds[] = $session->radacctid;
        $sessionDetails[] = [
            'id' => $session->radacctid,
            'username' => $session->username,
            'framedipaddress' => $session->framedipaddress
        ];
    }
    
    if (!empty($sessionIds)) {
        try {
            // Use prepared statement for consistency and security
            $ids = implode(',', array_map('intval', $sessionIds));
            $stopTime = date('Y-m-d H:i:s');
            $db = ORM::get_db();
            $stmt = $db->prepare("UPDATE radacct SET acctstoptime = ? WHERE radacctid IN ($ids)");
            $stmt->execute([$stopTime]);
            
            // Log each session
            foreach ($sessionDetails as $detail) {
                _log("Cleaned up stale MySQL session: {$detail['username']} from IP {$detail['framedipaddress']}", 'CRON');
                $count++;
            }
        } catch (Exception $e) {
            _log("Error cleaning up stale MySQL sessions: " . $e->getMessage(), 'CRON', 'ERROR');
        }
    }
}

echo "Cleaned up " . $count . " stale sessions\n";
