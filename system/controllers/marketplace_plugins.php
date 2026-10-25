<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

$admin = MarketplaceController::guard('Marketplace - Plugins');

$action = ($routes[0] === 'marketplace') ? ($routes['2'] ?? 'list') : ($routes['1'] ?? 'list');
$slug = ($routes[0] === 'marketplace') ? ($routes['3'] ?? '') : ($routes['2'] ?? '');
if (empty($action)) {
    $action = 'list';
}
if ($routes[0] === 'marketplace_plugins' && $action === 'list') {
    r2(getUrl('marketplace/plugins'));
}

// install / update / upload / uninstall / convert / license are shared by every extension type
MarketplaceController::common('plugin', $action, $slug);

if (in_array($action, ['activate', 'deactivate'], true)) {
    MarketplaceController::protect('marketplace/plugins');
    if ($slug === '' || !Extensions::validSlug($slug)) {
        r2(getUrl('marketplace/plugins'), 'e', Lang::T('Invalid plugin slug.'));
    }
    if ($action === 'activate') {
        $manifest = PluginLoader::$plugins[$slug] ?? [];
        $issues = Extensions::check($manifest);
        if (Extensions::hasBlocking($issues)) {
            r2(getUrl('marketplace/plugins'), 'e', implode('; ', array_column($issues, 'message')));
        }
        if (Extensions::licenseMissing('plugin', $slug, $manifest)) {
            r2(getUrl('marketplace/plugins'), 'e', Lang::T('This plugin needs a valid license key. Enter it with the License button first.'));
        }
        if (PluginLoader::setActive($slug, true)) {
            Extensions::log('activate', 'plugin', $slug);
            $install = PluginLoader::runInstall($slug);
            if (!$install['success']) {
                r2(getUrl('marketplace/plugins'), 'e', Lang::T('Plugin activated.') . ' ' . Lang::T('Install hook failed:') . ' ' . $install['message']);
            }
            r2(getUrl('marketplace/plugins'), 's', Lang::T('Plugin activated.'));
        }
        r2(getUrl('marketplace/plugins'), 'e', Lang::T('Failed to activate plugin.'));
    }
    if (PluginLoader::setActive($slug, false)) {
        Extensions::log('deactivate', 'plugin', $slug);
        r2(getUrl('marketplace/plugins'), 's', Lang::T('Plugin deactivated.'));
    }
    r2(getUrl('marketplace/plugins'), 'e', Lang::T('Failed to deactivate plugin.'));
}

// ---- listing ---------------------------------------------------------------
$repo = MarketplaceRepo::load();
$updatesIndex = MarketplaceUpdates::index();
$watch = MarketplaceController::watchlist();

$allPlugins = [];
foreach (PluginLoader::$plugins as $plgSlug => $plg) {
    $main = ($plg['path'] ?? '') . DIRECTORY_SEPARATOR . ($plg['main'] ?? $plgSlug . '.php');
    $allPlugins[] = MarketplaceController::installedCard('plugin', $plgSlug, [
        'name' => $plg['name'] ?? $plgSlug,
        'version' => $plg['version'] ?? '1.0.0',
        'description' => $plg['description'] ?? '',
        'author' => $plg['author'] ?? '',
        'active' => !empty($plg['active']),
        'type' => 'folder',
    ], $main, $plg, MarketplaceUpdates::matchStore('plugin', $plgSlug, $repo), $updatesIndex);
}
foreach (glob($PLUGIN_PATH . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
    $fileSlug = basename($file, '.php');
    if ($fileSlug === 'index' || PluginLoader::isShadowed($file)) {
        continue;
    }
    $name = $fileSlug;
    $code = @file_get_contents($file);
    if ($code && preg_match('/register_menu\(\s*[\'"]([^\'"]+)[\'"]/', $code, $m)) {
        $name = $m[1];
    }
    $allPlugins[] = MarketplaceController::installedCard('plugin', $fileSlug, [
        'name' => $name,
        'version' => 'legacy',
        'description' => 'Legacy flat plugin (' . $fileSlug . '.php)',
        'author' => '',
        'active' => true,
        'type' => 'legacy',
    ], $file, [], MarketplaceUpdates::matchStore('plugin', $fileSlug, $repo), $updatesIndex);
}
usort($allPlugins, fn($a, $b) => strcasecmp($a['name'], $b['name']));

$installedIds = [];
foreach ($allPlugins as $p) {
    $installedIds[strtolower($p['slug'])] = true;
    if (!empty($p['store']['id'])) {
        $installedIds[strtolower($p['store']['id'])] = true;
    }
}
$available = [];
foreach ($repo['plugins'] as $item) {
    if (!isset($installedIds[strtolower($item['id'])])) {
        $available[] = MarketplaceController::storeCard('plugin', $item, $watch);
    }
}

$stats = ['total' => count($allPlugins), 'active' => 0, 'inactive' => 0, 'folder' => 0, 'legacy' => 0, 'updates' => 0, 'errors' => 0, 'watched' => 0];
foreach ($allPlugins as $p) {
    $stats[$p['type'] === 'legacy' ? 'legacy' : 'folder']++;
    $stats[$p['active'] ? 'active' : 'inactive']++;
    $stats['updates'] += $p['update']['available'] ? 1 : 0;
    $stats['errors'] += $p['health']['status'] === 'error' ? 1 : 0;
}
foreach ($available as $a) {
    $stats['watched'] += $a['watched'] ? 1 : 0;
}

MarketplaceController::assignCommon('plugin', 'plugins');
$ui->assign('plugins', $allPlugins);
$ui->assign('available', $available);
$ui->assign('stats', $stats);
$ui->display('admin/marketplace/marketplace-plugins.tpl');
