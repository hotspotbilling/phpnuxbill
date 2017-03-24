<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)


* @copyright	Copyright (C) 2014-2015 PHP Mikrotik Billing
* @license		GNU General Public License version 2 or later; see LICENSE.txt

**/
_auth();
$ui->assign('_title', $_L['My_Account'].'- '. $config['CompanyName']);
$ui->assign('_system_menu', 'accounts');

$action = $routes['1'];
$user = User::_info();
$ui->assign('_user', $user);

use PEAR2\Net\RouterOS;
require_once 'system/autoload/PEAR2/Autoload.php';

switch ($action) {
	
    case 'change-password':
        $ui->display('user-change-password.tpl');
        break;

    case 'change-password-post':
        $password = _post('password');
        if($password != ''){
            $d = ORM::for_table('tbl_customers')->where('username',$user['username'])->find_one();
            if($d){
                $d_pass = $d['password'];
				$npass = _post('npass');
                $cnpass = _post('cnpass');
				
                if(Password::_uverify($password,$d_pass) == true){
					if(!Validator::Length($npass,15,2)){
                        r2(U.'accounts/change-password','e','New Password must be 3 to 14 character');
                    }
                    if($npass != $cnpass){
                        r2(U.'accounts/change-password','e','Both Password should be same');
                    }

					$c = ORM::for_table('tbl_user_recharges')->where('username',$user['username'])->find_one();
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
							$printRequest->setQuery(RouterOS\Query::where('name', $user['username']));
							$id = $client->sendSync($printRequest)->getProperty('.id');
							
							$setRequest = new RouterOS\Request('/ip/hotspot/user/set');
							$setRequest->setArgument('numbers', $id);
							$setRequest->setArgument('password', $npass);
							$client->sendSync($setRequest);
							
							//remove hotspot active
							$onlineRequest = new RouterOS\Request('/ip/hotspot/active/print');
							$onlineRequest->setArgument('.proplist', '.id');
							$onlineRequest->setQuery(RouterOS\Query::where('user', $user['username']));
							$id = $client->sendSync($onlineRequest)->getProperty('.id');

							$removeRequest = new RouterOS\Request('/ip/hotspot/active/remove');
							$removeRequest->setArgument('numbers', $id);
							$client->sendSync($removeRequest);
							
							$d->password = $npass;
							$d->save();
							
							_msglog('s',$_L['Password_Changed_Successfully']);
							_log('['.$user['username'].']: Password changed successfully','User',$user['id']);
							
							r2(U.'login');
							
						}else{
							try {
								$client = new RouterOS\Client($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
							} catch (Exception $e) {
								die('Unable to connect to the router.');
							}
							$printRequest = new RouterOS\Request('/ppp/secret/print');
							$printRequest->setArgument('.proplist', '.id');
							$printRequest->setQuery(RouterOS\Query::where('name', $user['username']));
							$id = $client->sendSync($printRequest)->getProperty('.id');
							
							$setRequest = new RouterOS\Request('/ppp/secret/set');
							$setRequest->setArgument('numbers', $id);
							$setRequest->setArgument('password', $npass);
							$client->sendSync($setRequest);
							
							//remove pppoe active
							$onlineRequest = new RouterOS\Request('/ppp/active/print');
							$onlineRequest->setArgument('.proplist', '.id');
							$onlineRequest->setQuery(RouterOS\Query::where('name', $user['username']));
							$id = $client->sendSync($onlineRequest)->getProperty('.id');

							$removeRequest = new RouterOS\Request('/ppp/active/remove');
							$removeRequest->setArgument('numbers', $id);
							$client->sendSync($removeRequest);
							
							$d->password = $npass;
							$d->save();
							
							_msglog('s',$_L['Password_Changed_Successfully']);
							_log('['.$user['username'].']: Password changed successfully','User',$user['id']);
							
							r2(U.'login');
						}
					}else{
						$d->password = $npass;
						$d->save();
						
						_msglog('s',$_L['Password_Changed_Successfully']);
						_log('['.$user['username'].']: Password changed successfully','User',$user['id']);
						
						r2(U.'login');
					}
					
                }else{
                    r2(U.'accounts/change-password','e',$_L['Incorrect_Current_Password']);
                }
            }else{
                r2(U.'accounts/change-password','e',$_L['Incorrect_Current_Password']);
            }
        }else{
            r2(U.'accounts/change-password','e',$_L['Incorrect_Current_Password']);
        }
        break;

    case 'profile':
		
        $id  = $_SESSION['uid'];
        $d = ORM::for_table('tbl_customers')->find_one($id);
        if($d){
            $ui->assign('d',$d);
            $ui->display('user-profile.tpl');
        }else{
            r2(U . 'accounts/users', 'e', $_L['Account_Not_Found']);
        }
        break;

    case 'edit-profile-post':
        $fullname = _post('fullname');
        $address = _post('address');
        $phonenumber = _post('phonenumber');

        $msg = '';
        if(Validator::Length($fullname,31,2) == false){
            $msg .= 'Full Name should be between 3 to 30 characters'. '<br>';
        }
		if(Validator::UnsignedNumber($phonenumber) == false){
			$msg .= 'Phone Number must be a number'. '<br>';
		}
		
        $id = _post('id');
        $d = ORM::for_table('tbl_customers')->find_one($id);
        if($d){
        }else{
            $msg .= $_L['Data_Not_Found']. '<br>';
        }

        if($msg == ''){
            $d->fullname = $fullname;
			$d->address = $address;
			$d->phonenumber = $phonenumber;
            $d->save();
			
			_log('['.$user['username'].']: '.$_L['User_Updated_Successfully'],'User',$user['id']);
            r2(U . 'accounts/profile', 's', $_L['User_Updated_Successfully']);
        }else{
            r2(U . 'accounts/profile', 'e', $msg);
        }
        break;
		
    default:
        echo 'action not defined';
}