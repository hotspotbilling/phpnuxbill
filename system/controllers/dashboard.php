<?php
/**
* PHP Mikrotik Billing (www.phpmixbill.com)
* Ismail Marzuqi <iesien22@yahoo.com>
* @version		5.0
* @copyright	Copyright (C) 2014-2015 PHP Mikrotik Billing
* @license		GNU General Public License version 2 or later; see LICENSE.txt
* @donate		PayPal: iesien22@yahoo.com / Bank Mandiri: 130.00.1024957.4
**/
_admin();
$ui->assign('_title', $_L['Dashboard'].' - '. $config['CompanyName']);
$admin = Admin::_info();
$ui->assign('_admin', $admin);

if($admin['user_type'] != 'Admin' AND $admin['user_type'] != 'Sales'){
	r2(U."home",'e',$_L['Do_Not_Access']);
}

$fdate = date('Y-m-01');
$tdate = date('Y-m-t');
//first day of month
$first_day_month = date('Y-m-01');
$mdate = date('Y-m-d');
$month_n = date('n');

$iday = ORM::for_table('tbl_transactions')->where('recharged_on',$mdate)->sum('price');
if($iday == ''){
    $iday = '0.00';
}
$ui->assign('iday',$iday);

$imonth = ORM::for_table('tbl_transactions')->where_gte('recharged_on',$first_day_month)->where_lte('recharged_on',$mdate)->sum('price');
if($imonth == ''){
    $imonth = '0.00';
}
$ui->assign('imonth',$imonth);

$u_act = ORM::for_table('tbl_user_recharges')->where('status','on')->count();
if($u_act == ''){
    $u_act = '0';
}
$ui->assign('u_act',$u_act);

$u_all = ORM::for_table('tbl_user_recharges')->count();
if($u_all == ''){
    $u_all = '0';
}
$ui->assign('u_all',$u_all);
//user expire
$expire = ORM::for_table('tbl_user_recharges')->where('expiration',$mdate)->order_by_desc('id')->find_many();
$ui->assign('expire',$expire);

//activity log
$dlog = ORM::for_table('tbl_logs')->limit(5)->order_by_desc('id')->find_many();
$ui->assign('dlog',$dlog);
$log = ORM::for_table('tbl_logs')->count();
$ui->assign('log',$log);

$ui->display('dashboard.tpl');