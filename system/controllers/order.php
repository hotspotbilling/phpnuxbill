<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_auth();
$action = $routes['1'];
$user = User::_info();
$ui->assign('_user', $user);

switch ($action) {
    case 'voucher':
        $ui->assign('_system_menu', 'voucher');
        $ui->assign('_title', Lang::T('Order Voucher'));
        run_hook('customer_view_order'); #HOOK
        $ui->display('user-order.tpl');
        break;
    case 'history':
        $ui->assign('_system_menu', 'history');
        $query = ORM::for_table('tbl_payment_gateway')->where('username', $user['username'])->order_by_desc('id');
        $d = Paginator::findMany($query);
        $ui->assign('d', $d);
        $ui->assign('_title', Lang::T('Order History'));
        run_hook('customer_view_order_history'); #HOOK
        $ui->display('user-orderHistory.tpl');
        break;
    case 'balance':
        if (strpos($user['email'], '@') === false) {
            r2(U . 'accounts/profile', 'e', Lang::T("Please enter your email address"));
        }
        $ui->assign('_title', 'Top Up');
        $ui->assign('_system_menu', 'balance');
        $plans_balance = ORM::for_table('tbl_plans')->where('enabled', '1')->where('type', 'Balance')->where('prepaid', 'yes')->find_many();
        $ui->assign('plans_balance', $plans_balance);
        $ui->display('user-orderBalance.tpl');
        break;
    case 'package':
        if (strpos($user['email'], '@') === false) {
            r2(U . 'accounts/profile', 'e', Lang::T("Please enter your email address"));
        }
        $ui->assign('_title', 'Order Plan');
        $ui->assign('_system_menu', 'package');
        $account_type = $user['account_type'];
        if (empty($account_type)) {
            $account_type = 'Personal';
        }
        if (!empty($_SESSION['nux-router'])) {
            if ($_SESSION['nux-router'] == 'radius') {
                $radius_pppoe = ORM::for_table('tbl_plans')->where('plan_type', $account_type)->where('enabled', '1')->where('is_radius', 1)->where('type', 'PPPOE')->where('prepaid', 'yes')->find_many();
                $radius_hotspot = ORM::for_table('tbl_plans')->where('plan_type', $account_type)->where('enabled', '1')->where('is_radius', 1)->where('type', 'Hotspot')->where('prepaid', 'yes')->find_many();
            } else {
                $routers = ORM::for_table('tbl_routers')->where('id', $_SESSION['nux-router'])->find_many();
                $rs = [];
                foreach ($routers as $r) {
                    $rs[] = $r['name'];
                }
                $plans_pppoe = ORM::for_table('tbl_plans')->where('plan_type', $account_type)->where('enabled', '1')->where_in('routers', $rs)->where('is_radius', 0)->where('type', 'PPPOE')->where('prepaid', 'yes')->find_many();
                $plans_hotspot = ORM::for_table('tbl_plans')->where('plan_type', $account_type)->where('enabled', '1')->where_in('routers', $rs)->where('is_radius', 0)->where('type', 'Hotspot')->where('prepaid', 'yes')->find_many();
            }
        } else {
            $radius_pppoe = ORM::for_table('tbl_plans')->where('plan_type', $account_type)->where('enabled', '1')->where('is_radius', 1)->where('type', 'PPPOE')->where('prepaid', 'yes')->find_many();
            $radius_hotspot = ORM::for_table('tbl_plans')->where('plan_type', $account_type)->where('enabled', '1')->where('is_radius', 1)->where('type', 'Hotspot')->where('prepaid', 'yes')->find_many();

            $routers = ORM::for_table('tbl_routers')->find_many();
            $plans_pppoe = ORM::for_table('tbl_plans')->where('plan_type', $account_type)->where('enabled', '1')->where('is_radius', 0)->where('type', 'PPPOE')->where('prepaid', 'yes')->find_many();
            $plans_hotspot = ORM::for_table('tbl_plans')->where('plan_type', $account_type)->where('enabled', '1')->where('is_radius', 0)->where('type', 'Hotspot')->where('prepaid', 'yes')->find_many();
        }
        $ui->assign('routers', $routers);
        $ui->assign('radius_pppoe', $radius_pppoe);
        $ui->assign('radius_hotspot', $radius_hotspot);
        $ui->assign('plans_pppoe', $plans_pppoe);
        $ui->assign('plans_hotspot', $plans_hotspot);
        run_hook('customer_view_order_plan'); #HOOK
        $ui->display('user-orderPlan.tpl');
        break;
    case 'unpaid':
        $d = ORM::for_table('tbl_payment_gateway')
            ->where('username', $user['username'])
            ->where('status', 1)
            ->find_one();
        run_hook('custome
        r_find_unpaid'); #HOOK
        if ($d) {
            if (empty($d['pg_url_payment'])) {
                r2(U . "order/buy/" . $trx['routers_id'] . '/' . $trx['plan_id'], 'w', Lang::T("Checking payment"));
            } else {
                r2(U . "order/view/" . $d['id'] . '/check/', 's', Lang::T("You have unpaid transaction"));
            }
        } else {
            r2(U . "order/package/", 's', Lang::T("You have no unpaid transaction"));
        }
        break;
    case 'view':
        $trxid = $routes['2'];
        $trx = ORM::for_table('tbl_payment_gateway')
            ->where('username', $user['username'])
            ->find_one($trxid);
        run_hook('customer_view_payment'); #HOOK
        // jika tidak ditemukan, berarti punya orang lain
        if (empty($trx)) {
            r2(U . "order/package", 'w', Lang::T("Payment not found"));
        }
        // jika url kosong, balikin ke buy, kecuali cancel
        if (empty($trx['pg_url_payment']) && $routes['3'] != 'cancel') {
            r2(U . "order/buy/" . (($trx['routers_id'] == 0) ? $trx['routers'] : $trx['routers_id']) . '/' . $trx['plan_id'], 'w', Lang::T("Checking payment"));
        }
        if ($routes['3'] == 'check') {
            if (!file_exists($PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . $trx['gateway'] . '.php')) {
                r2(U . 'order/view/' . $trxid, 'e', Lang::T("No Payment Gateway Available"));
            }
            run_hook('customer_check_payment_status'); #HOOK
            include $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . $trx['gateway'] . '.php';
            call_user_func($trx['gateway'] . '_validate_config');
            call_user_func($trx['gateway'] . '_get_status', $trx, $user);
        } else if ($routes['3'] == 'cancel') {
            run_hook('customer_cancel_payment'); #HOOK
            $trx->pg_paid_response = '{}';
            $trx->status = 4;
            $trx->paid_date = date('Y-m-d H:i:s');
            $trx->save();
            $trx = ORM::for_table('tbl_payment_gateway')
                ->where('username', $user['username'])
                ->find_one($trxid);
        }
        if (empty($trx)) {
            r2(U . "order/package", 'e', Lang::T("Transaction Not found"));
        }

        $router = Mikrotik::info($trx['routers']);
        $plan = ORM::for_table('tbl_plans')->find_one($trx['plan_id']);
        $bandw = ORM::for_table('tbl_bandwidth')->find_one($plan['id_bw']);
        $invoice = ORM::for_table('tbl_transactions')->where("invoice",$trx['trx_invoice'])->find_one();
        $ui->assign('invoice', $invoice);
        $ui->assign('trx', $trx);
        $ui->assign('router', $router);
        $ui->assign('plan', $plan);
        $ui->assign('bandw', $bandw);
        $ui->assign('_title', 'TRX #' . $trxid);
        $ui->display('user-orderView.tpl');
        break;
    case 'pay':
        if ($config['enable_balance'] != 'yes') {
            r2(U . "order/package", 'e', Lang::T("Balance not enabled"));
        }
        if (!empty(App::getTokenValue($_GET['stoken']))) {
            r2(U . "voucher/invoice/");
            die();
        }
        $plan = ORM::for_table('tbl_plans')->where('enabled', '1')->find_one($routes['3']);
        if (empty($plan)) {
            r2(U . "order/package", 'e', Lang::T("Plan Not found"));
        }
        if (!$plan['enabled']) {
            r2(U . "home", 'e', 'Plan is not exists');
        }
        if ($routes['2'] == 'radius') {
            $router_name = 'radius';
        } else {
            $router_name = $plan['routers'];
        }
        list($bills, $add_cost) = User::getBills($id_customer);
        if ($plan && $plan['enabled'] && $user['balance'] >= $plan['price']) {
            if (Package::rechargeUser($user['id'], $router_name, $plan['id'], 'Customer', 'Balance')) {
                // if success, then get the balance
                Balance::min($user['id'], $plan['price'] + $add_cost);
                App::setToken($_GET['stoken'], "success");
                r2(U . "voucher/invoice/", 's', Lang::T("Success to buy package"));
            } else {
                r2(U . "order/package", 'e', Lang::T("Failed to buy package"));
                Message::sendTelegram("Buy Package with Balance Failed\n\n#u$c[username] #buy \n" . $plan['name_plan'] .
                    "\nRouter: " . $router_name .
                    "\nPrice: " . $p['price']);
            }
        } else {
            r2(U . "home", 'e', 'Plan is not exists');
        }
        break;
    case 'send':
        if ($config['enable_balance'] != 'yes') {
            r2(U . "order/package", 'e', Lang::T("Balance not enabled"));
        }
        $ui->assign('_title', Lang::T('Buy for friend'));
        $ui->assign('_system_menu', 'package');
        $plan = ORM::for_table('tbl_plans')->find_one($routes['3']);
        if (empty($plan)) {
            r2(U . "order/package", 'e', Lang::T("Plan Not found"));
        }
        if (!$plan['enabled']) {
            r2(U . "home", 'e', 'Plan is not exists');
        }
        if ($routes['2'] == 'radius') {
            $router_name = 'radius';
        } else {
            $router_name = $plan['routers'];
        }
        if (isset($_POST['send']) && $_POST['send'] == 'plan') {
            $target = ORM::for_table('tbl_customers')->where('username', _post('username'))->find_one();
            list($bills, $add_cost) = User::getBills($target['id']);
            if (!empty($add_cost)) {
                $ui->assign('bills', $bills);
                $ui->assign('add_cost', $add_cost);
                $plan['price'] += $add_cost;
            }
            if (!$target) {
                r2(U . 'home', 'd', Lang::T('Username not found'));
            }
            if ($user['balance'] < $plan['price']) {
                r2(U . 'home', 'd', Lang::T('insufficient balance'));
            }
            if ($user['username'] == $target['username']) {
                r2(U . "order/pay/$routes[2]/$routes[3]", 's', '^_^ v');
            }
            $active = ORM::for_table('tbl_user_recharges')
                ->where('username', _post('username'))
                ->where('status', 'on')
                ->find_one();

            if ($active && $active['plan_id'] != $plan['id']) {
                r2(U . "order/package", 'e', Lang::T("Target has active plan, different with current plant.") . " [ <b>$active[namebp]</b> ]");
            }
            $result = Package::rechargeUser($target['id'], $router_name, $plan['id'], $user['username'], 'Balance');
            if (!empty($result)) {
                // if success, then get the balance
                Balance::min($user['id'], $plan['price']);
                //sender
                $d = ORM::for_table('tbl_payment_gateway')->create();
                $d->username = $user['username'];
                $d->gateway = $target['username'];
                $d->plan_id = $plan['id'];
                $d->plan_name = $plan['name_plan'];
                $d->routers_id = $routes['2'];
                $d->routers = $router_name;
                $d->price = $plan['price'];
                $d->payment_method = "Balance";
                $d->payment_channel = "Send Plan";
                $d->created_date = date('Y-m-d H:i:s');
                $d->paid_date = date('Y-m-d H:i:s');
                $d->expired_date = date('Y-m-d H:i:s');
                $d->pg_url_payment = 'balance';
                $d->trx_invoice = $result;
                $d->status = 2;
                $d->save();
                $trx_id = $d->id();
                //receiver
                $d = ORM::for_table('tbl_payment_gateway')->create();
                $d->username = $target['username'];
                $d->gateway = $user['username'];
                $d->plan_id = $plan['id'];
                $d->plan_name = $plan['name_plan'];
                $d->routers_id = $routes['2'];
                $d->routers = $router_name;
                $d->price = $plan['price'];
                $d->payment_method = "Balance";
                $d->payment_channel = "Received Plan";
                $d->created_date = date('Y-m-d H:i:s');
                $d->paid_date = date('Y-m-d H:i:s');
                $d->expired_date = date('Y-m-d H:i:s');
                $d->pg_url_payment = 'balance';
                $d->trx_invoice = $result;
                $d->status = 2;
                $d->save();
                r2(U . "order/view/$trx_id", 's', Lang::T("Success to send package"));
            } else {
                r2(U . "order/package", 'e', Lang::T("Failed to Send package"));
                Message::sendTelegram("Send Package with Balance Failed\n\n#u$user[username] #send \n" . $plan['name_plan'] .
                    "\nRouter: " . $router_name .
                    "\nPrice: " . $plan['price']);
            }
        }
        $ui->assign('username', $_GET['u']);
        $ui->assign('router', $router_name);
        $ui->assign('plan', $plan);
        $ui->display('user-sendPlan.tpl');
        break;
    case 'gateway':
        $ui->assign('_title', Lang::T('Select Payment Gateway'));
        $ui->assign('_system_menu', 'package');
        if (strpos($user['email'], '@') === false) {
            r2(U . 'accounts/profile', 'e', Lang::T("Please enter your email address"));
        }
        $pgs = array_values(explode(',', $config['payment_gateway']));
        if (count($pgs) == 0) {
            sendTelegram("Payment Gateway not set, please set it in Settings");
            _log(Lang::T("Payment Gateway not set, please set it in Settings"));
            r2(U . "home", 'e', Lang::T("Failed to create Transaction.."));
        }
        if (count($pgs) > 1) {
            $ui->assign('pgs', $pgs);
            //$ui->assign('pgs', $pgs);
            $ui->assign('route2', $routes[2]);
            $ui->assign('route3', $routes[3]);

            //$ui->assign('plan', $plan);
            $ui->display('user-selectGateway.tpl');
            break;
        } else {
            if (empty($pgs[0])) {
                sendTelegram("Payment Gateway not set, please set it in Settings");
                _log(Lang::T("Payment Gateway not set, please set it in Settings"));
                r2(U . "home", 'e', Lang::T("Failed to create Transaction.."));
            } else {
                $_POST['gateway'] = $pgs[0];
            }
        }
    case 'buy':
        $gateway = _post('gateway');
        if (empty($gateway) && !empty($_SESSION['gateway'])) {
            $gateway = $_SESSION['gateway'];
        } else if (!empty($gateway)) {
            $_SESSION['gateway'] = $gateway;
        }
        if (empty($gateway)) {
            r2(U . 'order/gateway/' . $routes[2] . '/' . $routes[3], 'w', Lang::T("Please select Payment Gateway"));
        }
        run_hook('customer_buy_plan'); #HOOK
        include $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . $gateway . '.php';
        call_user_func($gateway . '_validate_config');

        if ($routes['2'] == 'radius') {
            $router['id'] = 0;
            $router['name'] = 'radius';
        } else if ($routes['2'] > 0) {
            $router = ORM::for_table('tbl_routers')->where('enabled', '1')->find_one($routes['2']);
        } else {
            $router['id'] = 0;
            $router['name'] = 'balance';
        }
        $plan = ORM::for_table('tbl_plans')->where('enabled', '1')->find_one($routes['3']);
        if (empty($router) || empty($plan)) {
            r2(U . "order/package", 'e', Lang::T("Plan Not found"));
        }
        $d = ORM::for_table('tbl_payment_gateway')
            ->where('username', $user['username'])
            ->where('status', 1)
            ->find_one();
        if ($d) {
            if ($d['pg_url_payment']) {
                r2(U . "order/view/" . $d['id'], 'w', Lang::T("You already have unpaid transaction, cancel it or pay it."));
            } else {
                if ($gateway == $d['gateway']) {
                    $id = $d['id'];
                } else {
                    $d->status = 4;
                    $d->save();
                }
            }
        }
        $add_cost = 0;
        if ($router['name'] != 'balance') {
            list($bills, $add_cost) = User::getBills($id_customer);
        }
        if (empty($id)) {
            $d = ORM::for_table('tbl_payment_gateway')->create();
            $d->username = $user['username'];
            $d->gateway = $gateway;
            $d->plan_id = $plan['id'];
            $d->plan_name = $plan['name_plan'];
            $d->routers_id = $router['id'];
            $d->routers = $router['name'];
            if ($plan['validity_unit'] == 'Period') {
                // Postpaid price from field
                $add_inv = User::getAttribute("Invoice", $id_customer);
                if (empty($add_inv) or $add_inv == 0) {
                    $d->price = ($plan['price'] + $add_cost);
                } else {
                    $d->price = ($add_inv + $add_cost);
                }
            } else {
                $d->price = ($plan['price'] + $add_cost);
            }
            //$d->price = ($plan['price'] + $add_cost);
            $d->created_date = date('Y-m-d H:i:s');
            $d->status = 1;
            $d->save();
            $id = $d->id();
        } else {
            $d->username = $user['username'];
            $d->gateway = $gateway;
            $d->plan_id = $plan['id'];
            $d->plan_name = $plan['name_plan'];
            $d->routers_id = $router['id'];
            $d->routers = $router['name'];
            if ($plan['validity_unit'] == 'Period') {
                // Postpaid price from field
                $add_inv = User::getAttribute("Invoice", $id_customer);
                if (empty($add_inv) or $add_inv == 0) {
                    $d->price = ($plan['price'] + $add_cost);
                } else {
                    $d->price = ($add_inv + $add_cost);
                }
            } else {
                $d->price = ($plan['price'] + $add_cost);
            }
            //$d->price = ($plan['price'] + $add_cost);
            $d->created_date = date('Y-m-d H:i:s');
            $d->status = 1;
            $d->save();
        }
        if (!$id) {
            r2(U . "order/package/" . $d['id'], 'e', Lang::T("Failed to create Transaction.."));
        } else {
            call_user_func($gateway . '_create_transaction', $d, $user);
        }
        break;
    default:
        r2(U . "order/package/", 's', '');
}
