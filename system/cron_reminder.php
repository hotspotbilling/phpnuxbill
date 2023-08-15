<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 * This file for reminding user about expiration
 * Example to run every at 7:00 in the morning
 * 0 7 * * * /usr/bin/php /var/www/system/cron_reminder.php
 **/

require('../config.php');
require('orm.php');

require_once 'autoload/PEAR2/Autoload.php';

ORM::configure("mysql:host=$db_host;dbname=$db_name");
ORM::configure('username', $db_user);
ORM::configure('password', $db_password);
ORM::configure('return_result_sets', true);
ORM::configure('logging', true);


include "autoload/Hookers.php";

// notification message
if(file_exists("uploads/notifications.json")){
    $_notifmsg =json_decode(file_get_contents('uploads/notifications.json'), true);
}else{
    $_notifmsg = json_decode(file_get_contents('uploads/notifications.default.json'), true);
}

//register all plugin
foreach (glob("plugin/*.php") as $filename) {
    include $filename;
}

// on some server, it getting error because of slash is backwards
function _autoloader($class)
{
    if (strpos($class, '_') !== false) {
        $class = str_replace('_', DIRECTORY_SEPARATOR, $class);
        if (file_exists('autoload' . DIRECTORY_SEPARATOR . $class . '.php')) {
            include 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        } else {
            $class = str_replace("\\", DIRECTORY_SEPARATOR, $class);
            if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php'))
                include __DIR__ . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        }
    } else {
        if (file_exists('autoload' . DIRECTORY_SEPARATOR . $class . '.php')) {
            include 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        } else {
            $class = str_replace("\\", DIRECTORY_SEPARATOR, $class);
            if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php'))
                include __DIR__ . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        }
    }
}

spl_autoload_register('_autoloader');

$result = ORM::for_table('tbl_appconfig')->find_many();
foreach ($result as $value) {
    $config[$value['setting']] = $value['value'];
}
date_default_timezone_set($config['timezone']);


$d = ORM::for_table('tbl_user_recharges')->where('status', 'on')->find_many();

run_hook('cronjob_reminder'); #HOOK



$day7 = date('Y-m-d', strtotime("+7 day"));
$day3 = date('Y-m-d', strtotime("+3 day"));
$day1 = date('Y-m-d', strtotime("+1 day"));
print_r([$day1,$day3,$day7]);
foreach ($d as $ds) {
    if(in_array($ds['expiration'],[$day1,$day3,$day7])){
        $u = ORM::for_table('tbl_user_recharges')->where('id', $ds['id'])->find_one();
        $c = ORM::for_table('tbl_customers')->where('id', $ds['customer_id'])->find_one();
        if($ds['expiration']==$day7){
            echo Message::sendPackageNotification($c['phonenumber'], $c['fullname'], $u['namebp'], $_notifmsg['reminder_7_day'], $config['user_notification_reminder'])."\n";
        }else if($ds['expiration']==$day3){
            echo Message::sendPackageNotification($c['phonenumber'], $c['fullname'], $u['namebp'], $_notifmsg['reminder_3_day'], $config['user_notification_reminder'])."\n";
        }else if($ds['expiration']==$day1){
            echo Message::sendPackageNotification($c['phonenumber'], $c['fullname'], $u['namebp'], $_notifmsg['reminder_1_day'], $config['user_notification_reminder'])."\n";
        }
    }
}
