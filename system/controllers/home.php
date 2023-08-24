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
    if ($config['allow_balance_transfer'] == 'yes') {
        $target = ORM::for_table('tbl_customers')->where('username', _post('username'))->find_one();
        if (!$target) {
            r2(U . 'home', 'd', Lang::T('Username not found'));
        }
        $username = _post('username');
        $balance = _post('balance');
        if ($user['balance'] < $balance) {
            r2(U . 'home', 'd', Lang::T('insufficient balance'));
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
            Message::sendBalanceNotification($user['phonenumber'], $target['fullname'] . ' (' . $target['username'] . ')', $balance, Lang::getNotifText('balance_send'), $config['user_notification_reminder']);
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
            Message::sendBalanceNotification($target['phonenumber'], $user['fullname'] . ' (' . $user['username'] . ')', $balance, Lang::getNotifText('balance_received'), $config['user_notification_reminder']);
            Message::sendTelegram("#u$user[username] send balance to #u$target[username] \n" . Lang::moneyFormat($balance));
            r2(U . 'home', 's', Lang::T('Sending balance success'));
        }
    } else {
        r2(U . 'home', 'd', 'Failed, balance is not available');
    }
}

//Client Page
$bill = User::_billing();
$ui->assign('_bill', $bill);

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
