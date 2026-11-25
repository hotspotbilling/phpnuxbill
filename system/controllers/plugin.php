<?php
/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

// plugin/<function> runs a function a plugin defines, one that needs no arguments, and one the plugin has declared:
// a menu function, or a route (register_route() / "routes" in plugin.json, with who may call it). It used to run any
// PHP function by name, built-in ones included (plugin/phpinfo ...).
//
// plugin_routes_mode (Settings > General Settings > Security): 'warn' (the default) still runs an undeclared plugin function and logs it once
// a day; 'enforce' refuses it. See PLUGINS.md.
global $PLUGIN_PATH, $config, $_app_stage, $menu_registered, $admin;
$pluginFunction = (string) ($routes[1] ?? '');
$pluginRunnable = false;
$pluginRefusal = 'Function not found';
if ($pluginFunction !== '' && function_exists($pluginFunction)) {
    $reflection = new ReflectionFunction($pluginFunction);
    $definedIn = $reflection->getFileName(); // false for a built-in function
    $pluginRoot = realpath($PLUGIN_PATH);
    $pluginRunnable = $definedIn && $pluginRoot
        && strpos((string) realpath($definedIn), $pluginRoot . DIRECTORY_SEPARATOR) === 0
        && $reflection->getNumberOfRequiredParameters() === 0;
}
if ($pluginRunnable) {
    $routeAccess = plugin_route_access($pluginFunction);
    if ($routeAccess === null) {
        if (($config['plugin_routes_mode'] ?? 'warn') === 'enforce') {
            $pluginRunnable = false;
            if ($_app_stage != 'Live') {
                $pluginRefusal = "Function not found: '$pluginFunction' is not a declared plugin route. Declare it with register_route('$pluginFunction', 'public|admin|customer') or in the plugin.json \"routes\" list (PLUGINS.md).";
            }
        } else {
            plugin_route_warn($pluginFunction);
        }
    } elseif ($routeAccess === 'admin') {
        _admin();
    } elseif ($routeAccess === 'customer') {
        _auth();
    } elseif ($routeAccess === 'menu') {
        // A menu function is as open as the menu entries that name it: their audience (admin or customer) must be signed in, and an
        // entry limited to some roles ($auth) is for those roles only. Hiding the entry from the sidebar is not access control.
        $menuFn = strtolower($pluginFunction);
        $menuOk = false;
        $menuAdmin = false;
        $menuCustomer = false;
        $adminOk = false;
        $customerOk = false;
        foreach ((array) $menu_registered as $menuEntry) {
            if (strtolower((string) strtok((string) ($menuEntry['function'] ?? ''), '/')) !== $menuFn) {
                continue;
            }
            if (!empty($menuEntry['admin'])) {
                $menuAdmin = true;
                if (_admin(false) && (empty($menuEntry['auth']) || in_array($admin['user_type'] ?? '', (array) $menuEntry['auth'], true))) {
                    $menuOk = true;
                    $adminOk = true;
                }
            } else {
                $menuCustomer = true;
                if (_auth(false)) {
                    $menuOk = true;
                    $customerOk = true;
                }
            }
        }
        if (!$menuOk) {
            if (($menuAdmin && !_admin(false)) || ($menuCustomer && !$menuAdmin && !_auth(false))) {
                r2(getUrl('login')); // not signed in
            }
            r2(getUrl('dashboard'), 'e', Lang::T('You do not have permission to access this page'));
        }
        // the audience that was allowed is the one signed in for: a customer's grant never stands in for an admin entry whose role list
        // refused this admin (both can be signed in at once)
        if ($adminOk) {
            _admin();
        } elseif ($customerOk) {
            _auth();
        }
    }
    // 'public': the function does its own checks
}
if ($pluginRunnable) {
    call_user_func($pluginFunction);
} else {
    r2(getUrl('dashboard'), 'e', $pluginRefusal);
}
