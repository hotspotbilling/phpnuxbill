<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 **/
_auth();
$ui->assign('_title', $_L['Dashboard']);

$user = User::_info();
$ui->assign('_user', $user);

if (isset($_GET['renewal'])) {
    $user->auto_renewal = $_GET['renewal'];
    $user->save();
}

if (_post('send') == 'balance') {
    if ($config['enable_balance'] == 'yes' && $config['allow_balance_transfer'] == 'yes') {
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
    $active = ORM::for_table('tbl_user_recharges')
        ->where('username', _post('username'))
        ->find_one();
    $router = ORM::for_table('tbl_routers')->where('name', $active['routers'])->find_one();
    if ($router) {
        r2(U . "order/send/$router[id]/$active[plan_id]&u=" . trim(_post('username')), 's', Lang::T('Review package before recharge'));
    } else {
        r2(U . 'package/order', 'w', Lang::T('Your friend do not have active package'));
    }
}

//Client Page
$bill = User::_billing();
$ui->assign('_bill', $bill);

if(isset($_GET['recharge']) && $_GET['recharge'] == 1){
    $router = ORM::for_table('tbl_routers')->where('name', $bill['routers'])->find_one();
    if ($config['enable_balance'] == 'yes') {
        $plan = ORM::for_table('tbl_plans')->find_one($bill['plan_id']);
        if($user['balance']>$plan['price']){
            r2(U . "order/pay/$router[id]/$bill[plan_id]", 'e', 'Order Plan');
        }else{
            r2(U . "order/buy/$router[id]/$bill[plan_id]", 'e', 'Order Plan');
        }
    }else{
        r2(U . "order/buy/$router[id]/$bill[plan_id]", 'e', 'Order Plan');
    }
}else if(isset($_GET['deactivate']) && $_GET['deactivate'] == 1){
    if ($bill) {
        $mikrotik = Mikrotik::info($bill['routers']);
        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        if ($bill['type'] == 'Hotspot') {
            Mikrotik::removeHotspotUser($client, $bill['username']);
            Mikrotik::removeHotspotActiveUser($client, $bill['username']);
        } else if ($bill['type'] == 'PPPOE') {
            Mikrotik::removePpoeUser($client, $bill['username']);
            Mikrotik::removePpoeActive($client, $bill['username']);
        }
        $bill->status = 'off';
        $bill->expiration = date('Y-m-d');
        $bill->time = date('H:i:s');
        $bill->save();
        _log('User ' . $bill['username'] . ' Deactivate '.$bill['namebp'], 'User', $bill['customer_id']);
        Message::sendTelegram('User u' . $bill['username'] . ' Deactivate '.$bill['namebp']);
        r2(U . 'home', 's', 'Success deactivate '.$bill['namebp']);
    }else{
        r2(U . 'home', 'e', 'No Active Plan');
    }
}

if (!empty($_SESSION['nux-mac']) && !empty($_SESSION['nux-ip'])) {
    $ui->assign('nux_mac', $_SESSION['nux-mac']);
    $ui->assign('nux_ip', $_SESSION['nux-ip']);
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
