<?php
/**
* PHP Mikrotik Billing (www.phpmixbill.com)
* Ismail Marzuqi <iesien22@yahoo.com>
* @version		5.0
* @copyright	Copyright (C) 2014-2015 PHP Mikrotik Billing
* @license		GNU General Public License version 2 or later; see LICENSE.txt
* @donate		PayPal: iesien22@yahoo.com / Bank Mandiri: 130.00.1024957.4
**/
session_start();
function r2($to,$ntype='e',$msg=''){
    if($msg==''){
        header("location: $to"); 
		exit;
    }
    $_SESSION['ntype']=$ntype; 
	$_SESSION['notify']=$msg; 
	header("location: $to"); 
	exit;
}

if (file_exists('system/config.php')) {
    require('system/config.php');
} else {
    r2('system/install');
}

function safedata($value){
    $value = trim($value);
    return $value;
}

function _post($param,$defvalue = '') {
    if(!isset($_POST[$param])) 	{
        return $defvalue;
    } else {
        return safedata($_POST[$param]);
    }
}

function _get($param,$defvalue = ''){
    if(!isset($_GET[$param])) {
        return $defvalue;
    } else {
        return safedata($_GET[$param]);
    }
}

require('system/orm.php');

ORM::configure("mysql:host=$db_host;dbname=$db_name");
ORM::configure('username', $db_user);
ORM::configure('password', $db_password);
ORM::configure('driver_options', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
ORM::configure('return_result_sets', true);
ORM::configure('logging', true);

$result = ORM::for_table('tbl_appconfig')->find_many();
foreach($result as $value){
    $config[$value['setting']]=$value['value'];
}

date_default_timezone_set($config['timezone']);
$_c = $config;

function _notify($msg,$type='e'){
    $_SESSION['ntype']=$type ; $_SESSION['notify']=$msg ;
}

require_once('system/vendors/smarty/libs/Smarty.class.php');
$_theme = APP_URL.'/ui/theme/'.$config['theme'];
$lan_file = 'system/lan/' . $config['language'] . '/common.lan.php';
require($lan_file);
$ui = new Smarty();
$ui->setTemplateDir('ui/theme/' . $config['theme'] . '/');
$ui->setCompileDir('ui/compiled/');
$ui->setConfigDir('ui/conf/');
$ui->setCacheDir('ui/cache/');
$ui->assign('app_url', APP_URL);
define('U', APP_URL.'/index.php?_route=');
$ui->assign('_url', APP_URL.'/index.php?_route=');
$ui->assign('_theme', $_theme);
$ui->assign('_c', $config);
$ui->assign('_L', $_L);
$ui->assign('_system_menu', 'dashboard');
$ui->assign('_title', $config['CompanyName']);

function _msglog($type,$msg){
    $_SESSION['ntype'] = $type;
    $_SESSION['notify'] = $msg;
}

if (isset($_SESSION['notify'])) {
    $notify = $_SESSION['notify'];
    $ntype = $_SESSION['ntype'];
    if ($ntype == 's') {
		$ui->assign('notify','<div class="alert alert-info">
		<button type="button" class="close" data-dismiss="alert">
		<span aria-hidden="true">×</span>
		</button>
		<div>'.$notify.'</div></div>');
    } else {
		$ui->assign('notify','<div class="alert alert-danger">
		<button type="button" class="close" data-dismiss="alert">
		<span aria-hidden="true">×</span>
		</button>
		<div>'.$notify.'</div></div>');
    }
    unset($_SESSION['notify']);
    unset($_SESSION['ntype']);
}

function _autoloader($class) {
    if (strpos($class, '_') !== false) {
        $class = str_replace('_','/',$class);
        include 'autoload/' . $class . '.php';
    } else{
        include 'autoload/' . $class . '.php';
    }
}

spl_autoload_register('_autoloader');

function _auth(){
    if(isset($_SESSION['uid'])){
        return true;
    } else{
        r2(U.'login');
    }
}

function _admin(){
    if(isset($_SESSION['aid'])){
        return true;
    } else{
        r2(U.'login');
    }
}

function _raid($l){
    $r=  substr(str_shuffle(str_repeat('0123456789',$l)),0,$l);
    return $r;
}

function _log($description,$type='',$userid='0'){
    $d = ORM::for_table('tbl_logs')->create();
    $d->date = date('Y-m-d H:i:s');
    $d->type = $type;
    $d->description = $description;
    $d->userid = $userid;
    $d->ip = $_SERVER["REMOTE_ADDR"];
    $d->save();
}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

// Routing Engine
$req = _get('_route');
$routes = explode('/', $req);
$handler = $routes['0'];
if ($handler == '') {
    $handler = 'default';
}
$sys_render = 'system/controllers/' . $handler . '.php';
if (file_exists($sys_render)) {
    include($sys_render);
} else {
    exit ("$sys_render");
}
