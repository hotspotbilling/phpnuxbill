<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * One installer for plugins, payment gateways, device drivers and themes.
 *
 * Package conventions understood inside a ZIP / GitHub repository:
 *   <type>.json at the root                 -> the package IS a folder-style extension
 *   plugin/ paymentgateway/ device/ theme/  -> children are folder extensions (have <type>.json)
 *                                              or flat files (<slug>.php + ui/*.tpl)
 *   *.php at the root                       -> flat extension (gateway / device / plugin)
 *
 * Before anything is copied the package is checked (PHP syntax, requires_*), and any existing copy
 * is zipped to system/cache/backups/ so it can be restored from Marketplace > Tools.
 */
class ExtensionInstaller
{
    private const SUBFOLDERS = [
        'plugin' => ['plugin'],
        'gateway' => ['paymentgateway', 'paymentGateway', 'payment_gateway'],
        'device' => ['device'],
        'theme' => ['theme'],
    ];
    /** Core drivers the billing engine requires directly; never removed or converted */
    private const CORE_DEVICES = [
        'MikrotikHotspot', 'MikrotikPppoe', 'MikrotikStatic', 'MikrotikVpn',
        'Radius', 'RadiusALE', 'RadiusChillispot', 'RadiusRest', 'Voucher', 'Dummy',
    ];

    public static function isCoreDevice(string $slug): bool
    {
        return in_array($slug, self::CORE_DEVICES, true);
    }

    // ------------------------------------------------------------- download

    public static function workdir(): string
    {
        $dir = Extensions::privateDir('installer') . DIRECTORY_SEPARATOR . 'job_' . bin2hex(random_bytes(6));
        mkdir($dir, 0755, true);
        return $dir;
    }

    /**
     * Download a GitHub repository archive (master, then main) and extract it.
     * @return array ['ok'=>bool,'message'=>string,'dir'=>?string (extracted root),'work'=>?string (delete when done),'slug'=>string]
     */
    public static function downloadGithub(string $githubUrl): array
    {
        global $config;
        $repo = Extensions::githubRepo($githubUrl);
        if ($repo === null) {
            return ['ok' => false, 'message' => 'Only https://github.com/owner/repository URLs are supported'];
        }
        $slug = preg_replace('/[^A-Za-z0-9_-]/', '', basename($repo));
        $work = self::workdir();
        $zipFile = $work . DIRECTORY_SEPARATOR . 'package.zip';
        set_time_limit(0);
        $ok = false;
        foreach (['master', 'main'] as $branch) {
            $fp = fopen($zipFile, 'w+');
            $ch = curl_init($repo . '/archive/refs/heads/' . $branch . '.zip');
            curl_setopt_array($ch, [
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 90,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_FILE => $fp,
                CURLOPT_USERAGENT => 'PHPNuxBill-Marketplace',
            ]);
            if (!empty($config['github_token']) && !empty($config['github_username'])) {
                curl_setopt($ch, CURLOPT_USERPWD, $config['github_username'] . ':' . $config['github_token']);
            }
            curl_exec($ch);
            $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            fclose($fp);
            if ($http === 200 && filesize($zipFile) > 500) {
                $ok = true;
                break;
            }
        }
        if (!$ok) {
            Extensions::deleteTree($work);
            return ['ok' => false, 'message' => 'Could not download the repository archive'];
        }
        $res = self::extract($zipFile, $work . DIRECTORY_SEPARATOR . 'src');
        @unlink($zipFile);
        if (!$res['ok']) {
            Extensions::deleteTree($work);
            return $res;
        }
        return ['ok' => true, 'message' => 'Downloaded', 'dir' => $res['dir'], 'work' => $work, 'slug' => $slug];
    }

    /** The most a package download may be, in bytes. */
    private const MAX_PACKAGE = 50 * 1024 * 1024;
    /** The most a package may take up once unpacked, in bytes. */
    private const MAX_UNPACKED = 200 * 1024 * 1024;
    /** Most files a package may hold, and most redirects a download may follow. */
    private const MAX_ENTRIES = 5000;
    private const MAX_REDIRECTS = 3;

    /**
     * Download a .zip from any https address (a server or CDN of the developer's own) and extract it.
     * Only https is followed, also across redirects; the download stops at 50 MB; when $sha256 is given the file must match it.
     * $sign (public_key, signature, type, id, version) makes the file also carry the developer's signature: see ExtensionSignature.
     * @return array same shape as downloadGithub (slug is '' : the caller knows the name)
     */
    public static function downloadZip(string $url, string $sha256 = '', array $sign = []): array
    {
        $url = Extensions::httpsUrl($url);
        if ($url === '') {
            return ['ok' => false, 'message' => 'The package address must start with https://'];
        }
        $sha256 = strtolower(trim($sha256));
        if ($sha256 !== '' && !preg_match('/^[0-9a-f]{64}$/', $sha256)) {
            return ['ok' => false, 'message' => 'The SHA-256 is not valid: it must be 64 hexadecimal characters'];
        }
        $work = self::workdir();
        $zipFile = $work . DIRECTORY_SEPARATOR . 'package.zip';
        set_time_limit(0);
        $fp = fopen($zipFile, 'w+');
        // Redirects are followed by hand so that every hop is checked: https only, never a private or internal address.
        $http = 0;
        $error = '';
        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            $target = self::publicTarget($url);
            if (isset($target['error'])) {
                $error = $target['error'];
                break;
            }
            ftruncate($fp, 0);
            rewind($fp);
            $location = '';
            $ch = curl_init($url);
            // the file goes first: if any later option were refused, the body must still never be printed to the page
            $set = curl_setopt($ch, CURLOPT_FILE, $fp) && curl_setopt_array($ch, [
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_RESOLVE => [$target['host'] . ':' . $target['port'] . ':' . $target['ip']], // connect to the address that was checked
                CURLOPT_USERAGENT => 'PHPNuxBill-Marketplace',
                CURLOPT_NOPROGRESS => false,
                CURLOPT_PROGRESSFUNCTION => function ($ch, $total, $got) {
                    return $got > self::MAX_PACKAGE ? 1 : 0; // non-zero aborts the transfer
                },
                CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$location) {
                    if (stripos($line, 'location:') === 0) {
                        $location = trim(substr($line, 9));
                    }
                    return strlen($line);
                },
            ]);
            if ($set) {
                curl_exec($ch);
            }
            $http = $set ? curl_getinfo($ch, CURLINFO_HTTP_CODE) : 0;
            curl_close($ch);
            if (in_array($http, [301, 302, 303, 307, 308], true) && $location !== '') {
                $url = self::absoluteUrl($url, $location);
                if ($url === '') {
                    $error = 'The package address redirected somewhere that is not https';
                    break;
                }
                $http = 0;
                continue;
            }
            break;
        }
        fclose($fp);
        if ($error === '' && $http >= 300 && $http < 400) {
            $error = 'The package address redirected too many times';
        }
        clearstatcache(true, $zipFile);
        $size = (int) @filesize($zipFile);
        $magic = (string) @file_get_contents($zipFile, false, null, 0, 2);
        $fail = function ($msg) use ($work) {
            Extensions::deleteTree($work);
            return ['ok' => false, 'message' => $msg];
        };
        if ($error !== '') {
            return $fail($error);
        }
        if ($http !== 200 || $size < 100) {
            return $fail('Could not download the package');
        }
        if ($size > self::MAX_PACKAGE) {
            return $fail('The package is larger than 50 MB');
        }
        if ($magic !== 'PK') {
            return $fail('The address did not return a .zip file');
        }
        if ($sha256 !== '' && !hash_equals($sha256, (string) hash_file('sha256', $zipFile))) {
            return $fail('The package does not match the SHA-256 listed for it, so it was not installed');
        }
        if ($sign) {
            $ok = ExtensionSignature::verify((string) ($sign['public_key'] ?? ''), (string) ($sign['signature'] ?? ''), (string) ($sign['type'] ?? ''), (string) ($sign['id'] ?? ''), (string) ($sign['version'] ?? ''), (string) hash_file('sha256', $zipFile));
            if (!$ok) {
                return $fail('The package signature is not valid for this file, so it was not installed');
            }
        }
        $res = self::extract($zipFile, $work . DIRECTORY_SEPARATOR . 'src');
        @unlink($zipFile);
        if (!$res['ok']) {
            return $fail($res['message']);
        }
        return ['ok' => true, 'message' => 'Downloaded', 'dir' => $res['dir'], 'work' => $work, 'slug' => ''];
    }

    /**
     * Is this https address one the server may fetch? The host must resolve only to public addresses (nothing loopback, private,
     * link-local or reserved, which is where an internal service or cloud metadata would sit). Returns the host, port and the
     * checked address (the download connects to exactly that address, so a DNS answer that changes later gains nothing),
     * or ['error' => text].
     */
    private static function publicTarget(string $url): array
    {
        $p = parse_url($url);
        if (!$p || strtolower($p['scheme'] ?? '') !== 'https' || empty($p['host']) || isset($p['user']) || isset($p['pass'])) {
            return ['error' => 'The package address must be a plain https:// address'];
        }
        $host = strtolower(trim($p['host'], '[]'));
        $port = (int) ($p['port'] ?? 443);
        if ($port < 1 || $port > 65535) {
            return ['error' => 'The package address has an invalid port'];
        }
        $ips = [];
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips[] = $host;
        } else {
            foreach (@dns_get_record($host, DNS_A | DNS_AAAA) ?: [] as $r) {
                $ips[] = $r['ip'] ?? $r['ipv6'] ?? '';
            }
            if (!$ips) {
                $ips = @gethostbynamel($host) ?: [];
            }
        }
        $ips = array_values(array_filter($ips));
        if (!$ips) {
            return ['error' => 'The package address could not be found'];
        }
        foreach ($ips as $ip) {
            if (!self::publicIp($ip)) {
                return ['error' => 'The package address points to a private or internal address, so it was not downloaded'];
            }
        }
        $ip = $ips[0];
        return ['host' => $host, 'port' => $port, 'ip' => strpos($ip, ':') !== false ? '[' . $ip . ']' : $ip];
    }

    /** Public internet address? Rejects private, loopback, link-local and reserved ranges, and IPv4 inside IPv6. */
    private static function publicIp(string $ip): bool
    {
        if (strpos($ip, ':') !== false) {
            $bin = @inet_pton($ip);
            if ($bin === false) {
                return false;
            }
            if (substr($bin, 0, 12) === str_repeat("\0", 10) . "\xff\xff") { // ::ffff:a.b.c.d, 6to4-style mappings: judge the IPv4 part
                $ip = inet_ntop(substr($bin, 12));
            } elseif (substr($bin, 0, 2) === "\x20\x02" || substr($bin, 0, 4) === "\x20\x01\x00\x00") { // 6to4 and Teredo tunnel to anything
                return false;
            }
        }
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /** A redirect's Location as a full https address ('' if it is not one): absolute, protocol-relative or root-relative. */
    private static function absoluteUrl(string $from, string $location): string
    {
        $p = parse_url($from);
        $base = 'https://' . ($p['host'] ?? '') . (isset($p['port']) ? ':' . $p['port'] : '');
        if (preg_match('#^https://#i', $location)) {
            return $location;
        }
        if (strpos($location, '//') === 0) {
            return 'https:' . $location;
        }
        if (isset($location[0]) && $location[0] === '/') {
            return $base . $location;
        }
        return ''; // plain http:, other schemes and bare relative paths are not followed
    }

    /** Download a package from whichever source an entry or manifest names: its zip, else its GitHub repository. */
    public static function downloadSource(array $src): array
    {
        if (!empty($src['zip'])) {
            return self::downloadZip((string) $src['zip'], (string) ($src['sha256'] ?? ''), (array) ($src['sign'] ?? []));
        }
        if (!empty($src['github'])) {
            return self::downloadGithub((string) $src['github']);
        }
        return ['ok' => false, 'message' => 'No package source is known'];
    }

    /** @return array same shape as downloadGithub, for an uploaded .zip */
    public static function fromZipUpload(string $tmpFile): array
    {
        $work = self::workdir();
        $res = self::extract($tmpFile, $work . DIRECTORY_SEPARATOR . 'src');
        if (!$res['ok']) {
            Extensions::deleteTree($work);
            return $res;
        }
        return ['ok' => true, 'message' => 'Extracted', 'dir' => $res['dir'], 'work' => $work, 'slug' => ''];
    }

    /** @return array same shape, for a single uploaded .php driver */
    public static function fromPhpUpload(string $tmpFile, string $originalName): array
    {
        $name = preg_replace('/[^A-Za-z0-9_]/', '', pathinfo($originalName, PATHINFO_FILENAME));
        if ($name === '' || in_array(strtolower($name), ['index', 'readme'], true)) {
            return ['ok' => false, 'message' => 'Invalid file name'];
        }
        $work = self::workdir();
        $src = $work . DIRECTORY_SEPARATOR . 'src';
        mkdir($src, 0755, true);
        if (!move_uploaded_file($tmpFile, $src . DIRECTORY_SEPARATOR . $name . '.php')) {
            Extensions::deleteTree($work);
            return ['ok' => false, 'message' => 'Failed to move uploaded file'];
        }
        return ['ok' => true, 'message' => 'Uploaded', 'dir' => $src, 'work' => $work, 'slug' => $name];
    }

    /** Zip-slip safe extraction; descends into a single top-level folder (GitHub style) */
    private static function extract(string $zipFile, string $to): array
    {
        if (!class_exists('ZipArchive')) {
            return ['ok' => false, 'message' => 'PHP ZIP extension is not available'];
        }
        $z = new ZipArchive();
        if ($z->open($zipFile) !== true) {
            return ['ok' => false, 'message' => 'Cannot open ZIP file'];
        }
        for ($i = 0; $i < $z->numFiles; $i++) {
            $n = (string) $z->getNameIndex($i);
            if ($n === '' || $n[0] === '/' || $n[0] === '\\' || strpos($n, '..') !== false || preg_match('/^[A-Za-z]:/', $n)) {
                $z->close();
                return ['ok' => false, 'message' => 'The package contains unsafe paths'];
            }
        }
        if ($z->numFiles > self::MAX_ENTRIES) {
            $z->close();
            return ['ok' => false, 'message' => 'The package holds too many files (over ' . self::MAX_ENTRIES . ')'];
        }
        // a small zip can expand to gigabytes: add up the real sizes first and extract nothing if the total is unreasonable
        $total = 0;
        for ($i = 0; $i < $z->numFiles; $i++) {
            $st = $z->statIndex($i);
            $total += (int) ($st['size'] ?? 0);
            if ($total > self::MAX_UNPACKED) {
                $z->close();
                return ['ok' => false, 'message' => 'The package is too large once unpacked (over 200 MB)'];
            }
        }
        mkdir($to, 0755, true);
        $ok = $z->extractTo($to);
        $z->close();
        if (!$ok) {
            return ['ok' => false, 'message' => 'Extraction failed'];
        }
        $entries = array_values(array_filter(scandir($to) ?: [], fn($e) => $e !== '.' && $e !== '..' && $e !== '__MACOSX'));
        $dir = $to;
        $conventional = ['plugin', 'paymentgateway', 'paymentGateway', 'payment_gateway', 'device', 'theme'];
        // a lone wrapper folder (GitHub "repo-main/") is stepped into, but a lone "theme/" or "plugin/" is the package layout itself
        if (count($entries) === 1 && is_dir($to . DIRECTORY_SEPARATOR . $entries[0]) && !in_array($entries[0], $conventional, true)) {
            $dir = $to . DIRECTORY_SEPARATOR . $entries[0];
        }
        return ['ok' => true, 'message' => 'Extracted', 'dir' => $dir];
    }

    // -------------------------------------------------------------- detect

    /**
     * Work out what a package contains.
     * @return array list of ['type','mode'=>'folder'|'flat'|'merge','slug','src','manifest']
     */
    public static function detect(string $hint, string $dir, string $slugHint = ''): array
    {
        $d = rtrim($dir, '/\\');
        $items = [];

        // 1. the package root is itself a folder-style extension of the hinted type
        if (Extensions::isType($hint) && is_file($d . DIRECTORY_SEPARATOR . Extensions::manifestName($hint))) {
            $m = json_decode((string) file_get_contents($d . DIRECTORY_SEPARATOR . Extensions::manifestName($hint)), true);
            $m = is_array($m) ? $m : [];
            $slug = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($m['slug'] ?? ($slugHint !== '' ? $slugHint : preg_replace('/-(main|master)$/', '', basename($d)))));
            return [['type' => $hint, 'mode' => 'folder', 'slug' => $slug, 'src' => $d, 'manifest' => $m]];
        }

        // 2. conventional sub-folders
        foreach (self::SUBFOLDERS as $type => $names) {
            foreach ($names as $sub) {
                $s = $d . DIRECTORY_SEPARATOR . $sub;
                if (!is_dir($s)) {
                    continue;
                }
                $manifestName = Extensions::manifestName($type);
                $hasChildren = false;
                foreach (glob($s . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . $manifestName) ?: [] as $mf) {
                    $m = json_decode((string) file_get_contents($mf), true);
                    $items[] = ['type' => $type, 'mode' => 'folder', 'slug' => basename(dirname($mf)), 'src' => dirname($mf), 'manifest' => is_array($m) ? $m : []];
                    $hasChildren = true;
                }
                if ($hasChildren) {
                    break;
                }
                if (is_file($s . DIRECTORY_SEPARATOR . $manifestName)) { // <plugin>/plugin.json
                    $m = json_decode((string) file_get_contents($s . DIRECTORY_SEPARATOR . $manifestName), true);
                    $m = is_array($m) ? $m : [];
                    $slug = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($m['slug'] ?? $slugHint));
                    if ($slug !== '') {
                        $items[] = ['type' => $type, 'mode' => 'folder', 'slug' => $slug, 'src' => $s, 'manifest' => $m];
                        break;
                    }
                }
                if ($type === 'plugin') {
                    $items[] = ['type' => 'plugin', 'mode' => 'merge', 'slug' => self::phpSlugs($s)[0] ?? $slugHint, 'src' => $s, 'manifest' => []];
                } elseif ($type === 'theme') {
                    continue; // themes are folder-only
                } else {
                    foreach (self::phpSlugs($s) as $slug) {
                        $items[] = ['type' => $type, 'mode' => 'flat', 'slug' => $slug, 'src' => $s, 'manifest' => []];
                    }
                }
                break;
            }
        }
        if ($items) {
            return $items;
        }

        // 3. folder extensions sitting directly at the package root (<root>/<slug>/<type>.json)
        if (Extensions::isType($hint)) {
            foreach (glob($d . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . Extensions::manifestName($hint)) ?: [] as $mf) {
                $m = json_decode((string) file_get_contents($mf), true);
                $items[] = ['type' => $hint, 'mode' => 'folder', 'slug' => basename(dirname($mf)), 'src' => dirname($mf), 'manifest' => is_array($m) ? $m : []];
            }
            if ($items) {
                return $items;
            }
        }

        // 4. loose files at the package root
        $slugs = self::phpSlugs($d);
        if ($hint === 'plugin' && ($slugs || is_dir($d . DIRECTORY_SEPARATOR . 'ui'))) {
            return [['type' => 'plugin', 'mode' => 'merge', 'slug' => $slugs[0] ?? $slugHint, 'src' => $d, 'manifest' => []]];
        }
        if (in_array($hint, ['gateway', 'device'], true)) {
            foreach ($slugs as $slug) {
                $items[] = ['type' => $hint, 'mode' => 'flat', 'slug' => $slug, 'src' => $d, 'manifest' => []];
            }
        }
        return $items;
    }

    private static function phpSlugs(string $dir): array
    {
        $out = [];
        foreach (glob(rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . '*.php') ?: [] as $f) {
            $n = basename($f, '.php');
            if (!in_array(strtolower($n), ['index', 'readme'], true) && Extensions::validSlug($n)) {
                $out[] = $n;
            }
        }
        return $out;
    }

    /** First PHP syntax error among a package's PHP files, or null */
    private static function lintTree(string $dir, bool $recursive = true): ?string
    {
        $count = 0;
        $it = $recursive
            ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS))
            : new IteratorIterator(new DirectoryIterator($dir));
        foreach ($it as $f) {
            if (!$f->isFile() || strtolower($f->getExtension()) !== 'php') {
                continue;
            }
            if (++$count > 400) {
                break;
            }
            $err = Extensions::lint($f->getPathname());
            if ($err !== null) {
                return $f->getFilename() . ' - ' . $err;
            }
        }
        return null;
    }

    // ------------------------------------------------------------- install

    /**
     * Install whatever a package contains.
     * @param array $opts reason: install|update|upload, store: store item array, source: label, slug: slug hint
     * @return array ['ok'=>bool,'message'=>string,'installed'=>[['type','slug','mode']],'backups'=>string[],'warnings'=>string[]]
     */
    public static function install(string $hint, string $dir, array $opts = []): array
    {
        $reason = $opts['reason'] ?? 'install';
        $items = self::detect($hint, $dir, (string) ($opts['slug'] ?? ''));
        if (!$items) {
            return self::fail('Nothing installable was found in the package. Expected ' . Extensions::manifestName($hint)
                . ' at the root, a ' . (self::SUBFOLDERS[$hint][0] ?? $hint) . '/ folder, or a <name>.php driver.');
        }
        // 1. verify everything before touching the live site
        foreach ($items as $it) {
            if ($it['slug'] === '' || !Extensions::validSlug($it['slug'])) {
                return self::fail('Could not determine a valid name for the ' . $it['type']);
            }
            if (in_array($it['type'], ['gateway', 'device'], true) && !Extensions::validIdentifier($it['slug'])) {
                return self::fail(Extensions::label($it['type']) . ' names may only contain letters, numbers and underscores: ' . $it['slug']);
            }
            if (!Extensions::isWritable($it['type'])) {
                return self::fail(Extensions::label($it['type']) . ' folder is not writable: ' . Extensions::root($it['type']));
            }
            if ($it['type'] === 'device' && self::isCoreDevice($it['slug']) && $reason === 'upload') {
                return self::fail('Core device drivers cannot be replaced by an upload');
            }
            $issues = Extensions::check($it['manifest']);
            if (Extensions::hasBlocking($issues)) {
                return self::fail($it['slug'] . ': ' . implode('; ', array_column(array_filter($issues, fn($i) => $i['level'] === 'error'), 'message')));
            }
            $lint = self::lintTree($it['src'], $it['mode'] !== 'flat');
            if ($lint !== null) {
                return self::fail($it['slug'] . ' has a PHP error and was not installed: ' . $lint);
            }
        }
        // 2. back up, then copy
        $installed = [];
        $backups = [];
        $warnings = [];
        foreach ($items as $it) {
            $type = $it['type'];
            $slug = $it['slug'];
            $root = Extensions::root($type);
            if ($it['mode'] === 'merge') {
                $slugs = self::phpSlugs($it['src']);
                foreach ($slugs ?: [$slug] as $s) {
                    $b = ExtensionBackup::create($type, $s, $reason);
                    if (!$b['ok']) {
                        $warnings[] = $b['message'];
                    } elseif ($b['file']) {
                        $backups[] = $b['file'];
                    }
                }
                Extensions::copyTree($it['src'], $root, ['README.md', 'LICENSE', 'readme.md', '.github', '.gitignore']);
                foreach ($slugs ?: [$slug] as $s) {
                    self::record($type, $s, [], $opts);
                    $installed[] = ['type' => $type, 'slug' => $s, 'mode' => 'flat'];
                }
                continue;
            }
            $b = ExtensionBackup::create($type, $slug, $reason);
            if (!$b['ok']) {
                $warnings[] = $b['message'];
            } elseif ($b['file']) {
                $backups[] = $b['file'];
            }
            if ($it['mode'] === 'folder') {
                $dest = $root . DIRECTORY_SEPARATOR . $slug;
                if ($reason !== 'update') {
                    Extensions::deleteTree($dest); // clean replace on reinstall; updates merge to keep user data
                }
                if (Extensions::copyTree($it['src'], $dest, ['.github', '.gitignore']) === 0) {
                    return self::fail('Could not copy files into ' . $dest);
                }
                $installed[] = ['type' => $type, 'slug' => $slug, 'mode' => 'folder'];
                self::record($type, $slug, $it['manifest'], $opts);
            } else { // flat gateway / device
                copy($it['src'] . DIRECTORY_SEPARATOR . $slug . '.php', $root . DIRECTORY_SEPARATOR . $slug . '.php');
                $ui = $it['src'] . DIRECTORY_SEPARATOR . 'ui';
                if ($type === 'gateway' && is_dir($ui)) {
                    $destUi = $root . DIRECTORY_SEPARATOR . 'ui';
                    @mkdir($destUi, 0755, true);
                    foreach (glob($ui . DIRECTORY_SEPARATOR . '*.tpl') ?: [] as $t) {
                        copy($t, $destUi . DIRECTORY_SEPARATOR . basename($t));
                    }
                }
                $installed[] = ['type' => $type, 'slug' => $slug, 'mode' => 'flat'];
                self::record($type, $slug, [], $opts);
            }
        }
        GatewayLoader::forget();
        DeviceLoader::forget();
        foreach ($installed as $i) {
            Extensions::log($reason === 'update' ? 'update' : 'install', $i['type'], $i['slug'], $i['mode'] . ' style' . (!empty($opts['source']) ? ' from ' . $opts['source'] : ''));
        }
        return ['ok' => true, 'message' => 'Installed', 'installed' => $installed, 'backups' => $backups, 'warnings' => $warnings];
    }

    private static function record(string $type, string $slug, array $manifest, array $opts): void
    {
        $store = $opts['store'] ?? [];
        Extensions::setMeta($type, $slug, [
            'version' => $manifest['version'] ?? '',
            'repo_updated' => $store['last_update'] ?? '',
            'store_id' => $store['id'] ?? '',
            'installed_at' => date('c'),
            'source' => $opts['source'] ?? 'upload',
        ] + (!empty($opts['signing_key']) ? ['signing_key' => $opts['signing_key']] : []));
    }

    private static function fail(string $msg): array
    {
        return ['ok' => false, 'message' => $msg, 'installed' => [], 'backups' => [], 'warnings' => []];
    }

    /** Download a store item (or its update) and install it */
    public static function fromStore(string $type, string $id, string $reason = 'install', ?string $installedSlug = null): array
    {
        $item = MarketplaceRepo::find($type, $id);
        if (!$item) {
            return self::fail('Not found in the repository');
        }
        if (empty($item['zip']) && empty($item['github'])) {
            return self::fail('This repository entry has no zip or GitHub source to install from');
        }
        $slugHint = $installedSlug ?: preg_replace('/[^A-Za-z0-9_-]/', '', $item['id']);
        $key = (string) ($item['public_key'] ?? '');
        $sig = (string) ($item['signature'] ?? '');
        // The key an extension was first installed with is remembered. From then on only that key may sign its updates, and an
        // entry that drops the signature is refused, so taking over the repository file is not enough to push code.
        $pinned = (string) (Extensions::getMeta($type, $slugHint)['signing_key'] ?? '');
        if ($pinned !== '' && ($sig === '' || !hash_equals($pinned, $key))) {
            return self::fail($slugHint . ' was installed with a signed release, and this repository entry is not signed by the same key, so it was not installed. To change the key, uninstall it first.');
        }
        $signed = $key !== '' || $sig !== '';
        $sign = [];
        if ($signed) {
            if (!ExtensionSignature::available()) {
                return self::fail('The PHP sodium extension is needed to check this signed package');
            }
            if (!ExtensionSignature::validKey($key) || !ExtensionSignature::validSignature($sig) || !ExtensionSignature::validVersion((string) ($item['version'] ?? ''))) {
                return self::fail('The repository entry has an incomplete or malformed signature (public_key, signature and version are all needed)');
            }
            if (empty($item['zip'])) {
                return self::fail('Only a package listed with a zip address can be signed');
            }
            $sign = ['public_key' => $key, 'signature' => $sig, 'type' => $type, 'id' => (string) $item['id'], 'version' => (string) $item['version']];
        }
        $dl = self::downloadSource($item + ['sign' => $sign]);
        if (!$dl['ok']) {
            return self::fail($dl['message']);
        }
        try {
            if ($signed) { // the signed version must be the version inside the package, or an old signed zip could stand in for a new one
                foreach (self::detect($type, $dl['dir'], $slugHint) as $it) {
                    $inside = (string) ($it['manifest']['version'] ?? '');
                    if ($inside !== '' && $inside !== (string) $item['version']) {
                        return self::fail('The package is version ' . $inside . ' but the signed version is ' . $item['version'] . ', so it was not installed');
                    }
                }
            }
            return self::install($type, $dl['dir'], ['reason' => $reason, 'store' => $item, 'source' => $item['repo'] ?? 'store', 'slug' => $slugHint, 'signing_key' => $signed ? $key : '']);
        } finally {
            Extensions::deleteTree($dl['work']);
        }
    }

    // ----------------------------------------------------------- uninstall

    /**
     * Remove an extension after backing it up.
     * @return array ['ok'=>bool,'message'=>string]
     */
    public static function uninstall(string $type, string $slug): array
    {
        global $config;
        if (!Extensions::isType($type) || !Extensions::validSlug($slug)) {
            return ['ok' => false, 'message' => 'Invalid name'];
        }
        $root = Extensions::root($type);
        $folder = $root . DIRECTORY_SEPARATOR . $slug;
        $flat = $root . DIRECTORY_SEPARATOR . $slug . '.php';
        // a directory only counts as an extension when it carries its manifest (never "ui/" or other shared folders)
        $isFolder = Extensions::isFolder($type, $slug);
        if (!$isFolder && !is_file($flat)) {
            return ['ok' => false, 'message' => 'Not found'];
        }
        if (!is_writable($root)) {
            return ['ok' => false, 'message' => 'Folder is not writable'];
        }
        if ($type === 'device') {
            if (self::isCoreDevice($slug)) {
                return ['ok' => false, 'message' => 'Core device drivers cannot be uninstalled'];
            }
            $used = ORM::for_table('tbl_plans')->where('device', $slug)->count();
            if ($used > 0) {
                return ['ok' => false, 'message' => $used . ' plan(s) still use this driver. Move them to another device first.'];
            }
        }
        if ($type === 'theme') {
            if (($config['theme'] ?? '') === $slug) {
                return ['ok' => false, 'message' => 'Cannot uninstall the active theme. Switch to another theme first.'];
            }
            // refuse to remove a theme the active theme inherits from
            $cur = (string) ($config['theme'] ?? '');
            $seen = [];
            while ($cur !== '' && $cur !== 'default' && Extensions::validSlug($cur) && !isset($seen[$cur])) {
                $seen[$cur] = true;
                $parent = Extensions::manifest('theme', $cur)['parent'] ?? '';
                $cur = is_string($parent) ? $parent : '';
                if ($cur === $slug) {
                    return ['ok' => false, 'message' => 'This theme is the parent of the active theme. Switch themes first.'];
                }
            }
        }
        $b = ExtensionBackup::create($type, $slug, 'uninstall');
        if (!$b['ok'] && class_exists('ZipArchive')) {
            return ['ok' => false, 'message' => 'Aborted, could not back up first: ' . $b['message']];
        }
        if ($type === 'plugin' && $isFolder) {
            $hook = PluginLoader::runUninstall($slug);
            if (!$hook['success']) {
                return ['ok' => false, 'message' => 'Uninstall hook failed: ' . $hook['message']];
            }
            $state = PluginLoader::getState();
            $state['disabled'] = array_values(array_diff($state['disabled'] ?? [], [$slug]));
            file_put_contents(Extensions::cacheDir() . DIRECTORY_SEPARATOR . 'plugins.json', json_encode($state));
        }
        if ($isFolder) {
            Extensions::deleteTree($folder);
        }
        if (is_file($flat)) {
            @unlink($flat);
        }
        foreach (Extensions::flatTemplates($type, $slug) as $t) {
            @unlink($t);
        }
        if ($type === 'gateway') {
            $actives = array_filter(array_map('trim', explode(',', (string) ($config['payment_gateway'] ?? ''))));
            if (in_array($slug, $actives, true)) {
                Extensions::setConfig('payment_gateway', implode(',', array_diff($actives, [$slug])));
            }
        }
        Extensions::removeMeta($type, $slug);
        GatewayLoader::forget();
        DeviceLoader::forget();
        Extensions::log('uninstall', $type, $slug, $b['file'] ? 'backup ' . $b['file'] : '');
        return ['ok' => true, 'message' => 'Uninstalled', 'backup' => $b['file']];
    }

    // ------------------------------------------------------------- convert

    /**
     * Convert a flat gateway / device into the folder layout (non-lossy: backed up first).
     * @return array ['ok'=>bool,'message'=>string]
     */
    public static function convert(string $type, string $slug): array
    {
        if (!in_array($type, ['gateway', 'device'], true) || !Extensions::validIdentifier($slug)) {
            return ['ok' => false, 'message' => 'Invalid name'];
        }
        if ($type === 'device' && self::isCoreDevice($slug)) {
            return ['ok' => false, 'message' => 'Core device drivers must stay as single files'];
        }
        $root = Extensions::root($type);
        $src = $root . DIRECTORY_SEPARATOR . $slug . '.php';
        $dest = $root . DIRECTORY_SEPARATOR . $slug;
        if (!is_file($src)) {
            return ['ok' => false, 'message' => 'Flat file not found'];
        }
        if (is_file($dest . DIRECTORY_SEPARATOR . Extensions::manifestName($type))) {
            return ['ok' => false, 'message' => 'Already in folder style'];
        }
        if (!is_writable($root)) {
            return ['ok' => false, 'message' => 'Folder is not writable'];
        }
        $b = ExtensionBackup::create($type, $slug, 'convert');
        if (!$b['ok'] && class_exists('ZipArchive')) {
            return ['ok' => false, 'message' => 'Aborted, could not back up first: ' . $b['message']];
        }
        $code = (string) file_get_contents($src);
        $moved = [];
        if (!is_dir($dest) && !mkdir($dest, 0755, true)) {
            return ['ok' => false, 'message' => 'Cannot create ' . $dest];
        }
        if ($type === 'gateway') {
            $uiSrc = $root . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR;
            $tpls = [];
            foreach (Extensions::flatTemplates('gateway', $slug) as $t) {
                $tpls[basename($t)] = true;
            }
            if (preg_match_all('/[\'"](?:\[pg\]|pg:)?([A-Za-z0-9_\-\/]+\.tpl)[\'"]/', $code, $mm)) {
                foreach ($mm[1] as $t) {
                    if (is_file($uiSrc . $t)) {
                        $tpls[$t] = true;
                    }
                }
            }
            if ($tpls) {
                @mkdir($dest . DIRECTORY_SEPARATOR . 'ui', 0755, true);
                foreach (array_keys($tpls) as $t) {
                    $to = $dest . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . $t;
                    @mkdir(dirname($to), 0755, true);
                    copy($uiSrc . $t, $to);
                    $moved[] = $uiSrc . $t;
                }
            }
            // "[pg]foo.tpl" named-directory syntax no longer resolves; "pg:foo.tpl" resolves in both layouts
            $code = preg_replace('/([\'"])\[pg\]/', '$1pg:', $code);
        }
        file_put_contents($dest . DIRECTORY_SEPARATOR . $slug . '.php', $code);
        $info = Extensions::headerInfo($src);
        $manifest = [
            'name' => $info['title'] ?? ($info['name'] ?? $slug),
            'slug' => $slug,
            'version' => $info['version'] ?? '1.0.0',
            'description' => $info['description'] ?? '',
            'author' => $info['author'] ?? '',
            'requires_php' => '8.2',
            'main' => $slug . '.php',
            'converted_from_flat' => true,
        ];
        file_put_contents($dest . DIRECTORY_SEPARATOR . Extensions::manifestName($type), json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        @unlink($src);
        foreach ($moved as $m) {
            @unlink($m);
        }
        GatewayLoader::forget();
        DeviceLoader::forget();
        Extensions::log('convert', $type, $slug, 'flat -> folder');
        return ['ok' => true, 'message' => 'Converted to folder style', 'backup' => $b['file']];
    }
}
