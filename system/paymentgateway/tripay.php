
<?php


/**
 * PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
 *
 * Payment Gateway tripay.com
 **/

function duitku_validate_config()
{
    global $config;
    if (empty($config['tripay_secret_key'])) {
        sendTelegram("Tripay payment gateway not configured");
        r2(U . 'order/package', 'w', Lang::T("Admin has not yet setup Tripay payment gateway, please tell admin"));
    }
}

function tripay_show_config()
{
    global $ui, $config;
    $ui->assign('_title', 'Tripay - Payment Gateway - ' . $config['CompanyName']);
    $ui->assign('channels', json_decode(file_get_contents('system/paymentgateway/channel_tripay.json'), true));
    $ui->display('pg-tripay.tpl');
}

function tripay_save_config()
{
    global $admin, $_L;
    $tripay_merchant = _post('tripay_merchant');
    $tripay_api_key = _post('tripay_api_key');
    $tripay_secret_key = _post('tripay_secret_key');
    $d = ORM::for_table('tbl_appconfig')->where('setting', 'tripay_merchant')->find_one();
    if ($d) {
        $d->value = $tripay_merchant;
        $d->save();
    } else {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'tripay_merchant';
        $d->value = $tripay_merchant;
        $d->save();
    }
    $d = ORM::for_table('tbl_appconfig')->where('setting', 'tripay_api_key')->find_one();
    if ($d) {
        $d->value = $tripay_api_key;
        $d->save();
    } else {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'tripay_api_key';
        $d->value = $tripay_api_key;
        $d->save();
    }
    $d = ORM::for_table('tbl_appconfig')->where('setting', 'tripay_secret_key')->find_one();
    if ($d) {
        $d->value = $tripay_secret_key;
        $d->save();
    } else {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'tripay_secret_key';
        $d->value = $tripay_secret_key;
        $d->save();
    }
    $d = ORM::for_table('tbl_appconfig')->where('setting', 'tripay_channel')->find_one();
    if ($d) {
        $d->value = implode(',', $_POST['tripay_channel']);
        $d->save();
    } else {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'tripay_channel';
        $d->value = implode(',', $_POST['tripay_channel']);
        $d->save();
    }

    _log('[' . $admin['username'] . ']: Tripay ' . $_L['Settings_Saved_Successfully'] . json_encode($_POST['tripay_channel']), 'Admin', $admin['id']);

    r2(U . 'paymentgateway/tripay', 's', $_L['Settings_Saved_Successfully']);
}


function tripay_create_transaction($channel, $trx, $user)
{
    global $config, $routes, $ui;
    $channels = json_decode(file_get_contents('system/paymentgateway/channel_tripay.json'), true);
    if (!in_array($routes[4], explode(",", $config['tripay_channel']))) {
        $ui->assign('_title', 'Tripay Channel - ' . $config['CompanyName']);
        $ui->assign('channels', $channels);
        $ui->assign('tripay_channels', explode(",", $config['tripay_channel']));
        $ui->assign('path', $routes[2] . '/' . $routes[3]);
        $ui->display('tripay_channel.tpl');
        die();
    }
    $json = [
        'method' => $channel,
        'amount' => $trx['price'],
        'merchant_ref' => $trx['id'],
        'customer_name' =>  $user['fullname'],
        'customer_email' => (empty($user['email'])) ? $user['username'] . '@' . $_SERVER['HTTP_HOST'] : $user['email'],
        'customer_phone' => $user['phonenumber'],
        'order_items' => [
            [
                'name' => $trx['plan_name'],
                'price' => $trx['price'],
                'quantity' => 1
            ]
        ],
        'return_url' => U . 'order/view/' . $trx['id'] . '/check',
        'signature' => hash_hmac('sha256', $config['tripay_merchant'] . $trx['id'] . $trx['price'], $config['tripay_secret_key'])
    ];
    $result = json_decode(Http::postJsonData(tripay_get_server() . 'transaction/create', $json, ['Authorization: Bearer ' . $config['tripay_api_key']]), true);
    if ($result['success'] != 1) {
        sendTelegram("Tripay payment failed\n\n" . json_encode($result, JSON_PRETTY_PRINT));
        r2(U . 'order/package', 'e', Lang::T("Failed to create transaction."));
    }
    $d = ORM::for_table('tbl_payment_gateway')
        ->where('username', $user['username'])
        ->where('status', 1)
        ->find_one();
    $d->gateway_trx_id = $result['data']['reference'];
    $d->pg_url_payment = $result['data']['checkout_url'];
    $d->pg_request = json_encode($result);
    $d->expired_date = date('Y-m-d H:i:s', $result['data']['expired_time']);
    $d->save();
    r2(U . "order/view/" . $d['id'], 's', Lang::T("Create Transaction Success"));

}

function tripay_get_status($trx, $user)
{
    global $config;
    $result = json_decode(Http::getData(tripay_get_server() . 'transaction/detail?' . http_build_query(['reference' => $trx['id']]), [
        'Authorization: Bearer ' . $config['tripay_api_key']
    ]), true);
    if ($result['success'] != 1) {
        sendTelegram("Tripay payment status failed\n\n" . json_encode($result, JSON_PRETTY_PRINT));
        r2(U . "order/view/" . $trx['id'], 'w', Lang::T("Payment check failed."));
    }
    $result =  $result['data'];
    if ($result['status'] == 'UNPAID') {
        r2(U . "order/view/" . $trx['id'], 'w', Lang::T("Transaction still unpaid."));
    } else if (in_array($result['status'], ['PAID', 'SETTLED']) && $trx['status'] != 2) {
        if (!Package::rechargeUser($user['id'], $trx['routers'], $trx['plan_id'], $trx['gateway'],  $result['payment_method'] . ' ' . $result['payment_channel'])) {
            r2(U . "order/view/" . $trx['id'], 'd', Lang::T("Failed to activate your Package, try again later."));
        }

        $trx->pg_paid_response = json_encode($result);
        $trx->payment_method = $result['payment_method'];
        $trx->payment_channel = $result['payment_name'];
        $trx->paid_date = date('Y-m-d H:i:s', $result['paid_at']);
        $trx->status = 2;
        $trx->save();

        r2(U . "order/view/" . $trx['id'], 's', Lang::T("Transaction has been paid."));
    } else if (in_array($result['status'], ['EXPIRED', 'FAILED', 'REFUND'])) {
        $trx->pg_paid_response = json_encode($result);
        $trx->status = 3;
        $trx->save();
        r2(U . "order/view/" . $trx['id'], 'd', Lang::T("Transaction expired."));
    } else if ($trx['status'] == 2) {
        r2(U . "order/view/" . $trx['id'], 'd', Lang::T("Transaction has been paid.."));
    }
}

function tripay_get_server()
{
    global $_app_stage;
    if ($_app_stage == 'Live') {
        return 'https://tripay.co.id/api/';
    } else {
        return 'https://tripay.co.id/api-sandbox/';
    }
}
