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
            $id = Radius::addNas($shortname, $nasname, $ports, $secret, $description, $type, $server, $community);
            if ($id > 0) {
                r2(U . 'radius/nas-edit/' . $id, 's', "NAS Added");
            } else {
                r2(U . 'radius/nas-add/', 'e', "NAS Added Failed");
            }
        } else {
            r2(U . 'radius/nas-add', 'e', $msg);
        }
        break;
    case 'nas-edit':
        $ui->assign('_system_menu', 'network');
        $ui->assign('_title', "Network Access Server");

        $id  = $routes['2'];
        $d = ORM::for_table('nas', 'radius')->find_one($id);
        if (!$d) {
            $d = ORM::for_table('nas', 'radius')->where_equal('shortname', _get('name'))->find_one();
        }
        if ($d) {
            $ui->assign('d', $d);
            $ui->display('radius-nas-edit.tpl');
        } else {
            r2(U . 'radius/list', 'e', $_L['Account_Not_Found']);
        }

        break;
    case 'nas-edit-post':
        $id  = $routes['2'];
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
        if ($msg == '') {
            $d->nasname = $nasname;
            $d->shortname = $shortname;
            $d->secret = $secret;
            $d->ports = $ports;
            $d->type = $type;
            $d->server = $server;
            $d->community = $community;
            $d->description = $description;
            $d->save();
            if (Radius::updateNas($id, $shortname, $nasname, $ports, $secret, $description, $type, $server, $community)) {
                r2(U . 'radius/nas-edit/' . $id, 's', "NAS Saved");
            } else {
                r2(U . 'radius/nas-add', 'e', 'NAS NOT Exists');
            }
        } else {
            r2(U . 'radius/nas-add', 'e', $msg);
        }
        break;
    case 'nas-delete':
        $id  = $routes['2'];
        $d = ORM::for_table('nas', 'radius')->find_one($id);
        if ($d) {
            $d->delete();
        } else {
            r2(U . 'radius/nas-list', 'e', 'NAS Not found');
        }
    default:
        $ui->assign('_system_menu', 'network');
        $ui->assign('_title', "Network Access Server");
        $name = _post('name');
        if (empty($name)) {
            $nas = ORM::for_table('nas', 'radius')->find_many();
        } else {
            $nas = ORM::for_table('nas', 'radius')
                ->where_like('nasname', $name)
                ->where_like('shortname', $name)
                ->where_like('description', $name)
                ->find_many();
        }
        $ui->assign('name', $name);
        $ui->assign('nas', $nas);
        $ui->display('radius-nas.tpl');
}
