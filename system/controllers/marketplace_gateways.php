<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

$admin = MarketplaceController::guard('Marketplace - Payment Gateways');

$action = ($routes[0] === 'marketplace') ? ($routes['2'] ?? 'list') : ($routes['1'] ?? 'list');
$slug = ($routes[0] === 'marketplace') ? ($routes['3'] ?? '') : ($routes['2'] ?? '');
if (empty($action)) {
    $action = 'list';
}
if ($routes[0] === 'marketplace_gateways' && $action === 'list') {
    r2(getUrl('marketplace/gateways'));
}

if ($action === 'refresh') { // legacy link kept for old bookmarks
    r2(getUrl('marketplace/sync&from=gateways'));
}

MarketplaceController::common('gateway', $action, $slug);

$activeList = array_filter(array_map('trim', explode(',', $config['payment_gateway'] ?? '')));

if ($action === 'toggle') {
    MarketplaceController::protect('marketplace/gateways');
    if ($slug === '' || !Extensions::validSlug($slug) || !GatewayLoader::exists($slug)) {
        r2(getUrl('marketplace/gateways'), 'e', Lang::T('Invalid gateway slug.'));
    }
    if (in_array($slug, $activeList, true)) {
        $activeList = array_diff($activeList, [$slug]);
        $msg = Lang::T('Payment Gateway deactivated from checkout.');
        $log = 'disabled in checkout';
    } else {
        $manifest = GatewayLoader::manifest($slug);
        $issues = Extensions::check($manifest);
        if (Extensions::hasBlocking($issues)) {
            r2(getUrl('marketplace/gateways'), 'e', implode('; ', array_column($issues, 'message')));
        }
        if (Extensions::licenseMissing('gateway', $slug, $manifest)) {
            r2(getUrl('marketplace/gateways'), 'e', Lang::T('This gateway needs a valid license key. Enter it with the License button first.'));
        }
        $lint = Extensions::lint(GatewayLoader::file($slug));
        if ($lint !== null) {
            r2(getUrl('marketplace/gateways'), 'e', Lang::T('This gateway has a PHP error and cannot be enabled:') . ' ' . $lint);
        }
        $activeList[] = $slug;
        $msg = Lang::T('Payment Gateway activated for checkout.');
        $log = 'enabled in checkout';
    }
    Extensions::setConfig('payment_gateway', implode(',', array_unique($activeList)));
    Extensions::log('toggle', 'gateway', $slug, $log);
    r2(getUrl('marketplace/gateways'), 's', $msg);
}

// ---- listing ---------------------------------------------------------------
if (!function_exists('_gateway_region')) {
    function _gateway_region($slug, $desc = '')
    {
        $text = strtolower($slug . ' ' . $desc);
        if (strpos($text, 'indonesia') !== false || in_array($slug, ['duitku', 'tripay', 'xendit', 'midtrans', 'moota', 'faspay', 'oy'])) {
            return 'Indonesia';
        }
        if (strpos($text, 'kenya') !== false || strpos($text, 'nigeria') !== false || strpos($text, 'africa') !== false || in_array($slug, ['paystack', 'flutterwave', 'umspay', 'mpesa', 'monnify', 'opay'])) {
            return 'Africa';
        }
        if (strpos($text, 'bangladesh') !== false || in_array($slug, ['bkash', 'nagad', 'rocket'])) {
            return 'Bangladesh';
        }
        if (strpos($text, 'india') !== false || in_array($slug, ['razorpay', 'payu', 'allupipay', 'cashfree', 'phonepe', 'paytm'])) {
            return 'India';
        }
        if (strpos($text, 'oman') !== false || in_array($slug, ['thawani'])) {
            return 'Middle East';
        }
        if (in_array($slug, ['paypal', 'stripe', 'hitpay', 'coingate', 'coinbase', 'crypto'])) {
            return 'Global';
        }
        return 'Other';
    }
}

$repo = MarketplaceRepo::load();
$updatesIndex = MarketplaceUpdates::index();
$watch = MarketplaceController::watchlist();

$installedGateways = [];
$regions = [];
$configuredCount = 0;
$matchedStoreIds = [];
foreach (GatewayLoader::all() as $gSlug => $entry) {
    $manifest = $entry['manifest'];
    $header = Extensions::headerInfo($entry['file']);
    $title = $manifest['name'] ?? ($header['title'] ?? ($header['name'] ?? ucwords(str_replace(['phpnuxbill-', '-payment-gateway', '_', '-'], [' ', ' ', ' ', ' '], $gSlug))));
    $description = $manifest['description'] ?? ($header['description'] ?? '');
    $author = $manifest['author'] ?? ($header['author'] ?? '');
    $version = $manifest['version'] ?? ($header['version'] ?? '1.0.0');
    $store = MarketplaceUpdates::matchStore('gateway', $gSlug, $repo);
    if ($store) {
        $matchedStoreIds[strtolower($store['id'])] = true;
        $description = $description ?: ($store['description'] ?? '');
        $author = $author ?: ($store['author'] ?? '');
    }
    $isConfigured = false;
    try {
        $isConfigured = ORM::for_table('tbl_appconfig')->where_like('setting', $gSlug . '\_%')->where_not_equal('value', '')->count() > 0;
    } catch (Throwable $e) {
        // ignore db error
    }
    $configuredCount += $isConfigured ? 1 : 0;
    $hasUi = $entry['folder']
        ? is_dir($entry['dir'] . DIRECTORY_SEPARATOR . 'ui')
        : file_exists($PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . $gSlug . '.tpl');
    $region = $manifest['region'] ?? _gateway_region($gSlug, $description);
    $regions[$region] = ($regions[$region] ?? 0) + 1;

    $installedGateways[] = MarketplaceController::installedCard('gateway', $gSlug, [
        'name' => $title,
        'description' => $description ?: Lang::T('Payment gateway integration for online transactions.'),
        'author' => $author ?: 'PHPNuxBill Community',
        'version' => $version,
        'region' => $region,
        'active' => in_array($gSlug, $activeList, true),
        'configured' => $isConfigured,
        'has_ui' => $hasUi,
        'file_size' => round(filesize($entry['file']) / 1024, 1) . ' KB',
    ], $entry['file'], $manifest, $store, $updatesIndex);
}
usort($installedGateways, fn($a, $b) => strcasecmp($a['name'], $b['name']));

$availableGateways = [];
foreach ($repo['payment_gateway'] as $rg) {
    if (isset($matchedStoreIds[strtolower($rg['id'])])) {
        continue;
    }
    $rg['region'] = _gateway_region($rg['name'] ?? $rg['id'], $rg['description'] ?? '');
    $availableGateways[] = MarketplaceController::storeCard('gateway', $rg, $watch);
}

$stats = [
    'total' => count($installedGateways),
    'active' => count($activeList),
    'configured' => $configuredCount,
    'available' => count($availableGateways),
    'repo_total' => count($repo['payment_gateway']),
    'regions' => $regions,
    'folder' => count(array_filter($installedGateways, fn($g) => $g['style'] === 'folder')),
    'updates' => count(array_filter($installedGateways, fn($g) => $g['update']['available'])),
    'errors' => count(array_filter($installedGateways, fn($g) => $g['health']['status'] === 'error')),
    'watched' => count(array_filter($availableGateways, fn($g) => $g['watched'])),
];

MarketplaceController::assignCommon('gateway', 'gateways');
$ui->assign('stats', $stats);
$ui->assign('gateways', $installedGateways);
$ui->assign('available', $availableGateways);
$ui->assign('active_list', $activeList);
$ui->display('admin/marketplace/marketplace-gateways.tpl');
