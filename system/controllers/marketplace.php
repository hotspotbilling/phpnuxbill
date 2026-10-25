<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Marketplace'));
$ui->assign('_system_menu', 'marketplace');

$admin = Admin::_info();
$ui->assign('_admin', $admin);

if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
}

$action = $routes['1'] ?? '';

// Dispatch sub-modules: marketplace/plugins, marketplace/gateways, marketplace/themes, marketplace/devices
if (in_array($action, ['plugins', 'plugin'], true)) {
    include $root_path . File::pathFixer('system/controllers/marketplace_plugins.php');
    exit;
}
if (in_array($action, ['gateways', 'gateway'], true)) {
    include $root_path . File::pathFixer('system/controllers/marketplace_gateways.php');
    exit;
}
if (in_array($action, ['themes', 'theme'], true)) {
    include $root_path . File::pathFixer('system/controllers/marketplace_themes.php');
    exit;
}
if (in_array($action, ['devices', 'device'], true)) {
    include $root_path . File::pathFixer('system/controllers/marketplace_devices.php');
    exit;
}

// Repository cache handling
$plugin_repository = 'https://hotspotbilling.github.io/Plugin-Repository/repository.json';
$repoCache = $CACHE_PATH . File::pathFixer('/plugin_repository.json');

if ($action === 'sync') {
    // return to the tab the sync was triggered from
    $from = _get('from');
    $backTo = in_array($from, ['plugins', 'gateways', 'themes', 'devices'], true) ? 'marketplace/' . $from : 'marketplace';
    $data = Http::getData($plugin_repository);
    $decoded = ($data !== false && $data !== '') ? json_decode($data, true) : null;
    if (is_array($decoded) && !empty($decoded)) {
        file_put_contents($repoCache, $data);
        r2(getUrl($backTo), 's', Lang::T('Marketplace repository synchronized successfully.'));
    } else {
        // keep the previous cache untouched when the download fails or is invalid
        r2(getUrl($backTo), 'w', Lang::T('Could not connect to online repository. Using local cache if available.'));
    }
}

// Load repository
$repoJson = [];
$repoLastSync = 0;
if (file_exists($repoCache)) {
    $repoLastSync = filemtime($repoCache);
    if (time() - $repoLastSync < (24 * 60 * 60)) {
        $repoJson = json_decode(file_get_contents($repoCache), true) ?: [];
    }
}
if (empty($repoJson)) {
    $data = Http::getData($plugin_repository);
    if ($data !== false && $data !== '') {
        file_put_contents($repoCache, $data);
        $repoJson = json_decode($data, true) ?: [];
        $repoLastSync = time();
    } elseif (file_exists($repoCache)) {
        $repoJson = json_decode(file_get_contents($repoCache), true) ?: [];
    }
}

// 1. Plugins stats
$folderCount = count(PluginLoader::$plugins);
$legacyCount = 0;
$activeCount = 0;
foreach (PluginLoader::$plugins as $plg) {
    if (!empty($plg['active'])) {
        $activeCount++;
    }
}
foreach (glob($PLUGIN_PATH . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
    $fileSlug = basename($file, '.php');
    if ($fileSlug !== 'index' && !PluginLoader::isShadowed($file)) {
        $legacyCount++;
    }
}
$repoPluginsCount = count($repoJson['plugins'] ?? []);

// 2. Themes stats
$themeCount = 0;
$activeTheme = $config['theme'] ?? 'default';
$themesDir = File::pathFixer($UI_PATH . '/themes/');
if (is_dir($themesDir)) {
    $themeCount = count(glob($themesDir . '*' . DIRECTORY_SEPARATOR . 'theme.json') ?: []);
}

// 3. Devices stats
$deviceCount = 0;
foreach (glob($DEVICE_PATH . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
    $cn = basename($file, '.php');
    if ($cn !== 'readme' && $cn !== 'index') {
        $deviceCount++;
    }
}
$repoDevicesCount = count($repoJson['devices'] ?? []);

// 4. Payment Gateways stats
$gatewayCount = 0;
foreach (glob($PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
    $cn = basename($file, '.php');
    if ($cn !== 'readme' && $cn !== 'index') {
        $gatewayCount++;
    }
}
$activeGatewaysList = array_filter(array_map('trim', explode(',', $config['payment_gateway'] ?? '')));
$activeGatewaysCount = count($activeGatewaysList);
$repoGatewaysCount = count($repoJson['payment_gateway'] ?? []);

// 5. Featured / Spotlight Items (latest from each repository section)
$spotlight = [];
$spotSources = [
    ['key' => 'plugins', 'type' => 'Plugin', 'badge' => 'mp-badge--folder', 'target' => 'marketplace/plugins'],
    ['key' => 'payment_gateway', 'type' => 'Gateway', 'badge' => 'mp-badge--gateway', 'target' => 'marketplace/gateways'],
    ['key' => 'devices', 'type' => 'Device', 'badge' => 'mp-badge--device', 'target' => 'marketplace/devices'],
];
foreach ($spotSources as $src) {
    $items = $repoJson[$src['key']] ?? [];
    if (!is_array($items)) {
        continue;
    }
    // newest first when the repository carries a last_update date
    usort($items, fn($a, $b) => strcmp($b['last_update'] ?? '', $a['last_update'] ?? ''));
    foreach (array_slice($items, 0, 2) as $it) {
        $it['category_type'] = $src['type'];
        $it['badge_class'] = $src['badge'];
        $it['target_url'] = Text::url($src['target']);
        $it['ispaid'] = !empty($it['ispaid']);
        $spotlight[] = $it;
    }
}

// 6. Environment & Diagnostics
$diagnostics = [
    'php_version' => PHP_VERSION,
    'zip_enabled' => class_exists('ZipArchive'),
    'curl_enabled' => function_exists('curl_init'),
    'plugin_writable' => is_writable($PLUGIN_PATH),
    'themes_writable' => is_writable($themesDir),
    'devices_writable' => is_writable($DEVICE_PATH),
    'gateway_writable' => is_writable($PAYMENTGATEWAY_PATH),
    'cache_writable' => is_writable($CACHE_PATH),
    'github_token_set' => !empty($config['github_token']),
    'last_sync' => $repoLastSync > 0 ? date('M j, Y g:i A', $repoLastSync) : Lang::T('Never'),
    'repo_online' => !empty($repoJson),
];

$stats = [
    'plugins' => $folderCount + $legacyCount,
    'plugins_folder' => $folderCount,
    'plugins_active' => $activeCount,
    'plugins_legacy' => $legacyCount,
    'plugins_repo' => $repoPluginsCount,
    'themes' => $themeCount,
    'active_theme' => $activeTheme,
    'devices' => $deviceCount,
    'devices_repo' => $repoDevicesCount,
    'gateways' => $gatewayCount,
    'gateways_active' => $activeGatewaysCount,
    'gateways_repo' => $repoGatewaysCount,
    'repo_total' => $repoPluginsCount + $repoGatewaysCount + $repoDevicesCount,
    'total_extensions' => ($folderCount + $legacyCount) + $themeCount + $deviceCount + $gatewayCount,
];

$ui->assign('active_tab', 'hub');
$ui->assign('stats', $stats);
$ui->assign('spotlight', $spotlight);
$ui->assign('diagnostics', $diagnostics);
$ui->assign('csrf_token', Csrf::generateAndStoreToken());
$ui->display('admin/marketplace/marketplace.tpl');
