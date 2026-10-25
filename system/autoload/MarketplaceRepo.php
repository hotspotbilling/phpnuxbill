<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * Store repositories: the official one plus any custom https:// repository.json URLs configured in
 * Marketplace > Tools. Each repository is cached separately for 24h and merged by extension id
 * (earlier repositories win), so a reseller can host their own store next to the official one.
 *
 * repository.json keys: plugins, payment_gateway, devices, themes - each a list of
 * {id, name, description, ispaid, author, url, github, last_update}.
 */
class MarketplaceRepo
{
    public const OFFICIAL = 'https://hotspotbilling.github.io/Plugin-Repository/repository.json';
    private const TTL = 86400;
    private const RETRY_BACKOFF = 900; // seconds to wait after a failed background refresh

    /** @var array|null merged repository for this request */
    private static $loaded = null;
    /** @var array repo url => status for this request */
    private static $status = [];
    /** @var array url => true: expired caches to refresh after the response has been sent */
    private static $refreshQueue = [];
    private static $refreshRegistered = false;

    /** @return string[] repositories in priority order */
    public static function urls(): array
    {
        $urls = [];
        if (Extensions::config('marketplace_official', 'yes') !== 'no') {
            $urls[] = self::OFFICIAL;
        }
        foreach (self::customUrls() as $u) {
            $urls[] = $u;
        }
        return array_values(array_unique($urls));
    }

    /** @return string[] custom repositories, validated */
    public static function customUrls(): array
    {
        $out = [];
        foreach (preg_split('/\R+/', (string) Extensions::config('marketplace_repos', '')) as $line) {
            $line = trim($line);
            if (self::validUrl($line)) {
                $out[] = $line;
            }
        }
        return array_values(array_unique($out));
    }

    public static function validUrl(string $url): bool
    {
        return (bool) filter_var($url, FILTER_VALIDATE_URL) && preg_match('#^https://#i', $url) === 1;
    }

    private static function cacheFile(string $url): string
    {
        if ($url === self::OFFICIAL) {
            // shared with the legacy Plugin Manager
            return Extensions::cacheDir() . DIRECTORY_SEPARATOR . 'plugin_repository.json';
        }
        return Extensions::cacheDir() . DIRECTORY_SEPARATOR . 'repo_' . md5($url) . '.json';
    }

    /** Download one repository, validate it, and write its cache. Returns decoded JSON or null. */
    private static function fetch(string $url): ?array
    {
        $data = Http::getData($url, [], 5000, 15000);
        $json = (is_string($data) && $data !== '') ? json_decode($data, true) : null;
        if (!is_array($json) || !self::looksLikeRepo($json)) {
            return null;
        }
        file_put_contents(self::cacheFile($url), $data, LOCK_EX);
        return $json;
    }

    private static function looksLikeRepo(array $json): bool
    {
        foreach (['plugins', 'payment_gateway', 'devices', 'themes'] as $k) {
            if (isset($json[$k]) && is_array($json[$k])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Load every repository (cache first) and merge them.
     * @param bool $force ignore cache age and re-download
     */
    public static function load(bool $force = false): array
    {
        if (self::$loaded !== null && !$force) {
            return self::$loaded;
        }
        $merged = ['plugins' => [], 'payment_gateway' => [], 'devices' => [], 'themes' => []];
        $seen = [];
        self::$status = [];
        foreach (self::urls() as $url) {
            $cache = self::cacheFile($url);
            $json = null;
            $state = 'cache';
            $cached = is_file($cache) ? json_decode((string) file_get_contents($cache), true) : null;
            $cached = is_array($cached) && self::looksLikeRepo($cached) ? $cached : null;
            if (!$force && $cached !== null) {
                // passive page load: never wait on the network when a copy exists, even an old one
                // (sync / cron refresh it); an old copy is reported as "stale"
                $json = $cached;
                if ((time() - filemtime($cache)) < self::TTL) {
                    $state = 'cache';
                } else {
                    $state = 'stale';
                    self::queueRefresh($url); // served from the old copy now, refreshed in the background
                }
            } else {
                // forced sync, or nothing cached yet
                $json = self::fetch($url);
                $state = $json ? 'online' : 'error';
                if (!$json && $cached !== null) {
                    $json = $cached;
                    $state = 'stale';
                }
            }
            $json = is_array($json) ? $json : [];
            $count = 0;
            foreach (array_keys($merged) as $key) {
                foreach ((array) ($json[$key] ?? []) as $item) {
                    if (!is_array($item) || empty($item['id']) || !is_string($item['id']) || !preg_match('/^[A-Za-z0-9_.-]+$/', $item['id'])) {
                        continue;
                    }
                    $item = self::sanitizeItem($item);
                    $k = $key . '|' . strtolower($item['id']);
                    if (isset($seen[$k])) {
                        continue;
                    }
                    $seen[$k] = true;
                    $item['repo'] = parse_url($url, PHP_URL_HOST) ?: $url;
                    $merged[$key][] = $item;
                    $count++;
                }
            }
            self::$status[$url] = [
                'url' => $url,
                'official' => $url === self::OFFICIAL,
                'state' => $state,
                'count' => $count,
                'synced' => is_file($cache) ? filemtime($cache) : 0,
            ];
        }
        return self::$loaded = $merged;
    }

    /**
     * Refresh an expired cache after the page has been delivered. Only where the response can be closed first
     * (PHP-FPM / FastCGI); elsewhere the cron job or the Sync button keeps the store current.
     */
    private static function queueRefresh(string $url): void
    {
        if (!function_exists('fastcgi_finish_request')) {
            return;
        }
        if (self::retryAfter($url) > time()) {
            return; // the last background refresh failed: stay on the old copy for now
        }
        self::$refreshQueue[$url] = true;
        if (!self::$refreshRegistered) {
            self::$refreshRegistered = true;
            register_shutdown_function([self::class, 'refreshQueued']);
        }
    }

    /** Timestamp before which a failed background refresh of this repository is not retried */
    private static function retryAfter(string $url): int
    {
        $f = Extensions::cacheDir() . DIRECTORY_SEPARATOR . 'repo_' . md5($url) . '.retry';
        return is_file($f) ? (int) filemtime($f) + self::RETRY_BACKOFF : 0;
    }

    /** Runs at shutdown: closes the response, then re-downloads each queued repository (one process at a time per repo) */
    public static function refreshQueued(): void
    {
        if (!self::$refreshQueue) {
            return;
        }
        ignore_user_abort(true);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close(); // do not keep the visitor's session locked while we download
        }
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        }
        foreach (array_keys(self::$refreshQueue) as $url) {
            $base = Extensions::cacheDir() . DIRECTORY_SEPARATOR . 'repo_' . md5($url);
            // an exclusive non-blocking lock held for the whole download: a second request cannot bypass or remove it,
            // and the OS drops it if this process dies
            $handle = @fopen($base . '.lock', 'c');
            if (!$handle) {
                continue;
            }
            if (!flock($handle, LOCK_EX | LOCK_NB)) {
                fclose($handle); // another request is already refreshing it
                continue;
            }
            try {
                if (self::retryAfter($url) > time()) {
                    continue; // a recent attempt failed
                }
                if (self::fetch($url) === null) {
                    @touch($base . '.retry');
                } else {
                    @unlink($base . '.retry');
                }
            } catch (Throwable $e) {
                @touch($base . '.retry'); // the old copy keeps serving; back off before trying again
            } finally {
                flock($handle, LOCK_UN);
                fclose($handle);
            }
        }
        self::$refreshQueue = [];
    }

    /** Store entries are untrusted: text becomes plain strings, links must be http(s), GitHub sources must be real repos */
    private static function sanitizeItem(array $item): array
    {
        foreach (['name', 'description', 'author', 'last_update'] as $k) {
            $item[$k] = isset($item[$k]) && is_scalar($item[$k]) ? (string) $item[$k] : '';
        }
        $item['url'] = Extensions::httpUrl($item['url'] ?? '');
        $item['github'] = Extensions::githubRepo(isset($item['github']) && is_string($item['github']) ? $item['github'] : '') ?? '';
        $item['ispaid'] = !empty($item['ispaid']);
        return $item;
    }

    /** Re-download everything. Returns per-repo status; the previous cache survives a failed download. */
    public static function sync(): array
    {
        self::$loaded = null;
        self::load(true);
        return self::$status;
    }

    public static function status(): array
    {
        self::load();
        return array_values(self::$status);
    }

    /** Oldest successful sync timestamp among the repositories (0 = never) */
    public static function lastSync(): int
    {
        $times = [];
        foreach (self::status() as $s) {
            if ($s['synced'] > 0) {
                $times[] = $s['synced'];
            }
        }
        return $times ? min($times) : 0;
    }

    public static function online(): bool
    {
        foreach (self::status() as $s) {
            if ($s['state'] !== 'error') { // a stale copy still serves the pages
                return true;
            }
        }
        return false;
    }

    /** Find one store item by type and id (case-insensitive) */
    public static function find(string $type, string $id): ?array
    {
        $repo = self::load();
        foreach ($repo[Extensions::repoKey($type)] ?? [] as $item) {
            if (strcasecmp((string) ($item['id'] ?? ''), $id) === 0) {
                return $item;
            }
        }
        return null;
    }
}
