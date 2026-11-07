<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

$admin = MarketplaceController::guard('Marketplace');

$action = $routes['1'] ?? '';

// Dispatch sub-modules: marketplace/plugins, gateways, themes, devices
$modules = [
    'plugins' => 'marketplace_plugins', 'plugin' => 'marketplace_plugins',
    'gateways' => 'marketplace_gateways', 'gateway' => 'marketplace_gateways',
    'themes' => 'marketplace_themes', 'theme' => 'marketplace_themes',
    'devices' => 'marketplace_devices', 'device' => 'marketplace_devices',
];
if (isset($modules[$action])) {
    include $root_path . File::pathFixer('system/controllers/' . $modules[$action] . '.php');
    exit;
}

if (!function_exists('_mp_json')) {
    function _mp_json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ---- repository sync / update check ------------------------------------------
if ($action === 'sync' || $action === 'check') {
    $from = _get('from');
    $backTo = in_array($from, ['plugins', 'gateways', 'themes', 'devices', 'tools'], true) ? 'marketplace/' . $from : 'marketplace';
    if ($_app_stage == 'Demo') {
        r2(getUrl($backTo), 'e', Lang::T('You cannot perform this action in Demo mode'));
    }
    $result = MarketplaceUpdates::run(true, false);
    $states = array_column(MarketplaceRepo::status(), 'state');
    $n = count($result['updates']);
    Extensions::log('sync', 'plugin', 'repositories', $n . ' update(s) found');
    if (in_array('error', $states, true) || in_array('stale', $states, true)) {
        r2(getUrl($backTo), 'w', Lang::T('Could not reach every repository. Using local cache where available.'));
    }
    r2(getUrl($backTo), 's', Lang::T('Marketplace repository synchronized successfully.') . ($n ? ' ' . $n . ' ' . Lang::T('update(s) available.') : ''));
}

// ---- update everything that has an update --------------------------------------
if ($action === 'updateall') {
    MarketplaceController::protect('marketplace');
    set_time_limit(0);
    $ok = 0;
    $failed = [];
    foreach (MarketplaceUpdates::cached()['updates'] as $u) {
        $res = MarketplaceController::updateOne($u['type'], $u['slug']);
        if (!empty($res['ok'])) {
            $ok++;
        } else {
            $failed[] = $u['slug'] . ': ' . $res['message'];
        }
    }
    if ($failed) {
        r2(getUrl('marketplace'), 'w', $ok . ' ' . Lang::T('updated.') . ' ' . Lang::T('Failed:') . ' ' . implode('; ', $failed));
    }
    r2(getUrl('marketplace'), $ok ? 's' : 'w', $ok ? $ok . ' ' . Lang::T('extension(s) updated.') : Lang::T('Nothing to update.'));
}

// ---- JSON endpoints used by the page scripts --------------------------------------
if ($action === 'watch') {
    if (!Csrf::check(_post('csrf_token'))) {
        _mp_json(['ok' => false, 'message' => 'csrf'], 403);
    }
    $key = strtolower((string) _post('key'));
    $on = MarketplaceController::toggleWatch($key);
    _mp_json(['ok' => true, 'watched' => $on, 'count' => count(MarketplaceController::watchlist())]);
}
if ($action === 'readme') {
    // accepts only a GitHub "owner/name" identifier
    $ownerName = Extensions::githubSlug((string) _get('repo'));
    _mp_json(['ok' => $ownerName !== null, 'html' => $ownerName === null ? '' : Extensions::readmeHtml($ownerName)]);
}

// ---- tools: settings, backups, activity -------------------------------------------
if ($action === 'tools') {
    $sub = $routes['2'] ?? '';
    $arg = $routes['3'] ?? '';
    if ($sub === 'download') { // GET, admin only; the file name is whitelisted by ExtensionBackup::path()
        $p = ExtensionBackup::path($arg);
        if ($p === null) {
            r2(getUrl('marketplace/tools'), 'e', Lang::T('Backup not found'));
        }
        Extensions::log('download-backup', 'plugin', $arg);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $arg . '"');
        header('Content-Length: ' . filesize($p));
        readfile($p);
        exit;
    }
    if (in_array($sub, ['settings', 'restore', 'delete', 'backup'], true)) {
        MarketplaceController::protect('marketplace/tools');
        switch ($sub) {
            case 'settings':
                $repos = [];
                foreach (preg_split('/\R+/', (string) _post('marketplace_repos')) as $line) {
                    $line = trim($line);
                    if ($line !== '' && MarketplaceRepo::validUrl($line)) {
                        $repos[] = $line;
                    }
                }
                Extensions::setConfig('marketplace_repos', implode("\n", array_unique($repos)));
                Extensions::setConfig('marketplace_official', _post('marketplace_official') === 'yes' ? 'yes' : 'no');
                Extensions::setConfig('marketplace_notify', _post('marketplace_notify') === 'yes' ? 'yes' : 'no');
                Extensions::setConfig('marketplace_backup_keep', (string) max(1, min(50, (int) _post('marketplace_backup_keep'))));
                MarketplaceRepo::sync();
                Extensions::log('settings', 'plugin', 'marketplace', count($repos) . ' custom repo(s)');
                r2(getUrl('marketplace/tools'), 's', Lang::T('Marketplace settings saved.'));
                break;
            case 'restore':
                $res = ExtensionBackup::restore($arg);
                if ($res['ok']) {
                    Extensions::log('restore', $res['type'], $res['slug'], $arg);
                    GatewayLoader::forget();
                    DeviceLoader::forget();
                }
                r2(getUrl('marketplace/tools'), $res['ok'] ? 's' : 'e', $res['ok'] ? Lang::T('Backup restored.') : Lang::T($res['message']));
                break;
            case 'delete':
                $ok = ExtensionBackup::delete($arg);
                r2(getUrl('marketplace/tools'), $ok ? 's' : 'e', $ok ? Lang::T('Backup deleted.') : Lang::T('Backup not found'));
                break;
            case 'backup': // manual snapshot: tools/backup/<type>:<slug>
                [$bt, $bs] = array_pad(explode(':', $arg, 2), 2, '');
                if (!Extensions::isType($bt) || !Extensions::validSlug($bs)) {
                    r2(getUrl('marketplace/tools'), 'e', Lang::T('Invalid name.'));
                }
                $b = ExtensionBackup::create($bt, $bs, 'manual');
                if ($b['ok'] && $b['file']) {
                    Extensions::log('backup', $bt, $bs, $b['file']);
                }
                r2(getUrl('marketplace/tools'), $b['ok'] && $b['file'] ? 's' : 'e', $b['ok'] && $b['file'] ? Lang::T('Backup created') : Lang::T($b['message']));
                break;
        }
    }

    // installed extensions for the "Back up now" selector
    $installedList = [];
    foreach (Extensions::TYPES as $t) {
        foreach (MarketplaceUpdates::installed($t) as $s => $info) {
            $installedList[] = ['type' => $t, 'slug' => $s, 'name' => $info['name']];
        }
    }
    $ui->assign('active_tab', 'tools');
    $ui->assign('csrf_token', Csrf::generateAndStoreToken());
    $ui->assign('repos_text', (string) Extensions::config('marketplace_repos', ''));
    $ui->assign('official_on', Extensions::config('marketplace_official', 'yes') !== 'no');
    $ui->assign('notify_on', Extensions::config('marketplace_notify', 'yes') !== 'no');
    $ui->assign('backup_keep', ExtensionBackup::keep());
    $ui->assign('repo_status', MarketplaceRepo::status());
    $ui->assign('backups', ExtensionBackup::all());
    try {
        $logs = Paginator::findMany(Extensions::logsQuery(), [], 15, '', true);
    } catch (Throwable $e) {
        $logs = [];
    }
    $ui->assign('logs', $logs ?: []);
    $ui->assign('installed_list', $installedList);
    $ui->assign('updates_pending', count(MarketplaceUpdates::cached()['updates']));
    $ui->assign('zipExt', class_exists('ZipArchive'));
    $ui->display('admin/marketplace/marketplace-tools.tpl');
    exit;
}

// ---- hub -------------------------------------------------------------------------
$repo = MarketplaceRepo::load();
$cached = MarketplaceUpdates::cached();

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
    if (basename($file, '.php') !== 'index' && !PluginLoader::isShadowed($file)) {
        $legacyCount++;
    }
}
// 2. Themes
$themesDir = Extensions::root('theme');
$themeCount = is_dir($themesDir) ? count(glob($themesDir . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'theme.json') ?: []) : 0;
$activeTheme = $config['theme'] ?? 'default';
// 3. Devices / gateways
$deviceCount = count(DeviceLoader::names());
$gatewayCount = count(GatewayLoader::names());
$activeGatewaysCount = count(array_filter(array_map('trim', explode(',', $config['payment_gateway'] ?? ''))));

// 4. Problems: extensions whose PHP does not even parse, or that failed to load
$problems = [];
foreach (Extensions::TYPES as $t) {
    foreach (MarketplaceUpdates::installed($t) as $s => $info) {
        if ($info['main']) {
            $manifest = $t === 'plugin' ? (PluginLoader::$plugins[$s] ?? []) : Extensions::manifest($t, $s);
            $h = Extensions::health($t, $s, $info['main'], $manifest);
            if ($h['status'] === 'error') {
                $problems[] = ['type' => $t, 'slug' => $s, 'name' => $info['name'], 'message' => $h['messages'][0] ?? ''];
            }
        }
    }
}

// 5. Featured items (latest from each store section)
$watch = MarketplaceController::watchlist();
$spotlight = [];
foreach ([['plugin', 'Plugin', 'mp-badge--folder'], ['gateway', 'Gateway', 'mp-badge--gateway'], ['device', 'Device', 'mp-badge--device'], ['theme', 'Theme', 'mp-badge--theme']] as [$t, $label, $badge]) {
    $items = $repo[Extensions::repoKey($t)] ?? [];
    usort($items, fn($a, $b) => strcmp($b['last_update'] ?? '', $a['last_update'] ?? ''));
    foreach (array_slice($items, 0, $t === 'theme' ? 1 : 2) as $it) {
        $it['category_type'] = $label;
        $it['badge_class'] = $badge;
        $it['target_url'] = Text::url('marketplace/' . Extensions::route($t));
        $it['ispaid'] = !empty($it['ispaid']);
        $it['watched'] = in_array($t . ':' . strtolower($it['id']), $watch, true);
        $it['watch_key'] = $t . ':' . strtolower($it['id']);
        $spotlight[] = $it;
    }
}

// 6. Watchlist
$watched = [];
foreach ($watch as $wk) {
    [$wt, $wid] = array_pad(explode(':', $wk, 2), 2, '');
    if (Extensions::isType($wt) && ($item = MarketplaceRepo::find($wt, $wid))) {
        $item['category_type'] = Extensions::label($wt);
        $item['target_url'] = Text::url('marketplace/' . Extensions::route($wt));
        $item['watch_key'] = $wk;
        $item['watched'] = true;
        $item['ispaid'] = !empty($item['ispaid']);
        $watched[] = $item;
    }
}

// 7. Searchable catalog (installed + store)
$catalog = [];
foreach (Extensions::TYPES as $t) {
    foreach (MarketplaceUpdates::installed($t) as $s => $info) {
        $catalog[] = ['ctype' => $t, 'type' => Extensions::label($t), 'id' => $s, 'name' => (string) $info['name'], 'desc' => '', 'state' => 'installed',
            'url' => Text::url('marketplace/' . Extensions::route($t)), 'paid' => false, 'author' => ''];
    }
}
foreach (Extensions::TYPES as $t) {
    foreach ($repo[Extensions::repoKey($t)] ?? [] as $it) {
        $catalog[] = ['ctype' => $t, 'type' => Extensions::label($t), 'id' => (string) $it['id'], 'name' => (string) ($it['name'] ?? ''),
            'desc' => mb_substr(strip_tags((string) ($it['description'] ?? '')), 0, 140), 'state' => 'store',
            'url' => Text::url('marketplace/' . Extensions::route($t)), 'paid' => !empty($it['ispaid']), 'author' => (string) ($it['author'] ?? '')];
    }
}
$catalogJson = json_encode($catalog, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?: '[]';

$lastSync = MarketplaceRepo::lastSync();
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
    'last_sync' => $lastSync > 0 ? date('M j, Y g:i A', $lastSync) : Lang::T('Never'),
    'repo_online' => MarketplaceRepo::online(),
    'repo_stale' => $lastSync > 0 && (time() - $lastSync) > (7 * 24 * 60 * 60),
    'checked' => $cached['checked'] > 0 ? date('M j, Y g:i A', $cached['checked']) : Lang::T('Never'),
];

$stats = [
    'plugins' => $folderCount + $legacyCount,
    'plugins_folder' => $folderCount,
    'plugins_active' => $activeCount,
    'plugins_legacy' => $legacyCount,
    'plugins_repo' => count($repo['plugins']),
    'themes' => $themeCount,
    'active_theme' => $activeTheme,
    'devices' => $deviceCount,
    'devices_repo' => count($repo['devices']),
    'gateways' => $gatewayCount,
    'gateways_active' => $activeGatewaysCount,
    'gateways_repo' => count($repo['payment_gateway']),
    'repo_total' => count($repo['plugins']) + count($repo['payment_gateway']) + count($repo['devices']) + count($repo['themes']),
    'total_extensions' => ($folderCount + $legacyCount) + $themeCount + $deviceCount + $gatewayCount,
    'updates' => count($cached['updates']),
];

$ui->assign('active_tab', 'hub');
$ui->assign('stats', $stats);
$ui->assign('spotlight', $spotlight);
$ui->assign('watched', $watched);
$ui->assign('pending_updates', $cached['updates']);
$ui->assign('problems', $problems);
$ui->assign('repo_status', MarketplaceRepo::status());
$ui->assign('catalog_json', $catalogJson);
$ui->assign('diagnostics', $diagnostics);
$ui->assign('updates_pending', $stats['updates']);
$ui->assign('csrf_token', Csrf::generateAndStoreToken());
$ui->display('admin/marketplace/marketplace.tpl');
