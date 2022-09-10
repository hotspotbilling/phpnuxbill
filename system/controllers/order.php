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
    case 'package':
        $ui->assign('_title', 'Order PPOE Internet - ' . $config['CompanyName']);
        $routers = ORM::for_table('tbl_routers')->find_many();
        $plans = ORM::for_table('tbl_plans')->where('enabled', '1')->find_many();
        $ui->assign('routers', $routers);
        $ui->assign('plans', $plans);
        $ui->display('user-orderPackage.tpl');
        break;
    case 'unpaid':
        $d = ORM::for_table('tbl_payment_gateway')
            ->where('username', $user['username'])
            ->where('status', 1)
            ->find_one();
        if($d){
            if (empty($d['pg_url_payment'])) {
                r2(U . "order/buy/" . $trx['routers_id'] .'/'.$trx['plan_id'], 'w', Lang::T("Checking payment"));
            }else{
                r2(U . "order/view/" . $d['id'].'/check/', 's', Lang::T("You have unpaid transaction"));
            }
        }else{
            r2(U . "order/package/", 's', Lang::T("You have no unpaid transaction"));
        }
    case 'view':
        $trxid = $routes['2'] * 1;
        $trx = ORM::for_table('tbl_payment_gateway')
            ->where('username', $user['username'])
            ->find_one($trxid);
        // jika url kosong, balikin ke buy
        if (empty($trx['pg_url_payment'])) {
            r2(U . "order/buy/" . $trx['routers_id'] .'/'.$trx['plan_id'], 'w', Lang::T("Checking payment"));
        }
        if ($routes['3'] == 'check') {
            if ($trx['gateway'] == 'xendit') {
                $result = xendit_get_invoice($trx['gateway_trx_id']);
                if ($result['status'] == 'PENDING') {
                    r2(U . "order/view/" . $trxid, 'w', Lang::T("Transaction still unpaid."));
                } else if (in_array($result['status'],['PAID','SETTLED']) && $trx['status'] != 2) {

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
                print_r($result);
                die();
                r2(U . "order/view/" . $trxid, 'd', Lang::T("Unknown Command."));
            } else if ($trx['gateway'] == 'midtrans') {
                $result = midtrans_check_payment($trx['gateway_trx_id']);
                print_r($result);
            } else if ($trx['gateway'] == 'tripay') {
            }
        } else if ($routes['3'] == 'cancel') {
            $trx->pg_paid_response = '{}';
            $trx->status = 4;
            $trx->paid_date = date('Y-m-d H:i:s');
            $trx->save();
            $trx = ORM::for_table('tbl_payment_gateway')
                ->where('username', $user['username'])
                ->find_one($trxid);
            if('midtrans'==$trx['gateway']){
                //Hapus invoice link
            }
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
    case 'buy':
        $back = "order/package";
        $router = ORM::for_table('tbl_routers')->where('enabled', '1')->find_one($routes['2'] * 1);
        $plan = ORM::for_table('tbl_plans')->where('enabled', '1')->find_one($routes['3'] * 1);
        if (empty($router) || empty($plan)) {
            r2(U . $back, 'e', Lang::T("Plan Not found"));
        }
        $d = ORM::for_table('tbl_payment_gateway')
            ->where('username', $user['username'])
            ->where('status', 1)
            ->find_one();
        if($d){
            if ($d['pg_url_payment']) {
                r2(U . "order/view/" . $d['id'], 'w', Lang::T("You already have unpaid transaction, cancel it or pay it."));
            }else{
                if($_c['payment_gateway']==$d['gateway']){
                    $id = $d['id'];
                }else{
                    $d->status = 4;
                    $d->save();
                }
            }
        }
        if(empty($id)){
            $d = ORM::for_table('tbl_payment_gateway')->create();
            $d->username = $user['username'];
            $d->gateway = $_c['payment_gateway'];
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
        if ($_c['payment_gateway'] == 'xendit') {
            if (empty($_c['xendit_secret_key'])) {
                sendTelegram("Xendit payment gateway not configured");
                r2(U . $back, 'e', Lang::T("Admin has not yet setup Xendit payment gateway, please tell admin"));
            }
            if ($id) {
                $result = xendit_create_invoice($id, $plan['price'], $user['username'], $plan['name_plan']);
                if (!$result['id']) {
                    r2(U . $back, 'e', Lang::T("Failed to create transaction."));
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
            if (empty($_c['midtrans_server_key'])) {
                sendTelegram("Midtrans payment gateway not configured");
                r2(U . $back, 'e', Lang::T("Admin has not yet setup Midtrans payment gateway, please tell admin"));
            }
            if ($id) {
                $invoiceID = alphanumeric(strtolower($_c['CompanyName'])) . "-" . crc32($_c['CompanyName'] . $id) . "-" . $id;
                $result = midtrans_create_payment($id, $invoiceID, $plan['price'],$plan['name_plan']);
                if (!$result['payment_url']) {
                    sendTelegram("Midtrans payment failed\n\n".json_encode($result, JSON_PRETTY_PRINT));
                    r2(U . $back, 'e', Lang::T("Failed to create transaction."));
                }
                $d = ORM::for_table('tbl_payment_gateway')
                    ->where('username', $user['username'])
                    ->where('status', 1)
                    ->find_one();
                $d->gateway_trx_id = $invoiceID;
                $d->pg_url_payment = $result['payment_url'];
                $d->pg_request = json_encode($result);
                $d->expired_date = date('Y-m-d H:i:s', strtotime("+1 days"));
                $d->save();
                r2(U . "order/view/" . $id, 'w', Lang::T("Create Transaction Success"));
                exit();
            } else {
                r2(U . "order/view/" . $d['id'], 'w', Lang::T("Failed to create Transaction.."));
            }
        } else if ($_c['payment_gateway'] == 'tripay') {
            if (empty($_c['tripay_secret_key'])) {
                sendTelegram("Tripay payment gateway not configured");
                r2(U . $back, 'e', Lang::T("Admin has not yet setup Tripay payment gateway, please tell admin"));
            }
        }
        break;
    default:
        $ui->display('404.tpl');
}