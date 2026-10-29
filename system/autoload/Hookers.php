<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

$menu_registered = array();

/**
 * Register for global menu
 * @param string name Name of the menu
 * @param bool admin true if for admin and false for customer
 * @param string function function to run after menu clicks
 * @param string position position of menu, use AFTER_ for root menu |
 * Admin/Sales menu: AFTER_DASHBOARD, CUSTOMERS, PREPAID, SERVICES, REPORTS, VOUCHER, AFTER_ORDER, NETWORK, SETTINGS, AFTER_PAYMENTGATEWAY
 * | Customer menu: AFTER_DASHBOARD, ORDER, HISTORY, ACCOUNTS
 * @param string icon from ion icon, ion-person, only for AFTER_
 * @param string label for showing label or number of notification or update
 * @param string color Label color
 * @param string auth authorization ['SuperAdmin', 'Admin', 'Report', 'Agent', 'Sales'] will only show in this user, empty array for all users
 */
function register_menu($name, $admin, $function, $position, $icon = '', $label = '', $color = 'success', $auth = [])
{
    global $menu_registered;
    $menu_registered[] = [
        "name" => $name,
        "admin" => $admin,
        "position" => $position,
        "icon" => $icon,
        "function" => $function,
        "label" => $label,
        "color" => $color,
        "auth" => $auth
    ];
}

/**
 * Register a submenu item under a parent menu function.
 * Renders as a nested <ul class="treeview-menu"> inside the parent in boot.php.
 *
 * @param string $parentFunction The function name of the parent register_menu() item
 * @param string $name           Name of the menu
 * @param bool   $admin          true if for admin and false for customer
 * @param string $function       function to run after menu clicks
 * @param string $position       position of menu (must match parent's position)
 * @param string $icon           icon class
 * @param string $label          badge label
 * @param string $color          badge color
 * @param array  $auth           authorization list
 */
function register_submenu($parentFunction, $name, $admin, $function, $position, $icon = '', $label = '', $color = 'success', $auth = [])
{
    global $menu_registered;
    $menu_registered[] = [
        "name" => $name,
        "admin" => $admin,
        "position" => $position,
        "icon" => $icon,
        "function" => $function,
        "label" => $label,
        "color" => $color,
        "auth" => $auth,
        "parent" => $parentFunction,
    ];
}

$route_registered = array();

/**
 * Let an extension run its own licence rules. The developer decides how a key looks, where it is checked and how it is
 * stored; Marketplace shows the License button and the result. Both callbacks answer true / false or
 * ['state' => 'valid'|'invalid'|'unchecked'|'none', 'message' => '...']:
 *   register_license('acme_sms', 'acme_sms_license_status', 'acme_sms_license_save');
 * $type is 'plugin' (default), 'gateway', 'device' or 'theme'.
 */
function register_license($slug, $statusFunction, $saveFunction, $type = 'plugin')
{
    Extensions::registerLicense($type, $slug, $statusFunction, $saveFunction);
}

/**
 * Does the extension's licence unlock a feature (a name from the "features" its status callback returned, or "licensed" for
 * any valid licence)? Use it to guard pages and to decide which menus to register:
 *   if (license_has('acme_sms', 'merchants')) { register_menu(...); }
 */
function license_has($slug, $feature, $type = 'plugin')
{
    return Extensions::licenseHas($type, $slug, $feature);
}

/**
 * Declare a function a plugin page, webhook or API entry point: ?_route=plugin/<function> may run it.
 * Menu functions (register_menu / register_submenu, or "menus" in plugin.json) are allowed without this.
 *
 * @param string $function PHP function name, no arguments required
 * @param string $access   who may call it, checked by the core before the function runs:
 *                         'admin'    a signed-in staff member (_admin())
 *                         'customer' a signed-in customer (_auth())
 *                         'public'   anyone: webhooks, payment callbacks, a plugin's own API or login. The function
 *                                    must do its own checks (a signature, a token, its own session).
 *                         The default is 'admin', the safe choice when in doubt.
 * Folder plugins can declare the same in plugin.json: "routes": [{"function": "x_webhook", "access": "public"}].
 */
function register_route($function, $access = 'admin')
{
    global $route_registered;
    $function = (string) $function;
    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $function)) {
        return;
    }
    // PHP function names are case-insensitive (plugin/ADMIN_ACTION runs admin_action()), so the registry is too
    $function = strtolower($function);
    $route_registered[$function] = in_array($access, ['admin', 'customer', 'public'], true) ? $access : 'admin';
}

/**
 * How a function may be reached through plugin/<function>.
 * @return string|null 'admin' | 'customer' | 'public' for a declared route, 'menu' for a menu function (it checks the
 *                     sign-in itself, as it can serve several audiences), null when nothing declares it
 */
function plugin_route_access($function)
{
    global $route_registered, $menu_registered;
    $function = strtolower((string) $function); // see register_route(): any spelling of the name is the same function
    if (isset($route_registered[$function])) {
        return $route_registered[$function];
    }
    foreach ((array) $menu_registered as $menu) {
        // a menu function may carry a section: "myplugin/settings" runs myplugin()
        if (isset($menu['function']) && strtolower((string) strtok((string) $menu['function'], '/')) === $function) {
            return 'menu';
        }
    }
    return null;
}

/**
 * plugin/<function> ran a function nothing declares (see register_route). Only warns, at most once a day per function,
 * so a log does not fill up; the call still goes through unless plugin_routes_mode is 'enforce'.
 */
function plugin_route_warn($function)
{
    global $CACHE_PATH;
    $function = strtolower((string) $function);
    $file = rtrim((string) $CACHE_PATH, '/\\') . '/plugin_routes_warned.json';
    $seen = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
    if (isset($seen[$function]) && time() - (int) $seen[$function] < 86400) {
        return;
    }
    $seen[$function] = time();
    @file_put_contents($file, json_encode($seen), LOCK_EX);
    _log("Plugin route '$function' is not declared. It still works, but a later release will refuse it: declare it with register_route('$function', 'public|admin|customer') or in the plugin.json \"routes\" list (see PLUGINS.md).", 'Plugin');
}

$hook_registered = array();

function register_hook($action, $function){
    global $hook_registered;
    $hook_registered[] = [
        'action' => $action,
        'function' => $function
    ];
}

function run_hook($action, $args = []){
    global $hook_registered;
    foreach($hook_registered as $hook){
        if($hook['action'] == $action){
            if(function_exists($hook['function'])){
                return call_user_func($hook['function'], $args);
            }
        }
    }
    return false;
}
