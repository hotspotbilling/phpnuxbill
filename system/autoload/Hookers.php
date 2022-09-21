<?php



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
 */
function register_menu($name, $admin, $function, $position, $icon = '')
{
    global $menu_registered;
    $menu_registered[] = [
        "name" => $name,
        "admin" => $admin,
        "position" => $position,
        "icon" => $icon,
        "function" => $function
    ];
}

$hook_registered = array();

function register_hook($action, $function){
    $hook_registered[] = [
        'action' => $action,
        'function' => $function
    ];
}

function run_hook($action){
    global $hook_registered;
    foreach($hook_registered as $hook){
        if($hook['action'] == $action){
            if(function_exists($hook['function'])){
                call_user_func($hook['function']);
            }
        }
    }
}

