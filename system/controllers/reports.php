<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)


* @copyright	Copyright (C) 2014-2015 PHP Mikrotik Billing
* @license		GNU General Public License version 2 or later; see LICENSE.txt

**/
_admin();
$ui->assign('_title', $_L['Reports'].' - '. $config['CompanyName']);
$ui->assign('_system_menu', 'reports');

$action = $routes['1'];
$admin = Admin::_info();
$ui->assign('_admin', $admin);

if($admin['user_type'] != 'Admin' AND $admin['user_type'] != 'Sales'){
	r2(U."dashboard",'e',$_L['Do_Not_Access']);
}

$mdate = date('Y-m-d');
$mtime = date('H:i:s');
$tdate = date('Y-m-d', strtotime('today - 30 days'));
$firs_day_month = date('Y-m-01');
$this_week_start = date('Y-m-d', strtotime('previous sunday'));
$before_30_days = date('Y-m-d', strtotime('today - 30 days'));
$month_n = date('n');

switch ($action) {
    case 'daily-report':
		$paginator = Paginator::bootstrap('tbl_transactions','recharged_on',$mdate);
        $d = ORM::for_table('tbl_transactions')->where('recharged_on',$mdate)->offset($paginator['startpoint'])->limit($paginator['limit'])->order_by_desc('id')->find_many();
		$dr = ORM::for_table('tbl_transactions')->where('recharged_on',$mdate)->sum('price');
		
        $ui->assign('d',$d);
		$ui->assign('dr',$dr);
		$ui->assign('mdate',$mdate);
		$ui->assign('mtime',$mtime);
		$ui->assign('paginator',$paginator);
		
        $ui->display('reports-daily.tpl');
        break;
		
    case 'by-period':
		$ui->assign('mdate',$mdate);
		$ui->assign('mtime',$mtime);
		$ui->assign('tdate', $tdate);
		
        $ui->display('reports-period.tpl');
        break;
		
    case 'period-view':
        $fdate = _post('fdate');
        $tdate = _post('tdate');
        $stype = _post('stype');
		
        $d = ORM::for_table('tbl_transactions');
		if ($stype != ''){
				$d->where('type', $stype);
		}
        
        $d->where_gte('recharged_on', $fdate);
        $d->where_lte('recharged_on', $tdate);
        $d->order_by_desc('id');
        $x =  $d->find_many();
		
		$dr = ORM::for_table('tbl_transactions');
		if ($stype != ''){
				$dr->where('type', $stype);
		}
        
        $dr->where_gte('recharged_on', $fdate);
        $dr->where_lte('recharged_on', $tdate);
		$xy = $dr->sum('price');
        
		$ui->assign('d',$x);
		$ui->assign('dr',$xy);
        $ui->assign('fdate',$fdate);
        $ui->assign('tdate',$tdate);
        $ui->assign('stype',$stype);

        $ui->display('reports-period-view.tpl');
        break;
		
    default:
        echo 'action not defined';
}