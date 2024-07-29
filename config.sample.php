<?php

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$baseDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
define('APP_URL', $protocol . $host . $baseDir);

$_app_stage = 'Live'; # Do not change this

$db_host    = "localhost"; # Database Host
$db_port    = "";   # Database Port. Keep it blank if you are un sure.
$db_user    = "root"; # Database Username
$db_pass    = ""; # Database Password
$db_name    = "phpnuxbill"; # Database Name




//error reporting
if($_app_stage!='Live'){
    error_reporting(E_ERROR);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}else{
    error_reporting(E_ERROR);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}
