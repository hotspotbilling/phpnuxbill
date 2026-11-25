<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

// Quick language switch of the admin top bar: language/set/<name>. The choice is kept in the session and in a cookie
// (init.php reads both); only a language that has its file in system/lan is taken.
_admin();

$name = (string) ($routes[2] ?? '');
if (($routes[1] ?? '') !== 'set' || !Lang::valid($name)) {
    r2(getUrl('dashboard'), 'e', Lang::T('Language not found'));
}
$_SESSION['user_language'] = $name;
setcookie('user_language', $name, time() + 365 * 86400, '/');

// back to the page the admin was on, when that page is on this site
$back = (string) ($_SERVER['HTTP_REFERER'] ?? '');
// the referrer must be of this site's origin: scheme, host and port (the default port of the scheme when none is written)
$origin = function ($url) {
    $p = parse_url((string) $url);
    if (!$p || empty($p['host']) || empty($p['scheme'])) {
        return null;
    }
    $scheme = strtolower($p['scheme']);
    $port = $p['port'] ?? ($scheme === 'https' ? 443 : 80);
    return $scheme . '://' . strtolower($p['host']) . ':' . $port;
};
if ($back === '' || $origin($back) === null || $origin($back) !== $origin(APP_URL)) {
    $back = getUrl('dashboard');
}
r2($back, 's', ucwords(str_replace(['-', '_'], ' ', $name)));
