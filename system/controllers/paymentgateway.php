<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
**/
_admin();
$ui->assign('_system_menu', 'paymentgateway');

$action = $routes['1'];
$admin = Admin::_info();
$ui->assign('_admin', $admin);

switch ($action) {
    case 'xendit':
        $ui->assign('_title', 'Xendit - Payment Gateway - '. $config['CompanyName']);
        $ui->display('app-xendit.tpl');
        break;
    case 'xendit-post':
        $xendit_secret_key = _post('xendit_secret_key');
        $xendit_verification_token = _post('xendit_verification_token');
        $d = ORM::for_table('tbl_appconfig')->where('setting', 'xendit_secret_key')->find_one();
        if($d){
            $d->value = $xendit_secret_key;
            $d->save();
        }else{
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = 'xendit_secret_key';
            $d->value = $xendit_secret_key;
            $d->save();
        }
        $d = ORM::for_table('tbl_appconfig')->where('setting', 'xendit_verification_token')->find_one();
        if($d){
            $d->value = $xendit_verification_token;
            $d->save();
        }else{
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = 'xendit_verification_token';
            $d->value = $xendit_verification_token;
            $d->save();
        }

        _log('[' . $admin['username'] . ']: Xendit ' . $_L['Settings_Saved_Successfully'], 'Admin', $admin['id']);

        r2(U . 'paymentgateway/xendit', 's', $_L['Settings_Saved_Successfully']);
        break;
    case 'midtrans':
        $ui->assign('_title', 'Midtrans - Payment Gateway - '. $config['CompanyName']);

        $ui->display('app-midtrans.tpl');
        break;
    case 'midtrans-post':
        $midtrans_merchant_id = _post('midtrans_merchant_id');
        $midtrans_client_key = _post('midtrans_client_key');
        $midtrans_server_key = _post('midtrans_server_key');
        $d = ORM::for_table('tbl_appconfig')->where('setting', 'midtrans_merchant_id')->find_one();
        if($d){
            $d->value = $midtrans_merchant_id;
            $d->save();
        }else{
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = 'midtrans_merchant_id';
            $d->value = $midtrans_merchant_id;
            $d->save();
        }
        $d = ORM::for_table('tbl_appconfig')->where('setting', 'midtrans_client_key')->find_one();
        if($d){
            $d->value = $midtrans_client_key;
            $d->save();
        }else{
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = 'midtrans_client_key';
            $d->value = $midtrans_client_key;
            $d->save();
        }
        $d = ORM::for_table('tbl_appconfig')->where('setting', 'midtrans_server_key')->find_one();
        if($d){
            $d->value = $midtrans_server_key;
            $d->save();
        }else{
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = 'midtrans_server_key';
            $d->value = $midtrans_server_key;
            $d->save();
        }

        _log('[' . $admin['username'] . ']: Midtrans ' . $_L['Settings_Saved_Successfully'], 'Admin', $admin['id']);

        r2(U . 'paymentgateway/midtrans', 's', $_L['Settings_Saved_Successfully']);
        break;
}
