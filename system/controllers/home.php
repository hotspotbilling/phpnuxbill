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
            Message::sendBalanceNotification($user['phonenumber'], $target['fullname'] . ' (' . $target['username'] . ')', $balance, ($user['balance'] - $balance), Lang::getNotifText('balance_send'), $config['user_notification_payment']);
            Message::sendBalanceNotification($target['phonenumber'], $user['fullname'] . ' (' . $user['username'] . ')', $balance, ($target['balance'] + $balance), Lang::getNotifText('balance_received'), $config['user_notification_payment']);
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

$ui->assign('_bills', User::_billing());

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
            if ($tur['routers'] != 'radius') {
                $mikrotik = Mikrotik::info($tur['routers']);
                $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                $router = $tur['routers'];
            }
            $p = ORM::for_table('tbl_plans')->findOne($tur['plan_id']);
            if (!$p) {
                r2(U . 'home', '3', "Plan Not Found");
            }
            if ($tur['routers'] == 'radius') {
                Radius::customerAddPlan($user, $p, $tur['expiration'] . ' ' . $tur['time']);
            } else {
                if ($tur['type'] == 'Hotspot') {
                    Mikrotik::removeHotspotUser($client, $user['username']);
                    Mikrotik::addHotspotUser($client, $p, $user);
                } else if ($tur['type'] == 'PPPOE') {
                    Mikrotik::removePpoeUser($client, $user['username']);
                    Mikrotik::addPpoeUser($client, $p, $user);
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
        if ($p['is_radius']) {
            Radius::customerDeactivate($user['username']);
        } else {
            try {
                $mikrotik = Mikrotik::info($bill['routers']);
                $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                if ($bill['type'] == 'Hotspot') {
                    Mikrotik::removeHotspotUser($client, $bill['username']);
                    Mikrotik::removeHotspotActiveUser($client, $bill['username']);
                } else if ($bill['type'] == 'PPPOE') {
                    Mikrotik::removePpoeUser($client, $bill['username']);
                    Mikrotik::removePpoeActive($client, $bill['username']);
                }
            } catch (Exception $e) {
                //ignore it maybe mikrotik has been deleted
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

if (!empty($_SESSION['nux-mac']) && !empty($_SESSION['nux-ip'])) {
    $ui->assign('nux_mac', $_SESSION['nux-mac']);
    $ui->assign('nux_ip', $_SESSION['nux-ip']);
    $bill = ORM::for_table('tbl_user_recharges')->where('id', $_GET['id'])->where('username', $user['username'])->findOne();
    if ($_GET['mikrotik'] == 'login') {
        $m = Mikrotik::info($bill['routers']);
        $c = Mikrotik::getClient($m['ip_address'], $m['username'], $m['password']);
        Mikrotik::logMeIn($c, $user['username'], $user['password'], $_SESSION['nux-ip'], $_SESSION['nux-mac']);
        r2(U . 'home', 's', Lang::T('Login Request successfully'));
    } else if ($_GET['mikrotik'] == 'logout') {
        $m = Mikrotik::info($bill['routers']);
        $c = Mikrotik::getClient($m['ip_address'], $m['username'], $m['password']);
        Mikrotik::logMeOut($c, $user['username']);
        r2(U . 'home', 's', Lang::T('Logout Request successfully'));
    }
}

$ui->assign('unpaid', ORM::for_table('tbl_payment_gateway')
    ->where('username', $user['username'])
    ->where('status', 1)
    ->find_one());
run_hook('view_customer_dashboard'); #HOOK
$ui->display('user-dashboard.tpl');
