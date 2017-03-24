<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
* Ibnu Maksum <me@ibnux.net>
* @copyright	Copyright (C) 2014-2015 PHP Mikrotik Billing
* @license		GNU General Public License version 2 or later; see LICENSE.txt
* @donate		PayPal: me@ibnux.net
**/
_admin();
$ui->assign('_title', 'Disquss - '. $config['CompanyName']);
$ui->assign('_system_menu', 'disquss');

$admin = Admin::_info();
$ui->assign('_admin', $admin);

if($admin['user_type'] != 'Admin' AND $admin['user_type'] != 'Sales'){
	r2(U."dashboard",'e',$_L['Do_Not_Access']);
}

switch ($action) {
    default:
         $ui->display('disquss.tpl');
}