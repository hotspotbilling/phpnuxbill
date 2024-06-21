<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/
_admin();
$ui->assign('_title', Lang::T('Hotspot Plans'));
$ui->assign('_system_menu', 'services');

$action = $routes['1'];
$ui->assign('_admin', $admin);

if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
}

switch ($action) {
    case 'sync':
        set_time_limit(-1);
        if ($routes['2'] == 'hotspot') {
            $plans = ORM::for_table('tbl_plans')->where('type', 'Hotspot')->find_many();
            $log = '';
            foreach ($plans as $plan) {
                $dvc = Package::getDevice($plan);
                if ($_app_stage != 'demo') {
                    if (file_exists($dvc)) {
                        require_once $dvc;
                        (new $plan['device'])->add_plan($plan);
                        $log .= "DONE : $plan[name_plan], $plan[device]<br>";
                    } else {
                        $log .= "FAILED : $plan[name_plan], $plan[device] | Device Not Found<br>";
                    }
                }
            }
            r2(U . 'services/hotspot', 's', $log);
        } else if ($routes['2'] == 'pppoe') {
            $plans = ORM::for_table('tbl_plans')->where('type', 'PPPOE')->find_many();
            $log = '';
            foreach ($plans as $plan) {
                $dvc = Package::getDevice($plan);
                if ($_app_stage != 'demo') {
                    if (file_exists($dvc)) {
                        require_once $dvc;
                        (new $plan['device'])->add_plan($plan);
                        $log .= "DONE : $plan[name_plan], $plan[device]<br>";
                    } else {
                        $log .= "FAILED : $plan[name_plan], $plan[device] | Device Not Found<br>";
                    }
                }
            }
            r2(U . 'services/pppoe', 's', $log);
        }
        r2(U . 'services/hotspot', 'w', 'Unknown command');
    case 'hotspot':
        $ui->assign('xfooter', '<script type="text/javascript" src="ui/lib/c/hotspot.js"></script>');
        $name = _req('name');
        $type1 = _req('type1');
        $type2 = _req('type2');
        $type3 = _req('type3');
        $bandwidth = _req('bandwidth');
        $valid = _req('valid');
        $device = _req('device');
        $status = _req('status');
        $router = _req('router');
        $ui->assign('type1', $type1);
        $ui->assign('type2', $type2);
        $ui->assign('type3', $type3);
        $ui->assign('bandwidth', $bandwidth);
        $ui->assign('valid', $valid);
        $ui->assign('device', $device);
        $ui->assign('status', $status);
        $ui->assign('router', $router);

        $append_url = "&type1=" . urlencode($type1)
            . "&type2=" . urlencode($type2)
            . "&type3=" . urlencode($type3)
            . "&bandwidth=" . urlencode($bandwidth)
            . "&valid=" . urlencode($valid)
            . "&device=" . urlencode($device)
            . "&status=" . urlencode($status)
            . "&router=" . urlencode($router);

        $bws = ORM::for_table('tbl_plans')->distinct()->select("id_bw")->where('tbl_plans.type', 'Hotspot')->findArray();
        $ids = array_column($bws, 'id_bw');
        if(count($ids)){
            $ui->assign('bws', ORM::for_table('tbl_bandwidth')->select("id")->select('name_bw')->where_id_in($ids)->findArray());
        }else{
            $ui->assign('bws', []);
        }
        $ui->assign('type2s', ORM::for_table('tbl_plans')->getEnum("plan_type"));
        $ui->assign('type3s', ORM::for_table('tbl_plans')->getEnum("typebp"));
        $ui->assign('valids', ORM::for_table('tbl_plans')->getEnum("validity_unit"));
        $ui->assign('routers', array_column(ORM::for_table('tbl_plans')->distinct()->select("routers")->where('tbl_plans.type', 'Hotspot')->whereNotEqual('routers', '')->findArray(), 'routers'));
        $devices = [];
        $files = scandir($DEVICE_PATH);
        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext == 'php') {
                $devices[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }
        $ui->assign('devices', $devices);
        $query = ORM::for_table('tbl_bandwidth')
            ->left_outer_join('tbl_plans', array('tbl_bandwidth.id', '=', 'tbl_plans.id_bw'))
            ->where('tbl_plans.type', 'Hotspot');

        if (!empty($type1)) {
            $query->where('tbl_plans.prepaid', $type1);
        }
        if (!empty($type2)) {
            $query->where('tbl_plans.plan_type', $type2);
        }
        if (!empty($type3)) {
            $query->where('tbl_plans.typebp', $type3);
        }
        if (!empty($bandwidth)) {
            $query->where('tbl_plans.id_bw', $bandwidth);
        }
        if (!empty($valid)) {
            $query->where('tbl_plans.validity_unit', $valid);
        }
        if (!empty($router)) {
            if ($router == 'radius') {
                $query->where('tbl_plans.is_radius', '1');
            } else {
                $query->where('tbl_plans.routers', $router);
            }
        }
        if (!empty($device)) {
            $query->where('tbl_plans.device', $device);
        }
        if (in_array($status, ['0', '1'])) {
            $query->where('tbl_plans.enabled', $status);
        }
        if ($name != '') {
            $query->where_like('tbl_plans.name_plan', '%' . $name . '%');
        }
        $d = Paginator::findMany($query, ['name' => $name], 20, $append_url);
        $ui->assign('d', $d);
        run_hook('view_list_plans'); #HOOK
        $ui->display('hotspot.tpl');
        break;
    case 'add':
        $d = ORM::for_table('tbl_bandwidth')->find_many();
        $ui->assign('d', $d);
        $r = ORM::for_table('tbl_routers')->find_many();
        $ui->assign('r', $r);
        $devices = [];
        $files = scandir($DEVICE_PATH);
        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext == 'php') {
                $devices[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }
        $ui->assign('devices', $devices);
        run_hook('view_add_plan'); #HOOK
        $ui->display('hotspot-add.tpl');
        break;

    case 'edit':
        $id = $routes['2'];
        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            if (empty($d['device'])) {
                if ($d['is_radius']) {
                    $d->device = 'Radius';
                } else {
                    $d->device = 'MikrotikHotspot';
                }
                $d->save();
            }
            $ui->assign('d', $d);
            $b = ORM::for_table('tbl_bandwidth')->find_many();
            $ui->assign('b', $b);
            $devices = [];
            $files = scandir($DEVICE_PATH);
            foreach ($files as $file) {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                if ($ext == 'php') {
                    $devices[] = pathinfo($file, PATHINFO_FILENAME);
                }
            }
            $ui->assign('devices', $devices);
            //select expired plan
            if ($d['is_radius']) {
                $exps = ORM::for_table('tbl_plans')->selects('id', 'name_plan')->where('type', 'Hotspot')->where("is_radius", 1)->findArray();
            } else {
                $exps = ORM::for_table('tbl_plans')->selects('id', 'name_plan')->where('type', 'Hotspot')->where("routers", $d['routers'])->findArray();
            }
            $ui->assign('exps', $exps);
            run_hook('view_edit_plan'); #HOOK
            $ui->display('hotspot-edit.tpl');
        } else {
            r2(U . 'services/hotspot', 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'delete':
        $id = $routes['2'];

        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            run_hook('delete_plan'); #HOOK
            $dvc = Package::getDevice($d);
            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    (new $d['device'])->remove_plan($d);
                } else {
                    new Exception(Lang::T("Devices Not Found"));
                }
            }
            $d->delete();

            r2(U . 'services/hotspot', 's', Lang::T('Data Deleted Successfully'));
        }
        break;

    case 'add-post':
        $name = _post('name');
        $plan_type = _post('plan_type'); //Personal / Business
        $radius = _post('radius');
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
        $device = _post('device');
        $enabled = _post('enabled');
        $prepaid = _post('prepaid');
        $expired_date = _post('expired_date');

        $msg = '';
        if (Validator::UnsignedNumber($validity) == false) {
            $msg .= 'The validity must be a number' . '<br>';
        }
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '' or $id_bw == '' or $price == '' or $validity == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }
        if (empty($radius)) {
            if ($routers == '') {
                $msg .= Lang::T('All field is required') . '<br>';
            }
        }
        $d = ORM::for_table('tbl_plans')->where('name_plan', $name)->where('type', 'Hotspot')->find_one();
        if ($d) {
            $msg .= Lang::T('Name Plan Already Exist') . '<br>';
        }

        run_hook('add_plan'); #HOOK

        if ($msg == '') {
            // Create new plan
            $d = ORM::for_table('tbl_plans')->create();
            $d->name_plan = $name;
            $d->id_bw = $id_bw;
            $d->price = $price; // Set price with or without tax based on configuration
            $d->type = 'Hotspot';
            $d->typebp = $typebp;
            $d->plan_type = $plan_type;
            $d->limit_type = $limit_type;
            $d->time_limit = $time_limit;
            $d->time_unit = $time_unit;
            $d->data_limit = $data_limit;
            $d->data_unit = $data_unit;
            $d->validity = $validity;
            $d->validity_unit = $validity_unit;
            $d->shared_users = $sharedusers;
            if (!empty($radius)) {
                $d->is_radius = 1;
                $d->routers = '';
            } else {
                $d->is_radius = 0;
                $d->routers = $routers;
            }
            $d->enabled = $enabled;
            $d->prepaid = $prepaid;
            $d->device = $device;
            if ($prepaid == 'no') {
                if ($expired_date > 28 && $expired_date < 1) {
                    $expired_date = 20;
                }
                $d->expired_date = $expired_date;
            } else {
                $d->expired_date = 20;
            }
            $d->save();

            $dvc = Package::getDevice($d);
            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    (new $d['device'])->add_plan($d);
                } else {
                    new Exception(Lang::T("Devices Not Found"));
                }
            }
            r2(U . 'services/edit/' . $d->id(), 's', Lang::T('Data Created Successfully'));
        } else {
            r2(U . 'services/add', 'e', $msg);
        }
        break;


    case 'edit-post':
        $id = _post('id');
        $name = _post('name');
        $plan_type = _post('plan_type');
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
        $plan_expired = _post('plan_expired', '0');
        $device = _post('device');
        $enabled = _post('enabled');
        $prepaid = _post('prepaid');
        $routers = _post('routers');
        $on_login = _post('on_login');
        $on_logout = _post('on_logout');
        $expired_date = _post('expired_date');
        $msg = '';
        if (Validator::UnsignedNumber($validity) == false) {
            $msg .= 'The validity must be a number' . '<br>';
        }
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '' or $id_bw == '' or $price == '' or $validity == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }
        $d = ORM::for_table('tbl_plans')->where('id', $id)->find_one();
        $old = ORM::for_table('tbl_plans')->where('id', $id)->find_one();
        if ($d) {
        } else {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }
        run_hook('edit_plan'); #HOOK
        if ($msg == '') {
            $b = ORM::for_table('tbl_bandwidth')->where('id', $id_bw)->find_one();
            if ($b['rate_down_unit'] == 'Kbps') {
                $unitdown = 'K';
                $raddown = '000';
            } else {
                $unitdown = 'M';
                $raddown = '000000';
            }
            if ($b['rate_up_unit'] == 'Kbps') {
                $unitup = 'K';
                $radup = '000';
            } else {
                $unitup = 'M';
                $radup = '000000';
            }
            $rate = $b['rate_up'] . $unitup . "/" . $b['rate_down'] . $unitdown;
            $radiusRate = $b['rate_up'] . $radup . '/' . $b['rate_down'] . $raddown . '/' . $b['burst'];

            $rate = trim($rate . " " . $b['burst']);

            $d->name_plan = $name;
            $d->id_bw = $id_bw;
            $d->price = $price; // Set price with or without tax based on configuration
            $d->typebp = $typebp;
            $d->limit_type = $limit_type;
            $d->time_limit = $time_limit;
            $d->time_unit = $time_unit;
            $d->data_limit = $data_limit;
            $d->plan_type = $plan_type;
            $d->data_unit = $data_unit;
            $d->validity = $validity;
            $d->validity_unit = $validity_unit;
            $d->shared_users = $sharedusers;
            $d->plan_expired = $plan_expired;
            $d->enabled = $enabled;
            $d->prepaid = $prepaid;
            $d->on_login = $on_login;
            $d->on_logout = $on_logout;
            $d->device = $device;
            if ($prepaid == 'no') {
                if ($expired_date > 28 && $expired_date < 1) {
                    $expired_date = 20;
                }
                $d->expired_date = $expired_date;
            } else {
                $d->expired_date = 20;
            }
            $d->save();

            $dvc = Package::getDevice($d);
            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    (new $d['device'])->update_plan($old, $d);
                } else {
                    new Exception(Lang::T("Devices Not Found"));
                }
            }
            r2(U . 'services/hotspot', 's', Lang::T('Data Updated Successfully'));
        } else {
            r2(U . 'services/edit/' . $id, 'e', $msg);
        }
        break;

    case 'pppoe':
        $ui->assign('_title', Lang::T('PPPOE Plans'));
        $ui->assign('xfooter', '<script type="text/javascript" src="ui/lib/c/pppoe.js"></script>');

        $name = _post('name');
        $name = _req('name');
        $type1 = _req('type1');
        $type2 = _req('type2');
        $type3 = _req('type3');
        $bandwidth = _req('bandwidth');
        $valid = _req('valid');
        $device = _req('device');
        $status = _req('status');
        $router = _req('router');
        $ui->assign('type1', $type1);
        $ui->assign('type2', $type2);
        $ui->assign('type3', $type3);
        $ui->assign('bandwidth', $bandwidth);
        $ui->assign('valid', $valid);
        $ui->assign('device', $device);
        $ui->assign('status', $status);
        $ui->assign('router', $router);

        $append_url = "&type1=" . urlencode($type1)
            . "&type2=" . urlencode($type2)
            . "&type3=" . urlencode($type3)
            . "&bandwidth=" . urlencode($bandwidth)
            . "&valid=" . urlencode($valid)
            . "&device=" . urlencode($device)
            . "&status=" . urlencode($status)
            . "&router=" . urlencode($router);

        $bws = ORM::for_table('tbl_plans')->distinct()->select("id_bw")->where('tbl_plans.type', 'PPPOE')->findArray();
        $ids = array_column($bws, 'id_bw');
        if(count($ids)){
            $ui->assign('bws', ORM::for_table('tbl_bandwidth')->select("id")->select('name_bw')->where_id_in($ids)->findArray());
        }else{
            $ui->assign('bws', []);
        }
        $ui->assign('type2s', ORM::for_table('tbl_plans')->getEnum("plan_type"));
        $ui->assign('type3s', ORM::for_table('tbl_plans')->getEnum("typebp"));
        $ui->assign('valids', ORM::for_table('tbl_plans')->getEnum("validity_unit"));
        $ui->assign('routers', array_column(ORM::for_table('tbl_plans')->distinct()->select("routers")->whereNotEqual('routers', '')->findArray(), 'routers'));
        $devices = [];
        $files = scandir($DEVICE_PATH);
        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext == 'php') {
                $devices[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }
        $ui->assign('devices', $devices);
        $query = ORM::for_table('tbl_bandwidth')
            ->left_outer_join('tbl_plans', array('tbl_bandwidth.id', '=', 'tbl_plans.id_bw'))
            ->where('tbl_plans.type', 'PPPOE');
        if (!empty($type1)) {
            $query->where('tbl_plans.prepaid', $type1);
        }
        if (!empty($type2)) {
            $query->where('tbl_plans.plan_type', $type2);
        }
        if (!empty($type3)) {
            $query->where('tbl_plans.typebp', $type3);
        }
        if (!empty($bandwidth)) {
            $query->where('tbl_plans.id_bw', $bandwidth);
        }
        if (!empty($valid)) {
            $query->where('tbl_plans.validity_unit', $valid);
        }
        if (!empty($router)) {
            if ($router == 'radius') {
                $query->where('tbl_plans.is_radius', '1');
            } else {
                $query->where('tbl_plans.routers', $router);
            }
        }
        if (!empty($device)) {
            $query->where('tbl_plans.device', $device);
        }
        if (in_array($status, ['0', '1'])) {
            $query->where('tbl_plans.enabled', $status);
        }
        if ($name != '') {
            $query->where_like('tbl_plans.name_plan', '%' . $name . '%');
        }
        $d = Paginator::findMany($query, ['name' => $name], 20, $append_url);

        $ui->assign('d', $d);
        run_hook('view_list_ppoe'); #HOOK
        $ui->display('pppoe.tpl');
        break;

    case 'pppoe-add':
        $ui->assign('_title', Lang::T('PPPOE Plans'));
        $d = ORM::for_table('tbl_bandwidth')->find_many();
        $ui->assign('d', $d);
        $r = ORM::for_table('tbl_routers')->find_many();
        $ui->assign('r', $r);
        $devices = [];
        $files = scandir($DEVICE_PATH);
        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext == 'php') {
                $devices[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }
        $ui->assign('devices', $devices);
        run_hook('view_add_ppoe'); #HOOK
        $ui->display('pppoe-add.tpl');
        break;

    case 'pppoe-edit':
        $ui->assign('_title', Lang::T('PPPOE Plans'));
        $id = $routes['2'];
        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            if (empty($d['device'])) {
                if ($d['is_radius']) {
                    $d->device = 'Radius';
                } else {
                    $d->device = 'MikrotikPppoe';
                }
                $d->save();
            }
            $ui->assign('d', $d);
            $p = ORM::for_table('tbl_pool')->where('routers', ($d['is_radius']) ? 'radius' : $d['routers'])->find_many();
            $ui->assign('p', $p);
            $b = ORM::for_table('tbl_bandwidth')->find_many();
            $ui->assign('b', $b);
            $r = [];
            if ($d['is_radius']) {
                $r = ORM::for_table('tbl_routers')->find_many();
            }
            $ui->assign('r', $r);
            $devices = [];
            $files = scandir($DEVICE_PATH);
            foreach ($files as $file) {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                if ($ext == 'php') {
                    $devices[] = pathinfo($file, PATHINFO_FILENAME);
                }
            }
            $ui->assign('devices', $devices);
            //select expired plan
            if ($d['is_radius']) {
                $exps = ORM::for_table('tbl_plans')->selects('id', 'name_plan')->where('type', 'PPPOE')->where("is_radius", 1)->findArray();
            } else {
                $exps = ORM::for_table('tbl_plans')->selects('id', 'name_plan')->where('type', 'PPPOE')->where("routers", $d['routers'])->findArray();
            }
            $ui->assign('exps', $exps);
            run_hook('view_edit_ppoe'); #HOOK
            $ui->display('pppoe-edit.tpl');
        } else {
            r2(U . 'services/pppoe', 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'pppoe-delete':
        $id = $routes['2'];

        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            run_hook('delete_ppoe'); #HOOK

            $dvc = Package::getDevice($d);
            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    (new $d['device'])->remove_plan($d);
                } else {
                    new Exception(Lang::T("Devices Not Found"));
                }
            }
            $d->delete();

            r2(U . 'services/pppoe', 's', Lang::T('Data Deleted Successfully'));
        }
        break;

    case 'pppoe-add-post':
        $name = _post('name_plan');
        $plan_type = _post('plan_type');
        $radius = _post('radius');
        $id_bw = _post('id_bw');
        $price = _post('price');
        $validity = _post('validity');
        $validity_unit = _post('validity_unit');
        $routers = _post('routers');
        $device = _post('device');
        $pool = _post('pool_name');
        $enabled = _post('enabled');
        $prepaid = _post('prepaid');
        $expired_date = _post('expired_date');


        $msg = '';
        if (Validator::UnsignedNumber($validity) == false) {
            $msg .= 'The validity must be a number' . '<br>';
        }
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '' or $id_bw == '' or $price == '' or $validity == '' or $pool == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }
        if (empty($radius)) {
            if ($routers == '') {
                $msg .= Lang::T('All field is required') . '<br>';
            }
        }

        $d = ORM::for_table('tbl_plans')->where('name_plan', $name)->find_one();
        if ($d) {
            $msg .= Lang::T('Name Plan Already Exist') . '<br>';
        }
        run_hook('add_ppoe'); #HOOK
        if ($msg == '') {
            $b = ORM::for_table('tbl_bandwidth')->where('id', $id_bw)->find_one();
            if ($b['rate_down_unit'] == 'Kbps') {
                $unitdown = 'K';
                $raddown = '000';
            } else {
                $unitdown = 'M';
                $raddown = '000000';
            }
            if ($b['rate_up_unit'] == 'Kbps') {
                $unitup = 'K';
                $radup = '000';
            } else {
                $unitup = 'M';
                $radup = '000000';
            }
            $rate = $b['rate_up'] . $unitup . "/" . $b['rate_down'] . $unitdown;
            $radiusRate = $b['rate_up'] . $radup . '/' . $b['rate_down'] . $raddown . '/' . $b['burst'];
            $rate = trim($rate . " " . $b['burst']);
            $d = ORM::for_table('tbl_plans')->create();
            $d->type = 'PPPOE';
            $d->name_plan = $name;
            $d->id_bw = $id_bw;
            $d->price = $price;
            $d->plan_type = $plan_type;
            $d->validity = $validity;
            $d->validity_unit = $validity_unit;
            $d->pool = $pool;
            if (!empty($radius)) {
                $d->is_radius = 1;
                $d->routers = '';
            } else {
                $d->is_radius = 0;
                $d->routers = $routers;
            }
            if ($prepaid == 'no') {
                if ($expired_date > 28 && $expired_date < 1) {
                    $expired_date = 20;
                }
                $d->expired_date = $expired_date;
            } else {
                $d->expired_date = 0;
            }
            $d->enabled = $enabled;
            $d->prepaid = $prepaid;
            $d->device = $device;
            $d->save();

            $dvc = Package::getDevice($d);
            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    (new $d['device'])->add_plan($d);
                } else {
                    new Exception(Lang::T("Devices Not Found"));
                }
            }
            r2(U . 'services/pppoe', 's', Lang::T('Data Created Successfully'));
        } else {
            r2(U . 'services/pppoe-add', 'e', $msg);
        }
        break;

    case 'edit-pppoe-post':
        $id = _post('id');
        $plan_type = _post('plan_type');
        $name = _post('name_plan');
        $id_bw = _post('id_bw');
        $price = _post('price');
        $validity = _post('validity');
        $validity_unit = _post('validity_unit');
        $routers = _post('routers');
        $device = _post('device');
        $pool = _post('pool_name');
        $plan_expired = _post('plan_expired');
        $enabled = _post('enabled');
        $prepaid = _post('prepaid');
        $expired_date = _post('expired_date');
        $on_login = _post('on_login');
        $on_logout = _post('on_logout');

        $msg = '';
        if (Validator::UnsignedNumber($validity) == false) {
            $msg .= 'The validity must be a number' . '<br>';
        }
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '' or $id_bw == '' or $price == '' or $validity == '' or $pool == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }

        $d = ORM::for_table('tbl_plans')->where('id', $id)->find_one();
        $old = ORM::for_table('tbl_plans')->where('id', $id)->find_one();
        if ($d) {
        } else {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }
        run_hook('edit_ppoe'); #HOOK
        if ($msg == '') {
            $b = ORM::for_table('tbl_bandwidth')->where('id', $id_bw)->find_one();
            if ($b['rate_down_unit'] == 'Kbps') {
                $unitdown = 'K';
                $raddown = '000';
            } else {
                $unitdown = 'M';
                $raddown = '000000';
            }
            if ($b['rate_up_unit'] == 'Kbps') {
                $unitup = 'K';
                $radup = '000';
            } else {
                $unitup = 'M';
                $radup = '000000';
            }
            $rate = $b['rate_up'] . $unitup . "/" . $b['rate_down'] . $unitdown;
            $radiusRate = $b['rate_up'] . $radup . '/' . $b['rate_down'] . $raddown . '/' . $b['burst'];
            $rate = trim($rate . " " . $b['burst']);

            $d->name_plan = $name;
            $d->id_bw = $id_bw;
            $d->price = $price;
            $d->plan_type = $plan_type;
            $d->validity = $validity;
            $d->validity_unit = $validity_unit;
            $d->routers = $routers;
            $d->pool = $pool;
            $d->plan_expired = $plan_expired;
            $d->enabled = $enabled;
            $d->prepaid = $prepaid;
            $d->device = $device;
            $d->on_login = $on_login;
            $d->on_logout = $on_logout;
            if ($prepaid == 'no') {
                if ($expired_date > 28 && $expired_date < 1) {
                    $expired_date = 20;
                }
                $d->expired_date = $expired_date;
            } else {
                $d->expired_date = 0;
            }
            $d->save();

            $dvc = Package::getDevice($d);
            if ($_app_stage != 'demo') {
                if (file_exists($dvc)) {
                    require_once $dvc;
                    (new $d['device'])->update_plan($old, $d);
                } else {
                    new Exception(Lang::T("Devices Not Found"));
                }
            }
            r2(U . 'services/pppoe', 's', Lang::T('Data Updated Successfully'));
        } else {
            r2(U . 'services/pppoe-edit/' . $id, 'e', $msg);
        }
        break;
    case 'balance':
        $ui->assign('_title', Lang::T('Balance Plans'));
        $name = _post('name');
        if ($name != '') {
            $query = ORM::for_table('tbl_plans')->where('tbl_plans.type', 'Balance')->where_like('tbl_plans.name_plan', '%' . $name . '%');
            $d = Paginator::findMany($query, ['name' => $name]);
        } else {
            $query = ORM::for_table('tbl_plans')->where('tbl_plans.type', 'Balance');
            $d = Paginator::findMany($query);
        }

        $ui->assign('d', $d);
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
        $id = $routes['2'];
        $d = ORM::for_table('tbl_plans')->find_one($id);
        $ui->assign('d', $d);
        run_hook('view_edit_balance'); #HOOK
        $ui->display('balance-edit.tpl');
        break;
    case 'balance-delete':
        $id = $routes['2'];

        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            run_hook('delete_balance'); #HOOK
            $d->delete();
            r2(U . 'services/balance', 's', Lang::T('Data Deleted Successfully'));
        }
        break;
    case 'balance-edit-post':
        $id = _post('id');
        $name = _post('name');
        $price = _post('price');
        $enabled = _post('enabled');
        $prepaid = _post('prepaid');

        $msg = '';
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }

        $d = ORM::for_table('tbl_plans')->where('id', $id)->find_one();
        if ($d) {
        } else {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }
        run_hook('edit_ppoe'); #HOOK
        if ($msg == '') {
            $d->name_plan = $name;
            $d->price = $price;
            $d->enabled = $enabled;
            $d->prepaid = 'yes';
            $d->save();

            r2(U . 'services/balance', 's', Lang::T('Data Updated Successfully'));
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
            $msg .= Lang::T('All field is required') . '<br>';
        }

        $d = ORM::for_table('tbl_plans')->where('name_plan', $name)->find_one();
        if ($d) {
            $msg .= Lang::T('Name Plan Already Exist') . '<br>';
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
            $d->prepaid = 'yes';
            $d->save();

            r2(U . 'services/balance', 's', Lang::T('Data Created Successfully'));
        } else {
            r2(U . 'services/balance-add', 'e', $msg);
        }
        break;
    default:
        $ui->display('a404.tpl');
}
