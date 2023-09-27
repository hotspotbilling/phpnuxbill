<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 **/
_auth();
$action = $routes['1'];
$user = User::_info();
$ui->assign('_user', $user);

switch ($action) {
    case 'voucher':
        $ui->assign('_system_menu', 'voucher');
        $ui->assign('_title', $_L['Order_Voucher']);
        run_hook('customer_view_order'); #HOOK
        $ui->display('user-order.tpl');
        break;
    case 'history':
        $ui->assign('_system_menu', 'history');
        $paginator = Paginator::bootstrap('tbl_payment_gateway', 'username', $user['username']);
        $d = ORM::for_table('tbl_payment_gateway')
            ->where('username', $user['username'])
            ->order_by_desc('id')
            ->offset($paginator['startpoint'])->limit($paginator['limit'])
            ->find_many();
        $ui->assign('paginator', $paginator);
        $ui->assign('d', $d);
        $ui->assign('_title', Lang::T('Order History'));
        run_hook('customer_view_order_history'); #HOOK
        $ui->display('user-orderHistory.tpl');
        break;
    case 'package':
        if (strpos($user['email'], '@') === false) {
            r2(U . 'accounts/profile', 'e', Lang::T("Please enter your email address"));
        }
        $ui->assign('_title', 'Order Plan');
        $ui->assign('_system_menu', 'package');
        if(!empty($_SESSION['nux-router'])){
            $routers = ORM::for_table('tbl_routers')->where('id',$_SESSION['nux-router'])->find_many();
            $rs = [];
            foreach($routers as $r){
                $rs[] = $r['name'];
            }
            $plans_pppoe = ORM::for_table('tbl_plans')->where('enabled', '1')->where_in('routers', $rs)->where('type', 'PPPOE')->find_many();
            $plans_hotspot = ORM::for_table('tbl_plans')->where('enabled', '1')->where_in('routers', $rs)->where('type', 'Hotspot')->find_many();
        }else{
            $routers = ORM::for_table('tbl_routers')->find_many();
            $plans_pppoe = ORM::for_table('tbl_plans')->where('enabled', '1')->where('type', 'PPPOE')->find_many();
            $plans_hotspot = ORM::for_table('tbl_plans')->where('enabled', '1')->where('type', 'Hotspot')->find_many();
        }
        $plans_balance = ORM::for_table('tbl_plans')->where('enabled', '1')->where('type', 'Balance')->find_many();
        $ui->assign('routers', $routers);
        $ui->assign('plans_pppoe', $plans_pppoe);
        $ui->assign('plans_hotspot', $plans_hotspot);
        $ui->assign('plans_balance', $plans_balance);
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
            r2(U . "order/package", 'e', Lang::T("Transaction Not found"));
        }
        $router = ORM::for_table('tbl_routers')->find_one($trx['routers_id']);
        $plan = ORM::for_table('tbl_plans')->find_one($trx['plan_id']);
        $bandw = ORM::for_table('tbl_bandwidth')->find_one($plan['id_bw']);
        $ui->assign('trx', $trx);
        $ui->assign('router', $router);
        $ui->assign('plan', $plan);
        $ui->assign('bandw', $bandw);
        $ui->assign('_title', 'TRX #' . $trxid);
        $ui->display('user-orderView.tpl');
        break;
    case 'pay':
        if ($_c['enable_balance'] != 'yes' && $config['allow_balance_transfer'] != 'yes') {
            r2(U . "order/package", 'e', Lang::T("Balance not enabled"));
        }
        $plan = ORM::for_table('tbl_plans')->where('enabled', '1')->find_one($routes['3']);
        $router = ORM::for_table('tbl_routers')->where('enabled', '1')->find_one($routes['2']);
        if (empty($router) || empty($plan)) {
            r2(U . "order/package", 'e', Lang::T("Plan Not found"));
        }
        if ($plan && $plan['enabled'] && $user['balance'] >= $plan['price']) {
            if (Package::rechargeUser($user['id'], $plan['routers'], $plan['id'], 'Customer', 'Balance')) {
                // if success, then get the balance
                Balance::min($user['id'], $plan['price']);
                r2(U . "home", 's', Lang::T("Success to buy package"));
            } else {
                r2(U . "order/package", 'e', Lang::T("Failed to buy package"));
                Message::sendTelegram("Buy Package with Balance Failed\n\n#u$c[username] #buy \n" . $plan['name_plan'] .
                    "\nRouter: " . $router_name .
                    "\nPrice: " . $p['price']);
            }
        } else {
            echo "no renewall | plan enabled: $p[enabled] | User balance: $c[balance] | price $p[price]\n";
        }
        break;
    case 'send':
        if ($_c['enable_balance'] != 'yes') {
            r2(U . "order/package", 'e', Lang::T("Balance not enabled"));
        }
        $ui->assign('_title', Lang::T('Buy for friend'));
        $ui->assign('_system_menu', 'package');
        $plan = ORM::for_table('tbl_plans')->find_one($routes['3']);
        if (empty($plan)) {
            r2(U . "order/package", 'e', Lang::T("Plan Not found"));
        }
        if (isset($_POST['send']) && $_POST['send'] == 'plan') {
            $target = ORM::for_table('tbl_customers')->where('username', _post('username'))->find_one();
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
                r2(U . "order/package", 'e', Lang::T("Target has active plan, different with current plant.")." [ <b>$active[namebp]</b> ]");
            }
            if (Package::rechargeUser($target['id'], $plan['routers'], $plan['id'], $user['fullname'], 'Balance')) {
                // if success, then get the balance
                Balance::min($user['id'], $plan['price']);
                //sender
                $d = ORM::for_table('tbl_payment_gateway')->create();
                $d->username = $user['username'];
                $d->gateway = $target['username'];
                $d->plan_id = $plan['id'];
                $d->plan_name = $plan['name_plan'];
                $d->routers_id = $routes['2'];
                $d->routers = $plan['routers'];
                $d->price = $plan['price'];
                $d->payment_method = "Balance";
                $d->payment_channel = "Send Plan";
                $d->created_date = date('Y-m-d H:i:s');
                $d->paid_date = date('Y-m-d H:i:s');
                $d->expired_date = date('Y-m-d H:i:s');
                $d->pg_url_payment = 'balance';
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
                $d->routers = $plan['routers'];
                $d->price = $plan['price'];
                $d->payment_method = "Balance";
                $d->payment_channel = "Received Plan";
                $d->created_date = date('Y-m-d H:i:s');
                $d->paid_date = date('Y-m-d H:i:s');
                $d->expired_date = date('Y-m-d H:i:s');
                $d->pg_url_payment = 'balance';
                $d->status = 2;
                $d->save();
                r2(U . "order/view/$trx_id", 's', Lang::T("Success to send package"));
            } else {
                r2(U . "order/package", 'e', Lang::T("Failed to Send package"));
                Message::sendTelegram("Send Package with Balance Failed\n\n#u$user[username] #send \n" . $plan['name_plan'] .
                    "\nRouter: " . $plan['routers'] .
                    "\nPrice: " . $plan['price']);
            }
        }

        $ui->assign('username', $_GET['u']);
        $ui->assign('router', $router);
        $ui->assign('plan', $plan);
        $ui->display('user-sendPlan.tpl');
        break;
    case 'buy':
        if (strpos($user['email'], '@') === false) {
            r2(U . 'accounts/profile', 'e', Lang::T("Please enter your email address"));
        }
        if ($config['payment_gateway'] == 'none') {
            r2(U . 'home', 'e', Lang::T("No Payment Gateway Available"));
        }
        if (!file_exists('system/paymentgateway/' . $config['payment_gateway'] . '.php')) {
            r2(U . 'home', 'e', Lang::T("No Payment Gateway Available"));
        }
        run_hook('customer_buy_plan'); #HOOK
        include 'system/paymentgateway/' . $config['payment_gateway'] . '.php';
        call_user_func($config['payment_gateway'] . '_validate_config');
        if ($routes['2'] > 0) {
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
        r2(U . "order/package/", 's', '');
}
