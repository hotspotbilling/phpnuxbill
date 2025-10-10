<?php

include "../../init.php";

$isCli = (php_sapi_name() === 'cli');
if (!$isCli) echo "<pre>";

// Dry-run detection
$dryRun = false;
if ($isCli) {
    global $argv;
    $dryRun = in_array('--dry-run', $argv);
} else {
    $dryRun = isset($_GET['dry-run']) && $_GET['dry-run'] == '1';
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
}

// Summary
echo "\n===== SUMMARY =====\n";
echo "Total candidates: $countCandidates\n";
echo "Mode: " . ($dryRun ? "DRY RUN" : "LIVE DELETE") . "\n";

if (!$isCli) echo "</pre>";
