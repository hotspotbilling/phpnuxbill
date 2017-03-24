<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)


* @copyright	Copyright (C) 2014-2015 PHP Mikrotik Billing
* @license		GNU General Public License version 2 or later; see LICENSE.txt

**/
_admin();
$ui->assign('_title', $_L['Hotspot_Plans'].' - '. $config['CompanyName']);
$ui->assign('_system_menu', 'services');

$action = $routes['1'];
$admin = Admin::_info();
$ui->assign('_admin', $admin);

if($admin['user_type'] != 'Admin' AND $admin['user_type'] != 'Sales'){
	r2(U."dashboard",'e',$_L['Do_Not_Access']);
}

use PEAR2\Net\RouterOS;
require_once 'system/autoload/PEAR2/Autoload.php';

switch ($action) {
    case 'hotspot':
		$ui->assign('xfooter', '<script type="text/javascript" src="ui/lib/c/hotspot.js"></script>');

		$name = _post('name');
		if ($name != ''){
			$paginator = Paginator::bootstrap('tbl_plans','name_plan','%'.$name.'%','type','Hotspot');
			$d = ORM::for_table('tbl_bandwidth')->join('tbl_plans', array('tbl_bandwidth.id', '=', 'tbl_plans.id_bw'))->where('tbl_plans.type','Hotspot')->where_like('tbl_plans.name_plan','%'.$name.'%')->offset($paginator['startpoint'])->limit($paginator['limit'])->find_many();
		}else{
			$paginator = Paginator::bootstrap('tbl_plans','type','Hotspot');
			$d = ORM::for_table('tbl_bandwidth')->join('tbl_plans', array('tbl_bandwidth.id', '=', 'tbl_plans.id_bw'))->where('tbl_plans.type','Hotspot')->offset($paginator['startpoint'])->limit($paginator['limit'])->find_many();
		}

		$ui->assign('d',$d);
		$ui->assign('paginator',$paginator);
        $ui->display('hotspot.tpl');
        break;

    case 'add':
		$d = ORM::for_table('tbl_bandwidth')->find_many();
		$ui->assign('d',$d);
		$r = ORM::for_table('tbl_routers')->find_many();
		$ui->assign('r',$r);
		
        $ui->display('hotspot-add.tpl');
        break;

    case 'edit':
        $id  = $routes['2'];
        $d = ORM::for_table('tbl_plans')->find_one($id);
        if($d){
            $ui->assign('d',$d);
			$b = ORM::for_table('tbl_bandwidth')->find_many();
			$ui->assign('b',$b);
			$r = ORM::for_table('tbl_routers')->find_many();
			$ui->assign('r',$r);
			
            $ui->display('hotspot-edit.tpl');
        }else{
            r2(U . 'services/hotspot', 'e', $_L['Account_Not_Found']);
        }
        break;

    case 'delete':
        $id  = $routes['2'];
		
        $d = ORM::for_table('tbl_plans')->find_one($id);
        if($d){
			$mikrotik = Router::_info($d['routers']);
			try {
				$client = new RouterOS\Client($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
			} catch (Exception $e) {
				die('Unable to connect to the router.');
			}
			$printRequest = new RouterOS\Request(
				'/ip hotspot user profile print .proplist=name',
				RouterOS\Query::where('name', $d['name_plan'])
			);
			$profileName = $client->sendSync($printRequest)->getProperty('name');
			
			$removeRequest = new RouterOS\Request('/ip/hotspot/user/profile/remove');
			$client($removeRequest
                ->setArgument('numbers', $profileName)
            );

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
		$price = _post('pricebp');
		$sharedusers = _post('sharedusers');
        $validity = _post('validity');
		$validity_unit = _post('validity_unit');
		$routers = _post('routers');
		
        $msg = '';
		if(Validator::UnsignedNumber($validity) == false){
            $msg .= 'The validity must be a number'. '<br>';
        }
		if(Validator::UnsignedNumber($price) == false){
            $msg .= 'The price must be a number'. '<br>';
        }
		if ($name == '' OR $id_bw == '' OR $price == '' OR $validity == '' OR $routers == ''){
			$msg .= $_L['All_field_is_required']. '<br>';
		}

        $d = ORM::for_table('tbl_plans')->where('name_plan',$name)->where('type','Hotspot')->find_one();
        if($d){
            $msg .= $_L['Plan_already_exist']. '<br>';
        }

        if($msg == ''){
			$b = ORM::for_table('tbl_bandwidth')->where('id',$id_bw)->find_one();
			if($b['rate_down_unit'] == 'Kbps'){ $unitdown = 'K'; }else{ $unitdown = 'M'; }
			if($b['rate_up_unit'] == 'Kbps'){ $unitup = 'K'; }else{ $unitup = 'M'; }
			$rate = $b['rate_up'].$unitup."/".$b['rate_down'].$unitdown;

			$mikrotik = Router::_info($routers);
			try {
				$client = new RouterOS\Client($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
			} catch (Exception $e) {
				die('Unable to connect to the router.');
			}
			$addRequest = new RouterOS\Request('/ip/hotspot/user/profile/add');
			$client->sendSync($addRequest
                ->setArgument('name', $name)
                ->setArgument('shared-users', $sharedusers)
				->setArgument('rate-limit', $rate)
            );
			
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
            $d->save();
			
            r2(U . 'services/hotspot', 's', $_L['Created_Successfully']);
        }else{
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
		
        $msg = '';
		if(Validator::UnsignedNumber($validity) == false){
            $msg .= 'The validity must be a number'. '<br>';
        }
		if(Validator::UnsignedNumber($price) == false){
            $msg .= 'The price must be a number'. '<br>';
        }
		if ($name == '' OR $id_bw == '' OR $price == '' OR $validity == '' OR $routers == ''){
			$msg .= $_L['All_field_is_required']. '<br>';
		}

        $d = ORM::for_table('tbl_plans')->where('id',$id)->find_one();
        if($d){
        }else{
            $msg .= $_L['Data_Not_Found']. '<br>';
        }

        if($msg == ''){
			$b = ORM::for_table('tbl_bandwidth')->where('id',$id_bw)->find_one();
			if($b['rate_down_unit'] == 'Kbps'){ $unitdown = 'K'; }else{ $unitdown = 'M'; }
			if($b['rate_up_unit'] == 'Kbps'){ $unitup = 'K'; }else{ $unitup = 'M'; }
			$rate = $b['rate_up'].$unitup."/".$b['rate_down'].$unitdown;
			
			$mikrotik = Router::_info($routers);
			try {
				$client = new RouterOS\Client($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
			} catch (Exception $e) {
				die('Unable to connect to the router.');
			}
			$printRequest = new RouterOS\Request(
				'/ip hotspot user profile print .proplist=name',
				RouterOS\Query::where('name', $name)
			);
			$profileName = $client->sendSync($printRequest)->getProperty('name');
			
			$setRequest = new RouterOS\Request('/ip/hotspot/user/profile/set');
			$client($setRequest
                ->setArgument('numbers', $profileName)
                ->setArgument('shared-users', $sharedusers)
				->setArgument('rate-limit', $rate)
            );
						
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
            $d->save();
			
            r2(U . 'services/hotspot', 's', $_L['Updated_Successfully']);
        }else{
            r2(U . 'services/edit/'.$id, 'e', $msg);
        }
        break;
		
    case 'pppoe':
		$ui->assign('xfooter', '<script type="text/javascript" src="ui/lib/c/pppoe.js"></script>');
		
		$name = _post('name');
		if ($name != ''){
			$paginator = Paginator::bootstrap('tbl_plans','name_plan','%'.$name.'%','type','Hotspot');
			$d = ORM::for_table('tbl_bandwidth')->join('tbl_plans', array('tbl_bandwidth.id', '=', 'tbl_plans.id_bw'))->where('tbl_plans.type','PPPOE')->where_like('tbl_plans.name_plan','%'.$name.'%')->offset($paginator['startpoint'])->limit($paginator['limit'])->find_many();
		}else{
			$paginator = Paginator::bootstrap('tbl_plans','type','Hotspot');
			$d = ORM::for_table('tbl_bandwidth')->join('tbl_plans', array('tbl_bandwidth.id', '=', 'tbl_plans.id_bw'))->where('tbl_plans.type','PPPOE')->offset($paginator['startpoint'])->limit($paginator['limit'])->find_many();
		}
		
		$ui->assign('d',$d);
		$ui->assign('paginator',$paginator);
        $ui->display('pppoe.tpl');
        break;

    case 'pppoe-add':
		$d = ORM::for_table('tbl_bandwidth')->find_many();
		$ui->assign('d',$d);
		$p = ORM::for_table('tbl_pool')->find_many();
		$ui->assign('p',$p);
		$r = ORM::for_table('tbl_routers')->find_many();
		$ui->assign('r',$r);
		
        $ui->display('pppoe-add.tpl');
        break;

    case 'pppoe-edit':
        $id  = $routes['2'];
        $d = ORM::for_table('tbl_plans')->find_one($id);
        if($d){
            $ui->assign('d',$d);
			$b = ORM::for_table('tbl_bandwidth')->find_many();
			$ui->assign('b',$b);
			$p = ORM::for_table('tbl_pool')->find_many();
			$ui->assign('p',$p);
			$r = ORM::for_table('tbl_routers')->find_many();
			$ui->assign('r',$r);
			
            $ui->display('pppoe-edit.tpl');
        }else{
            r2(U . 'services/pppoe', 'e', $_L['Account_Not_Found']);
        }
        break;

    case 'pppoe-delete':
        $id  = $routes['2'];
		
        $d = ORM::for_table('tbl_plans')->find_one($id);
        if($d){
			$mikrotik = Router::_info($d['routers']);
			try {
				$client = new RouterOS\Client($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
			} catch (Exception $e) {
				die('Unable to connect to the router.');
			}
			$printRequest = new RouterOS\Request(
				'/ppp profile print .proplist=name',
				RouterOS\Query::where('name', $d['name_plan'])
			);
			$profileName = $client->sendSync($printRequest)->getProperty('name');
			
			$removeRequest = new RouterOS\Request('/ppp/profile/remove');
			$client($removeRequest
                ->setArgument('numbers', $profileName)
            );
			
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
		
        $msg = '';
		if(Validator::UnsignedNumber($validity) == false){
            $msg .= 'The validity must be a number'. '<br>';
        }
		if(Validator::UnsignedNumber($price) == false){
            $msg .= 'The price must be a number'. '<br>';
        }
		if ($name == '' OR $id_bw == '' OR $price == '' OR $validity == '' OR $routers == '' OR $pool == ''){
			$msg .= $_L['All_field_is_required']. '<br>';
		}
		
        $d = ORM::for_table('tbl_plans')->where('name_plan',$name)->find_one();
        if($d){
            $msg .= $_L['Plan_already_exist']. '<br>';
        }

        if($msg == ''){
			$b = ORM::for_table('tbl_bandwidth')->where('id',$id_bw)->find_one();
			if($b['rate_down_unit'] == 'Kbps'){ $unitdown = 'K'; }else{ $unitdown = 'M'; }
			if($b['rate_up_unit'] == 'Kbps'){ $unitup = 'K'; }else{ $unitup = 'M'; }
			$rate = $b['rate_up'].$unitup."/".$b['rate_down'].$unitdown;
			
			$mikrotik = Router::_info($routers);
			try {
				$client = new RouterOS\Client($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
			} catch (Exception $e) {
				die('Unable to connect to the router.');
			}
			$addRequest = new RouterOS\Request('/ppp/profile/add');
			$client->sendSync($addRequest
                ->setArgument('name', $name)
                ->setArgument('local-address', $pool)
				->setArgument('remote-address', $pool)
				->setArgument('rate-limit', $rate)
            );
			
            $d = ORM::for_table('tbl_plans')->create();
            $d->type = 'PPPOE';
			$d->name_plan = $name;
            $d->id_bw = $id_bw;
            $d->price = $price;
			$d->validity = $validity;
            $d->validity_unit = $validity_unit;
			$d->routers = $routers;
			$d->pool = $pool;
            $d->save();

            r2(U . 'services/pppoe', 's', $_L['Created_Successfully']);
        }else{
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
		
        $msg = '';
		if(Validator::UnsignedNumber($validity) == false){
            $msg .= 'The validity must be a number'. '<br>';
        }
		if(Validator::UnsignedNumber($price) == false){
            $msg .= 'The price must be a number'. '<br>';
        }
		if ($name == '' OR $id_bw == '' OR $price == '' OR $validity == '' OR $routers == '' OR $pool == ''){
			$msg .= $_L['All_field_is_required']. '<br>';
		}

        $d = ORM::for_table('tbl_plans')->where('id',$id)->find_one();
        if($d){
        }else{
            $msg .= $_L['Data_Not_Found']. '<br>';
        }

        if($msg == ''){
			$b = ORM::for_table('tbl_bandwidth')->where('id',$id_bw)->find_one();
			if($b['rate_down_unit'] == 'Kbps'){ $unitdown = 'K'; }else{ $unitdown = 'M'; }
			if($b['rate_up_unit'] == 'Kbps'){ $unitup = 'K'; }else{ $unitup = 'M'; }
			$rate = $b['rate_up'].$unitup."/".$b['rate_down'].$unitdown;
			
			$mikrotik = Router::_info($routers);
			try {
				$client = new RouterOS\Client($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
			} catch (Exception $e) {
				die('Unable to connect to the router.');
			}
			$printRequest = new RouterOS\Request(
				'/ppp profile print .proplist=name',
				RouterOS\Query::where('name', $name)
			);
			$profileName = $client->sendSync($printRequest)->getProperty('name');
			
			$setRequest = new RouterOS\Request('/ppp/profile/set');
			$client($setRequest
                ->setArgument('numbers', $profileName)
                ->setArgument('local-address', $pool)
				->setArgument('remote-address', $pool)
				->setArgument('rate-limit', $rate)
            );
						
            $d->name_plan = $name;
            $d->id_bw = $id_bw;
            $d->price = $price;
			$d->validity = $validity;
            $d->validity_unit = $validity_unit;
			$d->routers = $routers;
			$d->pool = $pool;
            $d->save();
			
            r2(U . 'services/pppoe', 's', $_L['Updated_Successfully']);
        }else{
            r2(U . 'services/pppoe-edit/'.$id, 'e', $msg);
        }
        break;
		
    default:
        echo 'action not defined';
}