<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Pragma: no-cache");

$isAdmin = false;
if (session_status() == PHP_SESSION_NONE) session_start();
if($_SESSION['aid'] != NULL){
    $isAdmin = true;
}
run_hook('customer_logout'); #HOOK
Admin::removeCookie();
User::removeCookie();
session_destroy();
if($isAdmin){
    _alert(Lang::T('Logout Successful'), 'warning', "admin");
}
_alert(Lang::T('Logout Successful'), 'warning', "login");