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
    $user->auto_renewal = $_GET['renewal'];
    $user->save();
}

if (_post('send') == 'balance') {
    if ($config['enable_balance'] == 'yes' && $config['allow_balance_transfer'] == 'yes') {
        if ($user['status'] != 'Active') {
            _alert(Lang::T('This account status') . ' : ' . Lang::T($user['status']), 'danger', "");
        }
        $target = ORM::for_table('tbl_customers')->where('username', _post('username'))->find_one();
        if (!$target) {
            r2(U . 'home', 'd', Lang::T('Username not found'));
        }
        $username = _post('username');
        $balance = _post('balance');
        if ($user['balance'] < $balance) {
            r2(U . 'home', 'd', Lang::T('insufficient balance'));
        }
        if (!empty($config['minimum_transfer']) && intval($balance) < intval($config['minimum_transfer'])) {
            r2(U . 'home', 'd', Lang::T('Minimum Transfer') . ' ' . Lang::moneyFormat($config['minimum_transfer']));
        }
        if ($user['username'] == $target['username']) {
            r2(U . 'home', 'd', Lang::T('Cannot send to yourself'));
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
            Message::sendBalanceNotification($user, $balance, ($user['balance'] - $balance), Lang::getNotifText('balance_send'), $config['user_notification_payment']);
            Message::sendBalanceNotification($target, $balance, ($target['balance'] + $balance), Lang::getNotifText('balance_received'), $config['user_notification_payment']);
            Message::sendTelegram("#u$user[username] send balance to #u$target[username] \n" . Lang::moneyFormat($balance));
            r2(U . 'home', 's', Lang::T('Sending balance success'));
        }
    } else {
        r2(U . 'home', 'd', Lang::T('Failed, balance is not available'));
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
            r2(U . "order/send/$router[id]/$active[plan_id]&u=" . trim(_post('username')), 's', Lang::T('Review package before recharge'));
        }
    }
    r2(U . 'home', 'w', Lang::T('Your friend do not have active package'));
}
$_bill = User::_billing();
$ui->assign('_bills', $_bill);

// Sync plan to router
if (isset($_GET['sync']) && !empty($_GET['sync'])) {
    foreach ($_bill as $tur) {
        if($tur['status'] == 'on'){
            $p = ORM::for_table('tbl_plans')->findOne($tur['plan_id']);
            if ($p) {
                $c = ORM::for_table('tbl_customers')->findOne($tur['customer_id']);
                if ($c) {
                    $dvc = Package::getDevice($p);
                    if ($_app_stage != 'demo') {
                        if (file_exists($dvc)) {
                            require_once $dvc;
                            (new $p['device'])->add_customer($c, $p);
                        } else {
                            new Exception(Lang::T("Devices Not Found"));
                        }
                    }
                    $log .= "DONE : $ptur[namebp], $tur[type], $tur[routers]<br>";
                } else {
                    $log .= "Customer NOT FOUND : $tur[namebp], $tur[type], $tur[routers]<br>";
                }
            } else {
                $log .= "PLAN NOT FOUND : $tur[namebp], $tur[type], $tur[routers]<br>";
            }
        }
    }
    r2(U . 'home', 's', $log);
}

if (isset($_GET['recharge']) && !empty($_GET['recharge'])) {
    if ($user['status'] != 'Active') {
        _alert(Lang::T('This account status') . ' : ' . Lang::T($user['status']), 'danger', "");
    }
    if (!empty(App::getTokenValue(_get('stoken')))) {
        r2(U . "voucher/invoice/");
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
        if ($config['enable_balance'] == 'yes') {
            $plan = ORM::for_table('tbl_plans')->find_one($bill['plan_id']);
            if (!$plan['enabled']) {
                r2(U . "home", 'e', 'Plan is not exists');
            }
            if ($user['balance'] > $plan['price']) {
                r2(U . "order/pay/$router/$bill[plan_id]&stoken=" . _get('stoken'), 'e', 'Order Plan');
            } else {
                r2(U . "order/buy/$router/$bill[plan_id]", 'e', 'Order Plan');
            }
        } else {
            r2(U . "order/buy/$router/$bill[plan_id]", 'e', 'Order Plan');
        }
    }
} else if (!empty(_get('extend'))) {
    if ($user['status'] != 'Active') {
        _alert(Lang::T('This account status') . ' : ' . Lang::T($user['status']), 'danger', "");
    }
    if (!$config['extend_expired']) {
        r2(U . 'home', 'e', "cannot extend");
    }
    if (!empty(App::getTokenValue(_get('stoken')))) {
        r2(U . 'home', 'e', "You already extend");
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
                r2(U . 'home', 'e', "You already extend for this month");
            }
        }
        if ($tur['status'] != 'on') {
            $p = ORM::for_table('tbl_plans')->findOne($tur['plan_id']);
            $dvc = Package::getDevice($p);
            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    (new $p['device'])->add_customer($user, $p);
                } else {
                    new Exception(Lang::T("Devices Not Found"));
                }
            }

            // make customer cannot extend again
            $days = $config['extend_days'];
            $expiration = date('Y-m-d', strtotime(" +$days day"));
            $tur->expiration = $expiration;
            $tur->status = "on";
            $tur->save();
            App::setToken(_get('stoken'), $id);
            file_put_contents($path, $m);
            _log("Customer $tur[customer_id] $tur[username] extend for $days days", "Customer", $user['id']);
            Message::sendTelegram("#u$user[username] #extend #" . $p['type'] . " \n" . $p['name_plan'] .
                "\nLocation: " . $p['routers'] .
                "\nCustomer: " . $user['fullname'] .
                "\nNew Expired: " . Lang::dateAndTimeFormat($expiration, $tur['time']));
            r2(U . 'home', 's', "Extend until $expiration");
        } else {
            r2(U . 'home', 'e', "Plan is not expired");
        }
    } else {
        r2(U . 'home', 'e', "Plan Not Found or Not Active");
    }
} else if (isset($_GET['deactivate']) && !empty($_GET['deactivate'])) {
    $bill = ORM::for_table('tbl_user_recharges')->where('id', $_GET['deactivate'])->where('username', $user['username'])->findOne();
    if ($bill) {
        $p = ORM::for_table('tbl_plans')->where('id', $bill['plan_id'])->find_one();
        $dvc = Package::getDevice($p);
        if ($_app_stage != 'demo') {
            if (file_exists($dvc)) {
                require_once $dvc;
                (new $p['device'])->remove_customer($user, $p);
            } else {
                new Exception(Lang::T("Devices Not Found"));
            }
        }
        $bill->status = 'off';
        $bill->expiration = date('Y-m-d');
        $bill->time = date('H:i:s');
        $bill->save();
        _log('User ' . $bill['username'] . ' Deactivate ' . $bill['namebp'], 'Customer', $bill['customer_id']);
        Message::sendTelegram('User u' . $bill['username'] . ' Deactivate ' . $bill['namebp']);
        r2(U . 'home', 's', 'Success deactivate ' . $bill['namebp']);
    } else {
        r2(U . 'home', 'e', 'No Active Plan');
    }
}

if (!empty($_SESSION['nux-mac']) && !empty($_SESSION['nux-ip'] && $_c['hs_auth_method'] != 'hchap')) {
    $ui->assign('nux_mac', $_SESSION['nux-mac']);
    $ui->assign('nux_ip', $_SESSION['nux-ip']);
    $bill = ORM::for_table('tbl_user_recharges')->where('id', $_GET['id'])->where('username', $user['username'])->findOne();
    $p = ORM::for_table('tbl_plans')->where('id', $bill['plan_id'])->find_one();
    $dvc = Package::getDevice($p);
    if ($_app_stage != 'demo') {
        if (file_exists($dvc)) {
            require_once $dvc;
            if ($_GET['mikrotik'] == 'login') {
                (new $p['device'])->connect_customer($user, $_SESSION['nux-ip'], $_SESSION['nux-mac'], $bill['routers']);
                r2(U . 'home', 's', Lang::T('Login Request successfully'));
            } else if ($_GET['mikrotik'] == 'logout') {
                (new $p['device'])->disconnect_customer($user, $bill['routers']);
                r2(U . 'home', 's', Lang::T('Logout Request successfully'));
            }
        } else {
            new Exception(Lang::T("Devices Not Found"));
        }
    }
}

if (!empty($_SESSION['nux-mac']) && !empty($_SESSION['nux-ip'] && !empty($_SESSION['nux-hostname']) && $_c['hs_auth_method'] == 'hchap')) {
    $apkurl = (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'onoff')|| $_SERVER['SERVER_PORT'] == 443)?'https':'http').'://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $ui->assign('nux_mac', $_SESSION['nux-mac']);
    $ui->assign('nux_ip', $_SESSION['nux-ip']);
    $keys = explode('-', $_SESSION['nux-key']);
    $ui->assign('hostname', $_SESSION['nux-hostname']);
    $ui->assign('apkurl', $apkurl);
    $ui->assign('key1', $keys[0]);
    $ui->assign('key2', $keys[1]);
    $ui->assign('hchap', $_GET['hchap']);
    $ui->assign('logged', $_GET['logged']);
    if ($_app_stage != 'demo') {
            if ($_GET['mikrotik'] == 'login') {
                r2(U . 'home&hchap=true', 's', Lang::T('Login Request successfully'));
                }
                $getmsg = $_GET['msg'];
                ///get auth notification from mikrotik
                if($getmsg == 'Connected') {
                    $msg .= Lang::T($getmsg);
                    r2(U . 'home&logged=1', 's', $msg);
                } else if($getmsg){
                    $msg .= Lang::T($getmsg);
                    r2(U . 'home', 's', $msg);
            }
        }
    }

if (!empty($_SESSION['nux-mac']) && !empty($_SESSION['nux-ip'] && !empty($_SESSION['nux-hostname']) && $_c['hs_auth_method'] == 'hchap')) {
    $apkurl = (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'onoff')|| $_SERVER['SERVER_PORT'] == 443)?'https':'http').'://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $ui->assign('nux_mac', $_SESSION['nux-mac']);
    $ui->assign('nux_ip', $_SESSION['nux-ip']);
    $keys = explode('-', $_SESSION['nux-key']);
    $ui->assign('hostname', $_SESSION['nux-hostname']);
    $ui->assign('apkurl', $apkurl);
    $ui->assign('key1', $keys[0]);
    $ui->assign('key2', $keys[1]);
    $ui->assign('hchap', $_GET['hchap']);
    $ui->assign('logged', $_GET['logged']);
    if ($_app_stage != 'demo') {
            if ($_GET['mikrotik'] == 'login') {
                r2(U . 'home&hchap=true', 's', Lang::T('Login Request successfully'));
                }
                $getmsg = $_GET['msg'];
                ///get auth notification from mikrotik
                if($getmsg == 'Connected') {
                    $msg .= Lang::T($getmsg);
                    r2(U . 'home&logged=1', 's', $msg);
                } else if($getmsg){
                    $msg .= Lang::T($getmsg);
                    r2(U . 'home', 's', $msg);
            }
        }
    }

$ui->assign('unpaid', ORM::for_table('tbl_payment_gateway')
    ->where('username', $user['username'])
    ->where('status', 1)
    ->find_one());
$ui->assign('code', alphanumeric(_get('code'), "-"));

$abills = User::getAttributes("Bill");
$ui->assign('abills', $abills);

run_hook('view_customer_dashboard'); #HOOK
$ui->display('user-dashboard.tpl');
