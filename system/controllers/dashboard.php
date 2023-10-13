<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', $_L['Dashboard']);
$admin = Admin::_info();
$ui->assign('_admin', $admin);
if (!in_array($admin['user_type'], ['Admin', 'Sales'])) {
    r2(U . "home", 'e', $_L['Do_Not_Access']);
}

$fdate = date('Y-m-01');
$tdate = date('Y-m-t');
//first day of month
$first_day_month = date('Y-m-01');
$mdate = date('Y-m-d');
$month_n = date('n');

$iday = ORM::for_table('tbl_transactions')->where('recharged_on', $mdate)->sum('price');
if ($iday == '') {
    $iday = '0.00';
}
$ui->assign('iday', $iday);

$imonth = ORM::for_table('tbl_transactions')->where_gte('recharged_on', $first_day_month)->where_lte('recharged_on', $mdate)->sum('price');
if ($imonth == '') {
    $imonth = '0.00';
}
$ui->assign('imonth', $imonth);

$u_act = ORM::for_table('tbl_user_recharges')->where('status', 'on')->count();
if (empty($u_act)) {
    $u_act = '0';
}
$ui->assign('u_act', $u_act);

$u_all = ORM::for_table('tbl_user_recharges')->count();
if (empty($u_all)) {
    $u_all = '0';
}
$ui->assign('u_all', $u_all);


$c_all = ORM::for_table('tbl_customers')->count();
if (empty($c_all)) {
    $c_all = '0';
}
$ui->assign('c_all', $c_all);

//user expire
$expire = ORM::for_table('tbl_user_recharges')->whereLte('expiration', $mdate)->order_by_desc('id')->limit(30)->find_many();
$ui->assign('expire', $expire);

//activity log
$dlog = ORM::for_table('tbl_logs')->limit(5)->order_by_desc('id')->find_many();
$ui->assign('dlog', $dlog);
$log = ORM::for_table('tbl_logs')->count();
$ui->assign('log', $log);

// Count stock
$tmp = $v = ORM::for_table('tbl_plans')->select('id')->select('name_plan')->find_many();
$plans = array();
$stocks = array("used" => 0, "unused" => 0);
$n = 0;
foreach ($tmp as $plan) {
    $unused = ORM::for_table('tbl_voucher')
        ->where('id_plan', $plan['id'])
        ->where('status', 0)->count();
    $used = ORM::for_table('tbl_voucher')
        ->where('id_plan', $plan['id'])
        ->where('status', 1)->count();
    if($unused>0 || $used>0){
        $plans[$n]['name_plan'] = $plan['name_plan'];
        $plans[$n]['unused'] = $unused;
        $plans[$n]['used'] = $used;
        $stocks["unused"] += $unused;
        $stocks["used"] += $used;
        $n++;
    }
}

$ui->assign('stocks', $stocks);
$ui->assign('plans', $plans);

run_hook('view_dashboard'); #HOOK
$ui->display('dashboard.tpl');
