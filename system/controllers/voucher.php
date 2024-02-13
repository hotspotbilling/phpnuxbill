<?php
/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/
_auth();
$ui->assign('_title', Lang::T('Voucher'));
$ui->assign('_system_menu', 'voucher');

$action = $routes['1'];
$user = User::_info();
$ui->assign('_user', $user);

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
            if (Package::rechargeUser($user['id'], $v1['routers'], $v1['id_plan'], "Voucher", $code)) {
                $v1->status = "1";
                $v1->user = $user['username'];
                $v1->save();
                r2(U . "voucher/list-activated", 's', Lang::T('Activation Vouchers Successfully'));
            } else {
                r2(U . 'voucher/activation', 'e', "Failed to refill account");
            }
        } else {
            r2(U . 'voucher/activation', 'e', Lang::T('Voucher Not Valid'));
        }
        break;

    case 'list-activated':
        $ui->assign('_system_menu', 'list-activated');
        $paginator = Paginator::build(ORM::for_table('tbl_transactions'), ['username' => $user['username']]);
        $d = ORM::for_table('tbl_transactions')->where('username', $user['username'])->offset($paginator['startpoint'])->limit($paginator['limit'])->order_by_desc('id')->find_many();

        $ui->assign('d', $d);
        $ui->assign('paginator', $paginator);
        run_hook('customer_view_activation_list'); #HOOK
        $ui->display('user-activation-list.tpl');

        break;

    default:
        $ui->display('a404.tpl');
}
