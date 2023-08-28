<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)

 **/
_auth();
$ui->assign('_title', $_L['Voucher']);
$ui->assign('_system_menu', 'voucher');

$action = $routes['1'];
$user = User::_info();
$ui->assign('_user', $user);

use PEAR2\Net\RouterOS;

require_once 'system/autoload/PEAR2/Autoload.php';

switch ($action) {

    case 'activation':
        run_hook('view_activate_voucher'); #HOOK
        $ui->display('user-activation.tpl');
        break;

    case 'activation-post':
        $code = _post('code');
        $v1 = ORM::for_table('tbl_voucher')->where('code', $code)->where('status', 0)->find_one();
        run_hook('customer_activate_voucher'); #HOOK
        if ($v1) {
            if (Package::rechargeUser($user, $v1['routers'], $v1['id_plan'], "Activation", "Voucher")) {
                $v1->status = "1";
                $v1->user = $c['username'];
                $v1->save();
                r2(U . "voucher/list-activated", 's', $_L['Activation_Vouchers_Successfully']);
            } else {
                r2(U . 'voucher/activation', 'e', "Failed to refill account");
            }
        } else {
            r2(U . 'voucher/activation', 'e', $_L['Voucher_Not_Valid']);
        }
        break;

    case 'list-activated':
        $ui->assign('_system_menu', 'list-activated');
        $paginator = Paginator::bootstrap('tbl_transactions', 'username', $user['username']);
        $d = ORM::for_table('tbl_transactions')->where('username', $user['username'])->offset($paginator['startpoint'])->limit($paginator['limit'])->order_by_desc('id')->find_many();

        $ui->assign('d', $d);
        $ui->assign('paginator', $paginator);
        run_hook('customer_view_activation_list'); #HOOK
        $ui->display('user-activation-list.tpl');

        break;

    default:
        $ui->display('404.tpl');
}
