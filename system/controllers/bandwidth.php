<?php
/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Bandwidth Plans'));
$ui->assign('_system_menu', 'services');

$action = $routes['1'];
$admin = Admin::_info();
$ui->assign('_admin', $admin);

if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
	r2(U."dashboard",'e',Lang::T('You do not have permission to access this page'));
}

switch ($action) {
    case 'list':
		$ui->assign('xfooter', '<script type="text/javascript" src="ui/lib/c/bandwidth.js"></script>');
        run_hook('view_list_bandwidth'); #HOOK
		$name = _post('name');
		if ($name != ''){
            $paginator = Paginator::build(ORM::for_table('tbl_bandwidth'), ['name_bw' => '%' . $name . '%'], $name);
			$d = ORM::for_table('tbl_bandwidth')->where_like('name_bw','%'.$name.'%')->offset($paginator['startpoint'])->limit($paginator['limit'])->order_by_desc('id')->find_many();
		}else{
            $paginator = Paginator::build(ORM::for_table('tbl_bandwidth'));
			$d = ORM::for_table('tbl_bandwidth')->offset($paginator['startpoint'])->limit($paginator['limit'])->order_by_desc('id')->find_many();
		}

        $ui->assign('d',$d);
		$ui->assign('paginator',$paginator);
        $ui->display('bandwidth.tpl');
        break;

    case 'add':
        run_hook('view_add_bandwidth'); #HOOK
        $ui->display('bandwidth-add.tpl');
        break;

    case 'edit':
        $id  = $routes['2'];
        run_hook('view_edit_bandwith'); #HOOK
        $d = ORM::for_table('tbl_bandwidth')->find_one($id);
        if($d){
            $ui->assign('d',$d);
            $ui->display('bandwidth-edit.tpl');
        }else{
            r2(U . 'bandwidth/list', 'e', $_L['Account_Not_Found']);
        }
        break;

    case 'delete':
        $id  = $routes['2'];
        run_hook('delete_bandwidth'); #HOOK
        $d = ORM::for_table('tbl_bandwidth')->find_one($id);
        if($d){
            $d->delete();
            r2(U . 'bandwidth/list', 's', Lang::T('Data Deleted Successfully'));
        }
        break;

    case 'add-post':
        $name = _post('name');
        $rate_down = _post('rate_down');
        $rate_down_unit = _post('rate_down_unit');
		$rate_up = _post('rate_up');
		$rate_up_unit = _post('rate_up_unit');
        run_hook('add_bandwidth'); #HOOK
        $msg = '';
        if(Validator::Length($name,16,4) == false){
            $msg .= 'Name should be between 5 to 15 characters'. '<br>';
        }

        if($rate_down_unit == 'Kbps'){ $unit_rate_down = $rate_down * 1024; }else{ $unit_rate_down = $rate_down * 1048576; }
		if($rate_up_unit == 'Kbps'){	$unit_rate_up = $min_up * 1024; }else{ $unit_rate_up = $min_up * 1048576; }

        $d = ORM::for_table('tbl_bandwidth')->where('name_bw',$name)->find_one();
        if($d){
            $msg .= Lang::T('Name Bandwidth Already Exist'). '<br>';
        }

        if($msg == ''){
            $d = ORM::for_table('tbl_bandwidth')->create();
            $d->name_bw = $name;
            $d->rate_down = $rate_down;
            $d->rate_down_unit = $rate_down_unit;
            $d->rate_up = $rate_up;
            $d->rate_up_unit = $rate_up_unit;
            $d->save();

            r2(U . 'bandwidth/list', 's', Lang::T('Data Created Successfully'));
        }else{
            r2(U . 'bandwidth/add', 'e', $msg);
        }
        break;

    case 'edit-post':
        $name = _post('name');
        $rate_down = _post('rate_down');
        $rate_down_unit = _post('rate_down_unit');
		$rate_up = _post('rate_up');
		$rate_up_unit = _post('rate_up_unit');
        run_hook('edit_bandwidth'); #HOOK
        $msg = '';
        if(Validator::Length($name,16,4) == false){
            $msg .= 'Name should be between 5 to 15 characters'. '<br>';
        }

        $id = _post('id');
        $d = ORM::for_table('tbl_bandwidth')->find_one($id);
        if($d){
        }else{
            $msg .= Lang::T('Data Not Found'). '<br>';
        }

        if($d['name_bw'] != $name){
            $c = ORM::for_table('tbl_bandwidth')->where('name_bw',$name)->find_one();
            if($c){
                $msg .= Lang::T('Name Bandwidth Already Exist'). '<br>';
            }
        }

        if($msg == ''){
            $d->name_bw = $name;
            $d->rate_down = $rate_down;
            $d->rate_down_unit = $rate_down_unit;
            $d->rate_up = $rate_up;
            $d->rate_up_unit = $rate_up_unit;
            $d->save();

            r2(U . 'bandwidth/list', 's', Lang::T('Data Updated Successfully'));
        }else{
            r2(U . 'bandwidth/edit/'.$id, 'e', $msg);
        }
        break;

    default:
        $ui->display('a404.tpl');
}