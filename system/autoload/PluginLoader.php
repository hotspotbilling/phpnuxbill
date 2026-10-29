<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * Folder plugin loader.
 *
 * Layout: system/plugin/<slug>/plugin.json + <slug>.php (+ controllers/, includes/, ui/, assets/, lan/)
 * Legacy flat plugins (system/plugin/*.php) are still loaded by init.php; a legacy file is skipped
 * when a folder plugin with the same slug exists.
 * Enabled/disabled state lives in system/cache/plugins.json (init.php loads plugins before tbl_appconfig).
 */
class PluginLoader
{
    /** @var array slug => manifest (+ 'path', 'active') */
    public static $plugins = [];
    /** @var array template dirs contributed by active folder plugins */
    public static $templateDirs = [];
    /** @var array slug => message for folder plugins whose main file threw while loading (shown as health in the Marketplace) */
    public static $errors = [];

    private static function stateFile(): string
    {
        global $CACHE_PATH;
        return $CACHE_PATH . DIRECTORY_SEPARATOR . 'plugins.json';
    }

    public static function getState(): array
    {
        $f = self::stateFile();
        $s = file_exists($f) ? json_decode(file_get_contents($f), true) : [];
        return is_array($s) ? $s : [];
    }

    public static function setActive(string $slug, bool $active): bool
    {
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $slug)) {
            return false;
        }
        $s = self::getState();
        $s['disabled'] = array_values(array_diff($s['disabled'] ?? [], [$slug]));
        if (!$active) {
            $s['disabled'][] = $slug;
        }
        return file_put_contents(self::stateFile(), json_encode($s)) !== false;
    }

    /**
     * Run a folder plugin's install.php lifecycle hook (on activate).
     * @return array ['success'=>bool, 'message'=>string]
     */
    public static function runInstall(string $slug): array
    {
        global $PLUGIN_PATH;
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $slug)) {
            return ['success' => false, 'message' => 'Invalid slug'];
        }
        $install = $PLUGIN_PATH . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'install.php';
        if (!is_file($install)) {
            return ['success' => true, 'message' => 'No install.php'];
        }
        try {
            include_once $install;
            return ['success' => true, 'message' => 'install.php executed'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'install.php error: ' . $e->getMessage()];
        }
    }

    /**
     * Run a folder plugin's uninstall.php lifecycle hook (on delete).
     * @return array ['success'=>bool, 'message'=>string]
     */
    public static function runUninstall(string $slug): array
    {
        global $PLUGIN_PATH;
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $slug)) {
            return ['success' => false, 'message' => 'Invalid slug'];
        }
        $uninstall = $PLUGIN_PATH . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'uninstall.php';
        if (!is_file($uninstall)) {
            return ['success' => true, 'message' => 'No uninstall.php'];
        }
        try {
            include_once $uninstall;
            return ['success' => true, 'message' => 'uninstall.php executed'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'uninstall.php error: ' . $e->getMessage()];
        }
    }

    /**
     * Check if a folder plugin has an update available via its update_url.
     * The update_url should return JSON with at least a "version" field
     * (e.g. GitHub releases API: https://api.github.com/repos/user/repo/releases/latest).
     *
     * @param array $plugin Plugin manifest array (must have 'version' and optionally 'update_url')
     * @return array ['update_available'=>bool, 'latest_version'=>string|null, 'installed_version'=>string, 'update_url'=>string|null]
     */
    public static function updateAvailable(array $plugin): array
    {
        $installed = $plugin['version'] ?? '1.0.0';
        $updateUrl = $plugin['update_url'] ?? null;

        if (!$updateUrl || !is_string($updateUrl) || $updateUrl === '') {
            return [
                'update_available' => false,
                'latest_version' => null,
                'installed_version' => $installed,
                'update_url' => null,
            ];
        }

        // The remote answer is cached so page views never wait on the network (6 hours; failures are cached too)
        global $CACHE_PATH;
        $cacheFile = $CACHE_PATH . DIRECTORY_SEPARATOR . 'plugin_update_' . md5($updateUrl) . '.json';
        $ttl = 6 * 3600;
        $latest = null;
        $cached = is_file($cacheFile) ? json_decode((string) file_get_contents($cacheFile), true) : null;
        if (is_array($cached) && isset($cached['t']) && (time() - (int) $cached['t']) < $ttl) {
            $latest = $cached['latest'] ?? null;
        } else {
            try {
                $data = Http::getData($updateUrl, [], 5000, 10000);
                if ($data !== false && $data !== '') {
                    $json = json_decode($data, true);
                    if (is_array($json)) {
                        // Support GitHub releases API format (tag_name) and generic format (version)
                        $raw = $json['tag_name'] ?? $json['version'] ?? null;
                        $latest = is_string($raw) || is_numeric($raw) ? (string) $raw : null;
                        if ($latest && str_starts_with($latest, 'v')) {
                            $latest = substr($latest, 1);
                        }
                    }
                }
            } catch (Throwable $e) {
                // Silently fail — update check is non-critical
            }
            @file_put_contents($cacheFile, json_encode(['t' => time(), 'latest' => $latest]), LOCK_EX);
        }

        $available = false;
        if ($latest && version_compare($installed, $latest, '<')) {
            $available = true;
        }

        return [
            'update_available' => $available,
            'latest_version' => $latest,
            'installed_version' => $installed,
            'update_url' => $updateUrl,
        ];
    }

    /** Is a legacy flat plugin file shadowed by a folder plugin? */
    public static function isShadowed(string $legacyFile): bool
    {
        global $PLUGIN_PATH;
        $slug = basename($legacyFile, '.php');
        return file_exists($PLUGIN_PATH . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'plugin.json');
    }

    /** Scan folders, read manifests and include the main file of active plugins */
    public static function loadAll(): void
    {
        global $PLUGIN_PATH;
        $disabled = self::getState()['disabled'] ?? [];
        foreach (glob($PLUGIN_PATH . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'plugin.json') ?: [] as $manifestFile) {
            $dir = dirname($manifestFile);
            $slug = basename($dir);
            if ($slug === 'ui') {
                continue;
            }
            // a plugin loaded earlier in this loop may have retired this one (renamed its manifest): skip it
            if (!is_file($manifestFile)) {
                continue;
            }
            $m = json_decode(file_get_contents($manifestFile), true);
            if (!is_array($m)) {
                continue;
            }
            $m['slug'] = $slug;
            $m['path'] = $dir;
            $m['active'] = !in_array($slug, $disabled, true);
            self::$plugins[$slug] = $m;
            if (!$m['active']) {
                continue;
            }
            if (!empty($m['requires_php']) && version_compare(PHP_VERSION, $m['requires_php'], '<')) {
                continue;
            }
            // a plugin that needs a licence is not loaded until a valid key is saved (Marketplace > the plugin's card)
            if (!empty($m['requires_license']) && Extensions::licenseMissing('plugin', $slug, $m)) {
                self::$plugins[$slug]['license_blocked'] = true;
                continue;
            }
            if (is_dir($dir . DIRECTORY_SEPARATOR . 'ui')) {
                self::$templateDirs[] = $dir . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR;
            }
            $main = $dir . DIRECTORY_SEPARATOR . ($m['main'] ?? ($slug . '.php'));
            if (is_file($main) && realpath($main) !== false && strpos(realpath($main), realpath($dir)) === 0) {
                try {
                    include_once $main;
                } catch (Throwable $e) {
                    // never break the app for one plugin, but remember why it failed
                    self::$errors[$slug] = get_class($e) . ': ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')';
                }
            }

            // Declared routes from plugin.json: "routes": [{"function": "x_webhook", "access": "public"}] (a plain string
            // means an admin-only route)
            if (!empty($m['routes']) && is_array($m['routes']) && function_exists('register_route')) {
                foreach ($m['routes'] as $route) {
                    if (is_string($route)) {
                        register_route($route, 'admin');
                    } elseif (is_array($route) && !empty($route['function'])) {
                        register_route($route['function'], $route['access'] ?? 'admin');
                    }
                }
            }

            // Declarative menus from plugin.json manifest
            if (!empty($m['menus']) && is_array($m['menus'])) {
                foreach ($m['menus'] as $mi) {
                    if (!is_array($mi)) {
                        continue;
                    }
                    // "feature": hide this menu unless the plugin's licence (register_license) unlocks that feature, or any
                    // valid licence for "licensed". Only hides the entry: the page itself must check license_has() too.
                    if (!empty($mi['feature']) && is_string($mi['feature']) && !Extensions::licenseHas('plugin', $slug, $mi['feature'])) {
                        continue;
                    }
                    $type = $mi['type'] ?? 'menu';
                    $name = $mi['name'] ?? '';
                    $admin = !empty($mi['admin']);
                    $function = $mi['function'] ?? '';
                    $position = $mi['position'] ?? 'AFTER_DASHBOARD';
                    $icon = $mi['icon'] ?? '';
                    $label = $mi['label'] ?? '';
                    $color = $mi['color'] ?? 'success';
                    $auth = $mi['auth'] ?? [];
                    if (!is_array($auth)) {
                        $auth = [];
                    }
                    if ($type === 'submenu') {
                        $parent = $mi['parent'] ?? '';
                        if ($parent !== '' && $function !== '') {
                            register_submenu($parent, $name, $admin, $function, $position, $icon, $label, $color, $auth);
                        }
                    } else {
                        if ($function !== '') {
                            register_menu($name, $admin, $function, $position, $icon, $label, $color, $auth);
                        }
                    }
                }
            }

        }
    }

    /**
     * Convert a legacy flat plugin (system/plugin/<slug>.php + ui/<slug>*.tpl) into folder style.
     * Non-destructive: files are copied; the legacy file is then shadowed by the folder plugin.
     * Templates are matched by name prefix (<slug>*.tpl) and by 'xxx.tpl' literals in the main file.
     * @return array ['success'=>bool,'message'=>string,'path'=>string]
     */
    public static function convertLegacy(string $slug, ?string $targetRoot = null): array
    {
        global $PLUGIN_PATH;
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $slug)) {
            return ['success' => false, 'message' => 'Invalid slug'];
        }
        $src = $PLUGIN_PATH . DIRECTORY_SEPARATOR . $slug . '.php';
        if (!is_file($src)) {
            return ['success' => false, 'message' => 'Legacy plugin not found'];
        }
        $dest = rtrim($targetRoot ?? $PLUGIN_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $slug;
        if (file_exists($dest . DIRECTORY_SEPARATOR . 'plugin.json')) {
            return ['success' => false, 'message' => 'Already converted'];
        }
        $code = file_get_contents($src);
        $uiSrc = $PLUGIN_PATH . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR;
        $tpls = [];
        foreach (Extensions::flatTemplates('plugin', $slug) as $t) {
            $tpls[basename($t)] = true;
        }
        if (preg_match_all('/[\'"]([A-Za-z0-9_\-\/]+\.tpl)[\'"]/', $code, $mm)) {
            foreach ($mm[1] as $t) {
                if (is_file($uiSrc . $t)) {
                    $tpls[$t] = true;
                }
            }
        }
        if (!is_dir($dest . DIRECTORY_SEPARATOR . 'ui') && !mkdir($dest . DIRECTORY_SEPARATOR . 'ui', 0755, true)) {
            return ['success' => false, 'message' => 'Cannot create ' . $dest];
        }
        foreach (['controllers', 'includes', 'assets', 'lan'] as $d) {
            @mkdir($dest . DIRECTORY_SEPARATOR . $d, 0755, true);
            file_put_contents($dest . DIRECTORY_SEPARATOR . $d . DIRECTORY_SEPARATOR . 'index.html', '');
        }
        copy($src, $dest . DIRECTORY_SEPARATOR . $slug . '.php');
        foreach (array_keys($tpls) as $t) {
            $to = $dest . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . $t;
            if (!is_dir(dirname($to))) {
                mkdir(dirname($to), 0755, true);
            }
            copy($uiSrc . $t, $to);
        }
        $name = $slug;
        if (preg_match('/register_menu\(\s*[\'"]([^\'"]+)[\'"]/', $code, $n)) {
            $name = $n[1];
        }
        $manifest = [
            'name' => $name,
            'slug' => $slug,
            'version' => '1.0.0',
            'description' => '',
            'author' => '',
            'requires_php' => '8.2',
            'main' => $slug . '.php',
            'converted_from_legacy' => true,
        ];
        file_put_contents($dest . DIRECTORY_SEPARATOR . 'plugin.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return ['success' => true, 'message' => 'Converted ' . count($tpls) . ' templates', 'path' => $dest];
    }

    /**
     * Remove a legacy flat plugin (system/plugin/<slug>.php + matching ui/<slug>*.tpl templates).
     * @return array ['success'=>bool,'message'=>string]
     */
    public static function uninstallLegacy(string $slug): array
    {
        global $PLUGIN_PATH;
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $slug)) {
            return ['success' => false, 'message' => 'Invalid slug'];
        }
        $src = $PLUGIN_PATH . DIRECTORY_SEPARATOR . $slug . '.php';
        if (!is_file($src)) {
            return ['success' => false, 'message' => 'Legacy plugin not found'];
        }
        if (self::isShadowed($src)) {
            return ['success' => false, 'message' => 'This legacy plugin is shadowed by a folder plugin'];
        }
        $removed = 0;
        if (unlink($src)) {
            $removed++;
        }
        // only templates owned by this slug ("<slug>.tpl" or "<slug>_*.tpl"), never another legacy plugin's
        foreach (Extensions::flatTemplates('plugin', $slug) as $t) {
            if (unlink($t)) {
                $removed++;
            }
        }
        return ['success' => true, 'message' => 'Removed ' . $removed . ' file(s)'];
    }
}
