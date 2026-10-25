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
        return (bool) preg_match('/^[A-Za-z0-9_-]+$/', $slug);
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

    public static function license(string $type, string $slug): string
    {
        return (string) self::config('license_' . $type . '_' . $slug, '');
    }

    public static function licenseStatus(string $type, string $slug): array
    {
        $raw = self::config('license_status_' . $type . '_' . $slug, '');
        $s = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($s) ? $s : ['state' => self::license($type, $slug) !== '' ? 'unchecked' : 'none'];
    }

    /**
     * Save a licence key. When the manifest declares license_check_url the key is verified remotely
     * (POST key, slug, domain; expects JSON {"valid": true|false, "message": "..."}).
     */
    public static function saveLicense(string $type, string $slug, string $key, array $manifest = []): array
    {
        $key = trim($key);
        self::setConfig('license_' . $type . '_' . $slug, $key);
        if ($key === '') {
            self::setConfig('license_status_' . $type . '_' . $slug, json_encode(['state' => 'none']));
            return ['state' => 'none', 'message' => 'Licence removed'];
        }
        $status = ['state' => 'unchecked', 'message' => 'Saved (no verification endpoint)', 'checked' => time()];
        $url = $manifest['license_check_url'] ?? '';
        if (is_string($url) && preg_match('#^https://#i', $url)) {
            $res = Http::postData($url, [
                'key' => $key,
                'slug' => $slug,
                'type' => $type,
                'domain' => parse_url(APP_URL, PHP_URL_HOST),
            ]);
            $j = is_string($res) ? json_decode($res, true) : null;
            if (is_array($j) && array_key_exists('valid', $j)) {
                $status = [
                    'state' => $j['valid'] ? 'valid' : 'invalid',
                    'message' => (string) ($j['message'] ?? ($j['valid'] ? 'Licence valid' : 'Licence rejected')),
                    'checked' => time(),
                ];
            } else {
                $status = ['state' => 'unchecked', 'message' => 'Verification endpoint did not answer', 'checked' => time()];
            }
        }
        self::setConfig('license_status_' . $type . '_' . $slug, json_encode($status));
        return $status;
    }

    /** True when the manifest demands a licence and none usable is stored */
    public static function licenseMissing(string $type, string $slug, array $manifest): bool
    {
        if (empty($manifest['requires_license'])) {
            return false;
        }
        $st = self::licenseStatus($type, $slug)['state'];
        if (self::license($type, $slug) === '' || $st === 'invalid') {
            return true;
        }
        return !empty($manifest['license_check_url']) && $st !== 'valid';
    }

    // ----------------------------------------------------------------- GitHub

    /** Normalise to https://github.com/owner/repo or null */
    public static function githubRepo(string $url): ?string
    {
        if (preg_match('#^https://(?:www\.)?github\.com/([A-Za-z0-9_.-]+)/([A-Za-z0-9_.-]+?)(?:\.git)?/?$#', trim($url), $m)) {
            return 'https://github.com/' . $m[1] . '/' . $m[2];
        }
        return null;
    }

    /** README of a GitHub repo rendered to safe HTML (cached 24h). Empty string if none. */
    public static function readmeHtml(string $githubUrl): string
    {
        $repo = self::githubRepo($githubUrl);
        if ($repo === null) {
            return '';
        }
        $dir = self::privateDir('readme');
        $cache = $dir . DIRECTORY_SEPARATOR . md5($repo) . '.html';
        if (is_file($cache) && time() - filemtime($cache) < 86400) {
            return (string) file_get_contents($cache);
        }
        $path = substr($repo, strlen('https://github.com/'));
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
