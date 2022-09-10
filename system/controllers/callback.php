<?php

/**
 * PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
 **/
_auth();
$ui->assign('_system_menu', 'order');
$action = $routes['1'];
$user = User::_info();
$ui->assign('_user', $user);


require('system/autoload/Paymentgateway.php');
require('system/autoload/Recharge.php');

switch ($action) {
    case 'xendit':
        echo "done";
        break;
    case 'midtrans':
        echo "done";
        break;
    case 'tripay':
        echo '{"success": true}';
        break;
    default:
        echo "not found";
}