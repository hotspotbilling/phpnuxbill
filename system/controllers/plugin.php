<?php
/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

// plugin/<function> runs a function a plugin defines, one that needs no arguments. It used to run any PHP function by
// name, built-in ones included (plugin/phpinfo ...).
global $PLUGIN_PATH;
$pluginFunction = (string) ($routes[1] ?? '');
$pluginRunnable = false;
if ($pluginFunction !== '' && function_exists($pluginFunction)) {
    $reflection = new ReflectionFunction($pluginFunction);
    $definedIn = $reflection->getFileName(); // false for a built-in function
    $pluginRoot = realpath($PLUGIN_PATH);
    $pluginRunnable = $definedIn && $pluginRoot
        && strpos((string) realpath($definedIn), $pluginRoot . DIRECTORY_SEPARATOR) === 0
        && $reflection->getNumberOfRequiredParameters() === 0;
}
if ($pluginRunnable) {
    call_user_func($pluginFunction);
} else {
    r2(getUrl('dashboard'), 'e', 'Function not found');
}