<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/


_auth();
$ui->assign('_title', Lang::T('My Account'));
$ui->assign('_system_menu', 'accounts');

$action = $routes['1'];
$user = User::_info();
$ui->assign('_user', $user);

switch ($action) {

    case 'change-password':
        run_hook('customer_view_change_password'); #HOOK
        $ui->display('user-change-password.tpl');
        break;

    case 'change-password-post':
        $password = _post('password');
        run_hook('customer_change_password'); #HOOK
        if ($password != '') {
            $d = ORM::for_table('tbl_customers')->where('username', $user['username'])->find_one();
            if ($d) {
                $d_pass = $d['password'];
                $npass = _post('npass');
                $cnpass = _post('cnpass');

                if (Password::_uverify($password, $d_pass) == true) {
                    if (!Validator::Length($npass, 15, 2)) {
                        r2(U . 'accounts/change-password', 'e', 'New Password must be 3 to 14 character');
                    }
                    if ($npass != $cnpass) {
                        r2(U . 'accounts/change-password', 'e', 'Both Password should be same');
                    }

                    $c = ORM::for_table('tbl_user_recharges')->where('username', $user['username'])->find_one();
                    if ($c) {
                        $p = ORM::for_table('tbl_plans')->where('id', $c['plan_id'])->find_one();
                        if($p['is_radius']){
                            if($c['type'] == 'Hotspot' || ($c['type'] == 'PPPOE' && empty($d['pppoe_password']))){
                                Radius::customerUpsert($d, $p);
                            }
                        }else{
                            $mikrotik = Mikrotik::info($c['routers']);
                            $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                            if ($c['type'] == 'Hotspot') {
                                    Mikrotik::setHotspotUser($client, $c['username'], $npass);
                                    Mikrotik::removeHotspotActiveUser($client, $user['username']);
                            } else if(empty($d['pppoe_password'])){
                                // only change when pppoe_password empty
                                Mikrotik::setPpoeUser($client, $c['username'], $npass);
                                Mikrotik::removePpoeActive($client, $user['username']);
                            }
                        }
                    }
                    $d->password = $npass;
                    $d->save();

                    _msglog('s', Lang::T('Password changed successfully, Please login again'));
                    _log('[' . $user['username'] . ']: Password changed successfully', 'User', $user['id']);

                    r2(U . 'login');
                } else {
                    r2(U . 'accounts/change-password', 'e', Lang::T('Incorrect Current Password'));
                }
            } else {
                r2(U . 'accounts/change-password', 'e', Lang::T('Incorrect Current Password'));
            }
        } else {
            r2(U . 'accounts/change-password', 'e', Lang::T('Incorrect Current Password'));
        }
        break;

    case 'profile':
        $d = ORM::for_table('tbl_customers')->find_one($user['id']);
        if ($d) {
            run_hook('customer_view_edit_profile'); #HOOK
            $ui->assign('d', $d);
            $ui->display('user-profile.tpl');
        } else {
            r2(U . 'home', 'e', $_L['Account_Not_Found']);
        }
        break;

    case 'edit-profile-post':
        $fullname = _post('fullname');
        $address = _post('address');
        $email = _post('email');
        $phonenumber = _post('phonenumber');
        run_hook('customer_edit_profile'); #HOOK
        $msg = '';
        if (Validator::Length($fullname, 31, 2) == false) {
            $msg .= 'Full Name should be between 3 to 30 characters' . '<br>';
        }
        if (Validator::UnsignedNumber($phonenumber) == false) {
            $msg .= 'Phone Number must be a number' . '<br>';
        }

        $d = ORM::for_table('tbl_customers')->find_one($user['id']);
        if ($d) {
        } else {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }

        if ($msg == '') {
            $d->fullname = $fullname;
            $d->address = $address;
            $d->email = $email;
            $d->phonenumber = $phonenumber;
            $d->save();

            _log('[' . $user['username'] . ']: ' . Lang::T('User Updated Successfully'), 'User', $user['id']);
            r2(U . 'accounts/profile', 's', Lang::T('User Updated Successfully'));
        } else {
            r2(U . 'accounts/profile', 'e', $msg);
        }
        break;

    default:
        $ui->display('a404.tpl');
}
