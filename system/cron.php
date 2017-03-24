<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)


* @copyright	Copyright (C) 2014-2015 PHP Mikrotik Billing
* @license		GNU General Public License version 2 or later; see LICENSE.txt

**/

require('config.php');
require('orm.php');

use PEAR2\Net\RouterOS;
require_once 'autoload/PEAR2/Autoload.php';

ORM::configure("mysql:host=$db_host;dbname=$db_name");
ORM::configure('username', $db_user);
ORM::configure('password', $db_password);
ORM::configure('return_result_sets', true);
ORM::configure('logging', true);

$result = ORM::for_table('tbl_appconfig')->find_many();
foreach($result as $value){
    $config[$value['setting']]=$value['value'];
}
date_default_timezone_set($config['timezone']);

$d = ORM::for_table('tbl_user_recharges')->where('status','on')->find_many();

foreach ($d as $ds){
	if($ds['type'] == 'Hotspot'){
		$date_now = strtotime(date("Y-m-d H:i:s"));
		$expiration = strtotime($ds['expiration'].' '.$ds['time']);
		echo $ds['expiration']." : ".$ds['username'];
		if ($date_now >= $expiration){
			echo " : EXPIRED \r\n";
			$u = ORM::for_table('tbl_user_recharges')->where('id',$ds['id'])->find_one();
			$c = ORM::for_table('tbl_customers')->where('id',$ds['customer_id'])->find_one();
			$m = ORM::for_table('tbl_routers')->where('name',$ds['routers'])->find_one();
			
			try {
				$client = new RouterOS\Client($m['ip_address'], $m['username'], $m['password']);
			} catch (Exception $e) {
				die('Unable to connect to the router.');
			}
			
			$printRequest = new RouterOS\Request('/ip/hotspot/user/print');
			$printRequest->setArgument('.proplist', '.id');
			$printRequest->setQuery(RouterOS\Query::where('name', $c['username']));
			$id = $client->sendSync($printRequest)->getProperty('.id');

			$setRequest = new RouterOS\Request('/ip/hotspot/user/set');
			$setRequest->setArgument('numbers', $id);
			$setRequest->setArgument('limit-uptime', '00:00:05');
			$client->sendSync($setRequest);

			//remove hotspot active
			$onlineRequest = new RouterOS\Request('/ip/hotspot/active/print');
			$onlineRequest->setArgument('.proplist', '.id');
			$onlineRequest->setQuery(RouterOS\Query::where('user', $c['username']));
			$id = $client->sendSync($onlineRequest)->getProperty('.id');

			$removeRequest = new RouterOS\Request('/ip/hotspot/active/remove');
			$removeRequest->setArgument('numbers', $id);
			$client->sendSync($removeRequest);
			
			//update database user dengan status off
			$u->status = 'off';
			$u->save();
		}else
			echo " : ACTIVE \r\n";
	}else{
		$date_now = strtotime(date("Y-m-d H:i:s"));
		$expiration = strtotime($ds['expiration'].' '.$ds['time']);
		echo $ds['expiration']." : ".$ds['username'];
		if ($date_now >= $expiration){
			echo " : EXPIRED \r\n";
			$u = ORM::for_table('tbl_user_recharges')->where('id',$ds['id'])->find_one();
			$c = ORM::for_table('tbl_customers')->where('id',$ds['customer_id'])->find_one();
			$m = ORM::for_table('tbl_routers')->where('name',$ds['routers'])->find_one();

			try {
				$client = new RouterOS\Client($m['ip_address'], $m['username'], $m['password']);
			} catch (Exception $e) {
				die('Unable to connect to the router.');
			}
			$printRequest = new RouterOS\Request('/ppp/secret/print');
			$printRequest->setArgument('.proplist', '.id');
			$printRequest->setQuery(RouterOS\Query::where('name', $c['username']));
			$id = $client->sendSync($printRequest)->getProperty('.id');

			$setRequest = new RouterOS\Request('/ppp/secret/disable');
			$setRequest->setArgument('numbers', $id);
			$client->sendSync($setRequest);

			//remove hotspot active
			$onlineRequest = new RouterOS\Request('/ppp/secret/print');
			$onlineRequest->setArgument('.proplist', '.id');
			$onlineRequest->setQuery(RouterOS\Query::where('name', $c['username']));
			$id = $client->sendSync($onlineRequest)->getProperty('.id');

			$removeRequest = new RouterOS\Request('/ppp/active/remove');
			$removeRequest->setArgument('numbers', $id);
			$client->sendSync($removeRequest);
			
			$u->status = 'off';
			$u->save();
		}else
			echo " : ACTIVE \r\n";
	}
}

?>