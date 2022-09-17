<?php

/**
 * PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
 **/

$action = $routes['1'];


if(file_exists('system/paymentgateway/'.$action.'.php')){
    include 'system/paymentgateway/'.$action.'.php';
    if(function_exists($action.'_payment_notification')){
        run_hook('callback_payment_notification'); #HOOK
        call_user_func($action.'_payment_notification');
        die();
    }
}

header('HTTP/1.1 404 Not Found');
echo 'Not Found';