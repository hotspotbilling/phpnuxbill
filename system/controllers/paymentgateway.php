<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
**/
_admin();
$ui->assign('_system_menu', 'paymentgateway');

$action = $routes['1'];
$admin = Admin::_info();
$ui->assign('_admin', $admin);

switch ($action) {
    case 'xendit':
        $ui->assign('_title', 'Xendit - Payment Gateway - '. $config['CompanyName']);
        $ui->display('a404.tpl');
        break;
    case 'midtrans':
        $ui->assign('_title', 'Midtrans - Payment Gateway - '. $config['CompanyName']);

        $ui->display('a404.tpl');
        break;
}
