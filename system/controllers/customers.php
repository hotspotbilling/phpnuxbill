<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', $_L['Customers']);
$ui->assign('_system_menu', 'customers');

$action = $routes['1'];
$admin = Admin::_info();
$ui->assign('_admin', $admin);


if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
    r2(U . "dashboard", 'e', $_L['Do_Not_Access']);
}

switch ($action) {
    case 'list':
        $search = _post('search');
        run_hook('list_customers'); #HOOK
        if ($search != '') {
            $paginator = Paginator::build(ORM::for_table('tbl_customers'), [
                'username' => '%' . $search . '%',
                'fullname' => '%' . $search . '%',
                'phonenumber' => '%' . $search . '%',
                'email' => '%' . $search . '%',
                'service_type' => '%' . $search . '%'
            ], $search);
            $d = ORM::for_table('tbl_customers')
                ->where_raw("(`username` LIKE '%$search%' OR `fullname` LIKE '%$search%' OR `phonenumber` LIKE '%$search%' OR `email` LIKE '%$search%')")
                ->offset($paginator['startpoint'])
                ->limit($paginator['limit'])
                ->order_by_asc('username')
                ->find_many();
        } else {
            $paginator = Paginator::build(ORM::for_table('tbl_customers'));
            $d = ORM::for_table('tbl_customers')
                ->offset($paginator['startpoint'])->limit($paginator['limit'])->order_by_desc('id')->find_many();
        }

        $ui->assign('search', htmlspecialchars($search));
        $ui->assign('d', $d);
        $ui->assign('paginator', $paginator);
        $ui->display('customers.tpl');
        break;

    case 'csv':
        $cs = ORM::for_table('tbl_customers')
            ->select('tbl_customers.id', 'id')
            ->select('tbl_customers.username', 'username')
            ->select('fullname')
            ->select('phonenumber')
            ->select('email')
            ->select('balance')
            ->select('namebp')
            ->select('routers')
            ->select('status')
            ->select('method', 'Payment')
            ->join('tbl_user_recharges', array('tbl_customers.id', '=', 'tbl_user_recharges.customer_id'))
            ->order_by_asc('tbl_customers.id')->find_array();
        $h = false;
        set_time_limit(-1);
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header("Content-type: text/csv");
        header('Content-Disposition: attachment;filename="phpnuxbill_customers_' . date('Y-m-d_H_i') . '.csv"');
        header('Content-Transfer-Encoding: binary');
        foreach ($cs as $c) {
            $ks = [];
            $vs = [];
            foreach ($c as $k => $v) {
                $ks[] = $k;
                $vs[] = $v;
            }
            if (!$h) {
                echo '"' . implode('";"', $ks) . "\"\n";
                $h = true;
            }
            echo '"' . implode('";"', $vs) . "\"\n";
        }
        break;
    case 'add':
        run_hook('view_add_customer'); #HOOK
        $ui->display('customers-add.tpl');
        break;
    case 'recharge':
        $id_customer  = $routes['2'];
        $b = ORM::for_table('tbl_user_recharges')->where('customer_id', $id_customer)->find_one();
        if ($b) {
            if (Package::rechargeUser($id_customer, $b['routers'], $b['plan_id'], "Recharge", $admin['fullname'])) {
                r2(U . 'customers/view/' . $id_customer, 's', 'Success Recharge Customer');
            } else {
                r2(U . 'customers/view/' . $id_customer, 'e', 'Customer plan is inactive');
            }
        }
        r2(U . 'customers/view/' . $id_customer, 'e', 'Cannot find active plan');
    case 'deactivate':
        $id_customer  = $routes['2'];
        $b = ORM::for_table('tbl_user_recharges')->where('customer_id', $id_customer)->find_one();
        if ($b) {
            $p = ORM::for_table('tbl_plans')->where('id', $b['plan_id'])->where('enabled', '1')->find_one();
            if ($p) {
                if ($p['is_radius']) {
                    Radius::customerDeactivate($b['username']);
                } else {
                    $mikrotik = Mikrotik::info($b['routers']);
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    if ($b['type'] == 'Hotspot') {
                        Mikrotik::removeHotspotUser($client, $b['username']);
                        Mikrotik::removeHotspotActiveUser($client, $b['username']);
                    } else if ($b['type'] == 'PPPOE') {
                        Mikrotik::removePpoeUser($client, $b['username']);
                        Mikrotik::removePpoeActive($client, $b['username']);
                    }
                }
                $b->status = 'off';
                $b->expiration = date('Y-m-d');
                $b->time = date('H:i:s');
                $b->save();
                _log('Admin ' . $admin['username'] . ' Deactivate ' . $b['namebp'] . ' for ' . $b['username'], 'User', $b['customer_id']);
                Message::sendTelegram('Admin ' . $admin['username'] . ' Deactivate ' . $b['namebp'] . ' for u' . $b['username']);
                r2(U . 'customers/view/' . $id_customer, 's', 'Success deactivate customer to Mikrotik');
            }
        }
        r2(U . 'customers/view/' . $id_customer, 'e', 'Cannot find active plan');
        break;
    case 'sync':
        $id_customer  = $routes['2'];
        $b = ORM::for_table('tbl_user_recharges')->where('customer_id', $id_customer)->where('status', 'on')->find_one();
        if ($b) {
            $c = ORM::for_table('tbl_customers')->find_one($id_customer);
            $p = ORM::for_table('tbl_plans')->where('id', $b['plan_id'])->where('enabled', '1')->find_one();
            if ($p) {
                if ($p['is_radius']) {
                    Radius::customerAddPlan($c, $p, $p['expiration'] . ' ' . $p['time']);
                    r2(U . 'customers/view/' . $id_customer, 's', 'Success sync customer to Radius');
                } else {
                    $mikrotik = Mikrotik::info($b['routers']);
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    if ($b['type'] == 'Hotspot') {
                        Mikrotik::addHotspotUser($client, $p, $c);
                    } else if ($b['type'] == 'PPPOE') {
                        Mikrotik::addPpoeUser($client, $p, $c);
                    }
                    r2(U . 'customers/view/' . $id_customer, 's', 'Success sync customer to Mikrotik');
                }
            } else {
                r2(U . 'customers/view/' . $id_customer, 'e', 'Customer plan is inactive');
            }
        }
        r2(U . 'customers/view/' . $id_customer, 'e', 'Cannot find active plan');
        break;
    case 'viewu':
        $customer = ORM::for_table('tbl_customers')->where('username', $routes['2'])->find_one();
    case 'view':
        $id  = $routes['2'];
        run_hook('view_customer'); #HOOK
        if (!$customer) {
            $customer = ORM::for_table('tbl_customers')->find_one($id);
        }
        if ($customer) {
            $v  = $routes['3'];
            if (empty($v) || $v == 'order') {
                $v = 'order';
                $paginator = Paginator::build(ORM::for_table('tbl_payment_gateway'), ['username' => $customer['username']]);
                $order = ORM::for_table('tbl_payment_gateway')
                    ->where('username', $customer['username'])
                    ->offset($paginator['startpoint'])
                    ->limit($paginator['limit'])
                    ->order_by_desc('id')
                    ->find_many();
                $ui->assign('paginator', $paginator);
                $ui->assign('order', $order);
            } else if ($v == 'activation') {
                $paginator = Paginator::build(ORM::for_table('tbl_transactions'), ['username' => $customer['username']]);
                $activation = ORM::for_table('tbl_transactions')
                    ->where('username', $customer['username'])
                    ->offset($paginator['startpoint'])
                    ->limit($paginator['limit'])
                    ->order_by_desc('id')
                    ->find_many();
                $ui->assign('paginator', $paginator);
                $ui->assign('activation', $activation);
            }
            $package = ORM::for_table('tbl_user_recharges')->where('username', $customer['username'])->find_one();
            $ui->assign('package', $package);
            $ui->assign('v', $v);
            $ui->assign('d', $customer);
            $ui->display('customers-view.tpl');
        } else {
            r2(U . 'customers/list', 'e', $_L['Account_Not_Found']);
        }
        break;
    case 'edit':
        $id  = $routes['2'];
        run_hook('edit_customer'); #HOOK
        $d = ORM::for_table('tbl_customers')->find_one($id);
        if ($d) {
            $ui->assign('d', $d);
            $ui->display('customers-edit.tpl');
        } else {
            r2(U . 'customers/list', 'e', $_L['Account_Not_Found']);
        }
        break;

    case 'delete':
        $id  = $routes['2'];
        run_hook('delete_customer'); #HOOK
        $d = ORM::for_table('tbl_customers')->find_one($id);
        if ($d) {
            $c = ORM::for_table('tbl_user_recharges')->where('username', $d['username'])->find_one();
            if ($c) {
                $p = ORM::for_table('tbl_plans')->find_one($c['plan_id']);
                if ($p['is_radius']) {
                    Radius::customerDelete($d['username']);
                } else {
                    $mikrotik = Mikrotik::info($c['routers']);
                    if ($c['type'] == 'Hotspot') {
                        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        Mikrotik::removeHotspotUser($client, $d['username']);
                        Mikrotik::removeHotspotActiveUser($client, $d['username']);
                    } else {
                        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        Mikrotik::removePpoeUser($client, $d['username']);
                        Mikrotik::removePpoeActive($client, $d['username']);
                    }
                    try {
                        $d->delete();
                    } catch (Exception $e) {
                    } catch (Throwable $e) {
                    }
                    try {
                        $c->delete();
                    } catch (Exception $e) {
                    }
                }
            } else {
                try {
                    $d->delete();
                } catch (Exception $e) {
                } catch (Throwable $e) {
                }
                try {
                    $c->delete();
                } catch (Exception $e) {
                } catch (Throwable $e) {
                }
            }

            r2(U . 'customers/list', 's', $_L['User_Delete_Ok']);
        }
        break;

    case 'add-post':
        $username = _post('username');
        $fullname = _post('fullname');
        $password = _post('password');
        $pppoe_password = _post('pppoe_password');
        $email = _post('email');
        $address = _post('address');
        $phonenumber = _post('phonenumber');
        $service_type = _post('service_type');
        run_hook('add_customer'); #HOOK
        $msg = '';
        if (Validator::Length($username, 35, 2) == false) {
            $msg .= 'Username should be between 3 to 55 characters' . '<br>';
        }
        if (Validator::Length($fullname, 36, 2) == false) {
            $msg .= 'Full Name should be between 3 to 25 characters' . '<br>';
        }
        if (!Validator::Length($password, 36, 2)) {
            $msg .= 'Password should be between 3 to 35 characters' . '<br>';
        }

        $d = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
        if ($d) {
            $msg .= $_L['account_already_exist'] . '<br>';
        }

        if ($msg == '') {
            $d = ORM::for_table('tbl_customers')->create();
            $d->username = Lang::phoneFormat($username);
            $d->password = $password;
            $d->pppoe_password = $pppoe_password;
            $d->email = $email;
            $d->fullname = $fullname;
            $d->address = $address;
            $d->phonenumber = Lang::phoneFormat($phonenumber);
            $d->service_type = $service_type;
            $d->save();
            r2(U . 'customers/list', 's', $_L['account_created_successfully']);
        } else {
            r2(U . 'customers/add', 'e', $msg);
        }
        break;

    case 'edit-post':
        $username = Lang::phoneFormat(_post('username'));
        $fullname = _post('fullname');
        $password = _post('password');
        $pppoe_password = _post('pppoe_password');
        $email = _post('email');
        $address = _post('address');
        $phonenumber = Lang::phoneFormat(_post('phonenumber'));
        $service_type = _post('service_type');
        run_hook('edit_customer'); #HOOK
        $msg = '';
        if (Validator::Length($username, 35, 2) == false) {
            $msg .= 'Username should be between 3 to 15 characters' . '<br>';
        }
        if (Validator::Length($fullname, 36, 1) == false) {
            $msg .= 'Full Name should be between 2 to 25 characters' . '<br>';
        }
        if ($password != '') {
            if (!Validator::Length($password, 36, 2)) {
                $msg .= 'Password should be between 3 to 15 characters' . '<br>';
            }
        }

        $id = _post('id');
        $d = ORM::for_table('tbl_customers')->find_one($id);
        if (!$d) {
            $msg .= $_L['Data_Not_Found'] . '<br>';
        }

        $oldusername = $d['username'];
        $oldPppoePassword =  $d['password'];
        $oldPassPassword =  $d['pppoe_password'];
        $userDiff = false;
        $pppoeDiff = false;
        $passDiff = false;
        if ($oldusername != $username) {
            $c = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
            if ($c) {
                $msg .= $_L['account_already_exist'] . '<br>';
            }
            $userDiff = true;
        }
        if ($oldPppoePassword != $pppoe_password) {
            $pppoeDiff = true;
        }
        if ($password != '' && $oldPassPassword != $password) {
            $passDiff = true;
        }

        if ($msg == '') {
            if ($userDiff) {
                $d->username = $username;
            }
            if ($password != '') {
                $d->password = $password;
            }
            $d->pppoe_password = $pppoe_password;
            $d->fullname = $fullname;
            $d->email = $email;
            $d->address = $address;
            $d->phonenumber = $phonenumber;
            $d->service_type = $service_type;
            $d->save();
            if ($userDiff || $pppoeDiff || $passDiff) {
                $c = ORM::for_table('tbl_user_recharges')->where('username', ($userDiff) ? $oldusername : $username)->find_one();
                if ($c) {
                    $c->username = $username;
                    $c->save();
                    $p = ORM::for_table('tbl_plans')->find_one($c['plan_id']);
                    if ($p['is_radius']) {
                        if ($userDiff) {
                            Radius::customerChangeUsername($oldusername, $username);
                        }
                        Radius::customerAddPlan($d, $p, $p['expiration'] . ' ' . $p['time']);
                    } else {
                        $mikrotik = Mikrotik::info($c['routers']);
                        if ($c['type'] == 'Hotspot') {
                            $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                            Mikrotik::setHotspotUser($client, $c['username'], $password);
                            Mikrotik::removeHotspotActiveUser($client, $d['username']);
                        } else {
                            $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                            if (!empty($d['pppoe_password'])) {
                                Mikrotik::setPpoeUser($client, $c['username'], $d['pppoe_password']);
                            } else {
                                Mikrotik::setPpoeUser($client, $c['username'], $password);
                            }
                            Mikrotik::removePpoeActive($client, $d['username']);
                        }
                    }
                }
            }
            r2(U . 'customers/list', 's', 'User Updated Successfully');
        } else {
            r2(U . 'customers/edit/' . $id, 'e', $msg);
        }
        break;

    default:
        r2(U . 'customers/list', 'e', 'action not defined');
}
