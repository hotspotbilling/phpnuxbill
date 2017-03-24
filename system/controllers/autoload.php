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

switch ($action) {
    case 'pool':
		$routers = _get('routers');
		$d = ORM::for_table('tbl_pool')->where('routers', $routers)->find_many();
		$ui->assign('d',$d);
		
        $ui->display('autoload-pool.tpl');
        break;
		
    case 'server':
		$d = ORM::for_table('tbl_routers')->find_many();
		$ui->assign('d',$d);
		
        $ui->display('autoload-server.tpl');
        break;
		
    case 'plan':
		$server = _post('server');
		$jenis = _post('jenis');
		$d = ORM::for_table('tbl_plans')->where('routers', $server)->where('type', $jenis)->find_many();
		$ui->assign('d',$d);
		
        $ui->display('autoload.tpl');
        break;
		
    default:
        echo 'action not defined';
}