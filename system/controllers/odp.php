<?php
/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 *  Optical Distribution Point (ODP) Controller by ItsLiLxyzx
 **/

_admin();
$ui->assign('_title', Lang::T('Optical Distribution Points'));
$ui->assign('_system_menu', 'network');

$action = $routes['1'];
$ui->assign('_admin', $admin);

if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
	r2(U."dashboard",'e',Lang::T('You do not have permission to access this page'));
}

$leafletpickerHeader = <<<EOT
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css">
EOT;

switch ($action) {
    case 'add':
        run_hook('view_add_odp'); #HOOK
        $ui->assign('xheader', $leafletpickerHeader);
        $ui->display('admin/odp/add.tpl');
        break;

    case 'edit':
        $id  = $routes['2'];
        run_hook('view_edit_odp'); #HOOK
        $d = ORM::for_table('tbl_odps')->find_one($id);
        if($d){
        	$ui->assign('xheader', $leafletpickerHeader);
            $ui->assign('d',$d);
            $ui->display('admin/odp/edit.tpl');
        }else{
            r2(U . 'odp/list', 'e', $_L['Account_Not_Found']);
        }
        break;

    case 'delete':
        $id  = $routes['2'];
        run_hook('delete_odp'); #HOOK
        $d = ORM::for_table('tbl_odps')->find_one($id);
        if($d){
            $d->delete();
            r2(U . 'odp/list', 's', Lang::T('Data Deleted Successfully'));
        }
        break;

    case 'add-post':
        $name = _post('name');
        $port_amount = _post('port_amount');
        $address = _post('address');
		$attenuation = _post('attenuation');
		$coordinates = _post('coordinates');
        $coverage = _post('coverage');
        run_hook('add_odp'); #HOOK
        $msg = '';
        if(Validator::Length($name,16,4) == false){
            $msg .= Lang::T('Name should be between 5 to 15 characters'). '<br>';
        }

        $d = ORM::for_table('tbl_odps')->where('name',$name)->find_one();
        if($d){
            $msg .= Lang::T('ODP Already Exits'). '<br>';
        }

        if($msg == ''){
            $d = ORM::for_table('tbl_odps')->create();
            $d->name = $name;
            $d->port_amount = $port_amount;
            $d->address = $address;
            $d->attenuation = $attenuation;
            $d->coordinates = $coordinates;
            $d->coverage = $coverage;
            $d->save();

            r2(U . 'odp/list', 's', Lang::T('Data Created Successfully'));
        }else{
            r2(U . 'odp/add', 'e', $msg);
        }
        break;

    case 'edit-post':
        $name = _post('name');
        $port_amount = _post('port_amount');
        $address = _post('address');
		$attenuation = _post('attenuation');
		$coordinates = _post('coordinates');
        $coverage = _post('coverage');
		run_hook('edit_odp'); #HOOK
        $msg = '';
        if(Validator::Length($name,16,4) == false){
            $msg .= Lang::T('Name should be between 5 to 15 characters'). '<br>';
        }

        $id = _post('id');
        $d = ORM::for_table('tbl_odps')->find_one($id);
        if($d){
        }else{
            $msg .= Lang::T('Data Not Found'). '<br>';
        }

        if($d['name'] != $name){
            $c = ORM::for_table('tbl_odps')->where('name',$name)->find_one();
            if($c){
                $msg .= Lang::T('ODP Already Exits'. '<br>');
            }
        }

        if($msg == ''){
            $d->name = $name;
            $d->port_amount = $port_amount;
            $d->address = $address;
            $d->attenuation = $attenuation;
            $d->coordinates = $coordinates;
            $d->coverage = $coverage;
            $d->save();

            r2(U . 'odp/list', 's', Lang::T('Data Updated Successfully'));
        }else{
            r2(U . 'odp/edit/'.$id, 'e', $msg);
        }
        break;

    default:
        run_hook('view_list_odp'); #HOOK
		$name = _post('name');
		if ($name != ''){
            $paginator = Paginator::build(ORM::for_table('tbl_odps'), ['name' => '%' . $name . '%'], $name);
			$d = ORM::for_table('tbl_odps')->where_like('name','%'.$name.'%')->offset($paginator['startpoint'])->limit($paginator['limit'])->order_by_desc('id')->find_many();
		}else{
            $paginator = Paginator::build(ORM::for_table('tbl_odps'));
			$d = ORM::for_table('tbl_odps')->offset($paginator['startpoint'])->limit($paginator['limit'])->order_by_desc('id')->find_many();
		}

        $ui->assign('d',$d);
		$ui->assign('paginator',$paginator);
        $ui->display('admin/odp/list.tpl');
        break;
}