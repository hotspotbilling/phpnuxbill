<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
**/
_auth();
$ui->assign('_title', $_L['Dashboard'].' - '. $config['CompanyName']);

$user = User::_info();
$ui->assign('_user', $user);

//Client Page
$bill = User::_billing();
$ui->assign('_bill', $bill);

$ui->display('user-dashboard.tpl');