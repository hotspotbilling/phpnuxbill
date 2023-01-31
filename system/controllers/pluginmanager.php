<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)

 **/
_admin();
$ui->assign('_title', $_L['Plugin Manager']);
$ui->assign('_system_menu', 'settings');

$action = $routes['1'];
$admin = Admin::_info();
$ui->assign('_admin', $admin);


if ($admin['user_type'] != 'Admin') {
    r2(U . "dashboard", 'e', $_L['Do_Not_Access']);
}

switch ($action) {

    default:
        $cache = 'system/cache/plugin_repository.json';
        if (file_exists($cache) && time() - filemtime($cache) > (24 * 60 * 60)) {
            $json = json_decode(file_get_contents($cache), true);
        }
        $data = file_get_contents('https://hotspotbilling.github.io/Plugin-Repository/repository.json');
        file_put_contents($cache, $data);
        $json = json_decode($data, true);
        $ui->assign('plugins', $json['plugins']);
        $ui->assign('pgs', $json['payment_gateway']);
        $ui->display('plugin-manager.tpl');
}
