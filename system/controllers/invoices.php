<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 * 
 **/


 _admin();
$ui->assign('_title', Lang::T('Invoice Lists'));
$ui->assign('_system_menu', 'reports');
$action = $routes['1'];
$ui->assign('_admin', $admin);

if (empty($action)) {
    $action = 'list';
}
switch ($action) {
    case 'list':
        $ui->assign('xheader', '<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">');
        $ui->assign('invoices', Invoice::getAll());
        $ui->display('admin/invoices/list.tpl');
        break;
    default:
        $ui->display('admin/404.tpl');
}
