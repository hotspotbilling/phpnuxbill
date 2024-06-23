<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Bandwidth Plans'));
$ui->assign('_system_menu', 'services');

$action = $routes['1'];
$ui->assign('_admin', $admin);

if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
    r2(U . "dashboard", 'e', Lang::T('You do not have permission to access this page'));
}

switch ($action) {
    case 'list':
        $ui->assign('xfooter', '<script type="text/javascript" src="ui/lib/c/bandwidth.js"></script>');
        run_hook('view_list_bandwidth'); #HOOK
        $name = _post('name');
        if ($name != '') {
            $query = ORM::for_table('tbl_bandwidth')->where_like('name_bw', '%' . $name . '%')->order_by_desc('id');
            $d = Paginator::findMany($query, ['name' => $name]);
        } else {
            $query = ORM::for_table('tbl_bandwidth')->order_by_desc('id');
            $d = Paginator::findMany($query);
        }

        $ui->assign('d', $d);
        $ui->display('bandwidth.tpl');
        break;

    case 'add':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        run_hook('view_add_bandwidth'); #HOOK
        $ui->display('bandwidth-add.tpl');
        break;

    case 'edit':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $id  = $routes['2'];
        run_hook('view_edit_bandwith'); #HOOK
        $d = ORM::for_table('tbl_bandwidth')->find_one($id);
        if ($d) {
            $ui->assign('burst', explode(" ", $d['burst']));
            $ui->assign('d', $d);
            $ui->display('bandwidth-edit.tpl');
        } else {
            r2(U . 'bandwidth/list', 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'delete':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $id  = $routes['2'];
        run_hook('delete_bandwidth'); #HOOK
        $d = ORM::for_table('tbl_bandwidth')->find_one($id);
        if ($d) {
            $d->delete();
            r2(U . 'bandwidth/list', 's', Lang::T('Data Deleted Successfully'));
        }
        break;

    case 'add-post':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $name = _post('name');
        $rate_down = _post('rate_down');
        $rate_down_unit = _post('rate_down_unit');
        $rate_up = _post('rate_up');
        $rate_up_unit = _post('rate_up_unit');
        run_hook('add_bandwidth'); #HOOK
        $isBurst = true;
        $burst = "";
        if (isset($_POST['burst'])) {
            foreach ($_POST['burst'] as $b) {
                if (empty($b)) {
                    $isBurst = false;
                }
            }
            if ($isBurst) {
                $burst = implode(' ', $_POST['burst']);
            };
        }
        $msg = '';
        if (Validator::Length($name, 256, 0) == false) {
            $msg .= 'Name should be between 1 to 255 characters' . '<br>';
        }

        if ($rate_down_unit == 'Kbps') {
            $unit_rate_down = $rate_down * 1024;
        } else {
            $unit_rate_down = $rate_down * 1048576;
        }
        if ($rate_up_unit == 'Kbps') {
            $unit_rate_up = $min_up * 1024;
        } else {
            $unit_rate_up = $min_up * 1048576;
        }

        $d = ORM::for_table('tbl_bandwidth')->where('name_bw', $name)->find_one();
        if ($d) {
            $msg .= Lang::T('Name Bandwidth Already Exist') . '<br>';
        }

        if ($msg == '') {
            $d = ORM::for_table('tbl_bandwidth')->create();
            $d->name_bw = $name;
            $d->rate_down = $rate_down;
            $d->rate_down_unit = $rate_down_unit;
            $d->rate_up = $rate_up;
            $d->rate_up_unit = $rate_up_unit;
            $d->burst = $burst;
            $d->save();

            r2(U . 'bandwidth/list', 's', Lang::T('Data Created Successfully'));
        } else {
            r2(U . 'bandwidth/add', 'e', $msg);
        }
        break;

    case 'edit-post':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $name = _post('name');
        $rate_down = _post('rate_down');
        $rate_down_unit = _post('rate_down_unit');
        $rate_up = _post('rate_up');
        $rate_up_unit = _post('rate_up_unit');
        run_hook('edit_bandwidth'); #HOOK
        $isBurst = true;
        $burst = "";
        if (isset($_POST['burst'])) {
            foreach ($_POST['burst'] as $b) {
                if (empty($b)) {
                    $isBurst = false;
                }
            }
            if ($isBurst) {
                $burst = implode(' ', $_POST['burst']);
            };
        }
        $msg = '';
        if (Validator::Length($name, 256, 0) == false) {
            $msg .= 'Name should be between 1 to 255 characters' . '<br>';
        }

        $id = _post('id');
        $d = ORM::for_table('tbl_bandwidth')->find_one($id);
        if ($d) {
        } else {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }

        if ($d['name_bw'] != $name) {
            $c = ORM::for_table('tbl_bandwidth')->where('name_bw', $name)->find_one();
            if ($c) {
                $msg .= Lang::T('Name Bandwidth Already Exist') . '<br>';
            }
        }

        if ($msg == '') {
            $d->name_bw = $name;
            $d->rate_down = $rate_down;
            $d->rate_down_unit = $rate_down_unit;
            $d->rate_up = $rate_up;
            $d->rate_up_unit = $rate_up_unit;
            $d->burst = $burst;
            $d->save();

            r2(U . 'bandwidth/list', 's', Lang::T('Data Updated Successfully'));
        } else {
            r2(U . 'bandwidth/edit/' . $id, 'e', $msg);
        }
        break;

    default:
        $ui->display('a404.tpl');
}
