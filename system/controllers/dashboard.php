<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Dashboard'));
$ui->assign('_admin', $admin);

if (isset($_GET['refresh'])) {
    $files = scandir($CACHE_PATH);
    foreach ($files as $file) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if (is_file($CACHE_PATH . DIRECTORY_SEPARATOR . $file) && $ext == 'temp') {
            unlink($CACHE_PATH . DIRECTORY_SEPARATOR . $file);
        }
    }
    r2(U . 'dashboard', 's', 'Data Refreshed');
}

$reset_day = $config['reset_day'];
if (empty($reset_day)) {
    $reset_day = 1;
}
//first day of month
if (date("d") >= $reset_day) {
    $start_date = date('Y-m-' . $reset_day);
} else {
    $start_date = date('Y-m-' . $reset_day, strtotime("-1 MONTH"));
}

$current_date = date('Y-m-d');
$month_n = date('n');

$iday = ORM::for_table('tbl_transactions')
    ->where('recharged_on', $current_date)
    ->where_not_equal('method', 'Customer - Balance')
    ->where_not_equal('method', 'Recharge Balance - Administrator')
    ->sum('price');

if ($iday == '') {
    $iday = '0.00';
}
$ui->assign('iday', $iday);

$imonth = ORM::for_table('tbl_transactions')
    ->where_not_equal('method', 'Customer - Balance')
    ->where_not_equal('method', 'Recharge Balance - Administrator')
    ->where_gte('recharged_on', $start_date)
    ->where_lte('recharged_on', $current_date)->sum('price');
if ($imonth == '') {
    $imonth = '0.00';
}
$ui->assign('imonth', $imonth);

if ($config['enable_balance'] == 'yes'){
    $cb = ORM::for_table('tbl_customers')->whereGte('balance', 0)->sum('balance');
    $ui->assign('cb', $cb);
}

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

if ($config['hide_uet'] != 'yes') {
    //user expire
    $query = ORM::for_table('tbl_user_recharges')
        ->where_lte('expiration', $current_date)
        ->order_by_desc('expiration');
    $expire = Paginator::findMany($query);

    // Get the total count of expired records for pagination
    $totalCount = ORM::for_table('tbl_user_recharges')
        ->where_lte('expiration', $current_date)
        ->count();

    // Pass the total count and current page to the paginator
    $paginator['total_count'] = $totalCount;

    // Assign the pagination HTML to the template variable
    $ui->assign('expire', $expire);
}

//activity log
$dlog = ORM::for_table('tbl_logs')->limit(5)->order_by_desc('id')->find_many();
$ui->assign('dlog', $dlog);
$log = ORM::for_table('tbl_logs')->count();
$ui->assign('log', $log);


if ($config['hide_vs'] != 'yes') {
    $cacheStocksfile = $CACHE_PATH . File::pathFixer('/VoucherStocks.temp');
    $cachePlanfile = $CACHE_PATH . File::pathFixer('/VoucherPlans.temp');
    //Cache for 5 minutes
    if (file_exists($cacheStocksfile) && time() - filemtime($cacheStocksfile) < 600) {
        $stocks = json_decode(file_get_contents($cacheStocksfile), true);
        $plans = json_decode(file_get_contents($cachePlanfile), true);
    } else {
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
            if ($unused > 0 || $used > 0) {
                $plans[$n]['name_plan'] = $plan['name_plan'];
                $plans[$n]['unused'] = $unused;
                $plans[$n]['used'] = $used;
                $stocks["unused"] += $unused;
                $stocks["used"] += $used;
                $n++;
            }
        }
        file_put_contents($cacheStocksfile, json_encode($stocks));
        file_put_contents($cachePlanfile, json_encode($plans));
    }
}

$cacheMRfile = File::pathFixer('/monthlyRegistered.temp');
//Cache for 1 hour
if (file_exists($cacheMRfile) && time() - filemtime($cacheMRfile) < 3600) {
    $monthlyRegistered = json_decode(file_get_contents($cacheMRfile), true);
} else {
    //Monthly Registered Customers
    $result = ORM::for_table('tbl_customers')
        ->select_expr('MONTH(created_at)', 'month')
        ->select_expr('COUNT(*)', 'count')
        ->where_raw('YEAR(created_at) = YEAR(NOW())')
        ->group_by_expr('MONTH(created_at)')
        ->find_many();

    $monthlyRegistered = [];
    foreach ($result as $row) {
        $monthlyRegistered[] = [
            'date' => $row->month,
            'count' => $row->count
        ];
    }
    file_put_contents($cacheMRfile, json_encode($monthlyRegistered));
}

$cacheMSfile = $CACHE_PATH . File::pathFixer('/monthlySales.temp');
//Cache for 12 hours
if (file_exists($cacheMSfile) && time() - filemtime($cacheMSfile) < 43200) {
    $monthlySales = json_decode(file_get_contents($cacheMSfile), true);
} else {
    // Query to retrieve monthly data
    $results = ORM::for_table('tbl_transactions')
        ->select_expr('MONTH(recharged_on)', 'month')
        ->select_expr('SUM(price)', 'total')
        ->where_raw("YEAR(recharged_on) = YEAR(CURRENT_DATE())") // Filter by the current year
        ->where_not_equal('method', 'Customer - Balance')
        ->where_not_equal('method', 'Recharge Balance - Administrator')
        ->group_by_expr('MONTH(recharged_on)')
        ->find_many();

    // Create an array to hold the monthly sales data
    $monthlySales = array();

    // Iterate over the results and populate the array
    foreach ($results as $result) {
        $month = $result->month;
        $totalSales = $result->total;

        $monthlySales[$month] = array(
            'month' => $month,
            'totalSales' => $totalSales
        );
    }

    // Fill in missing months with zero sales
    for ($month = 1; $month <= 12; $month++) {
        if (!isset($monthlySales[$month])) {
            $monthlySales[$month] = array(
                'month' => $month,
                'totalSales' => 0
            );
        }
    }

    // Sort the array by month
    ksort($monthlySales);

    // Reindex the array
    $monthlySales = array_values($monthlySales);
    file_put_contents($cacheMSfile, json_encode($monthlySales));
}

// Assign the monthly sales data to Smarty
$ui->assign('start_date', $start_date);
$ui->assign('current_date', $current_date);
$ui->assign('monthlySales', $monthlySales);
$ui->assign('xfooter', '');
$ui->assign('monthlyRegistered', $monthlyRegistered);
$ui->assign('stocks', $stocks);
$ui->assign('plans', $plans);

run_hook('view_dashboard'); #HOOK
$ui->display('dashboard.tpl');
