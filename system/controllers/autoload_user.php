<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * used for ajax
 **/

_auth();

$action = $routes['1'];
$user = User::_info();

switch ($action) {
    case 'isLogin':
        $bill = ORM::for_table('tbl_user_recharges')->where('id', $routes['2'])->where('username', $user['username'])->findOne();
        if ($bill['type'] == 'Hotspot' && $bill['status'] == 'on') {
            $p = ORM::for_table('tbl_plans')->find_one($bill['plan_id']);
            $dvc = Package::getDevice($p);
            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    if ((new $p['device'])->online_customer($user, $bill['routers'])) {
                        die('<a href="' . U . 'home&mikrotik=logout&id=' . $bill['id'] . '" onclick="return confirm(\'' . Lang::T('Disconnect Internet?') . '\')" class="btn btn-success btn-xs btn-block">' . Lang::T('You are Online, Logout?') . '</a>');
                    } else {
                        if (!empty($_SESSION['nux-mac']) && !empty($_SESSION['nux-ip'])) {
                            die('<a href="' . U . 'home&mikrotik=login&id=' . $bill['id'] . '" onclick="return confirm(\'' . Lang::T('Connect to Internet?') . '\')" class="btn btn-danger btn-xs btn-block">' . Lang::T('Not Online, Login now?') . '</a>');
                        } else {
                            die(Lang::T('-'));
                        }
                    }
                } else {
                    new Exception(Lang::T("Devices Not Found"));
                }
            }
        } else {
            die('--');
        }
        break;
    default:
        $ui->display('404.tpl');
}
