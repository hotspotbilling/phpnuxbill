<?php

/**
 * PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)

 **/
session_start();
function r2($to, $ntype = 'e', $msg = '')
{
    if ($msg == '') {
        header("location: $to");
        exit;
    }
    $_SESSION['ntype'] = $ntype;
    $_SESSION['notify'] = $msg;
    header("location: $to");
    exit;
}

if (file_exists('system/config.php')) {
    require('system/config.php');
} else {
    r2('system/install');
}

function safedata($value)
{
    $value = trim($value);
    return $value;
}

function _post($param, $defvalue = '')
{
    if (!isset($_POST[$param])) {
        return $defvalue;
    } else {
        return safedata($_POST[$param]);
    }
}

function _get($param, $defvalue = '')
{
    if (!isset($_GET[$param])) {
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
foreach ($result as $value) {
    $config[$value['setting']] = $value['value'];
}

date_default_timezone_set($config['timezone']);
$_c = $config;

function _notify($msg, $type = 'e')
{
    $_SESSION['ntype'] = $type;
    $_SESSION['notify'] = $msg;
}

require_once('system/vendors/smarty/libs/Smarty.class.php');
$_theme = APP_URL . '/ui/ui';
$lan_file = 'system/lan/' . $config['language'] . '/common.lan.php';
require($lan_file);
$ui = new Smarty();
$ui->setTemplateDir('ui/ui/');
$ui->setCompileDir('ui/compiled/');
$ui->setConfigDir('ui/conf/');
$ui->setCacheDir('ui/cache/');
$ui->assign('app_url', APP_URL);
define('U', APP_URL . '/index.php?_route=');
$ui->assign('_url', APP_URL . '/index.php?_route=');
$ui->assign('_theme', $_theme);
$ui->assign('_path', __DIR__);
$ui->assign('_c', $config);
$ui->assign('_L', $_L);
$ui->assign('_system_menu', 'dashboard');
$ui->assign('_title', $config['CompanyName']);

function _msglog($type, $msg)
{
    $_SESSION['ntype'] = $type;
    $_SESSION['notify'] = $msg;
}

if (isset($_SESSION['notify'])) {
    $notify = $_SESSION['notify'];
    $ntype = $_SESSION['ntype'];
    if ($ntype == 's') {
        $ui->assign('notify', '<div class="alert alert-info">
		<button type="button" class="close" data-dismiss="alert">
		<span aria-hidden="true">×</span>
		</button>
		<div>' . $notify . '</div></div>');
    } else {
        $ui->assign('notify', '<div class="alert alert-danger">
		<button type="button" class="close" data-dismiss="alert">
		<span aria-hidden="true">×</span>
		</button>
		<div>' . $notify . '</div></div>');
    }
    unset($_SESSION['notify']);
    unset($_SESSION['ntype']);
}

// on some server, it getting error because of slash is backwards
function _autoloader($class)
{
    if (strpos($class, '_') !== false) {
        $class = str_replace('_', '/', $class);
        if (file_exists('autoload/' . $class . '.php')) {
            include 'autoload/' . $class . '.php';
        } else {
            $class = str_replace("\\", "/", $class);
            if (file_exists(__DIR__ . '/autoload/' . $class . '.php'))
                include __DIR__ . '/autoload/' . $class . '.php';
        }
    } else {
        if (file_exists('autoload/' . $class . '.php')) {
            include 'autoload/' . $class . '.php';
        } else {
            $class = str_replace("\\", "/", $class);
            if (file_exists(__DIR__ . '/autoload/' . $class . '.php'))
                include __DIR__ . '/autoload/' . $class . '.php';
        }
    }
}

spl_autoload_register('_autoloader');

function _auth()
{
    if (isset($_SESSION['uid'])) {
        return true;
    } else {
        r2(U . 'login');
    }
}

function _admin()
{
    if (isset($_SESSION['aid'])) {
        return true;
    } else {
        r2(U . 'login');
    }
}

function _raid($l)
{
    $r =  substr(str_shuffle(str_repeat('0123456789', $l)), 0, $l);
    return $r;
}

function _log($description, $type = '', $userid = '0')
{
    $d = ORM::for_table('tbl_logs')->create();
    $d->date = date('Y-m-d H:i:s');
    $d->type = $type;
    $d->description = $description;
    $d->userid = $userid;
    $d->ip = $_SERVER["REMOTE_ADDR"];
    $d->save();
}


function sendTelegram($txt)
{
    global $_c;
    if(!empty($_c['telegram_bot']) && !empty($_c['telegram_target_id'])){
        file_get_contents('https://api.telegram.org/bot'.$_c['telegram_bot'].'/sendMessage?chat_id='.$_c['telegram_target_id'].'&text=' . urlencode($txt));
    }
}


function sendSMS($phone, $txt)
{
    global $_c;
    if(!empty($_c['sms_url'])){
        $smsurl = str_replace('[number]',urlencode($phone),$_c['sms_url']);
        $smsurl = str_replace('[text]',urlencode($txt),$smsurl);
        file_get_contents($smsurl);
    }
}


function time_elapsed_string($datetime, $full = false)
{
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
    exit("$sys_render");
}
