<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_system_menu', 'paymentgateway');

$action = alphanumeric($routes[1]);
$ui->assign('_admin', $admin);

if ($action == 'delete') {
    $pg = alphanumeric($routes[2]);
    if (file_exists($PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . $pg . '.php')) {
        deleteFile($PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR, $pg);
    }
    r2(U . 'paymentgateway', 's', Lang::T('Payment Gateway Deleted'));
}

if (file_exists($PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . $action . '.php')) {
    include $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . $action . '.php';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (function_exists($action . '_save_config')) {
            call_user_func($action . '_save_config');
        } else {
            $ui->display('a404.tpl');
        }
    } else {
        if (function_exists($action . '_show_config')) {
            call_user_func($action . '_show_config');
        } else {
            $ui->display('a404.tpl');
        }
    }
} else {
    if (!empty($action)) {
        r2(U . 'paymentgateway', 'w', Lang::T('Payment Gateway Not Found'));
    } else {
        $files = scandir($PAYMENTGATEWAY_PATH);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) == 'php') {
                $pgs[] = str_replace('.php', '', $file);
            }
        }
        if (isset($_POST['payment_gateway'])) {
            $payment_gateway = _post('payment_gateway');
            $d = ORM::for_table('tbl_appconfig')->where('setting', 'payment_gateway')->find_one();
            if ($d) {
                $d->value = $payment_gateway;
                $d->save();
            } else {
                $d = ORM::for_table('tbl_appconfig')->create();
                $d->setting = 'payment_gateway';
                $d->value = $payment_gateway;
                $d->save();
            }
            r2(U . 'paymentgateway', 's', Lang::T('Payment Gateway saved successfully'));
        }
        $ui->assign('_title', 'Payment Gateway Settings');
        $ui->assign('pgs', $pgs);
        $ui->display('paymentgateway.tpl');
    }
}


function deleteFile($path, $name)
{
    $files = scandir($path);
    foreach ($files as $file) {
        if (is_file($path . $file) && strpos($file, $name) !== false) {
            unlink($path . $file);
        } else if (is_dir($path . $file) && !in_array($file, ['.', '..'])) {
            deleteFile($path . $file . DIRECTORY_SEPARATOR, $name);
        }
    }
}
