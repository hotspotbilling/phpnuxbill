<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * Shared helpers for every extension type managed by the Marketplace:
 * plugin, gateway (payment gateway), device (driver) and theme.
 *
 * Folder layout (all types):  <root>/<slug>/<type>.json + <slug>.php (+ ui/, assets/, ...)
 * Flat layout (gateway/device/plugin): <root>/<slug>.php (+ templates in <root>/ui/)
 */
class Extensions
{
    public const TYPES = ['plugin', 'gateway', 'device', 'theme'];
    public const LOG_TYPE = 'Marketplace';

    /** @var array path|mtime => lint result cache for this request */
    private static $lintCache = [];

    // ------------------------------------------------------------------ paths

    public static function isType(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }

    public static function validSlug(string $slug): bool
    {
        // "ui", "index" and "readme" are shared folders/files inside the type roots, never extensions
        return (bool) preg_match('/^[A-Za-z0-9_-]+$/', $slug)
            && !in_array(strtolower($slug), ['ui', 'index', 'readme'], true);
    }

    /**
     * Templates (<root>/ui/*.tpl) that belong to a FLAT plugin or gateway: exactly "<slug>.tpl" or "<slug>_*.tpl".
     * A template that matches a longer sibling slug ("hotspot_pg-addpay.tpl" when removing "hotspot") is excluded.
     * @return string[] absolute paths
     */
    public static function flatTemplates(string $type, string $slug): array
    {
        if (!in_array($type, ['plugin', 'gateway'], true) || !self::validSlug($slug)) {
            return [];
        }
        $root = self::root($type);
        // names of every OTHER installed extension (flat files and folder extensions) that is longer than this slug
        $names = [];
        foreach (glob($root . DIRECTORY_SEPARATOR . '*.php') ?: [] as $f) {
            $names[] = basename($f, '.php');
        }
        foreach (glob($root . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . self::manifestName($type)) ?: [] as $mf) {
            $names[] = basename(dirname($mf));
        }
        $others = array_values(array_filter(array_unique($names), fn($s) => $s !== 'index' && $s !== $slug && strlen($s) > strlen($slug)));
        $out = [];
        foreach (glob($root . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . $slug . '*.tpl') ?: [] as $t) {
            $base = basename($t, '.tpl');
            if ($base !== $slug && strpos($base, $slug . '_') !== 0) {
                continue;
            }
            foreach ($others as $o) {
                if ($base === $o || strpos($base, $o . '_') === 0) {
                    continue 2;
                }
            }
            $out[] = $t;
        }
        return $out;
    }

    /** Gateways and devices are called as {slug}_function() / new {Slug}, so they must be identifiers */
    public static function validIdentifier(string $slug): bool
    {
        return (bool) preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $slug);
    }

    public static function root(string $type): string
    {
        global $PLUGIN_PATH, $PAYMENTGATEWAY_PATH, $DEVICE_PATH, $UI_PATH, $root_path;
        switch ($type) {
            case 'plugin':
                return rtrim($PLUGIN_PATH, '/\\');
            case 'gateway':
                return rtrim($PAYMENTGATEWAY_PATH, '/\\');
            case 'device':
                return rtrim($DEVICE_PATH, '/\\');
            case 'theme':
                return rtrim($root_path, '/\\') . DIRECTORY_SEPARATOR . trim(File::pathFixer($UI_PATH . '/themes'), '/\\');
        }
        throw new InvalidArgumentException('Unknown extension type: ' . $type);
    }

    public static function manifestName(string $type): string
    {
        return $type . '.json';
    }

    public static function label(string $type): string
    {
        return ['plugin' => 'Plugin', 'gateway' => 'Payment Gateway', 'device' => 'Device Driver', 'theme' => 'Theme'][$type] ?? $type;
    }

    /** URL segment used by marketplace routes */
    public static function route(string $type): string
    {
        return ['plugin' => 'plugins', 'gateway' => 'gateways', 'device' => 'devices', 'theme' => 'themes'][$type] ?? $type;
    }

    /** key of the repository.json list for a type */
    public static function repoKey(string $type): string
    {
        return ['plugin' => 'plugins', 'gateway' => 'payment_gateway', 'device' => 'devices', 'theme' => 'themes'][$type] ?? $type;
    }

    public static function cacheDir(string $sub = ''): string
    {
        global $CACHE_PATH;
        $dir = rtrim($CACHE_PATH, '/\\') . ($sub !== '' ? DIRECTORY_SEPARATOR . $sub : '');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /** Create a cache subfolder that cannot be listed or fetched over HTTP */
    public static function privateDir(string $sub): string
    {
        $dir = self::cacheDir($sub);
        if (!is_file($dir . DIRECTORY_SEPARATOR . '.htaccess')) {
            @file_put_contents($dir . DIRECTORY_SEPARATOR . '.htaccess', "Require all denied\nDeny from all\n");
            @file_put_contents($dir . DIRECTORY_SEPARATOR . 'index.html', '');
        }
        return $dir;
    }

    public static function isWritable(string $type): bool
    {
        $root = self::root($type);
        global $CACHE_PATH;
        return is_dir($root) && is_writable($root) && is_dir($CACHE_PATH) && is_writable($CACHE_PATH);
    }

    public static function deleteTree(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);
            return;
        }
        if (!is_dir($path)) {
            return;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $p) {
            $p->isDir() && !$p->isLink() ? @rmdir($p->getPathname()) : @unlink($p->getPathname());
        }
        @rmdir($path);
    }

    public static function copyTree(string $from, string $to, array $skip = []): int
    {
        $from = rtrim($from, '/\\');
        $to = rtrim($to, '/\\');
        if (!is_dir($to) && !mkdir($to, 0755, true)) {
            return 0;
        }
        $count = 0;
        foreach (scandir($from) ?: [] as $f) {
            if ($f === '.' || $f === '..' || in_array($f, $skip, true) || $f === '.git') {
                continue;
            }
            $src = $from . DIRECTORY_SEPARATOR . $f;
            if (is_link($src)) {
                continue;
            }
            if (is_dir($src)) {
                $count += self::copyTree($src, $to . DIRECTORY_SEPARATOR . $f, $skip);
            } elseif (copy($src, $to . DIRECTORY_SEPARATOR . $f)) {
                $count++;
            }
        }
        return $count;
    }

    // ----------------------------------------------------------------- config

    public static function setConfig(string $key, string $value): void
    {
        global $config;
        $d = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
        if (!$d) {
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = $key;
        }
        $d->value = $value;
        $d->save();
        $config[$key] = $value;
    }

    public static function config(string $key, $default = '')
    {
        global $config;
        return $config[$key] ?? $default;
    }

    // --------------------------------------------------------------- metadata

    private static function metaFile(): string
    {
        return self::cacheDir() . DIRECTORY_SEPARATOR . 'extensions.json';
    }

    /** Install records: ['plugin' => ['slug' => ['version'=>..., 'repo_updated'=>..., 'installed_at'=>..., 'source'=>...]]] */
    public static function meta(): array
    {
        $f = self::metaFile();
        $m = is_file($f) ? json_decode((string) file_get_contents($f), true) : [];
        return is_array($m) ? $m : [];
    }

    public static function getMeta(string $type, string $slug): array
    {
        return self::meta()[$type][$slug] ?? [];
    }

    public static function setMeta(string $type, string $slug, array $data): void
    {
        $m = self::meta();
        $m[$type][$slug] = array_merge($m[$type][$slug] ?? [], $data);
        @file_put_contents(self::metaFile(), json_encode($m, JSON_PRETTY_PRINT), LOCK_EX);
    }

    public static function removeMeta(string $type, string $slug): void
    {
        $m = self::meta();
        unset($m[$type][$slug]);
        @file_put_contents(self::metaFile(), json_encode($m, JSON_PRETTY_PRINT), LOCK_EX);
    }

    // ---------------------------------------------------------------- logging

    /** Record a marketplace action in tbl_logs (shown on Marketplace > Tools) */
    public static function log(string $action, string $type, string $slug, string $detail = ''): void
    {
        global $admin;
        try {
            $uid = is_object($admin) || is_array($admin) ? (int) ($admin['id'] ?? 0) : 0;
            $name = (is_object($admin) || is_array($admin)) ? ($admin['username'] ?? '') : 'CLI';
            _log(
                '[' . $action . '] ' . self::label($type) . ' ' . $slug
                    . ($detail !== '' ? ' - ' . $detail : '') . ($name !== '' ? ' (by ' . $name . ')' : ''),
                self::LOG_TYPE,
                (string) $uid
            );
        } catch (Throwable $e) {
            // logging must never break an install
        }
    }

    public static function recentLogs(int $limit = 50): array
    {
        try {
            return ORM::for_table('tbl_logs')->where('type', self::LOG_TYPE)
                ->order_by_desc('id')->limit($limit)->find_array();
        } catch (Throwable $e) {
            return [];
        }
    }

    // ------------------------------------------------------------ manifest/meta

    public static function manifest(string $type, string $slug): array
    {
        $file = self::root($type) . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . self::manifestName($type);
        if (!is_file($file)) {
            return [];
        }
        $m = json_decode((string) file_get_contents($file), true);
        return is_array($m) ? $m : [];
    }

    /** Is this extension installed as a folder (<root>/<slug>/<type>.json)? */
    public static function isFolder(string $type, string $slug): bool
    {
        return is_file(self::root($type) . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . self::manifestName($type));
    }

    /** Read "* Key: value" lines from the top docblock of a flat PHP file */
    public static function headerInfo(string $file): array
    {
        $out = [];
        $code = (string) @file_get_contents($file, false, null, 0, 6000);
        foreach (['Title', 'Name', 'Description', 'Author', 'Version'] as $k) {
            if (preg_match('/\*\s*' . $k . '\s*:\s*([^\n\r*]+)/i', $code, $m)) {
                $out[strtolower($k)] = trim($m[1]);
            }
        }
        return $out;
    }

    public static function phpnuxbillVersion(): string
    {
        global $root_path;
        $f = rtrim($root_path, '/\\') . DIRECTORY_SEPARATOR . 'version.json';
        $j = is_file($f) ? json_decode((string) file_get_contents($f), true) : [];
        return (string) ($j['version'] ?? '0');
    }

    // ----------------------------------------------------------- requirements

    /**
     * Compare a manifest against this server.
     * @return array list of ['level'=>'error'|'warn', 'message'=>string]
     */
    public static function check(array $manifest): array
    {
        $issues = [];
        if (!empty($manifest['requires_php']) && version_compare(PHP_VERSION, (string) $manifest['requires_php'], '<')) {
            $issues[] = ['level' => 'error', 'message' => 'Requires PHP ' . $manifest['requires_php'] . ' (running ' . PHP_VERSION . ')'];
        }
        foreach ((array) ($manifest['requires_extensions'] ?? []) as $ext) {
            if (is_string($ext) && !extension_loaded($ext)) {
                $issues[] = ['level' => 'error', 'message' => 'Missing PHP extension: ' . $ext];
            }
        }
        if (!empty($manifest['requires_version']) && version_compare(self::phpnuxbillVersion(), (string) $manifest['requires_version'], '<')) {
            $issues[] = ['level' => 'error', 'message' => 'Requires PHPNuxBill ' . $manifest['requires_version'] . ' (running ' . self::phpnuxbillVersion() . ')'];
        }
        foreach ((array) ($manifest['requires_plugins'] ?? []) as $dep) {
            if (!is_string($dep)) {
                continue;
            }
            $loaded = PluginLoader::$plugins[$dep] ?? null;
            if (!$loaded) {
                $issues[] = ['level' => 'warn', 'message' => 'Requires plugin: ' . $dep];
            } elseif (empty($loaded['active'])) {
                $issues[] = ['level' => 'warn', 'message' => 'Plugin ' . $dep . ' is installed but inactive'];
            }
        }
        return $issues;
    }

    public static function hasBlocking(array $issues): bool
    {
        foreach ($issues as $i) {
            if (($i['level'] ?? '') === 'error') {
                return true;
            }
        }
        return false;
    }

    // ----------------------------------------------------------------- health

    /** @var array|null persisted lint results: "path|mtime|size" => null|message */
    private static $lintDisk = null;

    /** Syntax-check a PHP file without executing it. Returns null when OK. Results are cached by path+mtime+size. */
    public static function lint(string $file): ?string
    {
        if (!is_file($file)) {
            return 'File not found';
        }
        $key = $file . '|' . filemtime($file) . '|' . filesize($file);
        if (array_key_exists($key, self::$lintCache)) {
            return self::$lintCache[$key];
        }
        $diskFile = self::cacheDir() . DIRECTORY_SEPARATOR . 'lint.json';
        if (self::$lintDisk === null) {
            $j = is_file($diskFile) ? json_decode((string) file_get_contents($diskFile), true) : [];
            self::$lintDisk = is_array($j) ? $j : [];
        }
        if (array_key_exists($key, self::$lintDisk)) {
            return self::$lintCache[$key] = self::$lintDisk[$key];
        }
        $result = null;
        try {
            token_get_all((string) file_get_contents($file), TOKEN_PARSE);
        } catch (ParseError $e) {
            $result = 'Line ' . $e->getLine() . ': ' . $e->getMessage();
        } catch (Throwable $e) {
            $result = $e->getMessage();
        }
        self::$lintDisk[$key] = $result;
        if (count(self::$lintDisk) > 600) { // keep the cache small: drop the oldest half
            self::$lintDisk = array_slice(self::$lintDisk, 300, null, true);
        }
        @file_put_contents($diskFile, json_encode(self::$lintDisk), LOCK_EX);
        return self::$lintCache[$key] = $result;
    }

    /**
     * Combine syntax lint, load-time errors and requirement issues.
     * @return array ['status'=>'ok'|'warn'|'error', 'messages'=>string[]]
     */
    public static function health(string $type, string $slug, ?string $mainFile, array $manifest = []): array
    {
        $messages = [];
        $status = 'ok';
        if ($mainFile !== null) {
            $err = self::lint($mainFile);
            if ($err !== null) {
                $status = 'error';
                $messages[] = 'Syntax error - ' . $err;
            }
        }
        if ($type === 'plugin' && isset(PluginLoader::$errors[$slug])) {
            $status = 'error';
            $messages[] = 'Load error - ' . PluginLoader::$errors[$slug];
        }
        foreach (self::check($manifest) as $issue) {
            $messages[] = $issue['message'];
            if ($issue['level'] === 'error') {
                $status = 'error';
            } elseif ($status === 'ok') {
                $status = 'warn';
            }
        }
        return ['status' => $status, 'messages' => $messages, 'text' => implode(' | ', $messages)];
    }

    // ---------------------------------------------------------------- licence

    /** @var array|null licence rows read straight from tbl_appconfig (plugins load before $config is filled) */
    private static $licenseRows = null;

    private static function licenseConfig(string $key): string
    {
        global $config;
        if (isset($config[$key])) {
            return (string) $config[$key];
        }
        if (self::$licenseRows === null) {
            self::$licenseRows = [];
            try {
                foreach (ORM::for_table('tbl_appconfig')->where_like('setting', 'license\\_%')->find_array() as $r) {
                    self::$licenseRows[$r['setting']] = (string) $r['value'];
                }
            } catch (Throwable $e) {
                // no database yet (installer): nothing is licensed
            }
        }
        return self::$licenseRows[$key] ?? '';
    }

    /** @var array "type:slug" => ['status' => callable, 'save' => callable] licence providers the extensions registered */
    private static $licenseProviders = [];

    /**
     * An extension that decides its own licence rules registers them (register_license() in init.php): the developer owns
     * the key format, the server, the storage and the checks, and the core only shows the License button and its result.
     * $status() returns the current state, $save($key) takes the key the admin typed; both answer true / false or
     * ['state' => 'valid'|'invalid'|'unchecked'|'none' (or 'valid' => bool), 'message' => '...'].
     */
    public static function registerLicense(string $type, string $slug, $status, $save): void
    {
        if (is_callable($status) && is_callable($save)) {
            self::$licenseProviders[$type . ':' . $slug] = ['status' => $status, 'save' => $save];
        }
    }

    /** @var array "type:slug" => true once the main file of a gateway / driver was included to look for its provider */
    private static $providerLoaded = [];

    public static function licenseProvider(string $type, string $slug): ?array
    {
        $k = $type . ':' . $slug;
        if (!isset(self::$licenseProviders[$k]) && ($type === 'gateway' || $type === 'device') && !isset(self::$providerLoaded[$k])) {
            // A gateway or driver file is only included on demand, so its register_license() has not run yet. A manifest that
            // says "license_provider": true asks the core to include the file (once) to find it. include_once, because the
            // billing code includes the same file later.
            self::$providerLoaded[$k] = true;
            $e = ($type === 'gateway' ? GatewayLoader::all() : DeviceLoader::all())[$slug] ?? null;
            if ($e !== null && !empty($e['manifest']['license_provider']) && is_file($e['file'])) {
                try {
                    include_once $e['file'];
                } catch (Throwable $t) {
                    // a broken file simply registers nothing, and the extension then counts as unlicensed
                }
            }
        }
        return self::$licenseProviders[$k] ?? null;
    }

    /** Run one provider callback and normalise its answer; a callback that throws counts as invalid */
    private static function providerCall($fn, array $args = []): array
    {
        try {
            $r = call_user_func_array($fn, $args);
        } catch (Throwable $e) {
            return ['state' => 'invalid', 'message' => 'Licence check failed: ' . $e->getMessage()];
        }
        if (is_bool($r)) {
            return ['state' => $r ? 'valid' : 'invalid', 'message' => ''];
        }
        if (!is_array($r)) {
            return ['state' => 'unchecked', 'message' => is_string($r) ? $r : ''];
        }
        $state = $r['state'] ?? (array_key_exists('valid', $r) ? ($r['valid'] ? 'valid' : 'invalid') : 'unchecked');
        $features = is_array($r['features'] ?? null) ? array_values(array_filter(array_map('strval', $r['features']))) : [];
        return [
            'state' => in_array($state, ['valid', 'invalid', 'unchecked', 'none'], true) ? $state : 'unchecked',
            'message' => (string) ($r['message'] ?? ''),
            'features' => $features,
        ];
    }

    /**
     * Does this extension's licence unlock a feature? The developer's status callback lists them:
     * ['state' => 'valid', 'features' => ['merchants', 'tokens']]. The feature "licensed" means any valid licence.
     * Without a registered licence provider, or without a valid licence, the answer is no.
     */
    public static function licenseHas(string $type, string $slug, string $feature): bool
    {
        $p = self::licenseProvider($type, $slug);
        if ($p === null) {
            return false;
        }
        $st = self::providerCall($p['status']);
        return $st['state'] === 'valid' && ($feature === 'licensed' || in_array($feature, $st['features'] ?? [], true));
    }

    public static function license(string $type, string $slug): string
    {
        return self::licenseConfig('license_' . $type . '_' . $slug);
    }

    public static function licenseStatus(string $type, string $slug): array
    {
        if ($p = self::licenseProvider($type, $slug)) {
            return self::providerCall($p['status']);
        }
        $raw = self::licenseConfig('license_status_' . $type . '_' . $slug);
        $s = $raw !== '' ? json_decode($raw, true) : null;
        return is_array($s) ? $s : ['state' => self::license($type, $slug) !== '' ? 'unchecked' : 'none'];
    }

    private static function storeLicense(string $type, string $slug, string $key, array $status): void
    {
        self::setConfig('license_' . $type . '_' . $slug, $key);
        self::setConfig('license_status_' . $type . '_' . $slug, json_encode($status));
        self::$licenseRows = null;
    }

    /**
     * The text the publisher signs: bound to this extension, this site's host, the key, an expiry (0 = never) and the verdict
     * "valid", so a signature can only exist for an answer that said the key is valid (a revoked answer cannot be replayed).
     */
    public static function licenseMessage(string $type, string $slug, string $key, int $expires): string
    {
        return implode('|', [$type, $slug, strtolower((string) parse_url(APP_URL, PHP_URL_HOST)), $key, $expires, 'valid']);
    }

    /**
     * Is the stored answer really signed by the publisher (manifest license_public_key, base64 Ed25519) for this site
     * and key, and not expired? Checked locally on every request, so editing the database cannot fake a licence.
     */
    public static function licenseSignatureOk(string $type, string $slug, array $manifest, string $key, array $status): bool
    {
        if (!function_exists('sodium_crypto_sign_verify_detached') || $key === '') {
            return false;
        }
        $pk = base64_decode((string) ($manifest['license_public_key'] ?? ''), true);
        $sig = base64_decode((string) ($status['signature'] ?? ''), true);
        if ($pk === false || $sig === false || strlen($pk) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES || strlen($sig) !== SODIUM_CRYPTO_SIGN_BYTES) {
            return false;
        }
        $expires = (int) ($status['expires'] ?? 0);
        if ($expires > 0 && $expires < time()) {
            return false;
        }
        try {
            return sodium_crypto_sign_verify_detached($sig, self::licenseMessage($type, $slug, $key, $expires), $pk);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Ask the publisher's endpoint about a key (POST key, slug, type, domain; expects JSON
     * {"valid": true|false, "message": "...", "expires": unix|0, "signature": "base64"}).
     * Returns the status to store, or null when there is no endpoint or it did not answer.
     */
    private static function queryLicense(string $type, string $slug, string $key, array $manifest): ?array
    {
        $url = $manifest['license_check_url'] ?? '';
        if (!is_string($url) || !preg_match('#^https://#i', $url)) {
            return null;
        }
        $res = Http::postData($url, [
            'key' => $key,
            'slug' => $slug,
            'type' => $type,
            'domain' => parse_url(APP_URL, PHP_URL_HOST),
        ]);
        $j = is_string($res) ? json_decode($res, true) : null;
        if (!is_array($j) || !array_key_exists('valid', $j)) {
            return null;
        }
        $status = [
            'state' => $j['valid'] ? 'valid' : 'invalid',
            'message' => (string) ($j['message'] ?? ($j['valid'] ? 'Licence valid' : 'Licence rejected')),
            'checked' => time(),
        ];
        if (!empty($manifest['license_public_key'])) {
            $status['expires'] = (int) ($j['expires'] ?? 0);
            $status['signature'] = (string) ($j['signature'] ?? '');
            if ($j['valid'] && !self::licenseSignatureOk($type, $slug, $manifest, $key, $status)) {
                $status['state'] = 'invalid';
                $status['message'] = function_exists('sodium_crypto_sign_verify_detached')
                    ? 'The licence answer is not signed by the publisher for this site'
                    : 'The PHP sodium extension is needed to verify this licence';
            }
        }
        return $status;
    }

    /**
     * Save a licence key. When the manifest declares license_check_url the key is verified remotely; with
     * license_public_key the answer must also carry the publisher's signature.
     */
    public static function saveLicense(string $type, string $slug, string $key, array $manifest = []): array
    {
        $key = trim($key);
        if ($p = self::licenseProvider($type, $slug)) {
            return self::providerCall($p['save'], [$key]); // the developer's code stores the key, wherever it likes
        }
        if ($key === '') {
            self::storeLicense($type, $slug, '', ['state' => 'none']);
            return ['state' => 'none', 'message' => 'Licence removed'];
        }
        $status = self::queryLicense($type, $slug, $key, $manifest);
        if ($status === null) {
            $status = !empty($manifest['license_check_url'])
                ? ['state' => 'unchecked', 'message' => 'Verification endpoint did not answer', 'checked' => time()]
                : ['state' => 'unchecked', 'message' => 'Saved (no verification endpoint)', 'checked' => time()];
        }
        self::storeLicense($type, $slug, $key, $status);
        return $status;
    }

    /**
     * Ask again about every stored key (cron, before a signed answer expires). An endpoint that does not answer keeps
     * the stored answer until it expires; one that says the key is no longer valid revokes it.
     * @return int how many keys were refreshed
     */
    public static function refreshLicenses(): int
    {
        $n = 0;
        foreach (self::TYPES as $type) {
            foreach (array_keys(MarketplaceUpdates::installed($type)) as $slug) {
                $manifest = self::manifest($type, $slug);
                $key = self::license($type, $slug);
                if (self::licenseProvider($type, $slug) || $key === '' || empty($manifest['requires_license']) || empty($manifest['license_check_url'])) {
                    continue;
                }
                $status = self::queryLicense($type, $slug, $key, $manifest);
                if ($status !== null) {
                    self::storeLicense($type, $slug, $key, $status);
                    $n++;
                }
            }
        }
        return $n;
    }

    /** True when the manifest demands a licence and none usable is stored */
    public static function licenseMissing(string $type, string $slug, array $manifest): bool
    {
        if ($p = self::licenseProvider($type, $slug)) {
            return self::providerCall($p['status'])['state'] !== 'valid'; // the developer's own rules
        }
        if (empty($manifest['requires_license'])) {
            return false;
        }
        $key = self::license($type, $slug);
        if ($key === '') {
            return true;
        }
        $status = self::licenseStatus($type, $slug);
        if (!empty($manifest['license_public_key'])) {
            // both must hold: the stored state says valid AND the publisher's signature (which covers "valid") checks out
            return ($status['state'] ?? '') !== 'valid' || !self::licenseSignatureOk($type, $slug, $manifest, $key, $status);
        }
        if ($status['state'] === 'invalid') {
            return true;
        }
        return !empty($manifest['license_check_url']) && $status['state'] !== 'valid';
    }

    // ----------------------------------------------------------------- GitHub

    /** Only http(s) URLs may become links; anything else (javascript:, data:, ...) becomes '' */
    public static function httpUrl($url): string
    {
        $url = is_string($url) ? trim($url) : '';
        return preg_match('#^https?://#i', $url) && filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
    }

    /** An https:// address or ''. Package downloads never use plain http. */
    public static function httpsUrl($url): string
    {
        $url = self::httpUrl($url);
        return stripos($url, 'https://') === 0 ? $url : '';
    }

    /** "owner/name" (letters, digits, . _ -) or null. Accepts a github.com URL and reduces it to that form. */
    public static function githubSlug(string $in): ?string
    {
        $in = trim($in);
        if (($url = self::githubRepo($in)) !== null) {
            $in = substr($url, strlen('https://github.com/'));
        }
        if (!preg_match('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $in)) {
            return null;
        }
        foreach (explode('/', $in) as $part) {
            if ($part === '.' || $part === '..') { // would escape the repository path on the raw host
                return null;
            }
        }
        return $in;
    }

    /** Normalise to https://github.com/owner/repo or null */
    public static function githubRepo(string $url): ?string
    {
        if (preg_match('#^https://(?:www\.)?github\.com/([A-Za-z0-9_.-]+)/([A-Za-z0-9_.-]+?)(?:\.git)?/?$#', trim($url), $m)) {
            return 'https://github.com/' . $m[1] . '/' . $m[2];
        }
        return null;
    }

    /** README of a GitHub "owner/name" repository rendered to safe HTML (cached 24h). Empty string if none. */
    public static function readmeHtml(string $ownerName): string
    {
        $path = self::githubSlug($ownerName);
        if ($path === null) {
            return '';
        }
        $dir = self::privateDir('readme');
        $cache = $dir . DIRECTORY_SEPARATOR . md5($path) . '.html';
        if (is_file($cache) && time() - filemtime($cache) < 86400) {
            return (string) file_get_contents($cache);
        }
        $md = '';
        foreach (['README.md', 'readme.md', 'Readme.md'] as $name) {
            $data = Http::getData('https://raw.githubusercontent.com/' . $path . '/HEAD/' . $name, [], 4000, 6000);
            if (is_string($data) && $data !== '' && stripos(ltrim($data), '404: Not Found') !== 0) {
                $md = $data;
                break;
            }
        }
        $html = $md === '' ? '' : self::markdown(mb_substr($md, 0, 40000));
        @file_put_contents($cache, $html);
        return $html;
    }

    /** Tiny, safe Markdown subset: everything is escaped first, then headings/lists/code/links are restored */
    public static function markdown(string $md): string
    {
        $md = str_replace(["\r\n", "\r"], "\n", $md);
        $blocks = [];
        // fenced code first so its content is never interpreted
        $md = preg_replace_callback('/```[^\n]*\n(.*?)```/s', function ($m) use (&$blocks) {
            $blocks[] = '<pre><code>' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . '</code></pre>';
            return "\n@@BLOCK" . (count($blocks) - 1) . "@@\n";
        }, $md);
        $md = htmlspecialchars($md, ENT_QUOTES, 'UTF-8');
        $inline = function (string $t): string {
            $t = preg_replace('/`([^`]+)`/', '<code>$1</code>', $t);
            $t = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $t);
            $t = preg_replace('/(?<![*\w])\*([^*\n]+)\*(?![*\w])/', '<em>$1</em>', $t);
            $t = preg_replace('/!\[[^\]]*\]\([^)]*\)/', '', $t); // drop images (tracking pixels / badges)
            return preg_replace_callback('/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/', function ($m) {
                return '<a href="' . $m[2] . '" target="_blank" rel="noopener noreferrer">' . $m[1] . '</a>';
            }, $t);
        };
        $out = [];
        $list = false;
        foreach (explode("\n", $md) as $line) {
            if (preg_match('/^@@BLOCK(\d+)@@$/', trim($line), $m)) {
                if ($list) {
                    $out[] = '</ul>';
                    $list = false;
                }
                $out[] = $blocks[(int) $m[1]];
                continue;
            }
            if (preg_match('/^\s*[-*+]\s+(.*)$/', $line, $m)) {
                if (!$list) {
                    $out[] = '<ul>';
                    $list = true;
                }
                $out[] = '<li>' . $inline($m[1]) . '</li>';
                continue;
            }
            if ($list) {
                $out[] = '</ul>';
                $list = false;
            }
            if (preg_match('/^(#{1,4})\s+(.*)$/', $line, $m)) {
                $lvl = min(6, strlen($m[1]) + 2);
                $out[] = "<h$lvl>" . $inline($m[2]) . "</h$lvl>";
            } elseif (trim($line) !== '') {
                $out[] = '<p>' . $inline($line) . '</p>';
            }
        }
        if ($list) {
            $out[] = '</ul>';
        }
        return implode("\n", $out);
    }

    // ------------------------------------------------------------- misc utils

    /** JSON for an HTML data-attribute (escape in the template) */
    public static function infoJson(array $info): string
    {
        return json_encode($info, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    /** Is a store date (YYYY-MM-DD) newer than a recorded one? */
    public static function newerDate(?string $store, ?string $local): bool
    {
        $a = strtotime((string) $store);
        $b = strtotime((string) $local);
        return $a !== false && $b !== false && $a > $b;
    }
}
