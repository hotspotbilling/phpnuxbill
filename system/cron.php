<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
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
if (file_exists("uploads/notifications.json")) {
    $_notifmsg = json_decode(file_get_contents('uploads/notifications.json'), true);
} else {
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

$textExpired = $_notifmsg['expired'];

$d = ORM::for_table('tbl_user_recharges')->where('status', 'on')->find_many();

run_hook('cronjob'); #HOOK

foreach ($d as $ds) {
    if ($ds['type'] == 'Hotspot') {
        $date_now = strtotime(date("Y-m-d H:i:s"));
        $expiration = strtotime($ds['expiration'] . ' ' . $ds['time']);
        echo $ds['expiration'] . " : " . $ds['username'];
        if ($date_now >= $expiration) {
            echo " : EXPIRED \r\n";
            $u = ORM::for_table('tbl_user_recharges')->where('id', $ds['id'])->find_one();
            $c = ORM::for_table('tbl_customers')->where('id', $ds['customer_id'])->find_one();
            $m = ORM::for_table('tbl_routers')->where('name', $ds['routers'])->find_one();

            if (!$_c['radius_mode']) {
                $client = Mikrotik::getClient($m['ip_address'], $m['username'], $m['password']);
                Mikrotik::setHotspotLimitUptime($client, $c['username']);
                Mikrotik::removeHotspotActiveUser($client, $c['username']);
                Message::sendPackageNotification($c['phonenumber'], $c['fullname'], $u['namebp'], $textExpired, $config['user_notification_expired']);
            }
            //update database user dengan status off
            $u->status = 'off';
            $u->save();

            // autorenewal from deposit
            if ($config['enable_balance'] == 'yes' && $c['auto_renewal']) {
                $p = ORM::for_table('tbl_plans')->where('id', $u['plan_id'])->find_one();
                if ($p && $p['enabled'] && $c['balance'] >= $p['price']) {
                    if (Package::rechargeUser($ds['customer_id'], $p['routers'], $p['id'], 'Customer', 'Balance')) {
                        // if success, then get the balance
                        Balance::min($ds['customer_id'], $p['price']);
                    } else {
                        Message::sendTelegram("FAILED RENEWAL #cron\n\n#u$c[username] #buy #Hotspot \n" . $p['name_plan'] .
                            "\nRouter: " . $router_name .
                            "\nPrice: " . $p['price']);
                    }
                }
            }
        } else echo " : ACTIVE \r\n";
    } else {
        $date_now = strtotime(date("Y-m-d H:i:s"));
        $expiration = strtotime($ds['expiration'] . ' ' . $ds['time']);
        echo $ds['expiration'] . " : " . $ds['username'];
        if ($date_now >= $expiration) {
            echo " : EXPIRED \r\n";
            $u = ORM::for_table('tbl_user_recharges')->where('id', $ds['id'])->find_one();
            $c = ORM::for_table('tbl_customers')->where('id', $ds['customer_id'])->find_one();
            $m = ORM::for_table('tbl_routers')->where('name', $ds['routers'])->find_one();

            if (!$_c['radius_mode']) {
                $client = Mikrotik::getClient($m['ip_address'], $m['username'], $m['password']);
                Mikrotik::disablePpoeUser($client, $c['username']);
                Mikrotik::removePpoeActive($client, $c['username']);
                Message::sendPackageNotification($c['phonenumber'], $c['fullname'], $u['namebp'], $textExpired, $config['user_notification_expired']);
            }

            $u->status = 'off';
            $u->save();

            // autorenewal from deposit
            if ($config['enable_balance'] == 'yes' && $c['auto_renewal']) {
                $p = ORM::for_table('tbl_plans')->where('id', $u['plan_id'])->find_one();
                if ($p && $p['enabled'] && $c['balance'] >= $p['price']) {
                    if (Package::rechargeUser($ds['customer_id'], $p['routers'], $p['id'], 'Customer', 'Balance')) {
                        // if success, then get the balance
                        Balance::min($ds['customer_id'], $p['price']);
                    } else {
                        Message::sendTelegram("FAILED RENEWAL #cron\n\n#u$c[username] #buy #Hotspot \n" . $p['name_plan'] .
                            "\nRouter: " . $router_name .
                            "\nPrice: " . $p['price']);
                    }
                }
            }
        } else echo " : ACTIVE \r\n";
    }
}
