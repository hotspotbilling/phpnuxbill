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
        if(empty($routers)){
            $d = ORM::for_table('tbl_pool')->find_many();
        }else{
            $d = ORM::for_table('tbl_pool')->where('routers', $routers)->find_many();
        }
        $ui->assign('routers', $routers);
        $ui->assign('d', $d);
        $ui->display('autoload-pool.tpl');
        break;

    case 'server':
        $d = ORM::for_table('tbl_routers')->where('enabled', '1')->find_many();
        $ui->assign('d', $d);

        $ui->display('autoload-server.tpl');
        break;

    case 'plan':
        $server = _post('server');
        $jenis = _post('jenis');
        if(in_array($admin['user_type'], array('SuperAdmin', 'Admin'))){
            if($server=='radius'){
                $d = ORM::for_table('tbl_plans')->where('is_radius', 1)->where('type', $jenis)->find_many();
            }else{
                $d = ORM::for_table('tbl_plans')->where('routers', $server)->where('type', $jenis)->find_many();
            }
        }else{
            if($server=='radius'){
                $d = ORM::for_table('tbl_plans')->where('is_radius', 1)->where('type', $jenis)->where('enabled', '1')->find_many();
            }else{
                $d = ORM::for_table('tbl_plans')->where('routers', $server)->where('type', $jenis)->where('enabled', '1')->find_many();
            }
        }
        $ui->assign('d', $d);

        $ui->display('autoload.tpl');
        break;
    case 'customer_is_active':
        $d = ORM::for_table('tbl_user_recharges')->where('customer_id', $routes['2'])->findOne();
        if ($d) {
            if ($d['status'] == 'on') {
                die('<span class="label label-success" title="Expired ' . Lang::dateAndTimeFormat($d['expiration'], $d['time']) . '">'.$d['namebp'].'</span>');
            } else {
                die('<span class="label label-danger" title="Expired ' . Lang::dateAndTimeFormat($d['expiration'], $d['time']) . '">'.$d['namebp'].'</span>');
            }
        } else {
            die('<span class="label label-danger">&bull;</span>');
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
