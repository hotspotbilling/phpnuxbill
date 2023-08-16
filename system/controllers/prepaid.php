<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 * @copyright	Copyright (C) 2014-2015 PHP Mikrotik Billing
 * @license		GNU General Public License version 2 or later; see LICENSE.txt

 **/
_admin();
$ui->assign('_title', $_L['Recharge_Account']);
$ui->assign('_system_menu', 'prepaid');

$action = $routes['1'];
$admin = Admin::_info();
$ui->assign('_admin', $admin);

if ($admin['user_type'] != 'Admin' and $admin['user_type'] != 'Sales') {
    r2(U . "dashboard", 'e', $_L['Do_Not_Access']);
}

use PEAR2\Net\RouterOS;

require_once 'system/autoload/PEAR2/Autoload.php';

switch ($action) {
    case 'list':
        $ui->assign('xfooter', '<script type="text/javascript" src="ui/lib/c/prepaid.js"></script>');

        $username = _post('username');
        if ($username != '') {
            $paginator = Paginator::bootstrap('tbl_user_recharges', 'username', '%' . $username . '%');
            $d = ORM::for_table('tbl_user_recharges')->where_like('username', '%' . $username . '%')->offset($paginator['startpoint'])->limit($paginator['limit'])->order_by_desc('id')->find_many();
        } else {
            $paginator = Paginator::bootstrap('tbl_user_recharges');
            $d = ORM::for_table('tbl_user_recharges')->offset($paginator['startpoint'])->limit($paginator['limit'])->order_by_desc('id')->find_many();
        }

        $ui->assign('d', $d);
        $ui->assign('cari', $username);
        $ui->assign('paginator', $paginator);
        run_hook('view_list_billing'); #HOOK
        $ui->display('prepaid.tpl');
        break;

    case 'recharge':
        $c = ORM::for_table('tbl_customers')->find_many();
        $ui->assign('c', $c);
        $p = ORM::for_table('tbl_plans')->where('enabled', '1')->find_many();
        $ui->assign('p', $p);
        $r = ORM::for_table('tbl_routers')->where('enabled', '1')->find_many();
        $ui->assign('r', $r);
        run_hook('view_recharge'); #HOOK
        $ui->display('recharge.tpl');
        break;

    case 'recharge-user':
        $id = $routes['2'];
        $ui->assign('id', $id);

        $c = ORM::for_table('tbl_customers')->find_many();
        $ui->assign('c', $c);
        $p = ORM::for_table('tbl_plans')->where('enabled', '1')->find_many();
        $ui->assign('p', $p);
        $r = ORM::for_table('tbl_routers')->where('enabled', '1')->find_many();
        $ui->assign('r', $r);
        run_hook('view_recharge_customer'); #HOOK
        $ui->display('recharge-user.tpl');
        break;

    case 'recharge-post':
        $id_customer = _post('id_customer');
        $type = _post('type');
        $server = _post('server');
        $plan = _post('plan');
        $date_only = date("Y-m-d");
        $time = date("H:i:s");

        $msg = '';
        if ($id_customer == '' or $type == '' or $server == '' or $plan == '') {
            $msg .= 'All field is required' . '<br>';
        }

        if ($msg == '') {
            if(Package::rechargeUser($id_customer, $server, $plan, "Recharge", $admin['fullname'])){
                $c = ORM::for_table('tbl_customers')->where('id', $id_customer)->find_one();
                $in = ORM::for_table('tbl_transactions')->where('username', $c['username'])->order_by_desc('id')->find_one();
                $ui->assign('in', $in);
                $ui->assign('date', date("Y-m-d H:i:s"));
                $ui->display('invoice.tpl');
                _log('[' . $admin['username'] . ']: ' . 'Recharge '.$c['username'].' ['.$in['plan_name'].']['.Lang::moneyFormat($in['price']).']', 'Admin', $admin['id']);
            }else{
                r2(U . 'prepaid/recharge', 'e', "Failed to recharge account");
            }
        } else {
            r2(U . 'prepaid/recharge', 'e', $msg);
        }
        break;

    case 'print':
        $id = _post('id');

        $d = ORM::for_table('tbl_transactions')->where('id', $id)->find_one();
        $ui->assign('d', $d);

        $ui->assign('date', date("Y-m-d H:i:s"));
        run_hook('print_invoice'); #HOOK
        $ui->display('invoice-print.tpl');
        break;

    case 'edit':
        $id  = $routes['2'];
        $d = ORM::for_table('tbl_user_recharges')->find_one($id);
        if ($d) {
            $ui->assign('d', $d);
            $p = ORM::for_table('tbl_plans')->where('enabled', '1')->find_many();
            $ui->assign('p', $p);
            run_hook('view_edit_customer_plan'); #HOOK
            $ui->display('prepaid-edit.tpl');
        } else {
            r2(U . 'services/list', 'e', $_L['Account_Not_Found']);
        }
        break;

    case 'delete':
        $id  = $routes['2'];

        $d = ORM::for_table('tbl_user_recharges')->find_one($id);
        $mikrotik = Mikrotik::info($d['routers']);
        if ($d) {
            run_hook('delete_customer_active_plan'); #HOOK
            if ($d['type'] == 'Hotspot') {
                if(!$config['radius_mode']){
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::removeHotspotUser($client,$c['username']);
                }

                $d->delete();
            } else {
                if(!$config['radius_mode']){
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::removePpoeUser($client,$c['username']);
                }
                $d->delete();
            }
            _log('[' . $admin['username'] . ']: ' . 'Delete Plan for Customer '.$c['username'].'  ['.$in['plan_name'].']['.Lang::moneyFormat($in['price']).']', 'Admin', $admin['id']);
            r2(U . 'prepaid/list', 's', $_L['Delete_Successfully']);
        }
        break;

    case 'edit-post':
        $username = _post('username');
        $id_plan = _post('id_plan');
        $recharged_on = _post('recharged_on');
        $expiration = _post('expiration');

        $id = _post('id');
        $d = ORM::for_table('tbl_user_recharges')->find_one($id);
        if ($d) {
        } else {
            $msg .= $_L['Data_Not_Found'] . '<br>';
        }

        if ($msg == '') {
            run_hook('edit_customer_plan'); #HOOK
            $d->username = $username;
            $d->plan_id = $id_plan;
            $d->recharged_on = $recharged_on;
            $d->expiration = $expiration;
            $d->save();
            Package::changeTo($username,$id_plan);
            _log('[' . $admin['username'] . ']: ' . 'Edit Plan for Customer '.$d['username'].' to ['.$d['plan_name'].']['.Lang::moneyFormat($d['price']).']', 'Admin', $admin['id']);
            r2(U . 'prepaid/list', 's', $_L['Updated_Successfully']);
        } else {
            r2(U . 'prepaid/edit/' . $id, 'e', $msg);
        }
        break;

    case 'voucher':
        $ui->assign('xfooter', '<script type="text/javascript" src="ui/lib/c/voucher.js"></script>');

        $code = _post('code');
        if ($code != '') {
            $ui->assign('code', $code);
            $paginator = Paginator::bootstrap('tbl_voucher', 'code', '%' . $code . '%');
            $d = ORM::for_table('tbl_plans')->where('enabled', '1')
                ->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where_like('tbl_voucher.code', '%' . $code . '%')
                ->offset($paginator['startpoint'])
                ->limit($paginator['limit'])
                ->find_many();
        } else {
            $paginator = Paginator::bootstrap('tbl_voucher');
            $d = ORM::for_table('tbl_plans')->where('enabled', '1')
                ->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->offset($paginator['startpoint'])
                ->limit($paginator['limit'])->find_many();
        }

        $ui->assign('d', $d);
        $ui->assign('_code', $code);
        $ui->assign('paginator', $paginator);
        run_hook('view_list_voucher'); #HOOK
        $ui->display('voucher.tpl');
        break;

    case 'add-voucher':

        $c = ORM::for_table('tbl_customers')->find_many();
        $ui->assign('c', $c);
        $p = ORM::for_table('tbl_plans')->where('enabled', '1')->find_many();
        $ui->assign('p', $p);
        $r = ORM::for_table('tbl_routers')->where('enabled', '1')->find_many();
        $ui->assign('r', $r);
        run_hook('view_add_voucher'); #HOOK
        $ui->display('voucher-add.tpl');
        break;

    case 'print-voucher':
        $from_id = _post('from_id') * 1;
        $planid = _post('planid') * 1;
        $pagebreak = _post('pagebreak') * 1;
        $limit = _post('limit') * 1;

        if ($pagebreak < 1) $pagebreak = 6;

        if ($limit < 1) $limit = $pagebreak * 2;

        if ($from_id > 0 && $planid > 0) {
            $v = ORM::for_table('tbl_plans')
                ->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0')
                ->where('tbl_plans.id', $planid)
                ->where_gt('tbl_voucher.id', $from_id)
                ->limit($limit)
                ->find_many();
            $vc = ORM::for_table('tbl_plans')
                ->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0')
                ->where('tbl_plans.id', $planid)
                ->where_gt('tbl_voucher.id', $from_id)
                ->count();
        } else if ($from_id == 0 && $planid > 0) {
            $v = ORM::for_table('tbl_plans')
                ->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0')
                ->where('tbl_plans.id', $planid)
                ->limit($limit)
                ->find_many();
            $vc = ORM::for_table('tbl_plans')
                ->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0')
                ->where('tbl_plans.id', $planid)
                ->count();
        } else if ($from_id > 0 && $planid == 0) {
            $v = ORM::for_table('tbl_plans')
                ->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0')
                ->where_gt('tbl_voucher.id', $from_id)
                ->limit($limit)
                ->find_many();
            $vc = ORM::for_table('tbl_plans')
                ->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0')
                ->where_gt('tbl_voucher.id', $from_id)
                ->count();
        } else {
            $v = ORM::for_table('tbl_plans')
                ->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0')
                ->limit($limit)
                ->find_many();
            $vc = ORM::for_table('tbl_plans')
                ->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0')
                ->count();
        }

        $ui->assign('_title', $_L['Voucher_Hotspot']);
        $ui->assign('from_id', $from_id);
        $ui->assign('pagebreak', $pagebreak);

        $plans = ORM::for_table('tbl_plans')->find_many();
        $ui->assign('plans', $plans);
        $ui->assign('limit', $limit);
        $ui->assign('planid', $planid);

        $ui->assign('v', $v);
        $ui->assign('vc', $vc);

        //for counting pagebreak
        $ui->assign('jml', 0);
        run_hook('view_print_voucher'); #HOOK
        $ui->display('print-voucher.tpl');
        break;
    case 'voucher-post':
        $type = _post('type');
        $plan = _post('plan');
        $server = _post('server');
        $numbervoucher = _post('numbervoucher');
        $lengthcode = _post('lengthcode');

        $msg = '';
        if ($type == '' or $plan == '' or $server == '' or $numbervoucher == '' or $lengthcode == '') {
            $msg .= $_L['All_field_is_required'] . '<br>';
        }
        if (Validator::UnsignedNumber($numbervoucher) == false) {
            $msg .= 'The Number of Vouchers must be a number' . '<br>';
        }
        if (Validator::UnsignedNumber($lengthcode) == false) {
            $msg .= 'The Length Code must be a number' . '<br>';
        }
        if ($msg == '') {
            run_hook('create_voucher'); #HOOK
            for ($i = 0; $i < $numbervoucher; $i++) {
                $code = strtoupper(substr(md5(time() . rand(10000, 99999)), 0, $lengthcode));
                //TODO: IMPLEMENT Voucher Generator
                $d = ORM::for_table('tbl_voucher')->create();
                $d->type = $type;
                $d->routers = $server;
                $d->id_plan = $plan;
                $d->code = $code;
                $d->user = '0';
                $d->status = '0';
                $d->save();
            }

            r2(U . 'prepaid/voucher', 's', $_L['Voucher_Successfully']);
        } else {
            r2(U . 'prepaid/add-voucher/' . $id, 'e', $msg);
        }
        break;

    case 'voucher-delete':
        $id  = $routes['2'];
        run_hook('delete_voucher'); #HOOK
        $d = ORM::for_table('tbl_voucher')->find_one($id);
        if ($d) {
            $d->delete();
            r2(U . 'prepaid/voucher', 's', $_L['Delete_Successfully']);
        }
        break;

    case 'refill':
        $ui->assign('xfooter', '<script type="text/javascript" src="ui/ui/scripts/form-elements.init.js"></script>');
        $ui->assign('_title', $_L['Refill_Account']);
        $c = ORM::for_table('tbl_customers')->find_many();
        $ui->assign('c', $c);
        run_hook('view_refill'); #HOOK
        $ui->display('refill.tpl');

        break;

    case 'refill-post':
        $user = _post('id_customer');
        $code = _post('code');

        $v1 = ORM::for_table('tbl_voucher')->where('code', $code)->where('status', 0)->find_one();

        run_hook('refill_customer'); #HOOK
        if ($v1) {
            if(Package::rechargeUser($user, $v1['routers'], $v1['id_plan'], "Refill", "Voucher")){
                $v1->status = "1";
                $v1->user = $c['username'];
                $v1->save();
                $c = ORM::for_table('tbl_customers')->where('id', $id_customer)->find_one();
                $in = ORM::for_table('tbl_transactions')->where('username', $c['username'])->order_by_desc('id')->find_one();
                $ui->assign('in', $in);
                $ui->assign('date', date("Y-m-d H:i:s"));
                $ui->display('invoice.tpl');
            }else{
                r2(U . 'prepaid/refill', 'e', "Failed to refill account");
            }
        } else {
            r2(U . 'prepaid/refill', 'e', $_L['Voucher_Not_Valid']);
        }
        break;
    case 'deposit':
        $ui->assign('_title', Lang::T('Refill Balance'));
        $ui->assign('c', ORM::for_table('tbl_customers')->find_many());
        $ui->assign('p', ORM::for_table('tbl_plans')->where('enabled', '1')->where('type', 'Balance')->find_many());
        run_hook('view_deposit'); #HOOK
        $ui->display('deposit.tpl');
        break;
    case 'deposit-post':
        $user = _post('id_customer');
        $plan = _post('id_plan');

        run_hook('deposit_customer'); #HOOK
        if (!empty($user) && !empty($plan)) {
            if(Package::rechargeUser($user, 'balance', $plan, "Deposit", $admin['fullname'])){
                $c = ORM::for_table('tbl_customers')->where('id', $user)->find_one();
                $in = ORM::for_table('tbl_transactions')->where('username', $c['username'])->order_by_desc('id')->find_one();
                $ui->assign('in', $in);
                $ui->assign('date', date("Y-m-d H:i:s"));
                $ui->display('invoice.tpl');
            }else{
                r2(U . 'prepaid/refill', 'e', "Failed to refill account");
            }
        } else {
            r2(U . 'prepaid/refill', 'e', "All field is required");
        }
        break;
    default:
        echo 'action not defined';
}
