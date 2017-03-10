<?php
/**
* PHP Mikrotik Billing (www.phpmixbill.com)
* Ismail Marzuqi <iesien22@yahoo.com>
* @version		5.0
* @copyright	Copyright (C) 2014-2015 PHP Mikrotik Billing
* @license		GNU General Public License version 2 or later; see LICENSE.txt
* @donate		PayPal: iesien22@yahoo.com / Bank Mandiri: 130.00.1024957.4
**/
_admin();
$ui->assign('_title', $_L['Bandwidth_Plans'].' - '. $config['CompanyName']);
$ui->assign('_system_menu', 'services');

$action = $routes['1'];
$admin = Admin::_info();
$ui->assign('_admin', $admin);

if($admin['user_type'] != 'Admin' AND $admin['user_type'] != 'Sales'){
	r2(U."dashboard",'e',$_L['Do_Not_Access']);
}

switch ($action) {
    case 'list':
		$ui->assign('xfooter', '<script type="text/javascript" src="ui/lib/c/bandwidth.js"></script>');
		
		$name = _post('name');
		if ($name != ''){
			$paginator = Paginator::bootstrap('tbl_bandwidth','name_bw','%'.$name.'%');
			$d = ORM::for_table('tbl_bandwidth')->where_like('name_bw','%'.$name.'%')->offset($paginator['startpoint'])->limit($paginator['limit'])->order_by_desc('id')->find_many();
		}else{
			$paginator = Paginator::bootstrap('tbl_bandwidth');
			$d = ORM::for_table('tbl_bandwidth')->offset($paginator['startpoint'])->limit($paginator['limit'])->order_by_desc('id')->find_many();
		}
		
        $ui->assign('d',$d);
		$ui->assign('paginator',$paginator);
        $ui->display('bandwidth.tpl');
        break;

    case 'add':
        $ui->display('bandwidth-add.tpl');
        break;

    case 'edit':
        $id  = $routes['2'];
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
        $d = ORM::for_table('tbl_bandwidth')->find_one($id);
        if($d){
            $d->delete();
            r2(U . 'bandwidth/list', 's', $_L['Delete_Successfully']);
        }
        break;

    case 'add-post':
        $name = _post('name');
        $rate_down = _post('rate_down');
        $rate_down_unit = _post('rate_down_unit');
		$rate_up = _post('rate_up');
		$rate_up_unit = _post('rate_up_unit');
		
        $msg = '';
        if(Validator::Length($name,16,4) == false){
            $msg .= 'Name should be between 5 to 15 characters'. '<br>';
        }
		
        if($rate_down_unit == 'Kbps'){ $unit_rate_down = $rate_down * 1024; }else{ $unit_rate_down = $rate_down * 1048576; }
		if($rate_up_unit == 'Kbps'){	$unit_rate_up = $min_up * 1024; }else{ $unit_rate_up = $min_up * 1048576; }

        $d = ORM::for_table('tbl_bandwidth')->where('name_bw',$name)->find_one();
        if($d){
            $msg .= $_L['BW_already_exist']. '<br>';
        }

        if($msg == ''){
            $d = ORM::for_table('tbl_bandwidth')->create();
            $d->name_bw = $name;
            $d->rate_down = $rate_down;
            $d->rate_down_unit = $rate_down_unit;
            $d->rate_up = $rate_up;
            $d->rate_up_unit = $rate_up_unit;
            $d->save();
			
            r2(U . 'bandwidth/list', 's', $_L['Created_Successfully']);
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

        $msg = '';
        if(Validator::Length($name,16,4) == false){
            $msg .= 'Name should be between 5 to 15 characters'. '<br>';
        }

        $id = _post('id');
        $d = ORM::for_table('tbl_bandwidth')->find_one($id);
        if($d){
        }else{
            $msg .= $_L['Data_Not_Found']. '<br>';
        }

        if($d['name_bw'] != $name){
            $c = ORM::for_table('tbl_bandwidth')->where('name_bw',$name)->find_one();
            if($c){
                $msg .= $_L['BW_already_exist']. '<br>';
            }
        }

        if($msg == ''){
            $d->name_bw = $name;
            $d->rate_down = $rate_down;
            $d->rate_down_unit = $rate_down_unit;
            $d->rate_up = $rate_up;
            $d->rate_up_unit = $rate_up_unit;
            $d->save();
		
            r2(U . 'bandwidth/list', 's', $_L['Updated_Successfully']);
        }else{
            r2(U . 'bandwidth/edit/'.$id, 'e', $msg);
        }
        break;

    default:
        echo 'action not defined';
}