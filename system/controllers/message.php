<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
**/
_admin();
$ui->assign('_title', $_L['Private_Message'].'- '. $config['CompanyName']);
$ui->assign('_system_menu', 'message');

$action = $routes['1'];
$admin = Admin::_info();
$ui->assign('_admin', $admin);

switch ($action) {
    default:
         $ui->display('a404.tpl');
}