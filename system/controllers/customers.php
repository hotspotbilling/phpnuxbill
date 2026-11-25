<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Customer'));
$ui->assign('_system_menu', 'customers');

$action = $routes['1'];
$ui->assign('_admin', $admin);

if (empty($action)) {
    $action = 'list';
}

$leafletpickerHeader = <<<EOT
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css">
EOT;

switch ($action) {
    case 'csv':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $csrf_token = _req('token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('customers'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }

        $cs = ORM::for_table('tbl_customers')
            ->select('tbl_customers.id', 'id')
            ->select('tbl_customers.username', 'username')
            ->select('fullname')
            ->select('address')
            ->select('phonenumber')
            ->select('email')
            ->select('balance')
            ->select('service_type')
            ->order_by_asc('tbl_customers.id')
            ->find_array();

        $h = false;
        set_time_limit(-1);
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header("Content-type: text/csv");
        header('Content-Disposition: attachment;filename="phpnuxbill_customers_' . date('Y-m-d_H_i') . '.csv"');
        header('Content-Transfer-Encoding: binary');

        $headers = [
            'id',
            'username',
            'fullname',
            'address',
            'phonenumber',
            'email',
            'balance',
            'service_type',
        ];

        if (!$h) {
            echo '"' . implode('","', $headers) . "\"\n";
            $h = true;
        }

        foreach ($cs as $c) {
            $row = [
                $c['id'],
                $c['username'],
                $c['fullname'],
                $c['address'],
                $c['phonenumber'],
                $c['email'],
                $c['balance'],
                $c['service_type'],
            ];
            echo '"' . implode('","', $row) . "\"\n";
        }
        break;
        //case csv-prepaid can be moved later to (plan.php)  php file dealing with prepaid users
    case 'csv-prepaid':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }

        $cs = ORM::for_table('tbl_customers')
            ->select('tbl_customers.id', 'id')
            ->select('tbl_customers.username', 'username')
            ->select('fullname')
            ->select('address')
            ->select('phonenumber')
            ->select('email')
            ->select('balance')
            ->select('service_type')
            ->select('namebp')
            ->select('routers')
            ->select('status')
            ->select('method', 'Payment')
            ->left_outer_join('tbl_user_recharges', array('tbl_customers.id', '=', 'tbl_user_recharges.customer_id'))
            ->order_by_asc('tbl_customers.id')
            ->find_array();

        $h = false;
        set_time_limit(-1);
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header("Content-type: text/csv");
        header('Content-Disposition: attachment;filename="phpnuxbill_prepaid_users' . date('Y-m-d_H_i') . '.csv"');
        header('Content-Transfer-Encoding: binary');

        $headers = [
            'id',
            'username',
            'fullname',
            'address',
            'phonenumber',
            'email',
            'balance',
            'service_type',
            'namebp',
            'routers',
            'status',
            'Payment'
        ];

        if (!$h) {
            echo '"' . implode('","', $headers) . "\"\n";
            $h = true;
        }

        foreach ($cs as $c) {
            $row = [
                $c['id'],
                $c['username'],
                $c['fullname'],
                $c['address'],
                $c['phonenumber'],
                $c['email'],
                $c['balance'],
                $c['service_type'],
                $c['namebp'],
                $c['routers'],
                $c['status'],
                $c['Payment']
            ];
            echo '"' . implode('","', $row) . "\"\n";
        }
        break;
    case 'add':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $ui->assign('xheader', $leafletpickerHeader);
        run_hook('view_add_customer'); #HOOK
        $ui->assign('csrf_token',  Csrf::generateAndStoreToken());
        $ui->display('admin/customers/add.tpl');
        break;
    case 'recharge':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $id_customer = $routes['2'];
        $plan_id = $routes['3'];
        $csrf_token = _req('token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('customers/view/') . $id_customer, 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $b = ORM::for_table('tbl_user_recharges')->where('customer_id', $id_customer)->where('plan_id', $plan_id)->find_one();
        if ($b) {
            $gateway = 'Recharge';
            $channel = $admin['fullname'];
            $cust = User::_info($id_customer);
            $plan = ORM::for_table('tbl_plans')->find_one($b['plan_id']);
			$add_inv = User::getAttribute("Invoice", $id_customer);
			if (!empty($add_inv)) {
				$plan['price'] = $add_inv;
			}
            $tax_enable = isset($config['enable_tax']) ? $config['enable_tax'] : 'no';
            $tax_rate_setting = isset($config['tax_rate']) ? $config['tax_rate'] : null;
            $custom_tax_rate = isset($config['custom_tax_rate']) ? (float)$config['custom_tax_rate'] : null;
            if ($tax_rate_setting === 'custom') {
                $tax_rate = $custom_tax_rate;
            } else {
                $tax_rate = $tax_rate_setting;
            }
            if ($tax_enable === 'yes') {
                $tax = Package::tax($plan['price'], $tax_rate);
            } else {
                $tax = 0;
            }
            list($bills, $add_cost) = User::getBills($id_customer);
            if ($using == 'balance' && $config['enable_balance'] == 'yes') {
                if (!$cust) {
                    r2(getUrl('plan/recharge'), 'e', Lang::T('Customer not found'));
                }
                if (!$plan) {
                    r2(getUrl('plan/recharge'), 'e', Lang::T('Plan not found'));
                }
                if ($cust['balance'] < ($plan['price'] + $add_cost + $tax)) {
                    r2(getUrl('plan/recharge'), 'e', Lang::T('insufficient balance'));
                }
                $gateway = 'Recharge Balance';
            }
            if ($using == 'zero') {
                $zero = 1;
                $gateway = 'Recharge Zero';
            }
            $usings = explode(',', $config['payment_usings']);
            $usings = array_filter(array_unique($usings));
            if (count($usings) == 0) {
                $usings[] = Lang::T('Cash');
            }
            $abills = User::getAttributes("Bill");
            if ($tax_enable === 'yes') {
                $ui->assign('tax', $tax);
            }
            $ui->assign('usings', $usings);
            $ui->assign('abills', $abills);
            $ui->assign('bills', $bills);
            $ui->assign('add_cost', $add_cost);
            $ui->assign('cust', $cust);
            $ui->assign('gateway', $gateway);
            $ui->assign('channel', $channel);
            $ui->assign('server', $b['routers']);
            $ui->assign('plan', $plan);
			$ui->assign('add_inv', $add_inv);
            $ui->assign('csrf_token',  Csrf::generateAndStoreToken());
            $ui->display('admin/plan/recharge-confirm.tpl');
        } else {
            r2(getUrl('customers/view/') . $id_customer, 'e', 'Cannot find active plan');
        }
        break;
    case 'deactivate':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $id_customer = $routes['2'];
        $plan_id = $routes['3'];
        $csrf_token = _req('token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('customers/view/') . $id_customer, 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $b = ORM::for_table('tbl_user_recharges')->where('customer_id', $id_customer)->where('plan_id', $plan_id)->find_one();
        if ($b) {
            $p = ORM::for_table('tbl_plans')->where('id', $b['plan_id'])->find_one();
            if ($p) {
                $p = ORM::for_table('tbl_plans')->where('id', $b['plan_id'])->find_one();
                $c = User::_info($id_customer);
                $dvc = Package::getDevice($p);
                if (strcasecmp((string) $_app_stage, 'demo') !== 0) {
                    if (file_exists($dvc)) {
                        require_once $dvc;
                        $partial = null;
                        try {
                            $r = (new $p['device'])->remove_customer($c, $p);
                            if (is_string($r) && $r !== '') {
                                $partial = $r; // MikrotikPppoe returns its partial result
                            }
                        } catch (Exception $e) {
                            // code 207: a later step was refused, and not every driver has taken the access off by then (a static
                            // address that could not be put on the Expired list is still open): the recharge is not marked off
                            if ($e->getCode() !== 207) {
                                throw $e;
                            }
                            $partial = $e->getMessage();
                        }
                        if ($partial !== null) {
                            try {
                                _log('[' . $c['username'] . '] deactivate incomplete: ' . $partial, 'Router');
                            } catch (Throwable $logErr) { // the warning below must reach the admin whatever the log does
                            }
                            r2(getUrl('customers/view/') . $id_customer, 'w', Lang::T('The router did not finish taking the access off, so the plan was not marked as expired') . ': ' . htmlspecialchars($partial, ENT_QUOTES));
                        }
                    } else {
                        // live mode: the access cannot be taken off without the driver, so the recharge is not marked off
                        r2(getUrl('customers/view/') . $id_customer, 'e', Lang::T('Devices Not Found') . ': ' . htmlspecialchars((string) $p['device'], ENT_QUOTES));
                    }
                }
                $b->status = 'off';
                $b->expiration = date('Y-m-d');
                $b->time = date('H:i:s');
                $b->save();
                _log('Admin ' . $admin['username'] . ' Deactivate ' . $b['namebp'] . ' for ' . $b['username'], 'User', $b['customer_id']);
                Message::sendTelegram('Admin ' . $admin['username'] . ' Deactivate ' . $b['namebp'] . ' for u' . $b['username']);
                r2(getUrl('customers/view/') . $id_customer, 's', 'Success deactivate customer to Mikrotik');
            }
        }
        r2(getUrl('customers/view/') . $id_customer, 'e', 'Cannot find active plan');
        break;
    case 'sync':
        $id_customer = $routes['2'];
        $csrf_token = _req('token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('customers/view/') . $id_customer, 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $bs = ORM::for_table('tbl_user_recharges')->where('customer_id', $id_customer)->where('status', 'on')->findMany();
        if ($bs) {
            $routers = [];
            $failedSync = [];
            $partialSync = []; // plans whose device did the main work but reported a step left over (a result string, or code 207)
            foreach ($bs as $b) {
                if (strtotime($b['expiration'] . ' ' . $b['time']) <= time()) {
                    continue; // expired (the cron may still be retrying a clean-up): access is not sent back
                }
                $c = ORM::for_table('tbl_customers')->find_one($id_customer);
                $p = ORM::for_table('tbl_plans')->where('id', $b['plan_id'])->find_one();
                if ($p && $p['device'] === 'Voucher') {
                    continue; // a voucher plan has no account on a device: add_customer() would issue a new voucher
                }
                if ($p) {
                    $dvc = Package::getDevice($p);
                    if (strcasecmp((string) $_app_stage, 'demo') !== 0) {
                        if (file_exists($dvc)) {
                            require_once $dvc;
                            try {
                                if (method_exists($p['device'], 'sync_customer')) {
                                    $syncRes = (new $p['device'])->sync_customer($c, $p);
                                } else {
                                    $syncRes = (new $p['device'])->add_customer($c, $p);
                                }
                                if (is_string($syncRes) && $syncRes !== '') {
                                    $partialSync[] = $p['name_plan'] . ': ' . $syncRes;
                                }
                            } catch (Exception $e) {
                                // code 207: saved on the router, a later step refused: said for this plan, and the others still go
                                if ($e->getCode() !== 207) {
                                    throw $e;
                                }
                                $partialSync[] = $p['name_plan'] . ': ' . $e->getMessage();
                            }
                        } else {
                            $failedSync[] = $b['routers']; // no driver file: not synced, and not reported as done
                            continue;
                        }
                    }
                    $routers[] = $b['routers']; // only once the driver has finished
                }
            }
            if ($partialSync) {
                try {
                    _log('[' . $c['username'] . '] sync incomplete: ' . implode(' | ', $partialSync), 'Router');
                } catch (Throwable $ignored) {
                }
            }
            if ($failedSync) {
                r2(getUrl('customers/view/') . $id_customer, 'e', Lang::T('Devices Not Found') . ': ' . htmlspecialchars(implode(', ', array_unique($failedSync)), ENT_QUOTES)
                    . ($routers ? '. ' . Lang::T('Synced') . ': ' . htmlspecialchars(implode(', ', $routers), ENT_QUOTES) : ''));
            }
            if ($partialSync && $routers) {
                r2(getUrl('customers/view/') . $id_customer, 'w', 'Sync to ' . implode(", ", $routers) . '. ' . Lang::T('The router update is incomplete for') . ': ' . htmlspecialchars(implode('; ', $partialSync), ENT_QUOTES));
            }
            if ($routers) { // nothing applied (every recharge expired or without a plan) is no success
                r2(getUrl('customers/view/') . $id_customer, 's', 'Sync success to ' . implode(", ", $routers));
            }
        }
        r2(getUrl('customers/view/') . $id_customer, 'e', 'Cannot find active plan');
        break;
    case 'login':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $id = $routes['2'];
        $csrf_token = _req('token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('customers/view/') . $id, 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $customer = ORM::for_table('tbl_customers')->find_one($id);
        if ($customer) {
            $_SESSION['uid'] = $id;
            $_SESSION['uid_pv'] = Password::fingerprint($customer['password']); // this session is for this customer
            $_SESSION['uid_pv_id'] = $customer['id'];
            User::setCookie($id);
            _alert("You are logging in as $customer[fullname],<br>don't logout just close tab.", 'info', "home", 10);
        }
        _alert(Lang::T('Customer not found'), 'danger', "customers");
        break;
    case 'viewu':
        $customer = ORM::for_table('tbl_customers')->where('username', $routes['2'])->find_one();
    case 'view':
        $id = $routes['2'];
        run_hook('view_customer'); #HOOK
        if (!$customer) {
            $customer = ORM::for_table('tbl_customers')->find_one($id);
        }
        if ($customer) {
            // Fetch the Customers Attributes values from the tbl_customer_custom_fields table
            $customFields = ORM::for_table('tbl_customers_fields')
                ->where('customer_id', $customer['id'])
                ->find_many();
            $v = $routes['3'];
            if (empty($v)) {
                $v = 'activation';
            }
            switch ($v) {
                case 'order':
                    $v = 'order';
                    $query = ORM::for_table('tbl_payment_gateway')->where('user_id', $customer['id'])->order_by_desc('id');
                    $order = Paginator::findMany($query);

                    if (empty($order) || $order < 5) {
                        $query = ORM::for_table('tbl_payment_gateway')->where('username', $customer['username'])->order_by_desc('id');
                        $order = Paginator::findMany($query);
                    }

                    $ui->assign('order', $order);
                    break;
                case 'activation':
                    $query = ORM::for_table('tbl_transactions')->where('user_id', $customer['id'])->order_by_desc('id');
                    $activation = Paginator::findMany($query);

                    if (empty($activation) || $activation < 5) {
                        $query = ORM::for_table('tbl_transactions')->where('username', $customer['username'])->order_by_desc('id');
                        $activation = Paginator::findMany($query);
                    }

                    $ui->assign('activation', $activation);
                    break;
            }
            $ui->assign('packages', User::_billing($customer['id']));
            $ui->assign('v', $v);
            $ui->assign('d', $customer);
            $ui->assign('customFields', $customFields);
            $ui->assign('xheader', $leafletpickerHeader);
            $ui->assign('csrf_token',  Csrf::generateAndStoreToken());
            $ui->display('admin/customers/view.tpl');
        } else {
            r2(getUrl('customers/list'), 'e', Lang::T('Account Not Found'));
        }
        break;
    case 'edit':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $id = $routes['2'];
        run_hook('edit_customer'); #HOOK
        $d = ORM::for_table('tbl_customers')->find_one($id);
        // Fetch the Customers Attributes values from the tbl_customers_fields table
        $customFields = ORM::for_table('tbl_customers_fields')
            ->where('customer_id', $id)
            ->find_many();
        if ($d) {
            if (isset($routes['3']) && $routes['3'] == 'deletePhoto') {
                if ($d['photo'] != '' && strpos($d['photo'], 'default') === false) {
                    if (file_exists($UPLOAD_PATH . $d['photo']) && strpos($d['photo'], 'default') === false) {
                        unlink($UPLOAD_PATH . $d['photo']);
                        if (file_exists($UPLOAD_PATH . $d['photo'] . '.thumb.jpg')) {
                            unlink($UPLOAD_PATH . $d['photo'] . '.thumb.jpg');
                        }
                    }
                    $d->photo = '/user.default.jpg';
                    $d->save();
                    $ui->assign('notify_t', 's');
                    $ui->assign('notify', 'You have successfully deleted the photo');
                } else {
                    $ui->assign('notify_t', 'e');
                    $ui->assign('notify', 'No photo found to delete');
                }
            }
            $ui->assign('d', $d);
            $ui->assign('statuses', ORM::for_table('tbl_customers')->getEnum("status"));
            $ui->assign('customFields', $customFields);
            $ui->assign('xheader', $leafletpickerHeader);
            $ui->assign('pppoe_password_seen', hash('sha256', (string) $d['pppoe_password']));
            $ui->assign('password_seen', hash('sha256', (string) $d['password']));
            $ui->assign('csrf_token',  Csrf::generateAndStoreToken());
            $ui->display('admin/customers/edit.tpl');
        } else {
            r2(getUrl('customers/list'), 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'delete':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $id = $routes['2'];
        $csrf_token = _req('token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('customers/view/') . $id, 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        run_hook('delete_customer'); #HOOK
        $c = ORM::for_table('tbl_customers')->find_one($id);
        if ($c) {
            //Delete active package (the customer's attributes, the router port numbers among them, go only after every router is cleaned up)
            $turs = ORM::for_table('tbl_user_recharges')->where('username', $c['username'])->find_many();
            $alreadyRemoved = []; // plans whose access was taken off and recharge row deleted before a later one stopped the delete
            foreach ($turs as $tur) {
                $p = ORM::for_table('tbl_plans')->find_one($tur['plan_id']);
                if ($p) {
                    $dvc = Package::getDevice($p);
                    if (strcasecmp((string) $_app_stage, 'demo') !== 0) {
                        if (file_exists($dvc)) {
                            require_once $dvc;
                            $p['plan_expired'] = 0;
                            $partial = null;
                            try {
                                $r = (new $p['device'])->remove_customer($c, $p);
                                if (is_string($r) && $r !== '') {
                                    $partial = $r;
                                }
                            } catch (Exception $e) {
                                // code 207: not every driver has taken the access off by then: the customer is kept until it is
                                if ($e->getCode() !== 207) {
                                    throw $e;
                                }
                                $partial = $e->getMessage();
                            }
                            if ($partial !== null) {
                                try {
                                    _log('[' . $c['username'] . '] delete incomplete: ' . $partial, 'Router');
                                } catch (Throwable $logErr) {
                                }
                                r2(getUrl('customers/view/') . $id, 'e', Lang::T('The router did not finish taking the access off, so the customer was not deleted') . ': ' . htmlspecialchars((string) $p['name_plan'] . ' - ' . $partial, ENT_QUOTES)
                                    . ($alreadyRemoved ? '. ' . Lang::T('Already removed') . ': ' . htmlspecialchars(implode(', ', $alreadyRemoved), ENT_QUOTES) : ''));
                            }
                        } else {
                            // nothing is deleted: the access could not be taken off the router, and the cleanup can be tried again
                            r2(getUrl('customers/view/') . $id, 'e', Lang::T('Devices Not Found') . ': ' . htmlspecialchars((string) $p['name_plan'] . ' (' . (string) $p['device'] . ')', ENT_QUOTES) . '. ' . Lang::T('The customer was not deleted')
                                . ($alreadyRemoved ? '. ' . Lang::T('Already removed') . ': ' . htmlspecialchars(implode(', ', $alreadyRemoved), ENT_QUOTES) : ''));
                        }
                    }
                }
                try {
                    $tur->delete();
                    $alreadyRemoved[] = $tur['namebp'] . ' (' . $tur['routers'] . ')';
                } catch (Exception $e) {
                }
            }
            // Delete the associated Customers Attributes records from tbl_customer_custom_fields table
            ORM::for_table('tbl_customers_fields')->where('customer_id', $id)->delete_many();
            try {
                $c->delete();
            } catch (Exception $e) {
            }
            if (class_exists('RedisCache')) {
                RedisCache::forgetRadiusCustomer($c['username']);
                RedisCache::forgetRadiusCustomer($c['pppoe_username']);
            }
            r2(getUrl('customers/list'), 's', Lang::T('User deleted Successfully'));
        }
        break;

    case 'add-post':

        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('customers/add'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $username = alphanumeric(_post('username'), ":+_.@-");
        $fullname = _post('fullname');
        $password = trim(_post('password'));
        $pppoe_username = trim(_post('pppoe_username'));
        $pppoe_password = trim(_post('pppoe_password'));
        $pppoe_ip = trim(_post('pppoe_ip'));
        $email = _post('email');
        $address = _post('address');
        $phonenumber = _post('phonenumber');
        $service_type = _post('service_type');
        $account_type = _post('account_type');
        $coordinates = _post('coordinates');
        //post Customers Attributes
        $custom_field_names = (array) $_POST['custom_field_name'];
        $custom_field_values = (array) $_POST['custom_field_value'];
        //additional information
        $city = _post('city');
        $district = _post('district');
        $state = _post('state');
        $zip = _post('zip');

        run_hook('add_customer'); #HOOK
        $msg = '';
        if (Validator::Length($username, 55, 2) == false) {
            $msg .= 'Username should be between 3 to 54 characters' . '<br>';
        }
        if (Validator::Length($fullname, 36, 1) == false) {
            $msg .= 'Full Name should be between 2 to 25 characters' . '<br>';
        }
        if (!Validator::Length($password, 36, 2)) {
            $msg .= 'Password should be between 3 to 35 characters' . '<br>';
        }

        $d = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
        if ($d) {
            $msg .= Lang::T('Account already axist') . '<br>';
        }
        if ($msg == '') {
            $d = ORM::for_table('tbl_customers')->create();
            $d->username = $username;
            $d->password = $password;
            $d->pppoe_username = $pppoe_username;
            $d->pppoe_password = $pppoe_password;
            $d->pppoe_ip = $pppoe_ip;
            $d->email = $email;
            $d->account_type = $account_type;
            $d->fullname = $fullname;
            $d->address = $address;
            $d->created_by = $admin['id'];
            $d->phonenumber = Lang::phoneFormat($phonenumber);
            $d->service_type = $service_type;
            $d->coordinates = $coordinates;
            $d->city = $city;
            $d->district = $district;
            $d->state = $state;
            $d->zip = $zip;
            $d->save();

            // Retrieve the customer ID of the newly created customer
            $customerId = $d->id();
            // Save Customers Attributes details
            if (!empty($custom_field_names) && !empty($custom_field_values)) {
                $totalFields = min(count($custom_field_names), count($custom_field_values));
                for ($i = 0; $i < $totalFields; $i++) {
                    $name = $custom_field_names[$i];
                    $value = $custom_field_values[$i];

                    if (!empty($name)) {
                        $customField = ORM::for_table('tbl_customers_fields')->create();
                        $customField->customer_id = $customerId;
                        $customField->field_name = $name;
                        $customField->field_value = $value;
                        $customField->save();
                    }
                }
            }

            // Send welcome message
            if (isset($_POST['send_welcome_message']) && $_POST['send_welcome_message'] == true) {
                $welcomeMessage = Lang::getNotifText('welcome_message');
                $welcomeMessage = str_replace('[[company]]', $config['CompanyName'], $welcomeMessage);
                $welcomeMessage = str_replace('[[name]]', $d['fullname'], $welcomeMessage);
                $welcomeMessage = str_replace('[[username]]', $d['username'], $welcomeMessage);
                $welcomeMessage = str_replace('[[password]]', $d['password'], $welcomeMessage);
                $welcomeMessage = str_replace('[[url]]', APP_URL . '/?_route=login', $welcomeMessage);

                $subject = "Welcome to " . $config['CompanyName'];

                $channels = [
                    'sms' => [
                        'enabled' => isset($_POST['sms']),
                        'method' => 'sendSMS',
                        'args' => [$d['phonenumber'], $welcomeMessage]
                    ],
                    'whatsapp' => [
                        'enabled' => isset($_POST['wa']),
                        'method' => 'sendWhatsapp',
                        'args' => [$d['phonenumber'], $welcomeMessage]
                    ],
                    'email' => [
                        'enabled' => isset($_POST['mail']),
                        'method' => 'Message::sendEmail',
                        'args' => [$d['email'], $subject, $welcomeMessage, $d['email']]
                    ],
                    'inbox' => [
                        'enabled' => isset($_POST['inbox']),
                        'method' => 'Message::addToInbox',
                        'args' => $d['id'], $subject, $welcomeMessage, $from = 'Admin'
                    ],
                ];

                foreach ($channels as $channel => $message) {
                    if ($message['enabled']) {
                        try {
                            call_user_func_array($message['method'], $message['args']);
                        } catch (Exception $e) {
                            // Log the error and handle the failure
                            _log("Failed to send welcome message via $channel: " . $e->getMessage());
                        }
                    }
                }
            }
            r2(getUrl('customers/list'), 's', Lang::T('Account Created Successfully'));
        } else {
            r2(getUrl('customers/add'), 'e', $msg);
        }
        break;

    case 'edit-post':
        $id = _post('id');
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('customers/edit/') . $id, 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $username = alphanumeric(_post('username'), ":+_.@-");
        $fullname = _post('fullname');
        $account_type = _post('account_type');
        $password = trim(_post('password'));
        // a form that still holds the portal password it was opened with did not change it (it may have been changed since, by
        // the customer): only a different value is a replacement. Compared as posted (untrimmed): the hash was made of the saved value
        $pwSeen = (string) _post('password_seen');
        $passwordRaw = (string) ($_POST['password'] ?? '');
        if ($pwSeen !== '' && $password !== '' && (hash_equals($pwSeen, hash('sha256', $passwordRaw)) || hash_equals($pwSeen, hash('sha256', $password)))) {
            $password = '';
        }
        $pppoe_username = trim(_post('pppoe_username'));
        $pppoe_password = trim(_post('pppoe_password'));
        $pppoe_ip = trim(_post('pppoe_ip'));
        $email = _post('email');
        $address = _post('address');
        $phonenumber = Lang::phoneFormat(_post('phonenumber'));
        $service_type = _post('service_type');
        $coordinates = _post('coordinates');
        $status = _post('status');
        //additional information
        $city = _post('city');
        $district = _post('district');
        $state = _post('state');
        $zip = _post('zip');
        run_hook('edit_customer'); #HOOK
        $msg = '';
        if (Validator::Length($username, 55, 2) == false) {
            $msg .= 'Username should be between 3 to 54 characters' . '<br>';
        }
        if (Validator::Length($fullname, 36, 1) == false) {
            $msg .= 'Full Name should be between 2 to 25 characters' . '<br>';
        }

        // the customer is read and saved under the lock a password change (Forgot password, account page) takes, so a
        // password kept by Package::keepDialPassword() meanwhile is not overwritten by this form
        if (!Package::passwordLock((int) $id)) {
            r2(getUrl('customers/edit/') . $id, 'e', Lang::T('This customer is being changed by another request. Try again in a moment.'));
        }
        $c = ORM::for_table('tbl_customers')->find_one($id);

        if (!$c) {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }

        //lets find user Customers Attributes using id
        $customFields = ORM::for_table('tbl_customers_fields')
            ->where('customer_id', $id)
            ->find_many();

        $oldusername = $c['username'];
        $oldPppoeUsername = $c['pppoe_username'];
        $oldPppoePassword = $c['pppoe_password'];
        $oldPppoeIp = $c['pppoe_ip'];
        $oldPassPassword = $c['password'];
        $userDiff = false;
        $pppoeDiff = false;
        $passDiff = false;
        $pppoeIpDiff = false;
        if ($oldusername != $username) {
            if (ORM::for_table('tbl_customers')->where('username', $username)->find_one()) {
                $msg .= Lang::T('Username already used by another customer') . '<br>';
            }
            if (ORM::for_table('tbl_customers')->where('pppoe_username', $username)->find_one()) {
                $msg .= Lang::T('Username already used by another pppoe username customer') . '<br>';
            }
            $userDiff = true;
        }
        if ($oldPppoeUsername != $pppoe_username) {
            // if(!empty($pppoe_username)){
            //     if(ORM::for_table('tbl_customers')->where('pppoe_username', $pppoe_username)->find_one()){
            //         $msg.= Lang::T('PPPoE Username already used by another customer') . '<br>';
            //     }
            //     if(ORM::for_table('tbl_customers')->where('username', $pppoe_username)->find_one()){
            //         $msg.= Lang::T('PPPoE Username already used by another customer') . '<br>';
            //     }
            // }
            $pppoeDiff = true;
        }

        // the form carries a hash of the PPPoE password it was shown with: a form that still has it did not change the field, so
        // the saved value (possibly kept or changed since by a password change) stays
        $pppoeSeen = (string) _post('pppoe_password_seen');
        if ($pppoeSeen !== '' && (hash_equals($pppoeSeen, hash('sha256', $pppoe_password)) || hash_equals($pppoeSeen, hash('sha256', (string) ($_POST['pppoe_password'] ?? ''))))) {
            $pppoe_password = (string) $oldPppoePassword;
        }
        $pppoePassDiff = (string) $oldPppoePassword !== $pppoe_password;
        if ($oldPppoeIp != $pppoe_ip) {
            $pppoeIpDiff = true;
        }
        if ($password !== '' && (string) $oldPassPassword !== $password) { // strict: "1e3" and "1000" are different passwords
            $passDiff = true;
        }

        if ($msg == '') {
            $oldPhoto = '';
            // a changed address is checked and saved under the locks VPN provisioning takes (MikrotikVpn::lockAddressEdit()):
            // the old one stays reserved until a provisioning that uses it has finished, and one another customer holds is refused
            $vpnLocks = [];
            $heldOld = false;
            if ($pppoeIpDiff) {
                if (class_exists('MikrotikVpn') || DeviceLoader::load('MikrotikVpn')) {
                    $vpnRouters = [];
                    foreach (ORM::for_table('tbl_user_recharges')->select('routers')->where('customer_id', $c['id'])->find_array() as $row) {
                        $vpnRouters[] = (string) $row['routers'];
                    }
                    $vpnLocks = MikrotikVpn::lockAddressEdit($c['id'], $vpnRouters);
                    if ($vpnLocks === false) {
                        Package::passwordLock((int) $id, false);
                        r2(getUrl('customers/edit/') . $id, 'e', Lang::T('Another customer is being given an address from this pool. Try again in a moment.'));
                    }
                }
                if ($pppoe_ip !== '' && ORM::for_table('tbl_customers')->where('pppoe_ip', $pppoe_ip)->where_not_equal('id', $c['id'])->find_one()) {
                    Package::passwordLock((int) $id, false);
                    if ($vpnLocks) {
                        MikrotikVpn::unlockAddressEdit($vpnLocks);
                    }
                    r2(getUrl('customers/edit/') . $id, 'e', Lang::T('This IP address is already saved for another customer') . ': ' . $pppoe_ip);
                }
            }
            if (!empty($_FILES['photo']['name']) && file_exists($_FILES['photo']['tmp_name'])) {
                if (function_exists('imagecreatetruecolor')) {
                    $hash = md5_file($_FILES['photo']['tmp_name']);
                    $subfolder = substr($hash, 0, 2);
                    $folder = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'photos' . DIRECTORY_SEPARATOR;
                    if (!file_exists($folder)) {
                        mkdir($folder);
                    }
                    $folder = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'photos' . DIRECTORY_SEPARATOR . $subfolder . DIRECTORY_SEPARATOR;
                    if (!file_exists($folder)) {
                        mkdir($folder);
                    }
                    $imgPath = $folder . $hash . '.jpg';
                    if (!file_exists($imgPath)) {
                        try {
                            File::resizeCropImage($_FILES['photo']['tmp_name'], $imgPath, 1600, 1600, 100);
                        } catch (Throwable $photoErr) {
                            // not a picture the server can convert: the locks taken above are let go before the error goes on
                            if ($vpnLocks) {
                                MikrotikVpn::unlockAddressEdit($vpnLocks);
                            }
                            Package::passwordLock((int) $id, false);
                            throw $photoErr;
                        }
                    }
                    if (!file_exists($imgPath . '.thumb.jpg')) {
                        if (_post('faceDetect') == 'yes') {
                            try {
                                $detector = new svay\FaceDetector();
                                $detector->setTimeout(5000);
                                $detector->faceDetect($imgPath);
                                $detector->cropFaceToJpeg($imgPath . '.thumb.jpg', false);
                            } catch (Exception $e) {
                                File::makeThumb($imgPath, $imgPath . '.thumb.jpg', 200);
                            } catch (Throwable $e) {
                                File::makeThumb($imgPath, $imgPath . '.thumb.jpg', 200);
                            }
                        } else {
                            File::makeThumb($imgPath, $imgPath . '.thumb.jpg', 200);
                        }
                    }
                    if (file_exists($imgPath)) {
                        // the previous photo is deleted only once the customer is saved with the new one
                        if ($c['photo'] != '' && strpos($c['photo'], 'default') === false && $c['photo'] !== '/photos/' . $subfolder . '/' . $hash . '.jpg') {
                            $oldPhoto = $c['photo'];
                        }
                        $c->photo = '/photos/' . $subfolder . '/' . $hash . '.jpg';
                    }
                    if (file_exists($_FILES['photo']['tmp_name'])) unlink($_FILES['photo']['tmp_name']);
                } else {
                    if ($vpnLocks) {
                        MikrotikVpn::unlockAddressEdit($vpnLocks);
                    }
                    Package::passwordLock((int) $id, false);
                    r2(getUrl('settings/app'), 'e', 'PHP GD is not installed');
                }
            }
            if ($userDiff) {
                $c->username = $username;
            }
            if ($password !== '') {
                $c->password = $password;
                if ($passDiff) {
                    User::revokeCookies($c['id']); // only a real change ends the customer's other sessions and remember-me cookies
                }
            }
            $c->pppoe_username = $pppoe_username;
            $c->pppoe_password = $pppoe_password;
            $c->pppoe_ip = $pppoe_ip;
            $c->fullname = $fullname;
            $c->email = $email;
            $c->account_type = $account_type;
            $c->address = $address;
            $c->status = $status;
            $c->phonenumber = $phonenumber;
            $c->service_type = $service_type;
            $c->coordinates = $coordinates;
            $c->city = $city;
            $c->district = $district;
            $c->state = $state;
            $c->zip = $zip;
            // The customer lock is kept through the device updates and a rollback, not only the save: a later edit or password change
            // then waits, so it cannot write the routers (or be undone by this edit's rollback) in between. Not where a database
            // frees the locks a connection holds when it takes another (MikrotikVpn::nestedLocks() false): the VPN device takes its
            // own, so there the lock is let go after the save, as before.
            $holdThrough = (class_exists('MikrotikVpn') || DeviceLoader::load('MikrotikVpn')) ? MikrotikVpn::nestedLocks() : true;
            $releaseEdit = function () use ($holdThrough, $id) {
                if ($holdThrough) {
                    Package::passwordLock((int) $id, false);
                }
            };
            try {
                $c->save();
                if ($vpnLocks && (string) $oldPppoeIp !== '' && (string) $oldPppoeIp !== $pppoe_ip) {
                    // the old address is not free until the router's secret has let go of it: held (checkIpAddr() skips it)
                    // past the unlock, until the device update below has finished cleanly
                    MikrotikVpn::holdAddress($oldPppoeIp);
                    $heldOld = true;
                }
            } finally {
                if ($vpnLocks) {
                    MikrotikVpn::unlockAddressEdit($vpnLocks);
                }
                if (!$holdThrough) {
                    Package::passwordLock((int) $id, false);
                }
            }
            if ($oldPhoto !== '') {
                if (file_exists($UPLOAD_PATH . $oldPhoto)) {
                    unlink($UPLOAD_PATH . $oldPhoto);
                }
                if (file_exists($UPLOAD_PATH . $oldPhoto . '.thumb.jpg')) {
                    unlink($UPLOAD_PATH . $oldPhoto . '.thumb.jpg');
                }
            }
            if (class_exists('RedisCache')) {
                RedisCache::forgetRadiusCustomer($oldusername);
                RedisCache::forgetRadiusCustomer($username);
                RedisCache::forgetRadiusCustomer($oldPppoeUsername);
                RedisCache::forgetRadiusCustomer($pppoe_username);
            }


            // Update Customers Attributes values in tbl_customers_fields table
            foreach ($customFields as $customField) {
                $fieldName = $customField['field_name'];
                if (isset($_POST['custom_fields'][$fieldName])) {
                    $customFieldValue = $_POST['custom_fields'][$fieldName];
                    $customField->set('field_value', $customFieldValue);
                    $customField->save();
                }
            }

            // Add new Customers Attributess
            if (isset($_POST['custom_field_name']) && isset($_POST['custom_field_value'])) {
                $newCustomFieldNames = $_POST['custom_field_name'];
                $newCustomFieldValues = $_POST['custom_field_value'];

                // Check if the number of field names and values match
                if (count($newCustomFieldNames) == count($newCustomFieldValues)) {
                    $numNewFields = count($newCustomFieldNames);

                    for ($i = 0; $i < $numNewFields; $i++) {
                        $fieldName = $newCustomFieldNames[$i];
                        $fieldValue = $newCustomFieldValues[$i];

                        // Insert the new Customers Attributes
                        $newCustomField = ORM::for_table('tbl_customers_fields')->create();
                        $newCustomField->set('customer_id', $id);
                        $newCustomField->set('field_name', $fieldName);
                        $newCustomField->set('field_value', $fieldValue);
                        $newCustomField->save();
                    }
                }
            }

            // Delete Customers Attributess
            if (isset($_POST['delete_custom_fields'])) {
                $fieldsToDelete = $_POST['delete_custom_fields'];
                foreach ($fieldsToDelete as $fieldName) {
                    // Delete the Customers Attributes with the given field name
                    ORM::for_table('tbl_customers_fields')
                        ->where('field_name', $fieldName)
                        ->where('customer_id', $id)
                        ->delete_many();
                }
            }

            $keepHeld = false;
            $deviceSkipped = false;
            $partialPlans = []; // plans whose router update was only partly done (code 207)
            $renamed = []; // [plan, from, to] of the router renames that went through, to be undone if a later one is refused
            $doneTurs = []; // recharges already moved to the new username
            $skippedPlans = []; // plans with no driver file: nothing was sent to their device
            $passwordOnly = ($passDiff || $pppoePassDiff) && !$userDiff && !$pppoeDiff && !$pppoeIpDiff;
            if ($passwordOnly) {
                // only a password changed: just the password goes to the devices (Package::sendPassword), not a full re-provisioning
                try {
                    foreach (Package::sendPassword($c) as $err) {
                        $partialPlans[] = $err;
                        try {
                            _log('[' . $c['username'] . '] edit: new password not sent to the router: ' . $err, 'Router');
                        } catch (Throwable $ignored) {
                        }
                    }
                } catch (Throwable $e) {
                    $partialPlans[] = $e->getMessage();
                }
            } elseif ($userDiff || $pppoeDiff || $pppoeIpDiff || $passDiff || $pppoePassDiff) {
                $turs = ORM::for_table('tbl_user_recharges')->where('customer_id', $c['id'])->findMany();
                foreach ($turs as $tur) {
                    $p = ORM::for_table('tbl_plans')->find_one($tur['plan_id']);
                    $dvc = Package::getDevice($p);
                    // a recharge past its expiry (the cron has not switched it off yet) is renamed on its device but not given its access
                    // back: add_customer() below would enable it again
                    $expiredTur = strtotime($tur['expiration'] . ' ' . $tur['time']) <= time();
                    if (strcasecmp((string) $_app_stage, 'demo') !== 0) {
                        // if has active package (a Voucher plan has no account on a device: its add_customer() would issue a new voucher)
                        if ($tur['status'] == 'on' && $p['device'] !== 'Voucher') {
                            if (file_exists($dvc)) {
                                require_once $dvc;
                                try {
                                    if ($userDiff) {
                                        (new $p['device'])->change_username($p, $oldusername, $username);
                                        $renamed[] = [$p, $oldusername, $username];
                                    }
                                    if ($pppoeDiff && in_array($tur['type'], ['PPPOE', 'VPN'], true)) {
                                        if (empty($oldPppoeUsername) && !empty($pppoe_username)) {
                                            // admin just add pppoe username
                                            $from = $username;
                                            $to = $pppoe_username;
                                        } else if (empty($pppoe_username) && !empty($oldPppoeUsername)) {
                                            // admin want to use customer username
                                            $from = $oldPppoeUsername;
                                            $to = $username;
                                        } else {
                                            // regular change pppoe username
                                            $from = $oldPppoeUsername;
                                            $to = $pppoe_username;
                                        }
                                        (new $p['device'])->change_username($p, $from, $to);
                                        $renamed[] = [$p, $from, $to];
                                    }
                                } catch (Exception $e) {
                                    // the router refused a rename (the new name is taken, say): the customer was already saved, so the
                                    // saved values are put back and the renames that went through are undone, and the recharges keep
                                    // their name: customer, recharges and routers stay under one name and the edit can be tried again
                                    $undoFailed = [];
                                    foreach (array_reverse($renamed) as [$rp, $rfrom, $rto]) {
                                        try {
                                            (new $rp['device'])->change_username($rp, $rto, $rfrom);
                                        } catch (Exception $e2) {
                                            $undoFailed[] = $rp['name_plan'];
                                        }
                                    }
                                    foreach ($doneTurs as $dt) {
                                        $dt->username = $oldusername;
                                        $dt->save();
                                    }
                                    $lockTaken = Package::passwordLock((int) $id);
                                    $cb = null;
                                    try {
                                        // without the lock another request may be changing the customer: nothing is written then
                                        $cb = $lockTaken ? ORM::for_table('tbl_customers')->find_one($id) : null;
                                        if (!$lockTaken) {
                                            _log('[' . $oldusername . '] edit rollback skipped, customer locked by another request', 'Router');
                                            $undoFailed[] = Lang::T('the customer record (it was not restored)');
                                        }
                                        if ($cb) {
                                            // each field is put back only while it still holds what this edit saved (not a newer change)
                                            if ((string) $cb['username'] === $username) {
                                                $cb->username = $oldusername;
                                            }
                                            if ((string) $cb['pppoe_username'] === $pppoe_username) {
                                                $cb->pppoe_username = $oldPppoeUsername;
                                            }
                                            if ((string) $cb['pppoe_password'] === $pppoe_password) {
                                                $cb->pppoe_password = $oldPppoePassword;
                                            }
                                            if ((string) $cb['pppoe_ip'] === $pppoe_ip) {
                                                $cb->pppoe_ip = $oldPppoeIp;
                                            }
                                            if ($passDiff && (string) $cb['password'] === $password) {
                                                $cb->password = $oldPassPassword; // not a password changed since
                                            }
                                            $cb->save();
                                        }
                                    } finally {
                                        if ($lockTaken) {
                                            Package::passwordLock((int) $id, false);
                                        }
                                    }
                                    if ($heldOld) {
                                        // released only once the customer's row reserves that address again: otherwise the hold
                                        // stays (it runs out by itself) and the recovery is reported as incomplete
                                        if ($cb && (string) $cb['pppoe_ip'] === (string) $oldPppoeIp) {
                                            MikrotikVpn::releaseHeld($oldPppoeIp);
                                        } else {
                                            _log('[' . $oldusername . '] edit rollback: address ' . $oldPppoeIp . ' is still held, the customer row does not reserve it again', 'Router');
                                            $undoFailed[] = Lang::T('the address') . ' ' . $oldPppoeIp;
                                        }
                                    }
                                    if (class_exists('RedisCache')) {
                                        RedisCache::forgetRadiusCustomer($oldusername);
                                        RedisCache::forgetRadiusCustomer($username);
                                        RedisCache::forgetRadiusCustomer($oldPppoeUsername);
                                        RedisCache::forgetRadiusCustomer($pppoe_username);
                                    }
                                    _log('[' . $oldusername . '] edit not saved, rename refused: ' . $e->getMessage(), 'Router');
                                    $releaseEdit();
                                    r2(getUrl('customers/edit/') . $id, 'e', Lang::T('The router did not accept the username change and it was reverted on the routers. Other profile changes in this form may have been saved') . ': ' . htmlspecialchars($e->getMessage(), ENT_QUOTES)
                                        . ($undoFailed ? '. ' . Lang::T('The rename could not be undone on') . ': ' . htmlspecialchars(implode(', ', $undoFailed), ENT_QUOTES) : ''));
                                }
                                try {
                                    if (!$expiredTur) {
                                        (new $p['device'])->add_customer($c, $p);
                                    }
                                } catch (Exception $e) {
                                    // code 207: saved on the router, one later step refused (a fixed IP not cleared, a port forward not
                                    // made): the change stands, so it is saved here too, logged for the admins and named in a warning;
                                    // any other error stops as before
                                    if ($e->getCode() !== 207) {
                                        throw $e;
                                    }
                                    $cause = $e->getPrevious();
                                    _log('[' . $c['username'] . '] edit: ' . $e->getMessage()
                                        . ($cause && strpos($e->getMessage(), $cause->getMessage()) === false ? ' (' . $cause->getMessage() . ')' : ''), 'Router');
                                    $partialPlans[] = $p['name_plan'];
                                    $heldOld = false; // not confirmed clean: the old address stays held until its time runs out
                                    $keepHeld = true;
                                }
                            } else {
                                new Exception(Lang::T("Devices Not Found"));
                                $deviceSkipped = true; // no driver file: no secret was changed, so no address is let go
                                $skippedPlans[] = $p['name_plan'] . ' (' . Lang::T('Devices Not Found') . ')';
                            }
                        }
                    }
                    $tur->username = $username;
                    $tur->save();
                    $doneTurs[] = $tur;
                }
            }
            if ($heldOld && !$keepHeld && !$deviceSkipped) {
                MikrotikVpn::releaseHeld($oldPppoeIp); // every device update finished: no secret uses the old address any more
            } elseif ((string) $oldPppoeIp !== '' && (string) $oldPppoeIp !== $pppoe_ip && ($keepHeld || $deviceSkipped) && $vpnLocks && class_exists('MikrotikVpn')) {
                // not confirmed cleared on the router: the address stays out of reach of other customers for 30 days instead of the
                // short hold, long enough for Sync to finish the job
                try {
                    MikrotikVpn::holdAddress($oldPppoeIp, 30 * 86400);
                } catch (Throwable $ignored) {
                }
            }
            $releaseEdit();
            if ($partialPlans || $skippedPlans) {
                r2(getUrl('customers/view/') . $id, 'w', Lang::T('User Updated, but the router update is incomplete for') . ': '
                    . htmlspecialchars(implode(', ', array_unique(array_merge($partialPlans, $skippedPlans))), ENT_QUOTES) . '. ' . Lang::T('See the Router logs.') . ' ' . Lang::T('Use Sync to send it again.'));
            }
            r2(getUrl('customers/view/') . $id, 's', 'User Updated Successfully');
        } else {
            Package::passwordLock((int) $id, false);
            r2(getUrl('customers/edit/') . $id, 'e', $msg);
        }
        break;

    default:
        run_hook('list_customers'); #HOOK
        $search = _req('search');
        $statuses = ORM::for_table('tbl_customers')->getEnum("status");
        // order, direction and status come from the request: only known values reach the query
        $order = _req('order', 'username');
        if (!in_array($order, ['username', 'fullname', 'lastname', 'created_at', 'balance', 'status'], true)) {
            $order = 'username';
        }
        $orderby = strtolower(_req('orderby', 'asc')) === 'desc' ? 'desc' : 'asc';
        $filter = _req('filter', 'Active');
        if ($filter !== 'All' && !in_array($filter, $statuses, true)) {
            $filter = 'Active';
        }
        $order_pos = [
            'username' => 0,
            'fullname' => 4,
            'lastname' => 4,
            'created_at' => 8,
            'balance' => 3,
            'status' => 7
        ];

        $append_url = "&order=" . urlencode($order) . "&filter=" . urlencode($filter) . "&orderby=" . urlencode($orderby);

        $query = ORM::for_table('tbl_customers');
        if ($filter !== 'All') {
            $query->where("status", $filter);
        }
        if ($search != '') {
            // the status filter applies to the whole search, and the search text is a bound value, never part of the SQL
            $like = '%' . addcslashes($search, '\\%_') . '%';
            $query->whereRaw(
                '(username LIKE ? OR fullname LIKE ? OR address LIKE ? OR phonenumber LIKE ? OR email LIKE ?)',
                [$like, $like, $like, $like, $like]
            );
        }
        if ($order == 'lastname') {
            $query->order_by_expr("SUBSTR(fullname, INSTR(fullname, ' ')) $orderby");
        } else {
            if ($orderby == 'asc') {
                $query->order_by_asc($order);
            } else {
                $query->order_by_desc($order);
            }
        }
        if (_post('export', '') == 'csv') {
            $csrf_token = _post('csrf_token');
            if (!Csrf::check($csrf_token)) {
                r2(getUrl('customers'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
            }
            $d = $query->findMany();
            $h = false;
            set_time_limit(-1);
            header('Pragma: public');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header("Content-type: text/csv");
            header('Content-Disposition: attachment;filename="phpnuxbill_customers_' . $filter . '_' . date('Y-m-d_H_i') . '.csv"');
            header('Content-Transfer-Encoding: binary');

            $headers = [
                'id',
                'username',
                'fullname',
                'address',
                'phonenumber',
                'email',
                'balance',
                'service_type',
            ];
            $fp = fopen('php://output', 'wb');
            if (!$h) {
                fputcsv($fp, $headers, ";");
                $h = true;
            }
            foreach ($d as $c) {
                $row = [
                    $c['id'],
                    $c['username'],
                    $c['fullname'],
                    str_replace("\n", " ", $c['address']),
                    $c['phonenumber'],
                    $c['email'],
                    $c['balance'],
                    $c['service_type'],
                ];
                fputcsv($fp, $row, ";");
            }
            fclose($fp);
            die();
        }

        // the page size: the cookie of this browser, else the saved setting, else 30 (always a whole number between 1 and 5000)
        $cfgSize = max(0, min(5000, (int) ($config['customer_per_page'] ?? 0)));
        $cookieSize = max(0, min(5000, (int) ($_COOKIE['customer_per_page'] ?? 0)));
        if ($cookieSize > 0 && $cookieSize != $cfgSize) {
            $d = ORM::for_table('tbl_appconfig')->where('setting', 'customer_per_page')->find_one();
            if ($d) {
                $d->value = (string) $cookieSize;
                $d->save();
            } else {
                $d = ORM::for_table('tbl_appconfig')->create();
                $d->setting = 'customer_per_page';
                $d->value = (string) $cookieSize;
                $d->save();
            }
        }
        if ($cfgSize > 0 && $cookieSize < 1) {
            $cookieSize = $cfgSize;
            setcookie('customer_per_page', (string) $cfgSize, time() + (86400 * 30), "/");
        }
        $per_page = $cookieSize > 0 ? $cookieSize : ($cfgSize > 0 ? $cfgSize : 30);
        // the sizes the dropdown offers, plus the active one when it is not among them, so the page always shows the size in use
        $sizes = array_values(array_unique(array_merge([10, 25, 50, 100, 200, 500, 1000], [$per_page])));
        sort($sizes);
        $ui->assign('cookie', $per_page);
        $ui->assign('per_page_options', $sizes);

        $d = Paginator::findMany($query, ['search' => $search], $per_page, $append_url);
        $ui->assign('d', $d);
        $ui->assign('statuses', $statuses);
        // how many customers each status tab holds (the tabs on the default theme's list)
        $counts = [];
        foreach (ORM::for_table('tbl_customers')->raw_query('SELECT status, COUNT(*) AS n FROM tbl_customers GROUP BY status')->find_array() as $r) {
            $counts[$r['status']] = (int) $r['n'];
        }
        $ui->assign('status_counts', $counts);
        $ui->assign('total_customers', array_sum($counts));
        $ui->assign('filter', $filter);
        $ui->assign('search', $search);
        $ui->assign('order', $order);
        $ui->assign('order_pos', $order_pos[$order]);
        $ui->assign('orderby', $orderby);
        $ui->assign('csrf_token',  Csrf::generateAndStoreToken());
        $ui->display('admin/customers/list.tpl');
        break;
}
