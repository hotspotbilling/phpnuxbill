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
        $ui->assign('code', alphanumeric(_get('code'),"-"));
        $ui->display('user-activation.tpl');
        break;

    case 'activation-post':
        $code = _post('code');
        $v1 = ORM::for_table('tbl_voucher')->where('code', $code)->where('status', 0)->find_one();
        run_hook('customer_activate_voucher'); #HOOK
        if ($v1) {
            if (Package::rechargeUser($user['id'], $v1['routers'], $v1['id_plan'], "Voucher", $code)) {
                $v1->status = "1";
                $v1->used_date = date('Y-m-d H:i:s');
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
        $query = ORM::for_table('tbl_transactions')->where('username', $user['username'])->order_by_desc('id');
        $d = Paginator::findMany($query);

        $ui->assign('d', $d);
        run_hook('customer_view_activation_list'); #HOOK
        $ui->display('user-activation-list.tpl');

        break;
    case 'invoice':
        $id = $routes[2];
        if(empty($id)){
            $in = ORM::for_table('tbl_transactions')->where('username', $user['username'])->order_by_desc('id')->find_one();
        }else{
            $in = ORM::for_table('tbl_transactions')->where('username', $user['username'])->where('id', $id)->find_one();
        }
        if($in){
            Package::createInvoice($in);
            $ui->display('invoice-customer.tpl');
        }else{
            r2(U . 'voucher/list-activated', 'e', Lang::T('Not Found'));
        }
        break;
    default:
        $ui->display('a404.tpl');
}
