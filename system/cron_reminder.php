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

$d = ORM::for_table('tbl_user_recharges')->where('status', 'on')->whereNotEqual('customer_id', '0')->find_many();

run_hook('cronjob_reminder'); #HOOK


echo "PHP Time\t" . date('Y-m-d H:i:s') . "\n";
$res = ORM::raw_execute('SELECT NOW() AS WAKTU;');
$statement = ORM::get_last_statement();
$rows = [];
while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
    echo "MYSQL Time\t" . $row['WAKTU'] . "\n";
}


$day7 = date('Y-m-d', strtotime("+7 day"));
$day3 = date('Y-m-d', strtotime("+3 day"));
$day1 = date('Y-m-d', strtotime("+1 day"));
print_r([$day1, $day3, $day7]);


/**
 * Send package notifications to users who have less than $days days left for expiration
 *
 * @param array $config Configuration array
 * @param array $customer Customer array
 * @param string $planName Package name
 * @param float $price Package price
 * @param string $messageType Notification message type
 * @param int $days Days left for expiration
 * @return array Array of outputs from each notification method
 */

function sendReminderNotifications($config, $customer, $planName, $price, $messageType, $days)
{
    $methods = ['sms', 'wa', 'email', 'inbox', 'push'];
    $outputs = [];
    foreach ($methods as $method) {
        if (isset($config["user_notification_reminder_$method"]) && $config["user_notification_reminder_$method"]) {
            try {
                $outputs[] = Message::sendPackageNotification(
                    $customer,
                    $planName,
                    $price,
                    $messageType,
                    $method
                ) . "\n";
                // After successful notification
                file_put_contents(
                    'logs/reminder_success.log',
                    date('Y-m-d H:i:s') . " [SUCCESS] $method notification sent to {$customer['username']}.\n",
                    FILE_APPEND
                );
            } catch (Exception $e) {
                file_put_contents(
                    'logs/reminder.log',
                    date('Y-m-d H:i:s') . " [ERROR] $method notification for {$customer['username']}: {$e->getMessage()}\n",
                    FILE_APPEND
                );
                sendTelegram("Failed to send $days-day $method reminder to {$customer['username']}: {$e->getMessage()}");
            }
        }
    }
    return $outputs;
}

foreach ($d as $ds) {
    if (in_array($ds['expiration'], [$day1, $day3, $day7])) {
        $u = ORM::for_table('tbl_user_recharges')->where('id', $ds['id'])->find_one();
        $p = ORM::for_table('tbl_plans')->where('id', $u['plan_id'])->find_one();
        $c = ORM::for_table('tbl_customers')->where('id', $ds['customer_id'])->find_one();
        if ($p['validity_unit'] == 'Period') {
            // Postpaid price from field
            $add_inv = User::getAttribute("Invoice", $ds['customer_id']);
            if (empty($add_inv) or $add_inv == 0) {
                $price = $p['price'];
            } else {
                $price = $add_inv;
            }
        } else {
            $price = $p['price'];
        }

        if ($ds['expiration'] == $day7 && $config['notification_reminder_7days'] !== 'no') {
            try {
                print_r(sendReminderNotifications(
                    $config,
                    $c,
                    $p['name_plan'],
                    $price,
                    Message::getMessageType($p['type'], Lang::getNotifText('reminder_7_day')),
                    $config['user_notification_reminder']
                )) . "\n";
                sleep(1); // Add 1 second delay after sending notification
            } catch (Exception $e) {
                sendTelegram("Cron Reminder failed to send 7-day reminder to " . $ds['username'] . " Error: " . $e->getMessage());
            }
        } else if ($ds['expiration'] == $day3 && $config['notification_reminder_3days'] !== 'no') {
            try {
                print_r(sendReminderNotifications(
                    $config,
                    $c,
                    $p['name_plan'],
                    $price,
                    Message::getMessageType($p['type'], Lang::getNotifText('reminder_3_day')),
                    $config['user_notification_reminder']
                )) . "\n";
                sleep(1); // Add 1 second delay after sending notification
            } catch (Exception $e) {
                sendTelegram("Cron Reminder failed to send 3-day reminder to " . $ds['username'] . " Error: " . $e->getMessage());
            }
        } else if ($ds['expiration'] == $day1 && $config['notification_reminder_1day'] !== 'no') {
            try {
                print_r(sendReminderNotifications(
                    $config,
                    $c,
                    $p['name_plan'],
                    $price,
                    Message::getMessageType($p['type'], Lang::getNotifText('reminder_1_day')),
                    $config['user_notification_reminder']
                )) . "\n";
                sleep(1); // Add 1 second delay after sending notification
            } catch (Exception $e) {
                sendTelegram("Cron Reminder failed to send 1-day reminder to " . $ds['username'] . " Error: " . $e->getMessage());
            }
        }
    }
}
