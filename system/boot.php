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
// a theme that needs a licence falls back to the default look until a valid key is saved
if ($activeTheme && Extensions::licenseMissing('theme', $activeTheme, Extensions::manifest('theme', $activeTheme))) {
    $activeTheme = null;
}

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

    // Resolve parent theme chain. A parent from theme.json is only trusted when it is a plain slug string.
    $themeSlugOk = function ($v) {
        return is_string($v) && preg_match('/^[A-Za-z0-9_-]+$/', $v) === 1 ? $v : null;
    };
    $parent = $themeSlugOk($themeMeta['parent'] ?? null);
    $seen = [$activeTheme => true];
    while ($parent && !isset($seen[$parent])) {
        $parentDir = File::pathFixer($UI_PATH . '/themes/' . $parent);
        if (is_dir($parentDir)) {
            $themeChain[] = $parentDir;
            $seen[$parent] = true;
            $parentMetaFile = $parentDir . '/theme.json';
            if (is_file($parentMetaFile)) {
                $pm = json_decode(file_get_contents($parentMetaFile), true);
                $parent = is_array($pm) ? $themeSlugOk($pm['parent'] ?? null) : null;
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

// A theme restyles the customer portal chrome (header, menus, footer) by shipping styles/customer.css: it loads after
// the core customer styles, parent theme first so the child wins. The file time busts the browser cache on a change.
$themeCustomerCss = [];
if ($activeTheme) {
    foreach (array_reverse($themeChain) as $tDir) {
        $cssFile = $tDir . '/styles/customer.css';
        if (is_file($cssFile)) {
            $themeCustomerCss[] = APP_URL . '/' . $UI_PATH . '/themes/' . basename($tDir) . '/styles/customer.css?' . filemtime($cssFile);
        }
    }
}
$ui->assign('_theme_customer_css', $themeCustomerCss);

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
    '', 'success', ['SuperAdmin', 'Admin']);

/**
 * Sidebar icon markup that looks like the built-in items. Built-in entries use an inline <svg class="icon">;
 * plugins pass a Font Awesome / ion class string ("fa fa-cube"), their own <svg> or an <img>. Everything is
 * given the "icon" class so it is sized and spaced the same way. Top-level items without an icon get a
 * generic puzzle icon so their labels line up with the rest of the menu.
 */
function _menu_icon($icon, $placeholder = false)
{
    $icon = trim((string) $icon);
    if ($icon === '') {
        return $placeholder
            ? '<svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7h3a1 1 0 0 0 1 -1v-1a2 2 0 0 1 4 0v1a1 1 0 0 0 1 1h3a1 1 0 0 1 1 1v3a1 1 0 0 0 1 1h1a2 2 0 0 1 0 4h-1a1 1 0 0 0 -1 1v3a1 1 0 0 1 -1 1h-3a1 1 0 0 1 -1 -1v-1a2 2 0 0 0 -4 0v1a1 1 0 0 1 -1 1h-3a1 1 0 0 1 -1 -1v-3a1 1 0 0 1 1 -1h1a2 2 0 0 0 0 -4h-1a1 1 0 0 1 -1 -1v-3a1 1 0 0 1 1 -1"/></svg>'
            : '';
    }
    if (stripos($icon, '<svg') === 0 || stripos($icon, '<img') === 0) {
        // add "icon" to the real class attribute (found by walking the attributes, so "class" inside another
        // attribute's quoted value is ignored) or create one; never duplicate it
        return preg_replace_callback('/^<(svg|img)((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>?/i', function ($m) {
            $attrs = $m[2];
            $rest = substr($m[0], strlen($m[1]) + 1 + strlen($attrs));
            $found = false;
            if (preg_match_all('/\s+([^\s"\'=\/>]+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'>]+)))?/', $attrs, $all, PREG_OFFSET_CAPTURE)) {
                foreach ($all[1] as $i => $name) {
                    if (strcasecmp($name[0], 'class') !== 0) {
                        continue;
                    }
                    $found = true;
                    foreach ([2, 3, 4] as $g) {
                        $v = $all[$g][$i];
                        if ($v[1] < 0) {
                            continue;
                        }
                        if (!preg_match('/(^|\s)icon(\s|$)/', $v[0])) {
                            $new = trim('icon ' . $v[0]);
                            if ($g === 4) {
                                $new = '"' . $new . '"'; // unquoted value needs quotes once it holds two classes
                            }
                            $attrs = substr_replace($attrs, $new, $v[1], strlen($v[0]));
                        }
                        break 2;
                    }
                    // valueless class attribute
                    $attrs = substr_replace($attrs, ' class="icon"', $name[1] - 1, 0);
                    $attrs = preg_replace('/(\sclass="icon")\s+class(?=[\s\/>]|$)/i', '$1', $attrs, 1);
                    break;
                }
            }
            if (!$found) {
                $attrs = ' class="icon"' . $attrs;
            }
            return '<' . $m[1] . $attrs . $rest;
        }, $icon, 1);
    }
    return '<i class="icon ' . htmlspecialchars($icon, ENT_QUOTES) . '"></i>';
}

/**
 * Assign the sidebar slots ($_MENU_AFTER_DASHBOARD ...) for one audience, 'admin' or 'customer'. Slots the other
 * audience fills are cleared, so an admin plugin menu can never show in the customer portal (or the reverse) just
 * because both sessions are open in the same browser.
 */
function _menu_publish($audience)
{
    global $ui;
    $all = isset($GLOBALS['_menus_by_audience']) ? $GLOBALS['_menus_by_audience'] : [];
    $slots = [];
    foreach ($all as $byPosition) {
        foreach ($byPosition as $pos => $html) {
            $slots[$pos] = '';
        }
    }
    foreach (isset($all[$audience]) ? $all[$audience] : [] as $pos => $html) {
        $slots[$pos] = $html;
    }
    foreach ($slots as $pos => $html) {
        $ui->assign('_MENU_' . $pos, $html);
    }
}

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
            $aud = $isAdmin ? 'admin' : 'customer'; // admin and customer pages share slot names such as AFTER_DASHBOARD
            if (!empty($menu['parent'])) {
                $children[$aud][$pos][$menu['parent']][] = $menu;
            } else {
                $topLevel[$aud][$pos][] = $menu;
            }
        }
        foreach ($topLevel as $aud => $byPosition) {
        foreach ($byPosition as $pos => $items) {
            $html = '';
            foreach ($items as $item) {
                $kids = isset($children[$aud][$pos][$item['function']]) ? $children[$aud][$pos][$item['function']] : array();
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
                    $html .= '<a href="#" title="' . htmlspecialchars(strip_tags($item['name']), ENT_QUOTES) . '">';
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
                    $html .= '<a href="' . $menuUrl . '" title="' . htmlspecialchars(strip_tags($item['name']), ENT_QUOTES) . '">';
                }
                $html .= _menu_icon($item['icon'] ?? '', true) . ' '; // the space matches the built-in markup: the customer sidebar is inline, not flex
                $html .= '<span class="text">' . $item['name'] . '</span>';
                if (!empty($item['label']) && is_scalar($item['label'])) { // an array here (wrong argument order) printed "Array"
                    $html .= '<span class="pull-right-container"><small class="label pull-right bg-' . $item['color'] . '">' . $item['label'] . '</small></span>';
                }
                if (!empty($kids)) {
                    // both chevrons are emitted; each theme's CSS shows the one it uses (default: fa-angle-down; a theme may use the svg)
                    $html .= '<span class="pull-right-container"><i class="fa fa-angle-down pull-right"></i>'
                        . '<svg class="treeview-chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6"/></svg></span>';
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
                        $html .= '<li' . $kidActive . '><a href="' . $kidMenuUrl . '" title="' . htmlspecialchars(strip_tags($kid['name']), ENT_QUOTES) . '">';
                        $html .= _menu_icon($kid['icon'] ?? '') . ' ';
                        $html .= '<span class="text">' . $kid['name'] . '</span>';
                        if (!empty($kid['label']) && is_scalar($kid['label'])) { // after the name, so the badge sits at the end of the row
                            $html .= '<span class="pull-right-container"><small class="label pull-right bg-' . $kid['color'] . '">' . $kid['label'] . '</small></span>';
                        }
                        $html .= '</a></li>';
                    }
                    $html .= '</ul>';
                }
                $html .= '</li>';
            }
            $menus[$aud][$pos] = $html;
        }
        }
        // Every page gets only its own audience's menus: the controller's _admin() / _auth() call publishes them
        // (see _menu_publish). Pages that call neither get the admin menus when an admin is signed in, else the customer ones.
        $GLOBALS['_menus_by_audience'] = $menus;
        _menu_publish(Admin::getID() ? 'admin' : 'customer');
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
