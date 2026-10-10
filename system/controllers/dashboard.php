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
    r2(getUrl('dashboard'), 's', 'Data Refreshed');
}


$tipeUser = _req("user");
if (empty($tipeUser)) {
    $tipeUser = 'Admin';
}
$ui->assign('tipeUser', $tipeUser);

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
$ui->assign('start_date', $start_date);
$ui->assign('current_date', $current_date);


if ($config['dashboard_type'] === 'dynamic') {
    $today = date('Y-m-d');
    $thisMonthStart = date('Y-m-01');
    $lastMonthStart = date('Y-m-01', strtotime('-1 month', strtotime($thisMonthStart)));
    $lastMonthEnd = date('Y-m-t', strtotime('-1 month', strtotime($thisMonthStart)));

    // 1. Total Customers
    $c_all = (int) ORM::for_table('tbl_customers')->count();
    $c_this_month = (int) ORM::for_table('tbl_customers')->where_gte('created_at', $thisMonthStart . ' 00:00:00')->count();
    $c_last_month = (int) ORM::for_table('tbl_customers')->where_gte('created_at', $lastMonthStart . ' 00:00:00')->where_lte('created_at', $lastMonthEnd . ' 23:59:59')->count();
    $c_percentage_change = $c_last_month > 0 ? round((($c_this_month - $c_last_month) / $c_last_month) * 100, 1) : ($c_this_month > 0 ? 100 : 0);

    // 2. Active Connections (User Recharges)
    $u_act = (int) ORM::for_table('tbl_user_recharges')->where('status', 'on')->count();
    $u_all = (int) ORM::for_table('tbl_user_recharges')->count();
    $u_act_pct = $u_all > 0 ? round(($u_act / $u_all) * 100, 1) : 0;
    $u_expired = max(0, $u_all - $u_act);

    // 3. Monthly Revenue
    $imonth = (float) (ORM::for_table('tbl_transactions')
        ->where_not_equal('method', 'Customer - Balance')
        ->where_not_equal('method', 'Recharge Balance - Administrator')
        ->where_gte('recharged_on', $thisMonthStart)
        ->where_lte('recharged_on', $today)
        ->sum('price') ?: 0);
    $imonth_last = (float) (ORM::for_table('tbl_transactions')
        ->where_not_equal('method', 'Customer - Balance')
        ->where_not_equal('method', 'Recharge Balance - Administrator')
        ->where_gte('recharged_on', $lastMonthStart)
        ->where_lte('recharged_on', $lastMonthEnd)
        ->sum('price') ?: 0);
    $rev_percentage_change = $imonth_last > 0 ? round((($imonth - $imonth_last) / $imonth_last) * 100, 1) : ($imonth > 0 ? 100 : 0);

    // 4. Today Revenue & Transactions Count
    $iday = (float) (ORM::for_table('tbl_transactions')
        ->where('recharged_on', $today)
        ->where_not_equal('method', 'Customer - Balance')
        ->where_not_equal('method', 'Recharge Balance - Administrator')
        ->sum('price') ?: 0);
    $iday_count = (int) ORM::for_table('tbl_transactions')
        ->where('recharged_on', $today)
        ->count();

    // 5. Routers Status
    $routers_total = (int) ORM::for_table('tbl_routers')->count();
    $routers_online = (int) ORM::for_table('tbl_routers')->where('status', 'Online')->count();
    $routers_offline = max(0, $routers_total - $routers_online);
    $network_uptime = $routers_total > 0 ? round(($routers_online / $routers_total) * 100, 1) : 100;

    // Charts: Sales Report Data (Day, Week, Month, Year)
    $daysMap = [];
    for ($i = 29; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $daysMap[$d] = ['label' => date('M d', strtotime($d)), 'value' => 0.0, 'count' => 0];
    }
    $dayTrx = ORM::for_table('tbl_transactions')
        ->select('recharged_on')
        ->select_expr('SUM(price)', 'total')
        ->select_expr('COUNT(*)', 'count')
        ->where_gte('recharged_on', date('Y-m-d', strtotime('-29 days')))
        ->where_not_equal('method', 'Customer - Balance')
        ->where_not_equal('method', 'Recharge Balance - Administrator')
        ->group_by('recharged_on')
        ->find_array();
    foreach ($dayTrx as $r) {
        if (isset($daysMap[$r['recharged_on']])) {
            $daysMap[$r['recharged_on']]['value'] = (float)$r['total'];
            $daysMap[$r['recharged_on']]['count'] = (int)$r['count'];
        }
    }

    $weeksMap = [];
    for ($i = 11; $i >= 0; $i--) {
        $wTime = strtotime("-$i weeks");
        $wKey = date('o-W', $wTime);
        $weeksMap[$wKey] = ['label' => 'Wk ' . date('W', $wTime), 'value' => 0.0, 'count' => 0];
    }
    $weekTrx = ORM::for_table('tbl_transactions')
        ->select_expr("DATE_FORMAT(recharged_on, '%x-%v')", 'week_key')
        ->select_expr('SUM(price)', 'total')
        ->select_expr('COUNT(*)', 'count')
        ->where_gte('recharged_on', date('Y-m-d', strtotime('-12 weeks')))
        ->where_not_equal('method', 'Customer - Balance')
        ->where_not_equal('method', 'Recharge Balance - Administrator')
        ->group_by_expr("DATE_FORMAT(recharged_on, '%x-%v')")
        ->find_array();
    foreach ($weekTrx as $r) {
        if (isset($weeksMap[$r['week_key']])) {
            $weeksMap[$r['week_key']]['value'] = (float)$r['total'];
            $weeksMap[$r['week_key']]['count'] = (int)$r['count'];
        }
    }

    $monthsMap = [];
    for ($i = 11; $i >= 0; $i--) {
        $mKey = date('Y-m', strtotime("-$i months", strtotime($thisMonthStart)));
        $monthsMap[$mKey] = ['label' => date('M Y', strtotime($mKey . '-01')), 'value' => 0.0, 'count' => 0, 'new_customers' => 0, 'churned' => 0];
    }
    $monthTrx = ORM::for_table('tbl_transactions')
        ->select_expr("DATE_FORMAT(recharged_on, '%Y-%m')", 'month_key')
        ->select_expr('SUM(price)', 'total')
        ->select_expr('COUNT(*)', 'count')
        ->where_gte('recharged_on', date('Y-m-01', strtotime('-11 months', strtotime($thisMonthStart))))
        ->where_not_equal('method', 'Customer - Balance')
        ->where_not_equal('method', 'Recharge Balance - Administrator')
        ->group_by_expr("DATE_FORMAT(recharged_on, '%Y-%m')")
        ->find_array();
    foreach ($monthTrx as $r) {
        if (isset($monthsMap[$r['month_key']])) {
            $monthsMap[$r['month_key']]['value'] = (float)$r['total'];
            $monthsMap[$r['month_key']]['count'] = (int)$r['count'];
        }
    }

    $yearsMap = [];
    $curYear = (int)date('Y');
    for ($y = $curYear - 4; $y <= $curYear; $y++) {
        $yearsMap[$y] = ['label' => (string)$y, 'value' => 0.0, 'count' => 0];
    }
    $yearTrx = ORM::for_table('tbl_transactions')
        ->select_expr('YEAR(recharged_on)', 'year_key')
        ->select_expr('SUM(price)', 'total')
        ->select_expr('COUNT(*)', 'count')
        ->where_gte('recharged_on', ($curYear - 4) . '-01-01')
        ->where_not_equal('method', 'Customer - Balance')
        ->where_not_equal('method', 'Recharge Balance - Administrator')
        ->group_by_expr('YEAR(recharged_on)')
        ->find_array();
    foreach ($yearTrx as $r) {
        if (isset($yearsMap[$r['year_key']])) {
            $yearsMap[$r['year_key']]['value'] = (float)$r['total'];
            $yearsMap[$r['year_key']]['count'] = (int)$r['count'];
        }
    }

    // Customer Growth Chart Data
    $custMonths = ORM::for_table('tbl_customers')
        ->select_expr("DATE_FORMAT(created_at, '%Y-%m')", 'month_key')
        ->select_expr('COUNT(*)', 'count')
        ->where_gte('created_at', date('Y-m-01 00:00:00', strtotime('-11 months', strtotime($thisMonthStart))))
        ->group_by_expr("DATE_FORMAT(created_at, '%Y-%m')")
        ->find_array();
    foreach ($custMonths as $c) {
        if (isset($monthsMap[$c['month_key']])) {
            $monthsMap[$c['month_key']]['new_customers'] = (int)$c['count'];
        }
    }

    $expMonths = ORM::for_table('tbl_user_recharges')
        ->select_expr("DATE_FORMAT(expiration, '%Y-%m')", 'month_key')
        ->select_expr('COUNT(*)', 'count')
        ->where('status', 'off')
        ->where_gte('expiration', date('Y-m-01', strtotime('-11 months', strtotime($thisMonthStart))))
        ->group_by_expr("DATE_FORMAT(expiration, '%Y-%m')")
        ->find_array();
    foreach ($expMonths as $e) {
        if (isset($monthsMap[$e['month_key']])) {
            $monthsMap[$e['month_key']]['churned'] = (int)$e['count'];
        }
    }

    // Transactions by Service / Type (last 90 days)
    $trxTypes = ORM::for_table('tbl_transactions')
        ->select('type')
        ->select_expr('COUNT(*)', 'count')
        ->select_expr('SUM(price)', 'total')
        ->where_gte('recharged_on', date('Y-m-d', strtotime('-90 days')))
        ->group_by('type')
        ->find_array();
    $trxLabels = [];
    $trxCounts = [];
    $trxTotals = [];
    foreach ($trxTypes as $t) {
        $trxLabels[] = !empty($t['type']) ? $t['type'] : 'Other';
        $trxCounts[] = (int)$t['count'];
        $trxTotals[] = (float)$t['total'];
    }
    if (empty($trxLabels)) {
        $trxLabels = ['Hotspot', 'PPPoE'];
        $trxCounts = [0, 0];
        $trxTotals = [0, 0];
    }

    // Router / Hotspot Usage (last 90 days)
    $routerUsage = ORM::for_table('tbl_transactions')
        ->select('routers')
        ->select_expr('COUNT(*)', 'count')
        ->select_expr('SUM(price)', 'total')
        ->where_gte('recharged_on', date('Y-m-d', strtotime('-90 days')))
        ->group_by('routers')
        ->order_by_desc('count')
        ->limit(6)
        ->find_array();
    $ruLabels = [];
    $ruCounts = [];
    foreach ($routerUsage as $ru) {
        $ruLabels[] = !empty($ru['routers']) ? $ru['routers'] : 'Default';
        $ruCounts[] = (int)$ru['count'];
    }
    if (empty($ruLabels)) {
        $ruLabels = ['Default'];
        $ruCounts = [0];
    }

    // Day of Week Activity (last 90 days)
    $dowNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    $dowCounts = array_fill(0, 7, 0);
    $dowTrx = ORM::for_table('tbl_transactions')
        ->select_expr('DAYOFWEEK(recharged_on)', 'dow')
        ->select_expr('COUNT(*)', 'count')
        ->where_gte('recharged_on', date('Y-m-d', strtotime('-90 days')))
        ->group_by_expr('DAYOFWEEK(recharged_on)')
        ->find_array();
    foreach ($dowTrx as $d) {
        $idx = (int)$d['dow'] - 1;
        if ($idx >= 0 && $idx < 7) {
            $dowCounts[$idx] = (int)$d['count'];
        }
    }

    // Plans Distribution by Type
    $planTypes = ORM::for_table('tbl_plans')
        ->select('type')
        ->select_expr('COUNT(*)', 'count')
        ->group_by('type')
        ->find_array();
    $ptLabels = [];
    $ptCounts = [];
    foreach ($planTypes as $pt) {
        $ptLabels[] = !empty($pt['type']) ? $pt['type'] : 'Other';
        $ptCounts[] = (int)$pt['count'];
    }
    if (empty($ptLabels)) {
        $ptLabels = ['Hotspot', 'PPPoE', 'VPN'];
        $ptCounts = [0, 0, 0];
    }

    // Top Selling Plans (last 90 days)
    $topPlans = ORM::for_table('tbl_transactions')
        ->select('plan_name')
        ->select_expr('COUNT(*)', 'count')
        ->select_expr('SUM(price)', 'total')
        ->where_gte('recharged_on', date('Y-m-d', strtotime('-90 days')))
        ->group_by('plan_name')
        ->order_by_desc('count')
        ->limit(5)
        ->find_array();
    $tpLabels = [];
    $tpCounts = [];
    $tpTotals = [];
    foreach ($topPlans as $tp) {
        $tpLabels[] = !empty($tp['plan_name']) ? $tp['plan_name'] : 'Unnamed';
        $tpCounts[] = (int)$tp['count'];
        $tpTotals[] = (float)$tp['total'];
    }
    if (empty($tpLabels)) {
        $tpLabels = ['None'];
        $tpCounts = [0];
        $tpTotals = [0];
    }

    // Hourly Activity (last 30 days)
    $hoursLabels = [];
    $hoursCounts = array_fill(0, 24, 0);
    for ($h = 0; $h < 24; $h++) {
        $hoursLabels[] = sprintf('%02d:00', $h);
    }
    $hourTrx = ORM::for_table('tbl_transactions')
        ->select_expr("HOUR(COALESCE(time, recharged_time, '00:00:00'))", 'hr')
        ->select_expr('COUNT(*)', 'count')
        ->where_gte('recharged_on', date('Y-m-d', strtotime('-30 days')))
        ->group_by_expr("HOUR(COALESCE(time, recharged_time, '00:00:00'))")
        ->find_array();
    $peakCount = 0;
    $peakHourIdx = 12;
    foreach ($hourTrx as $h) {
        $hr = (int)$h['hr'];
        if ($hr >= 0 && $hr < 24) {
            $cnt = (int)$h['count'];
            $hoursCounts[$hr] = $cnt;
            if ($cnt > $peakCount) {
                $peakCount = $cnt;
                $peakHourIdx = $hr;
            }
        }
    }
    $peak_hour_str = date('g:i A', strtotime("$peakHourIdx:00"));

    // Peak Usage (3-hour blocks)
    $blockLabels = ['12AM', '3AM', '6AM', '9AM', '12PM', '3PM', '6PM', '9PM'];
    $blockCounts = [0, 0, 0, 0, 0, 0, 0, 0];
    for ($h = 0; $h < 24; $h++) {
        $bIdx = (int)floor($h / 3);
        $blockCounts[$bIdx] += $hoursCounts[$h];
    }

    // Equipment Status
    $equipmentData = [
        'labels' => ['Routers', 'Plans', 'Pools', 'Bandwidths', 'Vouchers', 'Customers'],
        'values' => [
            (int)ORM::for_table('tbl_routers')->count(),
            (int)ORM::for_table('tbl_plans')->count(),
            (int)ORM::for_table('tbl_pool')->count(),
            (int)ORM::for_table('tbl_bandwidth')->count(),
            (int)ORM::for_table('tbl_voucher')->count(),
            (int)ORM::for_table('tbl_customers')->count()
        ]
    ];

    $allDashboardData = [
        'sales' => [
            'day' => [
                'labels' => array_column(array_values($daysMap), 'label'),
                'values' => array_column(array_values($daysMap), 'value'),
                'counts' => array_column(array_values($daysMap), 'count')
            ],
            'week' => [
                'labels' => array_column(array_values($weeksMap), 'label'),
                'values' => array_column(array_values($weeksMap), 'value'),
                'counts' => array_column(array_values($weeksMap), 'count')
            ],
            'month' => [
                'labels' => array_column(array_values($monthsMap), 'label'),
                'values' => array_column(array_values($monthsMap), 'value'),
                'counts' => array_column(array_values($monthsMap), 'count')
            ],
            'year' => [
                'labels' => array_column(array_values($yearsMap), 'label'),
                'values' => array_column(array_values($yearsMap), 'value'),
                'counts' => array_column(array_values($yearsMap), 'count')
            ]
        ],
        'customerGrowth' => [
            'labels' => array_column(array_values($monthsMap), 'label'),
            'new' => array_column(array_values($monthsMap), 'new_customers'),
            'churned' => array_column(array_values($monthsMap), 'churned')
        ],
        'network' => [
            'labels' => ['Online', 'Offline'],
            'values' => [$routers_online, $routers_offline],
            'uptime' => $network_uptime
        ],
        'transactions' => [
            'labels' => $trxLabels,
            'counts' => $trxCounts,
            'totals' => $trxTotals
        ],
        'routerUsage' => [
            'labels' => $ruLabels,
            'values' => $ruCounts
        ],
        'portalLogins' => [
            'labels' => $dowNames,
            'values' => $dowCounts
        ],
        'planTypes' => [
            'labels' => $ptLabels,
            'values' => $ptCounts
        ],
        'topPlans' => [
            'labels' => $tpLabels,
            'counts' => $tpCounts,
            'totals' => $tpTotals
        ],
        'hourly' => [
            'labels' => $hoursLabels,
            'values' => $hoursCounts,
            'peak_hour' => $peak_hour_str
        ],
        'peakUsage' => [
            'labels' => $blockLabels,
            'values' => $blockCounts
        ],
        'equipment' => $equipmentData
    ];

    $ui->assign('c_all', $c_all);
    $ui->assign('c_percentage_change', $c_percentage_change);
    $ui->assign('u_act', $u_act);
    $ui->assign('u_all', $u_all);
    $ui->assign('u_act_pct', $u_act_pct);
    $ui->assign('u_expired', $u_expired);
    $ui->assign('imonth', $imonth);
    $ui->assign('imonth_formatted', number_format($imonth, 2));
    $ui->assign('rev_percentage_change', $rev_percentage_change);
    $ui->assign('iday', $iday);
    $ui->assign('iday_formatted', number_format($iday, 2));
    $ui->assign('iday_count', $iday_count);
    $ui->assign('routers_total', $routers_total);
    $ui->assign('routers_online', $routers_online);
    $ui->assign('routers_offline', $routers_offline);
    $ui->assign('network_uptime', $network_uptime);
    $ui->assign('peak_hour_str', $peak_hour_str);
    $ui->assign('dashboard_data_json', json_encode($allDashboardData));

    $ui->display('admin/dashboard-new.tpl');
} else {
    $tipeUser = $admin['user_type'];
    if (in_array($tipeUser, ['SuperAdmin', 'Admin'])) {
        $tipeUser = 'Admin';
    }

    $widgets = ORM::for_table('tbl_widgets')->where("enabled", 1)->where('user', $tipeUser)->order_by_asc("orders")->findArray();
    $count = count($widgets);
    for ($i = 0; $i < $count; $i++) {
        try {
            if (file_exists($WIDGET_PATH . DIRECTORY_SEPARATOR . $widgets[$i]['widget'] . ".php")) {
                require_once $WIDGET_PATH . DIRECTORY_SEPARATOR . $widgets[$i]['widget'] . ".php";
                $widgets[$i]['content'] = (new $widgets[$i]['widget'])->getWidget($widgets[$i]);
            } else {
                $widgets[$i]['content'] = "Widget not found";
            }
        } catch (Throwable $e) {
            $widgets[$i]['content'] = $e->getMessage();
        }
    }

    $ui->assign('widgets', $widgets);
    run_hook('view_dashboard'); #HOOK
    $ui->display('admin/dashboard.tpl');
}
