<?php


/**
 * PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
 *
 * Payment Gateway duitku.com
 **/

function duitku_validate_config()
{
    global $config;
    if (empty($config['duitku_merchant_key'])) {
        sendTelegram("Duitku payment gateway not configured");
        r2(U . 'order/package', 'w', Lang::T("Admin has not yet setup Duitku payment gateway, please tell admin"));
    }
}

function duitku_show_config()
{
    global $ui, $config;
    $ui->assign('_title', 'Duitku - Payment Gateway - ' . $config['CompanyName']);
    $ui->assign('channels', json_decode(file_get_contents('system/paymentgateway/channel_duitku.json'), true));
    $ui->display('pg-duitku.tpl');
}

function duitku_save_config()
{
    global $admin;
    $duitku_merchant_id = _post('duitku_merchant_id');
    $duitku_merchant_key = _post('duitku_merchant_key');
    $d = ORM::for_table('tbl_appconfig')->where('setting', 'duitku_merchant_id')->find_one();
    if ($d) {
        $d->value = $duitku_merchant_id;
        $d->save();
    } else {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'duitku_merchant_id';
        $d->value = $duitku_merchant_id;
        $d->save();
    }
    $d = ORM::for_table('tbl_appconfig')->where('setting', 'duitku_merchant_key')->find_one();
    if ($d) {
        $d->value = $duitku_merchant_key;
        $d->save();
    } else {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'duitku_merchant_key';
        $d->value = $duitku_merchant_key;
        $d->save();
    }
    $d = ORM::for_table('tbl_appconfig')->where('setting', 'duitku_channel')->find_one();
    if ($d) {
        $d->value = implode(',', $_POST['duitku_channel']);
        $d->save();
    } else {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'duitku_channel';
        $d->value = implode(',', $_POST['duitku_channel']);
        $d->save();
    }
    _log('[' . $admin['username'] . ']: Duitku ' . Lang::T('Settings_Saved_Successfully'), 'Admin', $admin['id']);
    r2(U . 'paymentgateway/duitku', 's', Lang::T('Settings_Saved_Successfully'));
}

function duitku_create_transaction($trx, $user)
{
    global $config, $routes, $ui;

    $channels = json_decode(file_get_contents('system/paymentgateway/channel_duitku.json'), true);
    if (!in_array($routes[4], explode(",", $config['duitku_channel']))) {
        $ui->assign('_title', 'Duitku Channel - ' . $config['CompanyName']);
        $ui->assign('channels', $channels);
        $ui->assign('duitku_channels', explode(",", $config['duitku_channel']));
        $ui->assign('path', $routes['2'] . '/' . $routes['3']);
        $ui->display('duitku_channel.tpl');
        die();
    }

    $json = [
        'paymentMethod' => $routes[4],
        'paymentAmount' => $trx['price'],
        'merchantCode' => $config['duitku_merchant_id'],
        'merchantOrderId' => $trx['id'],
        'productDetails' => $trx['plan_name'],
        'merchantUserInfo' =>  $user['fullname'],
        'customerVaName' =>  $user['fullname'],
        'email' => (empty($user['email'])) ? $user['username'] . '@' . $_SERVER['HTTP_HOST'] : $user['email'],
        'phoneNumber' => $user['phonenumber'],
        'itemDetails' => [
            [
                'name' => $trx['plan_name'],
                'price' => $trx['price'],
                'quantity' => 1
            ]
        ],
        'returnUrl' => U . 'order/view/' . $trx['id'] . '/check',
        'signature' => md5($config['duitku_merchant_id'] . $trx['id'] . $trx['price'] . $config['duitku_merchant_key'])
    ];

    $result = json_decode(Http::postJsonData(duitku_get_server() . 'v2/inquiry', $json), true);

    if (empty($result['paymentUrl'])) {
        sendTelegram("Duitku payment failed\n\n" . json_encode($result, JSON_PRETTY_PRINT));
        r2(U . 'order/package', 'e', Lang::T("Failed to create transaction."));
    }
    $d = ORM::for_table('tbl_payment_gateway')
        ->where('username', $user['username'])
        ->where('status', 1)
        ->find_one();
    $d->gateway_trx_id = $result['reference'];
    $d->pg_url_payment = $result['paymentUrl'];
    $d->payment_method = $routes['4'];
    foreach ($channels as $channel) {
        if ($channel['id'] == $routes['4']) {
            $d->payment_channel = $channel['name'];
            break;
        }
    }
    $d->pg_request = json_encode($result);
    $d->expired_date = date('Y-m-d H:i:s', strtotime("+1 day"));
    $d->save();
    r2(U . "order/view/" . $d['id'], 's', Lang::T("Create Transaction Success"));
}

function duitku_get_status($trx, $user)
{
    global $config;
    $json = [
        'merchantCode' => $config['duitku_merchant_id'],
        'merchantOrderId' => $trx['id'],
        'signature' => md5($config['duitku_merchant_id'] . $trx['id'] . $config['duitku_merchant_key'])
    ];
    $result = json_decode(Http::postJsonData(duitku_get_server() . 'transactionStatus', $json), true);
    if ($result['reference'] != $trx['gateway_trx_id']) {
        sendTelegram("Duitku payment status failed\n\n" . json_encode($result, JSON_PRETTY_PRINT));
        r2(U . "order/view/" . $trx['id'], 'w', Lang::T("Payment check failed."));
    }
    if ($result['statusCode'] == '01') {
        r2(U . "order/view/" . $trx['id'], 'w', Lang::T("Transaction still unpaid."));
    } else if ($result['statusCode'] == '00' && $trx['status'] != 2) {
        if (!Package::rechargeUser($user['id'], $trx['routers'], $trx['plan_id'], $trx['gateway'],  $trx['payment_channel'])) {
            r2(U . "order/view/" . $trx['id'], 'd', Lang::T("Failed to activate your Package, try again later."));
        }

        $trx->pg_paid_response = json_encode($result);
        $trx->paid_date = date('Y-m-d H:i:s');
        $trx->status = 2;
        $trx->save();

        r2(U . "order/view/" . $trx['id'], 's', Lang::T("Transaction has been paid."));
    } else if ($result['statusCode'] == '02') {
        $trx->pg_paid_response = json_encode($result);
        $trx->status = 3;
        $trx->save();
        r2(U . "order/view/" . $trx['id'], 'd', Lang::T("Transaction expired or Failed."));
    } else if ($trx['status'] == 2) {
        r2(U . "order/view/" . $trx['id'], 'd', Lang::T("Transaction has been paid.."));
    }
}

function duitku_get_server()
{
    global $_app_stage;
    if ($_app_stage == 'Live') {
        return 'https://passport.duitku.com/webapi/api/merchant/';
    } else {
        return 'https://sandbox.duitku.com/webapi/api/merchant/';
    }
}
