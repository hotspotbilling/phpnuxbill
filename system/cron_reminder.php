<?php
/**
 * Pro version
 * Cron job for reminding users about expiration
 * Example: 0 7 * * * /usr/bin/php /var/www/system/cron_reminder.php
 */

include "../init.php";

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

date_default_timezone_set('UTC'); // Set appropriate time zone

// Validate configuration
$requiredConfigs = [
    'notification_reminder_1day', 'notification_reminder_3days', 'notification_reminder_7days',
    'user_notification_reminder_sms', 'user_notification_reminder_wa',
    'user_notification_reminder_email', 'user_notification_reminder_inbox',
    'user_notification_reminder_push'
];
foreach ($requiredConfigs as $key) {
    if (!isset($config[$key])) {
        die("Error: Configuration key '$key' is missing.\n");
    }
}

const REMINDER_DAYS = [1, 3, 7];

// Fetch active recharges with plan and customer data
$d = ORM::for_table('tbl_user_recharges')
    ->select('tbl_user_recharges.*')
    ->select('tbl_plans.name_plan')
    ->select('tbl_plans.price')
    ->select('tbl_plans.type')
    ->select('tbl_plans.validity_unit')
    ->select('tbl_customers.*')
    ->join('tbl_plans', ['tbl_user_recharges.plan_id', '=', 'tbl_plans.id'])
    ->join('tbl_customers', ['tbl_user_recharges.customer_id', '=', 'tbl_customers.id'])
    ->where('tbl_user_recharges.status', 'on')
    ->whereNotEqual('tbl_user_recharges.customer_id', '0')
    ->find_many();

run_hook('cronjob_reminder'); // HOOK

echo "PHP Time\t" . date('Y-m-d H:i:s') . "\n";
echo "MYSQL Time\t" . ORM::for_table('tbl_user_recharges')->raw_query('SELECT NOW() AS WAKTU')->find_one()['WAKTU'] . "\n";

// Calculate reminder dates
$reminderDates = [];
foreach (REMINDER_DAYS as $days) {
    $reminderDates[$days] = date('Y-m-d', strtotime("+$days day"));
}
print_r($reminderDates);

function sendNotifications($config, $customer, $planName, $price, $messageType, $days) {
    $methods = ['sms', 'wa', 'email', 'inbox', 'push'];
    $outputs = [];
    foreach ($methods as $method) {
        if ($config["user_notification_reminder_$method"]) {
            try {
                $outputs[] = Message::sendPackageNotification(
                    $customer,
                    $planName,
                    $price,
                    $messageType,
                    $method
                ) . "\n";
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

foreach ($d as $index => $ds) {
    $expirationDate = date('Y-m-d', strtotime($ds['expiration']));
    foreach (REMINDER_DAYS as $days) {
        if ($expirationDate === $reminderDates[$days] && $config["notification_reminder_{$days}days"] !== 'no') {
            // Determine price for postpaid plans
            $price = $ds['price'];
            if ($ds['validity_unit'] === 'Period') {
                $add_inv = User::getAttribute("Invoice", $ds['customer_id']);
                if (!empty($add_inv) && $add_inv != 0) {
                    $price = $add_inv;
                }
            }

            // Send notifications
            $messageType = Message::getMessageType($ds['type'], Lang::getNotifText("reminder_{$days}_days"));
            $outputs = sendNotifications($config, $ds, $ds['name_plan'], $price, $messageType, $days);
            foreach ($outputs as $output) {
                echo $output;
            }
        }
    }

    // Batch processing: Pause every 100 records
    if ($index % 100 === 0 && $index > 0) {
        sleep(1);
    }
}