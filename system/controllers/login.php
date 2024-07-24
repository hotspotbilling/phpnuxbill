<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

$maintenance_mode = $config['maintenance_mode'];
if ($maintenance_mode == true) {
    displayMaintenanceMessage();
}

if (User::getID()) {
    r2(U . 'home');
}

if (isset($routes['1'])) {
    $do = $routes['1'];
} else {
    $do = 'login-display';
}

switch ($do) {
    case 'post':
        $username = _post('username');
        $password = _post('password');
        run_hook('customer_login'); #HOOK
        if ($username != '' and $password != '') {
            $d = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
            if ($d) {
                $d_pass = $d['password'];
                if ($d['status'] == 'Banned') {
                    _alert(Lang::T('This account status') . ' : ' . Lang::T($d['status']), 'danger', "");
                }
                if (Password::_uverify($password, $d_pass) == true) {
                    $_SESSION['uid'] = $d['id'];
                    User::setCookie($d['id']);
                    $d->last_login = date('Y-m-d H:i:s');
                    $d->save();
                    _log($username . ' ' . Lang::T('Login Successful'), 'User', $d['id']);
                    _alert(Lang::T('Login Successful'), 'success', "home");
                } else {
                    _msglog('e', Lang::T('Invalid Username or Password'));
                    _log($username . ' ' . Lang::T('Failed Login'), 'User');
                    r2(U . 'login');
                }
            } else {
                _msglog('e', Lang::T('Invalid Username or Password'));
                r2(U . 'login');
            }
        } else {
            _msglog('e', Lang::T('Invalid Username or Password'));
            r2(U . 'login');
        }

        break;

    case 'activation':
        if (!empty(_post('voucher_only'))) {
            $voucher = _post('voucher_only');
            $tur = ORM::for_table('tbl_user_recharges')
                ->where('username', $voucher)
                ->where('customer_id', '0') // Voucher Only will make customer ID as 0
                ->find_one();
            if ($tur) {
                if ($tur['status'] == 'off') {
                    _alert(Lang::T('Internet Voucher Expired'), 'danger', "login");
                }
                $p = ORM::for_table('tbl_plans')->where('id', $tur['plan_id'])->find_one();
                if ($p) {
                    $dvc = Package::getDevice($p);
                    if ($_app_stage != 'demo') {
                        if (file_exists($dvc)) {
                            if (file_exists($dvc)) {
                                require_once $dvc;
                                $c = [
                                    'fullname' => "Voucher",
                                    'email' => '',
                                    'username' => $voucher,
                                    'password' => $voucher,
                                ];
                                (new $p['device'])->add_customer($c, $p);
                            } else {
                                new Exception(Lang::T("Devices Not Found"));
                            }
                            if (!empty($config['voucher_redirect'])) {
                                r2($config['voucher_redirect'], 's', Lang::T("Voucher activation success, now you can login"));
                            } else {
                                r2(U . "login", 's', Lang::T("Voucher activation success, now you can login"));
                            }
                        } else {
                            new Exception(Lang::T("Devices Not Found"));
                        }
                    }
                    if (!empty($config['voucher_redirect'])) {
                        _alert(Lang::T("Voucher activation success, now you can login"), 'danger', $config['voucher_redirect']);
                    } else {
                        r2(U . "login", 's', Lang::T("Voucher activation success, you are connected to internet"));
                    }
                } else {
                    _alert(Lang::T('Internet Plan Expired'), 'danger', "login");
                }
            } else {
                $v = ORM::for_table('tbl_voucher')->where('code', $voucher)->find_one();
                if (!$v) {
                    _alert(Lang::T('Voucher invalid'), 'danger', "login");
                }
                if ($v['status'] == 0) {
                    if (Package::rechargeUser(0, $v['routers'], $v['id_plan'], "Voucher", $voucher)) {
                        $v->status = "1";
                        $v->save();
                        $tur = ORM::for_table('tbl_user_recharges')->where('username', $voucher)->find_one();
                        if ($tur) {
                            $p = ORM::for_table('tbl_plans')->where('id', $tur['plan_id'])->find_one();
                            if ($p) {
                                $dvc = Package::getDevice($p);
                                if ($_app_stage != 'demo') {
                                    if (file_exists($dvc)) {
                                        if (file_exists($dvc)) {
                                            require_once $dvc;
                                            $c = [
                                                'fullname' => "Voucher",
                                                'email' => '',
                                                'username' => $voucher,
                                                'password' => $voucher,
                                            ];
                                            (new $p['device'])->add_customer($c, $p);
                                        } else {
                                            new Exception(Lang::T("Devices Not Found"));
                                        }
                                        if (!empty($config['voucher_redirect'])) {
                                            r2($config['voucher_redirect'], 's', Lang::T("Voucher activation success, now you can login"));
                                        } else {
                                            r2(U . "login", 's', Lang::T("Voucher activation success, now you can login"));
                                        }
                                    } else {
                                        new Exception(Lang::T("Devices Not Found"));
                                    }
                                }
                                if (!empty($config['voucher_redirect'])) {
                                    _alert(Lang::T("Voucher activation success, now you can login"), 'danger', $config['voucher_redirect']);
                                } else {
                                    r2(U . "login", 's', Lang::T("Voucher activation success, you are connected to internet"));
                                }
                            } else {
                                _alert(Lang::T('Internet Plan Expired'), 'danger', "login");
                            }
                        } else {
                            _alert(Lang::T('Voucher activation failed'), 'danger', "login");
                        }
                    } else {
                        _alert(Lang::T('Voucher activation failed'), 'danger', "login");
                    }
                } else {
                    _alert(Lang::T('Internet Voucher Expired'), 'danger', "login");
                }
            }
        } else {
            $voucher = _post('voucher');
            $username = _post('username');
            $v1 = ORM::for_table('tbl_voucher')->where('code', $voucher)->find_one();
            if ($v1) {
                // voucher exists, check customer exists or not
                $user = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
                if (!$user) {
                    $d = ORM::for_table('tbl_customers')->create();
                    $d->username = alphanumeric($username, "+_.@-");
                    $d->password = $voucher;
                    $d->fullname = '';
                    $d->address = '';
                    $d->email = '';
                    $d->phonenumber = (strlen($username) < 21) ? $username : '';
                    if ($d->save()) {
                        $user = ORM::for_table('tbl_customers')->where('username', $username)->find_one($d->id());
                        if (!$user) {
                            r2(U . 'login', 'e', Lang::T('Voucher activation failed'));
                        }
                    } else {
                        _alert(Lang::T('Login Successful'), 'success', "dashboard");
                        r2(U . 'login', 'e', Lang::T('Voucher activation failed') . '.');
                    }
                }
                if ($v1['status'] == 0) {
                    $oldPass = $user['password'];
                    // change customer password to voucher code
                    $user->password = $voucher;
                    $user->save();
                    // voucher activation
                    if (Package::rechargeUser($user['id'], $v1['routers'], $v1['id_plan'], "Voucher", $voucher)) {
                        $v1->status = "1";
                        $v1->used_date = date('Y-m-d H:i:s');
                        $v1->user = $user['username'];
                        $v1->save();
                        $user->last_login = date('Y-m-d H:i:s');
                        $user->save();
                        // add customer to mikrotik
                        if (!empty($_SESSION['nux-mac']) && !empty($_SESSION['nux-ip'])) {
                            try {
                                $p = ORM::for_table('tbl_plans')->where('id', $v1['id_plan'])->find_one();
                                $dvc = Package::getDevice($p);
                                if ($_app_stage != 'demo') {
                                    if (file_exists($dvc)) {
                                        require_once $dvc;
                                        (new $p['device'])->connect_customer($user, $_SESSION['nux-ip'], $_SESSION['nux-mac'], $v1['routers']);
                                        if (!empty($config['voucher_redirect'])) {
                                            r2($config['voucher_redirect'], 's', Lang::T("Voucher activation success, now you can login"));
                                        } else {
                                            r2(U . "login", 's', Lang::T("Voucher activation success, now you can login"));
                                        }
                                    } else {
                                        new Exception(Lang::T("Devices Not Found"));
                                    }
                                }
                                if (!empty($config['voucher_redirect'])) {
                                    r2($config['voucher_redirect'], 's', Lang::T("Voucher activation success, you are connected to internet"));
                                } else {
                                    r2(U . "login", 's', Lang::T("Voucher activation success, you are connected to internet"));
                                }
                            } catch (Exception $e) {
                                if (!empty($config['voucher_redirect'])) {
                                    r2($config['voucher_redirect'], 's', Lang::T("Voucher activation success, now you can login"));
                                } else {
                                    r2(U . "login", 's', Lang::T("Voucher activation success, now you can login"));
                                }
                            }
                        }
                        if (!empty($config['voucher_redirect'])) {
                            r2($config['voucher_redirect'], 's', Lang::T("Voucher activation success, now you can login"));
                        } else {
                            r2(U . "login", 's', Lang::T("Voucher activation success, now you can login"));
                        }
                    } else {
                        // if failed to recharge, restore old password
                        $user->password = $oldPass;
                        $user->save();
                        r2(U . 'login', 'e', Lang::T("Failed to activate voucher"));
                    }
                } else {
                    // used voucher
                    // check if voucher used by this username
                    if ($v1['user'] == $user['username']) {
                        $user->last_login = date('Y-m-d H:i:s');
                        $user->save();
                        if (!empty($_SESSION['nux-mac']) && !empty($_SESSION['nux-ip'])) {
                            try {
                                $p = ORM::for_table('tbl_plans')->where('id', $v1['id_plan'])->find_one();
                                $dvc = Package::getDevice($p);
                                if ($_app_stage != 'demo') {
                                    if (file_exists($dvc)) {
                                        require_once $dvc;
                                        (new $p['device'])->connect_customer($user, $_SESSION['nux-ip'], $_SESSION['nux-mac'], $v1['routers']);
                                        if (!empty($config['voucher_redirect'])) {
                                            r2($config['voucher_redirect'], 's', Lang::T("Voucher activation success, now you can login"));
                                        } else {
                                            r2(U . "login", 's', Lang::T("Voucher activation success, now you can login"));
                                        }
                                    } else {
                                        new Exception(Lang::T("Devices Not Found"));
                                    }
                                }
                                if (!empty($config['voucher_redirect'])) {
                                    r2($config['voucher_redirect'], 's', Lang::T("Voucher activation success, you are connected to internet"));
                                } else {
                                    r2(U . "login", 's', Lang::T("Voucher activation success, now you can login"));
                                }
                            } catch (Exception $e) {
                                if (!empty($config['voucher_redirect'])) {
                                    r2($config['voucher_redirect'], 's', Lang::T("Voucher activation success, now you can login"));
                                } else {
                                    r2(U . "login", 's', Lang::T("Voucher activation success, now you can login"));
                                }
                            }
                        } else {
                            if (!empty($config['voucher_redirect'])) {
                                r2($config['voucher_redirect'], 's', Lang::T("Voucher activation success, you are connected to internet"));
                            } else {
                                r2(U . "login", 's', Lang::T("Voucher activation success, now you can login"));
                            }
                        }
                    } else {
                        // voucher used by other customer
                        r2(U . 'login', 'e', Lang::T('Voucher Not Valid'));
                    }
                }
            } else {
                _msglog('e', Lang::T('Invalid Username or Password'));
                r2(U . 'login');
            }
        }
    default:
        run_hook('customer_view_login'); #HOOK
        if ($config['disable_registration'] == 'yes') {
            $ui->assign('code', alphanumeric(_get('code'), "-"));
            $ui->display('user-login-noreg.tpl');
        } else {
            $ui->display('user-login.tpl');
        }
        break;
}
