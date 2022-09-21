<?php

/**
 * PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
 **/
_auth();
$ui->assign('_system_menu', 'order');
$action = $routes['1'];
$user = User::_info();
$ui->assign('_user', $user);

switch ($action) {
    case 'voucher':
        $ui->assign('_title', $_L['Order_Voucher'] . ' - ' . $config['CompanyName']);
        run_hook('customer_view_order'); #HOOK
        $ui->display('user-order.tpl');
        break;
    case 'history':
        $d = ORM::for_table('tbl_payment_gateway')
            ->where('username', $user['username'])
            ->find_many();
        $paginator = Paginator::bootstrap('tbl_payment_gateway', 'username', $user['username']);
        $ui->assign('paginator', $paginator);
        $ui->assign('d', $d);
        $ui->assign('_title', Lang::T('Order History') . ' - ' . $config['CompanyName']);
        run_hook('customer_view_order_history'); #HOOK
        $ui->display('user-orderHistory.tpl');
        break;
    case 'package':
        $ui->assign('_title', 'Order Plan - ' . $config['CompanyName']);
        $routers = ORM::for_table('tbl_routers')->find_many();
        $plans = ORM::for_table('tbl_plans')->where('enabled', '1')->find_many();
        $ui->assign('routers', $routers);
        $ui->assign('plans', $plans);
        run_hook('customer_view_order_plan'); #HOOK
        $ui->display('user-orderPlan.tpl');
        break;
    case 'unpaid':
        $d = ORM::for_table('tbl_payment_gateway')
            ->where('username', $user['username'])
            ->where('status', 1)
            ->find_one();
        run_hook('customer_find_unpaid'); #HOOK
        if ($d) {
            if (empty($d['pg_url_payment'])) {
                r2(U . "order/buy/" . $trx['routers_id'] . '/' . $trx['plan_id'], 'w', Lang::T("Checking payment"));
            } else {
                r2(U . "order/view/" . $d['id'] . '/check/', 's', Lang::T("You have unpaid transaction"));
            }
        } else {
            r2(U . "order/package/", 's', Lang::T("You have no unpaid transaction"));
        }
    case 'view':
        $trxid = $routes['2'] * 1;
        $trx = ORM::for_table('tbl_payment_gateway')
            ->where('username', $user['username'])
            ->find_one($trxid);
        run_hook('customer_view_payment'); #HOOK
        // jika tidak ditemukan, berarti punya orang lain
        if (empty($trx)) {
            r2(U . "order/package", 'w', Lang::T("Payment not found"));
        }
        // jika url kosong, balikin ke buy
        if (empty($trx['pg_url_payment'])) {
            r2(U . "order/buy/" . $trx['routers_id'] . '/' . $trx['plan_id'], 'w', Lang::T("Checking payment"));
        }
        if ($routes['3'] == 'check') {
            if (!file_exists('system/paymentgateway/' . $trx['gateway'] . '.php')) {
                r2(U . 'order/view/' . $trxid, 'e', Lang::T("No Payment Gateway Available"));
            }
            run_hook('customer_check_payment_status'); #HOOK
            include 'system/paymentgateway/' . $trx['gateway'] . '.php';
            call_user_func($trx['gateway'] . '_validate_config');
            call_user_func($config['payment_gateway'] . '_get_status', $trx, $user);

        } else if ($routes['3'] == 'cancel') {
            run_hook('customer_cancel_payment'); #HOOK
            $trx->pg_paid_response = '{}';
            $trx->status = 4;
            $trx->paid_date = date('Y-m-d H:i:s');
            $trx->save();
            $trx = ORM::for_table('tbl_payment_gateway')
                ->where('username', $user['username'])
                ->find_one($trxid);
            if ('midtrans' == $trx['gateway']) {
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
        if ($config['payment_gateway'] == 'none') {
            r2(U . 'home', 'e', Lang::T("No Payment Gateway Available"));
        }
        if (!file_exists('system/paymentgateway/' . $config['payment_gateway'] . '.php')) {
            r2(U . 'home', 'e', Lang::T("No Payment Gateway Available"));
        }
        run_hook('customer_buy_plan'); #HOOK
        include 'system/paymentgateway/' . $config['payment_gateway'] . '.php';
        call_user_func($config['payment_gateway'] . '_validate_config');

        $router = ORM::for_table('tbl_routers')->where('enabled', '1')->find_one($routes['2'] * 1);
        $plan = ORM::for_table('tbl_plans')->where('enabled', '1')->find_one($routes['3'] * 1);
        if (empty($router) || empty($plan)) {
            r2(U . $back, 'e', Lang::T("Plan Not found"));
        }
        $d = ORM::for_table('tbl_payment_gateway')
            ->where('username', $user['username'])
            ->where('status', 1)
            ->find_one();
        if ($d) {
            if ($d['pg_url_payment']) {
                r2(U . "order/view/" . $d['id'], 'w', Lang::T("You already have unpaid transaction, cancel it or pay it."));
            } else {
                if ($config['payment_gateway'] == $d['gateway']) {
                    $id = $d['id'];
                } else {
                    $d->status = 4;
                    $d->save();
                }
            }
        }
        if (empty($id)) {
            $d = ORM::for_table('tbl_payment_gateway')->create();
            $d->username = $user['username'];
            $d->gateway = $config['payment_gateway'];
            $d->plan_id = $plan['id'];
            $d->plan_name = $plan['name_plan'];
            $d->routers_id = $router['id'];
            $d->routers = $router['name'];
            $d->price = $plan['price'];
            $d->created_date = date('Y-m-d H:i:s');
            $d->status = 1;
            $d->save();
            $id = $d->id();
        } else {
            $d->username = $user['username'];
            $d->gateway = $config['payment_gateway'];
            $d->plan_id = $plan['id'];
            $d->plan_name = $plan['name_plan'];
            $d->routers_id = $router['id'];
            $d->routers = $router['name'];
            $d->price = $plan['price'];
            $d->created_date = date('Y-m-d H:i:s');
            $d->status = 1;
            $d->save();
        }
        if (!$id) {
            r2(U . "order/package/" . $d['id'], 'e', Lang::T("Failed to create Transaction.."));
        } else {
            call_user_func($config['payment_gateway'] . '_create_transaction', $d, $user);
        }
        break;
    default:
        $ui->display('404.tpl');
}
