<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
**/
_auth();
$ui->assign('_system_menu', 'order');
$action = $routes['1'];
$user = User::_info();
$ui->assign('_user', $user);

//Client Page
$bill = User::_billing();
$ui->assign('_bill', $bill);


switch ($action) {
    case 'voucher':
        $ui->assign('_title', $_L['Order_Voucher'].' - '. $config['CompanyName']);
        $ui->display('user-order.tpl');
        break;
    case 'ppoe':
        $ui->assign('_title', 'Order PPOE Internet- '. $config['CompanyName']);$routers = ORM::for_table('tbl_routers')->find_many();
        $plans = ORM::for_table('tbl_plans')->where('type', 'PPPOE')->where('enabled', '1')->find_many();
        $ui->assign('routers',$routers);
        $ui->assign('plans', $plans);
        $ui->display('user-orderPPOE.tpl');
        break;
    case 'hotspot':
        $ui->assign('_title', 'Order Hotspot Internet- '. $config['CompanyName']);
        $routers = ORM::for_table('tbl_routers')->find_many();
        $plans = ORM::for_table('tbl_plans')->where('type', 'Hotspot')->where('enabled', '1')->find_many();
        $ui->assign('routers',$routers);
        $ui->assign('plans', $plans);
        $ui->display('user-orderHotspot.tpl');
        break;
    default:
        $ui->display('404.tpl');
}

