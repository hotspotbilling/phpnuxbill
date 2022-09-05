<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
**/


// Payment Gateway Server
if($_app_stage = 'Live'){
    $xendit_server = 'https://api.xendit.co/v2/';
    $midtrans_server = 'https://api.midtrans.com';
    $moota_server = 'https://api.xendit.co/v2/';
}else{
    $xendit_server = 'https://api.xendit.co/v2/';
    $midtrans_server = 'https://api.sandbox.midtrans.com';
    $moota_server = 'https://api.xendit.co/v2/';
}


function create_invoice_xendit($trxID, $amount, $phone, $description){
    global $xendit_server,$_c;
    $json = [
        'external_id' => $trxID,
        'amount' => $amount,
        'description' => $description,
        'customer' => [
            'mobile_number' => $phone,
        ],
        'customer_notification_preference'=>[
            'invoice_created' => ['whatsapp','sms'],
            'invoice_reminder' => ['whatsapp','sms'],
            'invoice_paid' => ['whatsapp','sms'],
            'invoice_expired' => ['whatsapp','sms']
        ],
        'success_redirect_url' => APP_URL,
        'failure_redirect_url' => APP_URL
    ];
    return json_decode(postJsonData($xendit_server, $json, [
        'Authorization: Basic '.$_c['xendit_secret']
    ]),true);
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

function get_invoice_xendit($xendittrxID){
    global $xendit_server,$_c;
    return json_decode(getData($xendit_server.'invoices/'.$xendittrxID, [
        'Authorization: Basic '.$_c['xendit_secret']
    ]),true);
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
