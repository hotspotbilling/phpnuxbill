<?php

/**
 * PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)

 **/
_auth();
$ui->assign('_title', $_L['Voucher'] . '- ' . $config['CompanyName']);
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

        $c = ORM::for_table('tbl_customers')->find_one($user['id']);
        $p = ORM::for_table('tbl_plans')->find_one($v1['id_plan']);
        $b = ORM::for_table('tbl_user_recharges')->where('customer_id', $user['id'])->find_one();

        $date_now = date("Y-m-d H:i:s");
        $date_only = date("Y-m-d");
        $time = date("H:i:s");

        $mikrotik = Mikrotik::info($v1['routers']);
        if ($p['validity_unit'] == 'Months') {
            $date_exp = date("Y-m-d", strtotime('+' . $p['validity'] . ' month'));
        } else if ($p['validity_unit'] == 'Days') {
            $date_exp = date("Y-m-d", strtotime('+' . $p['validity'] . ' day'));
        } else if ($p['validity_unit'] == 'Hrs') {
            $datetime = explode(' ', date("Y-m-d H:i:s", strtotime('+' . $p['validity'] . ' hour')));
            $date_exp = $datetime[0];
            $time = $datetime[1];
        } else if ($p['validity_unit'] == 'Mins') {
            $datetime = explode(' ', date("Y-m-d H:i:s", strtotime('+' . $p['validity'] . ' minute')));
            $date_exp = $datetime[0];
            $time = $datetime[1];
        }
        run_hook('customer_activate_voucher'); #HOOK
        if ($v1) {
            if ($v1['type'] == 'Hotspot') {
                if ($b) {
                    if (!$config['radius_mode']) {
                        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        Mikrotik::removeHotspotUser($client, $c['username']);
                        Mikrotik::addHotspotUser($client, $p, $c);
                    }
                    $b->customer_id = $user['id'];
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
                    if (!$config['radius_mode']) {
                        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        Mikrotik::addHotspotUser($client, $p, $c);
                    }

                    $d = ORM::for_table('tbl_user_recharges')->create();
                    $d->customer_id = $user['id'];
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
                // Telegram to Admin
                sendTelegram('#u' . $c['username'] . " Activate #Voucher #Hotspot\n" . $p['name_plan'] .
                    "\nCode: " . $code .
                    "\nRouter: " . $v1['routers'] .
                    "\nPrice: " . $p['price']);
            } else {
                if ($b) {
                    if (!$config['radius_mode']) {
                        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        Mikrotik::removePpoeUser($client, $c['username']);
                        Mikrotik::addPpoeUser($client, $p, $c);
                    }

                    $b->customer_id = $user['id'];
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
                    if (!$config['radius_mode']) {
                        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        Mikrotik::addPpoeUser($client, $p, $c);
                    }

                    $d = ORM::for_table('tbl_user_recharges')->create();
                    $d->customer_id = $user['id'];
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
                // Telegram to Admin
                sendTelegram('#u' . $c['username'] . " Activate #Voucher #PPPOE\n" . $p['name_plan'] .
                    "\nCode: " . $code .
                    "\nRouter: " . $v1['routers'] .
                    "\nPrice: " . $p['price']);
            }

            r2(U . "voucher/list-activated", 's', $_L['Activation_Vouchers_Successfully']);
        } else {
            r2(U . 'voucher/activation', 'e', $_L['Voucher_Not_Valid']);
        }
        break;

    case 'list-activated':
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
