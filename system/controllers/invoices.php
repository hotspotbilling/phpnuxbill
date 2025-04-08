<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 * 
 **/


_auth();
$ui->assign('_title', Lang::T('Invoices'));
$ui->assign('_system_menu', 'Reports');

$action = $routes['1'];
$user = User::_info();
$ui->assign('_user', $user);

switch ($action) {

    case 'list':
        $ui->assign('xheader', '<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">');
        $ui->assign('invoices', Invoice::getAll());
        $ui->display('admin/invoice/list.tpl');
        break;
    default:
        $ui->display('admin/404.tpl');
}
