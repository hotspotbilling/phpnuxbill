<?php

/**
 * PHPNuxBill - Expired Vouchers Cleanup Script
 * This script can be run from CLI via cron job for actual deletions
 * Web access is allowed only for dry-run mode (preview only)
 */

session_start();

include __DIR__ . "/../../init.php"; // from any folder: cron starts in the home folder, not here

// Determine if running from CLI or web
$isCli = (php_sapi_name() === 'cli');

// Set up HTML formatting for web interface
if (!$isCli) {
    echo "<pre>";
}

$failed = false; // set when the delete transaction fails, so cron sees a non-zero exit status

// text from the database or from an exception goes into an HTML <pre> in web mode (escaped), but stays raw in CLI output
$out = function ($text) use ($isCli) {
    return $isCli ? (string) $text : htmlspecialchars((string) $text);
};

// Dry-run detection
$dryRun = false;
if ($isCli) {
    global $argv;
    $dryRun = in_array('--dry-run', $argv);
} else {
    // Web access is forced to dry-run mode for safety
    if (isset($_GET['dry-run']) && $_GET['dry-run'] === '1') {
        $dryRun = true;
    } else {
        // If accessed via web without dry-run parameter, force dry-run mode
        $dryRun = true;
    }

    // Web preview is for SuperAdmin / Admin (it lists voucher codes) or for a caller that knows the API key.
    // An empty api_key never matches, and the key is compared in constant time.
    $webAllowed = false;
    if (!empty($_SESSION['aid'])) {
        $staff = ORM::for_table('tbl_users')->find_one((int) $_SESSION['aid']);
        $webAllowed = $staff && in_array($staff['user_type'], ['SuperAdmin', 'Admin'], true);
    }
    if (!$webAllowed && !empty($config['api_key']) && isset($_GET['secret']) && is_string($_GET['secret'])) {
        $webAllowed = hash_equals((string) $config['api_key'], $_GET['secret']);
    }
    if (!$isCli && !$webAllowed) {
        // Log unauthorized access
        _log("Unauthorized access attempt to clean_expired_vouchers.php from " . $_SERVER['REMOTE_ADDR'] . " User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'), 'Warning');

        // Return HTTP 403 Forbidden
        header('HTTP/1.1 403 Forbidden');
        echo "Access denied. You do not have permission to access this resource.";
        exit(1);
    }
}

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

echo "Found $countCandidates expired voucher(s) older than $cutoffDate \n";

if ($countCandidates === 0) {
    echo "Nothing to delete.\n";
    exit;
}

if ($dryRun) {
    echo ">>> DRY RUN MODE: No deletions will be performed <<<\n";
    foreach ($usernames as $user) {
        echo "[DRY RUN] Would delete voucher(s) in tbl_voucher with code = " . $out($user) . "\n";
    }
} else {
    // Get database connection for transaction
    $db = ORM::get_db();

    try {
        // Begin transaction to ensure atomicity
        $db->beginTransaction();

        $deletedRecharges = 0;
        $deletedVouchers = 0;
        // in chunks: thousands of expired vouchers would make one IN (...) list too big for the query
        foreach (array_chunk($usernames, 500) as $chunk) {
            // Step 2: Delete expired recharges
            $deletedRecharges += ORM::for_table('tbl_user_recharges')
                ->where_in('username', $chunk)
                ->where('status', 'off')
                ->where('customer_id', '0')
                ->delete_many();

            // Step 3: Delete matching vouchers from tbl_voucher
            $deletedVouchers += ORM::for_table('tbl_voucher')
                ->where_in('code', $chunk) // match code = username
                ->where('status', '1')
                ->delete_many();
        }

        echo "Deleted $deletedRecharges expired voucher(s) from tbl_user_recharges\n";
        echo "Deleted $deletedVouchers vouchers from tbl_voucher\n";

        // Commit the transaction if both deletes were successful
        $db->commit();
    } catch (Throwable $e) {
        // Rollback the transaction on any error (there may be none to roll back if it never began)
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        // Log the error
        _log("Error in clean_expired_vouchers.php: " . $e->getMessage(), 'CRON');
        echo "ERROR: Transaction failed and was rolled back: " . $out($e->getMessage()) . "\n";
        $failed = true;
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

if ($isCli && $failed) {
    exit(1);
}
