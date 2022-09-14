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
        $ui->assign('channels', json_decode(file_get_contents('system/paymentgateway/channel_xendit.json'), true));
        $ui->display('pg-xendit.tpl');
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
        $d = ORM::for_table('tbl_appconfig')->where('setting', 'xendit_channel')->find_one();
        if($d){
            $d->value = implode(',',$_POST['xendit_channel']);
            $d->save();
        }else{
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = 'xendit_channel';
            $d->value = implode(',',$_POST['xendit_channel']);
            $d->save();
        }

        _log('[' . $admin['username'] . ']: Xendit ' . $_L['Settings_Saved_Successfully'], 'Admin', $admin['id']);

        r2(U . 'paymentgateway/xendit', 's', $_L['Settings_Saved_Successfully']);
        break;
    case 'midtrans':
        $ui->assign('_title', 'Midtrans - Payment Gateway - '. $config['CompanyName']);
        $ui->assign('channels', json_decode(file_get_contents('system/paymentgateway/channel_midtrans.json'), true));
        $ui->display('pg-midtrans.tpl');
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
        $d = ORM::for_table('tbl_appconfig')->where('setting', 'midtrans_channel')->find_one();
        if($d){
            $d->value = implode(',',$_POST['midtrans_channel']);
            $d->save();
        }else{
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = 'midtrans_channel';
            $d->value = implode(',',$_POST['midtrans_channel']);
            $d->save();
        }

        _log('[' . $admin['username'] . ']: Midtrans ' . $_L['Settings_Saved_Successfully'], 'Admin', $admin['id']);

        r2(U . 'paymentgateway/midtrans', 's', $_L['Settings_Saved_Successfully']);
        break;
    case 'tripay':
        $ui->assign('_title', 'Tripay - Payment Gateway - '. $config['CompanyName']);
        $ui->assign('channels', json_decode(file_get_contents('system/paymentgateway/channel_tripay.json'), true));
        $ui->display('pg-tripay.tpl');
        break;
    case 'tripay-post':
        $tripay_merchant = _post('tripay_merchant');
        $tripay_api_key = _post('tripay_api_key');
        $tripay_secret_key = _post('tripay_secret_key');
        $d = ORM::for_table('tbl_appconfig')->where('setting', 'tripay_merchant')->find_one();
        if($d){
            $d->value = $tripay_merchant;
            $d->save();
        }else{
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = 'tripay_merchant';
            $d->value = $tripay_merchant;
            $d->save();
        }
        $d = ORM::for_table('tbl_appconfig')->where('setting', 'tripay_api_key')->find_one();
        if($d){
            $d->value = $tripay_api_key;
            $d->save();
        }else{
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = 'tripay_api_key';
            $d->value = $tripay_api_key;
            $d->save();
        }
        $d = ORM::for_table('tbl_appconfig')->where('setting', 'tripay_secret_key')->find_one();
        if($d){
            $d->value = $tripay_secret_key;
            $d->save();
        }else{
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = 'tripay_secret_key';
            $d->value = $tripay_secret_key;
            $d->save();
        }
        $d = ORM::for_table('tbl_appconfig')->where('setting', 'tripay_channel')->find_one();
        if($d){
            $d->value = implode(',',$_POST['tripay_channel']);
            $d->save();
        }else{
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = 'tripay_channel';
            $d->value = implode(',',$_POST['tripay_channel']);
            $d->save();
        }

        _log('[' . $admin['username'] . ']: Tripay ' . $_L['Settings_Saved_Successfully'].json_encode($_POST['tripay_channel']), 'Admin', $admin['id']);

        r2(U . 'paymentgateway/tripay', 's', $_L['Settings_Saved_Successfully']);
        break;
    case 'duitku':
        $ui->assign('_title', 'Duitku - Payment Gateway - '. $config['CompanyName']);
        $ui->assign('channels', json_decode(file_get_contents('system/paymentgateway/channel_duitku.json'), true));
        $ui->display('pg-duitku.tpl');
        break;
    case 'duitku-post':
        $duitku_merchant_id = _post('duitku_merchant_id');
        $duitku_merchant_key = _post('duitku_merchant_key');
        $d = ORM::for_table('tbl_appconfig')->where('setting', 'duitku_merchant_id')->find_one();
        if($d){
            $d->value = $duitku_merchant_id;
            $d->save();
        }else{
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = 'duitku_merchant_id';
            $d->value = $duitku_merchant_id;
            $d->save();
        }
        $d = ORM::for_table('tbl_appconfig')->where('setting', 'duitku_merchant_key')->find_one();
        if($d){
            $d->value = $duitku_merchant_key;
            $d->save();
        }else{
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = 'duitku_merchant_key';
            $d->value = $duitku_merchant_key;
            $d->save();
        }

        $d = ORM::for_table('tbl_appconfig')->where('setting', 'duitku_channel')->find_one();
        if($d){
            $d->value = implode(',',$_POST['duitku_channel']);
            $d->save();
        }else{
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = 'duitku_channel';
            $d->value = implode(',',$_POST['duitku_channel']);
            $d->save();
        }

        _log('[' . $admin['username'] . ']: Duitku ' . $_L['Settings_Saved_Successfully'], 'Admin', $admin['id']);

        r2(U . 'paymentgateway/duitku', 's', $_L['Settings_Saved_Successfully']);
        break;
    default:
        $ui->display('a404.tpl');
}
