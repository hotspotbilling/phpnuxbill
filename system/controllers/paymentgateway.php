<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
**/
_admin();
$ui->assign('_system_menu', 'paymentgateway');

$action = alphanumeric($routes['1']);
$admin = Admin::_info();
$ui->assign('_admin', $admin);

if(file_exists('system/paymentgateway/'.$action.'.php')){
    include 'system/paymentgateway/'.$action.'.php';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if(function_exists($action.'_save_config')){
            call_user_func($action.'_save_config');
        }else{
            $ui->display('a404.tpl');
        }
    }else{
        if(function_exists($action.'_show_config')){
            call_user_func($action.'_show_config');
        }else{
            $ui->display('a404.tpl');
        }
    }
}else{
    if(!empty($action)){
        r2(U . 'paymentgateway', 'w', Lang::T('Payment Gateway Not Found'));
    }else{
        $files = scandir('system/paymentgateway/');
        foreach($files as $file){
            if(pathinfo($file, PATHINFO_EXTENSION)=='php'){
                $pgs[] = str_replace('.php','',$file);
            }
        }
        if(isset($_POST['payment_gateway'])){
            $payment_gateway = _post('payment_gateway');
            $d = ORM::for_table('tbl_appconfig')->where('setting', 'payment_gateway')->find_one();
            if($d){
                $d->value = $payment_gateway;
                $d->save();
            }else{
                $d = ORM::for_table('tbl_appconfig')->create();
                $d->setting = 'payment_gateway';
                $d->value = $payment_gateway;
                $d->save();
            }
            r2(U . 'paymentgateway', 's', Lang::T('Payment Gateway saved successfully'));
        }
        $ui->assign('_title', 'Payment Gateway Settings - '. $config['CompanyName']);
        $ui->assign('pgs', $pgs);
        $ui->display('paymentgateway.tpl');
    }
}