<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/
_admin();
$ui->assign('_title', Lang::T('Plugin Manager'));
$ui->assign('_system_menu', 'settings');

$action = $routes['1'];
$ui->assign('_admin', $admin);


if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
}

$routelyVpn = function () {
    // Routely (an optional plugin): the card that offers its VPN for a router with no public IP, and which addresses lead through it
    return function_exists('routely_vpn_core_info') ? routely_vpn_core_info() : ['installed' => function_exists('routely'), 'devices' => 0, 'addresses' => []];
};
/** A NAS's CoA address (host:port) when it is not its IP address: a router with no public IP, reached through a forwarded port. */
$saveCoa = function ($nasId, $coa) {
    global $config;
    $key = 'radius_coa_' . (int) $nasId;
    $row = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
    if ($coa === '') {
        if ($row) {
            $row->delete();
        }
        unset($config[$key]);
        return;
    }
    if (!$row) {
        $row = ORM::for_table('tbl_appconfig')->create();
        $row->setting = $key;
    }
    $row->value = $coa;
    $row->save();
    $config[$key] = $coa;
};
$coaOk = function ($coa) {
    return $coa === '' || preg_match('/^[A-Za-z0-9.-]{1,253}:\d{1,5}$/', $coa);
};

switch ($action) {

    case 'nas-add':
        $ui->assign('_system_menu', 'radius');
        $ui->assign('_title', "Network Access Server");
        $ui->assign('routers', ORM::for_table('tbl_routers')->find_many());
        $ui->assign('routely_vpn', $routelyVpn());
        $ui->display('admin/radius/nas-add.tpl');
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
        $routers = _post('routers');
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
        $coa = trim((string) _post('coa'));
        if (!$coaOk($coa)) {
            $msg .= Lang::T('CoA address must look like host:port, such as vpn1.example.com:34567') . '<br>';
        }
        if ($msg == '') {
            require_once $DEVICE_PATH . DIRECTORY_SEPARATOR . "Radius.php";
            $newId = (new Radius())->nasAdd($shortname, $nasname, $ports, $secret, $routers, $description, $type, $server, $community);
            if ($newId > 0) {
                $saveCoa($newId, $coa);
                r2(getUrl('radius/nas-list/'), 's', "NAS Added");
            } else {
                r2(getUrl('radius/nas-add/'), 'e', "NAS Added Failed");
            }
        } else {
            r2(getUrl('radius/nas-add'), 'e', $msg);
        }
        break;
    case 'nas-edit':
        $ui->assign('_system_menu', 'radius');
        $ui->assign('_title', "Network Access Server");

        $id  = $routes['2'];
        $d = ORM::for_table('nas', 'radius')->find_one($id);
        if (!$d) {
            $d = ORM::for_table('nas', 'radius')->where_equal('shortname', _get('name'))->find_one();
        }
        if ($d) {
            $ui->assign('routers', ORM::for_table('tbl_routers')->find_many());
            $ui->assign('d', $d);
            $ui->assign('routely_vpn', $routelyVpn());
            $ui->display('admin/radius/nas-edit.tpl');
        } else {
            r2(getUrl('radius/list'), 'e', Lang::T('Account Not Found'));
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
        $routers = _post('routers');
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
        $coa = trim((string) _post('coa'));
        if (!$coaOk($coa)) {
            $msg .= Lang::T('CoA address must look like host:port, such as vpn1.example.com:34567') . '<br>';
        }
        if ($msg == '') {
            require_once $DEVICE_PATH . DIRECTORY_SEPARATOR . "Radius.php";
            if ((new Radius())->nasUpdate($id, $shortname, $nasname, $ports, $secret, $routers, $description, $type, $server, $community)) {
                $saveCoa($id, $coa);
                r2(getUrl('radius/list/'), 's', "NAS Saved");
            } else {
                r2(getUrl('radius/nas-add'), 'e', 'NAS NOT Exists');
            }
        } else {
            r2(getUrl('radius/nas-add'), 'e', $msg);
        }
        break;
    case 'nas-delete':
        $id  = $routes['2'];
        $d = ORM::for_table('nas', 'radius')->find_one($id);
        if ($d) {
            $d->delete();
        } else {
            r2(getUrl('radius/nas-list'), 'e', 'NAS Not found');
        }
    default:
        $ui->assign('_system_menu', 'radius');
        $ui->assign('_title', "Network Access Server");
        $name = _post('name');
        if (empty($name)) {
            $query = ORM::for_table('nas', 'radius');
            $nas = Paginator::findMany($query);
        } else {
            $query = ORM::for_table('nas', 'radius')
                ->where_like('nasname', $search)
                ->where_like('shortname', $search)
                ->where_like('description', $search);
            $nas = Paginator::findMany($query, ['name' => $name]);
        }
        $ui->assign('name', $name);
        $ui->assign('nas', $nas);
        $ui->assign('routely_vpn', $routelyVpn());
        $ui->display('admin/radius/nas.tpl');
}
