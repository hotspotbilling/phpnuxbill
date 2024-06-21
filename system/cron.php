<?php

include "../init.php";
$isCli = true;
if (php_sapi_name() !== 'cli') {
    $isCli = false;
    echo "<pre>";
}
echo "PHP Time\t" . date('Y-m-d H:i:s') . "\n";
$res = ORM::raw_execute('SELECT NOW() AS WAKTU;');
$statement = ORM::get_last_statement();
$rows = array();
while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
    echo "MYSQL Time\t" . $row['WAKTU'] . "\n";
}

$_c = $config;


$textExpired = Lang::getNotifText('expired');

$d = ORM::for_table('tbl_user_recharges')->where('status', 'on')->where_lte('expiration', date("Y-m-d"))->find_many();
echo "Found " . count($d) . " user(s)\n";
run_hook('cronjob'); #HOOK

foreach ($d as $ds) {
    $date_now = strtotime(date("Y-m-d H:i:s"));
    $expiration = strtotime($ds['expiration'] . ' ' . $ds['time']);
    echo $ds['expiration'] . " : " . (($isCli) ? $ds['username'] : Lang::maskText($ds['username']));
    if ($date_now >= $expiration) {
        echo " : EXPIRED \r\n";
        $u = ORM::for_table('tbl_user_recharges')->where('id', $ds['id'])->find_one();
        $c = ORM::for_table('tbl_customers')->where('id', $ds['customer_id'])->find_one();
        $p = ORM::for_table('tbl_plans')->where('id', $u['plan_id'])->find_one();
        $dvc = Package::getDevice($p);
        if($_app_stage != 'demo'){
            if (file_exists($dvc)) {
                require_once $dvc;
                (new $p['device'])->remove_customer($c, $p);
            } else {
                echo "Cron error Devices $p[device] not found, cannot disconnect $c[username]";
                Message::sendTelegram("Cron error Devices $p[device] not found, cannot disconnect $c[username]");
            }
        }
        echo Message::sendPackageNotification($c, $u['namebp'], $p['price'], $textExpired, $config['user_notification_expired']) . "\n";
        //update database user dengan status off
        $u->status = 'off';
        $u->save();

        // autorenewal from deposit
        if ($config['enable_balance'] == 'yes' && $c['auto_renewal']) {
            list($bills, $add_cost) = User::getBills($ds['customer_id']);
            if ($add_cost > 0) {
                if (!empty($add_cost)) {
                    $p['price'] += $add_cost;
                }
            }
            if ($p && $c['balance'] >= $p['price']) {
                if (Package::rechargeUser($ds['customer_id'], $ds['routers'], $p['id'], 'Customer', 'Balance')) {
                    // if success, then get the balance
                    Balance::min($ds['customer_id'], $p['price']);
                    echo "plan enabled: $p[enabled] | User balance: $c[balance] | price $p[price]\n";
                    echo "auto renewall Success\n";
                } else {
                    echo "plan enabled: $p[enabled] | User balance: $c[balance] | price $p[price]\n";
                    echo "auto renewall Failed\n";
                    Message::sendTelegram("FAILED RENEWAL #cron\n\n#u$c[username] #buy #Hotspot \n" . $p['name_plan'] .
                        "\nRouter: " . $p['routers'] .
                        "\nPrice: " . $p['price']);
                }
            } else {
                echo "no renewall | plan enabled: $p[enabled] | User balance: $c[balance] | price $p[price]\n";
            }
        } else {
            echo "no renewall | balance $config[enable_balance] auto_renewal $c[auto_renewal]\n";
        }
    } else {
        echo " : ACTIVE \r\n";
    }
}
