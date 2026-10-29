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
global $PLUGIN_PATH, $config, $_app_stage;
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
    }
    // 'public' and 'menu': the function does its own checks
}
if ($pluginRunnable) {
    call_user_func($pluginFunction);
} else {
    r2(getUrl('dashboard'), 'e', $pluginRefusal);
}
