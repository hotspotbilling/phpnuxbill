<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)

 **/
_admin();
$ui->assign('_title', $_L['Customers']);
$ui->assign('_system_menu', 'customers');

$action = $routes['1'];
$admin = Admin::_info();
$ui->assign('_admin', $admin);

use PEAR2\Net\RouterOS;

require_once 'system/autoload/PEAR2/Autoload.php';

if ($admin['user_type'] != 'Admin' and $admin['user_type'] != 'Sales') {
    r2(U . "dashboard", 'e', $_L['Do_Not_Access']);
}

switch ($action) {
    case 'list':
        $ui->assign('xfooter', '<script type="text/javascript" src="ui/lib/c/customers.js"></script>');
        $search = _post('search');
        $what = _post('what');
        if(!in_array($what,['username','fullname','phonenumber','email'])){
            $what = 'username';
        }
        run_hook('list_customers'); #HOOK
        if ($search != '') {
            $paginator = Paginator::bootstrap('tbl_customers', 'username', '%' . $search . '%');
            $d = ORM::for_table('tbl_customers')
            ->where_like($what, '%' . $search . '%')
            ->offset($paginator['startpoint'])
            ->limit($paginator['limit'])
            ->order_by_desc('id')->find_many();
        } else {
            $paginator = Paginator::bootstrap('tbl_customers');
            $d = ORM::for_table('tbl_customers')->offset($paginator['startpoint'])->limit($paginator['limit'])->order_by_desc('id')->find_many();
        }

        $ui->assign('search', htmlspecialchars($search));
        $ui->assign('what', $what);
        $ui->assign('d', $d);
        $ui->assign('paginator', $paginator);
        $ui->display('customers.tpl');
        break;

    case 'add':
        run_hook('view_add_customer'); #HOOK
        $ui->display('customers-add.tpl');
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
                $mikrotik = Mikrotik::info($c['routers']);
                if ($c['type'] == 'Hotspot') {
                    if(!$config['radius_mode']){
                        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        Mikrotik::removeHotspotUser($client,$c['username']);
                        Mikrotik::removeHotspotActiveUser($client,$user['username']);
                    }
                } else {
                    if(!$config['radius_mode']){
                        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        Mikrotik::removePpoeUser($client,$c['username']);
                        Mikrotik::removePpoeActive($client,$user['username']);
                    }
                }
                try {
                    $d->delete();
                } catch (Exception $e) {
                } catch(Throwable $e){
                }
                try {
                    $c->delete();
                } catch (Exception $e) {
                }
            } else {
                try {
                    $d->delete();
                } catch (Exception $e) {
                } catch(Throwable $e){
                }
                try {
                    $c->delete();
                } catch (Exception $e) {
                } catch(Throwable $e){
                }
            }

            r2(U . 'customers/list', 's', $_L['User_Delete_Ok']);
        }
        break;

    case 'add-post':
        $username = _post('username');
        $fullname = _post('fullname');
        $password = _post('password');
        $cpassword = _post('cpassword');
        $address = _post('address');
        $phonenumber = _post('phonenumber');
        run_hook('add_customer'); #HOOK
        $msg = '';
        if (Validator::Length($username, 35, 2) == false) {
            $msg .= 'Username should be between 3 to 55 characters' . '<br>';
        }
        if (Validator::Length($fullname, 36, 2) == false) {
            $msg .= 'Full Name should be between 3 to 25 characters' . '<br>';
        }
        if (!Validator::Length($password, 35, 2)) {
            $msg .= 'Password should be between 3 to 35 characters' . '<br>';
        }
        if ($password != $cpassword) {
            $msg .= 'Passwords does not match' . '<br>';
        }

        $d = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
        if ($d) {
            $msg .= $_L['account_already_exist'] . '<br>';
        }

        if ($msg == '') {
            $d = ORM::for_table('tbl_customers')->create();
            $d->username = $username;
            $d->password = $password;
            $d->fullname = $fullname;
            $d->address = $address;
            $d->phonenumber = $username;
            $d->save();
            r2(U . 'customers/list', 's', $_L['account_created_successfully']);
        } else {
            r2(U . 'customers/add', 'e', $msg);
        }
        break;

    case 'edit-post':
        $username = _post('username');
        $fullname = _post('fullname');
        $password = _post('password');
        $cpassword = _post('cpassword');
        $address = _post('address');
        $phonenumber = _post('phonenumber');
        run_hook('edit_customer'); #HOOK
        $msg = '';
        if (Validator::Length($username, 16, 2) == false) {
            $msg .= 'Username should be between 3 to 15 characters' . '<br>';
        }
        if (Validator::Length($fullname, 26, 2) == false) {
            $msg .= 'Full Name should be between 3 to 25 characters' . '<br>';
        }
        if ($password != '') {
            if (!Validator::Length($password, 15, 2)) {
                $msg .= 'Password should be between 3 to 15 characters' . '<br>';
            }
            if ($password != $cpassword) {
                $msg .= 'Passwords does not match' . '<br>';
            }
        }

        $id = _post('id');
        $d = ORM::for_table('tbl_customers')->find_one($id);
        if (!$d) {
            $msg .= $_L['Data_Not_Found'] . '<br>';
        }

        if ($d['username'] != $username) {
            $c = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
            if ($c) {
                $msg .= $_L['account_already_exist'] . '<br>';
            }
        }

        if ($msg == '') {
            $c = ORM::for_table('tbl_user_recharges')->where('username', $username)->find_one();
            if ($c) {
                $mikrotik = Mikrotik::info($c['routers']);
                if ($c['type'] == 'Hotspot') {
                    if(!$config['radius_mode']){
                        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        Mikrotik::setHotspotUser($client,$c['username'],$password);
                        Mikrotik::removeHotspotActiveUser($client,$user['username']);
                    }

                    $d->password = $password;
                    $d->save();
                } else {
                    if(!$config['radius_mode']){
                        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        Mikrotik::setPpoeUser($client,$c['username'],$password);
                        Mikrotik::removePpoeActive($client,$user['username']);
                    }

                    $d->password = $password;
                    $d->save();
                }
                $d->username = $username;
                if ($password != '') {
                    $d->password = $password;
                }
                $d->fullname = $fullname;
                $d->address = $address;
                $d->phonenumber = $phonenumber;
                $d->save();
            } else {
                $d->username = $username;
                if ($password != '') {
                    $d->password = $password;
                }
                $d->fullname = $fullname;
                $d->address = $address;
                $d->phonenumber = $phonenumber;
                $d->save();
            }
            r2(U . 'customers/list', 's', 'User Updated Successfully');
        } else {
            r2(U . 'customers/edit/' . $id, 'e', $msg);
        }
        break;

    default:
    r2(U . 'customers/list', 'e', 'action not defined');
}
