<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * Marketplace sync: refresh every store repository, look for updates to installed plugins, payment
 * gateways, device drivers and themes, cache the result for the Marketplace pages and send a Telegram
 * notification for updates that were not announced before (Marketplace > Tools > Settings).
 *
 * CLI only. Suggested cron entry (every 6 hours):
 *   0 *\/6 * * * php /path/to/phpnuxbill/system/cron/marketplace_sync.php
 */

if (php_sapi_name() !== 'cli') {
    header('HTTP/1.1 403 Forbidden');
    exit('CLI only');
}

include __DIR__ . '/../../init.php';

$result = MarketplaceUpdates::run(true, true);

// signed licences expire: ask the publisher again before they do
$licenses = Extensions::refreshLicenses();

echo date('Y-m-d H:i:s') . ' repositories: ';
foreach (MarketplaceRepo::status() as $r) {
    echo $r['url'] . ' [' . $r['state'] . ', ' . $r['count'] . "] ";
}
echo "\n";
echo count($result['updates']) . " update(s) pending, " . count($result['new']) . ' new' . ($result['notified'] ? ", notification sent\n" : "\n");
foreach ($result['updates'] as $u) {
    echo '  - ' . $u['type'] . ' ' . $u['slug'] . ': ' . ($u['current'] ?: '?') . ' -> ' . $u['latest'] . "\n";
}
