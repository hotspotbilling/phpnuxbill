<?php

/**
 * PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
 **/


// Payment Gateway Server
if ($_app_stage == 'Live') {
    $xendit_server = 'https://api.xendit.co/v2/';
    $midtrans_server = 'https://api.midtrans.com/';
    $tripay_server = 'https://tripay.co.id/api/';
} else {
    $xendit_server = 'https://api.xendit.co/v2/';
    $midtrans_server = 'https://api.sandbox.midtrans.com/';
    $tripay_server = 'https://tripay.co.id/api-sandbox/';
}


function xendit_create_invoice($trxID, $amount, $phone, $description)
{
    global $xendit_server, $_c;
    $json = [
        'external_id' => $trxID,
        'amount' => $amount,
        'description' => $description,
        'customer' => [
            'mobile_number' => $phone,
        ],
        'customer_notification_preference' => [
            'invoice_created' => ['whatsapp', 'sms'],
            'invoice_reminder' => ['whatsapp', 'sms'],
            'invoice_paid' => ['whatsapp', 'sms'],
            'invoice_expired' => ['whatsapp', 'sms']
        ],
        'payment_methods ' => explode(',', $_c['xendit_channel']),
        'success_redirect_url' => U . 'order/view/' . $trxID . '/check',
        'failure_redirect_url' => U . 'order/view/' . $trxID . '/check'
    ];

    return json_decode(postJsonData($xendit_server . 'invoices', $json, ['Authorization: Basic ' . base64_encode($_c['xendit_secret_key'] . ':')]), true);
    /*
    {
        "id": "631597513897510bace2459d", #gateway_trx_id
        "external_id": "test-va-success-1662359375",
        "user_id": "599bd7f1ccab55b020bb1147",
        "status": "PENDING",
        "merchant_name": "Xendit",
        "merchant_profile_picture_url": "https://xnd-companies.s3.amazonaws.com/prod/1538466380522_868.png",
        "amount": 3000000,
        "description": "Test - VA Successful invoice payment",
        "expiry_date": "2022-09-06T06:29:37.251Z",
        "invoice_url": "https://checkout-staging.xendit.co/web/631597513897510bace2459d"
        "created": "2022-09-05T06:29:37.954Z",
        "updated": "2022-09-05T06:29:37.954Z"
    }
    */
}

function xendit_get_invoice($xendittrxID)
{
    global $xendit_server, $_c;
    return json_decode(getData($xendit_server . 'invoices/' . $xendittrxID, [
        'Authorization: Basic ' . base64_encode($_c['xendit_secret_key'] . ':')
    ]), true);
    /*
    {
        "id": "631597513897510bace2459d", #gateway_trx_id
        "external_id": "test-va-success-1662359375",
        "user_id": "599bd7f1ccab55b020bb1147",
        "status": "PENDING", // CHECK THIS
        "merchant_name": "Xendit",
        "merchant_profile_picture_url": "https://xnd-companies.s3.amazonaws.com/prod/1538466380522_868.png",
        "amount": 3000000,
        "description": "Test - VA Successful invoice payment",
        "expiry_date": "2022-09-06T06:29:37.251Z",
        "invoice_url": "https://checkout-staging.xendit.co/web/631597513897510bace2459d"
        "created": "2022-09-05T06:29:37.954Z",
        "updated": "2022-09-05T06:29:37.954Z"
    }
    */
}

/**    MIDTRANS */


function midtrans_create_payment($trxID, $invoiceID, $amount, $description)
{
    global $midtrans_server, $_c;
    $json = [
        'transaction_details' => [
            'order_id' => $trxID,
            'gross_amount' => intval($amount),
            "payment_link_id" => $invoiceID
        ],
        "item_details" => [
            [
                "name" => $description,
                "price" => intval($amount),
                "quantity" => 1
            ]
        ],
        'enabled_payments' => explode(',', $_c['midtrans_channel']),
        "usage_limit" =>  4,
        "expiry" => [
            "duration" => 24,
            "unit" => "hours"
        ]
    ];
    $data = postJsonData($midtrans_server . 'v1/payment-links', $json, ['Authorization: Basic ' . base64_encode($_c['midtrans_server_key'] . ':')]);
    $json = json_decode($data, true);
    if (!empty($json['error_messages'])) {
        sendTelegram(json_encode("Midtrans create Payment error:\n" . alphanumeric($_c['CompanyName']) . "_" . crc32($_c['CompanyName']) . "_" . $trxID . "\n" . $json['error_messages']));
    }
    return $json;
    /*
    {
        "order_id": "concert-ticket-05", //traxid
        "payment_url": "https://app.sandbox.midtrans.com/payment-links/amazing-ticket-payment-123"
	}
    */
}

function midtrans_check_payment($midtranstrxID)
{
    global $midtrans_server, $_c;
    echo $midtrans_server . 'v2/' . $midtranstrxID . '/status';
    return json_decode(getData($midtrans_server . 'v2/' . $midtranstrxID . '/status', [
        'Authorization: Basic ' . base64_encode($_c['midtrans_server_key'] . ':')
    ]), true);
    /*
    {
        "masked_card": "41111111-1111",
        "approval_code": "1599493766402",
        "bank": "bni",
        "channel_response_code": "00",
        "channel_response_message": "Approved",
        "transaction_time": "2020-09-07 22:49:26",
        "gross_amount": "10000.00",
        "currency": "IDR",
        "order_id": "SANDBOX-G710367688-806",
        "payment_type": "credit_card",
        "signature_key": "4d4abc70f5a88b09f48f3ab5cb91245feb0b3d89181117a677767b42f8cbe477f5a0d38af078487071311f97da646c1eb9542c1bbf0b19fa9f12e64605ac405e",
        "status_code": "200",
        "transaction_id": "3853c491-ca9b-4bcc-ac20-3512ff72a5d0",
        "transaction_status": "cancel",
        "fraud_status": "challenge",
        "status_message": "Success, transaction is found",
        "merchant_id": "G710367688",
        "card_type": "credit"
    }
    */
}

function getData($url, $headers)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $server_output = curl_exec($ch);
    curl_close($ch);
    return $server_output;
}


function postJsonData($url, $array_post, $headers = [], $basic = null)
{
    $headers[] = 'Content-Type: application/json';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_VERBOSE, false);
    curl_setopt($ch, CURLINFO_HEADER_OUT, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($array_post));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if (!empty($basic)) {
        curl_setopt($ch, CURLOPT_USERPWD, $basic);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $server_output = curl_exec($ch);
    curl_close($ch);
    return $server_output;
}
