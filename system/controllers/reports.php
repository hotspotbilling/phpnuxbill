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
        $data = $routes['2'];
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
        $sd = _req('sd', $start_date);
        $ed = _req('ed', $mdate);
        $ts = _req('ts', '00:00:00');
        $te = _req('te', '23:59:59');
        $types = ORM::for_table('tbl_transactions')->getEnum('type');
        $tps = ($_GET['tps']) ? $_GET['tps'] : $types;
        $plans = array_column(ORM::for_table('tbl_transactions')->select('plan_name')->distinct('plan_name')->find_array(), 'plan_name');
        $plns = ($_GET['plns']) ? $_GET['plns'] : $plans;
        $methods = array_column(ORM::for_table('tbl_transactions')->rawQuery("SELECT DISTINCT SUBSTRING_INDEX(`method`, ' - ', 1) as method FROM tbl_transactions;")->findArray(), 'method');
        $mts = ($_GET['mts']) ? $_GET['mts'] : $methods;
        $routers = array_column(ORM::for_table('tbl_transactions')->select('routers')->distinct('routers')->find_array(), 'routers');
        $rts = ($_GET['rts']) ? $_GET['rts'] : $routers;
        $result = [];
        switch ($data) {
            case 'type':
                foreach ($tps as $tp) {
                    $query = ORM::for_table('tbl_transactions')
                        ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) >= " . strtotime("$sd $ts"))
                        ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) <= " . strtotime("$ed $te"))
                        ->where('type', $tp);
                    if (count($mts) > 0) {
                        if (count($mts) != count($methods)) {
                            foreach ($mts as $mt) {
                                $query->where_like('method', "$mt - %");
                            }
                        }
                    }
                    if (count($rts) > 0) {
                        $query->where_in('routers', $rts);
                    }
                    if (count($plns) > 0) {
                        $query->where_in('plan_name', $plns);
                    }
                    $count = $query->count();
                    if ($count > 0) {
                        $result['datas'][] = $count;
                        $result['labels'][] = "$tp ($count)";
                    }
                }
                break;
            case 'plan':
                foreach ($plns as $pln) {
                    $query = ORM::for_table('tbl_transactions')
                        ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) >= " . strtotime("$sd $ts"))
                        ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) <= " . strtotime("$ed $te"))
                        ->where('plan_name', $pln);
                    if (count($tps) > 0) {
                        $query->where_in('type', $tps);
                    }
                    if (count($mts) > 0) {
                        if (count($mts) != count($methods)) {
                            foreach ($mts as $mt) {
                                $query->where_like('method', "$mt - %");
                            }
                        }
                    }
                    if (count($rts) > 0) {
                        $query->where_in('routers', $rts);
                    }
                    $count = $query->count();
                    if ($count > 0) {
                        $result['datas'][] = $count;
                        $result['labels'][] = "$pln ($count)";
                    }
                }
                break;
            case 'method':
                foreach ($mts as $mt) {
                    $query = ORM::for_table('tbl_transactions')
                        ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) >= " . strtotime("$sd $ts"))
                        ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) <= " . strtotime("$ed $te"))
                        ->where_like('method', "$mt - %");
                    if (count($tps) > 0) {
                        $query->where_in('type', $tps);
                    }
                    if (count($rts) > 0) {
                        $query->where_in('routers', $rts);
                    }
                    if (count($plns) > 0) {
                        $query->where_in('plan_name', $plns);
                    }
                    if (count($mts) > 0) {
                        if (count($mts) != count($methods)) {
                            foreach ($mts as $mt) {
                                $query->where_like('method', "$mt - %");
                            }
                        }
                    }
                    $count = $query->count();
                    if ($count > 0) {
                        $result['datas'][] = $count;
                        $result['labels'][] = "$mt ($count)";
                    }
                }
                break;
            case 'router':
                foreach ($rts as $rt) {
                    $query = ORM::for_table('tbl_transactions')
                        ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) >= " . strtotime("$sd $ts"))
                        ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) <= " . strtotime("$ed $te"))
                        ->where('routers', $rt);
                    if (count($tps) > 0) {
                        $query->where_in('type', $tps);
                    }
                    if (count($plns) > 0) {
                        $query->where_in('plan_name', $plns);
                    }
                    $count = $query->count();
                    if ($count > 0) {
                        $result['datas'][] = $count;
                        $result['labels'][] = "$rt ($count)";
                    }
                }
                break;
            case 'line':
                $query = ORM::for_table('tbl_transactions')
                    ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) >= " . strtotime("$sd $ts"))
                    ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) <= " . strtotime("$ed $te"))
                    ->order_by_desc('id');
                if (count($tps) > 0) {
                    $query->where_in('type', $tps);
                }
                if (count($mts) > 0) {
                    if (count($mts) != count($methods)) {
                        foreach ($mts as $mt) {
                            $query->where_like('method', "$mt - %");
                        }
                    }
                }
                if (count($rts) > 0) {
                    $query->where_in('routers', $rts);
                }
                if (count($plns) > 0) {
                    $query->where_in('plan_name', $plns);
                }
                $datas = $query->find_array();
                $period = new DatePeriod(
                    new DateTime($sd),
                    new DateInterval('P1D'),
                    new DateTime($ed)
                );
                $pos = 0;
                $dates = [];
                foreach ($period as $key => $value) {
                    $dates[] = $value->format('Y-m-d');
                }
                $dates = array_reverse($dates);
                $result = [];
                $temp;
                foreach ($dates as $date) {
                    $result['labels'][] = $date;
                    // type
                    foreach ($tps as $key) {
                        if (!isset($temp[$key][$date])) {
                            $temp[$key][$date] = 0;
                        }
                        foreach ($datas as $data) {
                            if ($data['recharged_on'] == date('Y-m-d', strtotime($date)) && $data['type'] == $key) {
                                $temp[$key][$date] += 1;
                            }
                        }
                    }
                    //plan
                    foreach ($plns as $key) {
                        if (!isset($temp[$key][$date])) {
                            $temp[$key][$date] = 0;
                        }
                        foreach ($datas as $data) {
                            if ($data['recharged_on'] == date('Y-m-d', strtotime($date)) && $data['plan_name'] == $key) {
                                $temp[$key][$date] += 1;
                            }
                        }
                    }
                    //method
                    foreach ($mts as $key) {
                        if (!isset($temp[$key][$date])) {
                            $temp[$key][$date] = 0;
                        }
                        foreach ($datas as $data) {
                            if ($data['recharged_on'] == date('Y-m-d', strtotime($date)) && strpos($data['method'], $key) !== false) {
                                $temp[$key][$date] += 1;
                            }
                        }
                    }

                    foreach ($rts as $key) {
                        if (!isset($temp[$key][$date])) {
                            $temp[$key][$date] = 0;
                        }
                        foreach ($datas as $data) {
                            if ($data['recharged_on'] == date('Y-m-d', strtotime($date)) && $data['routers'] == $key) {
                                $temp[$key][$date] += 1;
                            }
                        }
                    }
                    $pos++;
                    if ($pos > 29) {
                        // only 30days
                        break;
                    }
                }
                foreach ($temp as $key => $value) {
                    $array = ['label' => $key];
                    $total = 0;
                    foreach ($value as $k => $v) {
                        $total += $v;
                        $array['data'][] = $v;
                    }
                    if($total>0){
                        $result['datas'][] = $array;
                    }
                }
                break;
            default:
                $result = ['labels' => [], 'datas' => []];
        }
        echo json_encode($result);
        die();
    case 'by-date':
    case 'activation':
        $q = (_post('q') ? _post('q') : _get('q'));
        $keep = _post('keep');
        if (!empty($keep)) {
            ORM::raw_execute("DELETE FROM tbl_transactions WHERE date < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL $keep DAY))");
            r2(getUrl('logs/list/'), 's', "Delete logs older than $keep days");
        }
        if ($q != '') {
            $query = ORM::for_table('tbl_transactions')->where_like('invoice', '%' . $q . '%')->order_by_desc('id');
            $d = Paginator::findMany($query, ['q' => $q]);
        } else {
            $query = ORM::for_table('tbl_transactions')->order_by_desc('id');
            $d = Paginator::findMany($query);
        }

        $ui->assign('activation', $d);
        $ui->assign('q', $q);
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
        if ($stype != '') {
            $d->where('type', $stype);
        }

        $d->where_gte('recharged_on', $fdate);
        $d->where_lte('recharged_on', $tdate);
        $d->order_by_desc('id');
        $x =  $d->find_many();

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
        $types = ORM::for_table('tbl_transactions')->getEnum('type');
        $methods = array_column(ORM::for_table('tbl_transactions')->rawQuery("SELECT DISTINCT SUBSTRING_INDEX(`method`, ' - ', 1) as method FROM tbl_transactions;")->findArray(), 'method');
        $routers = array_column(ORM::for_table('tbl_transactions')->select('routers')->distinct('routers')->find_array(), 'routers');
        $plans = array_column(ORM::for_table('tbl_transactions')->select('plan_name')->distinct('plan_name')->find_array(), 'plan_name');
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
        $tps = ($_GET['tps']) ? $_GET['tps'] : $types;
        $mts = ($_GET['mts']) ? $_GET['mts'] : $methods;
        $rts = ($_GET['rts']) ? $_GET['rts'] : $routers;
        $plns = ($_GET['plns']) ? $_GET['plns'] : $plans;
        $sd = _req('sd', $start_date);
        $ed = _req('ed', $mdate);
        $ts = _req('ts', '00:00:00');
        $te = _req('te', '23:59:59');
        $urlquery = str_replace('_route=reports', '', $_SERVER['QUERY_STRING']);


        $query = ORM::for_table('tbl_transactions')
            ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) >= " . strtotime("$sd $ts"))
            ->whereRaw("UNIX_TIMESTAMP(CONCAT(`recharged_on`,' ',`recharged_time`)) <= " . strtotime("$ed $te"))
            ->order_by_desc('id');
        if (count($tps) > 0) {
            $query->where_in('type', $tps);
        }
        if (count($mts) > 0) {
            if (count($mts) != count($methods)) {
                foreach ($mts as $mt) {
                    $query->where_like('method', "$mt - %");
                }
            }
        }
        if (count($rts) > 0) {
            $query->where_in('routers', $rts);
        }
        if (count($plns) > 0) {
            $query->where_in('plan_name', $plns);
        }
        $d = Paginator::findMany($query, [], 100, $urlquery);
        $dr = $query->sum('price');

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
        run_hook('view_daily_reports'); #HOOK
        $ui->display('admin/reports/list.tpl');
        break;
}
