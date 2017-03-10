<?php
/**
* PHP Mikrotik Billing (www.phpmixbill.com)
* Ismail Marzuqi <iesien22@yahoo.com>
* @version		5.0
* @copyright	Copyright (C) 2014-2015 PHP Mikrotik Billing
* @license		GNU General Public License version 2 or later; see LICENSE.txt
* @donate		PayPal: iesien22@yahoo.com / Bank Mandiri: 130.00.1024957.4
**/
_auth();
$ui->assign('_title', $_L['Order_Voucher'].'- '. $config['CompanyName']);
$ui->assign('_system_menu', 'order');

$user = User::_info();
$ui->assign('_user', $user);

switch ($action) {
    default:
         $ui->display('404.tpl');
}