<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * Shared behaviour for the marketplace_* controllers: access guard, CSRF, the actions that work the
 * same for every extension type (install / update / upload / uninstall / convert / licence),
 * card data builders and the per-admin watchlist.
 */
class MarketplaceController
{
    /** Admin/SuperAdmin only. Returns the admin record. */
    public static function guard(string $title, string $menu = 'marketplace')
    {
        global $ui;
        _admin();
        $ui->assign('_title', Lang::T($title));
        $ui->assign('_system_menu', $menu);
        $admin = Admin::_info();
        $ui->assign('_admin', $admin);
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
        }
        return $admin;
    }

    /** Demo-mode and CSRF protection for a state changing request */
    public static function protect(string $backRoute): void
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            r2(getUrl($backRoute), 'e', Lang::T('You cannot perform this action in Demo mode'));
        }
        if (!Csrf::check(_post('csrf_token') ?: _get('csrf_token'))) {
            r2(getUrl($backRoute), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
        }
    }

    /** Variables every marketplace page needs */
    public static function assignCommon(string $type, string $tab): void
    {
        global $ui;
        $ui->assign('active_tab', $tab);
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->assign('zipExt', class_exists('ZipArchive'));
        $ui->assign('writable', Extensions::isWritable($type));
        $ui->assign('ext_type', $type);
        $ui->assign('updates_pending', count(MarketplaceUpdates::cached()['updates']));
    }

    /** Redirect with a message built from an installer / backup result */
    public static function finish(string $route, array $res, string $okMessage): void
    {
        $back = getUrl('marketplace/' . $route);
        if (empty($res['ok'])) {
            r2($back, 'e', Lang::T($res['message'] ?? 'Failed'));
        }
        $msg = $okMessage;
        if (!empty($res['backups'])) {
            $msg .= ' ' . Lang::T('Previous version backed up.');
        } elseif (!empty($res['backup'])) {
            $msg .= ' ' . Lang::T('A backup was saved.');
        }
        if (!empty($res['warnings'])) {
            r2($back, 'w', $msg . ' ' . implode(' ', array_map(fn($w) => Lang::T($w), $res['warnings'])));
        }
        r2($back, 's', $msg);
    }

    /**
     * Actions that behave identically for every type. Redirects and exits when it handled the action.
     */
    public static function common(string $type, string $action, string $slug): void
    {
        $route = Extensions::route($type);
        $label = Lang::T(Extensions::label($type));
        $stateChanging = ['install', 'update', 'upload', 'uninstall', 'convert', 'license'];
        if (!in_array($action, $stateChanging, true)) {
            return;
        }
        self::protect('marketplace/' . $route);

        if ($action === 'upload') {
            self::upload($type, $route, $label);
        }
        if ($slug === '' || !Extensions::validSlug($slug)) {
            r2(getUrl('marketplace/' . $route), 'e', Lang::T('Invalid name.'));
        }
        switch ($action) {
            case 'install':
                $res = ExtensionInstaller::fromStore($type, $slug, 'install');
                self::afterInstall($res);
                self::finish($route, $res, $label . ' ' . $slug . ' ' . Lang::T('has been installed.'));
                break;

            case 'update':
                $res = self::updateOne($type, $slug);
                self::afterInstall($res, true);
                self::finish($route, $res, $label . ' ' . $slug . ' ' . Lang::T('has been updated.'));
                break;

            case 'uninstall':
                $res = ExtensionInstaller::uninstall($type, $slug);
                if (!empty($res['ok'])) {
                    MarketplaceUpdates::dropCached($type, $slug);
                }
                self::finish($route, $res, $label . ' ' . $slug . ' ' . Lang::T('has been uninstalled.'));
                break;

            case 'convert':
                if ($type === 'plugin') {
                    $r = PluginLoader::convertLegacy($slug);
                    $res = ['ok' => $r['success'], 'message' => $r['message']];
                    if ($r['success']) {
                        Extensions::log('convert', 'plugin', $slug, 'flat -> folder');
                    }
                } else {
                    $res = ExtensionInstaller::convert($type, $slug);
                }
                self::finish($route, $res, Lang::T('Converted to folder style.'));
                break;

            case 'license':
                $manifest = $type === 'plugin' ? (PluginLoader::$plugins[$slug] ?? []) : Extensions::manifest($type, $slug);
                $st = Extensions::saveLicense($type, $slug, (string) _post('license_key'), $manifest);
                Extensions::log('license', $type, $slug, $st['state']);
                $level = in_array($st['state'], ['valid', 'unchecked', 'none'], true) ? 's' : 'e';
                r2(getUrl('marketplace/' . $route), $level, Lang::T($st['message'] ?? 'Saved'));
                break;
        }
    }

    /**
     * Re-install an installed extension from its store entry (or its manifest "zip" or "github" URL),
     * backing the current version up first. Used by the Update button and "Update all".
     * @return array installer result
     */
    public static function updateOne(string $type, string $slug): array
    {
        $store = MarketplaceUpdates::matchStore($type, $slug);
        $manifest = $type === 'plugin' ? (PluginLoader::$plugins[$slug] ?? []) : Extensions::manifest($type, $slug);
        $source = ['zip' => Extensions::httpsUrl($manifest['zip'] ?? ''), 'github' => $manifest['github'] ?? ''];
        if ($store) {
            $res = ExtensionInstaller::fromStore($type, $store['id'], 'update', $slug);
        } elseif ($source['zip'] !== '' || $source['github'] !== '') {
            $dl = ExtensionInstaller::downloadSource($source);
            if (!$dl['ok']) {
                return $dl;
            }
            try {
                $res = ExtensionInstaller::install($type, $dl['dir'], ['reason' => 'update', 'source' => $source['zip'] !== '' ? 'zip' : 'github', 'slug' => $slug]);
            } finally {
                Extensions::deleteTree($dl['work']);
            }
        } else {
            return ['ok' => false, 'message' => 'No update source is known for this extension'];
        }
        if (!empty($res['ok'])) {
            MarketplaceUpdates::dropCached($type, $slug);
        }
        return $res;
    }

    /** Folder plugins run their install.php once after installation */
    private static function afterInstall(array $res, bool $isUpdate = false): void
    {
        if (empty($res['ok'])) {
            return;
        }
        foreach ($res['installed'] ?? [] as $i) {
            if ($i['type'] === 'plugin' && $i['mode'] === 'folder' && !$isUpdate) {
                PluginLoader::runInstall($i['slug']);
            }
        }
    }

    private static function upload(string $type, string $route, string $label): void
    {
        global $_app_stage;
        $dl = null;
        $gh = trim((string) _post('gh_url', ''));
        $zipUrl = trim((string) _post('zip_url', ''));
        $fieldNames = ['plugin' => 'zip_plugin', 'gateway' => 'gateway_file', 'device' => 'zip_device', 'theme' => 'zip_theme'];
        $file = $_FILES[$fieldNames[$type]] ?? null;
        if ($zipUrl !== '') {
            $dl = ExtensionInstaller::downloadZip($zipUrl, Extensions::sha256Hex(_post('zip_sha256', '')));
            $source = 'zip';
        } elseif ($gh !== '') {
            $dl = ExtensionInstaller::downloadGithub($gh);
            $source = 'github';
        } elseif ($file && !empty($file['name']) && is_uploaded_file($file['tmp_name'])) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $source = 'upload';
            if ($ext === 'zip') {
                $dl = ExtensionInstaller::fromZipUpload($file['tmp_name']);
            } elseif ($ext === 'php' && in_array($type, ['gateway', 'device'], true)) {
                $dl = ExtensionInstaller::fromPhpUpload($file['tmp_name'], $file['name']);
            } else {
                r2(getUrl('marketplace/' . $route), 'e', Lang::T('Unsupported file type. Upload a .zip package.'));
            }
        } else {
            r2(getUrl('marketplace/' . $route), 'e', Lang::T('Nothing Installed'));
        }
        if (!$dl['ok']) {
            r2(getUrl('marketplace/' . $route), 'e', Lang::T($dl['message']));
        }
        try {
            $res = ExtensionInstaller::install($type, $dl['dir'], ['reason' => 'upload', 'source' => $source, 'slug' => $dl['slug']]);
        } finally {
            Extensions::deleteTree($dl['work']);
        }
        self::afterInstall($res);
        $names = $res['ok'] ? implode(', ', array_column($res['installed'], 'slug')) : '';
        self::finish($route, $res, $label . ' ' . $names . ' ' . Lang::T('has been installed.'));
    }

    // ------------------------------------------------------------ card data

    /** Fields shared by every installed extension card */
    public static function installedCard(string $type, string $slug, array $base, ?string $mainFile, array $manifest, ?array $store, array $updatesIndex): array
    {
        $upd = $updatesIndex[$type . ':' . $slug] ?? null;
        $req = Extensions::licenseProvider($type, $slug) !== null || !empty($manifest['requires_license']) || (!empty($store['ispaid']) && !empty($manifest['license_check_url']));
        $licState = Extensions::licenseStatus($type, $slug)['state'];
        $card = $base + [
            'slug' => $slug,
            'style' => Extensions::isFolder($type, $slug) ? 'folder' : 'flat',
            'health' => Extensions::health($type, $slug, $mainFile, $manifest),
            'update' => $upd ? ['available' => true, 'current' => $upd['current'], 'latest' => $upd['latest']] : ['available' => false],
            'license' => [
                'required' => $req,
                'state' => $licState,
                'has_key' => Extensions::licenseProvider($type, $slug) ? $licState !== 'none' : Extensions::license($type, $slug) !== '',
                'blocked' => Extensions::licenseMissing($type, $slug, $manifest),
            ],
            'store' => $store,
            'github' => Extensions::httpUrl($store['github'] ?? ($manifest['github'] ?? '')),
            'zip' => Extensions::httpsUrl($store['zip'] ?? ($manifest['zip'] ?? '')),
            'url' => Extensions::httpUrl($store['url'] ?? ($manifest['url'] ?? '')),
            'paid' => !empty($store['ispaid']),
        ];
        $card['info_json'] = Extensions::infoJson([
            'kind' => 'installed',
            'type' => $type,
            'slug' => $slug,
            'name' => $base['name'] ?? $slug,
            'author' => $base['author'] ?? '',
            'version' => $base['version'] ?? '',
            'description' => $base['description'] ?? '',
            'url' => $card['url'],
            'github' => $card['github'],
            'zip' => $card['zip'],
            'updated' => $store['last_update'] ?? '',
            'paid' => $card['paid'],
            'style' => $card['style'],
            'health' => $card['health'],
            'update' => $card['update'],
            'license' => $card['license'],
        ]);
        return $card;
    }

    /** Store card: adds watchlist state and the data attribute used by the details drawer */
    public static function storeCard(string $type, array $item, array $watch): array
    {
        $key = $type . ':' . strtolower($item['id'] ?? '');
        $item['watched'] = in_array($key, $watch, true);
        $item['watch_key'] = $key;
        $item['info_json'] = Extensions::infoJson([
            'kind' => 'store',
            'type' => $type,
            'id' => $item['id'] ?? '',
            'name' => $item['name'] ?? '',
            'author' => $item['author'] ?? '',
            'description' => $item['description'] ?? '',
            'url' => $item['url'] ?? '',
            'github' => $item['github'] ?? '',
            'zip' => $item['zip'] ?? '',
            'updated' => $item['last_update'] ?? '',
            'paid' => !empty($item['ispaid']),
            'repo' => $item['repo'] ?? '',
        ]);
        return $item;
    }

    // ------------------------------------------------------------ watchlist

    private static function watchKey(): string
    {
        global $admin;
        return 'marketplace_watch_' . (int) (is_array($admin) || is_object($admin) ? $admin['id'] : 0);
    }

    /** @return string[] entries like "gateway:paystack" */
    public static function watchlist(): array
    {
        $j = json_decode((string) Extensions::config(self::watchKey(), '[]'), true);
        return is_array($j) ? array_values(array_filter($j, 'is_string')) : [];
    }

    public static function toggleWatch(string $key): bool
    {
        if (!preg_match('/^(plugin|gateway|device|theme):[a-z0-9_.-]+$/', $key)) {
            return false;
        }
        $list = self::watchlist();
        if (in_array($key, $list, true)) {
            $list = array_values(array_diff($list, [$key]));
            $on = false;
        } else {
            $list[] = $key;
            $on = true;
        }
        Extensions::setConfig(self::watchKey(), json_encode($list));
        return $on;
    }
}
