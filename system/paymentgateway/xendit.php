
<?php


/**
 * PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
 *
 * Payment Gateway xendit.com
 **/

function xendit_validate_config()
{
    global $config;
    if (empty($config['xendit_secret_key']) || empty($config['xendit_verification_token'])) {
        sendTelegram("Xendit payment gateway not configured");
        r2(U . 'order/package', 'w', Lang::T("Admin has not yet setup Xendit payment gateway, please tell admin"));
    }
}

function xendit_show_config()
{
    global $ui, $config;
    $ui->assign('_title', 'Xendit - Payment Gateway - ' . $config['CompanyName']);
    $ui->assign('channels', json_decode(file_get_contents('system/paymentgateway/channel_xendit.json'), true));
    $ui->display('pg-xendit.tpl');
}

function xendit_save_config()
{
    global $admin, $_L;
    $xendit_secret_key = _post('xendit_secret_key');
    $xendit_verification_token = _post('xendit_verification_token');
    $d = ORM::for_table('tbl_appconfig')->where('setting', 'xendit_secret_key')->find_one();
    if ($d) {
        $d->value = $xendit_secret_key;
        $d->save();
    } else {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'xendit_secret_key';
        $d->value = $xendit_secret_key;
        $d->save();
    }
    $d = ORM::for_table('tbl_appconfig')->where('setting', 'xendit_verification_token')->find_one();
    if ($d) {
        $d->value = $xendit_verification_token;
        $d->save();
    } else {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'xendit_verification_token';
        $d->value = $xendit_verification_token;
        $d->save();
    }
    $d = ORM::for_table('tbl_appconfig')->where('setting', 'xendit_channel')->find_one();
    if ($d) {
        $d->value = implode(',', $_POST['xendit_channel']);
        $d->save();
    } else {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'xendit_channel';
        $d->value = implode(',', $_POST['xendit_channel']);
        $d->save();
    }

    _log('[' . $admin['username'] . ']: Xendit ' . $_L['Settings_Saved_Successfully'], 'Admin', $admin['id']);

    r2(U . 'paymentgateway/xendit', 's', $_L['Settings_Saved_Successfully']);
}


function xendit_create_transaction($trx, $user)
{
    global $config;
    $json = [
        'external_id' => $trx['id'],
        'amount' => $trx['price'],
        'description' => $trx['plan_name'],
        'customer' => [
            'mobile_number' => $user['phonenumber'],
        ],
        'customer_notification_preference' => [
            'invoice_created' => ['whatsapp', 'sms'],
            'invoice_reminder' => ['whatsapp', 'sms'],
            'invoice_paid' => ['whatsapp', 'sms'],
            'invoice_expired' => ['whatsapp', 'sms']
        ],
        'payment_methods ' => explode(',', $config['xendit_channel']),
        'success_redirect_url' => U . 'order/view/' . $trx['id'] . '/check',
        'failure_redirect_url' => U . 'order/view/' . $trx['id'] . '/check'
    ];

    $result = json_decode(Http::postJsonData(xendit_get_server() . 'invoices', $json, ['Authorization: Basic ' . base64_encode($config['xendit_secret_key'] . ':')]), true);
    if (!$result['id']) {
        r2(U . 'order/package', 'e', Lang::T("Failed to create transaction."));
    }
    $d = ORM::for_table('tbl_payment_gateway')
        ->where('username', $user['username'])
        ->where('status', 1)
        ->find_one();
    $d->gateway_trx_id = $result['id'];
    $d->pg_url_payment = $result['invoice_url'];
    $d->pg_request = json_encode($result);
    $d->expired_date = date('Y-m-d H:i:s', strtotime($result['expiry_date']));
    $d->save();
    header('Location: ' . $result['invoice_url']);
    exit();
}

function xendit_get_status($trx, $user)
{
    global $config;
    $result = json_decode(Http::getData(xendit_get_server() . 'invoices/' . $trx['gateway_trx_id'], [
        'Authorization: Basic ' . base64_encode($config['xendit_secret_key'] . ':')
    ]), true);

    if ($result['status'] == 'PENDING') {
        r2(U . "order/view/" . $trx['id'], 'w', Lang::T("Transaction still unpaid."));
    } else if (in_array($result['status'], ['PAID', 'SETTLED']) && $trx['status'] != 2) {
        if (!Package::rechargeUser($user['id'], $trx['routers'], $trx['plan_id'], $trx['gateway'], $result['payment_channel'])) {
            r2(U . "order/view/" . $trx['id'], 'd', Lang::T("Failed to activate your Package, try again later."));
        }
        $trx->pg_paid_response = json_encode($result);
        $trx->payment_method = $result['payment_method'];
        $trx->payment_channel = $result['payment_channel'];
        $trx->paid_date = date('Y-m-d H:i:s', strtotime($result['updated']));
        $trx->status = 2;
        $trx->save();

        r2(U . "order/view/" . $trx['id'], 's', Lang::T("Transaction has been paid."));
    } else if ($result['status'] == 'EXPIRED') {
        $trx->pg_paid_response = json_encode($result);
        $trx->status = 3;
        $trx->save();
        r2(U . "order/view/" . $trx['id'], 'd', Lang::T("Transaction expired."));
    } else if ($trx['status'] == 2) {
        r2(U . "order/view/" . $trx['id'], 'd', Lang::T("Transaction has been paid.."));
    }else{
        sendTelegram("xendit_get_status: unknown result\n\n".json_encode($result, JSON_PRETTY_PRINT));
        r2(U . "order/view/" . $trx['id'], 'd', Lang::T("Unknown Command."));
    }
}

function xendit_get_server()
{
    global $_app_stage;
    if ($_app_stage == 'Live') {
        return 'https://api.xendit.co/v2/';
    } else {
        return 'https://api.xendit.co/v2/';
    }
}
