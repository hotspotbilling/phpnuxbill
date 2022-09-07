<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
**/
_auth();
$ui->assign('_title', 'Community - '. $config['CompanyName']);
$ui->assign('_system_menu', 'community');

$action = $routes['1'];
$admin = Admin::_info();
$ui->assign('_admin', $admin);

$ui->display('community.tpl');