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

    /** @var array|null merged repository for this request */
    private static $loaded = null;
    /** @var array repo url => status for this request */
    private static $status = [];

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
                $state = (time() - filemtime($cache)) < self::TTL ? 'cache' : 'stale';
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
