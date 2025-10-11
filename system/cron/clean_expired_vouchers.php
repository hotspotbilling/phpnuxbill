<?php

/**
 * PHPNuxBill - Expired Vouchers Cleanup Script
 * This script can be run from CLI via cron job for actual deletions
 * Web access is allowed only for dry-run mode (preview only)
 */

session_start();

include "../../init.php";

// Determine if running from CLI or web
$isCli = (php_sapi_name() === 'cli');

// Set up HTML formatting for web interface
if (!$isCli) {
    echo "<pre>";
}

// Dry-run detection
$dryRun = false;
if ($isCli) {
    global $argv;
    $dryRun = in_array('--dry-run', $argv);
} else {
    // Web access is forced to dry-run mode for safety
    $dryRun = true;

    // Check if the user is authenticated and authorized
    if (!isset($_SESSION['aid']) || empty($_SESSION['aid'])) {
        // Log unauthorized access
        _log("Unauthorized access attempt to clean_expired_vouchers.php from " . $_SERVER['REMOTE_ADDR'], 'Warning');
        
        // Return HTTP 403 Forbidden
        header('HTTP/1.1 403 Forbidden');
        echo "Access denied. You must be logged in with admin privileges to view this page.</pre>";
        exit(1);
    }
}

/**
 * Check if current user has permission to view this script
 * @return bool
 */

echo "PHP Time: " . date('Y-m-d H:i:s') . "\n";

// Cutoff: expired more than 30 days ago
$cutoffDate = date("Y-m-d", strtotime("-30 days"));

// Step 1: Get usernames of expired vouchers
$expiredUsers = ORM::for_table('tbl_user_recharges')
    ->where('status', 'off')
    ->where('customer_id', '0')
    ->where_lte('expiration', $cutoffDate)
    ->select('username')
    ->find_array();

$usernames = array_column($expiredUsers, 'username');
$countCandidates = count($usernames);

echo "Found $countCandidates expired voucher(s) older than 30 days\n";

if ($countCandidates === 0) {
    echo "Nothing to delete.\n";
    exit;
}

if ($dryRun) {
    echo ">>> DRY RUN MODE: No deletions will be performed <<<\n";
    foreach ($usernames as $user) {
        echo "[DRY RUN] Would delete voucher(s) in tbl_voucher with code = $user\n";
    }
} else {
    // Get database connection for transaction
    $db = ORM::get_db();
    
    try {
        // Begin transaction to ensure atomicity
        $db->beginTransaction();
        
        // Step 2: Delete expired recharges
        $deletedRecharges = ORM::for_table('tbl_user_recharges')
            ->where_in('username', $usernames)
            ->where('status', 'off')
            ->where('customer_id', '0')
            ->delete_many();

        echo "Deleted $deletedRecharges expired voucher(s) from tbl_user_recharges\n";

        // Step 3: Delete matching vouchers from tbl_voucher
        $deletedVouchers = ORM::for_table('tbl_voucher')
            ->where_in('code', $usernames) // match code = username
            ->where('status', '1')
            ->delete_many();

        echo "Deleted $deletedVouchers vouchers from tbl_voucher\n";
        
        // Commit the transaction if both deletes were successful
        $db->commit();
        
    } catch (Exception $e) {
        // Rollback the transaction on any error
        $db->rollBack();
        
        // Log the error
        error_log("Error in clean_expired_vouchers.php: " . $e->getMessage());
        echo "ERROR: Transaction failed and was rolled back: " . $e->getMessage() . "\n";
    }
}

// Summary
echo "\n===== SUMMARY =====\n";
echo "Total candidates: $countCandidates\n";
echo "Mode: " . ($dryRun ? "DRY RUN" : "LIVE DELETE") . "\n";

// Add HTML formatting for web interface
if (!$isCli) {
    echo "</pre>";
    
    if ($dryRun) {
        echo "<div style='margin-top: 20px; padding: 10px; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 4px;'>";
        echo "<p><strong>Note:</strong> This is a preview mode only. Actual deletions can only be performed via CLI.</p>";
        echo "<p>To run this script via CLI with actual deletions: <code>php clean_expired_vouchers.php</code></p>";
        echo "<p>To run this script via CLI in dry-run mode: <code>php clean_expired_vouchers.php --dry-run</code></p>";
        echo "</div>";
    }
}
