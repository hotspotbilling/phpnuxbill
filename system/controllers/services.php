<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)

 **/
_admin();
$ui->assign('_title', $_L['Hotspot_Plans']);
$ui->assign('_system_menu', 'services');

$action = $routes['1'];
$admin = Admin::_info();
$ui->assign('_admin', $admin);

if ($admin['user_type'] != 'Admin' and $admin['user_type'] != 'Sales') {
    r2(U . "dashboard", 'e', $_L['Do_Not_Access']);
}

use PEAR2\Net\RouterOS;

require_once 'system/autoload/PEAR2/Autoload.php';

switch ($action) {
    case 'hotspot':
        $ui->assign('xfooter', '<script type="text/javascript" src="ui/lib/c/hotspot.js"></script>');

        $name = _post('name');
        if ($name != '') {
            $paginator = Paginator::bootstrap('tbl_plans', 'name_plan', '%' . $name . '%', 'type', 'Hotspot');
            $d = ORM::for_table('tbl_bandwidth')->join('tbl_plans', array('tbl_bandwidth.id', '=', 'tbl_plans.id_bw'))->where('tbl_plans.type', 'Hotspot')->where_like('tbl_plans.name_plan', '%' . $name . '%')->offset($paginator['startpoint'])->limit($paginator['limit'])->find_many();
        } else {
            $paginator = Paginator::bootstrap('tbl_plans', 'type', 'Hotspot');
            $d = ORM::for_table('tbl_bandwidth')->join('tbl_plans', array('tbl_bandwidth.id', '=', 'tbl_plans.id_bw'))->where('tbl_plans.type', 'Hotspot')->offset($paginator['startpoint'])->limit($paginator['limit'])->find_many();
        }

        $ui->assign('d', $d);
        $ui->assign('paginator', $paginator);
        run_hook('view_list_plans'); #HOOK
        $ui->display('hotspot.tpl');
        break;

    case 'add':
        $d = ORM::for_table('tbl_bandwidth')->find_many();
        $ui->assign('d', $d);
        $r = ORM::for_table('tbl_routers')->find_many();
        $ui->assign('r', $r);
        run_hook('view_add_plan'); #HOOK
        $ui->display('hotspot-add.tpl');
        break;

    case 'edit':
        $id  = $routes['2'];
        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            $ui->assign('d', $d);
            $b = ORM::for_table('tbl_bandwidth')->find_many();
            $ui->assign('b', $b);
            $r = ORM::for_table('tbl_routers')->find_many();
            $ui->assign('r', $r);
            run_hook('view_edit_plan'); #HOOK
            $ui->display('hotspot-edit.tpl');
        } else {
            r2(U . 'services/hotspot', 'e', $_L['Account_Not_Found']);
        }
        break;

    case 'delete':
        $id  = $routes['2'];

        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            run_hook('delete_plan'); #HOOK
            if (!$config['radius_mode']) {
                $mikrotik = Mikrotik::info($d['routers']);
                $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                Mikrotik::removeHotspotPlan($client, $d['name_plan']);
            }

            $d->delete();

            r2(U . 'services/hotspot', 's', $_L['Delete_Successfully']);
        }
        break;

    case 'add-post':
        $name = _post('name');
        $typebp = _post('typebp');
        $limit_type = _post('limit_type');
        $time_limit = _post('time_limit');
        $time_unit = _post('time_unit');
        $data_limit = _post('data_limit');
        $data_unit = _post('data_unit');
        $id_bw = _post('id_bw');
        $price = _post('price');
        $sharedusers = _post('sharedusers');
        $validity = _post('validity');
        $validity_unit = _post('validity_unit');
        $routers = _post('routers');
        $enabled = _post('enabled');

        $msg = '';
        if (Validator::UnsignedNumber($validity) == false) {
            $msg .= 'The validity must be a number' . '<br>';
        }
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '' or $id_bw == '' or $price == '' or $validity == '' or $routers == '') {
            $msg .= $_L['All_field_is_required'] . '<br>';
        }

        $d = ORM::for_table('tbl_plans')->where('name_plan', $name)->where('type', 'Hotspot')->find_one();
        if ($d) {
            $msg .= $_L['Plan_already_exist'] . '<br>';
        }

        run_hook('add_plan'); #HOOK

        if ($msg == '') {
            $b = ORM::for_table('tbl_bandwidth')->where('id', $id_bw)->find_one();
            if ($b['rate_down_unit'] == 'Kbps') {
                $unitdown = 'K';
            } else {
                $unitdown = 'M';
            }
            if ($b['rate_up_unit'] == 'Kbps') {
                $unitup = 'K';
            } else {
                $unitup = 'M';
            }
            $rate = $b['rate_up'] . $unitup . "/" . $b['rate_down'] . $unitdown;

            if (!$config['radius_mode']) {
                $mikrotik = Mikrotik::info($routers);
                $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                Mikrotik::addHotspotPlan($client, $name, $sharedusers, $rate);
            }

            $d = ORM::for_table('tbl_plans')->create();
            $d->name_plan = $name;
            $d->id_bw = $id_bw;
            $d->price = $price;
            $d->type = 'Hotspot';
            $d->typebp = $typebp;
            $d->limit_type = $limit_type;
            $d->time_limit = $time_limit;
            $d->time_unit = $time_unit;
            $d->data_limit = $data_limit;
            $d->data_unit = $data_unit;
            $d->validity = $validity;
            $d->validity_unit = $validity_unit;
            $d->shared_users = $sharedusers;
            $d->routers = $routers;
            $d->enabled = $enabled;
            $d->save();

            r2(U . 'services/hotspot', 's', $_L['Created_Successfully']);
        } else {
            r2(U . 'services/add', 'e', $msg);
        }
        break;


    case 'edit-post':
        $id = _post('id');
        $name = _post('name');
        $id_bw = _post('id_bw');
        $typebp = _post('typebp');
        $price = _post('price');
        $limit_type = _post('limit_type');
        $time_limit = _post('time_limit');
        $time_unit = _post('time_unit');
        $data_limit = _post('data_limit');
        $data_unit = _post('data_unit');
        $sharedusers = _post('sharedusers');
        $validity = _post('validity');
        $validity_unit = _post('validity_unit');
        $routers = _post('routers');
        $enabled = _post('enabled');

        $msg = '';
        if (Validator::UnsignedNumber($validity) == false) {
            $msg .= 'The validity must be a number' . '<br>';
        }
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '' or $id_bw == '' or $price == '' or $validity == '' or $routers == '') {
            $msg .= $_L['All_field_is_required'] . '<br>';
        }

        $d = ORM::for_table('tbl_plans')->where('id', $id)->find_one();
        if ($d) {
        } else {
            $msg .= $_L['Data_Not_Found'] . '<br>';
        }
        run_hook('edit_plan'); #HOOK
        if ($msg == '') {
            $b = ORM::for_table('tbl_bandwidth')->where('id', $id_bw)->find_one();
            if ($b['rate_down_unit'] == 'Kbps') {
                $unitdown = 'K';
            } else {
                $unitdown = 'M';
            }
            if ($b['rate_up_unit'] == 'Kbps') {
                $unitup = 'K';
            } else {
                $unitup = 'M';
            }
            $rate = $b['rate_up'] . $unitup . "/" . $b['rate_down'] . $unitdown;

            if (!$config['radius_mode']) {
                $mikrotik = Mikrotik::info($routers);
                $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                Mikrotik::setHotspotPlan($client, $name, $sharedusers, $rate);
            }

            $d->name_plan = $name;
            $d->id_bw = $id_bw;
            $d->price = $price;
            $d->typebp = $typebp;
            $d->limit_type = $limit_type;
            $d->time_limit = $time_limit;
            $d->time_unit = $time_unit;
            $d->data_limit = $data_limit;
            $d->data_unit = $data_unit;
            $d->validity = $validity;
            $d->validity_unit = $validity_unit;
            $d->shared_users = $sharedusers;
            $d->routers = $routers;
            $d->enabled = $enabled;
            $d->save();

            r2(U . 'services/hotspot', 's', $_L['Updated_Successfully']);
        } else {
            r2(U . 'services/edit/' . $id, 'e', $msg);
        }
        break;

    case 'pppoe':
        $ui->assign('_title', $_L['PPPOE_Plans']);
        $ui->assign('xfooter', '<script type="text/javascript" src="ui/lib/c/pppoe.js"></script>');

        $name = _post('name');
        if ($name != '') {
            $paginator = Paginator::bootstrap('tbl_plans', 'name_plan', '%' . $name . '%', 'type', 'Hotspot');
            $d = ORM::for_table('tbl_bandwidth')->join('tbl_plans', array('tbl_bandwidth.id', '=', 'tbl_plans.id_bw'))->where('tbl_plans.type', 'PPPOE')->where_like('tbl_plans.name_plan', '%' . $name . '%')->offset($paginator['startpoint'])->limit($paginator['limit'])->find_many();
        } else {
            $paginator = Paginator::bootstrap('tbl_plans', 'type', 'Hotspot');
            $d = ORM::for_table('tbl_bandwidth')->join('tbl_plans', array('tbl_bandwidth.id', '=', 'tbl_plans.id_bw'))->where('tbl_plans.type', 'PPPOE')->offset($paginator['startpoint'])->limit($paginator['limit'])->find_many();
        }

        $ui->assign('d', $d);
        $ui->assign('paginator', $paginator);
        run_hook('view_list_ppoe'); #HOOK
        $ui->display('pppoe.tpl');
        break;

    case 'pppoe-add':
        $ui->assign('_title', $_L['PPPOE_Plans']);
        $d = ORM::for_table('tbl_bandwidth')->find_many();
        $ui->assign('d', $d);
        $p = ORM::for_table('tbl_pool')->find_many();
        $ui->assign('p', $p);
        $r = ORM::for_table('tbl_routers')->find_many();
        $ui->assign('r', $r);
        run_hook('view_add_ppoe'); #HOOK
        $ui->display('pppoe-add.tpl');
        break;

    case 'pppoe-edit':
        $ui->assign('_title', $_L['PPPOE_Plans']);
        $id  = $routes['2'];
        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            $ui->assign('d', $d);
            $b = ORM::for_table('tbl_bandwidth')->find_many();
            $ui->assign('b', $b);
            $p = ORM::for_table('tbl_pool')->find_many();
            $ui->assign('p', $p);
            $r = ORM::for_table('tbl_routers')->find_many();
            $ui->assign('r', $r);
            run_hook('view_edit_ppoe'); #HOOK
            $ui->display('pppoe-edit.tpl');
        } else {
            r2(U . 'services/pppoe', 'e', $_L['Account_Not_Found']);
        }
        break;

    case 'pppoe-delete':
        $id  = $routes['2'];

        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            run_hook('delete_ppoe'); #HOOK
            if (!$config['radius_mode']) {
                $mikrotik = Mikrotik::info($d['routers']);
                $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                Mikrotik::removePpoePlan($client, $d['name_plan']);
            }
            $d->delete();

            r2(U . 'services/pppoe', 's', $_L['Delete_Successfully']);
        }
        break;

    case 'pppoe-add-post':
        $name = _post('name_plan');
        $id_bw = _post('id_bw');
        $price = _post('price');
        $validity = _post('validity');
        $validity_unit = _post('validity_unit');
        $routers = _post('routers');
        $pool = _post('pool_name');
        $enabled = _post('enabled');

        $msg = '';
        if (Validator::UnsignedNumber($validity) == false) {
            $msg .= 'The validity must be a number' . '<br>';
        }
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '' or $id_bw == '' or $price == '' or $validity == '' or $routers == '' or $pool == '') {
            $msg .= $_L['All_field_is_required'] . '<br>';
        }

        $d = ORM::for_table('tbl_plans')->where('name_plan', $name)->find_one();
        if ($d) {
            $msg .= $_L['Plan_already_exist'] . '<br>';
        }
        run_hook('add_ppoe'); #HOOK
        if ($msg == '') {
            $b = ORM::for_table('tbl_bandwidth')->where('id', $id_bw)->find_one();
            if ($b['rate_down_unit'] == 'Kbps') {
                $unitdown = 'K';
            } else {
                $unitdown = 'M';
            }
            if ($b['rate_up_unit'] == 'Kbps') {
                $unitup = 'K';
            } else {
                $unitup = 'M';
            }
            $rate = $b['rate_up'] . $unitup . "/" . $b['rate_down'] . $unitdown;

            if (!$config['radius_mode']) {
                $mikrotik = Mikrotik::info($routers);
                $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                Mikrotik::addPpoePlan($client, $name, $pool, $rate);
            }

            $d = ORM::for_table('tbl_plans')->create();
            $d->type = 'PPPOE';
            $d->name_plan = $name;
            $d->id_bw = $id_bw;
            $d->price = $price;
            $d->validity = $validity;
            $d->validity_unit = $validity_unit;
            $d->routers = $routers;
            $d->pool = $pool;
            $d->enabled = $enabled;
            $d->save();

            r2(U . 'services/pppoe', 's', $_L['Created_Successfully']);
        } else {
            r2(U . 'services/pppoe-add', 'e', $msg);
        }
        break;

    case 'edit-pppoe-post':
        $id = _post('id');
        $name = _post('name_plan');
        $id_bw = _post('id_bw');
        $price = _post('price');
        $validity = _post('validity');
        $validity_unit = _post('validity_unit');
        $routers = _post('routers');
        $pool = _post('pool_name');
        $enabled = _post('enabled');

        $msg = '';
        if (Validator::UnsignedNumber($validity) == false) {
            $msg .= 'The validity must be a number' . '<br>';
        }
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '' or $id_bw == '' or $price == '' or $validity == '' or $routers == '' or $pool == '') {
            $msg .= $_L['All_field_is_required'] . '<br>';
        }

        $d = ORM::for_table('tbl_plans')->where('id', $id)->find_one();
        if ($d) {
        } else {
            $msg .= $_L['Data_Not_Found'] . '<br>';
        }
        run_hook('edit_ppoe'); #HOOK
        if ($msg == '') {
            $b = ORM::for_table('tbl_bandwidth')->where('id', $id_bw)->find_one();
            if ($b['rate_down_unit'] == 'Kbps') {
                $unitdown = 'K';
            } else {
                $unitdown = 'M';
            }
            if ($b['rate_up_unit'] == 'Kbps') {
                $unitup = 'K';
            } else {
                $unitup = 'M';
            }
            $rate = $b['rate_up'] . $unitup . "/" . $b['rate_down'] . $unitdown;

            if (!$config['radius_mode']) {
                $mikrotik = Mikrotik::info($routers);
                $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                Mikrotik::setPpoePlan($client, $name, $pool, $rate);
            }

            $d->name_plan = $name;
            $d->id_bw = $id_bw;
            $d->price = $price;
            $d->validity = $validity;
            $d->validity_unit = $validity_unit;
            $d->routers = $routers;
            $d->pool = $pool;
            $d->enabled = $enabled;
            $d->save();

            r2(U . 'services/pppoe', 's', $_L['Updated_Successfully']);
        } else {
            r2(U . 'services/pppoe-edit/' . $id, 'e', $msg);
        }
        break;
    case 'balance':
        $ui->assign('_title', Lang::T('Balance Plans'));
        $name = _post('name');
        if ($name != '') {
            $paginator = Paginator::bootstrap('tbl_plans', 'name_plan', '%' . $name . '%', 'type', 'Balance');
            $d = ORM::for_table('tbl_plans')->where('tbl_plans.type', 'Balance')->where_like('tbl_plans.name_plan', '%' . $name . '%')->offset($paginator['startpoint'])->limit($paginator['limit'])->find_many();
        } else {
            $paginator = Paginator::bootstrap('tbl_plans', 'type', 'Hotspot');
            $d = ORM::for_table('tbl_plans')->where('tbl_plans.type', 'Balance')->offset($paginator['startpoint'])->limit($paginator['limit'])->find_many();
        }

        $ui->assign('d', $d);
        $ui->assign('paginator', $paginator);
        run_hook('view_list_balance'); #HOOK
        $ui->display('balance.tpl');
        break;
    case 'balance-add':
        $ui->assign('_title', Lang::T('Balance Plans'));
        run_hook('view_add_balance'); #HOOK
        $ui->display('balance-add.tpl');
        break;
    case 'balance-edit':
        $ui->assign('_title', Lang::T('Balance Plans'));
        $id  = $routes['2'];
        $d = ORM::for_table('tbl_plans')->find_one($id);
        $ui->assign('d', $d);
        run_hook('view_edit_balance'); #HOOK
        $ui->display('balance-edit.tpl');
        break;
    case 'balance-delete':
        $id  = $routes['2'];

        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            run_hook('delete_balance'); #HOOK
            $d->delete();
            r2(U . 'services/balance', 's', $_L['Delete_Successfully']);
        }
        break;
    case 'balance-edit-post':
        $id = _post('id');
        $name = _post('name');
        $price = _post('price');
        $enabled = _post('enabled');

        $msg = '';
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '') {
            $msg .= $_L['All_field_is_required'] . '<br>';
        }

        $d = ORM::for_table('tbl_plans')->where('id', $id)->find_one();
        if ($d) {
        } else {
            $msg .= $_L['Data_Not_Found'] . '<br>';
        }
        run_hook('edit_ppoe'); #HOOK
        if ($msg == '') {
            $d->name_plan = $name;
            $d->price = $price;
            $d->enabled = $enabled;
            $d->save();

            r2(U . 'services/balance', 's', $_L['Updated_Successfully']);
        } else {
            r2(U . 'services/balance-edit/' . $id, 'e', $msg);
        }
        break;
    case 'balance-add-post':
        $name = _post('name');
        $price = _post('price');
        $enabled = _post('enabled');

        $msg = '';
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '') {
            $msg .= $_L['All_field_is_required'] . '<br>';
        }

        $d = ORM::for_table('tbl_plans')->where('name_plan', $name)->find_one();
        if ($d) {
            $msg .= $_L['Plan_already_exist'] . '<br>';
        }
        run_hook('add_ppoe'); #HOOK
        if ($msg == '') {
            $d = ORM::for_table('tbl_plans')->create();
            $d->type = 'Balance';
            $d->name_plan = $name;
            $d->id_bw = 0;
            $d->price = $price;
            $d->validity = 0;
            $d->validity_unit = 'Months';
            $d->routers = '';
            $d->pool = '';
            $d->enabled = $enabled;
            $d->save();

            r2(U . 'services/balance', 's', $_L['Created_Successfully']);
        } else {
            r2(U . 'services/balance-add', 'e', $msg);
        }
        break;
    default:
        echo 'action not defined';
}
