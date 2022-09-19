<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
**/

if(php_sapi_name() !== 'cli'){
    die("RUN ON COMMAND LINE ONLY BY RADIUS ENGINE");
}

require(__DIR__.'/../config.php');
require(__DIR__.'/orm.php');

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