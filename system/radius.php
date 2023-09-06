<?php
/**
* PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
**/

if(php_sapi_name() !== 'cli'){
    die("RUN ON COMMAND LINE ONLY BY RADIUS ENGINE");
}

require_once __DIR__.File::pathFixer('/../config.php');
require_once __DIR__.File::pathFixer('orm.php');
require_once __DIR__.File::pathFixer('/autoload/PEAR2/Autoload.php');
include __DIR__.File::pathFixer("/autoload/Hookers.php");

use PEAR2\Net\RouterOS;

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