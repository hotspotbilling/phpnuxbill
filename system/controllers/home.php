<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_auth();
$ui->assign('_title', Lang::T('Dashboard'));

$user = User::_info();
$ui->assign('_user', $user);

if (isset($_GET['renewal'])) {
    if (!App::checkToken(_get('stoken'))) {
        r2(getUrl('home'), 'e', Lang::T('Invalid or expired token'));
    }
    App::setToken(_get('stoken'), 1); // consume token, prevent replay
    $user->auto_renewal = _get('renewal') == '1' ? 1 : 0;
    $user->save();
    r2(getUrl('home'), 's', $user->auto_renewal ? Lang::T('Auto Renewal Enabled') : Lang::T('Auto Renewal Disabled'));
}

if (_post('send') == 'balance') {
    if ($config['enable_balance'] == 'yes' && $config['allow_balance_transfer'] == 'yes') {
        if (!Csrf::check(_post('csrf_token'))) {
            r2(getUrl('home'), 'e', Lang::T('Invalid or expired token'));
        }
        if ($user['status'] != 'Active') {
            _alert(Lang::T('This account status') . ' : ' . Lang::T($user['status']), 'danger', "");
        }
        $target = ORM::for_table('tbl_customers')->where('username', _post('username'))->find_one();
        if (!$target) {
            r2(getUrl('home'), 'd', Lang::T('Username not found'));
        }
        $username = _post('username');
        $balance = round(floatval(_post('balance')), 2);
        if ($balance <= 0) {
            r2(getUrl('home'), 'd', Lang::T('Invalid amount'));
        }
        if (floatval($user['balance']) < $balance) {
            r2(getUrl('home'), 'd', Lang::T('insufficient balance'));
        }
        if (!empty($config['minimum_transfer']) && $balance < floatval($config['minimum_transfer'])) {
            r2(getUrl('home'), 'd', Lang::T('Minimum Transfer') . ' ' . Lang::moneyFormat($config['minimum_transfer']));
        }
        if ($user['username'] == $target['username']) {
            r2(getUrl('home'), 'd', Lang::T('Cannot send to yourself'));
        }
        if (Balance::transfer($user['id'], $username, $balance)) {
            //sender
            $d = ORM::for_table('tbl_payment_gateway')->create();
            $d->username = $user['username'];
            $d->gateway = $target['username'];
            $d->plan_id = 0;
            $d->plan_name = 'Send Balance';
            $d->routers_id = 0;
            $d->routers = 'balance';
            $d->price = $balance;
            $d->payment_method = "Customer";
            $d->payment_channel = "Balance";
            $d->created_date = date('Y-m-d H:i:s');
            $d->paid_date = date('Y-m-d H:i:s');
            $d->expired_date = date('Y-m-d H:i:s');
            $d->pg_url_payment = 'balance';
            $d->status = 2;
            $d->save();
            //receiver
            $d = ORM::for_table('tbl_payment_gateway')->create();
            $d->username = $target['username'];
            $d->gateway = $user['username'];
            $d->plan_id = 0;
            $d->plan_name = 'Receive Balance';
            $d->routers_id = 0;
            $d->routers = 'balance';
            $d->payment_method = "Customer";
            $d->payment_channel = "Balance";
            $d->price = $balance;
            $d->created_date = date('Y-m-d H:i:s');
            $d->paid_date = date('Y-m-d H:i:s');
            $d->expired_date = date('Y-m-d H:i:s');
            $d->pg_url_payment = 'balance';
            $d->status = 2;
            $d->save();
            //
            Message::sendBalanceNotification($user, $target, $balance, ($user['balance'] - $balance), Lang::getNotifText('balance_send'), $config['user_notification_payment']);
            Message::sendBalanceNotification($target, $user, $balance, ($target['balance'] + $balance), Lang::getNotifText('balance_received'), $config['user_notification_payment']);
            Message::sendTelegram("#u$user[username] send balance to #u$target[username] \n" . Lang::moneyFormat($balance));
            r2(getUrl('home'), 's', Lang::T('Sending balance success'));
        }
    } else {
        r2(getUrl('home'), 'd', Lang::T('Failed, balance is not available'));
    }
} else if (_post('send') == 'plan') {
    if ($user['status'] != 'Active') {
        _alert(Lang::T('This account status') . ' : ' . Lang::T($user['status']), 'danger', "");
    }
    $actives = ORM::for_table('tbl_user_recharges')
        ->where('username', _post('username'))
        ->find_many();
    foreach ($actives as $active) {
        $router = ORM::for_table('tbl_routers')->where('name', $active['routers'])->find_one();
        if ($router) {
            r2(getUrl('order/send/$router[id]/$active[plan_id]&u=') . trim(_post('username')), 's', Lang::T('Review package before recharge'));
        }
    }
    r2(getUrl('home'), 'w', Lang::T('Your friend do not have active package'));
}


// Sync plan to router
if (isset($_GET['sync']) && !empty($_GET['sync'])) {
    if (!App::checkToken(_get('stoken'))) {
        r2(getUrl('home'), 'e', Lang::T('Invalid or expired token'));
    }
    App::setToken(_get('stoken'), 1); // consume token, prevent replay
    $_bill = User::_billing();
    foreach ($_bill as $tur) {
        if ($tur['status'] == 'on') {
            $p = ORM::for_table('tbl_plans')->findOne($tur['plan_id']);
            if ($p) {
                $c = ORM::for_table('tbl_customers')->findOne($tur['customer_id']);
                if ($c) {
                    $dvc = Package::getDevice($p);
                    if ($_app_stage != 'demo') {
                        if (file_exists($dvc)) {
                            require_once $dvc;
                            if (method_exists($p['device'], 'sync_customer')) {
                                (new $p['device'])->sync_customer($c, $p);
                            }else{
                                (new $p['device'])->add_customer($c, $p);
                            }
                        } else {
                            $log .= "DEVICES NOT FOUND : $tur[namebp], $tur[type], $tur[routers]<br>";
                            continue;
                        }
                    }
                    $log .= "DONE : $tur[namebp], $tur[type], $tur[routers]<br>";
                } else {
                    $log .= "Customer NOT FOUND : $tur[namebp], $tur[type], $tur[routers]<br>";
                }
            } else {
                $log .= "PLAN NOT FOUND : $tur[namebp], $tur[type], $tur[routers]<br>";
            }
        }
    }
    r2(getUrl('home'), 's', $log);
}

if (isset($_GET['recharge']) && !empty($_GET['recharge'])) {
    if ($user['status'] != 'Active') {
        _alert(Lang::T('This account status') . ' : ' . Lang::T($user['status']), 'danger', "");
    }
    if (!empty(App::getTokenValue(_get('stoken')))) {
        r2(getUrl('voucher/invoice/'));
        die();
    }
    $bill = ORM::for_table('tbl_user_recharges')->where('id', $_GET['recharge'])->where('username', $user['username'])->findOne();
    if ($bill) {
        if ($bill['routers'] == 'radius') {
            $router = 'radius';
        } else {
            $routers = ORM::for_table('tbl_routers')->where('name', $bill['routers'])->find_one();
            $router = $routers['id'];
        }
        r2(getUrl("order/gateway/$router/$bill[plan_id]"));
    }
} else if (!empty(_get('extend'))) {
    if ($user['status'] != 'Active') {
        _alert(Lang::T('This account status') . ' : ' . Lang::T($user['status']), 'danger', "");
    }
    if (!$config['extend_expired']) {
        r2(getUrl('home'), 'e', "cannot extend");
    }
    if (!App::checkToken(_get('stoken'))) {
        r2(getUrl('home'), 'e', Lang::T('Invalid or expired token'));
    }
    $id = _get('extend');
    $tur = ORM::for_table('tbl_user_recharges')->where('customer_id', $user['id'])->where('id', $id)->find_one();
    if ($tur) {
        $m = date("m");
        $path = $CACHE_PATH . DIRECTORY_SEPARATOR . "extends" . DIRECTORY_SEPARATOR;
        if (!file_exists($path)) {
            mkdir($path);
        }
        $path .= $user['id'] . ".txt";
        if (file_exists($path)) {
            // is already extend
            $last = file_get_contents($path);
            if ($last == $m) {
                r2(getUrl('home'), 'e', "You already extend for this month");
            }
        }
        if ($tur['status'] != 'on') {
            $p = ORM::for_table('tbl_plans')->findOne($tur['plan_id']);
            $dvc = Package::getDevice($p);
            $extendPartial = false; // the router kept the change but a later step (a fixed IP, a port forward) was refused
            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    global $isChangePlan;
                    $isChangePlan = true;
                    try {
                        (new $p['device'])->add_customer($user, $p);
                    } catch (Exception $e) {
                        // code 207: saved on the router, one later step refused (a fixed IP not cleared): the change stands, so it is
                        // saved here too, and logged for the admins; any other error stops as before
                        if ($e->getCode() !== 207) {
                            throw $e;
                        }
                        _log('[' . $user['username'] . '] extend' . ': ' . $e->getMessage() . ' (pending: a Sync customers run repairs it)', 'Router');
                        $extendPartial = true;
                    }
                } else {
                    r2(getUrl('home'), 'e', Lang::T("Devices Not Found"));
                }
            }

            // make customer cannot extend again
            $days = $config['extend_days'];
            $expiration = date('Y-m-d', strtotime(" +$days day"));
            $tur->expiration = $expiration;
            $tur->status = "on";
            $tur->save();
            App::setToken(_get('stoken'), 1); // consume token, prevent replay
            file_put_contents($path, $m);
            _log("Customer $tur[customer_id] $user[fullname] ($tur[username]) extend for $days days", "Customer", $user['id']);
            Message::sendTelegram("#u$user[username] ($user[fullname]) #id$tur[customer_id] #extend #" . $p['type'] . " \n" . $p['name_plan'] .
                "\nLocation: " . $p['routers'] .
                "\nCustomer: " . $user['fullname'] .
                "\nNew Expired: " . Lang::dateAndTimeFormat($expiration, $tur['time']));
            if ($extendPartial) {
                // not reported as complete: the extension is saved, the router step is still pending until the next sync
                r2(getUrl('home'), 'w', "Extend until $expiration. " . Lang::T('The router update is not complete yet. Contact support if the connection does not work.'));
            }
            r2(getUrl('home'), 's', "Extend until $expiration");
        } else {
            r2(getUrl('home'), 'e', "Plan is not expired");
        }
    } else {
        r2(getUrl('home'), 'e', "Plan Not Found or Not Active");
    }
} else if (isset($_GET['deactivate']) && !empty($_GET['deactivate'])) {
    if (!App::checkToken(_get('stoken'))) {
        r2(getUrl('home'), 'e', Lang::T('Invalid or expired token'));
    }
    $bill = ORM::for_table('tbl_user_recharges')->where('id', _get('deactivate'))->where('username', $user['username'])->findOne();
    if ($bill) {
        $p = ORM::for_table('tbl_plans')->where('id', $bill['plan_id'])->find_one();
        $dvc = Package::getDevice($p);
        if ($_app_stage != 'demo') {
            if (file_exists($dvc)) {
                require_once $dvc;
                try {
                    (new $p['device'])->remove_customer($user, $p);
                } catch (Exception $e) {
                    // code 207: the plan was taken off but a later step was refused (a fixed IP not cleared, a port forward left): the
                    // deactivation stands, and the admins are told in the log; any other error stops as before
                    // (MikrotikStatic raises it when the Expired-list block failed: access is not stopped there, so it stops as well)
                    if ($e->getCode() !== 207 || $p['device'] === 'MikrotikStatic') {
                        throw $e;
                    }
                    _log('[' . $user['username'] . '] deactivate: ' . $e->getMessage(), 'Router');
                }
            } else {
                r2(getUrl('home'), 'e', Lang::T("Devices Not Found"));
            }
        }
        $bill->status = 'off';
        $bill->expiration = date('Y-m-d');
        $bill->time = date('H:i:s');
        $bill->save();
        App::setToken(_get('stoken'), 1); // consume token, prevent replay
        _log('User ' . $bill['username'] . ' Deactivate ' . $bill['namebp'], 'Customer', $bill['customer_id']);
        Message::sendTelegram('User u' . $bill['username'] . ' Deactivate ' . $bill['namebp']);
        r2(getUrl('home'), 's', 'Success deactivate ' . $bill['namebp']);
    } else {
        r2(getUrl('home'), 'e', 'No Active Plan');
    }
}

/**
 * Safely assign session variable to UI with HTML encoding
 * 
 * @param object $ui The UI object
 * @param string $var_name Variable name for template
 * @param string $session_key Session key to retrieve
 * @return void
 */
function safeAssignSessionVar($ui, $var_name, $session_key) {
    if (isset($_SESSION[$session_key])) {
        $ui->assign($var_name, htmlspecialchars($_SESSION[$session_key], ENT_QUOTES, 'UTF-8'));
    }
}

if (!empty($_SESSION['nux-mac']) && !empty($_SESSION['nux-ip']) && $_c['hs_auth_method'] != 'hchap') {
    safeAssignSessionVar($ui, 'nux_mac', 'nux-mac');
    safeAssignSessionVar($ui, 'nux_ip', 'nux-ip');
    $bill = ORM::for_table('tbl_user_recharges')->where('id', _get('id'))->where('username', $user['username'])->findOne();
    if ($bill) {
        $p = ORM::for_table('tbl_plans')->where('id', $bill['plan_id'])->find_one();
        $dvc = Package::getDevice($p);
        if ($_app_stage != 'demo') {
            if (file_exists($dvc)) {
                require_once $dvc;
                if (_get('mikrotik') == 'login') {
                    (new $p['device'])->connect_customer($user, $_SESSION['nux-ip'], $_SESSION['nux-mac'], $bill['routers']);
                    r2(getUrl('home'), 's', Lang::T('Login Request successfully'));
                } else if (_get('mikrotik') == 'logout') {
                    (new $p['device'])->disconnect_customer($user, $bill['routers']);
                    r2(getUrl('home'), 's', Lang::T('Logout Request successfully'));
                }
            } else {
                r2(getUrl('home'), 'e', Lang::T("Devices Not Found"));
            }
        }
    }
}

if (!empty($_SESSION['nux-mac']) && !empty($_SESSION['nux-ip']) && !empty($_SESSION['nux-hostname']) && $_c['hs_auth_method'] == 'hchap') {
    $apkurl = (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'onoff') || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    safeAssignSessionVar($ui, 'nux_mac', 'nux-mac');
    safeAssignSessionVar($ui, 'nux_ip', 'nux-ip');
    $keys = explode('-', isset($_SESSION['nux-key']) ? $_SESSION['nux-key'] : '');
    $ui->assign('hostname', $_SESSION['nux-hostname']);
    $ui->assign('apkurl', $apkurl);
    $ui->assign('key1', isset($keys[0]) ? $keys[0] : '');
    $ui->assign('key2', isset($keys[1]) ? $keys[1] : '');
    $ui->assign('hchap', _get('hchap'));
    $ui->assign('logged', _get('logged'));
    if ($_app_stage != 'demo') {
        if (_get('mikrotik') == 'login') {
            r2(getUrl('home&hchap=true'), 's', Lang::T('Login Request successfully'));
        }
        $getmsg = strip_tags(_get('msg'));
        ///get auth notification from mikrotik
        if ($getmsg == 'Connected') {
            $msg = Lang::T($getmsg);
            r2(getUrl('home&logged=1'), 's', $msg);
        } else if ($getmsg) {
            $msg = Lang::T($getmsg);
            r2(getUrl('home'), 's', $msg);
        }
    }
}


$widgets = ORM::for_table('tbl_widgets')->where("enabled", 1)->where('user', 'Customer')->order_by_asc("orders")->findArray();
$count = count($widgets);
for ($i = 0; $i < $count; $i++) {
    try{
        $widgetFile = preg_replace('/[^a-zA-Z0-9_]/', '', $widgets[$i]['widget']);
        if(file_exists($WIDGET_PATH . DIRECTORY_SEPARATOR . 'customer' . DIRECTORY_SEPARATOR . $widgetFile.".php")){
            require_once $WIDGET_PATH . DIRECTORY_SEPARATOR . 'customer' . DIRECTORY_SEPARATOR . $widgetFile.".php";
            $widgets[$i]['content'] = (new $widgetFile())->getWidget($widgets[$i]);
        }else{
            $widgets[$i]['content'] = "Widget not found";
        }
    } catch (Throwable $e) {
        $widgets[$i]['content'] = Lang::T('Failed to load widget');
    }
}

$ui->assign('widgets', $widgets);

$ui->assign('code', alphanumeric(_get('code'), "-"));

run_hook('view_customer_dashboard'); #HOOK
$ui->display('customer/dashboard.tpl');
