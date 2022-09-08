<?php

/**
 * PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
 **/
_auth();
$ui->assign('_system_menu', 'order');
$action = $routes['1'];
$user = User::_info();
$ui->assign('_user', $user);


require('system/autoload/Paymentgateway.php');
require('system/autoload/Recharge.php');

switch ($action) {
    case 'voucher':
        $ui->assign('_title', $_L['Order_Voucher'] . ' - ' . $config['CompanyName']);
        $ui->display('user-order.tpl');
        break;
    case 'ppoe':
        $ui->assign('_title', 'Order PPOE Internet - ' . $config['CompanyName']);
        $routers = ORM::for_table('tbl_routers')->find_many();
        $plans = ORM::for_table('tbl_plans')->where('type', 'PPPOE')->where('enabled', '1')->find_many();
        $ui->assign('routers', $routers);
        $ui->assign('plans', $plans);
        $ui->display('user-orderPPOE.tpl');
        break;
    case 'hotspot':
        $ui->assign('_title', 'Order Hotspot Internet - ' . $config['CompanyName']);
        $routers = ORM::for_table('tbl_routers')->find_many();
        $plans = ORM::for_table('tbl_plans')->where('type', 'Hotspot')->where('enabled', '1')->find_many();
        $ui->assign('routers', $routers);
        $ui->assign('plans', $plans);
        $ui->display('user-orderHotspot.tpl');
        break;
    case 'view':
        $trxid = $routes['2'] * 1;
        $trx = ORM::for_table('tbl_payment_gateway')
            ->where('username', $user['username'])
            ->find_one($trxid);
        if ($routes['3'] == 'check') {
            if ($trx['gateway'] == 'xendit') {
                $result = xendit_get_invoice($trx['gateway_trx_id']);
                if ($result['status'] == 'PENDING') {
                    r2(U . "order/view/" . $trxid, 'w', Lang::T("Transaction still unpaid."));
                } else if ($result['status'] == 'PAID' && $trx['status'] != 2) {

                    if (!rechargeUser($user['id'], $trx['routers'], $trx['plan_id'], 'xendit',  $result['payment_method'] . ' ' . $result['payment_channel'])) {
                        r2(U . "order/view/" . $trxid, 'd', Lang::T("Failed to activate your Package, try again later."));
                    }

                    $trx->pg_paid_response = json_encode($result);
                    $trx->payment_method = $result['payment_method'];
                    $trx->payment_channel = $result['payment_channel'];
                    $trx->paid_date = date('Y-m-d H:i:s', strtotime($result['updated']));
                    $trx->status = 2;
                    $trx->save();

                    r2(U . "order/view/" . $trxid, 's', Lang::T("Transaction has been paid."));
                } else if ($result['status'] == 'EXPIRED') {
                    $trx->pg_paid_response = json_encode($result);
                    $trx->status = 3;
                    $trx->save();
                    r2(U . "order/view/" . $trxid, 'd', Lang::T("Transaction expired."));
                }else if($trx['status'] == 2){
                    r2(U . "order/view/" . $trxid, 'd', Lang::T("Transaction has been paid.."));
                }
                r2(U . "order/view/" . $trxid, 'd', Lang::T("Unknown Command."));
            } else if ($trx['gateway'] == 'midtrans') {
            } else if ($trx['gateway'] == 'tripay') {
            }
        } else if ($routes['3'] == 'cancel') {
            $trx->pg_paid_response = json_encode($result);
            $trx->status = 4;
            $trx->save();
            $trx = ORM::for_table('tbl_payment_gateway')
                ->where('username', $user['username'])
                ->find_one($trxid);
        }
        if (empty($trx)) {
            r2(U . "home", 'e', Lang::T("Transaction Not found"));
        }
        $router = ORM::for_table('tbl_routers')->find_one($trx['routers_id']);
        $plan = ORM::for_table('tbl_plans')->find_one($trx['plan_id']);
        $bandw = ORM::for_table('tbl_bandwidth')->find_one($plan['id_bw']);
        $ui->assign('trx', $trx);
        $ui->assign('router', $router);
        $ui->assign('plan', $plan);
        $ui->assign('bandw', $bandw);
        $ui->assign('_title', 'TRX #' . $trxid . ' - ' . $config['CompanyName']);
        $ui->display('user-orderView.tpl');
        break;
    case 'hotspot-buy':
        if (empty($_c['xendit_secret_key'])) {
            r2(U . "order/hotspot", 'e', Lang::T("Admin has not yet setup Xendit payment gateway, please tell admin"));
        }
        $router = ORM::for_table('tbl_routers')->where('enabled', '1')->find_one($routes['2'] * 1);
        $plan = ORM::for_table('tbl_plans')->where('enabled', '1')->find_one($routes['3'] * 1);
        if (empty($router) || empty($plan)) {
            r2(U . "order/hotspot", 'e', Lang::T("Plan Not found"));
        }
        if ($_c['payment_gateway'] == 'xendit') {
            $d = ORM::for_table('tbl_payment_gateway')
                ->where('username', $user['username'])
                ->where('status', 1)
                ->find_one();
            if ($d) {
                if ($d['pg_url_payment']) {
                    r2(U . "order/view/" . $d['id'], 'w', Lang::T("You already have unpaid transaction, cancel it or pay it."));
                }
                $id = $d['id'];
            } else {
                $d = ORM::for_table('tbl_payment_gateway')->create();
                $d->username = $user['username'];
                $d->gateway = 'xendit';
                $d->plan_id = $plan['id'];
                $d->plan_name = $plan['name_plan'];
                $d->routers_id = $router['id'];
                $d->routers = $router['name'];
                $d->price = $plan['price'];
                $d->created_date = date('Y-m-d H:i:s');
                $d->status = 1;
                $d->save();
                $id = $d->id();
            }
            if ($id) {
                $result = xendit_create_invoice($id, $plan['price'], $user['username'], $plan['name_plan']);
                if (!$result['id']) {
                    r2(U . "order/hotspot", 'e', Lang::T("Failed to create transaction."));
                }
                $d = ORM::for_table('tbl_payment_gateway')
                    ->where('username', $user['username'])
                    ->where('status', 1)
                    ->find_one();
                $d->gateway_trx_id = $result['id'];
                $d->pg_url_payment = $result['invoice_url'];
                $d->pg_request = json_encode($result);
                $d->expired_date = date('Y-m-d H:i:s', strtotime($result['expiry_date']));
                $d->save();
                header('Location: ' . $result['invoice_url']);
                exit();
            } else {
                r2(U . "order/view/" . $d['id'], 'w', Lang::T("Failed to create Transaction.."));
            }
        } else if ($_c['payment_gateway'] == 'midtrans') {
        } else if ($_c['payment_gateway'] == 'tripay') {
        }
        break;
    default:
        $ui->display('404.tpl');
}