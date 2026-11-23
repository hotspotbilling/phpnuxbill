<?php


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
        if (isset($config["user_notification_expired_$method"]) && $config["user_notification_expired_$method"]) {
            try {
                $outputs[] = Message::sendPackageNotification(
                    $customer,
                    $planName,
                    $price,
                    $messageType,
                    $method
                ) . "\n";
                // After successful notification
                _log(date('Y-m-d H:i:s') . " [SUCCESS] $method notification sent to {$customer['username']}.", 'Info');
            } catch (Exception $e) {
                _log("Failed to send $days-day $method reminder to {$customer['username']}: {$e->getMessage()}", 'Error');
                sendTelegram("Failed to send $days-day $method reminder to {$customer['username']}: {$e->getMessage()}");
            }
        }
    }
    return $outputs;
}


session_start();

include __DIR__ . "/../init.php"; // from any folder: cron starts in the home folder, not here

// Determine if running from CLI or web
$isCli = (php_sapi_name() === 'cli');
// Set up HTML formatting for web interface
if (!$isCli) {
    echo "<pre>";
}


// Check if the user is authenticated and authorized or valid API key
if (!$isCli && (!isset($_SESSION['aid']) || empty($_SESSION['aid'])) && (!isset($_GET['secret']) || $_GET['secret'] !== $config['api_key'])) {
    // Log unauthorized access
    _log("Unauthorized access attempt to cron.php from " . $_SERVER['REMOTE_ADDR'] . " User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'), 'Warning');

    // Return HTTP 403 Forbidden
    header('HTTP/1.1 403 Forbidden');
    echo "Access denied. You do not have permission to access this resource.";
    exit(1);
}

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
                    (new $p['device'])->remove_customer($c, $p);
                } else {
                    throw new Exception("Cron error: Devices " . $p['device'] . "not found, cannot disconnect " . $c['username'] . "\n");
                }
            }

            // Send notification and update user status
            try {
                print_r(sendReminderNotifications(
                    $config,
                    $c,
                    $u['namebp'],
                    $p['price'],
                    Message::getMessageType($p['type'], $textExpired),
                    $config['user_notification_expired']
                )) . "\n";
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

// Check for stale radius-rest sessions
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
    // the alert emails are sent when the run ends: SMTP has no overall time limit (minutes for each step), so a stalled mail
    // server must not hold up the router history and the nightly backups that run after this. Never throws.
    $routerCheckMail = function ($to, $subject, $message) {
        register_shutdown_function(function () use ($to, $subject, $message) {
            try {
                Message::SendEmail($to, $subject, $message);
            } catch (Throwable $e) {
            }
        });
    };
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
        // "host", "host:port", "[IPv6]" or "[IPv6]:port", read as the router pages and devices read it
        [$ip, $port] = RouterAddress::split($router->ip_address);
        $port = (int) ($port ?: 8728);
        $sockHost = strpos($ip, ':') !== false ? '[' . $ip . ']' : $ip; // an IPv6 address is written in brackets to connect
        $isOnline = false;

        try {
            $timeout = 5;
            if (is_callable('fsockopen') && false === stripos(ini_get('disable_functions'), 'fsockopen')) {
                $fsock = @fsockopen($sockHost, $port, $errno, $errstr, $timeout);
                if ($fsock) {
                    fclose($fsock);
                    $isOnline = true;
                } else {
                    throw new Exception("Unable to connect to $ip on port $port using fsockopen: $errstr ($errno)");
                }
            } elseif (is_callable('stream_socket_client') && false === stripos(ini_get('disable_functions'), 'stream_socket_client')) {
                $connection = @stream_socket_client("tcp://$sockHost:$port", $errno, $errstr, $timeout);
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

        // the result is kept only for the address that was checked: under the lock routers.php holds while it saves an edit, the
        // router is read again, and a result for an address changed meanwhile is dropped (the edit has cleared the status), as is
        // one for a router disabled meanwhile (no longer checked, so no status and no offline alert)
        $locked = false;
        try {
            $locked = (int) ORM::getDb()->query("SELECT GET_LOCK('phpnuxbill_router_endpoint', 20)")->fetchColumn() === 1;
        } catch (Throwable $e) {
        }
        try {
            $cur = $locked ? ORM::for_table('tbl_routers')->select_many('ip_address', 'enabled')->find_one($router->id) : null;
            if (!$cur || (string) $cur['ip_address'] !== (string) $router->ip_address || (int) $cur['enabled'] !== 1) {
                continue;
            }
            if ($isOnline) {
                $router->last_seen = date('Y-m-d H:i:s');
                $router->status = 'Online';
            } else {
                $router->status = 'Offline';
                $offlineRouters[] = $router;
            }
            $router->save();
        } finally {
            if ($locked) {
                try {
                    ORM::getDb()->query("SELECT RELEASE_LOCK('phpnuxbill_router_endpoint')");
                } catch (Throwable $e) {
                }
            }
        }
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
        $routerCheckMail($adminEmail, $subject, $message);
        // a short time limit (the default is minutes): a stalled Telegram must not hold up the monitor and the backups below
        Message::sendTelegram($message, null, '', 15);
    }

    if (!empty($errors)) {
        $message = "The following errors occurred during router monitoring:\n";
        foreach ($errors as $error) {
            $message .= "$error\n";
        }

        $adminEmail = $config['mail_from'];
        $subject = "Router Monitoring Error Alert";
        $routerCheckMail($adminEmail, $subject, $message);
        // a short time limit (the default is minutes): a stalled Telegram must not hold up the monitor and the backups below
        Message::sendTelegram($message, null, '', 15);
    }
    echo "Router monitoring finished checking.\n";
}

flock($lock, LOCK_UN);
fclose($lock);
unlink($lockFile);

$timestampFile = "$UPLOAD_PATH/cron_last_run.txt";
file_put_contents($timestampFile, time());

// Pre-warm Redis dashboard cache in the background
if (class_exists('RedisCache') && RedisCache::isDashboardCacheEnabled()) {
    try {
        echo "Updating Redis dashboard cache in background...\n";
        RedisCache::warmDashboard();
        echo "Redis dashboard cache successfully updated.\n";
    } catch (Throwable $e) {
        echo "Redis cache warm note: " . $e->getMessage() . "\n";
    }
}

// router history and alerts (Network > Routers > Settings), a reading of each router about every 5 minutes
try {
    // the lines hold what routers answered: shown as text, not markup, when the cron is opened in a browser
    foreach (RouterMonitor::run() as $line) {
        echo "Router monitor: " . ($isCli ? $line : htmlspecialchars($line, ENT_QUOTES)) . "\n";
    }
} catch (Throwable $e) {
    echo "Router monitor note: " . ($isCli ? $e->getMessage() : htmlspecialchars($e->getMessage(), ENT_QUOTES)) . "\n";
}

// nightly router backups (Network > Routers > Settings), once a day; saved on each router itself
try {
    foreach (RouterApi::nightlyBackups() as $line) {
        echo "Router backup: " . ($isCli ? $line : htmlspecialchars($line, ENT_QUOTES)) . "\n";
    }
} catch (Throwable $e) {
    echo "Router backup note: " . ($isCli ? $e->getMessage() : htmlspecialchars($e->getMessage(), ENT_QUOTES)) . "\n";
}

run_hook('cronjob_end'); #HOOK
echo "Cron job finished and completed successfully.\n";
