<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 * This file for reminding user about expiration
 * Example to run every at 7:00 in the morning
 * 0 7 * * * /usr/bin/php /var/www/system/cron_reminder.php
 **/

// on some server, it getting error because of slash is backwards
function _autoloader($class)
{
    if (strpos($class, '_') !== false) {
        $class = str_replace('_', DIRECTORY_SEPARATOR, $class);
        if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php')) {
            include __DIR__ . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        } else {
            $class = str_replace("\\", DIRECTORY_SEPARATOR, $class);
            if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php'))
                include __DIR__ . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        }
    } else {
        if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php')) {
            include __DIR__ . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        } else {
            $class = str_replace("\\", DIRECTORY_SEPARATOR, $class);
            if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php'))
                include __DIR__ . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        }
    }
}
spl_autoload_register('_autoloader');

if(php_sapi_name() !== 'cli'){
    echo "<pre>";
}

if(!file_exists('../config.php')){
    die("config.php file not found");
}


if(!file_exists('orm.php')){
    die("orm.php file not found");
}

if(!file_exists('uploads/notifications.default.json')){
    die("uploads/notifications.default.json file not found");
}

require_once '../config.php';
require_once 'orm.php';
require_once 'autoload/PEAR2/Autoload.php';
include "autoload/Hookers.php";

ORM::configure("mysql:host=$db_host;dbname=$db_name");
ORM::configure('username', $db_user);
ORM::configure('password', $db_password);
ORM::configure('return_result_sets', true);
ORM::configure('logging', true);

// notification message
if (file_exists("uploads/notifications.json")) {
    $_notifmsg = json_decode(file_get_contents('uploads/notifications.json'), true);
}
$_notifmsg_default = json_decode(file_get_contents('uploads/notifications.default.json'), true);

//register all plugin
foreach (glob(File::pathFixer("plugin/*.php")) as $filename) {
    include $filename;
}

$result = ORM::for_table('tbl_appconfig')->find_many();
foreach ($result as $value) {
    $config[$value['setting']] = $value['value'];
}
date_default_timezone_set($config['timezone']);


$d = ORM::for_table('tbl_user_recharges')->where('status', 'on')->find_many();

run_hook('cronjob_reminder'); #HOOK


echo "PHP Time\t" . date('Y-m-d H:i:s') . "\n";
$res = ORM::raw_execute('SELECT NOW() AS WAKTU;');
$statement = ORM::get_last_statement();
$rows = array();
while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
    echo "MYSQL Time\t" . $row['WAKTU'] . "\n";
}


$day7 = date('Y-m-d', strtotime("+7 day"));
$day3 = date('Y-m-d', strtotime("+3 day"));
$day1 = date('Y-m-d', strtotime("+1 day"));
print_r([$day1, $day3, $day7]);
foreach ($d as $ds) {
    if (in_array($ds['expiration'], [$day1, $day3, $day7])) {
        $u = ORM::for_table('tbl_user_recharges')->where('id', $ds['id'])->find_one();
        $c = ORM::for_table('tbl_customers')->where('id', $ds['customer_id'])->find_one();
        if ($ds['expiration'] == $day7) {
            echo Message::sendPackageNotification($c['phonenumber'], $c['fullname'], $u['namebp'], Lang::getNotifText('reminder_7_day'), $config['user_notification_reminder']) . "\n";
        } else if ($ds['expiration'] == $day3) {
            echo Message::sendPackageNotification($c['phonenumber'], $c['fullname'], $u['namebp'], Lang::getNotifText('reminder_3_day'), $config['user_notification_reminder']) . "\n";
        } else if ($ds['expiration'] == $day1) {
            echo Message::sendPackageNotification($c['phonenumber'], $c['fullname'], $u['namebp'], Lang::getNotifText('reminder_1_day'), $config['user_notification_reminder']) . "\n";
        }
    }
}
