<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * Update detection for every extension type.
 *
 * An installed extension has an update when
 *  - its manifest has update_url and that reports a newer version (plugins/themes), or
 *  - the store lists it with a last_update newer than what was recorded when it was installed
 *    (extensions.json), falling back to the main file's modification date for older installs.
 *
 * Results are cached in system/cache/marketplace_updates.json so pages never block on the network;
 * system/cron/marketplace_sync.php and Marketplace > "Check for updates" refresh them.
 */
class MarketplaceUpdates
{
    private static function file(): string
    {
        return Extensions::cacheDir() . DIRECTORY_SEPARATOR . 'marketplace_updates.json';
    }

    /** @return array ['checked'=>int,'updates'=>list,'notified'=>array] */
    public static function cached(): array
    {
        $f = self::file();
        $j = is_file($f) ? json_decode((string) file_get_contents($f), true) : [];
        return is_array($j) ? $j + ['checked' => 0, 'updates' => [], 'notified' => []] : ['checked' => 0, 'updates' => [], 'notified' => []];
    }

    /** Forget one pending update right after it was applied (no network needed) */
    public static function dropCached(string $type, string $slug): void
    {
        $c = self::cached();
        $c['updates'] = array_values(array_filter($c['updates'], fn($u) => !($u['type'] === $type && $u['slug'] === $slug)));
        unset($c['notified'][$type . ':' . $slug]);
        file_put_contents(self::file(), json_encode($c, JSON_PRETTY_PRINT), LOCK_EX);
    }

    /** Cached updates indexed "type:slug" => entry */
    public static function index(): array
    {
        $out = [];
        foreach (self::cached()['updates'] as $u) {
            $out[$u['type'] . ':' . $u['slug']] = $u;
        }
        return $out;
    }

    /** @return array slug => ['name'=>string,'main'=>?string] for everything installed of a type */
    public static function installed(string $type): array
    {
        $out = [];
        switch ($type) {
            case 'plugin':
                foreach (PluginLoader::$plugins as $slug => $p) {
                    $out[$slug] = ['name' => $p['name'] ?? $slug, 'main' => ($p['path'] ?? '') . DIRECTORY_SEPARATOR . ($p['main'] ?? $slug . '.php')];
                }
                foreach (glob(Extensions::root('plugin') . DIRECTORY_SEPARATOR . '*.php') ?: [] as $f) {
                    $s = basename($f, '.php');
                    if ($s !== 'index' && !PluginLoader::isShadowed($f)) {
                        $out[$s] = ['name' => $s, 'main' => $f];
                    }
                }
                break;
            case 'gateway':
            case 'device':
                $loader = $type === 'gateway' ? 'GatewayLoader' : 'DeviceLoader';
                foreach ($loader::all() as $slug => $e) {
                    $out[$slug] = ['name' => $e['manifest']['name'] ?? $slug, 'main' => $e['file']];
                }
                break;
            case 'theme':
                foreach (glob(Extensions::root('theme') . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'theme.json') ?: [] as $mf) {
                    $m = json_decode((string) file_get_contents($mf), true);
                    $out[basename(dirname($mf))] = ['name' => $m['name'] ?? basename(dirname($mf)), 'main' => null];
                }
                break;
        }
        return $out;
    }

    /** Normalised comparison key: community gateways are published as phpnuxbill-xyz-payment-gateway */
    private static function normalize(string $type, string $s): string
    {
        $s = preg_replace('/[^a-z0-9]/', '', strtolower($s));
        return $type === 'gateway' ? str_replace(['phpnuxbill', 'paymentgateway', 'gateway', 'payment'], '', $s) : $s;
    }

    /** Find the store entry that corresponds to an installed extension */
    public static function matchStore(string $type, string $slug, ?array $repo = null): ?array
    {
        $repo = $repo ?? MarketplaceRepo::load();
        $list = $repo[Extensions::repoKey($type)] ?? [];
        $meta = Extensions::getMeta($type, $slug);
        $want = strtolower($meta['store_id'] ?? '');
        foreach ($list as $item) {
            $id = strtolower((string) ($item['id'] ?? ''));
            if (($want !== '' && $id === $want) || $id === strtolower($slug)) {
                return $item;
            }
        }
        if ($type === 'gateway' || $type === 'device') {
            // exact match on the normalised id only: substring matching would hide e.g. "RadiusRest" behind "Radius"
            $c = self::normalize($type, $slug);
            if ($c !== '') {
                foreach ($list as $item) {
                    $ci = self::normalize($type, (string) ($item['id'] ?? ''));
                    if ($ci !== '' && $ci === $c) {
                        return $item;
                    }
                }
            }
        }
        return null;
    }

    /**
     * @return array ['available'=>bool,'current'=>string,'latest'=>string,'basis'=>'version'|'date'|'estimated'|'']
     */
    public static function status(string $type, string $slug, ?array $store, ?string $mainFile): array
    {
        $none = ['available' => false, 'current' => '', 'latest' => '', 'basis' => ''];
        $manifest = Extensions::manifest($type, $slug);
        if ($type === 'plugin' && isset(PluginLoader::$plugins[$slug])) {
            $manifest = PluginLoader::$plugins[$slug];
        }
        if (!empty($manifest['update_url'])) {
            $u = PluginLoader::updateAvailable($manifest);
            if ($u['latest_version']) {
                return [
                    'available' => $u['update_available'],
                    'current' => (string) $u['installed_version'],
                    'latest' => (string) $u['latest_version'],
                    'basis' => 'version',
                ];
            }
        }
        if (!$store || empty($store['last_update'])) {
            return $none;
        }
        $meta = Extensions::getMeta($type, $slug);
        if (!empty($meta['repo_updated'])) {
            return [
                'available' => Extensions::newerDate($store['last_update'], $meta['repo_updated']),
                'current' => (string) $meta['repo_updated'],
                'latest' => (string) $store['last_update'],
                'basis' => 'date',
            ];
        }
        if ($mainFile && is_file($mainFile)) { // installed before tracking: compare with the file date, with a week of slack
            $local = date('Y-m-d', filemtime($mainFile));
            return [
                'available' => strtotime($store['last_update']) > strtotime($local) + 7 * 86400,
                'current' => $local,
                'latest' => (string) $store['last_update'],
                'basis' => 'estimated',
            ];
        }
        return $none;
    }

    /** Check everything installed against the store (uses cached repositories) */
    public static function scan(): array
    {
        $repo = MarketplaceRepo::load();
        $updates = [];
        foreach (Extensions::TYPES as $type) {
            foreach (self::installed($type) as $slug => $info) {
                $store = self::matchStore($type, $slug, $repo);
                $s = self::status($type, $slug, $store, $info['main']);
                if ($s['available']) {
                    $updates[] = [
                        'type' => $type,
                        'slug' => $slug,
                        'name' => $info['name'],
                        'current' => $s['current'],
                        'latest' => $s['latest'],
                        'basis' => $s['basis'],
                        'store_id' => $store['id'] ?? '',
                    ];
                }
            }
        }
        return $updates;
    }

    /**
     * Sync the repositories (optional), rescan, store the result and notify about new updates.
     * @return array ['updates'=>list,'new'=>list,'notified'=>bool]
     */
    public static function run(bool $sync = true, bool $notify = true): array
    {
        if ($sync) {
            MarketplaceRepo::sync();
        }
        $prev = self::cached();
        $updates = self::scan();
        $notified = $prev['notified'];
        $new = [];
        foreach ($updates as $u) {
            $k = $u['type'] . ':' . $u['slug'];
            if (($notified[$k] ?? '') !== $u['latest']) {
                $new[] = $u;
            }
        }
        // forget entries that are no longer pending
        $pending = array_map(fn($u) => $u['type'] . ':' . $u['slug'], $updates);
        $notified = array_intersect_key($notified, array_flip($pending));
        $sent = false;
        if ($new && $notify && Extensions::config('marketplace_notify', 'yes') !== 'no') {
            $lines = array_map(fn($u) => '- ' . Extensions::label($u['type']) . ' ' . $u['name'] . ': ' . ($u['current'] ?: '?') . ' -> ' . $u['latest'], $new);
            try {
                Message::sendTelegram("Marketplace: " . count($new) . " extension update(s) available\n" . implode("\n", $lines) . "\n" . APP_URL . '/?_route=marketplace');
                $sent = true;
            } catch (Throwable $e) {
                // notification is best effort
            }
            if ($sent) {
                foreach ($new as $u) {
                    $notified[$u['type'] . ':' . $u['slug']] = $u['latest'];
                }
            }
        }
        file_put_contents(self::file(), json_encode(['checked' => time(), 'updates' => $updates, 'notified' => $notified], JSON_PRETTY_PRINT), LOCK_EX);
        return ['updates' => $updates, 'new' => $new, 'notified' => $sent];
    }
}
