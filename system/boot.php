<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)

 **/

try {
    require_once 'init.php';
} catch (Throwable $e) {
    die($e->getMessage() . '<br><pre>' . $e->getTraceAsString() . '</pre>');
} catch (Exception $e) {
    die($e->getMessage() . '<br><pre>' . $e->getTraceAsString() . '</pre>');
}

function _notify($msg, $type = 'e')
{
    $_SESSION['ntype'] = $type;
    $_SESSION['notify'] = $msg;
}

$ui = new Smarty();
$ui->assign('_kolaps', $_COOKIE['kolaps']);
if (!empty($config['theme']) && $config['theme'] != 'default') {
    $_theme = APP_URL . '/' . $UI_PATH . '/themes/' . $config['theme'];
    $ui->setTemplateDir([
        'custom' => File::pathFixer($UI_PATH . '/ui_custom/'),
        'theme' => File::pathFixer($UI_PATH . '/themes/' . $config['theme']),
        'default' => File::pathFixer($UI_PATH . '/ui/')
    ]);
} else {
    $_theme = APP_URL . '/' . $UI_PATH . '/ui';
    $ui->setTemplateDir([
        'custom' => File::pathFixer($UI_PATH . '/ui_custom/'),
        'default' => File::pathFixer($UI_PATH . '/ui/')
    ]);
}
$ui->assign('_theme', $_theme);
$ui->addTemplateDir($PAYMENTGATEWAY_PATH . File::pathFixer('/ui/'), 'pg');
$ui->addTemplateDir($PLUGIN_PATH . File::pathFixer('/ui/'), 'plugin');
$ui->setCompileDir(File::pathFixer($UI_PATH . '/compiled/'));
$ui->setConfigDir(File::pathFixer($UI_PATH . '/conf/'));
$ui->setCacheDir(File::pathFixer($UI_PATH . '/cache/'));
$ui->assign('app_url', APP_URL);
$ui->assign('_domain', str_replace('www.', '', parse_url(APP_URL, PHP_URL_HOST)));
$ui->assign('_url', APP_URL . '/index.php?_route=');
$ui->assign('_path', __DIR__);
$ui->assign('_c', $config);
$ui->assign('UPLOAD_PATH', str_replace($root_path, '',  $UPLOAD_PATH));
$ui->assign('CACHE_PATH', str_replace($root_path, '',  $CACHE_PATH));
$ui->assign('PAGES_PATH', str_replace($root_path, '',  $PAGES_PATH));
$ui->assign('_system_menu', 'dashboard');

function _msglog($type, $msg)
{
    $_SESSION['ntype'] = $type;
    $_SESSION['notify'] = $msg;
}

if (isset($_SESSION['notify'])) {
    $notify = $_SESSION['notify'];
    $ntype = $_SESSION['ntype'];
    $ui->assign('notify', $notify);
    $ui->assign('notify_t', $ntype);
    unset($_SESSION['notify']);
    unset($_SESSION['ntype']);
}

// Routing Engine
$req = _get('_route');
$routes = explode('/', $req);
$ui->assign('_routes', $routes);
$handler = $routes[0];
if ($handler == '') {
    $handler = 'default';
}
$admin = Admin::_info();
try {
    $sys_render = $root_path . File::pathFixer('system/controllers/' . $handler . '.php');
    if (file_exists($sys_render)) {
        $menus = array();
        // "name" => $name,
        // "admin" => $admin,
        // "position" => $position,
        // "function" => $function
        $ui->assign('_system_menu', $routes[0]);
        foreach ($menu_registered as $menu) {
            if ($menu['admin'] && _admin(false)) {
                if (count($menu['auth']) == 0 || in_array($admin['user_type'], $menu['auth'])) {
                    $menus[$menu['position']] .= '<li' . (($routes[1] == $menu['function']) ? ' class="active"' : '') . '><a href="' . U . 'plugin/' . $menu['function'] . '">';
                    if (!empty($menu['icon'])) {
                        $menus[$menu['position']] .= '<i class="' . $menu['icon'] . '"></i>';
                    }
                    if (!empty($menu['label'])) {
                        $menus[$menu['position']] .= '<span class="pull-right-container">';
                        $menus[$menu['position']] .= '<small class="label pull-right bg-' . $menu['color'] . '">' . $menu['label'] . '</small></span>';
                    }
                    $menus[$menu['position']] .= '<span class="text">' . $menu['name'] . '</span></a></li>';
                }
            } else if (!$menu['admin'] && _auth(false)) {
                $menus[$menu['position']] .= '<li' . (($routes[1] == $menu['function']) ? ' class="active"' : '') . '><a href="' . U . 'plugin/' . $menu['function'] . '">';
                if (!empty($menu['icon'])) {
                    $menus[$menu['position']] .= '<i class="' . $menu['icon'] . '"></i>';
                }
                if (!empty($menu['label'])) {
                    $menus[$menu['position']] .= '<span class="pull-right-container">';
                    $menus[$menu['position']] .= '<small class="label pull-right bg-' . $menu['color'] . '">' . $menu['label'] . '</small></span>';
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
} catch (Throwable $e) {
    Message::sendTelegram(
        "Sistem Error.\n" .
            $e->getMessage() . "\n" .
            $e->getTraceAsString()
    );
    if (!Admin::getID()) {
        r2(U . 'home', 'e', $e->getMessage());
    }
    $ui->assign("error_message", $e->getMessage() . '<br><pre>' . $e->getTraceAsString() . '</pre>');
    $ui->assign("error_title", "PHPNuxBill Crash");
    $ui->display('router-error.tpl');
    die();
} catch (Exception $e) {
    Message::sendTelegram(
        "Sistem Error.\n" .
            $e->getMessage() . "\n" .
            $e->getTraceAsString()
    );
    if (!Admin::getID()) {
        r2(U . 'home', 'e', $e->getMessage());
    }
    $ui->assign("error_message", $e->getMessage() . '<br><pre>' . $e->getTraceAsString() . '</pre>');
    $ui->assign("error_title", "PHPNuxBill Crash");
    $ui->display('router-error.tpl');
    die();
}
