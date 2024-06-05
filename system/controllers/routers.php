<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Network'));
$ui->assign('_system_menu', 'network');

$action = $routes['1'];
$ui->assign('_admin', $admin);

require_once $DEVICE_PATH . DIRECTORY_SEPARATOR . "MikrotikHotspot.php";

if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
}

switch ($action) {
    case 'list':
        $ui->assign('xfooter', '<script type="text/javascript" src="ui/lib/c/routers.js"></script>');

        $name = _post('name');
        if ($name != '') {
            $query = ORM::for_table('tbl_routers')->where_like('name', '%' . $name . '%')->order_by_desc('id');
            $d = Paginator::findMany($query, ['name' => $name]);
        } else {
            $query = ORM::for_table('tbl_routers')->order_by_desc('id');
            $d = Paginator::findMany($query);
        }

        $ui->assign('d', $d);
        run_hook('view_list_routers'); #HOOK
        $ui->display('routers.tpl');
        break;

    case 'add':
        run_hook('view_add_routers'); #HOOK
        $ui->display('routers-add.tpl');
        break;

    case 'edit':
        $id  = $routes['2'];
        $d = ORM::for_table('tbl_routers')->find_one($id);
        if (!$d) {
            $d = ORM::for_table('tbl_routers')->where_equal('name', _get('name'))->find_one();
        }
        if ($d) {
            $ui->assign('d', $d);
            run_hook('view_router_edit'); #HOOK
            $ui->display('routers-edit.tpl');
        } else {
            r2(U . 'routers/list', 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'delete':
        $id  = $routes['2'];
        run_hook('router_delete'); #HOOK
        $d = ORM::for_table('tbl_routers')->find_one($id);
        if ($d) {
            $d->delete();
            r2(U . 'routers/list', 's', Lang::T('Data Deleted Successfully'));
        }
        break;

    case 'add-post':
        $name = _post('name');
        $ip_address = _post('ip_address');
        $username = _post('username');
        $password = _post('password');
        $description = _post('description');
        $enabled = _post('enabled');

        $msg = '';
        if (Validator::Length($name, 30, 4) == false) {
            $msg .= 'Name should be between 5 to 30 characters' . '<br>';
        }
        if ($ip_address == '' or $username == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }

        $d = ORM::for_table('tbl_routers')->where('ip_address', $ip_address)->find_one();
        if ($d) {
            $msg .= Lang::T('IP Router Already Exist') . '<br>';
        }
        if (strtolower($name) == 'radius') {
            $msg .= '<b>Radius</b> name is reserved<br>';
        }

        if ($msg == '') {
            run_hook('add_router'); #HOOK
            if(_post("testIt")){
                (new MikrotikHotspot())->getClient($ip_address, $username, $password);
            }
            $d = ORM::for_table('tbl_routers')->create();
            $d->name = $name;
            $d->ip_address = $ip_address;
            $d->username = $username;
            $d->password = $password;
            $d->description = $description;
            $d->enabled = $enabled;
            $d->save();

            r2(U . 'routers/list', 's', Lang::T('Data Created Successfully'));
        } else {
            r2(U . 'routers/add', 'e', $msg);
        }
        break;


    case 'edit-post':
        $name = _post('name');
        $ip_address = _post('ip_address');
        $username = _post('username');
        $password = _post('password');
        $description = _post('description');
        $enabled = $_POST['enabled'];
        $msg = '';
        if (Validator::Length($name, 30, 4) == false) {
            $msg .= 'Name should be between 5 to 30 characters' . '<br>';
        }
        if ($ip_address == '' or $username == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }

        $id = _post('id');
        $d = ORM::for_table('tbl_routers')->find_one($id);
        if ($d) {
        } else {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }

        if ($d['name'] != $name) {
            $c = ORM::for_table('tbl_routers')->where('name', $name)->where_not_equal('id', $id)->find_one();
            if ($c) {
                $msg .= 'Name Already Exists<br>';
            }
        }
        $oldname = $d['name'];

        if ($d['ip_address'] != $ip_address) {
            $c = ORM::for_table('tbl_routers')->where('ip_address', $ip_address)->where_not_equal('id', $id)->find_one();
            if ($c) {
                $msg .= 'IP Already Exists<br>';
            }
        }

        if (strtolower($name) == 'radius') {
            $msg .= '<b>Radius</b> name is reserved<br>';
        }

        if ($msg == '') {
            run_hook('router_edit'); #HOOK
            if(_post("testIt")){
                (new MikrotikHotspot())->getClient($ip_address, $username, $password);
            }
            $d->name = $name;
            $d->ip_address = $ip_address;
            $d->username = $username;
            $d->password = $password;
            $d->description = $description;
            $d->enabled = $enabled;
            $d->save();
            if ($name != $oldname) {
                $p = ORM::for_table('tbl_plans')->where('routers', $oldname)->find_result_set();
                $p->set('routers', $name);
                $p->save();
                $p = ORM::for_table('tbl_payment_gateway')->where('routers', $oldname)->find_result_set();
                $p->set('routers', $name);
                $p->save();
                $p = ORM::for_table('tbl_pool')->where('routers', $oldname)->find_result_set();
                $p->set('routers', $name);
                $p->save();
                $p = ORM::for_table('tbl_transactions')->where('routers', $oldname)->find_result_set();
                $p->set('routers', $name);
                $p->save();
                $p = ORM::for_table('tbl_user_recharges')->where('routers', $oldname)->find_result_set();
                $p->set('routers', $name);
                $p->save();
                $p = ORM::for_table('tbl_voucher')->where('routers', $oldname)->find_result_set();
                $p->set('routers', $name);
                $p->save();
            }
            r2(U . 'routers/list', 's', Lang::T('Data Updated Successfully'));
        } else {
            r2(U . 'routers/edit/' . $id, 'e', $msg);
        }
        break;

    default:
        r2(U . 'routers/list/', 's', '');
}
