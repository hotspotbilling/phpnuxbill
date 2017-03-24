<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)


* @copyright	Copyright (C) 2014-2015 PHP Mikrotik Billing
* @license		GNU General Public License version 2 or later; see LICENSE.txt

**/
_admin();
$ui->assign('_title', $_L['Network'].' - '. $config['CompanyName']);
$ui->assign('_system_menu', 'network');

$action = $routes['1'];
$admin = Admin::_info();
$ui->assign('_admin', $admin);

if($admin['user_type'] != 'Admin'){
	r2(U."dashboard",'e',$_L['Do_Not_Access']);
}

switch ($action) {
    case 'list':
		$ui->assign('xfooter', '<script type="text/javascript" src="ui/lib/c/routers.js"></script>');
		
		$name = _post('name');
		if ($name != ''){
			$paginator = Paginator::bootstrap('tbl_routers','name','%'.$name.'%');
			$d = ORM::for_table('tbl_routers')->where_like('name','%'.$name.'%')->offset($paginator['startpoint'])->limit($paginator['limit'])->order_by_desc('id')->find_many();
		}else{
			$paginator = Paginator::bootstrap('tbl_routers');
			$d = ORM::for_table('tbl_routers')->offset($paginator['startpoint'])->limit($paginator['limit'])->order_by_desc('id')->find_many();
		}
		
		$ui->assign('d',$d);
		$ui->assign('paginator',$paginator);
        $ui->display('routers.tpl');
        break;

    case 'add':
		$d = ORM::for_table('tbl_routers')->find_many();
		$ui->assign('d',$d);
        $ui->display('routers-add.tpl');
        break;

    case 'edit':
        $id  = $routes['2'];
        $d = ORM::for_table('tbl_routers')->find_one($id);
        if($d){
            $ui->assign('d',$d);
            $ui->display('routers-edit.tpl');
        }else{
            r2(U . 'routers/list', 'e', $_L['Account_Not_Found']);
        }
        break;

    case 'delete':
        $id  = $routes['2'];

        $d = ORM::for_table('tbl_routers')->find_one($id);
        if($d){
            $d->delete();
            r2(U . 'routers/list', 's', $_L['Delete_Successfully']);
        }
        break;

    case 'add-post':
        $name = _post('name');
        $ip_address = _post('ip_address');
        $username = _post('username');
        $password = _post('password');
        $description = _post('description');
		
        $msg = '';
        if(Validator::Length($name,30,4) == false){
            $msg .= 'Name should be between 5 to 30 characters'. '<br>';
        }
        if ($ip_address == '' OR $username == ''){
			$msg .= $_L['All_field_is_required']. '<br>';
		}
		
        $d = ORM::for_table('tbl_routers')->where('ip_address',$ip_address)->find_one();
        if($d){
            $msg .= $_L['Router_already_exist']. '<br>';
        }

        if($msg == ''){
            $d = ORM::for_table('tbl_routers')->create();
            $d->name = $name;
            $d->ip_address = $ip_address;
            $d->username = $username;
            $d->password = $password;
			$d->description = $description;
            $d->save();

            r2(U . 'routers/list', 's', $_L['Created_Successfully']);
        }else{
            r2(U . 'routers/add', 'e', $msg);
        }
        break;


    case 'edit-post':
        $name = _post('name');
        $ip_address = _post('ip_address');
        $username = _post('username');
        $password = _post('password');
        $description = _post('description');

        $msg = '';
        if(Validator::Length($name,30,4) == false){
            $msg .= 'Name should be between 5 to 30 characters'. '<br>';
        }
        if ($ip_address == '' OR $username == ''){
			$msg .= $_L['All_field_is_required']. '<br>';
		}

        $id = _post('id');
        $d = ORM::for_table('tbl_routers')->find_one($id);
        if($d){

        }else{
            $msg .= $_L['Data_Not_Found']. '<br>';
        }

        if($d['name'] != $name){
            $c = ORM::for_table('tbl_routers')->where('ip_address',$ip_address)->find_one();
            if($c){
                $msg .= $_L['Router_already_exist']. '<br>';
            }
        }

        if($msg == ''){
            $d->name = $name;
            $d->ip_address = $ip_address;
            $d->username = $username;
            $d->password = $password;
			$d->description = $description;
            $d->save();
            r2(U . 'routers/list', 's', $_L['Updated_Successfully']);
        }else{
            r2(U . 'routers/edit/'.$id, 'e', $msg);
        }
        break;

    default:
        echo 'action not defined';
}