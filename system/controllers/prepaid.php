<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
* @copyright	Copyright (C) 2014-2015 PHP Mikrotik Billing
* @license		GNU General Public License version 2 or later; see LICENSE.txt

**/
_admin();
$ui->assign('_title', $_L['Recharge_Account'].' - '. $config['CompanyName']);
$ui->assign('_system_menu', 'prepaid');

$action = $routes['1'];
$admin = Admin::_info();
$ui->assign('_admin', $admin);

if($admin['user_type'] != 'Admin' AND $admin['user_type'] != 'Sales'){
	r2(U."dashboard",'e',$_L['Do_Not_Access']);
}

use PEAR2\Net\RouterOS;
require_once 'system/autoload/PEAR2/Autoload.php';

switch ($action) {
	case 'list':
		$ui->assign('xfooter', '<script type="text/javascript" src="ui/lib/c/prepaid.js"></script>');
		
		$username = _post('username');
		if ($username != ''){
			$paginator = Paginator::bootstrap('tbl_user_recharges','username','%'.$username.'%');
			$d = ORM::for_table('tbl_user_recharges')->where_like('username','%'.$username.'%')->offset($paginator['startpoint'])->limit($paginator['limit'])->order_by_desc('id')->find_many();
		}else{
			$paginator = Paginator::bootstrap('tbl_user_recharges');
			$d = ORM::for_table('tbl_user_recharges')->offset($paginator['startpoint'])->limit($paginator['limit'])->order_by_desc('id')->find_many();
		}
		
        $ui->assign('d',$d);
		$ui->assign('paginator',$paginator);
		$ui->display('prepaid.tpl');
        break;
		
    case 'recharge':
		$c = ORM::for_table('tbl_customers')->find_many();
		$ui->assign('c',$c);
		$p = ORM::for_table('tbl_plans')->find_many();
		$ui->assign('p',$p);
		$r = ORM::for_table('tbl_routers')->find_many();
		$ui->assign('r',$r);

        $ui->display('recharge.tpl');
        break;

    case 'recharge-user':
		$id = $routes['2'];
		$ui->assign('id',$id);

		$c = ORM::for_table('tbl_customers')->find_many();
		$ui->assign('c',$c);
		$p = ORM::for_table('tbl_plans')->find_many();
		$ui->assign('p',$p);
		$r = ORM::for_table('tbl_routers')->find_many();
		$ui->assign('r',$r);

        $ui->display('recharge-user.tpl');
        break;		

    case 'recharge-post':
        $id_customer = _post('id_customer');
		$type = _post('type');
        $server = _post('server');
		$plan = _post('plan');
		
		$date_now = date("Y-m-d H:i:s");
		$date_only = date("Y-m-d");
		$time = date("H:i:s");

		$msg = '';
        if ($id_customer == '' OR $type == '' OR $server == '' OR $plan == ''){
			$msg .= 'All field is required'. '<br>';
		}

        if($msg == ''){
			$c = ORM::for_table('tbl_customers')->where('id',$id_customer)->find_one();
			$p = ORM::for_table('tbl_plans')->where('id',$plan)->find_one();
			$b = ORM::for_table('tbl_user_recharges')->where('customer_id',$id_customer)->find_one();

			$mikrotik = Router::_info($server);
			$date_exp = date("Y-m-d", mktime(0,0,0,date("m"),date("d") + $p['validity'],date("Y")));
			
			if($type == 'Hotspot'){
				if($b){
					try {
						$client = new RouterOS\Client($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
					} catch (Exception $e) {
						die('Unable to connect to the router.');
					}

					$printRequest = new RouterOS\Request(
						'/ip hotspot user print .proplist=name',
						RouterOS\Query::where('name', $c['username'])
					);
					$userName = $client->sendSync($printRequest)->getProperty('name');
					$removeRequest = new RouterOS\Request('/ip/hotspot/user/remove');
					$client($removeRequest
						->setArgument('numbers', $userName)
					);
					/* iBNuX Added:
					* 	Time limit to Mikrotik
					*	'Time_Limit', 'Data_Limit', 'Both_Limit'
					*/
					$addRequest = new RouterOS\Request('/ip/hotspot/user/add');
					if($p['typebp']=="Limited"){
						if($p['limit_type']=="Time_Limit"){
							if($p['time_unit']=='Hrs')
								$timelimit = $p['time_limit'].":00:00";
							else
								$timelimit = "00:".$p['time_limit'].":00";
							$client->sendSync($addRequest
								->setArgument('name', $c['username'])
								->setArgument('profile', $p['name_plan'])
								->setArgument('password', $c['password'])
								->setArgument('limit-uptime', $timelimit)
							);
						}else if($p['limit_type']=="Data_Limit"){
							if($p['data_unit']=='GB')
								$datalimit = $p['data_limit']."000000000";
							else
								$datalimit = $p['data_limit']."000000";
							$client->sendSync($addRequest
								->setArgument('name', $c['username'])
								->setArgument('profile', $p['name_plan'])
								->setArgument('password', $c['password'])
								->setArgument('limit-bytes-total', $datalimit)
							);
						}else if($p['limit_type']=="Both_Limit"){
							if($p['time_unit']=='Hrs')
								$timelimit = $p['time_limit'].":00:00";
							else
								$timelimit = "00:".$p['time_limit'].":00";
							if($p['data_unit']=='GB')
								$datalimit = $p['data_limit']."000000000";
							else
								$datalimit = $p['data_limit']."000000";
							$client->sendSync($addRequest
								->setArgument('name', $c['username'])
								->setArgument('profile', $p['name_plan'])
								->setArgument('password', $c['password'])
								->setArgument('limit-uptime', $timelimit)
								->setArgument('limit-bytes-total', $datalimit)
							);
						}
					}else{
						$client->sendSync($addRequest
							->setArgument('name', $c['username'])
							->setArgument('profile', $p['name_plan'])
							->setArgument('password', $c['password'])
						);
					}

					$b->customer_id = $id_customer;
					$b->username = $c['username'];
					$b->plan_id = $plan;
					$b->namebp = $p['name_plan'];
					$b->recharged_on = $date_only;
					$b->expiration = $date_exp;
					$b->time = $time;
					$b->status = "on";
					$b->method = "admin";
					$b->routers = $server;
					$b->type = "Hotspot";
					$b->save();
					
					// insert table transactions
					$t = ORM::for_table('tbl_transactions')->create();
					$t->invoice = "INV-"._raid(5);
					$t->username = $c['username'];
					$t->plan_name = $p['name_plan'];
					$t->price = $p['price'];
					$t->recharged_on = $date_only;
					$t->expiration = $date_exp;
					$t->time = $time;
					$t->method = "admin";
					$t->routers = $server;
					$t->type = "Hotspot";
					$t->save();
					
				}else{
					try {
						$client = new RouterOS\Client($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
					} catch (Exception $e) {
						die('Unable to connect to the router.');
					}

					/* iBNuX Added:
					* 	Time limit to Mikrotik
					*	'Time_Limit', 'Data_Limit', 'Both_Limit'
					*/
					$addRequest = new RouterOS\Request('/ip/hotspot/user/add');
					if($p['typebp']=="Limited"){
						if($p['limit_type']=="Time_Limit"){
							if($p['time_unit']=='Hrs')
								$timelimit = $p['time_limit'].":00:00";
							else
								$timelimit = "00:".$p['time_limit'].":00";
							$client->sendSync($addRequest
								->setArgument('name', $c['username'])
								->setArgument('profile', $p['name_plan'])
								->setArgument('password', $c['password'])
								->setArgument('limit-uptime', $timelimit)
							);
						}else if($p['limit_type']=="Data_Limit"){
							if($p['data_unit']=='GB')
								$datalimit = $p['data_limit']."000000000";
							else
								$datalimit = $p['data_limit']."000000";
							$client->sendSync($addRequest
								->setArgument('name', $c['username'])
								->setArgument('profile', $p['name_plan'])
								->setArgument('password', $c['password'])
								->setArgument('limit-bytes-total', $datalimit)
							);
						}else if($p['limit_type']=="Both_Limit"){
							if($p['time_unit']=='Hrs')
								$timelimit = $p['time_limit'].":00:00";
							else
								$timelimit = "00:".$p['time_limit'].":00";
							if($p['data_unit']=='GB')
								$datalimit = $p['data_limit']."000000000";
							else
								$datalimit = $p['data_limit']."000000";
							$client->sendSync($addRequest
								->setArgument('name', $c['username'])
								->setArgument('profile', $p['name_plan'])
								->setArgument('password', $c['password'])
								->setArgument('limit-uptime', $timelimit)
								->setArgument('limit-bytes-total', $datalimit)
							);
						}
					}else{
						$client->sendSync($addRequest
							->setArgument('name', $c['username'])
							->setArgument('profile', $p['name_plan'])
							->setArgument('password', $c['password'])
						);
					}
					
					$d = ORM::for_table('tbl_user_recharges')->create();
					$d->customer_id = $id_customer;
					$d->username = $c['username'];
					$d->plan_id = $plan;
					$d->namebp = $p['name_plan'];
					$d->recharged_on = $date_only;
					$d->expiration = $date_exp;
					$d->time = $time;
					$d->status = "on";
					$d->method = "admin";
					$d->routers = $server;
					$d->type = "Hotspot";
					$d->save();
					
					// insert table transactions
					$t = ORM::for_table('tbl_transactions')->create();
					$t->invoice = "INV-"._raid(5);
					$t->username = $c['username'];
					$t->plan_name = $p['name_plan'];
					$t->price = $p['price'];
					$t->recharged_on = $date_only;
					$t->expiration = $date_exp;
					$t->time = $time;
					$t->method = "admin";
					$t->routers = $server;
					$t->type = "Hotspot";
					$t->save();
				}

			}else{
				
				if($b){
					try {
						$client = new RouterOS\Client($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
					} catch (Exception $e) {
						die('Unable to connect to the router.');
					}
					$printRequest = new RouterOS\Request(
						'/ppp secret print .proplist=name',
						RouterOS\Query::where('name', $c['username'])
					);
					$userName = $client->sendSync($printRequest)->getProperty('name');
					
					$removeRequest = new RouterOS\Request('/ppp/secret/remove');
					$client($removeRequest
						->setArgument('numbers', $userName)
					);
					
					$addRequest = new RouterOS\Request('/ppp/secret/add');
					$client->sendSync($addRequest
						->setArgument('name', $c['username'])
						->setArgument('service', 'pppoe')
						->setArgument('profile', $p['name_plan'])
						->setArgument('password', $c['password'])
					);
										
					$b->customer_id = $id_customer;
					$b->username = $c['username'];
					$b->plan_id = $plan;
					$b->namebp = $p['name_plan'];
					$b->recharged_on = $date_only;
					$b->expiration = $date_exp;
					$b->time = $time;
					$b->status = "on";
					$b->method = "admin";
					$b->routers = $server;
					$b->type = "PPPOE";
					$b->save();
					
					// insert table transactions
					$t = ORM::for_table('tbl_transactions')->create();
					$t->invoice = "INV-"._raid(5);
					$t->username = $c['username'];
					$t->plan_name = $p['name_plan'];
					$t->price = $p['price'];
					$t->recharged_on = $date_only;
					$t->expiration = $date_exp;
					$t->time = $time;
					$t->method = "admin";
					$t->routers = $server;
					$t->type = "PPPOE";
					$t->save();
					
				}else{
					try {
						$client = new RouterOS\Client($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
					} catch (Exception $e) {
						die('Unable to connect to the router.');
					}
					$addRequest = new RouterOS\Request('/ppp/secret/add');
					$client->sendSync($addRequest
						->setArgument('name', $c['username'])
						->setArgument('service', 'pppoe')
						->setArgument('profile', $p['name_plan'])
						->setArgument('password', $c['password'])
					);
					
					$d = ORM::for_table('tbl_user_recharges')->create();
					$d->customer_id = $id_customer;
					$d->username = $c['username'];
					$d->plan_id = $plan;
					$d->namebp = $p['name_plan'];
					$d->recharged_on = $date_only;
					$d->expiration = $date_exp;
					$d->time = $time;
					$d->status = "on";
					$d->method = "admin";
					$d->routers = $server;
					$d->type = "PPPOE";
					$d->save();
					
					// insert table transactions
					$t = ORM::for_table('tbl_transactions')->create();
					$t->invoice = "INV-"._raid(5);
					$t->username = $c['username'];
					$t->plan_name = $p['name_plan'];
					$t->price = $p['price'];
					$t->recharged_on = $date_only;
					$t->expiration = $date_exp;
					$t->time = $time;
					$t->method = "admin";
					$t->routers = $server;
					$t->type = "PPPOE";
					$t->save();
				}
			}
			$in = ORM::for_table('tbl_transactions')->where('username',$c['username'])->order_by_desc('id')->find_one();
			$ui->assign('in',$in);
			
			$ui->assign('date',$date_now);
			$ui->display('invoice.tpl');
			
        }else{
            r2(U . 'prepaid/recharge', 'e', $msg);
        }
        break;
	
	case 'print':
		$date_now = date("Y-m-d H:i:s");
		$id = _post('id');
		
		$d = ORM::for_table('tbl_transactions')->where('id',$id)->find_one();
		$ui->assign('d',$d);
		
		$ui->assign('date',$date_now);
        $ui->display('invoice-print.tpl');
        break;
	break;
	
    case 'edit':
        $id  = $routes['2'];
        $d = ORM::for_table('tbl_user_recharges')->find_one($id);
        if($d){
            $ui->assign('d',$d);
			$p = ORM::for_table('tbl_plans')->find_many();
			$ui->assign('p',$p);
			
            $ui->display('prepaid-edit.tpl');
        }else{
            r2(U . 'services/list', 'e', $_L['Account_Not_Found']);
        }
        break;

    case 'delete':
        $id  = $routes['2'];

        $d = ORM::for_table('tbl_user_recharges')->find_one($id);
		$mikrotik = Router::_info($d['routers']);
        if($d){
			if($d['type'] == 'Hotspot'){
				try {
					$client = new RouterOS\Client($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
				} catch (Exception $e) {
					die('Unable to connect to the router.');
				}
				$printRequest = new RouterOS\Request(
					'/ip hotspot user print .proplist=name',
					RouterOS\Query::where('name', $d['username'])
				);
				$userName = $client->sendSync($printRequest)->getProperty('name');
				$removeRequest = new RouterOS\Request('/ip/hotspot/user/remove');
				$client($removeRequest
					->setArgument('numbers', $userName)
				);
				
				$d->delete();
			}else{
				try {
					$client = new RouterOS\Client($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
				} catch (Exception $e) {
					die('Unable to connect to the router.');
				}
				$printRequest = new RouterOS\Request(
					'/ppp secret print .proplist=name',
					RouterOS\Query::where('name', $d['username'])
				);
				$userName = $client->sendSync($printRequest)->getProperty('name');
				
				$removeRequest = new RouterOS\Request('/ppp/secret/remove');
				$client($removeRequest
					->setArgument('numbers', $userName)
				);
				$d->delete();
			}
            r2(U . 'prepaid/list', 's', $_L['Delete_Successfully']);
        }
        break;

    case 'edit-post':
        $username = _post('username');
        $id_plan = _post('id_plan');
        $recharged_on = _post('recharged_on');
		$expiration = _post('expiration');

        $id = _post('id');
        $d = ORM::for_table('tbl_user_recharges')->find_one($id);
        if($d){

        }else{
            $msg .= $_L['Data_Not_Found']. '<br>';
        }

        if($msg == ''){
            $d->username = $username;
            $d->plan_id = $id_plan;
            $d->recharged_on = $recharged_on;
            $d->expiration = $expiration;
            $d->save();
			
            r2(U . 'prepaid/list', 's', $_L['Updated_Successfully']);
        }else{
            r2(U . 'prepaid/edit/'.$id, 'e', $msg);
        }
        break;
		
	case 'voucher':
		$ui->assign('xfooter', '<script type="text/javascript" src="ui/lib/c/voucher.js"></script>');
		
		$code = _post('code');
		if ($code != ''){
			$ui->assign('code',$code);
			$paginator = Paginator::bootstrap('tbl_voucher','code','%'.$code.'%');
			$d = ORM::for_table('tbl_plans')
				->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
				->where_like('tbl_plans.code','%'.$code.'%')
				->offset($paginator['startpoint'])
				->limit($paginator['limit'])
				->find_many();
		}else{
			$paginator = Paginator::bootstrap('tbl_voucher');
			$d = ORM::for_table('tbl_plans')
			->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
			->offset($paginator['startpoint'])
			->limit($paginator['limit'])->find_many();
		}
		
        $ui->assign('d',$d);
		$ui->assign('paginator',$paginator);
		$ui->display('voucher.tpl');
        break;
		
    case 'add-voucher':

		$c = ORM::for_table('tbl_customers')->find_many();
		$ui->assign('c',$c);
		$p = ORM::for_table('tbl_plans')->find_many();
		$ui->assign('p',$p);
		$r = ORM::for_table('tbl_routers')->find_many();
		$ui->assign('r',$r);

        $ui->display('voucher-add.tpl');
        break;
		
	case 'print-voucher':
		$from_id = _post('from_id')*1;
		$planid = _post('planid')*1;
		$pagebreak = _post('pagebreak')*1;
		$limit = _post('limit')*1;
		
		if($pagebreak<1) $pagebreak = 6; 
		
		if($limit<1) $limit = $pagebreak *2;

		if ($from_id >0 && $planid >0){
			$v = ORM::for_table('tbl_plans')
				->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
				->where('tbl_voucher.status','0')
				->where('tbl_plans.id',$planid)
				->where_gt('tbl_voucher.id',$from_id)
				->limit($limit)
				->find_many();
			$vc = ORM::for_table('tbl_plans')
				->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
				->where('tbl_voucher.status','0')
				->where('tbl_plans.id',$planid)
				->where_gt('tbl_voucher.id',$from_id)
				->count();
		}else if ($from_id == 0 && $planid > 0){
			$v = ORM::for_table('tbl_plans')
				->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
				->where('tbl_voucher.status','0')
				->where('tbl_plans.id',$planid)
				->limit($limit)
				->find_many();
			$vc = ORM::for_table('tbl_plans')
				->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
				->where('tbl_voucher.status','0')
				->where('tbl_plans.id',$planid)
				->count();
		}else if ($from_id > 0 && $planid == 0){
			$v = ORM::for_table('tbl_plans')
				->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
				->where('tbl_voucher.status','0')
				->where_gt('tbl_voucher.id',$from_id)
				->limit($limit)
				->find_many();
			$vc = ORM::for_table('tbl_plans')
				->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
				->where('tbl_voucher.status','0')
				->where_gt('tbl_voucher.id',$from_id)
				->count();
		}else{
			$v = ORM::for_table('tbl_plans')
				->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
				->where('tbl_voucher.status','0')
				->limit($limit)
				->find_many();
			$vc = ORM::for_table('tbl_plans')
				->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
				->where('tbl_voucher.status','0')
				->count();
		}
		
		$ui->assign('_title', $_L['Voucher_Hotspot'].' - '. $config['CompanyName']);
		$ui->assign('from_id',$from_id); 
		$ui->assign('pagebreak',$pagebreak);

		$plans = ORM::for_table('tbl_plans')->find_many();
		$ui->assign('plans',$plans);
		$ui->assign('limit',$limit);
		$ui->assign('planid',$planid);
		
		$ui->assign('v',$v);
		$ui->assign('vc',$vc);
		
		//for counting pagebreak
		$ui->assign('jml',0);

		$ui->display('print-voucher.tpl');
        break;
    case 'voucher-post':
        $type = _post('type');
		$plan = _post('plan');
		$server = _post('server');
		$numbervoucher = _post('numbervoucher');
		$lengthcode = _post('lengthcode');
		
		$msg = '';
		if ($type == '' OR $plan == '' OR $server == '' OR $numbervoucher == '' OR $lengthcode == ''){
			$msg .= $_L['All_field_is_required']. '<br>';
		}
		if(Validator::UnsignedNumber($numbervoucher) == false){
            $msg .= 'The Number of Vouchers must be a number'. '<br>';
        }
		if(Validator::UnsignedNumber($lengthcode) == false){
            $msg .= 'The Length Code must be a number'. '<br>';
        }
        if($msg == ''){
			for ($i=0; $i < $numbervoucher; $i++){
				$code = strtoupper(substr(md5(time().rand(10000,99999)),0,$lengthcode));
				
				$d = ORM::for_table('tbl_voucher')->create();
				$d->type = $type;
				$d->routers = $server;
				$d->id_plan = $plan;
				$d->code = $code;
				$d->user = '0';
				$d->status = '0';
				$d->save();
			}
			
            r2(U . 'prepaid/voucher', 's', $_L['Voucher_Successfully']);
        }else{
            r2(U . 'prepaid/add-voucher/'.$id, 'e', $msg);
        }
        break;
		
    case 'voucher-delete':
        $id  = $routes['2'];

        $d = ORM::for_table('tbl_voucher')->find_one($id);
        if($d){
            $d->delete();
            r2(U . 'prepaid/voucher', 's', $_L['Delete_Successfully']);
        }
        break;
		
    case 'refill':
		$ui->assign('xfooter', '<script type="text/javascript" src="' . $_theme . '/scripts/form-elements.init.js"></script>');
		
		$c = ORM::for_table('tbl_customers')->find_many();
		$ui->assign('c',$c);

        $ui->display('refill.tpl');

        break;
		
    case 'refill-post':
	    $user = _post('id_customer');
		$code = _post('code');
		
		$v1 = ORM::for_table('tbl_voucher')->where('code',$code)->where('status',0)->find_one();
		
		$c = ORM::for_table('tbl_customers')->find_one($user);
		$p = ORM::for_table('tbl_plans')->find_one($v1['id_plan']);
		$b = ORM::for_table('tbl_user_recharges')->where('customer_id',$user)->find_one();
		
		$date_now = date("Y-m-d H:i:s");
		$date_only = date("Y-m-d");
		$time = date("H:i:s");
		
		$mikrotik = Router::_info($v1['routers']);
		$date_exp = date("Y-m-d", mktime(0,0,0,date("m"),date("d") + $p['validity'],date("Y")));

		if ($v1){
			if($v1['type'] == 'Hotspot'){
				if($b){
					try {
						$client = new RouterOS\Client($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
					} catch (Exception $e) {
						die('Unable to connect to the router.');
					}
					$printRequest = new RouterOS\Request(
						'/ip hotspot user print .proplist=name',
						RouterOS\Query::where('name', $c['username'])
					);
					$userName = $client->sendSync($printRequest)->getProperty('name');
					$removeRequest = new RouterOS\Request('/ip/hotspot/user/remove');
					$client($removeRequest
						->setArgument('numbers', $userName)
					);
					/* iBNuX Added:
					* 	Time limit to Mikrotik
					*	'Time_Limit', 'Data_Limit', 'Both_Limit'
					*/
					$addRequest = new RouterOS\Request('/ip/hotspot/user/add');
					if($p['typebp']=="Limited"){
						if($p['limit_type']=="Time_Limit"){
							if($p['time_unit']=='Hrs')
								$timelimit = $p['time_limit'].":00:00";
							else
								$timelimit = "00:".$p['time_limit'].":00";
							$client->sendSync($addRequest
								->setArgument('name', $c['username'])
								->setArgument('profile', $p['name_plan'])
								->setArgument('password', $c['password'])
								->setArgument('limit-uptime', $timelimit)
							);
						}else if($p['limit_type']=="Data_Limit"){
							if($p['data_unit']=='GB')
								$datalimit = $p['data_limit']."000000000";
							else
								$datalimit = $p['data_limit']."000000";
							$client->sendSync($addRequest
								->setArgument('name', $c['username'])
								->setArgument('profile', $p['name_plan'])
								->setArgument('password', $c['password'])
								->setArgument('limit-bytes-total', $datalimit)
							);
						}else if($p['limit_type']=="Both_Limit"){
							if($p['time_unit']=='Hrs')
								$timelimit = $p['time_limit'].":00:00";
							else
								$timelimit = "00:".$p['time_limit'].":00";
							if($p['data_unit']=='GB')
								$datalimit = $p['data_limit']."000000000";
							else
								$datalimit = $p['data_limit']."000000";
							$client->sendSync($addRequest
								->setArgument('name', $c['username'])
								->setArgument('profile', $p['name_plan'])
								->setArgument('password', $c['password'])
								->setArgument('limit-uptime', $timelimit)
								->setArgument('limit-bytes-total', $datalimit)
							);
						}
					}else{
						$client->sendSync($addRequest
							->setArgument('name', $c['username'])
							->setArgument('profile', $p['name_plan'])
							->setArgument('password', $c['password'])
						);
					}
					
					$b->customer_id = $user;
					$b->username = $c['username'];
					$b->plan_id = $v1['id_plan'];
					$b->namebp = $p['name_plan'];
					$b->recharged_on = $date_only;
					$b->expiration = $date_exp;
					$b->time = $time;
					$b->status = "on";
					$b->method = "voucher";
					$b->routers = $v1['routers'];
					$b->type = "Hotspot";
					$b->save();
					
					// insert table transactions
					$t = ORM::for_table('tbl_transactions')->create();
					$t->invoice = "INV-"._raid(5);
					$t->username = $c['username'];
					$t->plan_name = $p['name_plan'];
					$t->price = $p['price'];
					$t->recharged_on = $date_only;
					$t->expiration = $date_exp;
					$t->time = $time;
					$t->method = "voucher";
					$t->routers = $v1['routers'];
					$t->type = "Hotspot";
					$t->save();
					
				}else{
					try {
						$client = new RouterOS\Client($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
					} catch (Exception $e) {
						die('Unable to connect to the router.');
					}
					/* iBNuX Added:
					* 	Time limit to Mikrotik
					*	'Time_Limit', 'Data_Limit', 'Both_Limit'
					*/
					$addRequest = new RouterOS\Request('/ip/hotspot/user/add');
					if($p['typebp']=="Limited"){
						if($p['limit_type']=="Time_Limit"){
							if($p['time_unit']=='Hrs')
								$timelimit = $p['time_limit'].":00:00";
							else
								$timelimit = "00:".$p['time_limit'].":00";
							$client->sendSync($addRequest
								->setArgument('name', $c['username'])
								->setArgument('profile', $p['name_plan'])
								->setArgument('password', $c['password'])
								->setArgument('limit-uptime', $timelimit)
							);
						}else if($p['limit_type']=="Data_Limit"){
							if($p['data_unit']=='GB')
								$datalimit = $p['data_limit']."000000000";
							else
								$datalimit = $p['data_limit']."000000";
							$client->sendSync($addRequest
								->setArgument('name', $c['username'])
								->setArgument('profile', $p['name_plan'])
								->setArgument('password', $c['password'])
								->setArgument('limit-bytes-total', $datalimit)
							);
						}else if($p['limit_type']=="Both_Limit"){
							if($p['time_unit']=='Hrs')
								$timelimit = $p['time_limit'].":00:00";
							else
								$timelimit = "00:".$p['time_limit'].":00";
							if($p['data_unit']=='GB')
								$datalimit = $p['data_limit']."000000000";
							else
								$datalimit = $p['data_limit']."000000";
							$client->sendSync($addRequest
								->setArgument('name', $c['username'])
								->setArgument('profile', $p['name_plan'])
								->setArgument('password', $c['password'])
								->setArgument('limit-uptime', $timelimit)
								->setArgument('limit-bytes-total', $datalimit)
							);
						}
					}else{
						$client->sendSync($addRequest
							->setArgument('name', $c['username'])
							->setArgument('profile', $p['name_plan'])
							->setArgument('password', $c['password'])
						);
					}
					
					$d = ORM::for_table('tbl_user_recharges')->create();
					$d->customer_id = $user;
					$d->username = $c['username'];
					$d->plan_id = $v1['id_plan'];
					$d->namebp = $p['name_plan'];
					$d->recharged_on = $date_only;
					$d->expiration = $date_exp;
					$d->time = $time;
					$d->status = "on";
					$d->method = "voucher";
					$d->routers = $v1['routers'];
					$d->type = "Hotspot";
					$d->save();
					
					// insert table transactions
					$t = ORM::for_table('tbl_transactions')->create();
					$t->invoice = "INV-"._raid(5);
					$t->username = $c['username'];
					$t->plan_name = $p['name_plan'];
					$t->price = $p['price'];
					$t->recharged_on = $date_only;
					$t->expiration = $date_exp;
					$t->time = $time;
					$t->method = "voucher";
					$t->routers = $v1['routers'];
					$t->type = "Hotspot";
					$t->save();
					
				}
				
				$v1->status = "1";
				$v1->user = $c['username'];
				$v1->save();
				
			}else{
				if($b){
					try {
						$client = new RouterOS\Client($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
					} catch (Exception $e) {
						die('Unable to connect to the router.');
					}
					$printRequest = new RouterOS\Request(
						'/ppp secret print .proplist=name',
						RouterOS\Query::where('name', $c['username'])
					);
					$userName = $client->sendSync($printRequest)->getProperty('name');
					
					$removeRequest = new RouterOS\Request('/ppp/secret/remove');
					$client($removeRequest
						->setArgument('numbers', $userName)
					);
					
					$addRequest = new RouterOS\Request('/ppp/secret/add');
					$client->sendSync($addRequest
						->setArgument('name', $c['username'])
						->setArgument('service', 'pppoe')
						->setArgument('profile', $p['name_plan'])
						->setArgument('password', $c['password'])
					);
					
					$b->customer_id = $user;
					$b->username = $c['username'];
					$b->plan_id = $v1['id_plan'];
					$b->namebp = $p['name_plan'];
					$b->recharged_on = $date_only;
					$b->expiration = $date_exp;
					$b->time = $time;
					$b->status = "on";
					$b->method = "voucher";
					$b->routers = $v1['routers'];
					$b->type = "PPPOE";
					$b->save();
					
					// insert table transactions
					$t = ORM::for_table('tbl_transactions')->create();
					$t->invoice = "INV-"._raid(5);
					$t->username = $c['username'];
					$t->plan_name = $p['name_plan'];
					$t->price = $p['price'];
					$t->recharged_on = $date_only;
					$t->expiration = $date_exp;
					$t->time = $time;
					$t->method = "voucher";
					$t->routers = $v1['routers'];
					$t->type = "PPPOE";
					$t->save();
					
				}else{
					try {
						$client = new RouterOS\Client($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
					} catch (Exception $e) {
						die('Unable to connect to the router.');
					}
					$addRequest = new RouterOS\Request('/ppp/secret/add');
					$client->sendSync($addRequest
						->setArgument('name', $c['username'])
						->setArgument('service', 'pppoe')
						->setArgument('profile', $p['name_plan'])
						->setArgument('password', $c['password'])
					);
					
					$d = ORM::for_table('tbl_user_recharges')->create();
					$d->customer_id = $user;
					$d->username = $c['username'];
					$d->plan_id = $v1['id_plan'];
					$d->namebp = $p['name_plan'];
					$d->recharged_on = $date_only;
					$d->expiration = $date_exp;
					$d->time = $time;
					$d->status = "on";
					$d->method = "voucher";
					$d->routers = $v1['routers'];
					$d->type = "PPPOE";
					$d->save();
					
					// insert table transactions
					$t = ORM::for_table('tbl_transactions')->create();
					$t->invoice = "INV-"._raid(5);
					$t->username = $c['username'];
					$t->plan_name = $p['name_plan'];
					$t->price = $p['price'];
					$t->recharged_on = $date_only;
					$t->expiration = $date_exp;
					$t->time = $time;
					$t->method = "voucher";
					$t->routers = $v1['routers'];
					$t->type = "PPPOE";
					$t->save();
				}
				
				$v1->status = "1";
				$v1->user = $c['username'];
				$v1->save();
			}
			$in = ORM::for_table('tbl_transactions')->where('username',$c['username'])->order_by_desc('id')->find_one();
			$ui->assign('in',$in);
			
			$ui->assign('date',$date_now);
			$ui->display('invoice.tpl');
		}else{
			r2(U . 'prepaid/refill', 'e', $_L['Voucher_Not_Valid']);
		}
        break;
		
    default:
        echo 'action not defined';
}