<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 **/
_auth();
$ui->assign('_title', $_L['Dashboard']);

$user = User::_info();
$ui->assign('_user', $user);

//Client Page
$bill = User::_billing();
$ui->assign('_bill', $bill);


$ui->assign('unpaid', ORM::for_table('tbl_payment_gateway')
    ->where('username', $user['username'])
    ->where('status', 1)
    ->find_one());
run_hook('view_customer_dashboard'); #HOOK
$ui->display('user-dashboard.tpl');
