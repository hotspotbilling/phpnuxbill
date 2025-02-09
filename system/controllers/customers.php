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
                if ($_app_stage != 'demo') {
                    if (file_exists($dvc)) {
                        require_once $dvc;
                        (new $p['device'])->remove_customer($c, $p);
                    } else {
                        new Exception(Lang::T("Devices Not Found"));
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
            foreach ($bs as $b) {
                $c = ORM::for_table('tbl_customers')->find_one($id_customer);
                $p = ORM::for_table('tbl_plans')->where('id', $b['plan_id'])->find_one();
                if ($p) {
                    $routers[] = $b['routers'];
                    $dvc = Package::getDevice($p);
                    if ($_app_stage != 'demo') {
                        if (file_exists($dvc)) {
                            require_once $dvc;
                            if (method_exists($dvc, 'sync_customer')) {
                                (new $p['device'])->sync_customer($c, $p);
                            }else{
                                (new $p['device'])->add_customer($c, $p);
                            }
                        } else {
                            new Exception(Lang::T("Devices Not Found"));
                        }
                    }
                }
            }
            r2(getUrl('customers/view/') . $id_customer, 's', 'Sync success to ' . implode(", ", $routers));
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
            // Delete the associated Customers Attributes records from tbl_customer_custom_fields table
            ORM::for_table('tbl_customers_fields')->where('customer_id', $id)->delete_many();
            //Delete active package
            $turs = ORM::for_table('tbl_user_recharges')->where('username', $c['username'])->find_many();
            foreach ($turs as $tur) {
                $p = ORM::for_table('tbl_plans')->find_one($tur['plan_id']);
                if ($p) {
                    $dvc = Package::getDevice($p);
                    if ($_app_stage != 'demo') {
                        if (file_exists($dvc)) {
                            require_once $dvc;
                            $p['plan_expired'] = 0;
                            (new $p['device'])->remove_customer($c, $p);
                        } else {
                            new Exception(Lang::T("Devices Not Found"));
                        }
                    }
                }
                try {
                    $tur->delete();
                } catch (Exception $e) {
                }
            }
            try {
                $c->delete();
            } catch (Exception $e) {
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

                $emailSubject = "Welcome to " . $config['CompanyName'];

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
                        'args' => [$d['email'], $emailSubject, $welcomeMessage, $d['email']]
                    ]
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

        if ($oldPppoeIp != $pppoe_ip) {
            $pppoeIpDiff = true;
        }
        if ($password != '' && $oldPassPassword != $password) {
            $passDiff = true;
        }

        if ($msg == '') {
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
                        File::resizeCropImage($_FILES['photo']['tmp_name'], $imgPath, 1600, 1600, 100);
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
                        if ($c['photo'] != '' && strpos($c['photo'], 'default') === false) {
                            if (file_exists($UPLOAD_PATH . $c['photo'])) {
                                unlink($UPLOAD_PATH . $c['photo']);
                                if (file_exists($UPLOAD_PATH . $c['photo'] . '.thumb.jpg')) {
                                    unlink($UPLOAD_PATH . $c['photo'] . '.thumb.jpg');
                                }
                            }
                        }
                        $c->photo = '/photos/' . $subfolder . '/' . $hash . '.jpg';
                    }
                    if (file_exists($_FILES['photo']['tmp_name'])) unlink($_FILES['photo']['tmp_name']);
                } else {
                    r2(getUrl('settings/app'), 'e', 'PHP GD is not installed');
                }
            }
            if ($userDiff) {
                $c->username = $username;
            }
            if ($password != '') {
                $c->password = $password;
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
            $c->save();


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

            if ($userDiff || $pppoeDiff || $pppoeIpDiff || $passDiff) {
                $turs = ORM::for_table('tbl_user_recharges')->where('customer_id', $c['id'])->findMany();
                foreach ($turs as $tur) {
                    $p = ORM::for_table('tbl_plans')->find_one($tur['plan_id']);
                    $dvc = Package::getDevice($p);
                    if ($_app_stage != 'demo') {
                        // if has active package
                        if ($tur['status'] == 'on') {
                            if (file_exists($dvc)) {
                                require_once $dvc;
                                if ($userDiff) {
                                    (new $p['device'])->change_username($p, $oldusername, $username);
                                }
                                if ($pppoeDiff && $tur['type'] == 'PPPOE') {
                                    if (empty($oldPppoeUsername) && !empty($pppoe_username)) {
                                        // admin just add pppoe username
                                        (new $p['device'])->change_username($p, $username, $pppoe_username);
                                    } else if (empty($pppoe_username) && !empty($oldPppoeUsername)) {
                                        // admin want to use customer username
                                        (new $p['device'])->change_username($p, $oldPppoeUsername, $username);
                                    } else {
                                        // regular change pppoe username
                                        (new $p['device'])->change_username($p, $oldPppoeUsername, $pppoe_username);
                                    }
                                }
                                (new $p['device'])->add_customer($c, $p);
                            } else {
                                new Exception(Lang::T("Devices Not Found"));
                            }
                        }
                    }
                    $tur->username = $username;
                    $tur->save();
                }
            }
            r2(getUrl('customers/view/') . $id, 's', 'User Updated Successfully');
        } else {
            r2(getUrl('customers/edit/') . $id, 'e', $msg);
        }
        break;

    default:
        run_hook('list_customers'); #HOOK
        $search = _req('search');
        $order = _req('order', 'username');
        $filter = _req('filter', 'Active');
        $orderby = _req('orderby', 'asc');
        $order_pos = [
            'username' => 0,
            'created_at' => 8,
            'balance' => 3,
            'status' => 7
        ];

        $append_url = "&order=" . urlencode($order) . "&filter=" . urlencode($filter) . "&orderby=" . urlencode($orderby);

        if ($search != '') {
            $query = ORM::for_table('tbl_customers')
                ->whereRaw("username LIKE '%$search%' OR fullname LIKE '%$search%' OR address LIKE '%$search%' " .
                    "OR phonenumber LIKE '%$search%' OR email LIKE '%$search%' AND status='$filter'");
        } else {
            $query = ORM::for_table('tbl_customers');
            $query->where("status", $filter);
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
        $d = Paginator::findMany($query, ['search' => $search], 30, $append_url);
        $ui->assign('d', $d);
        $ui->assign('statuses', ORM::for_table('tbl_customers')->getEnum("status"));
        $ui->assign('filter', $filter);
        $ui->assign('search', $search);
        $ui->assign('order', $order);
        $ui->assign('order_pos', $order_pos[$order]);
        $ui->assign('orderby', $orderby);
        $ui->assign('csrf_token',  Csrf::generateAndStoreToken());
        $ui->display('admin/customers/list.tpl');
        break;
}
