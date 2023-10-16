<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', 'PHPNuxBill Logs');
$ui->assign('_system_menu', 'logs');

$action = $routes['1'];
$admin = Admin::_info();
$ui->assign('_admin', $admin);

if ($admin['user_type'] != 'Admin') {
    r2(U . "dashboard", 'e', $_L['Do_Not_Access']);
}


switch ($action) {
    case 'list':
        $name = _post('name');
        $keep = _post('keep');
        if (!empty($keep)) {
            ORM::raw_execute("DELETE FROM tbl_logs WHERE date < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL $keep DAY))");
            r2(U . "logs/list/", 's', "Delete logs older than $keep days");
        }
        if ($name != '') {
            $paginator = Paginator::bootstrap('tbl_logs', 'description', '%' . $name . '%');
            $d = ORM::for_table('tbl_logs')->where_like('description', '%' . $name . '%')->offset($paginator['startpoint'])->limit($paginator['limit'])->order_by_desc('id')->find_many();
        } else {
            $paginator = Paginator::bootstrap('tbl_logs');
            $d = ORM::for_table('tbl_logs')->offset($paginator['startpoint'])->limit($paginator['limit'])->order_by_desc('id')->find_many();
        }

        $ui->assign('name', $name);
        $ui->assign('d', $d);
        $ui->assign('paginator', $paginator);
        $ui->display('logs.tpl');
        break;
    case 'radius':
        $name = _post('name');
        $keep = _post('keep');
        $page = (isset($routes['2']) ? intval($routes['2']) : 0);
        $pageNow = $page * 20;
        if (!empty($keep)) {
            ORM::raw_execute("DELETE FROM radpostauth WHERE UNIX_TIMESTAMP(authdate) < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL $keep DAY))", [], 'radius');
            r2(U . "logs/radius/", 's', "Delete logs older than $keep days");
        }
        if ($name != '') {
            $d = ORM::for_table('radpostauth', 'radius')->where_like('username', '%' . $name . '%')->offset($pageNow)->limit(10)->order_by_desc('id')->find_many();
        } else {
            $d = ORM::for_table('radpostauth', 'radius')->offset($pageNow)->limit(10)->order_by_desc('id')->find_many();
        }

        $ui->assign('page', $page);
        $ui->assign('name', $name);
        $ui->assign('d', $d);
        $ui->assign('paginator', $paginator);
        $ui->display('logs-radius.tpl');
        break;


    default:
        r2(U . 'logs/list/', 's', '');
}
