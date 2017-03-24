<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)


* @copyright	Copyright (C) 2014-2015 PHP Mikrotik Billing
* @license		GNU General Public License version 2 or later; see LICENSE.txt

**/
if (isset($routes['1'])) {
    $do = $routes['1'];
} else {
    $do = 'login-display';
}

switch($do){
    case 'post':
		$username = _post('username');
		$password = _post('password');
		if($username != '' AND $password != ''){
			$d = ORM::for_table('tbl_users')->where('username',$username)->find_one();
			if($d){
			 $d_pass = $d['password'];
				if(Password::_verify($password,$d_pass) == true){
					$_SESSION['aid'] = $d['id'];
					$d->last_login = date('Y-m-d H:i:s');
					$d->save();
					_log($username .' '. $_L['Login_Successful'],'Admin',$d['id']);
					r2(U.'dashboard');
				}else{
					_msglog('e',$_L['Invalid_Username_or_Password']);
					_log($username .' '. $_L['Failed_Login'],'Admin');
					r2(U.'admin');
				}
			}else{
				_msglog('e',$_L['Invalid_Username_or_Password']);
				r2(U.'admin');
			}
		}else{
			_msglog('e',$_L['Invalid_Username_or_Password']);
			r2(U.'admin');
		}

        break;

    case 'login-display':
        $ui->display('admin.tpl');
        break;

    default:
        $ui->display('admin.tpl');
        break;
}

