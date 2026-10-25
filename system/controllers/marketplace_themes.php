<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

$admin = MarketplaceController::guard('Marketplace - Themes');

$action = ($routes[0] === 'marketplace') ? ($routes['2'] ?? 'list') : ($routes['1'] ?? 'list');
$theme = ($routes[0] === 'marketplace') ? ($routes['3'] ?? '') : ($routes['2'] ?? '');
if (empty($action)) {
    $action = 'list';
}
if ($routes[0] === 'marketplace_themes' && $action === 'list') {
    r2(getUrl('marketplace/themes'));
}

MarketplaceController::common('theme', $action, $theme);

if (in_array($action, ['activate', 'default'], true)) {
    MarketplaceController::protect('marketplace/themes');
    if ($action === 'default') {
        Extensions::setConfig('theme', 'default');
        $_c['theme'] = 'default';
        Extensions::log('activate', 'theme', 'default');
        r2(getUrl('marketplace/themes'), 's', Lang::T('Switched to default theme.'));
    }
    if ($theme === '' || !Extensions::validSlug($theme) || !Extensions::isFolder('theme', $theme)) {
        r2(getUrl('marketplace/themes'), 'e', Lang::T('Theme not found.'));
    }
    $manifest = Extensions::manifest('theme', $theme);
    $issues = Extensions::check($manifest);
    if (Extensions::hasBlocking($issues)) {
        r2(getUrl('marketplace/themes'), 'e', implode('; ', array_column($issues, 'message')));
    }
    $functions = Extensions::root('theme') . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . 'functions.php';
    if (is_file($functions) && ($err = Extensions::lint($functions)) !== null) {
        r2(getUrl('marketplace/themes'), 'e', Lang::T('This theme has a PHP error and was not activated:') . ' ' . $err);
    }
    if (Extensions::licenseMissing('theme', $theme, $manifest)) {
        r2(getUrl('marketplace/themes'), 'e', Lang::T('This theme needs a valid license key. Enter it with the License button first.'));
    }
    Extensions::setConfig('theme', $theme);
    $_c['theme'] = $theme;
    Extensions::log('activate', 'theme', $theme);
    r2(getUrl('marketplace/themes'), 's', Lang::T('Theme activated.'));
}

// ---- listing ---------------------------------------------------------------
$repo = MarketplaceRepo::load();
$updatesIndex = MarketplaceUpdates::index();
$watch = MarketplaceController::watchlist();
$themesDir = Extensions::root('theme');

$availableThemes = [];
$matchedStoreIds = [];
foreach (glob($themesDir . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'theme.json') ?: [] as $metaFile) {
    $themeDir = dirname($metaFile);
    $tSlug = basename($themeDir);
    $meta = json_decode((string) file_get_contents($metaFile), true);
    $meta = is_array($meta) ? $meta : [];

    // preview image: theme.json "preview", else a conventional file name
    $preview = '';
    $candidates = [];
    if (!empty($meta['preview']) && is_string($meta['preview'])) {
        $candidates[] = $meta['preview'];
    }
    foreach (['preview.png', 'preview.jpg', 'preview.webp', 'screenshot.png', 'screenshot.jpg', 'screenshot.webp'] as $c) {
        $candidates[] = $c;
    }
    foreach ($candidates as $c) {
        if (preg_match('#^[A-Za-z0-9_./-]+\.(png|jpe?g|gif|webp|svg)$#i', $c) && strpos($c, '..') === false && is_file($themeDir . DIRECTORY_SEPARATOR . $c)) {
            $preview = APP_URL . '/' . $UI_PATH . '/themes/' . rawurlencode($tSlug) . '/' . $c;
            break;
        }
    }

    $store = MarketplaceUpdates::matchStore('theme', $tSlug, $repo);
    if ($store) {
        $matchedStoreIds[strtolower($store['id'])] = true;
    }
    $functionsFile = $themeDir . DIRECTORY_SEPARATOR . 'functions.php';
    $availableThemes[] = MarketplaceController::installedCard('theme', $tSlug, [
        'name' => $meta['name'] ?? $tSlug,
        'version' => $meta['version'] ?? '1.0.0',
        'author' => $meta['author'] ?? '',
        'description' => $meta['description'] ?? '',
        'parent' => $meta['parent'] ?? null,
        'active' => ($config['theme'] ?? '') === $tSlug,
        'has_functions' => is_file($functionsFile),
        'preview' => $preview,
    ], is_file($functionsFile) ? $functionsFile : null, $meta, $store, $updatesIndex);
}
usort($availableThemes, fn($a, $b) => strcasecmp($a['name'], $b['name']));

$available = [];
foreach ($repo['themes'] as $t) {
    if (!isset($matchedStoreIds[strtolower($t['id'])]) && !Extensions::isFolder('theme', $t['id'])) {
        $available[] = MarketplaceController::storeCard('theme', $t, $watch);
    }
}

$stats = ['total' => count($availableThemes), 'active' => 0, 'child' => 0, 'functions' => 0, 'updates' => 0, 'errors' => 0, 'available' => count($available)];
foreach ($availableThemes as $t) {
    $stats['active'] += $t['active'] ? 1 : 0;
    $stats['child'] += $t['parent'] ? 1 : 0;
    $stats['functions'] += $t['has_functions'] ? 1 : 0;
    $stats['updates'] += $t['update']['available'] ? 1 : 0;
    $stats['errors'] += $t['health']['status'] === 'error' ? 1 : 0;
}

MarketplaceController::assignCommon('theme', 'themes');
$ui->assign('themes', $availableThemes);
$ui->assign('available', $available);
$ui->assign('stats', $stats);
$ui->assign('active_theme', $config['theme'] ?? 'default');
$ui->display('admin/marketplace/marketplace-themes.tpl');
