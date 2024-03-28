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
            $m = Mikrotik::info($bill['routers']);
            $client = Mikrotik::getClient($m['ip_address'], $m['username'], $m['password']);
            if (Mikrotik::isUserLogin($client, $user['username'])) {
                die('<a href="' . U . 'home&mikrotik=logout&id='.$bill['id'].'" onclick="return confirm(\''.Lang::T('Disconnect Internet?').'\')" class="btn btn-success btn-xs btn-block">'.Lang::T('You are Online, Logout?').'</a>');
            } else {
                if (!empty($_SESSION['nux-mac']) && !empty($_SESSION['nux-ip'])) {
                    die('<a href="' . U . 'home&mikrotik=login&id='.$bill['id'].'" onclick="return confirm(\''.Lang::T('Connect to Internet?').'\')" class="btn btn-danger btn-xs btn-block">'.Lang::T('Not Online, Login now?').'</a>');
                }else{
                    die(Lang::T('Your account not connected to internet'));
                }
            }
        } else {
            die('--');
        }
        break;
    default:
        $ui->display('404.tpl');
}
