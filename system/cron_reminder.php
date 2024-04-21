<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 * This file for reminding user about expiration
 * Example to run every at 7:00 in the morning
 * 0 7 * * * /usr/bin/php /var/www/system/cron_reminder.php
 **/

include "../init.php";

$isCli = true;
if (php_sapi_name() !== 'cli') {
    $isCli = false;
    echo "<pre>";
}

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
        $p = ORM::for_table('tbl_plans')->where('id', $u['plan_id'])->find_one();
        $c = ORM::for_table('tbl_customers')->where('id', $ds['customer_id'])->find_one();
        if ($p['validity_unit'] == 'Period') {
			// Postpaid price from field
			$add_inv = User::getAttribute("Invoice", $ds['customer_id']);
			if (empty ($add_inv) or $add_inv == 0) {
				$price = $p['price'];
			} else {
				$price = $add_inv;
			}
        } else {
                $price = $p['price'];
        }
        if ($ds['expiration'] == $day7) {
            echo Message::sendPackageNotification($c, $p['name_plan'], $price, Lang::getNotifText('reminder_7_day'), $config['user_notification_reminder']) . "\n";
        } else if ($ds['expiration'] == $day3) {
            echo Message::sendPackageNotification($c, $p['name_plan'], $price, Lang::getNotifText('reminder_3_day'), $config['user_notification_reminder']) . "\n";
        } else if ($ds['expiration'] == $day1) {
            echo Message::sendPackageNotification($c, $p['name_plan'], $price, Lang::getNotifText('reminder_1_day'), $config['user_notification_reminder']) . "\n";
        }
    }
}