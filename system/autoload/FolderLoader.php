<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * Resolves payment gateways and device drivers whether they use the original flat layout
 * (<root>/<slug>.php) or the folder layout (<root>/<slug>/<type>.json + <slug>.php + ui/ ...).
 * A folder with a manifest wins over a flat file with the same slug.
 */
abstract class FolderLoader
{
    protected const TYPE = '';

    /** @var array type => slug => entry */
    private static $cache = [];

    /** @return array slug => ['slug','file','folder'=>bool,'dir'=>?string,'manifest'=>array] */
    public static function all(): array
    {
        $type = static::TYPE;
        if (isset(self::$cache[$type])) {
            return self::$cache[$type];
        }
        $root = Extensions::root($type);
        $found = [];
        foreach (glob($root . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
            $slug = basename($file, '.php');
            if (in_array($slug, ['index', 'readme'], true) || !Extensions::validIdentifier($slug)) {
                continue;
            }
            $found[$slug] = ['slug' => $slug, 'file' => $file, 'folder' => false, 'dir' => null, 'manifest' => []];
        }
        $manifestName = Extensions::manifestName($type);
        foreach (glob($root . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . $manifestName) ?: [] as $mf) {
            $dir = dirname($mf);
            $slug = basename($dir);
            if ($slug === 'ui' || !Extensions::validIdentifier($slug)) {
                continue;
            }
            $m = json_decode((string) file_get_contents($mf), true);
            $m = is_array($m) ? $m : [];
            $main = $dir . DIRECTORY_SEPARATOR . basename((string) ($m['main'] ?? ($slug . '.php')));
            if (!is_file($main)) {
                continue;
            }
            $found[$slug] = ['slug' => $slug, 'file' => $main, 'folder' => true, 'dir' => $dir, 'manifest' => $m];
        }
        ksort($found, SORT_NATURAL | SORT_FLAG_CASE);
        return self::$cache[$type] = $found;
    }

    public static function forget(): void
    {
        unset(self::$cache[static::TYPE]);
    }

    public static function names(): array
    {
        return array_keys(static::all());
    }

    public static function exists(string $slug): bool
    {
        return isset(static::all()[$slug]);
    }

    /** Absolute path of the main PHP file, or null */
    public static function file(string $slug): ?string
    {
        return static::all()[$slug]['file'] ?? null;
    }

    public static function isFolder(string $slug): bool
    {
        return !empty(static::all()[$slug]['folder']);
    }

    public static function manifest(string $slug): array
    {
        return static::all()[$slug]['manifest'] ?? [];
    }

    /** ui/ directories contributed by folder-style extensions */
    public static function templateDirs(): array
    {
        $dirs = [];
        foreach (static::all() as $e) {
            if ($e['folder'] && is_dir($e['dir'] . DIRECTORY_SEPARATOR . 'ui')) {
                $dirs[] = $e['dir'] . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR;
            }
        }
        return $dirs;
    }
}
