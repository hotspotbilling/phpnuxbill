<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * Zip snapshots of an extension taken before install / update / uninstall.
 * Archives live in system/cache/backups/ (web access denied) and hold paths relative to the
 * extension type's root, so a restore is a plain extract into that root.
 * The archive comment carries {"type","slug","reason","version","created"}.
 */
class ExtensionBackup
{
    private static function dir(): string
    {
        return Extensions::privateDir('backups');
    }

    public static function keep(): int
    {
        return max(1, min(50, (int) Extensions::config('marketplace_backup_keep', 5)));
    }

    /** Files (relative to the type root) that make up an extension */
    public static function files(string $type, string $slug): array
    {
        $root = Extensions::root($type);
        $rel = [];
        $folder = $root . DIRECTORY_SEPARATOR . $slug;
        if (is_dir($folder) && !is_link($folder)) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                if ($f->isFile() && !$f->isLink()) {
                    $rel[] = substr($f->getPathname(), strlen($root) + 1);
                }
            }
        }
        // flat layout: <slug>.php plus its templates under ui/
        if ($type !== 'theme') {
            if (is_file($root . DIRECTORY_SEPARATOR . $slug . '.php')) {
                $rel[] = $slug . '.php';
            }
            if ($type === 'plugin' || $type === 'gateway') {
                foreach (glob($root . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . $slug . '*.tpl') ?: [] as $t) {
                    $rel[] = 'ui' . DIRECTORY_SEPARATOR . basename($t);
                }
            }
        }
        return array_values(array_unique($rel));
    }

    /**
     * @return array ['ok'=>bool, 'message'=>string, 'file'=>?string]
     */
    public static function create(string $type, string $slug, string $reason): array
    {
        $files = self::files($type, $slug);
        if (!$files) {
            return ['ok' => true, 'message' => 'Nothing to back up', 'file' => null];
        }
        if (!class_exists('ZipArchive')) {
            return ['ok' => false, 'message' => 'PHP ZIP extension is not available, backup skipped', 'file' => null];
        }
        $root = Extensions::root($type);
        $name = $type . '-' . $slug . '-' . date('Ymd-His') . '-' . preg_replace('/[^a-z]/', '', strtolower($reason)) . '.zip';
        $path = self::dir() . DIRECTORY_SEPARATOR . $name;
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return ['ok' => false, 'message' => 'Could not create backup archive', 'file' => null];
        }
        foreach ($files as $r) {
            $zip->addFile($root . DIRECTORY_SEPARATOR . $r, str_replace('\\', '/', $r));
        }
        $manifest = Extensions::manifest($type, $slug);
        $zip->setArchiveComment(json_encode([
            'type' => $type,
            'slug' => $slug,
            'reason' => $reason,
            'version' => $manifest['version'] ?? (Extensions::getMeta($type, $slug)['version'] ?? ''),
            'created' => date('c'),
        ]));
        if (!$zip->close() || !is_file($path)) {
            return ['ok' => false, 'message' => 'Could not write backup archive', 'file' => null];
        }
        self::prune($type, $slug);
        return ['ok' => true, 'message' => 'Backup created', 'file' => $name];
    }

    private static function prune(string $type, string $slug): void
    {
        $mine = array_filter(self::all(), fn($b) => $b['type'] === $type && $b['slug'] === $slug);
        usort($mine, fn($a, $b) => $b['time'] <=> $a['time']);
        foreach (array_slice($mine, self::keep()) as $old) {
            @unlink(self::dir() . DIRECTORY_SEPARATOR . $old['file']);
        }
    }

    /** All backups, newest first */
    public static function all(): array
    {
        $list = [];
        foreach (glob(self::dir() . DIRECTORY_SEPARATOR . '*.zip') ?: [] as $f) {
            $info = ['type' => '', 'slug' => '', 'reason' => '', 'version' => '', 'created' => ''];
            if (class_exists('ZipArchive')) {
                $z = new ZipArchive();
                if ($z->open($f) === true) {
                    $c = json_decode((string) $z->getArchiveComment(), true);
                    if (is_array($c)) {
                        $info = array_merge($info, $c);
                    }
                    $z->close();
                }
            }
            if ($info['type'] === '' && preg_match('/^(plugin|gateway|device|theme)-(.+)-\d{8}-\d{6}-([a-z]*)\.zip$/', basename($f), $m)) {
                $info['type'] = $m[1];
                $info['slug'] = $m[2];
                $info['reason'] = $m[3];
            }
            $list[] = $info + ['file' => basename($f), 'size' => filesize($f), 'time' => filemtime($f)];
        }
        usort($list, fn($a, $b) => $b['time'] <=> $a['time']);
        return $list;
    }

    public static function path(string $file): ?string
    {
        if (!preg_match('/^[A-Za-z0-9_.-]+\.zip$/', $file)) {
            return null;
        }
        $p = self::dir() . DIRECTORY_SEPARATOR . $file;
        return is_file($p) ? $p : null;
    }

    public static function delete(string $file): bool
    {
        $p = self::path($file);
        return $p !== null && @unlink($p);
    }

    /**
     * Restore an archive: snapshot the current state first, remove the live copy, extract the backup.
     * @return array ['ok'=>bool, 'message'=>string, 'type'=>?string, 'slug'=>?string]
     */
    public static function restore(string $file): array
    {
        $p = self::path($file);
        if ($p === null) {
            return ['ok' => false, 'message' => 'Backup not found'];
        }
        $z = new ZipArchive();
        if ($z->open($p) !== true) {
            return ['ok' => false, 'message' => 'Backup archive is unreadable'];
        }
        $c = json_decode((string) $z->getArchiveComment(), true);
        $type = $c['type'] ?? '';
        $slug = $c['slug'] ?? '';
        if (!Extensions::isType($type) || !Extensions::validSlug($slug)) {
            $z->close();
            return ['ok' => false, 'message' => 'Backup metadata is missing or invalid'];
        }
        // zip-slip guard: only relative, traversal-free entries
        for ($i = 0; $i < $z->numFiles; $i++) {
            $n = (string) $z->getNameIndex($i);
            if ($n === '' || $n[0] === '/' || strpos($n, '..') !== false || preg_match('/^[A-Za-z]:/', $n)) {
                $z->close();
                return ['ok' => false, 'message' => 'Backup contains unsafe paths'];
            }
        }
        $root = Extensions::root($type);
        if (!is_writable($root)) {
            $z->close();
            return ['ok' => false, 'message' => 'Target folder is not writable'];
        }
        $pre = self::create($type, $slug, 'prerestore');
        if (!$pre['ok'] && self::files($type, $slug)) {
            $z->close();
            return ['ok' => false, 'message' => 'Could not snapshot the current version: ' . $pre['message']];
        }
        foreach (self::files($type, $slug) as $r) {
            @unlink($root . DIRECTORY_SEPARATOR . $r);
        }
        Extensions::deleteTree($root . DIRECTORY_SEPARATOR . $slug);
        $ok = $z->extractTo($root);
        $z->close();
        return $ok
            ? ['ok' => true, 'message' => 'Restored', 'type' => $type, 'slug' => $slug]
            : ['ok' => false, 'message' => 'Extraction failed'];
    }
}
