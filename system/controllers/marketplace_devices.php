<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

$admin = MarketplaceController::guard('Marketplace - Device Drivers');

$action = ($routes[0] === 'marketplace') ? ($routes['2'] ?? 'list') : ($routes['1'] ?? 'list');
$slug = ($routes[0] === 'marketplace') ? ($routes['3'] ?? '') : ($routes['2'] ?? '');
if (empty($action)) {
    $action = 'list';
}
if ($routes[0] === 'marketplace_devices' && $action === 'list') {
    r2(getUrl('marketplace/devices'));
}

MarketplaceController::common('device', $action, $slug);

// ---- listing ---------------------------------------------------------------
$repo = MarketplaceRepo::load();
$updatesIndex = MarketplaceUpdates::index();
$watch = MarketplaceController::watchlist();

$devices = [];
$categories = [];
$matchedStoreIds = [];
foreach (DeviceLoader::all() as $className => $entry) {
    $manifest = $entry['manifest'];
    $code = (string) file_get_contents($entry['file']);
    $title = $manifest['name'] ?? $className;
    $description = $manifest['description'] ?? '';
    $author = $manifest['author'] ?? '';
    if (!$manifest) {
        if (preg_match("/'title'\s*=>\s*'([^']+)'/", $code, $m)) {
            $title = $m[1];
        }
        if (preg_match("/'description'\s*=>\s*'([^']+)'/", $code, $m)) {
            $description = $m[1];
        }
        if (preg_match("/'author'\s*=>\s*'([^']+)'/", $code, $m)) {
            $author = $m[1];
        }
    }
    $category = $manifest['category'] ?? 'Other';
    if (!isset($manifest['category'])) {
        foreach (['Mikrotik', 'Radius', 'Voucher', 'Dummy'] as $prefix) {
            if (stripos($className, $prefix) === 0) {
                $category = $prefix;
                break;
            }
        }
    }
    $categories[$category] = ($categories[$category] ?? 0) + 1;
    $store = MarketplaceUpdates::matchStore('device', $className, $repo);
    if ($store) {
        $matchedStoreIds[strtolower($store['id'])] = true;
    }
    $used = ORM::for_table('tbl_plans')->where('device', $className)->count();
    $devices[] = MarketplaceController::installedCard('device', $className, [
        'name' => $title,
        'description' => $description,
        'author' => $author,
        'version' => $manifest['version'] ?? '',
        'category' => $category,
        'size' => round(filesize($entry['file']) / 1024, 1) . ' KB',
        'core' => ExtensionInstaller::isCoreDevice($className),
        'plans' => $used,
    ], $entry['file'], $manifest, $store, $updatesIndex);
}
usort($devices, fn($a, $b) => strcasecmp($a['name'], $b['name']));

$available = [];
foreach ($repo['devices'] as $d) {
    if (!isset($matchedStoreIds[strtolower($d['id'])])) {
        $available[] = MarketplaceController::storeCard('device', $d, $watch);
    }
}

$stats = [
    'total' => count($devices),
    'categories' => $categories,
    'folder' => count(array_filter($devices, fn($d) => $d['style'] === 'folder')),
    'updates' => count(array_filter($devices, fn($d) => $d['update']['available'])),
    'errors' => count(array_filter($devices, fn($d) => $d['health']['status'] === 'error')),
    'watched' => count(array_filter($available, fn($d) => $d['watched'])),
];

MarketplaceController::assignCommon('device', 'devices');
$ui->assign('devices', $devices);
$ui->assign('available', $available);
$ui->assign('stats', $stats);
$ui->display('admin/marketplace/marketplace-devices.tpl');
