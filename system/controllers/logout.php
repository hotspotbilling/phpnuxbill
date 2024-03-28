<?php
/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

run_hook('customer_logout'); #HOOK
if (session_status() == PHP_SESSION_NONE) session_start();
Admin::removeCookie();
User::removeCookie();
session_destroy();
_alert(Lang::T('Logout Successful'),'warning', "login");