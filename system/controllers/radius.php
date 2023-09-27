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

    case 'nas-list':
        $ui->assign('_system_menu', 'network');
        $ui->assign('_title', "Network Access Server");
        $nas = ORM::for_table('nas', 'radius')->find_many();
        $ui->assign('nas', $nas);
        $ui->display('radius-nas.tpl');
        break;
    case 'nas-add':
        $ui->assign('_system_menu', 'network');
        $ui->assign('_title', "Network Access Server");
        $ui->display('radius-nas-add.tpl');
        break;
    case 'nas-add-post':
        $shortname = _post('shortname');
        $nasname = _post('nasname');
        $secret = _post('secret');
        $ports = _post('ports', null);
        $type = _post('type', 'other');
        $server = _post('server', null);
        $community = _post('community', null);
        $description = _post('description');
        $msg = '';

        if (Validator::Length($shortname, 30, 2) == false) {
            $msg .= 'Name should be between 3 to 30 characters' . '<br>';
        }
        if (empty($ports)) {
            $ports = null;
        }
        if (empty($server)) {
            $server = null;
        }
        if (empty($community)) {
            $community = null;
        }
        if (empty($type)) {
            $type = null;
        }
        $d = ORM::for_table('nas', 'radius')->where('nasname', $nasname)->find_one();
        if ($d) {
            $msg .= 'NAS IP Exists<br>';
        }
        if ($msg == '') {
            $b = ORM::for_table('nas', 'radius')->create();
            $b->nasname = $nasname;
            $b->shortname = $shortname;
            $b->secret = $secret;
            $b->ports = $ports;
            $b->type = $type;
            $b->server = $server;
            $b->community = $community;
            $b->description = $description;
            $b->save();
            $id = $b->id();
            if($id>0){
                r2(U . 'radius/nas-edit/'.$id, 's', "NAS Added");
            }else{
                r2(U . 'radius/nas-add/', 'e', "NAS Added Failed");
            }
        }else{
            r2(U . 'radius/nas-add', 'e', $msg);
        }
        break;
    default:
        $ui->display('a404.tpl');
}
