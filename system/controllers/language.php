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
$appHost = parse_url(APP_URL, PHP_URL_HOST);
if ($back === '' || !$appHost || strcasecmp((string) parse_url($back, PHP_URL_HOST), $appHost) !== 0) {
    $back = getUrl('dashboard');
}
r2($back, 's', ucwords(str_replace(['-', '_'], ' ', $name)));
