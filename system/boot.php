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
$ui->assign('_kolaps', (!empty($_COOKIE['kolaps']) && $_COOKIE['kolaps'] === 'true'));
// Theme system — Phase 4
$themeMeta = [];
$themeChain = []; // ordered list of theme dirs (child first, then parent(s), then default)
$activeTheme = !empty($config['theme']) && $config['theme'] != 'default' ? $config['theme'] : null;

if ($activeTheme) {
    $themeDir = File::pathFixer($UI_PATH . '/themes/' . $activeTheme);
    $themeMetaFile = $themeDir . '/theme.json';
    if (is_file($themeMetaFile)) {
        $themeMeta = json_decode(file_get_contents($themeMetaFile), true);
        if (!is_array($themeMeta)) {
            $themeMeta = [];
        }
    }
    $themeChain[] = $themeDir;

    // Resolve parent theme chain
    $parent = $themeMeta['parent'] ?? null;
    $seen = [$activeTheme => true];
    while ($parent && !isset($seen[$parent])) {
        $parentDir = File::pathFixer($UI_PATH . '/themes/' . $parent);
        if (is_dir($parentDir)) {
            $themeChain[] = $parentDir;
            $seen[$parent] = true;
            $parentMetaFile = $parentDir . '/theme.json';
            if (is_file($parentMetaFile)) {
                $pm = json_decode(file_get_contents($parentMetaFile), true);
                $parent = $pm['parent'] ?? null;
            } else {
                $parent = null;
            }
        } else {
            $parent = null;
        }
    }
}

$templateDirs = [
    'custom' => File::pathFixer($UI_PATH . '/ui_custom/'),
];
foreach ($themeChain as $tDir) {
    $templateDirs['theme_' . basename($tDir)] = $tDir;
}
$templateDirs['default'] = File::pathFixer($UI_PATH . '/ui/');

if ($activeTheme) {
    $_theme = APP_URL . '/' . $UI_PATH . '/themes/' . $activeTheme;
    $ui->setTemplateDir($templateDirs);
} else {
    $_theme = APP_URL . '/' . $UI_PATH . '/ui';
    $ui->setTemplateDir([
        'custom' => File::pathFixer($UI_PATH . '/ui_custom/'),
        'default' => File::pathFixer($UI_PATH . '/ui/')
    ]);
}
$ui->assign('_theme', $_theme);
$ui->assign('_theme_meta', $themeMeta);

// Load theme functions.php if present (child first, then parents)
if ($activeTheme) {
    foreach ($themeChain as $tDir) {
        $functionsFile = $tDir . '/functions.php';
        if (is_file($functionsFile)) {
            require_once $functionsFile;
        }
    }
}
$ui->addTemplateDir($PAYMENTGATEWAY_PATH . File::pathFixer('/ui/'), 'pg');
$ui->addTemplateDir($PLUGIN_PATH . File::pathFixer('/ui/'), 'plugin');
foreach (PluginLoader::$templateDirs as $pluginTplDir) {
    $ui->addTemplateDir($pluginTplDir); // folder plugins: templates resolvable by plain name
}
// folder-style payment gateways: plain template names, plus the "pg:name.tpl" resource for both layouts
foreach (GatewayLoader::templateDirs() as $gatewayTplDir) {
    $ui->addTemplateDir($gatewayTplDir);
}
$ui->registerResource('pg', new SmartyPgResource());
$ui->setCompileDir(File::pathFixer($UI_PATH . '/compiled/'));
$ui->setConfigDir(File::pathFixer($UI_PATH . '/conf/'));
$ui->setCacheDir(File::pathFixer($UI_PATH . '/cache/'));
$ui->assign('app_url', APP_URL);
$ui->assign('_domain', str_replace('www.', '', parse_url(APP_URL, PHP_URL_HOST)));
$ui->assign('_url', APP_URL . '/?_route=');
$ui->assign('_path', __DIR__);
$ui->assign('_c', $config);
$ui->assign('user_language', $_SESSION['user_language']);
$ui->assign('UPLOAD_PATH', str_replace($root_path, '',  $UPLOAD_PATH));
$ui->assign('CACHE_PATH', str_replace($root_path, '',  $CACHE_PATH));
$ui->assign('PAGES_PATH', str_replace($root_path, '',  $PAGES_PATH));
$ui->assign('_system_menu', 'dashboard');
$ui->assign('csrf_token', Csrf::generateAndStoreToken());

// Core menu: Marketplace — direct single item navigation to Marketplace hub
register_menu('Marketplace', true, 'marketplace', 'AFTER_LOGS',
    '<svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0"/><path d="M3 7v1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1h-18l2 -4h14l2 4"/><path d="M5 21l0 -10.15"/><path d="M19 21l0 -10.15"/><path d="M9 21v-4a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v4"/></svg>',
    '', 'success', []);

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

if (!isset($_GET['_route'])) {
    $req = ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $len = strlen(ltrim(parse_url(APP_URL, PHP_URL_PATH), '/'));
    if ($len > 0) {
        $req = ltrim(substr($req, $len), '/');
    }
} else {
    // Routing Engine
    $req = _get('_route');
}

$routes = explode('/', $req);
$ui->assign('_routes', $routes);
$handler = $routes[0];
if ($handler == '') {
    $handler = 'default';
}
try {
    if (!empty($_GET['uid'])) {
        $_COOKIE['uid'] = $_GET['uid'];
    }
    $admin = Admin::_info();
    $sys_render = $root_path . File::pathFixer('system/controllers/' . $handler . '.php');
    if (file_exists($sys_render)) {
        $menus = array();
        // "name" => $name,
        // "admin" => $admin,
        // "position" => $position,
        // "function" => $function
        // "parent" => parent function name when this item is a submenu (null = top-level)
        $ui->assign('_system_menu', $routes[0]);
        $routePath = implode('/', array_filter($routes));
        $topLevel = array();
        $children = array();
        foreach ($menu_registered as $menu) {
            $isAdmin = (bool) $menu['admin'];
            $allowed = $isAdmin ? _admin(false) : _auth(false);
            if (!$allowed) {
                continue;
            }
            if (count($menu['auth']) > 0 && !in_array($admin['user_type'], $menu['auth'])) {
                continue;
            }
            $menu['_active'] = ($routePath == $menu['function']) || ('plugin/' . $menu['function'] == $routePath) || (strpos($routePath, 'plugin/' . $menu['function'] . '/') === 0) || ($routes[1] == $menu['function']) || ($routes[0] == $menu['function']) || (
                in_array($menu['function'], ['hotspot_merchantAdmin']) &&
                stripos($routes[1], $menu['function']) === 0
            );
            $pos = $menu['position'];
            if (!empty($menu['parent'])) {
                $children[$pos][$menu['parent']][] = $menu;
            } else {
                $topLevel[$pos][] = $menu;
            }
        }
        foreach ($topLevel as $pos => $items) {
            $html = '';
            foreach ($items as $item) {
                $kids = isset($children[$pos][$item['function']]) ? $children[$pos][$item['function']] : array();
                $isActive = !empty($item['_active']);
                foreach ($kids as $kid) {
                    if (!empty($kid['_active'])) {
                        $isActive = true;
                        break;
                    }
                }
                $classAttr = '';
                $liClasses = [];
                if ($isActive) {
                    $liClasses[] = 'active';
                }
                if (!empty($kids)) {
                    $liClasses[] = 'treeview';
                }
                if ($isActive && !empty($kids)) {
                    $liClasses[] = 'menu-open';
                }
                if (!empty($liClasses)) {
                    $classAttr = ' class="' . implode(' ', $liClasses) . '"';
                }
                $html .= '<li' . $classAttr . '>';
                if (!empty($kids)) {
                    $html .= '<a href="#">';
                } else {
                    // Hierarchical routes (function contains '/') route via the plugin system;
                    // core controllers (system/controllers/<function>.php) route directly;
                    // plugin functions route via ?_route=plugin/<function>
                    if (strpos($item['function'], '/') !== false) {
                        $menuUrl = getUrl('plugin/' . $item['function']);
                    } else {
                        $ctrlFile = $root_path . File::pathFixer('system/controllers/' . $item['function'] . '.php');
                        $menuUrl = is_file($ctrlFile) ? getUrl($item['function']) : getUrl('plugin/' . $item['function']);
                    }
                    $html .= '<a href="' . $menuUrl . '">';
                }
                if (!empty($item['icon'])) {
                    if (str_starts_with($item['icon'], '<svg')) {
                        $html .= $item['icon'];
                    } else {
                        $html .= '<i class="' . $item['icon'] . '"></i>';
                    }
                }
                $html .= '<span class="text">' . $item['name'] . '</span>';
                if (!empty($item['label'])) {
                    $html .= '<span class="pull-right-container"><small class="label pull-right bg-' . $item['color'] . '">' . $item['label'] . '</small></span>';
                }
                if (!empty($kids)) {
                    $html .= '<span class="pull-right-container"><i class="fa fa-angle-down pull-right"></i></span>';
                }
                $html .= '</a>';
                if (!empty($kids)) {
                    $html .= '<ul class="treeview-menu">';
                    foreach ($kids as $kid) {
                        $kidActive = !empty($kid['_active']) ? ' class="active"' : '';
                        if (strpos($kid['function'], '/') !== false) {
                            $kidMenuUrl = getUrl('plugin/' . $kid['function']);
                        } else {
                            $kidCtrlFile = $root_path . File::pathFixer('system/controllers/' . $kid['function'] . '.php');
                            $kidMenuUrl = is_file($kidCtrlFile) ? getUrl($kid['function']) : getUrl('plugin/' . $kid['function']);
                        }
                        $html .= '<li' . $kidActive . '><a href="' . $kidMenuUrl . '">';
                        if (!empty($kid['icon'])) {
                            if (str_starts_with($kid['icon'], '<svg')) {
                                $html .= $kid['icon'];
                            } else {
                                $html .= '<i class="' . $kid['icon'] . '"></i>';
                            }
                        }
                        if (!empty($kid['label'])) {
                            $html .= '<span class="pull-right-container"><small class="label pull-right bg-' . $kid['color'] . '">' . $kid['label'] . '</small></span>';
                        }
                        $html .= '<span class="text">' . $kid['name'] . '</span></a></li>';
                    }
                    $html .= '</ul>';
                }
                $html .= '</li>';
            }
            $menus[$pos] = $html;
        }
        foreach ($menus as $k => $v) {
            $ui->assign('_MENU_' . $k, $v);
        }
        unset($menus, $menu_registered, $topLevel, $children);
        include($sys_render);
    } else {
        if( empty($_SERVER["HTTP_SEC_FETCH_DEST"]) || $_SERVER["HTTP_SEC_FETCH_DEST"] != 'document' ){
            // header 404
            header("HTTP/1.0 404 Not Found");
            header("Content-Type: text/html; charset=utf-8");
            echo "404 Not Found";
            die();
        }else{
            r2(getUrl('login'));
        }
    }
} catch (Throwable $e) {
    Message::sendTelegram(
        "Sistem Error.\n" .
            $e->getMessage() . "\n" .
            $e->getTraceAsString()
    );
    if (empty($_SESSION['aid'])) {
        $ui->display('customer/error.tpl');
        die();
    }
    $ui->assign("error_message", $e->getMessage() . '<br><pre>' . $e->getTraceAsString() . '</pre>');
    $ui->assign("error_title", "PHPNuxBill Crash");
    $ui->display('admin/error.tpl');
    die();
} catch (Exception $e) {
    Message::sendTelegram(
        "Sistem Error.\n" .
            $e->getMessage() . "\n" .
            $e->getTraceAsString()
    );
    if (empty($_SESSION['aid'])) {
        $ui->display('customer/error.tpl');
        die();
    }
    $ui->assign("error_message", $e->getMessage() . '<br><pre>' . $e->getTraceAsString() . '</pre>');
    $ui->assign("error_title", "PHPNuxBill Crash");
    $ui->display('admin/error.tpl');
    die();
}
