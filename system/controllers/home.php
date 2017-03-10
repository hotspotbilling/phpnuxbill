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
$ui->assign('_title', $_L['Dashboard'].' - '. $config['CompanyName']);

$user = User::_info();
$ui->assign('_user', $user);

//Client Page
$bill = User::_billing();
$ui->assign('_bill', $bill);

$ui->display('user-dashboard.tpl');