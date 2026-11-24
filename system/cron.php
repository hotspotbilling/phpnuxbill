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

// the session is closed first when the run ends, before the alert emails queued for then (cron.php's router check and
// RouterMonitor's, run in the order registered): a stalled mail server must not keep the session locked for the next run
// opened in the same browser. The run's own lock is released before the router history (below), so nothing else waits on SMTP.
register_shutdown_function('session_write_close');

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

            // One expiry at a time for this customer: the routers.php sync takes the same lock around its re-read and device call,
            // so it cannot send access back after this has read the recharge as on. Not taken where a database frees the locks a
            // connection holds when it takes another (MikrotikVpn::nestedLocks() false). Not got in time: skipped, the next run retries.
            $expLock = null;
            if ((class_exists('MikrotikVpn') || DeviceLoader::load('MikrotikVpn')) && MikrotikVpn::nestedLocks()) {
                $expLock = 'phpnuxbill_exp_c_' . (int) $ds['customer_id'];
                $gotExp = false;
                try {
                    $gotExp = (int) ORM::getDb()->query('SELECT GET_LOCK(' . ORM::getDb()->quote($expLock) . ', 5)')->fetchColumn() === 1;
                } catch (Throwable $e) {
                    $gotExp = false;
                }
                if (!$gotExp) {
                    $expLock = null;
                    echo "Busy (being changed by another request), retried next run\n";
                    continue;
                }
            }
            try {
                // Fetch user recharge details (read under the lock: a recharge turned off or renewed meanwhile is left alone)
                $u = ORM::for_table('tbl_user_recharges')->where('id', $ds['id'])->find_one();
                if (!$u) {
                    throw new Exception("User recharge record not found for ID: " . $ds['id']);
                }
                if ($u['status'] !== 'on') {
                    continue;
                }
                // renewed while waiting for the lock: no longer expired, nothing is taken off
                if (strtotime($u['expiration'] . ' ' . $u['time']) > time()) {
                    continue;
                }

                // Fetch customer details
                $c = ORM::for_table('tbl_customers')->where('id', $ds['customer_id'])->find_one();
                if (!$c) {
                    $c = $u;
                }

                // Fetch plan details
                $p = ORM::for_table('tbl_plans')->where('id', $u['plan_id'])->find_one();
                if (!$p) {
                    // the plan was deleted: there is no device to take the customer off, and the error would come back on every run.
                    // The recharge is marked off, and a note says the router may still hold the user, to be removed by hand
                    $u->status = 'off';
                    $u->save();
                    _log("Plan #{$u['plan_id']} no longer exists: recharge of {$u['username']} ({$u['namebp']}) was marked off. Remove the user from router '{$u['routers']}' by hand if it is still there.", 'Router');
                    echo "Plan not found for ID {$u['plan_id']}: recharge marked off\n";
                    continue;
                }

                // A fixed-IP clear (or an address-list entry) the router refused leaves the recharge on, so the next run tries
                // again; the customer is told once (a marker in tbl_appconfig), and the recharge goes off when it succeeds.
                $pendingKey = 'exp_pending_' . (int) $u['id'];
                $pendingRow = ORM::for_table('tbl_appconfig')->where('setting', $pendingKey)->find_one();
                $partial = null;
                // the marker belongs to this expiry only (a renewed recharge has a later date and is told again)
                $pendingVal = $u['expiration'] . ' ' . $u['time'];
                if ($pendingRow && (string) $pendingRow['value'] !== $pendingVal) {
                    $pendingRow->delete();
                    $pendingRow = false;
                }

                $dvc = Package::getDevice($p);
                if ($_app_stage != 'demo') {
                    if (file_exists($dvc)) {
                        require_once $dvc;
                        try {
                            $r = (new $p['device'])->remove_customer($c, $p);
                            if (is_string($r) && $r !== '') {
                                $partial = $r; // MikrotikPppoe returns its partial result
                            }
                        } catch (Exception $e) {
                            if ($e->getCode() !== 207) {
                                throw $e;
                            }
                            $partial = $e->getMessage(); // the access is taken off, one later step was refused
                        }
                    } else {
                        throw new Exception("Cron error: Devices " . $p['device'] . "not found, cannot disconnect " . $c['username'] . "\n");
                    }
                }

                if ($partial !== null) {
                    _log('Expiry of ' . $c['username'] . ' is incomplete, retried next run: ' . $partial, 'Router');
                    echo "Partial: " . ($isCli ? $partial : htmlspecialchars($partial, ENT_QUOTES)) . "\n";
                }

                // Send notification and update user status
                try {
                    if (!$pendingRow) {
                        print_r(sendReminderNotifications(
                            $config,
                            $c,
                            $u['namebp'],
                            $p['price'],
                            Message::getMessageType($p['type'], $textExpired),
                            $config['user_notification_expired']
                        )) . "\n";
                    }
                    if ($partial !== null) {
                        if (!$pendingRow) {
                            $pendingRow = ORM::for_table('tbl_appconfig')->create();
                            $pendingRow->setting = $pendingKey;
                            $pendingRow->value = $pendingVal;
                            $pendingRow->save();
                        }
                    } else {
                        $u->status = 'off';
                        $u->save();
                        if ($pendingRow) {
                            $pendingRow->delete();
                        }
                    }
                } catch (Throwable $e) {
                    _log($e->getMessage());
                    sendTelegram($e->getMessage());
                    echo "Error: " . $e->getMessage() . "\n";
                }
            } finally {
                if ($expLock !== null) {
                    try {
                        ORM::getDb()->query('SELECT RELEASE_LOCK(' . ORM::getDb()->quote($expLock) . ')');
                    } catch (Throwable $e) {
                    }
                }
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

// RADIUS sessions: those that stopped sending accounting updates, then old finished REST sessions (rad_acct)
try {
    // accounting packets radius.php could not save (database busy or down) and kept in a file: written now, oldest first,
    // before stale sessions are judged (a queued update may be what shows a session is still alive)
    try {
        RadiusSessions::replayAccounting();
    } catch (Throwable $e) {
        // a failed replay (packets stay queued for the next run) must not skip the stale-session cleanup and retention below
        echo "RADIUS accounting replay: " . ($isCli ? $e->getMessage() : htmlspecialchars($e->getMessage(), ENT_QUOTES)) . "\n";
    }
    // Clear Stale Sessions (Settings > General): sessions that stopped sending accounting updates are closed, REST and MySQL
    // alike, on every run (system/cron/cleanup_stale_sessions.php does the same, for a site that runs it more often)
    $staleMinutes = RadiusSessions::staleMinutes($config);
    if ($staleMinutes > 0) {
        foreach (RadiusSessions::closeStale($staleMinutes)['lines'] as $line) {
            echo "RADIUS: " . ($isCli ? $line : htmlspecialchars($line, ENT_QUOTES)) . "\n";
        }
    }
    // Finished sessions older than the number of days set in Settings (0 keeps them all), a few thousand rows per run so
    // the table is never locked for long. A session inside an active recharge is always kept: a limited plan adds up its
    // usage from these rows (radius.php, radius_usage: rows of the recharge's own username with dateAdded between the recharge
    // start and end, which is what the test below mirrors; a separate PPPoE username's rows are not part of that sum).
    $keepDays = (int) ($config['frrest_keep_days'] ?? 0);
    if ($keepDays > 0) {
        $before = date('Y-m-d H:i:s', time() - $keepDays * 86400);
        $removed = 0;
        for ($i = 0; $i < 20; $i++) {
            ORM::raw_execute(
                "DELETE FROM rad_acct
                  WHERE acctstatustype = 'Stop' AND COALESCE(dateUpdated, dateAdded) < ?
                    AND NOT EXISTS (SELECT 1 FROM tbl_user_recharges r
                         WHERE r.username = rad_acct.username AND r.status = 'on'
                           AND rad_acct.dateAdded >= TIMESTAMP(r.recharged_on, r.recharged_time)
                           AND rad_acct.dateAdded <= TIMESTAMP(r.expiration, r.time))
                  LIMIT 5000",
                [$before]
            );
            $n = ORM::get_last_statement()->rowCount();
            $removed += $n;
            if ($n < 5000) {
                break;
            }
        }
        if ($removed > 0) {
            echo "RADIUS REST: removed $removed finished session(s) older than $keepDays day(s)\n";
        }
    }
} catch (Throwable $e) {
    // (no rad_acct table: RADIUS REST is not used here)
    echo "RADIUS REST sessions: " . ($isCli ? $e->getMessage() : htmlspecialchars($e->getMessage(), ENT_QUOTES)) . "\n";
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
        // nothing to check, but the rest of the run (history pruning, nightly backups, the end hook) still goes ahead
        echo "No active routers found in the database.\n";
        $routers = [];
    }

    $offlineRouters = [];
    $errors = [];

    foreach ($routers as $router) {
        $ip = (string) $router->ip_address;
        $isOnline = false;
        // kept for this router until the check below confirms the address checked is still the saved one and the router is
        // still enabled: an error for an address changed (or a router disabled) meanwhile must not reach the alert
        $probeError = null;

        try {
            // "host", "host:port", "[IPv6]" or "[IPv6]:port", read as the router pages and devices read it; a malformed saved
            // address is an error for this router only, the next one is still checked
            [$ip, $port] = RouterAddress::split($router->ip_address);
            $ip = (string) $ip;
            if ($ip === '') {
                throw new Exception("The router {$router->name} has no address saved");
            }
            $port = (int) ($port ?: 8728);
            $sockHost = strpos($ip, ':') !== false ? '[' . $ip . ']' : $ip; // an IPv6 address is written in brackets to connect
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
        } catch (Throwable $e) {
            $probeError = $e->getMessage();
        }

        // the result is kept only for the address that was checked: under the lock routers.php holds while it saves an edit, the
        // router is read again, and a result for an address changed meanwhile is dropped (the edit has cleared the status), as is
        // one for a router disabled meanwhile (no longer checked, so no status and no offline alert)
        $locked = false;
        try {
            $locked = (int) ORM::getDb()->query("SELECT GET_LOCK('phpnuxbill_router_endpoint', 20)")->fetchColumn() === 1;
            if (!$locked) {
                // timed out: nothing is saved for this router (the safeguard below), and the skipped check is reported
                _log("Router check skipped for {$router->name}: the router endpoint lock was busy");
                $errors[] = "Router check skipped for {$router->name}: the router endpoint lock was busy";
            }
        } catch (Throwable $e) {
            _log($e->getMessage());
            $errors[] = "Router check skipped for {$router->name}: " . $e->getMessage();
        }
        try {
            $cur = $locked ? ORM::for_table('tbl_routers')->select_many('ip_address', 'enabled')->find_one($router->id) : null;
            if (!$cur || (string) $cur['ip_address'] !== (string) $router->ip_address || (int) $cur['enabled'] !== 1) {
                continue;
            }
            if ($probeError !== null) {
                _log($probeError);
                $errors[] = "Error with router $ip: " . $probeError;
            }
            if ($isOnline) {
                $router->last_seen = date('Y-m-d H:i:s');
                $router->status = 'Online';
            } else {
                $router->status = 'Offline';
            }
            $router->save();
            // listed as offline only once the status is saved: a failed save stays in the error alert alone
            if (!$isOnline) {
                $offlineRouters[] = $router;
            }
        } catch (Throwable $e) {
            // a failed re-read or save is reported for this router only: the next router, the history and the nightly
            // backups below still run
            _log($e->getMessage());
            $errors[] = "Error with router {$router->name}: " . $e->getMessage();
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
        // a short time limit (the default is minutes): a stalled Telegram must not hold up the monitor and the backups below,
        // and a failure there (or in a send_telegram plugin hook) must not stop them either
        try {
            Message::sendTelegram($message, null, '', 15);
        } catch (Throwable $e) {
            echo "Router alert Telegram note: " . ($isCli ? $e->getMessage() : htmlspecialchars($e->getMessage(), ENT_QUOTES)) . "\n";
        }
    }

    if (!empty($errors)) {
        $message = "The following errors occurred during router monitoring:\n";
        foreach ($errors as $error) {
            $message .= "$error\n";
        }

        $adminEmail = $config['mail_from'];
        $subject = "Router Monitoring Error Alert";
        $routerCheckMail($adminEmail, $subject, $message);
        // a short time limit (the default is minutes): a stalled Telegram must not hold up the monitor and the backups below,
        // and a failure there (or in a send_telegram plugin hook) must not stop them either
        try {
            Message::sendTelegram($message, null, '', 15);
        } catch (Throwable $e) {
            echo "Router alert Telegram note: " . ($isCli ? $e->getMessage() : htmlspecialchars($e->getMessage(), ENT_QUOTES)) . "\n";
        }
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
