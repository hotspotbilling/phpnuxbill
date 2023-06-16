<?php
/**
* PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
**/
session_start();

if(isset($_GET['nux-mac']) && !empty($_GET['nux-mac'])){
    $_SESSION['nux-mac'] = $_GET['nux-mac'];
}

if(isset($_GET['nux-ip']) && !empty($_GET['nux-ip'])){
    $_SESSION['nux-ip'] = $_GET['nux-ip'];
}
require_once 'system/vendor/autoload.php';
require_once 'system/boot.php';
App::_run();
