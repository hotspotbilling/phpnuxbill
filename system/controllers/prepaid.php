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

        $date_now = date("Y-m-d H:i:s");
        $date_only = date("Y-m-d");
        $time = date("H:i:s");

        $msg = '';
        if ($id_customer == '' or $type == '' or $server == '' or $plan == '') {
            $msg .= 'All field is required' . '<br>';
        }

        if ($msg == '') {
            $c = ORM::for_table('tbl_customers')->where('id', $id_customer)->find_one();
            $p = ORM::for_table('tbl_plans')->where('id', $plan)->where('enabled', '1')->find_one();
            $b = ORM::for_table('tbl_user_recharges')->where('customer_id', $id_customer)->find_one();

            $mikrotik = Mikrotik::info($server);
            if($p['validity_unit']=='Months'){
                $date_exp = date("Y-m-d", strtotime('+'.$p['validity'].' month'));
            }else if($p['validity_unit']=='Days'){
                $date_exp = date("Y-m-d", strtotime('+'.$p['validity'].' day'));
            }else if($p['validity_unit']=='Hrs'){
                $datetime = explode(' ',date("Y-m-d H:i:s", strtotime('+'.$p['validity'].' hour')));
                $date_exp = $datetime[0];
                $time = $datetime[1];
            }else if($p['validity_unit']=='Mins'){
                $datetime = explode(' ',date("Y-m-d H:i:s", strtotime('+'.$p['validity'].' minute')));
                $date_exp = $datetime[0];
                $time = $datetime[1];
            }
            run_hook('recharge_customer'); #HOOK
            if ($type == 'Hotspot') {
                if ($b) {
                    if(!$config['radius_mode']){
                        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        Mikrotik::removeHotspotUser($client,$c['username']);
                        Mikrotik::addHotspotUser($client,$p,$c);
                    }

                    $b->customer_id = $id_customer;
                    $b->username = $c['username'];
                    $b->plan_id = $plan;
                    $b->namebp = $p['name_plan'];
                    $b->recharged_on = $date_only;
                    $b->expiration = $date_exp;
                    $b->time = $time;
                    $b->status = "on";
                    $b->method = "admin";
                    $b->routers = $server;
                    $b->type = "Hotspot";
                    $b->save();

                    // insert table transactions
                    $t = ORM::for_table('tbl_transactions')->create();
                    $t->invoice = "INV-" . _raid(5);
                    $t->username = $c['username'];
                    $t->plan_name = $p['name_plan'];
                    $t->price = $p['price'];
                    $t->recharged_on = $date_only;
                    $t->expiration = $date_exp;
                    $t->time = $time;
                    $t->method = "admin";
                    $t->routers = $server;
                    $t->type = "Hotspot";
                    $t->save();
                } else {
                    if(!$config['radius_mode']){
                        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        Mikrotik::addHotspotUser($client,$p,$c);
                    }

                    $d = ORM::for_table('tbl_user_recharges')->create();
                    $d->customer_id = $id_customer;
                    $d->username = $c['username'];
                    $d->plan_id = $plan;
                    $d->namebp = $p['name_plan'];
                    $d->recharged_on = $date_only;
                    $d->expiration = $date_exp;
                    $d->time = $time;
                    $d->status = "on";
                    $d->method = "admin";
                    $d->routers = $server;
                    $d->type = "Hotspot";
                    $d->save();

                    // insert table transactions
                    $t = ORM::for_table('tbl_transactions')->create();
                    $t->invoice = "INV-" . _raid(5);
                    $t->username = $c['username'];
                    $t->plan_name = $p['name_plan'];
                    $t->price = $p['price'];
                    $t->recharged_on = $date_only;
                    $t->expiration = $date_exp;
                    $t->time = $time;
                    $t->method = "admin";
                    $t->routers = $server;
                    $t->type = "Hotspot";
                    $t->save();
                }
                sendTelegram( "$admin[fullname] #Recharge Voucher #Hotspot for #u$c[username]\n".$p['name_plan'].
                "\nRouter: ".$server.
                "\nPrice: ".$p['price']);
            } else {

                if ($b) {
                    if(!$config['radius_mode']){
                        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        Mikrotik::removePpoeUser($client,$c['username']);
                        Mikrotik::addPpoeUser($client,$p,$c);
                    }

                    $b->customer_id = $id_customer;
                    $b->username = $c['username'];
                    $b->plan_id = $plan;
                    $b->namebp = $p['name_plan'];
                    $b->recharged_on = $date_only;
                    $b->expiration = $date_exp;
                    $b->time = $time;
                    $b->status = "on";
                    $b->method = "admin";
                    $b->routers = $server;
                    $b->type = "PPPOE";
                    $b->save();

                    // insert table transactions
                    $t = ORM::for_table('tbl_transactions')->create();
                    $t->invoice = "INV-" . _raid(5);
                    $t->username = $c['username'];
                    $t->plan_name = $p['name_plan'];
                    $t->price = $p['price'];
                    $t->recharged_on = $date_only;
                    $t->expiration = $date_exp;
                    $t->time = $time;
                    $t->method = "admin";
                    $t->routers = $server;
                    $t->type = "PPPOE";
                    $t->save();
                } else {
                    if(!$config['radius_mode']){
                        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        Mikrotik::addPpoeUser($client,$p,$c);
                    }

                    $d = ORM::for_table('tbl_user_recharges')->create();
                    $d->customer_id = $id_customer;
                    $d->username = $c['username'];
                    $d->plan_id = $plan;
                    $d->namebp = $p['name_plan'];
                    $d->recharged_on = $date_only;
                    $d->expiration = $date_exp;
                    $d->time = $time;
                    $d->status = "on";
                    $d->method = "admin";
                    $d->routers = $server;
                    $d->type = "PPPOE";
                    $d->save();

                    // insert table transactions
                    $t = ORM::for_table('tbl_transactions')->create();
                    $t->invoice = "INV-" . _raid(5);
                    $t->username = $c['username'];
                    $t->plan_name = $p['name_plan'];
                    $t->price = $p['price'];
                    $t->recharged_on = $date_only;
                    $t->expiration = $date_exp;
                    $t->time = $time;
                    $t->method = "admin";
                    $t->routers = $server;
                    $t->type = "PPPOE";
                    $t->save();
                }
                sendTelegram( "$admin[fullname] #Recharge Voucher #PPPOE for #u$c[username]\n".$p['name_plan'].
                "\nRouter: ".$server.
                "\nPrice: ".$p['price']);
            }

            $in = ORM::for_table('tbl_transactions')->where('username', $c['username'])->order_by_desc('id')->find_one();
            $ui->assign('in', $in);

            sendWhatsapp($c['username'], "*$config[CompanyName]*\n".
					"$config[address]\n".
					"$config[phone]\n".
					"\n\n".
					"INVOICE: *$in[invoice]*\n".
                    "$_L[Date] : $date_now\n".
					"$_L[Sales] : $admin[fullname]\n".
					"\n\n".
					"$_L[Type] : *$in[type]*\n".
					"$_L[Plan_Name] : *$in[plan_name]*\n".
					"$_L[Plan_Price] : *$config[currency_code] ".number_format($in['price'],2,$config['dec_point'],$config['thousands_sep'])."*\n\n".
					"$_L[Username] : *$in[username]*\n".
					"$_L[Password] : **********\n\n".
					"$_L[Created_On] :\n*".date($config['date_format'], strtotime($in['recharged_on']))." $in[time]*\n".
					"$_L[Expires_On] :\n*".date($config['date_format'], strtotime($in['expiration']))." $in[time]*\n".
					"\n\n".
					"$config[note]");


            $ui->assign('date', $date_now);
            $ui->display('invoice.tpl');
        } else {
            r2(U . 'prepaid/recharge', 'e', $msg);
        }
        break;

    case 'print':
        $date_now = date("Y-m-d H:i:s");
        $id = _post('id');

        $d = ORM::for_table('tbl_transactions')->where('id', $id)->find_one();
        $ui->assign('d', $d);

        $ui->assign('date', $date_now);
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
            //TODO set mikrotik for editedd plan
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
                ->where_like('tbl_plans.code', '%' . $code . '%')
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

        $c = ORM::for_table('tbl_customers')->find_many();
        $ui->assign('c', $c);
        run_hook('view_refill'); #HOOK
        $ui->display('refill.tpl');

        break;

    case 'refill-post':
        $user = _post('id_customer');
        $code = _post('code');

        $v1 = ORM::for_table('tbl_voucher')->where('code', $code)->where('status', 0)->find_one();

        $c = ORM::for_table('tbl_customers')->find_one($user);
        $p = ORM::for_table('tbl_plans')->find_one($v1['id_plan']);
        $b = ORM::for_table('tbl_user_recharges')->where('customer_id', $user)->find_one();

        $date_now = date("Y-m-d H:i:s");
        $date_only = date("Y-m-d");
        $time = date("H:i:s");

        $mikrotik = Mikrotik::info($v1['routers']);

        if($p['validity_unit']=='Months'){
            $date_exp = date("Y-m-d", strtotime('+'.$p['validity'].' month'));
        }else if($p['validity_unit']=='Days'){
            $date_exp = date("Y-m-d", strtotime('+'.$p['validity'].' day'));
        }else if($p['validity_unit']=='Hrs'){
            $datetime = explode(' ',date("Y-m-d H:i:s", strtotime('+'.$p['validity'].' hour')));
            $date_exp = $datetime[0];
            $time = $datetime[1];
        }else if($p['validity_unit']=='Mins'){
            $datetime = explode(' ',date("Y-m-d H:i:s", strtotime('+'.$p['validity'].' minute')));
            $date_exp = $datetime[0];
            $time = $datetime[1];
        }
        run_hook('refill_customer'); #HOOK
        if ($v1) {
            if ($v1['type'] == 'Hotspot') {
                if ($b) {
                    if(!$config['radius_mode']){
                        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        Mikrotik::removeHotspotUser($client,$c['username']);
                        Mikrotik::addHotspotUser($client,$p,$c);
                    }

                    $b->customer_id = $user;
                    $b->username = $c['username'];
                    $b->plan_id = $v1['id_plan'];
                    $b->namebp = $p['name_plan'];
                    $b->recharged_on = $date_only;
                    $b->expiration = $date_exp;
                    $b->time = $time;
                    $b->status = "on";
                    $b->method = "voucher";
                    $b->routers = $v1['routers'];
                    $b->type = "Hotspot";
                    $b->save();

                    // insert table transactions
                    $t = ORM::for_table('tbl_transactions')->create();
                    $t->invoice = "INV-" . _raid(5);
                    $t->username = $c['username'];
                    $t->plan_name = $p['name_plan'];
                    $t->price = $p['price'];
                    $t->recharged_on = $date_only;
                    $t->expiration = $date_exp;
                    $t->time = $time;
                    $t->method = "voucher";
                    $t->routers = $v1['routers'];
                    $t->type = "Hotspot";
                    $t->save();
                } else {
                    if(!$config['radius_mode']){
                        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        Mikrotik::addHotspotUser($client,$p,$c);
                    }

                    $d = ORM::for_table('tbl_user_recharges')->create();
                    $d->customer_id = $user;
                    $d->username = $c['username'];
                    $d->plan_id = $v1['id_plan'];
                    $d->namebp = $p['name_plan'];
                    $d->recharged_on = $date_only;
                    $d->expiration = $date_exp;
                    $d->time = $time;
                    $d->status = "on";
                    $d->method = "voucher";
                    $d->routers = $v1['routers'];
                    $d->type = "Hotspot";
                    $d->save();

                    // insert table transactions
                    $t = ORM::for_table('tbl_transactions')->create();
                    $t->invoice = "INV-" . _raid(5);
                    $t->username = $c['username'];
                    $t->plan_name = $p['name_plan'];
                    $t->price = $p['price'];
                    $t->recharged_on = $date_only;
                    $t->expiration = $date_exp;
                    $t->time = $time;
                    $t->method = "voucher";
                    $t->routers = $v1['routers'];
                    $t->type = "Hotspot";
                    $t->save();
                }

                $v1->status = "1";
                $v1->user = $c['username'];
                $v1->save();

                sendTelegram( "$admin[fullname] #Refill #Voucher #Hotspot for #u$c[username]\n".$p['name_plan'].
                "\nCode: ".$code.
                "\nRouter: ".$v1['routers'].
                "\nPrice: ".$p['price']);
            } else {
                if ($b) {
                    if(!$config['radius_mode']){
                        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        Mikrotik::removePpoeUser($client,$c['username']);
                        Mikrotik::addPpoeUser($client,$p,$c);
                    }

                    $b->customer_id = $user;
                    $b->username = $c['username'];
                    $b->plan_id = $v1['id_plan'];
                    $b->namebp = $p['name_plan'];
                    $b->recharged_on = $date_only;
                    $b->expiration = $date_exp;
                    $b->time = $time;
                    $b->status = "on";
                    $b->method = "voucher";
                    $b->routers = $v1['routers'];
                    $b->type = "PPPOE";
                    $b->save();

                    // insert table transactions
                    $t = ORM::for_table('tbl_transactions')->create();
                    $t->invoice = "INV-" . _raid(5);
                    $t->username = $c['username'];
                    $t->plan_name = $p['name_plan'];
                    $t->price = $p['price'];
                    $t->recharged_on = $date_only;
                    $t->expiration = $date_exp;
                    $t->time = $time;
                    $t->method = "voucher";
                    $t->routers = $v1['routers'];
                    $t->type = "PPPOE";
                    $t->save();
                } else {
                    if(!$config['radius_mode']){
                        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        Mikrotik::addPpoeUser($client,$p,$c);
                    }

                    $d = ORM::for_table('tbl_user_recharges')->create();
                    $d->customer_id = $user;
                    $d->username = $c['username'];
                    $d->plan_id = $v1['id_plan'];
                    $d->namebp = $p['name_plan'];
                    $d->recharged_on = $date_only;
                    $d->expiration = $date_exp;
                    $d->time = $time;
                    $d->status = "on";
                    $d->method = "voucher";
                    $d->routers = $v1['routers'];
                    $d->type = "PPPOE";
                    $d->save();

                    // insert table transactions
                    $t = ORM::for_table('tbl_transactions')->create();
                    $t->invoice = "INV-" . _raid(5);
                    $t->username = $c['username'];
                    $t->plan_name = $p['name_plan'];
                    $t->price = $p['price'];
                    $t->recharged_on = $date_only;
                    $t->expiration = $date_exp;
                    $t->time = $time;
                    $t->method = "voucher";
                    $t->routers = $v1['routers'];
                    $t->type = "PPPOE";
                    $t->save();
                }

                $v1->status = "1";
                $v1->user = $c['username'];
                $v1->save();


                sendTelegram( "$admin[fullname] Refill #Voucher #PPPOE for #u$c[username]\n".$p['name_plan'].
                "\nCode: ".$code.
                "\nRouter: ".$v1['routers'].
                "\nPrice: ".$p['price']);
            }
            $in = ORM::for_table('tbl_transactions')->where('username', $c['username'])->order_by_desc('id')->find_one();
            $ui->assign('in', $in);


            sendWhatsapp($c['username'], "*$config[CompanyName]*\n".
					"$config[address]\n".
					"$config[phone]\n".
					"\n\n".
					"INVOICE: *$in[invoice]*\n".
                    "$_L[Date] : $date_now\n".
					"$_L[Sales] : $admin[fullname]\n".
					"\n\n".
					"$_L[Type] : *$in[type]*\n".
					"$_L[Plan_Name] : *$in[plan_name]*\n".
					"$_L[Plan_Price] : *$config[currency_code] ".number_format($in['price'],2,$config['dec_point'],$config['thousands_sep'])."*\n\n".
					"$_L[Username] : *$in[username]*\n".
					"$_L[Password] : **********\n\n".
					"$_L[Created_On] :\n*".date($config['date_format'], strtotime($in['recharged_on']))." $in[time]*\n".
					"$_L[Expires_On] :\n*".date($config['date_format'], strtotime($in['expiration']))." $in[time]*\n".
					"\n\n".
					"$config[note]");

            $ui->assign('date', $date_now);
            $ui->display('invoice.tpl');
        } else {
            r2(U . 'prepaid/refill', 'e', $_L['Voucher_Not_Valid']);
        }
        break;

    default:
        echo 'action not defined';
}
