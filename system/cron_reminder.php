<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 * This file is for reminding users about expiration
 * Example to run every day at 7:00 in the morning:
 * 0 7 * * * /usr/bin/php /var/www/system/cron_reminder.php
 **/

include "../init.php";

$isCli = true;
if (php_sapi_name() !== 'cli') {
    $isCli = false;
    echo "<pre>";
}

$recharges = ORM::for_table('tbl_user_recharges')->where('status', 'on')->find_many();

run_hook('cronjob_reminder'); // HOOK

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

foreach ($recharges as $recharge) {
    if (in_array($recharge['expiration'], [$day1, $day3, $day7])) {
        $user_recharge = ORM::for_table('tbl_user_recharges')->where('id', $recharge['id'])->find_one();
        $plan = ORM::for_table('tbl_plans')->where('id', $user_recharge['plan_id'])->find_one();
        $customer = ORM::for_table('tbl_customers')->where('id', $recharge['customer_id'])->find_one();

        if ($plan['validity_unit'] == 'Period') {
            // Postpaid price from field
            $additional_invoice = User::getAttribute("Invoice", $recharge['customer_id']);
            if (empty($additional_invoice) || $additional_invoice == 0) {
                $price = $plan['price'];
            } else {
                $price = $additional_invoice;
            }
        } else {
            $price = $plan['price'];
        }

        if ($recharge['expiration'] == $day7) {
            echo Message::sendPackageNotification($customer, $plan['name_plan'], $price, Lang::getNotifText('reminder_7_day'), $config['user_notification_reminder']) . "\n";
        } elseif ($recharge['expiration'] == $day3) {
            echo Message::sendPackageNotification($customer, $plan['name_plan'], $price, Lang::getNotifText('reminder_3_day'), $config['user_notification_reminder']) . "\n";
        } elseif ($recharge['expiration'] == $day1) {
            echo Message::sendPackageNotification($customer, $plan['name_plan'], $price, Lang::getNotifText('reminder_1_day'), $config['user_notification_reminder']) . "\n";
        }
    }
}
