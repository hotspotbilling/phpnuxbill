<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 **/
_auth();
$ui->assign('_title', $_L['Dashboard']);

$user = User::_info();
$ui->assign('_user', $user);

if(isset($_GET['renewal'])){
    $user->auto_renewal = $_GET['renewal'];
    $user->save();
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
    }else if ($_GET['mikrotik'] == 'logout') {
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
