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
    const VERSION = 3;

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
            // script blocks are left alone: positions worked out by code are not CSS
            $parts = preg_split('/(<script\b[^>]*>.*?<\/script\s*>)/is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
            if ($parts === false) {
                return $html;
            }
            $extra = false;
            foreach ($parts as $i => $part) {
                if ($i % 2 === 1) {
                    continue;
                }
                $part = self::htmlTag($part);
                $part = preg_replace_callback('/<link\b[^>]*>/i', function ($m) {
                    return self::link($m[0]);
                }, $part);
                $part = preg_replace_callback('/(<style\b[^>]*>)(.*?)(<\/style\s*>)/is', function ($m) {
                    return $m[1] . self::flip($m[2]) . $m[3];
                }, $part);
                // (an entity such as &quot; holds a semicolon: it splits into a part with no colon, which is kept as it is)
                $part = preg_replace_callback('/(\sstyle\s*=\s*)(?:"([^"]*)"|\'([^\']*)\')/i', function ($m) {
                    $q = isset($m[3]) && $m[3] !== '' ? "'" : '"';
                    return $m[1] . $q . self::declarations($q === "'" ? $m[3] : $m[2]) . $q;
                }, $part);
                if (!$extra && stripos($part, '</head>') !== false) {
                    $part = preg_replace('/<\/head>/i', self::extraLink() . '</head>', $part, 1);
                    $extra = true;
                }
                $parts[$i] = $part;
            }
            return implode('', $parts);
        } catch (Throwable $e) {
            return $html; // a page shown left to right is better than no page
        }
    }

    /** dir="rtl" and the language code on <html>, unless the template set a direction itself */
    private static function htmlTag($part)
    {
        return preg_replace_callback('/<html\b([^>]*)>/i', function ($m) {
            $attrs = $m[1];
            if (!preg_match('/\sdir\s*=/i', $attrs)) {
                $attrs .= ' dir="rtl"';
            }
            $lang = htmlspecialchars(self::iso(), ENT_QUOTES);
            $attrs = preg_match('/\slang\s*=/i', $attrs)
                ? preg_replace('/\slang\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', ' lang="' . $lang . '"', $attrs, 1)
                : $attrs . ' lang="' . $lang . '"';
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
        return substr_replace($tag, '"' . htmlspecialchars($url, ENT_QUOTES) . '"', $h[1][1], strlen($h[1][0]));
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
    public static function mirrored($href)
    {
        global $root_path, $UI_PATH;
        $path = self::localPath($href);
        if ($path === null) {
            return null;
        }
        foreach (self::OWN_RTL as $own) {
            if (stripos(basename($path), $own) === 0) {
                return null;
            }
        }
        $rel = substr($path, strlen(rtrim(realpath($root_path), DIRECTORY_SEPARATOR)) + 1);
        $rel = str_replace(DIRECTORY_SEPARATOR, '/', $rel);
        $dir = $root_path . ($UI_PATH ?: 'ui') . '/cache/rtl';
        $name = substr(sha1($rel), 0, 12) . '-' . preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($rel));
        $out = $dir . '/' . $name;
        $mtime = filemtime($path);
        $stamp = $mtime . '.' . self::VERSION;
        if (!is_file($out) || filemtime($out) < $mtime || @file_get_contents($out, false, null, 0, 64) !== self::stampLine($stamp)) {
            if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
                return null;
            }
            $css = @file_get_contents($path);
            if ($css === false) {
                return null;
            }
            // the copy lives in ui/cache/rtl/: links to fonts and images are pointed back to the stylesheet's own folder
            $css = self::rebase($css, self::relative('ui/cache/rtl', dirname($rel)));
            $tmp = $out . '.' . getmypid() . '.tmp';
            if (@file_put_contents($tmp, self::stampLine($stamp) . self::flip($css)) === false || !@rename($tmp, $out)) {
                @unlink($tmp);
                return null;
            }
        }
        return APP_URL . '/' . ($UI_PATH ?: 'ui') . '/cache/rtl/' . $name . '?' . $stamp;
    }

    private static function stampLine($stamp)
    {
        return '/* rtl ' . $stamp . " */\n";
    }

    /** The file on disk for a stylesheet URL of this site (or a path relative to it); null for anything else */
    private static function localPath($href)
    {
        global $root_path;
        $href = trim((string) $href);
        if ($href === '' || preg_match('#^(data:|javascript:)#i', $href)) {
            return null;
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
                    return $sitePath === '' ? self::inside($root_path . ltrim($path, '/')) : null;
                }
                $path = substr($path, strlen($sitePath) + 1);
            }
            $path = preg_replace('#^\./#', '', $path);
        }
        return self::inside($root_path . rawurldecode($path));
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

    /** Relative url(...) and @import paths, prefixed so they still point to the same files from the copy's folder */
    private static function rebase($css, $prefix)
    {
        $fix = function ($u) use ($prefix) {
            return preg_match('#^([a-z][a-z0-9+.-]*:|/|\#)#i', $u) || $u === '' ? $u : $prefix . $u;
        };
        $css = preg_replace_callback('/url\(\s*(["\']?)([^"\')]*)\1\s*\)/i', function ($m) use ($fix) {
            return 'url(' . $m[1] . $fix(trim($m[2])) . $m[1] . ')';
        }, $css);
        return preg_replace_callback('/@import\s+(["\'])([^"\']+)\1/i', function ($m) use ($fix) {
            return '@import ' . $m[1] . $fix($m[2]) . $m[1];
        }, $css);
    }

    /* ---------- mirroring CSS ---------- */

    /** A stylesheet (or the inside of a <style> block), mirrored */
    public static function flip($css)
    {
        // comments are kept aside while the rules are read; "@noflip" in one keeps the next rule or declaration as it is
        $comments = [];
        $css = preg_replace_callback('/\/\*.*?\*\//s', function ($m) use (&$comments) {
            $comments[] = $m[0];
            return "\x01" . (count($comments) - 1) . (stripos($m[0], '@noflip') !== false ? 'n' : 'c') . "\x01";
        }, $css);
        // strings are kept aside too, so a brace or a semicolon inside one is not taken for the end of a rule
        $strings = [];
        $css = preg_replace_callback('/"(?:[^"\\\\\n]|\\\\.)*"|\'(?:[^\'\\\\\n]|\\\\.)*\'/s', function ($m) use (&$strings) {
            $strings[] = $m[0];
            return "\x02" . (count($strings) - 1) . "\x02";
        }, $css);
        // the innermost blocks hold the declarations; what comes before the brace is the selector
        $css = preg_replace_callback('/([^{}]*)\{([^{}]*)\}/', function ($m) {
            $selector = $m[1];
            if (preg_match('/\x01\d+n\x01(?:\s*\x01\d+c\x01)*[^\x01;]*$/', $selector)) {
                return $m[0]; // the rule is marked @noflip
            }
            $plain = preg_replace('/\x01\d+[nc]\x01/', '', $selector);
            if (preg_match('/@font-face\s*$/i', $plain)) {
                return $m[0];
            }
            // a rule written for right to left already ([dir="rtl"] ..., :dir(rtl), .swal2-rtl) is right as it is
            if (preg_match('/\[\s*dir\s*=\s*\x02?\d*\x02?\s*rtl|\[\s*dir\s*=|:dir\(\s*rtl|[-_]rtl\b|\.rtl\b/i', $plain)) {
                return $m[0];
            }
            return $selector . '{' . self::declarations($m[2]) . '}';
        }, $css);
        $css = preg_replace_callback('/\x02(\d+)\x02/', function ($m) use ($strings) {
            return $strings[(int) $m[1]];
        }, $css);
        return preg_replace_callback('/\x01(\d+)[nc]\x01/', function ($m) use ($comments) {
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
                return self::position($value);
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
        return preg_replace_callback('/\b(left|right)\b/i', function ($m) {
            return self::swapWord($m[1]);
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

    /** left and right swapped; a horizontal percentage (the first value) counted from the other side */
    private static function position($value)
    {
        $layers = self::split($value, ',');
        foreach ($layers as $i => $layer) {
            $t = self::tokens($layer);
            if ($t && preg_match('/^(-?[\d.]+)%$/', $t[0], $m) && count($t) <= 2) {
                $t[0] = (100 - (float) $m[1]) . '%';
                $layers[$i] = self::lead($layer) . implode(' ', $t) . self::trail($layer);
            } else {
                $layers[$i] = self::swapWords($layer);
            }
        }
        return implode(',', $layers);
    }

    /** The horizontal offset of each shadow, the other way */
    private static function shadow($value)
    {
        $layers = self::split($value, ',');
        foreach ($layers as $i => $layer) {
            $t = self::tokens($layer);
            foreach ($t as $k => $tok) {
                if (preg_match('/^-?(\d*\.)?\d+([a-z%]*)$/i', $tok)) {
                    $t[$k] = self::negate($tok);
                    $layers[$i] = self::lead($layer) . implode(' ', $t) . self::trail($layer);
                    break;
                }
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
        return preg_replace_callback('/\b(translate|translateX|translate3d)\(\s*([^,)]+)/i', function ($m) {
            return $m[1] . '(' . self::negate(trim($m[2]));
        }, $value);
    }

    private static function negate($v)
    {
        $v = trim($v);
        if (preg_match('/^calc\(/i', $v)) {
            return 'calc(-1 * ' . substr($v, 5);
        }
        if (preg_match('/^-?0+(\.0+)?([a-z%]*)$/i', $v)) {
            return $v;
        }
        if (!preg_match('/^-?(\d*\.)?\d+[a-z%]*$/i', $v)) {
            return $v; // var(), keywords: left as they are
        }
        return $v[0] === '-' ? substr($v, 1) : '-' . $v;
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

    /** The space-separated values, brackets kept whole; comment and string placeholders count as part of a value */
    private static function tokens($s)
    {
        $t = [];
        foreach (self::split(preg_replace('/\s+/', ' ', trim($s)), ' ') as $x) {
            if ($x !== '') {
                $t[] = $x;
            }
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
