<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * used for ajax
 **/

_admin();
$ui->assign('_title', Lang::T('Network'));
$ui->assign('_system_menu', 'network');

$action = $routes['1'];
$ui->assign('_admin', $admin);

switch ($action) {
    case 'pool':
        $routers = _get('routers');
        if (empty($routers)) {
            $d = ORM::for_table('tbl_pool')->find_many();
        } else {
            $d = ORM::for_table('tbl_pool')->where('routers', $routers)->find_many();
        }
        $ui->assign('routers', $routers);
        $ui->assign('d', $d);
        $ui->display('autoload-pool.tpl');
        break;
    case 'bw_name':
        $bw = ORM::for_table('tbl_bandwidth')->select("name_bw")->find_one($routes['2']);
        echo $bw['name_bw'];
        die();
    case 'server':
        $d = ORM::for_table('tbl_routers')->where('enabled', '1')->find_many();
        $ui->assign('d', $d);

        $ui->display('autoload-server.tpl');
        break;
    case 'pppoe_ip_used':
        if (!empty(_get('ip'))) {
            $cs = ORM::for_table('tbl_customers')
                ->select("username")
                ->where_not_equal('id', _get('id'))
                ->where("pppoe_ip", _get('ip'))
                ->findArray();
            if (count($cs) > 0) {
                $c = array_column($cs, 'username');
                die(Lang::T("IP has been used by") . ' : ' . implode(", ", $c));
            }
        }
        die();
    case 'pppoe_username_used':
        if (!empty(_get('u'))) {
            $cs = ORM::for_table('tbl_customers')
                ->select("username")
                ->where_not_equal('id', _get('id'))
                ->where("pppoe_username", _get('u'))
                ->findArray();
            if (count($cs) > 0) {
                $c = array_column($cs, 'username');
                die(Lang::T("Username has been used by") . ' : ' . implode(", ", $c));
            }
        }
        die();
    case 'plan':
        $server = _post('server');
        $jenis = _post('jenis');
        if (in_array($admin['user_type'], array('SuperAdmin', 'Admin'))) {
            if ($server == 'radius') {
                $d = ORM::for_table('tbl_plans')->where('is_radius', 1)->where('type', $jenis)->find_many();
            } else {
                $d = ORM::for_table('tbl_plans')->where('routers', $server)->where('type', $jenis)->find_many();
            }
        } else {
            if ($server == 'radius') {
                $d = ORM::for_table('tbl_plans')->where('is_radius', 1)->where('type', $jenis)->where('enabled', '1')->find_many();
            } else {
                $d = ORM::for_table('tbl_plans')->where('routers', $server)->where('type', $jenis)->where('enabled', '1')->find_many();
            }
        }
        $ui->assign('d', $d);

        $ui->display('autoload.tpl');
        break;
    case 'customer_is_active':
        if ($config['check_customer_online'] == 'yes') {
            $c = ORM::for_table('tbl_customers')->where('username', $routes['2'])->find_one();
            $p = ORM::for_table('tbl_plans')->find_one($routes['3']);
            $dvc = Package::getDevice($p);
            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    try {
                        //don't wait more than 5 seconds for response from device, otherwise we get timeout error.
                        ini_set('default_socket_timeout', 5);
                        if ((new $p['device'])->online_customer($c, $p['routers'])) {
                            echo '<span class="label label-success" title="online">&nbsp;</span>';
                        }
                    } catch (Exception $e) {
                        echo '<span class="label label-danger" title="error">&nbsp;</span>';
                    }
                }
            }
        }
        break;
    case 'plan_is_active':
        $ds = ORM::for_table('tbl_user_recharges')->where('customer_id', $routes['2'])->find_array();
        if ($ds) {
            $ps = [];
            $c = ORM::for_table('tbl_customers')->find_one($routes['2']);
            foreach ($ds as $d) {
                if ($d['status'] == 'on') {
                    if ($config['check_customer_online'] == 'yes') {
                        $p = ORM::for_table('tbl_plans')->find_one($d['plan_id']);
                        $dvc = Package::getDevice($p);
                        $status = "";
                        if ($_app_stage != 'demo') {
                            if (file_exists($dvc)) {
                                require_once $dvc;
                                try {
                                    //don't wait more than 5 seconds for response from device, otherwise we get timeout error.
                                    ini_set('default_socket_timeout', 5);
                                    if ((new $p['device'])->online_customer($c, $p['routers'])) {
                                        $status = '<span class="label label-success" title="online">&nbsp;</span>';
                                    }
                                } catch (Exception $e) {
                                    $status = '<span class="label label-danger" title="error">&nbsp;</span>';
                                }
                            }
                        }
                    }
                    $ps[] = ('<span class="label label-primary m-1" title="Expired ' . Lang::dateAndTimeFormat($d['expiration'], $d['time']) . '">' . $d['namebp'] . ' ' . $status . '</span>');
                } else {
                    $ps[] = ('<span class="label label-danger m-1" title="Expired ' . Lang::dateAndTimeFormat($d['expiration'], $d['time']) . '">' . $d['namebp'] . '</span>');
                }
            }
            echo implode("<br>", $ps);
        } else {
            die('');
        }
        break;
    case 'customer_select2':

        $s = addslashes(_get('s'));
        if (empty($s)) {
            $c = ORM::for_table('tbl_customers')->limit(30)->find_many();
        } else {
            $c = ORM::for_table('tbl_customers')->where_raw("(`username` LIKE '%$s%' OR `fullname` LIKE '%$s%' OR `phonenumber` LIKE '%$s%' OR `email` LIKE '%$s%')")->limit(30)->find_many();
        }
        header('Content-Type: application/json');
        foreach ($c as $cust) {
            $json[] = [
                'id' => $cust['id'],
                'text' => $cust['username'] . ' - ' . $cust['fullname'] . ' - ' . $cust['email']
            ];
        }
        echo json_encode(['results' => $json]);
        die();
    default:
        $ui->display('a404.tpl');
}
