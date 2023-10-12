<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)

 **/


// on some server, it getting error because of slash is backwards
function _autoloader($class)
{
    if (strpos($class, '_') !== false) {
        $class = str_replace('_', DIRECTORY_SEPARATOR, $class);
        if (file_exists('autoload' . DIRECTORY_SEPARATOR . $class . '.php')) {
            include 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        } else {
            $class = str_replace("\\", DIRECTORY_SEPARATOR, $class);
            if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php'))
                include __DIR__ . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        }
    } else {
        if (file_exists('autoload' . DIRECTORY_SEPARATOR . $class . '.php')) {
            include 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        } else {
            $class = str_replace("\\", DIRECTORY_SEPARATOR, $class);
            if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php'))
                include __DIR__ . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . $class . '.php';
        }
    }
}

spl_autoload_register('_autoloader');

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

if (file_exists('config.php')) {
    require('config.php');
} else {
    r2('install');
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
try {

    require_once File::pathFixer('system/orm.php');

    ORM::configure("mysql:host=$db_host;dbname=$db_name");
    ORM::configure('username', $db_user);
    ORM::configure('password', $db_password);
    ORM::configure('driver_options', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
    ORM::configure('return_result_sets', true);
    if ($_app_stage != 'Live') {
        ORM::configure('logging', true);
    }

    $result = ORM::for_table('tbl_appconfig')->find_many();
    foreach ($result as $value) {
        $config[$value['setting']] = $value['value'];
    }

    date_default_timezone_set($config['timezone']);
    $_c = $config;

    // check if proxy setup in database
    if (empty($http_proxy) && !empty($config['http_proxy'])) {
        $http_proxy = $config['http_proxy'];
        if (empty($http_proxyauth) && !empty($config['http_proxyauth'])) {
            $http_proxyauth = $config['http_proxyauth'];
        }
    }
    if ((!empty($radius_user) && $config['radius_enable']) || _post('radius_enable')) {
        ORM::configure("mysql:host=$radius_host;dbname=$radius_name", null, 'radius');
        ORM::configure('username', $radius_user, 'radius');
        ORM::configure('password', $radius_pass, 'radius');
        ORM::configure('driver_options', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'), 'radius');
        ORM::configure('return_result_sets', true, 'radius');
    }
} catch (Exception $e) {
    $ui = new Smarty();
    $ui->setTemplateDir(['custom' => File::pathFixer('ui/ui_custom/'), 'default' => File::pathFixer('ui/ui/')]);
    $ui->assign('_url', APP_URL . '/index.php?_route=');
    $ui->setCompileDir(File::pathFixer('ui/compiled/'));
    $ui->setConfigDir(File::pathFixer('ui/conf/'));
    $ui->setCacheDir(File::pathFixer('ui/cache/'));
    $ui->assign("error_title", "PHPNuxBill Crash");
    $ui->assign("error_message", $e->getMessage());
    $ui->display('router-error.tpl');
    die();
}

function _notify($msg, $type = 'e')
{
    $_SESSION['ntype'] = $type;
    $_SESSION['notify'] = $msg;
}

$lan_file = File::pathFixer('system/lan/' . $config['language'] . '/common.lan.php');
require $lan_file;

$ui = new Smarty();

if (!empty($config['theme']) && $config['theme'] != 'default') {
    $_theme = APP_URL . '/ui/theme/' . $config['theme'];
    $ui->setTemplateDir(['custom' => File::pathFixer('ui/ui_custom/'), 'theme' => $_theme, 'default' => File::pathFixer('ui/ui/')]);
} else {
    $_theme = APP_URL . '/ui/ui';
    $ui->setTemplateDir(['custom' => File::pathFixer('ui/ui_custom/'), 'default' => File::pathFixer('ui/ui/')]);
}
$ui->assign('_theme', $_theme);
$ui->addTemplateDir(File::pathFixer('system/paymentgateway/ui/'), 'pg');
$ui->addTemplateDir(File::pathFixer('system/plugin/ui/'), 'plugin');
$ui->setCompileDir(File::pathFixer('ui/compiled/'));
$ui->setConfigDir(File::pathFixer('ui/conf/'));
$ui->setCacheDir(File::pathFixer('ui/cache/'));
$ui->assign('app_url', APP_URL);
$ui->assign('_domain', str_replace('www.', '', parse_url(APP_URL, PHP_URL_HOST)));
define('U', APP_URL . '/index.php?_route=');
$ui->assign('_url', APP_URL . '/index.php?_route=');
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

include "autoload/Hookers.php";

// notification message
if (file_exists(File::pathFixer("system/uploads/notifications.json"))) {
    $_notifmsg = json_decode(file_get_contents(File::pathFixer('system/uploads/notifications.json')), true);
}
$_notifmsg_default = json_decode(file_get_contents(File::pathFixer('system/uploads/notifications.default.json')), true);

//register all plugin
foreach (glob(File::pathFixer("system/plugin/*.php")) as $filename) {
    include $filename;
}


function _auth($login = true)
{
    if (isset($_SESSION['uid'])) {
        return true;
    } else {
        if ($login) {
            r2(U . 'login');
        } else {
            return false;
        }
    }
}

function _admin($login = true)
{
    if (isset($_SESSION['aid'])) {
        return true;
    } else {
        if ($login) {
            r2(U . 'login');
        } else {
            return false;
        }
    }
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

function Lang($key)
{
    global $_L, $lan_file;
    if (!empty($_L[$key])) {
        return $_L[$key];
    }
    $val = $key;
    $key = alphanumeric($key, " ");
    if (!empty($_L[$key])) {
        return $_L[$key];
    } else if (!empty($_L[str_replace(' ', '_', $key)])) {
        return $_L[str_replace(' ', '_', $key)];
    } else {
        $key = str_replace(' ', '_', $key);
        file_put_contents($lan_file, "$" . "_L['$key'] = '" . addslashes($val) . "';\n", FILE_APPEND);
        return $val;
    }
}

function alphanumeric($str, $tambahan = "")
{
    return preg_replace("/[^a-zA-Z0-9" . $tambahan . "]+/", "", $str);
}


function sendTelegram($txt)
{
    Message::sendTelegram($txt);
}

function sendSMS($phone, $txt)
{
    Message::sendSMS($phone, $txt);
}

function sendWhatsapp($phone, $txt)
{
    Message::sendWhatsapp($phone, $txt);
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
$ui->assign('_routes', $routes);
$handler = $routes[0];
if ($handler == '') {
    $handler = 'default';
}
try {

    $sys_render = File::pathFixer('system/controllers/' . $handler . '.php');
    if (file_exists($sys_render)) {
        $menus = array();
        // "name" => $name,
        // "admin" => $admin,
        // "position" => $position,
        // "function" => $function
        $ui->assign('_system_menu', $routes[0]);
        foreach ($menu_registered as $menu) {
            if ($menu['admin'] && _admin(false)) {
                $menus[$menu['position']] .= '<li' . (($routes[1] == $menu['function']) ? ' class="active"' : '') . '><a href="' . U . 'plugin/' . $menu['function'] . '">';
                if (!empty($menu['icon'])) {
                    $menus[$menu['position']] .= '<i class="' . $menu['icon'] . '"></i>';
                }
                $menus[$menu['position']] .= '<span class="text">' . $menu['name'] . '</span></a></li>';
            } else if (!$menu['admin'] && _auth(false)) {
                $menus[$menu['position']] .= '<li' . (($routes[1] == $menu['function']) ? ' class="active"' : '') . '><a href="' . U . 'plugin/' . $menu['function'] . '">';
                if (!empty($menu['icon'])) {
                    $menus[$menu['position']] .= '<i class="' . $menu['icon'] . '"></i>';
                }
                $menus[$menu['position']] .= '<span class="text">' . $menu['name'] . '</span></a></li>';
            }
        }
        foreach ($menus as $k => $v) {
            $ui->assign('_MENU_' . $k, $v);
        }
        unset($menus, $menu_registered);
        include($sys_render);
    } else {
        r2(U . 'dashboard', 'e', 'not found');
    }
} catch (Exception $e) {
    $ui->assign("error_title", "PHPNuxBill Crash");
    $ui->assign("error_message", $e->getMessage() . '<br><pre>' . $e->getTraceAsString() . '</pre>');
    $ui->display('router-error.tpl');
    die();
}
