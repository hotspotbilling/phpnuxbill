<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)


* @copyright	Copyright (C) 2014-2015 PHP Mikrotik Billing
* @license		GNU General Public License version 2 or later; see LICENSE.txt

**/
_admin();
$ui->assign('_title', $_L['Customers'].' - '. $config['CompanyName']);
$ui->assign('_system_menu', 'customers');

$action = $routes['1'];
$admin = Admin::_info();
$ui->assign('_admin', $admin);

use PEAR2\Net\RouterOS;
require_once 'system/autoload/PEAR2/Autoload.php';

if($admin['user_type'] != 'Admin' AND $admin['user_type'] != 'Sales'){
	r2(U."dashboard",'e',$_L['Do_Not_Access']);
}

switch ($action) {
    case 'list':
        $ui->assign('xfooter', '<script type="text/javascript" src="ui/lib/c/customers.js"></script>');
		$username = _post('username');
		if ($username != ''){
			$paginator = Paginator::bootstrap('tbl_customers','username','%'.$username.'%');
			$d = ORM::for_table('tbl_customers')->where_like('username','%'.$username.'%')->offset($paginator['startpoint'])->limit($paginator['limit'])->order_by_desc('id')->find_many();
		}else{
			$paginator = Paginator::bootstrap('tbl_customers');
			$d = ORM::for_table('tbl_customers')->offset($paginator['startpoint'])->limit($paginator['limit'])->order_by_desc('id')->find_many();
		}
		
        $ui->assign('d',$d);
		$ui->assign('paginator',$paginator);
        $ui->display('customers.tpl');
        break;

    case 'add':
        $ui->display('customers-add.tpl');
        break;

    case 'edit':
        $id  = $routes['2'];
        $d = ORM::for_table('tbl_customers')->find_one($id);
        if($d){
            $ui->assign('d',$d);
            $ui->display('customers-edit.tpl');
        }else{
            r2(U . 'customers/list', 'e', $_L['Account_Not_Found']);
        }
        break;

    case 'delete':
        $id  = $routes['2'];

        $d = ORM::for_table('tbl_customers')->find_one($id);
        if($d){
				$c = ORM::for_table('tbl_user_recharges')->where('username',$d['username'])->find_one();
					if ($c){
						$mikrotik = Router::_info($c['routers']);
						if($c['type'] == 'Hotspot'){
							try {
								$client = new RouterOS\Client($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
							} catch (Exception $e) {
								die('Unable to connect to the router.');
							}
							$printRequest = new RouterOS\Request('/ip/hotspot/user/print');
							$printRequest->setArgument('.proplist', '.id');
							$printRequest->setQuery(RouterOS\Query::where('name', $c['username']));
							$id = $client->sendSync($printRequest)->getProperty('.id');
							
							$setRequest = new RouterOS\Request('/ip/hotspot/user/remove');
							$setRequest->setArgument('numbers', $id);
							$client->sendSync($setRequest);
							
							//remove hotspot active
							$onlineRequest = new RouterOS\Request('/ip/hotspot/active/print');
							$onlineRequest->setArgument('.proplist', '.id');
							$onlineRequest->setQuery(RouterOS\Query::where('user', $c['username']));
							$id = $client->sendSync($onlineRequest)->getProperty('.id');

							$removeRequest = new RouterOS\Request('/ip/hotspot/active/remove');
							$removeRequest->setArgument('numbers', $id);
							$client->sendSync($removeRequest);
							
						}else{
							
							try {
								$client = new RouterOS\Client($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
							} catch (Exception $e) {
								die('Unable to connect to the router.');
							}
							$printRequest = new RouterOS\Request('/ppp/secret/print');
							$printRequest->setArgument('.proplist', '.id');
							$printRequest->setQuery(RouterOS\Query::where('name', $c['username']));
							$id = $client->sendSync($printRequest)->getProperty('.id');
							
							$setRequest = new RouterOS\Request('/ppp/secret/remove');
							$setRequest->setArgument('numbers', $id);
							$client->sendSync($setRequest);
							
							//remove pppoe active
							$onlineRequest = new RouterOS\Request('/ppp/active/print');
							$onlineRequest->setArgument('.proplist', '.id');
							$onlineRequest->setQuery(RouterOS\Query::where('name', $c['username']));
							$id = $client->sendSync($onlineRequest)->getProperty('.id');

							$removeRequest = new RouterOS\Request('/ppp/active/remove');
							$removeRequest->setArgument('numbers', $id);
							$client->sendSync($removeRequest);
						}
						try{
							$d->delete();
						}catch(Exception $e){}
						try{
							$c->delete();
						}catch(Exception $e){}
					}else{
						try{
							$d->delete();
						}catch(Exception $e){}
						try{
							$c->delete();
						}catch(Exception $e){}
					}
           
            r2(U . 'customers/list', 's', $_L['User_Delete_Ok']);
        }
        break;

    case 'add-post':
        $username = _post('username');
        $fullname = _post('fullname');
        $password = _post('password');
        $cpassword = _post('cpassword');
        $address = _post('address');
		$phonenumber = _post('phonenumber');
		
        $msg = '';
        if(Validator::Length($username,35,2) == false){
            $msg .= 'Username should be between 3 to 55 characters'. '<br>';
        }
        if(Validator::Length($fullname,36,2) == false){
            $msg .= 'Full Name should be between 3 to 25 characters'. '<br>';
        }
        if(!Validator::Length($password,35,2)){
            $msg .= 'Password should be between 3 to 35 characters'. '<br>';

        }
        if($password != $cpassword){
            $msg .= 'Passwords does not match'. '<br>';
        }

        $d = ORM::for_table('tbl_customers')->where('username',$username)->find_one();
        if($d){
            $msg .= $_L['account_already_exist']. '<br>';
        }

        if($msg == ''){
            $d = ORM::for_table('tbl_customers')->create();
            $d->username = $username;
            $d->password = $password;
            $d->fullname = $fullname;
            $d->address = $address;
			$d->phonenumber = $phonenumber;
            $d->save();
            r2(U . 'customers/list', 's', $_L['account_created_successfully']);
        }else{
            r2(U . 'customers/add', 'e', $msg);
        }
        break;

    case 'edit-post':
        $username = _post('username');
        $fullname = _post('fullname');
        $password = _post('password');
        $cpassword = _post('cpassword');
        $address = _post('address');
		$phonenumber = _post('phonenumber');

        $msg = '';
        if(Validator::Length($username,16,2) == false){
            $msg .= 'Username should be between 3 to 15 characters'. '<br>';
        }
        if(Validator::Length($fullname,26,2) == false){
            $msg .= 'Full Name should be between 3 to 25 characters'. '<br>';
        }
        if($password != ''){
            if(!Validator::Length($password,15,2)){
                $msg .= 'Password should be between 3 to 15 characters'. '<br>';

            }
            if($password != $cpassword){
                $msg .= 'Passwords does not match'. '<br>';
            }
        }

        $id = _post('id');
        $d = ORM::for_table('tbl_customers')->find_one($id);
        if($d){

        }else{
            $msg .= $_L['Data_Not_Found']. '<br>';
        }

        if($d['username'] != $username){
            $c = ORM::for_table('tbl_customers')->where('username',$username)->find_one();
            if($c){
                $msg .= $_L['account_already_exist']. '<br>';
            }
        }

        if($msg == ''){
					$c = ORM::for_table('tbl_user_recharges')->where('username',$username)->find_one();
					if ($c){
						$mikrotik = Router::_info($c['routers']);
						if($c['type'] == 'Hotspot'){
							try {
								$client = new RouterOS\Client($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
							} catch (Exception $e) {
								die('Unable to connect to the router.');
							}
							$printRequest = new RouterOS\Request('/ip/hotspot/user/print');
							$printRequest->setArgument('.proplist', '.id');
							$printRequest->setQuery(RouterOS\Query::where('name', $c['username']));
							$id = $client->sendSync($printRequest)->getProperty('.id');
							
							$setRequest = new RouterOS\Request('/ip/hotspot/user/set');
							$setRequest->setArgument('numbers', $id);
							$setRequest->setArgument('password', $password);
							$client->sendSync($setRequest);
							
							//remove hotspot active
							$onlineRequest = new RouterOS\Request('/ip/hotspot/active/print');
							$onlineRequest->setArgument('.proplist', '.id');
							$onlineRequest->setQuery(RouterOS\Query::where('user', $c['username']));
							$id = $client->sendSync($onlineRequest)->getProperty('.id');

							$removeRequest = new RouterOS\Request('/ip/hotspot/active/remove');
							$removeRequest->setArgument('numbers', $id);
							$client->sendSync($removeRequest);
							
							$d->password = $password;
							$d->save();
							
						}else{
							try {
								$client = new RouterOS\Client($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
							} catch (Exception $e) {
								die('Unable to connect to the router.');
							}
							$printRequest = new RouterOS\Request('/ppp/secret/print');
							$printRequest->setArgument('.proplist', '.id');
							$printRequest->setQuery(RouterOS\Query::where('name', $c['username']));
							$id = $client->sendSync($printRequest)->getProperty('.id');
							
							$setRequest = new RouterOS\Request('/ppp/secret/set');
							$setRequest->setArgument('numbers', $id);
							$setRequest->setArgument('password', $password);
							$client->sendSync($setRequest);
							
							//remove pppoe active
							$onlineRequest = new RouterOS\Request('/ppp/active/print');
							$onlineRequest->setArgument('.proplist', '.id');
							$onlineRequest->setQuery(RouterOS\Query::where('name', $c['username']));
							$id = $client->sendSync($onlineRequest)->getProperty('.id');

							$removeRequest = new RouterOS\Request('/ppp/active/remove');
							$removeRequest->setArgument('numbers', $id);
							$client->sendSync($removeRequest);
							
							$d->password = $password;
							$d->save();
						}
						$d->username = $username;
						if($password != ''){
							$d->password = $password;
						}
						$d->fullname = $fullname;
						$d->address = $address;
						$d->phonenumber = $phonenumber;
						$d->save();
					}else{
						$d->username = $username;
						if($password != ''){
							$d->password = $password;
						}
						$d->fullname = $fullname;
						$d->address = $address;
						$d->phonenumber = $phonenumber;
						$d->save();
					}
            r2(U . 'customers/list', 's', 'User Updated Successfully');
        }else{
            r2(U . 'customers/edit/'.$id, 'e', $msg);
        }
        break;

    default:
        echo 'action not defined';
}