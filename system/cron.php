<?php

include "../init.php";

$isCli = (php_sapi_name() !== 'cli') ? false : true;
if (!$isCli) {
    echo "<pre>";
}

echo "PHP Time\t" . date('Y-m-d H:i:s') . "\n";
$res = ORM::raw_execute('SELECT NOW() AS WAKTU;');
$statement = ORM::get_last_statement();
$rows = [];
while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
    echo "MYSQL Time\t" . $row['WAKTU'] . "\n";
}

$textExpired = Lang::getNotifText('expired');

$recharges = ORM::for_table('tbl_user_recharges')
    ->where('status', 'on')
    ->where_lte('expiration', date("Y-m-d"))
    ->find_many();

echo "Found " . count($recharges) . " user(s)\n";
run_hook('cronjob'); // HOOK

foreach ($recharges as $recharge) {
    $dateNow = strtotime(date("Y-m-d H:i:s"));
    $expiration = strtotime($recharge['expiration'] . ' ' . $recharge['time']);

    echo $recharge['expiration'] . " : " . (($isCli) ? $recharge['username'] : Lang::maskText($recharge['username']));

    if ($dateNow >= $expiration) {
        echo " : EXPIRED \r\n";
        $userRecharge = ORM::for_table('tbl_user_recharges')->where('id', $recharge['id'])->find_one();
        $customer = ORM::for_table('tbl_customers')->where('id', $recharge['customer_id'])->find_one();
        $plan = ORM::for_table('tbl_plans')->where('id', $userRecharge['plan_id'])->find_one();
        $router = ($recharge['type'] == 'Hotspot') ? Mikrotik::info($recharge['routers']) : ORM::for_table('tbl_routers')->where('name', $recharge['routers'])->find_one();

        if ($plan['is_radius']) {
            if (empty($plan['pool_expired'])) {
                print_r(Radius::customerDeactivate($customer['username']));
            } else {
                Radius::upsertCustomerAttr($customer['username'], 'Framed-Pool', $plan['pool_expired'], ':=');
                print_r(Radius::disconnectCustomer($customer['username']));
            }
        } else {
            $client = Mikrotik::getClient($router['ip_address'], $router['username'], $router['password']);

            if (!empty($plan['pool_expired'])) {
                if ($recharge['type'] == 'Hotspot') {
                    Mikrotik::setHotspotUserPackage($client, $customer['username'], 'EXPIRED NUXBILL ' . $plan['pool_expired']);
                } else {
                    Mikrotik::setPpoeUserPlan($client, $customer['username'], 'EXPIRED NUXBILL ' . $plan['pool_expired']);
                }
            } else {
                if ($recharge['type'] == 'Hotspot') {
                    Mikrotik::removeHotspotUser($client, $customer['username']);
                    Mikrotik::removeHotspotActiveUser($client, $customer['username']);
                } else {
                    Mikrotik::removePpoeUser($client, $customer['username']);
                    Mikrotik::removePpoeActive($client, $customer['username']);
                }
            }
        }

        echo Message::sendPackageNotification($customer, $userRecharge['namebp'], $plan['price'], $textExpired, $config['user_notification_expired']) . "\n";

        $userRecharge->status = 'off';
        $userRecharge->save();

        if ($config['enable_balance'] == 'yes' && $customer['auto_renewal']) {
            list($bills, $add_cost) = User::getBills($recharge['customer_id']);
            if ($add_cost > 0) {
                $plan['price'] += $add_cost;
            }
            if ($plan && $plan['enabled'] && $customer['balance'] >= $plan['price']) {
                if (Package::rechargeUser($recharge['customer_id'], $plan['routers'], $plan['id'], 'Customer', 'Balance')) {
                    Balance::min($recharge['customer_id'], $plan['price']);
                    echo "Plan enabled: $plan[enabled] | User balance: $customer[balance] | Price: $plan[price]\n";
                    echo "Autorenewal Success\n";
                } else {
                    echo "Plan enabled: $plan[enabled] | User balance: $customer[balance] | Price: $plan[price]\n";
                    echo "Autorenewal Failed\n";
                    Message::sendTelegram("FAILED RENEWAL #cron\n\n#u$customer[username] #buy #" . (($recharge['type'] == 'Hotspot') ? 'Hotspot' : 'PPPOE') . " \n" . $plan['name_plan'] .
                        "\nRouter: " . $plan['routers'] .
                        "\nPrice: " . $plan['price']);
                }
            } else {
                echo "No renewal | Plan enabled: $plan[enabled] | User balance: $customer[balance] | Price: $plan[price]\n";
            }
        } else {
            echo "No renewal | Balance: $config[enable_balance] Auto-renewal: $customer[auto_renewal]\n";
        }
    } else {
        echo " : ACTIVE \r\n";
    }
}