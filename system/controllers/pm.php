<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)


* @copyright	Copyright (C) 2014-2015 PHP Mikrotik Billing
* @license		GNU General Public License version 2 or later; see LICENSE.txt

**/
_auth();
$ui->assign('_title', $_L['Private_Message'].'- '. $config['CompanyName']);
$ui->assign('_system_menu', 'pm');

$action = $routes['1'];
$user = User::_info();
$ui->assign('_user', $user);

switch ($action) {
    default:
         $ui->display('404.tpl');
}