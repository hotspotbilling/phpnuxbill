<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Reports'));
$ui->assign('_system_menu', 'reports');

$action = $routes['1'];
$ui->assign('_admin', $admin);

$mdate = date('Y-m-d');
$mtime = date('H:i:s');
$tdate = date('Y-m-d', strtotime('today - 30 days'));
$firs_day_month = date('Y-m-01');
$this_week_start = date('Y-m-d', strtotime('previous sunday'));
$before_30_days = date('Y-m-d', strtotime('today - 30 days'));
$month_n = date('n');

switch ($action) {
    case 'ajax':
        // chart data for the Daily Report: one GROUP BY query per chart, whatever the table size
        $data = $routes['2'];
        $f = TransactionReport::filterFromRequest(TransactionReport::filterOptions(), TransactionReport::defaultStart());
        if ($data === 'summary') {
            $result = TransactionReport::summary($f);
        } elseif ($data === 'line') {
            $result = TransactionReport::line($f);
        } elseif (isset(TransactionReport::DIMENSIONS[$data])) {
            $result = TransactionReport::pie($f, $data);
        } else {
            $result = ['labels' => [], 'datas' => []];
        }
        header('Content-Type: application/json');
        echo json_encode($result);
        die();
    case 'by-date':
    case 'activation':
        $q = trim((string) (_post('q') ?: _get('q')));
        $typeCounts = TransactionReport::typeCounts();
        $type = (string) _get('type');
        // any type the column allows, also one with no transactions yet
        if (!in_array($type, ORM::for_table('tbl_transactions')->getEnum('type'), true)) {
            $type = '';
        }
        // newest first by id: the primary key (and the type index, which ends with it), so a page is read straight off an index
        $query = ORM::for_table('tbl_transactions')->order_by_desc('id');
        if ($type !== '') {
            $query->where('type', $type);
        }
        if ($q !== '') {
            // invoice numbers start with "INV-": match from the start (indexed), with or without the prefix typed
            $like = addcslashes($q, '\\%_') . '%';
            if (stripos($q, 'INV-') === 0) {
                $query->where_raw('`invoice` LIKE ?', [$like]);
            } else {
                $query->where_raw('(`invoice` LIKE ? OR `invoice` LIKE ?)', [$like, 'INV-' . $like]);
            }
            $total = (clone $query)->count(); // a prefix search is counted exactly; it reads only the matching index range
        } else {
            $total = $type !== '' ? ($typeCounts[$type] ?? 0) : array_sum($typeCounts);
            if (!$total) {
                // the counts are kept a minute: a zero may be stale, and counting nothing (or next to nothing) is cheap
                $total = (clone $query)->count();
            }
        }

        try {
            $d = Paginator::findMany($query, array_filter(['q' => $q, 'type' => $type], 'strlen'), '10', '', true, $total);
            $d = TransactionReport::withNames($d ?: []);
            // still running: expires later than now (the date and time, as saved, in the app's timezone)
            $now = date('Y-m-d H:i:s');
            foreach ($d as &$row) {
                $row['is_active'] = ($row['expiration'] . ' ' . ($row['time'] ?: '23:59:59')) >= $now;
            }
            unset($row);
        } catch (Exception $e) {
            r2(getUrl('reports/activation/'), 'e', 'Database query failed: ' . $e->getMessage());
            $d = [];
        }

        $ui->assign('activation', $d);
        $ui->assign('q', $q);
        $ui->assign('type', $type);
        $ui->assign('typeCounts', $typeCounts);
        $ui->assign('total', $total);
        $ui->assign('_title', Lang::T('Activation History'));
        $ui->display('admin/reports/activation.tpl');
        break;

    case 'by-period':
        $ui->assign('mdate', $mdate);
        $ui->assign('mtime', $mtime);
        $ui->assign('tdate', $tdate);
        run_hook('view_reports_by_period'); #HOOK
        $ui->display('admin/reports/period.tpl');
        break;

    case 'period-view':
        $fdate = _post('fdate');
        $tdate = _post('tdate');
        $stype = _post('stype');

        $d = ORM::for_table('tbl_transactions');
        $d->left_outer_join('tbl_customers', 'tbl_transactions.username = tbl_customers.username')
            ->select('tbl_transactions.*')
            ->select('tbl_customers.fullname', 'fullname')
            ->order_by_desc('tbl_transactions.id');
        if ($stype != '') {
            $d->where('type', $stype);
        }

        $d->where_gte('recharged_on', $fdate);
        $d->where_lte('recharged_on', $tdate);
        $d->order_by_desc('id');
        $x = $d->find_many();

        $dr = ORM::for_table('tbl_transactions');
        if ($stype != '') {
            $dr->where('type', $stype);
        }

        $dr->where_gte('recharged_on', $fdate);
        $dr->where_lte('recharged_on', $tdate);
        $xy = $dr->sum('price');

        $ui->assign('d', $x);
        $ui->assign('dr', $xy);
        $ui->assign('fdate', $fdate);
        $ui->assign('tdate', $tdate);
        $ui->assign('stype', $stype);
        run_hook('view_reports_period'); #HOOK
        $ui->display('admin/reports/period-view.tpl');
        break;

    case 'daily-report':
    default:
        $options = TransactionReport::filterOptions();
        $types = $options['types'];
        $methods = $options['methods'];
        $routers = $options['routers'];
        $plans = $options['plans'];
        $f = TransactionReport::filterFromRequest($options, TransactionReport::defaultStart());
        // a list that narrows nothing shows every choice ticked, as before
        $tps = $f['tps'] ?: $types;
        $mts = $f['mts'] ?: $methods;
        $rts = $f['rts'] ?: $routers;
        $plns = $f['plns'] ?: $plans;
        $sd = $f['sd'];
        $ed = $f['ed'];
        $ts = $f['ts'];
        $te = $f['te'];
        $urlquery = str_replace('_route=reports', '', $_SERVER['QUERY_STRING']);

        $totals = TransactionReport::totals($f);
        $prev = TransactionReport::totals(TransactionReport::previous($f));
        $days = max(1, (int) round((strtotime($ed) - strtotime($sd)) / 86400) + 1);
        $ui->assign('kpi', [
            'count' => $totals['count'],
            'sum' => $totals['sum'],
            'avg' => $totals['count'] ? $totals['sum'] / $totals['count'] : 0,
            'per_day' => $totals['sum'] / $days,
            'days' => $days,
            'prev_count' => $prev['count'],
            'prev_sum' => $prev['sum'],
            // change against the period of the same length just before; null when there is nothing to compare with
            'sum_change' => $prev['sum'] > 0 ? round(($totals['sum'] - $prev['sum']) / $prev['sum'] * 100, 1) : null,
            'count_change' => $prev['count'] > 0 ? round(($totals['count'] - $prev['count']) / $prev['count'] * 100, 1) : null,
        ]);
        // quick ranges for the filter
        $ui->assign('ranges', [
            ['label' => Lang::T('Today'), 'sd' => $mdate, 'ed' => $mdate],
            ['label' => Lang::T('Yesterday'), 'sd' => date('Y-m-d', strtotime('-1 day')), 'ed' => date('Y-m-d', strtotime('-1 day'))],
            ['label' => Lang::T('Last 7 days'), 'sd' => date('Y-m-d', strtotime('-6 days')), 'ed' => $mdate],
            ['label' => Lang::T('Last 30 days'), 'sd' => date('Y-m-d', strtotime('-29 days')), 'ed' => $mdate],
            ['label' => Lang::T('This billing month'), 'sd' => TransactionReport::defaultStart(), 'ed' => $mdate],
        ]);
        $ui->assign('active_filters', count(array_filter([$f['tps'], $f['plns'], $f['mts'], $f['rts']])));
        // what the admin narrowed to (an empty list means every value); the lists above stay as they were for plugins
        $ui->assign('sel', ['tps' => $f['tps'], 'plns' => $f['plns'], 'mts' => $f['mts'], 'rts' => $f['rts']]);
        // the narrowed lists as a query string, so a quick range keeps them
        $selQuery = http_build_query(array_filter(['tps' => $f['tps'], 'plns' => $f['plns'], 'mts' => $f['mts'], 'rts' => $f['rts']]));
        $ui->assign('sel_query', $selQuery === '' ? '' : '&' . $selQuery);
        $ui->assign('full_day', $ts === '00:00:00' && $te === '23:59:59');
        $d = Paginator::findMany(TransactionReport::query($f), [], 100, $urlquery, true, $totals['count']);
        $d = TransactionReport::withNames($d ?: []);
        $dr = $totals['sum'];

        $ui->assign('methods', $methods);
        $ui->assign('types', $types);
        $ui->assign('routers', $routers);
        $ui->assign('plans', $plans);
        $ui->assign('filter', $urlquery);

        // time
        $ui->assign('sd', $sd);
        $ui->assign('ed', $ed);
        $ui->assign('ts', $ts);
        $ui->assign('te', $te);

        $ui->assign('mts', $mts);
        $ui->assign('tps', $tps);
        $ui->assign('rts', $rts);
        $ui->assign('plns', $plns);

        $ui->assign('d', $d);
        $ui->assign('dr', $dr);
        $ui->assign('mdate', $mdate);
        $ui->assign('_title', Lang::T('Daily Reports'));
        run_hook('view_daily_reports'); #HOOK
        $ui->display('admin/reports/list.tpl');
        break;
}
