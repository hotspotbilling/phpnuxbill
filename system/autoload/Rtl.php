<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * Right-to-left pages for Arabic, Urdu, Persian, Hebrew and the other right-to-left languages.
 *
 * When the language in use is one of them, every page Smarty renders goes through filter(): the <html> tag gets dir="rtl"
 * and the language code, each local stylesheet is swapped for a mirrored copy (made once and kept in ui/cache/rtl/ until its
 * source changes), <style> blocks and style="" attributes are mirrored the same way, and ui/ui/styles/rtl.css is added last
 * for what mirroring alone cannot get right (arrow icons, charts). Templates, themes and plugins need no changes.
 *
 * Mirroring swaps left and right: margin-left becomes margin-right, float: left becomes float: right, the 2nd and 4th
 * values of margin/padding/border shorthands change places, and so on. A rule or declaration that must stay as it is
 * (a chart axis, a drawing) is marked with a comment just before it: / * @noflip * / (written without the spaces).
 */
class Rtl
{
    /** Language codes written right to left */
    const LANGS = ['ar', 'arc', 'ckb', 'dv', 'fa', 'he', 'iw', 'ks', 'ku', 'ps', 'sd', 'syr', 'ug', 'ur', 'yi'];

    /**
     * Stylesheets of components that turn themselves right to left (they read the page's direction): mirrored again, they would
     * be the wrong way round. Matched against the start of the file name.
     */
    const OWN_RTL = ['sweetalert2', 'select2'];

    /** Bumped when the mirroring changes, so the copies already made are made again */
    const VERSION = 10;

    /**
     * Put on what has been mirrored already. Smarty runs the filter on fetch() as well as display(), so a part fetched and then
     * shown inside a page (a dashboard widget) goes through twice: what carries the mark is left alone the second time, as
     * mirroring it again would turn it back.
     */
    const MARK = '/*rtl*/';

    /** The language code (ar, en, ...) of the language in use, from system/lan/country.json */
    public static function iso()
    {
        global $config;
        $lang = (string) ($config['language'] ?? 'english');
        $iso = Lang::getIsoLang()[$lang] ?? '';
        return is_string($iso) ? strtolower($iso) : '';
    }

    /** Whether the language in use is written right to left */
    public static function active()
    {
        return in_array(self::iso(), self::LANGS, true);
    }

    /* ---------- the page ---------- */

    /** Smarty output filter: mirror a rendered page when the language is right to left; any other page is left as it is */
    public static function filter($html)
    {
        if (!is_string($html) || $html === '' || !self::active()) {
            return $html;
        }
        try {
            // script blocks are left alone: positions worked out by code are not CSS. So is what a textarea holds: it is text
            // being edited (a voucher template's HTML), saved back as it is, and must not come back mirrored
            $parts = preg_split('/(<script\b[^>]*>.*?<\/script\s*>|<textarea\b[^>]*>.*?<\/textarea\s*>)/is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
            if ($parts === false) {
                return $html;
            }
            $extra = false;
            foreach ($parts as $i => $part) {
                if ($i % 2 === 1) {
                    // a textarea's start tag is mirrored like any other (its style="" and so on); what it holds is text being
                    // edited and is left exactly as it is. A script block is left whole.
                    if (preg_match('/^(<textarea\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>)(.*)$/is', $part, $ta)) {
                        $open = self::styleAttributes($ta[1]);
                        $parts[$i] = ($open === null ? $ta[1] : $open) . $ta[2];
                    }
                    continue;
                }
                // a step that fails (a regular expression that gives up on very large input returns null) keeps the page as it was
                $part = self::htmlTag($part);
                $part = $part === null ? null : preg_replace_callback('/<link\b[^>]*>/i', function ($m) {
                    return self::link($m[0]);
                }, $part);
                $part = $part === null ? null : preg_replace_callback('/(<style\b[^>]*>)(.*?)(<\/style\s*>)/is', function ($m) {
                    if (strpos($m[2], self::MARK) !== false) {
                        return $m[0];
                    }
                    $flipped = self::flip($m[2]);
                    return $flipped === null ? $m[0] : $m[1] . self::MARK . $flipped . $m[3];
                }, $part);
                $part = $part === null ? null : self::outsideStyleBlocks($part);
                if ($part === null) {
                    return $html;
                }
                if (!$extra && stripos($part, '</head>') !== false && stripos($part, '/ui/ui/styles/rtl.css') === false) {
                    $part = preg_replace('/<\/head>/i', self::extraLink() . '</head>', $part, 1);
                    if ($part === null) {
                        return $html;
                    }
                    $extra = true;
                }
                $parts[$i] = $part;
            }
            return implode('', $parts);
        } catch (Throwable $e) {
            return $html; // a page shown left to right is better than no page
        }
    }

    /** style="" attributes mirrored on the real tags only: the text of a <style> block (already mirrored) is never read as tags */
    private static function outsideStyleBlocks($part)
    {
        $pieces = preg_split('/(<style\b[^>]*>.*?<\/style\s*>)/is', $part, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($pieces === false) {
            return null;
        }
        foreach ($pieces as $i => $piece) {
            if ($i % 2 === 1) {
                continue;
            }
            $piece = self::styleAttributes($piece);
            if ($piece === null) {
                return null;
            }
            $pieces[$i] = $piece;
        }
        return implode('', $pieces);
    }

    /**
     * The style="" attributes of the start tags, mirrored. Each tag is read attribute by attribute, so text that only looks like
     * one (in a title="", or in the page's text) is left as it is. A tag that cannot be read is left as it is too.
     * (an entity such as &quot; holds a semicolon: it splits into a part with no colon, which is kept as it is)
     */
    private static function styleAttributes($part)
    {
        $attr = '(?:\s+[^\s"\'>\/=]+(?:\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s"\'>]+))?)';
        return preg_replace_callback('/<([a-zA-Z][\w:-]*)(' . $attr . '*)(\s*\/?>)/', function ($t) {
            if (stripos($t[2], 'style') === false) {
                return $t[0];
            }
            $attrs = preg_replace_callback('/(\s+)([^\s"\'>\/=]+)(?:(\s*=\s*)("[^"]*"|\'[^\']*\'|[^\s"\'>]+))?/', function ($a) {
                if (strtolower($a[2]) !== 'style' || !isset($a[4]) || $a[4] === '' || ($a[4][0] !== '"' && $a[4][0] !== "'")) {
                    return $a[0];
                }
                $q = $a[4][0];
                $v = substr($a[4], 1, -1);
                if (trim($v) === '' || strpos($v, self::MARK) !== false) {
                    return $a[0];
                }
                // comments and strings are put aside first, as flip() does for a stylesheet: a @noflip comment keeps the
                // declaration after it as it is, and a quoted file name (in image-set(), or content: "left") is not mirrored
                $comments = [];
                $strings = [];
                $p = self::protect($v, $comments, $strings);
                $v = $p === null ? null : self::restore(self::declarations($p), $comments, $strings);
                if ($v === null) {
                    return $a[0];
                }
                return $a[1] . $a[2] . $a[3] . $q . $v . (substr(rtrim($v), -1) === ';' ? '' : ';') . self::MARK . $q;
            }, $t[2]);
            return '<' . $t[1] . $attrs . $t[3];
        }, $part);
    }

    /** dir="rtl" and the language code on <html>, unless the template set a direction itself */
    private static function htmlTag($part)
    {
        // comments are left alone: a "<html>" written inside one (a conditional comment, commented-out markup) is not the root element
        $pieces = preg_split('/(<!--.*?-->)/s', $part, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($pieces === false) {
            return null;
        }
        $done = false;
        foreach ($pieces as $i => $piece) {
            if ($done || $i % 2 === 1 || stripos($piece, '<html') === false) {
                continue;
            }
            $new = self::htmlTagIn($piece);
            if ($new === null) {
                return null;
            }
            $done = $new !== $piece || preg_match('/<html\b/i', $piece) === 1;
            $pieces[$i] = $new;
        }
        return implode('', $pieces);
    }

    private static function htmlTagIn($part)
    {
        // the start tag is read to its real end (a ">" inside a quoted value does not end it), and its attributes by name
        return preg_replace_callback('/<html\b((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>/i', function ($m) {
            $attrs = $m[1];
            $attr = '/(\s+)([^\s"\'>\/=]+)(?:(\s*=\s*)("[^"]*"|\'[^\']*\'|[^\s"\'>]+))?/';
            $hasDir = false;
            $hasLang = false;
            if (preg_match_all($attr, $attrs, $all)) {
                foreach ($all[2] as $name) {
                    $hasDir = $hasDir || strcasecmp($name, 'dir') === 0;
                    $hasLang = $hasLang || strcasecmp($name, 'lang') === 0;
                }
            }
            $lang = htmlspecialchars(self::iso(), ENT_QUOTES);
            if ($hasLang) {
                $done = false;
                $attrs = preg_replace_callback($attr, function ($a) use ($lang, &$done) {
                    if (!$done && strcasecmp($a[2], 'lang') === 0) {
                        $done = true;
                        return $a[1] . 'lang="' . $lang . '"';
                    }
                    return $a[0];
                }, $attrs);
            } else {
                $attrs .= ' lang="' . $lang . '"';
            }
            if (!$hasDir) {
                $attrs .= ' dir="rtl"';
            }
            return '<html' . $attrs . '>';
        }, $part, 1);
    }

    /** A <link rel="stylesheet"> to a local file points to its mirrored copy instead */
    private static function link($tag)
    {
        if (!preg_match('/\srel\s*=\s*["\']?stylesheet/i', $tag) || !preg_match('/\shref\s*=\s*("([^"]*)"|\'([^\']*)\')/i', $tag, $h, PREG_OFFSET_CAPTURE)) {
            return $tag;
        }
        $href = $h[2][1] >= 0 ? $h[2][0] : $h[3][0];
        $url = self::mirrored(html_entity_decode($href, ENT_QUOTES));
        if ($url === null) {
            return $tag;
        }
        $tag = substr_replace($tag, '"' . htmlspecialchars($url, ENT_QUOTES) . '"', $h[1][1], strlen($h[1][0]));
        // an integrity hash is for the original file: the browser would refuse the mirrored copy with it
        return preg_replace('/\sintegrity\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $tag);
    }

    private static function extraLink()
    {
        global $root_path;
        $file = $root_path . 'ui/ui/styles/rtl.css';
        if (!is_file($file)) {
            return '';
        }
        return '<link rel="stylesheet" href="' . htmlspecialchars(APP_URL . '/ui/ui/styles/rtl.css?' . filemtime($file), ENT_QUOTES) . '" />' . "\n";
    }

    /* ---------- mirrored copies of stylesheets ---------- */

    /**
     * The URL of the mirrored copy of a stylesheet of this site, made now if missing or older than the file; null for a
     * stylesheet on another site or one that cannot be read (the page then keeps the original).
     */
    /** The stylesheets being mirrored now (real paths): an @import that leads back to one of them is not followed */
    private static $busy = [];

    /** A stylesheet that is never mirrored: a component that turns itself, a mirrored copy already, or rtl.css */
    private static function excluded($path)
    {
        global $root_path, $UI_PATH;
        foreach (self::OWN_RTL as $own) {
            if (stripos(basename($path), $own) === 0) {
                return true;
            }
        }
        $rel = str_replace(DIRECTORY_SEPARATOR, '/', substr($path, strlen(rtrim(realpath($root_path), DIRECTORY_SEPARATOR)) + 1));
        // (a page that went through the filter twice links the copies; rtl.css is written right to left)
        return strpos($rel, ($UI_PATH ?: 'ui') . '/cache/rtl/') === 0 || $rel === ($UI_PATH ?: 'ui') . '/ui/styles/rtl.css';
    }

    public static function mirrored($href)
    {
        global $root_path, $UI_PATH;
        $path = self::localPath($href);
        if ($path === null || isset(self::$busy[$path]) || self::excluded($path)) {
            return null;
        }
        $rel = substr($path, strlen(rtrim(realpath($root_path), DIRECTORY_SEPARATOR)) + 1);
        $rel = str_replace(DIRECTORY_SEPARATOR, '/', $rel);
        $cache = ($UI_PATH ?: 'ui') . '/cache/rtl';
        $dir = $root_path . $cache;
        $name = substr(sha1($rel), 0, 12) . '-' . preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($rel));
        $out = $dir . '/' . $name;
        // the source's time and a short hash of its bytes: a file replaced with the same time (a deploy that keeps times, a fast edit)
        // still makes the copy again
        $fingerprint = function () use ($path) {
            $h = @hash_file('crc32b', $path);
            return filemtime($path) . ($h !== false ? '-' . $h : '');
        };
        $mtime = $fingerprint();
        self::$busy[$path] = true;
        try {
            // the copy's first line names its stamp and the local stylesheets it imports: those are checked too, and a change
            // to one of them makes this copy again, with the new address of the import's own copy
            $head = self::readStamp($out);
            $stamp = $head ? self::stamp($mtime, self::depUrls($head['deps'])) : null;
            if (!$head || !is_file($out) || filemtime($out) < (int) $mtime || $head['stamp'] !== $stamp) {
                if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
                    return self::fail($rel, 'the folder ' . $cache . ' could not be created (it must be writable)');
                }
                $css = @file_get_contents($path);
                if ($css === false) {
                    return self::fail($rel, 'the stylesheet could not be read');
                }
                // a local stylesheet it imports is mirrored as well, and imported from its copy
                $deps = [];
                $urls = [];
                // (comments and strings are matched first, so text that only looks like an @import inside one is left as it is)
                $css = preg_replace_callback('/(\/\*.*?\*\/)|("(?:[^"\\\\\n]|\\\\.)*"|\'(?:[^\'\\\\\n]|\\\\.)*\')|@import\s+(?:url\(\s*(["\']?)([^"\')]+)\3\s*\)|(["\'])([^"\']+)\5)/is', function ($m) use ($rel, &$deps, &$urls) {
                    if (($m[1] ?? '') !== '' || ($m[2] ?? '') !== '') {
                        return $m[0];
                    }
                    $target = trim(isset($m[6]) && $m[6] !== '' ? $m[6] : $m[4]);
                    if ($target === '') {
                        return $m[0];
                    }
                    if (preg_match('#^([a-z][a-z0-9+.-]*:|//)#i', $target)) {
                        // a full address: one of this site's stylesheets is mirrored like a link to it; another site (or data:)
                        // is left as it is
                        if (self::localPath($target) === null) {
                            return $m[0];
                        }
                        $site = $target;
                    } else {
                        $site = $target[0] === '/' ? $target : APP_URL . '/' . (dirname($rel) === '.' ? '' : dirname($rel) . '/') . $target;
                    }
                    $url = self::mirrored($site);
                    $file = self::localPath($site);
                    if ($file === null) {
                        // a local import that is not there (yet): listed with the missing mark, so the copy is made again once the
                        // file appears; the import itself is kept (rebased below) in the copy
                        $missing = self::sitePath($site);
                        if ($missing !== null && !preg_match('/[\s,*]/', $missing)) {
                            $deps[] = $missing;
                            $urls[] = '-';
                        }
                        return $m[0];
                    }
                    $dep = str_replace(DIRECTORY_SEPARATOR, '/', substr($file, strlen(rtrim(realpath($GLOBALS['root_path']), DIRECTORY_SEPARATOR)) + 1));
                    if ($dep === '' || preg_match('/[\s,*]/', $dep)) {
                        return $m[0]; // (a name the first line of the copy cannot list: imported unmirrored)
                    }
                    if ($url === null) {
                        if (isset(self::$busy[$file]) || self::excluded($file)) {
                            return $m[0]; // never mirrored (an import loop, or a stylesheet left as it is)
                        }
                        // its copy could not be made now (the cache not writable, the file unreadable): imported as it is, and
                        // listed, so this copy is made again once that one can be
                        $deps[] = $dep;
                        $urls[] = '-';
                        return $m[0];
                    }
                    $deps[] = $dep;
                    $urls[] = $url;
                    return '@import url("' . $url . '")';
                }, $css);
                $stamp = self::stamp($mtime, $urls);
                // the copy lives in ui/cache/rtl/: links to fonts and images are pointed back to the stylesheet's own folder
                $css = $css === null ? null : self::rebase($css, self::relative($cache, dirname($rel)));
                $flipped = $css === null ? null : self::flipLarge($css);
                if ($flipped === null) {
                    // a step failed: the page keeps the original stylesheet, and no copy is written
                    return self::fail($rel, 'mirroring failed (' . preg_last_error_msg() . ')');
                }
                $tmp = $out . '.' . getmypid() . '.tmp';
                // Published under a lock, as one step: the copy and its stamp go together, and a worker that read an older source
                // (or was slow) cannot write over what a newer one published. Under the lock the source is read again: changed since,
                // this copy is out of date and not written (the page keeps the original this time, the next request makes the new one).
                $lock = @fopen($out . '.lock', 'c');
                if (!$lock || !flock($lock, LOCK_EX)) {
                    if ($lock) {
                        fclose($lock);
                    }
                    return null;
                }
                try {
                    if ($fingerprint() !== $mtime) {
                        return null;
                    }
                    $now = self::readStamp($out);
                    if (!($now && $now['stamp'] === $stamp && is_file($out))) {
                        // the stamp goes after the copy: a copy with no matching stamp is simply made again
                        if (@file_put_contents($tmp, $flipped) === false || !@rename($tmp, $out)
                            || @file_put_contents($tmp . '.stamp', self::stampLine($stamp, $deps)) === false || !@rename($tmp . '.stamp', $out . '.stamp')) {
                            @unlink($tmp);
                            @unlink($tmp . '.stamp');
                            @unlink($out . '.stamp');
                            return self::fail($rel, 'the copy could not be written to ' . $cache . ' (it must be writable)');
                        }
                    }
                } finally {
                    flock($lock, LOCK_UN);
                    fclose($lock);
                }
            }
        } finally {
            unset(self::$busy[$path]);
        }
        return APP_URL . '/' . $cache . '/' . $name . '?' . $stamp;
    }

    /**
     * A stylesheet that could not be mirrored: the page keeps the original (left to right), and the reason goes to the PHP error log
     * once per file and request, so a server where right-to-left pages stay left to right can be told why. Returns null.
     */
    private static function fail($rel, $why)
    {
        static $told = [];
        if (!isset($told[$rel])) {
            $told[$rel] = true;
            @error_log('[RTL] ' . $rel . ' not mirrored: ' . $why);
        }
        return null;
    }

    /**
     * flip() with room for a large stylesheet: a server's lower PCRE limits (backtracking, recursion, or a JIT stack that runs out) can
     * make a regular expression give up on a big file, which leaves the whole layout left to right. The limits are raised for the
     * mirroring and put back after; a JIT failure is tried once more without JIT.
     */
    private static function flipLarge($css)
    {
        $old = [];
        foreach (['pcre.backtrack_limit' => '10000000', 'pcre.recursion_limit' => '1000000'] as $k => $v) {
            $cur = ini_get($k);
            if ($cur !== false && (int) $cur < (int) $v) {
                $old[$k] = $cur;
                @ini_set($k, $v);
            }
        }
        try {
            $out = self::flip($css);
            if ($out === null && preg_last_error() === PREG_JIT_STACKLIMIT_ERROR && ini_get('pcre.jit')) {
                $jit = ini_get('pcre.jit');
                @ini_set('pcre.jit', '0');
                try {
                    $out = self::flip($css);
                } finally {
                    @ini_set('pcre.jit', $jit);
                }
            }
            return $out;
        } finally {
            foreach ($old as $k => $v) {
                @ini_set($k, $v);
            }
        }
    }

    /** The stamp of a copy: the source's time and the mirroring version, and the addresses of the copies it imports */
    private static function stamp($mtime, array $importUrls)
    {
        return $mtime . '.' . self::VERSION . ($importUrls ? '.' . substr(sha1(implode('|', $importUrls)), 0, 10) : '');
    }

    /**
     * The addresses of the mirrored copies of these stylesheets (paths in the install), made again where they are out of date;
     * "-" for one that has no copy now. A change either way (a copy made at last, or one no longer possible) changes the stamp.
     */
    private static function depUrls(array $deps)
    {
        $urls = [];
        foreach ($deps as $d) {
            $u = self::mirrored(APP_URL . '/' . $d);
            $urls[] = $u === null ? '-' : $u;
        }
        return $urls;
    }

    /** The stamp and imports written on a copy's first line, or null when the copy is missing or from before this format */
    private static function readStamp($file)
    {
        // the stamp is kept beside the copy (a first line in the copy would push a leading @charset or byte order mark out of place)
        $fh = @fopen($file . '.stamp', 'r');
        if (!$fh) {
            return null;
        }
        $line = (string) fgets($fh, 8192);
        fclose($fh);
        if (!preg_match('#^/\* rtl (\S+)(?: deps:(\S+))? \*/$#', rtrim($line, "\r\n"), $m)) {
            return null;
        }
        return ['stamp' => $m[1], 'deps' => isset($m[2]) && $m[2] !== '' ? explode(',', $m[2]) : []];
    }

    private static function stampLine($stamp, array $deps = [])
    {
        return '/* rtl ' . $stamp . ($deps ? ' deps:' . implode(',', $deps) : '') . " */\n";
    }

    /** The file on disk for a stylesheet URL of this site (or a path relative to it); null for anything else */
    private static function localPath($href)
    {
        global $root_path;
        $href = trim((string) $href);
        if ($href === '' || preg_match('#^(data:|javascript:)#i', $href)) {
            return null;
        }
        if (strpos($href, '//') === 0) {
            $href = (parse_url(APP_URL, PHP_URL_SCHEME) ?: 'http') . ':' . $href; // scheme-relative: same scheme as the site
        }
        $path = preg_replace('/[?#].*$/', '', $href);
        $base = rtrim(APP_URL, '/');
        if (stripos($path, $base . '/') === 0) {
            $path = substr($path, strlen($base) + 1);
        } elseif (preg_match('#^([a-z][a-z0-9+.-]*:)?//#i', $path)) {
            return null; // another site
        } else {
            $sitePath = rtrim((string) parse_url($base, PHP_URL_PATH), '/');
            if ($path[0] === '/') {
                if ($sitePath === '' || strpos($path, $sitePath . '/') !== 0) {
                    return $sitePath === '' ? self::inside($root_path . rawurldecode(ltrim($path, '/'))) : null;
                }
                $path = substr($path, strlen($sitePath) + 1);
            }
            $path = preg_replace('#^\./#', '', $path);
        }
        return self::inside($root_path . rawurldecode($path));
    }

    /** The path inside the install a local address names, dots resolved, or null when it leaves the install or is not local */
    private static function sitePath($site)
    {
        $path = rawurldecode(preg_replace('/[?#].*$/', '', (string) $site));
        if (strpos($path, '//') === 0) {
            $path = (parse_url(APP_URL, PHP_URL_SCHEME) ?: 'http') . ':' . $path;
        }
        $base = rtrim(APP_URL, '/');
        if (stripos($path, $base . '/') === 0) {
            $path = substr($path, strlen($base) + 1);
        } elseif (preg_match('#^([a-z][a-z0-9+.-]*:)?//#i', $path)) {
            return null;
        } else {
            $sitePath = rtrim((string) parse_url($base, PHP_URL_PATH), '/');
            if (($path[0] ?? '') === '/') {
                if ($sitePath !== '' && strpos($path, $sitePath . '/') === 0) {
                    $path = substr($path, strlen($sitePath) + 1);
                } elseif ($sitePath !== '') {
                    return null;
                }
            }
        }
        $out = [];
        foreach (explode('/', ltrim($path, '/')) as $seg) {
            if ($seg === '' || $seg === '.') {
                continue;
            }
            if ($seg === '..') {
                if (!$out) {
                    return null;
                }
                array_pop($out);
                continue;
            }
            $out[] = $seg;
        }
        return $out ? implode('/', $out) : null;
    }

    /** The real path of a .css file inside the install, or null */
    private static function inside($file)
    {
        global $root_path;
        $real = realpath($file);
        $root = realpath($root_path);
        if ($real === false || $root === false || strtolower(pathinfo($real, PATHINFO_EXTENSION)) !== 'css' || !is_file($real)) {
            return null;
        }
        return strpos($real, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) === 0 ? $real : null;
    }

    /** The relative path from one folder of the install to another ("ui/cache/rtl" to "ui/ui/styles" is "../../ui/styles") */
    private static function relative($from, $to)
    {
        $a = array_values(array_filter(explode('/', $from), 'strlen'));
        $b = array_values(array_filter(explode('/', $to), 'strlen'));
        while ($a && $b && $a[0] === $b[0]) {
            array_shift($a);
            array_shift($b);
        }
        return str_repeat('../', count($a)) . ($b ? implode('/', $b) . '/' : '');
    }

    /** Relative url(...), image-set() and @import paths, prefixed so they still point to the same files from the copy's folder */
    private static function rebase($css, $prefix)
    {
        $fix = function ($u) use ($prefix) {
            return preg_match('#^([a-z][a-z0-9+.-]*:|/|\#)#i', $u) || $u === '' ? $u : $prefix . $u;
        };
        $str = '"(?:[^"\\\\\n]|\\\\.)*"|\'(?:[^\'\\\\\n]|\\\\.)*\'';
        // inside image-set() a quoted string is a file, as url() is (only the one in type("image/avif") is not): read with its
        // brackets (url(), type()) and strings whole
        $imageSet = '((?:-webkit-)?image-set\((?:[^()"\']|' . $str . '|\((?:[^()"\']|' . $str . ')*\))*\))';
        // one pass, left to right: comments and quoted strings are skipped whole, so text such as content: "url(a.png)" is
        // left as it is, while url(...), image-set(...) and @import "..." outside them are rebased. A quoted url("...") is read
        // to its closing quote, so a bracket inside it (url("a(1).png")) is part of the path
        return preg_replace_callback('/(\/\*.*?\*\/)|url\(\s*(["\'])?((?(2)(?:(?!\2)[^\\\\\n]|\\\\.)*|[^"\')]*))(?(2)\2)\s*\)|@import\s+(["\'])([^"\']+)\4|' . $imageSet . '|(' . $str . ')/is', function ($m) use ($fix, $str) {
            if ($m[1] !== null || $m[7] !== null) {
                return $m[0];
            }
            if ($m[6] !== null) {
                return preg_replace_callback('/\btype\(\s*(?:' . $str . ')\s*\)|url\(\s*(["\'])?((?(1)(?:(?!\1)[^\\\\\n]|\\\\.)*|[^"\')]*))(?(1)\1)\s*\)|(' . $str . ')/is', function ($n) use ($fix) {
                    if ($n[3] !== null) {
                        $q = $n[3][0];
                        return $q . $fix(substr($n[3], 1, -1)) . $q;
                    }
                    if ($n[2] !== null) {
                        return 'url(' . $n[1] . $fix(trim((string) $n[2])) . $n[1] . ')';
                    }
                    return $n[0];
                }, $m[6], -1, $count, PREG_UNMATCHED_AS_NULL) ?? $m[0];
            }
            if ($m[5] !== null) {
                return '@import ' . $m[4] . $fix($m[5]) . $m[4];
            }
            return 'url(' . $m[2] . $fix(trim((string) $m[3])) . $m[2] . ')';
        }, $css, -1, $count, PREG_UNMATCHED_AS_NULL);
    }

    /* ---------- mirroring CSS ---------- */

    /** A stylesheet (or the inside of a <style> block), mirrored; null when a regular expression failed on it */
    public static function flip($css)
    {
        // comments and strings are kept aside while the rules are read, in one pass from left to right, so "/*" inside a string
        // is not a comment and a quote inside a comment is not a string. "@noflip" in a comment keeps the next rule or
        // declaration as it is; a brace or a semicolon inside a string is not taken for the end of a rule
        $comments = [];
        $strings = [];
        $css = self::protect($css, $comments, $strings);
        if ($css === null) {
            return null;
        }
        // a grouping rule marked @noflip (@media, @supports, ...) keeps every rule inside it as it is: the whole block is put
        // aside, its braces counted, before the innermost rules are read
        $kept = [];
        $out = '';
        $pos = 0;
        while (preg_match('/\x01\d+n\x01(?:\s|\x01\d+c\x01)*@[a-z-]+[^{};]*\{/i', $css, $g, PREG_OFFSET_CAPTURE, $pos)) {
            $start = $g[0][1];
            $i = $start + strlen($g[0][0]);
            for ($depth = 1, $len = strlen($css); $i < $len && $depth > 0; $i++) {
                $depth += $css[$i] === '{' ? 1 : ($css[$i] === '}' ? -1 : 0);
            }
            $kept[] = substr($css, $start, $i - $start);
            $out .= substr($css, $pos, $start - $pos) . "\x04" . (count($kept) - 1) . "\x04";
            $pos = $i;
        }
        if (preg_last_error() !== PREG_NO_ERROR) {
            return null;
        }
        $css = $out . substr($css, $pos);
        // Rules are read from the innermost out, each one replaced by a placeholder (\x05) holding its result, so the rule around
        // it is read next and the declarations it has of its own (before, between or after nested rules) are mirrored too. What
        // comes before the brace is the selector; in a nested rule it is preceded by the parent's declarations up to the last ";".
        $done = [];
        $flipText = function ($text) {
            $pieces = preg_split('/(\x05\d+\x05)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
            foreach ($pieces as $i => $piece) {
                if ($i % 2 === 0 && $piece !== '') {
                    $pieces[$i] = self::declarations($piece);
                }
            }
            return implode('', $pieces);
        };
        for ($guard = 0; $guard < 64; $guard++) {
            $count = 0;
            $css = preg_replace_callback('/([^{}]*)\{([^{}]*)\}/', function ($m) use (&$done, &$count, $flipText) {
                $count++;
                $selector = $m[1];
                $head = '';
                if (($semi = strrpos($selector, ';')) !== false && !preg_match('/^\s*@/', $selector)) {
                    $head = $flipText(substr($selector, 0, $semi + 1)); // the parent's declarations that come before this rule
                    $selector = substr($selector, $semi + 1);
                }
                $plain = preg_replace('/\x01\d+[nc]\x01/', '', $selector);
                if (preg_match('/\x01\d+n\x01(?:\s*\x01\d+c\x01)*[^\x01;]*$/', $selector)
                    || preg_match('/@font-face\s*$/i', $plain)
                    // a rule written for right to left already ([dir="rtl"] ..., :dir(rtl), .swal2-rtl) is right as it is
                    || preg_match('/\[\s*dir\s*=\s*\x02?\d*\x02?\s*rtl|\[\s*dir\s*=|:dir\(\s*rtl|[-_]rtl\b|\.rtl\b/i', $plain)) {
                    $done[] = $head . $selector . '{' . $m[2] . '}'; // (@noflip, @font-face and rtl rules keep their declarations)
                } else {
                    $done[] = $head . $selector . '{' . $flipText($m[2]) . '}';
                }
                return "\x05" . (count($done) - 1) . "\x05";
            }, $css);
            if ($css === null) {
                return null;
            }
            if ($count === 0) {
                break;
            }
        }
        // what is left outside any rule (a ";"-ended @import) is a list of declarations of no kind: kept as it is
        for ($guard = 0; $guard < 64 && $css !== null && strpos($css, "\x05") !== false; $guard++) {
            $css = preg_replace_callback('/\x05(\d+)\x05/', function ($m) use ($done) {
                return $done[(int) $m[1]];
            }, $css);
        }
        $css = $css === null ? null : preg_replace_callback('/\x04(\d+)\x04/', function ($m) use ($kept) {
            return $kept[(int) $m[1]];
        }, $css);
        return $css === null ? null : self::restore($css, $comments, $strings);
    }

    /**
     * Comments and quoted strings put aside as placeholders (\x01 for a comment, with "n" when it says @noflip, \x02 for a
     * string), in one pass from left to right; null when the regular expression failed. restore() puts them back.
     */
    private static function protect($css, &$comments, &$strings)
    {
        return preg_replace_callback('/\/\*.*?\*\/|"(?:[^"\\\\\n]|\\\\.)*"|\'(?:[^\'\\\\\n]|\\\\.)*\'/s', function ($m) use (&$comments, &$strings) {
            if ($m[0][0] === '/') {
                $comments[] = $m[0];
                return "\x01" . (count($comments) - 1) . (stripos($m[0], '@noflip') !== false ? 'n' : 'c') . "\x01";
            }
            $strings[] = $m[0];
            return "\x02" . (count($strings) - 1) . "\x02";
        }, $css);
    }

    /** The comments and strings protect() put aside, back in their places; null when a regular expression failed */
    private static function restore($css, array $comments, array $strings)
    {
        $css = preg_replace_callback('/\x02(\d+)\x02/', function ($m) use ($strings) {
            return $strings[(int) $m[1]];
        }, $css);
        return $css === null ? null : preg_replace_callback('/\x01(\d+)[nc]\x01/', function ($m) use ($comments) {
            return $comments[(int) $m[1]];
        }, $css);
    }

    /** A list of declarations ("margin-left: 4px; float: left"), mirrored */
    public static function declarations($block)
    {
        $out = '';
        foreach (self::split($block, ';') as $decl) {
            $out .= self::declaration($decl) . ';';
        }
        return substr($out, 0, -1);
    }

    private static function declaration($decl)
    {
        if (strpos($decl, ':') === false || preg_match('/\x01\d+n\x01/', $decl)) {
            return $decl;
        }
        [$prop, $value] = explode(':', $decl, 2);
        $name = strtolower(trim(preg_replace('/\x01\d+[nc]\x01/', '', $prop)));
        if ($name === '' || strpos($name, '--') === 0) {
            return $decl; // custom properties: their use is unknown
        }
        $important = '';
        if (preg_match('/\s*!\s*important\s*$/i', $value, $im)) {
            $important = $im[0];
            $value = substr($value, 0, -strlen($important));
        }
        $flippedName = preg_replace_callback('/\b(left|right)\b/i', function ($m) {
            return self::swapWord($m[0]);
        }, $prop);
        // url(...) is kept aside: a file called left-arrow.png keeps its name
        $urls = [];
        $value = preg_replace_callback('/url\([^)]*\)/i', function ($m) use (&$urls) {
            $urls[] = $m[0];
            return "\x03" . (count($urls) - 1) . "\x03";
        }, $value);
        $value = preg_replace_callback('/\x03(\d+)\x03/', function ($m) use ($urls) {
            return $urls[(int) $m[1]];
        }, self::value($name, $value));
        return $flippedName . ':' . $value . $important;
    }

    private static function value($name, $value)
    {
        switch (true) {
            case in_array($name, ['float', 'clear', 'text-align', 'text-align-last', 'caption-side', 'justify-self', 'justify-items', 'transition', 'transition-property', 'will-change'], true):
                return self::swapWords($value);
            case in_array($name, ['margin', 'padding', 'border-width', 'border-style', 'border-color', 'inset', 'scroll-margin', 'scroll-padding'], true):
                return self::fourValues($value);
            case $name === 'border-radius':
                return self::radius($value);
            case in_array($name, ['background-position', 'background-position-x', 'object-position', 'transform-origin', 'perspective-origin', 'mask-position'], true):
                return self::position($value, $name);
            case in_array($name, ['box-shadow', 'text-shadow'], true):
                return self::shadow($value);
            case in_array($name, ['transform', 'translate'], true):
                return self::translate($name, $value);
            case in_array($name, ['background', 'background-image'], true):
                return self::swapWords($value); // "to left" in a gradient, "left center" as the position

            case $name === 'cursor':
                return preg_replace_callback('/\b(n|s)?(e|w)-resize\b/i', function ($m) {
                    return $m[1] . (strtolower($m[2]) === 'e' ? 'w' : 'e') . '-resize';
                }, $value);
        }
        return $value;
    }

    private static function swapWord($w)
    {
        $map = ['left' => 'right', 'right' => 'left', 'Left' => 'Right', 'Right' => 'Left', 'LEFT' => 'RIGHT', 'RIGHT' => 'LEFT'];
        return $map[$w] ?? (strtolower($w) === 'left' ? 'right' : 'left');
    }

    private static function swapWords($value)
    {
        // a custom property's name (--menu-left, also inside var()) is an identifier, not a side: kept as it is. Other words
        // are swapped, "left" alone and inside a property name ("transition: margin-left") alike.
        return preg_replace_callback('/(--[\w-]+)|\b(left|right)\b/i', function ($m) {
            return $m[1] !== '' ? $m[1] : self::swapWord($m[2]);
        }, $value);
    }

    /** "1px 2px 3px 4px" (top right bottom left) becomes "1px 4px 3px 2px" */
    private static function fourValues($value)
    {
        $t = self::tokens($value);
        if (count($t) !== 4) {
            return $value;
        }
        return self::lead($value) . implode(' ', [$t[0], $t[3], $t[2], $t[1]]) . self::trail($value);
    }

    /** border-radius corners mirrored: top-left with top-right, bottom-right with bottom-left */
    private static function radius($value)
    {
        $sides = self::split($value, '/');
        foreach ($sides as $i => $side) {
            $t = self::tokens($side);
            if (count($t) === 4) {
                $t = [$t[1], $t[0], $t[3], $t[2]];
            } elseif (count($t) === 3) {
                $t = [$t[1], $t[0], $t[1], $t[2]];
            } elseif (count($t) === 2) {
                $t = [$t[1], $t[0]];
            } else {
                continue;
            }
            $sides[$i] = self::lead($side) . implode(' ', $t) . self::trail($side);
        }
        return implode('/', $sides);
    }

    /**
     * left and right swapped; a horizontal percentage (the first value) counted from the other side. For transform-origin the
     * horizontal value is found whichever order the keywords come in ("top 30%"), a zero counts as 0% (the left edge), and the
     * vertical value and a z-offset are kept as written.
     */
    private static function position($value, $name = '')
    {
        $layers = self::split($value, ',');
        foreach ($layers as $i => $layer) {
            $t = self::tokens($layer);
            if ($name === 'transform-origin' && $t && count($t) <= 3) {
                $h = in_array(strtolower($t[0]), ['top', 'bottom'], true) ? 1 : 0;
                if (isset($t[$h]) && preg_match('/^[+-]?(?:\d*\.?\d+)%$/', $t[$h])) {
                    $t[$h] = (100 - (float) $t[$h]) . '%';
                    $layers[$i] = self::lead($layer) . implode(' ', $t) . self::trail($layer);
                    continue;
                }
                if (isset($t[$h]) && preg_match('/^[+-]?0*\.?0+(?:px)?$/i', $t[$h])) {
                    $t[$h] = '100%'; // (0 is the left edge: the right one when mirrored)
                    $layers[$i] = self::lead($layer) . implode(' ', $t) . self::trail($layer);
                    continue;
                }
                if (isset($t[$h]) && preg_match('/^[+-]?(?:\d*\.)?\d+(?:px|em|rem|ch|ex|vw|vh|vmin|vmax|cm|mm|in|pt|pc)$/i', $t[$h])) {
                    $t[$h] = 'calc(100% - ' . ltrim($t[$h], '+') . ')'; // (a length from the left edge is the same length from the right)
                    $layers[$i] = self::lead($layer) . implode(' ', $t) . self::trail($layer);
                    continue;
                }
                $layers[$i] = self::swapWords($layer);
                continue;
            }
            if ($t && preg_match('/^(-?[\d.]+)%$/', $t[0], $m) && count($t) <= 2) {
                $t[0] = (100 - (float) $m[1]) . '%';
                $layers[$i] = self::lead($layer) . implode(' ', $t) . self::trail($layer);
            } else {
                $layers[$i] = self::swapWords($layer);
            }
        }
        return implode(',', $layers);
    }

    /**
     * The horizontal offset of each shadow, the other way. It is the first value that is neither "inset" nor a colour; when that
     * value is a number (or calc()) it is negated, and when it is anything else (var(), env()) the shadow is left as it is,
     * rather than turning a later value (the vertical offset) the other way.
     */
    private static function shadow($value)
    {
        $layers = self::split($value, ',');
        foreach ($layers as $i => $layer) {
            $t = self::tokens($layer);
            foreach ($t as $k => $tok) {
                // a comment (kept aside by flip() as a placeholder) is not a value: one on its own is skipped, one attached to a
                // value is kept around it, and the value alone decides
                preg_match('/^((?:\x01\d+c\x01)*)(.*?)((?:\x01\d+c\x01)*)$/s', $tok, $cm);
                $bare = $cm[2];
                if ($bare === '') {
                    continue;
                }
                if (strtolower($bare) === 'inset' || $bare[0] === '#' || preg_match('/^[a-z][a-z-]*$/i', $bare)
                    || preg_match('/^(rgba?|hsla?|hwb|lab|lch|oklab|oklch|color|color-mix|light-dark)\(/i', $bare)) {
                    continue; // a keyword or a colour, which can come before the offsets
                }
                if (preg_match('/^[+-]?(\d*\.)?\d+([a-z%]*)$/i', $bare) || preg_match('/^calc\(/i', $bare)) {
                    $t[$k] = $cm[1] . self::negate($bare) . $cm[3];
                    $layers[$i] = self::lead($layer) . implode(' ', $t) . self::trail($layer);
                }
                break;
            }
        }
        return implode(',', $layers);
    }

    /** translate(x, y), translateX(x), translate3d(x, y, z) and the translate property: x the other way */
    private static function translate($name, $value)
    {
        if ($name === 'translate') {
            $t = self::tokens($value);
            if ($t) {
                $t[0] = self::negate($t[0]);
                return self::lead($value) . implode(' ', $t) . self::trail($value);
            }
            return $value;
        }
        // the first argument of each translate function, read with its brackets balanced (it can be a calc() of its own)
        $out = '';
        $pos = 0;
        while (preg_match('/\b(translate|translateX|translate3d)\(\s*/i', $value, $m, PREG_OFFSET_CAPTURE, $pos)) {
            $start = $m[0][1] + strlen($m[0][0]);
            $depth = 0;
            $end = $start;
            for ($len = strlen($value); $end < $len; $end++) {
                $ch = $value[$end];
                if ($ch === '(') {
                    $depth++;
                } elseif ($ch === ')' || $ch === ',') {
                    if ($depth === 0) {
                        break;
                    }
                    if ($ch === ')') {
                        $depth--;
                    }
                }
            }
            $arg = substr($value, $start, $end - $start);
            $out .= substr($value, $pos, $start - $pos) . self::negate($arg) . (preg_match('/\s+$/', $arg, $ws) ? $ws[0] : '');
            $pos = $end;
        }
        return $out . substr($value, $pos);
    }

    private static function negate($v)
    {
        $v = trim($v);
        if (preg_match('/^calc\((.*)\)$/is', $v, $m)) {
            return 'calc(-1 * (' . $m[1] . '))'; // the whole expression, its own grouping kept
        }
        if (preg_match('/^[+-]?0+(\.0+)?([a-z%]*)$/i', $v)) {
            return ltrim($v, '+');
        }
        if (preg_match('/^(var|env)\(.*\)$/is', $v)) {
            return 'calc(-1 * ' . $v . ')'; // a length held in a variable: negated, the name kept
        }
        if (!preg_match('/^[+-]?(\d*\.)?\d+[a-z%]*$/i', $v)) {
            return $v; // keywords: left as they are
        }
        // an explicit plus is dropped: "+4px" mirrors to "-4px", never "-+4px"
        return $v[0] === '-' ? substr($v, 1) : '-' . ltrim($v, '+');
    }

    /** Split on a separator, not inside brackets */
    private static function split($s, $sep)
    {
        $out = [];
        $depth = 0;
        $cur = '';
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $c = $s[$i];
            if ($c === '(') {
                $depth++;
            } elseif ($c === ')') {
                $depth = max(0, $depth - 1);
            }
            if ($c === $sep && $depth === 0) {
                $out[] = $cur;
                $cur = '';
                continue;
            }
            $cur .= $c;
        }
        $out[] = $cur;
        return $out;
    }

    /**
     * The space-separated values, brackets kept whole; a string placeholder counts as a value, a comment placeholder does not:
     * it is kept with the value next to it (before it, or after the last one), so the count of values is the CSS's own
     */
    private static function tokens($s)
    {
        $t = [];
        $carry = '';
        foreach (self::split(preg_replace('/\s+/', ' ', trim($s)), ' ') as $x) {
            if ($x === '') {
                continue;
            }
            if (preg_match('/^(?:\x01\d+[nc]\x01)+$/', $x)) {
                if ($t) {
                    $t[count($t) - 1] .= $x;
                } else {
                    $carry .= $x;
                }
                continue;
            }
            $t[] = $carry . $x;
            $carry = '';
        }
        if ($carry !== '') {
            $t[] = $carry; // only comments: kept as they were
        }
        return $t;
    }

    private static function lead($s)
    {
        return preg_match('/^\s*/', $s, $m) ? $m[0] : '';
    }

    private static function trail($s)
    {
        return preg_match('/\s*$/', $s, $m) ? $m[0] : '';
    }
}
