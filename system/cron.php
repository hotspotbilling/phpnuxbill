<?php

include "../init.php";
$lockFile = "$CACHE_PATH/router_monitor.lock";

if (!is_dir($CACHE_PATH)) {
    echo "Directory '$CACHE_PATH' does not exist. Exiting...\n";
    exit;
}

$lock = fopen($lockFile, 'c');

if ($lock === false) {
    echo "Failed to open lock file. Exiting...\n";
    exit;
}

if (!flock($lock, LOCK_EX | LOCK_NB)) {
    echo "Script is already running. Exiting...\n";
    fclose($lock);
    exit;
}


$isCli = true;
if (php_sapi_name() !== 'cli') {
    $isCli = false;
    echo "<pre>";
}
echo "PHP Time\t" . date('Y-m-d H:i:s') . "\n";
$res = ORM::raw_execute('SELECT NOW() AS WAKTU;');
$statement = ORM::get_last_statement();
$rows = [];
while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
    echo "MYSQL Time\t" . $row['WAKTU'] . "\n";
}

$_c = $config;


$textExpired = Lang::getNotifText('expired');

$d = ORM::for_table('tbl_user_recharges')->where('status', 'on')->where_lte('expiration', date("Y-m-d"))->find_many();
echo "Found " . count($d) . " user(s)\n";
run_hook('cronjob'); #HOOK

foreach ($d as $ds) {
    try {
        $date_now = strtotime(date("Y-m-d H:i:s"));
        $expiration = strtotime($ds['expiration'] . ' ' . $ds['time']);
        echo $ds['expiration'] . " : " . ($isCli ? $ds['username'] : Lang::maskText($ds['username']));

        if ($date_now >= $expiration) {
            echo " : EXPIRED \r\n";

            // Fetch user recharge details
            $u = ORM::for_table('tbl_user_recharges')->where('id', $ds['id'])->find_one();
            if (!$u) {
                throw new Exception("User recharge record not found for ID: " . $ds['id']);
            }

            // Fetch customer details
            $c = ORM::for_table('tbl_customers')->where('id', $ds['customer_id'])->find_one();
            if (!$c) {
                $c = $u;
            }

            // Fetch plan details
            $p = ORM::for_table('tbl_plans')->where('id', $u['plan_id'])->find_one();
            if (!$p) {
                throw new Exception("Plan not found for ID: " . $u['plan_id']);
            }

            $dvc = Package::getDevice($p);
            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    try {
                        (new $p['device'])->remove_customer($c, $p);
                    } catch (Throwable $e) {
                        _log($e->getMessage());
                        sendTelegram($e->getMessage());
                        echo "Error: " . $e->getMessage() . "\n";
                    }
                } else {
                    throw new Exception("Cron error: Devices " . $p['device'] . "not found, cannot disconnect ".$c['username']."\n");
                }
            }

            // Send notification and update user status
            try {
                echo Message::sendPackageNotification($c, $u['namebp'], $p['price'], $textExpired, $config['user_notification_expired']) . "\n";
                $u->status = 'off';
                $u->save();
            } catch (Throwable $e) {
                _log($e->getMessage());
                sendTelegram($e->getMessage());
                echo "Error: " . $e->getMessage() . "\n";
            }

            // Auto-renewal from deposit
            if ($config['enable_balance'] == 'yes' && $c['auto_renewal']) {
                [$bills, $add_cost] = User::getBills($ds['customer_id']);
                if ($add_cost != 0) {
                    $p['price'] += $add_cost;
                }

                if ($p && $c['balance'] >= $p['price']) {
                    if (Package::rechargeUser($ds['customer_id'], $ds['routers'], $p['id'], 'Customer', 'Balance')) {
                        Balance::min($ds['customer_id'], $p['price']);
                        echo "plan enabled: " . (string) $p['enabled'] . " | User balance: " . (string) $c['balance'] . " | price " . (string) $p['price'] . "\n";
                        echo "auto renewal Success\n";
                    } else {
                        echo "plan enabled: " . $p['enabled'] . " | User balance: " . $c['balance'] . " | price " . $p['price'] . "\n";
                        echo "auto renewal Failed\n";
                        Message::sendTelegram("FAILED RENEWAL #cron\n\n#u." . $c['username'] . " #buy #Hotspot \n" . $p['name_plan'] .
                            "\nRouter: " . $p['routers'] .
                            "\nPrice: " . $p['price']);
                    }
                } else {
                    echo "no renewal | plan enabled: " . (string) $p['enabled'] . " | User balance: " . (string) $c['balance'] . " | price " . (string) $p['price'] . "\n";
                }
            } else {
                echo "no renewal | balance" . $config['enable_balance'] . " auto_renewal " . $c['auto_renewal'] . "\n";
            }
        } else {
            echo " : ACTIVE \r\n";
        }
    } catch (Throwable $e) {
        // Catch any unexpected errors
        _log($e->getMessage());
        sendTelegram($e->getMessage());
        echo "Unexpected Error: " . $e->getMessage() . "\n";
    }
}

//Cek interim-update radiusrest
if ($config['frrest_interim_update'] != 0) {

    $r_a = ORM::for_table('rad_acct')
        ->whereRaw("BINARY acctstatustype = 'Start' OR acctstatustype = 'Interim-Update'")
        ->where_lte('dateAdded', date("Y-m-d H:i:s"))->find_many();

    foreach ($r_a as $ra) {
        $interval = $_c['frrest_interim_update'] * 60;
        $timeUpdate = strtotime($ra['dateAdded']) + $interval;
        $timeNow = strtotime(date("Y-m-d H:i:s"));
        if ($timeNow >= $timeUpdate) {
            $ra->acctstatustype = 'Stop';
            $ra->save();
        }
    }
}

if ($config['router_check']) {
    echo "Checking router status...\n";
    $routers = ORM::for_table('tbl_routers')->where('enabled', '1')->find_many();
    if (!$routers) {
        echo "No active routers found in the database.\n";
        flock($lock, LOCK_UN);
        fclose($lock);
        unlink($lockFile);
        exit;
    }

    $offlineRouters = [];
    $errors = [];

    foreach ($routers as $router) {
        // check if custom port
        if (strpos($router->ip_address, ':') === false) {
            $ip = $router->ip_address;
            $port = 8728;
        } else {
            [$ip, $port] = explode(':', $router->ip_address);
        }
        $isOnline = false;

        try {
            $timeout = 5;
            if (is_callable('fsockopen') && false === stripos(ini_get('disable_functions'), 'fsockopen')) {
                $fsock = @fsockopen($ip, $port, $errno, $errstr, $timeout);
                if ($fsock) {
                    fclose($fsock);
                    $isOnline = true;
                } else {
                    throw new Exception("Unable to connect to $ip on port $port using fsockopen: $errstr ($errno)");
                }
            } elseif (is_callable('stream_socket_client') && false === stripos(ini_get('disable_functions'), 'stream_socket_client')) {
                $connection = @stream_socket_client("$ip:$port", $errno, $errstr, $timeout);
                if ($connection) {
                    fclose($connection);
                    $isOnline = true;
                } else {
                    throw new Exception("Unable to connect to $ip on port $port using stream_socket_client: $errstr ($errno)");
                }
            } else {
                throw new Exception("Neither fsockopen nor stream_socket_client are enabled on the server.");
            }
        } catch (Exception $e) {
            _log($e->getMessage());
            $errors[] = "Error with router $ip: " . $e->getMessage();
        }

        if ($isOnline) {
            $router->last_seen = date('Y-m-d H:i:s');
            $router->status = 'Online';
        } else {
            $router->status = 'Offline';
            $offlineRouters[] = $router;
        }

        $router->save();
    }

    if (!empty($offlineRouters)) {
        $message = "Dear Administrator,\n";
        $message .= "The following routers are offline:\n";
        foreach ($offlineRouters as $router) {
            $message .= "Name: {$router->name}, IP: {$router->ip_address}, Last Seen: {$router->last_seen}\n";
        }
        $message .= "\nPlease check the router's status and take appropriate action.\n\nBest regards,\nRouter Monitoring System";

        $adminEmail = $config['mail_from'];
        $subject = "Router Offline Alert";
        Message::SendEmail($adminEmail, $subject, $message);
        sendTelegram($message);
    }

    if (!empty($errors)) {
        $message = "The following errors occurred during router monitoring:\n";
        foreach ($errors as $error) {
            $message .= "$error\n";
        }

        $adminEmail = $config['mail_from'];
        $subject = "Router Monitoring Error Alert";
        Message::SendEmail($adminEmail, $subject, $message);
        sendTelegram($message);
    }
    echo "Router monitoring finished checking.\n";
}

flock($lock, LOCK_UN);
fclose($lock);
unlink($lockFile);

$timestampFile = "$UPLOAD_PATH/cron_last_run.txt";
file_put_contents($timestampFile, time());

run_hook('cronjob_end'); #HOOK
echo "Cron job finished and completed successfully.\n";