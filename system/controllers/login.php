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
    r2(getUrl('home'));
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
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            _msglog('e', Lang::T('Invalid or Expired CSRF Token'));
            r2(getUrl('login'));
        }
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
                    $token = User::setCookie($d['id']);
                    $d->last_login = date('Y-m-d H:i:s');
                    $d->save();
                    _log($username . ' ' . Lang::T('Login Successful'), 'User', $d['id']);
                    if ($isApi) {
                        if ($token) {
                            showResult(true, Lang::T('Login Successful'), ['token' => "u." . $token]);
                        } else {
                            showResult(false, Lang::T('Invalid Username or Password'));
                        }
                    }
                    _alert(Lang::T('Login Successful'), 'success', "home");
                } else {
                    _msglog('e', Lang::T('Invalid Username or Password'));
                    _log($username . ' ' . Lang::T('Failed Login'), 'User');
                    r2(getUrl('login'));
                }
            } else {
                _msglog('e', Lang::T('Invalid Username or Password'));
                r2(getUrl('login'));
            }
        } else {
            _msglog('e', Lang::T('Invalid Username or Password'));
            r2(getUrl('login'));
        }

        break;

    case 'activation':
        if (!empty(_post('voucher_only'))) {
            $csrf_token = _post('csrf_token');
            if (!Csrf::check($csrf_token)) {
                _msglog('e', Lang::T('Invalid or Expired CSRF Token'));
                r2(getUrl('login'));
            }
            $voucher = Text::alphanumeric(_post('voucher_only'), "-_.,");
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
                                r2(getUrl('login'), 's', Lang::T("Voucher activation success, now you can login"));
                            }
                        } else {
                            new Exception(Lang::T("Devices Not Found"));
                        }
                    }
                    if (!empty($config['voucher_redirect'])) {
                        _alert(Lang::T("Voucher activation success, now you can login"), 'danger', $config['voucher_redirect']);
                    } else {
                        r2(getUrl('login'), 's', Lang::T("Voucher activation success, you are connected to internet"));
                    }
                } else {
                    _alert(Lang::T('Internet Plan Expired'), 'danger', "login");
                }
            } else {
                $v = ORM::for_table('tbl_voucher')->whereRaw("BINARY code = '$voucher'")->find_one();
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
                                            r2(getUrl('login'), 's', Lang::T("Voucher activation success, now you can login"));
                                        }
                                    } else {
                                        new Exception(Lang::T("Devices Not Found"));
                                    }
                                }
                                if (!empty($config['voucher_redirect'])) {
                                    _alert(Lang::T("Voucher activation success, now you can login"), 'danger', $config['voucher_redirect']);
                                } else {
                                    r2(getUrl('login'), 's', Lang::T("Voucher activation success, you are connected to internet"));
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
            $voucher = Text::alphanumeric(_post('voucher'), "-_.,");
            $username = _post('username');
            $v1 = ORM::for_table('tbl_voucher')->whereRaw("BINARY code = '$voucher'")->find_one();
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
                            r2(getUrl('login'), 'e', Lang::T('Voucher activation failed'));
                        }
                    } else {
                        _alert(Lang::T('Login Successful'), 'success', "dashboard");
                        r2(getUrl('login'), 'e', Lang::T('Voucher activation failed') . '.');
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
                                            r2(getUrl('login'), 's', Lang::T("Voucher activation success, now you can login"));
                                        }
                                    } else {
                                        new Exception(Lang::T("Devices Not Found"));
                                    }
                                }
                                if (!empty($config['voucher_redirect'])) {
                                    r2($config['voucher_redirect'], 's', Lang::T("Voucher activation success, you are connected to internet"));
                                } else {
                                    r2(getUrl('login'), 's', Lang::T("Voucher activation success, you are connected to internet"));
                                }
                            } catch (Exception $e) {
                                if (!empty($config['voucher_redirect'])) {
                                    r2($config['voucher_redirect'], 's', Lang::T("Voucher activation success, now you can login"));
                                } else {
                                    r2(getUrl('login'), 's', Lang::T("Voucher activation success, now you can login"));
                                }
                            }
                        }
                        if (!empty($config['voucher_redirect'])) {
                            r2($config['voucher_redirect'], 's', Lang::T("Voucher activation success, now you can login"));
                        } else {
                            r2(getUrl('login'), 's', Lang::T("Voucher activation success, now you can login"));
                        }
                    } else {
                        // if failed to recharge, restore old password
                        $user->password = $oldPass;
                        $user->save();
                        r2(getUrl('login'), 'e', Lang::T("Failed to activate voucher"));
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
                                            r2(getUrl('login'), 's', Lang::T("Voucher activation success, now you can login"));
                                        }
                                    } else {
                                        new Exception(Lang::T("Devices Not Found"));
                                    }
                                }
                                if (!empty($config['voucher_redirect'])) {
                                    r2($config['voucher_redirect'], 's', Lang::T("Voucher activation success, you are connected to internet"));
                                } else {
                                    r2(getUrl('login'), 's', Lang::T("Voucher activation success, now you can login"));
                                }
                            } catch (Exception $e) {
                                if (!empty($config['voucher_redirect'])) {
                                    r2($config['voucher_redirect'], 's', Lang::T("Voucher activation success, now you can login"));
                                } else {
                                    r2(getUrl('login'), 's', Lang::T("Voucher activation success, now you can login"));
                                }
                            }
                        } else {
                            if (!empty($config['voucher_redirect'])) {
                                r2($config['voucher_redirect'], 's', Lang::T("Voucher activation success, you are connected to internet"));
                            } else {
                                r2(getUrl('login'), 's', Lang::T("Voucher activation success, now you can login"));
                            }
                        }
                    } else {
                        // voucher used by other customer
                        r2(getUrl('login'), 'e', Lang::T('Voucher Not Valid'));
                    }
                }
            } else {
                _msglog('e', Lang::T('Invalid Username or Password'));
                r2(getUrl('login'));
            }
        }
    default:
        run_hook('customer_view_login'); #HOOK
        $csrf_token = Csrf::generateAndStoreToken();
        if ($config['disable_registration'] == 'yes') {
            $ui->assign('csrf_token', $csrf_token);
            $ui->assign('_title', Lang::T('Activation'));
            $ui->assign('code', alphanumeric(_get('code'), "-"));
            $ui->display('customer/login-noreg.tpl');
        } else {
            $UPLOAD_URL_PATH = str_replace($root_path, '',  $UPLOAD_PATH);
            if (!empty($config['login_page_logo']) && file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_logo'])) {
                $login_logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_logo'];
            } elseif (file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'login-logo.png')) {
                $login_logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'login-logo.png';
            } else {
                $login_logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'login-logo.default.png';
            }

            if (!empty($config['login_page_wallpaper']) && file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_wallpaper'])) {
                $wallpaper = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_wallpaper'];
            } elseif (file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'wallpaper.png')) {
                $wallpaper = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'wallpaper.png';
            } else {
                $wallpaper = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'wallpaper.default.png';
            }

            if (!empty($config['login_page_favicon']) && file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_favicon'])) {
                $favicon = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_favicon'];
            } elseif (file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'favicon.png')) {
                $favicon = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'favicon.png';
            } else {
                $favicon = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'favicon.default.png';
            }

            $ui->assign('login_logo', $login_logo);
            $ui->assign('wallpaper', $wallpaper);
            $ui->assign('favicon', $favicon);
            $ui->assign('csrf_token', $csrf_token);
            $ui->assign('_title', Lang::T('Login'));
            switch ($config['login_page_type']) {
                case 'custom':
                    $ui->display('customer/login-custom-' . $config['login_Page_template'] . '.tpl');
                    break;
                default:
                    $ui->display('customer/login.tpl');
                    break;
            }
        }

        break;
}
