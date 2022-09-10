<?php


/**
 * PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
 *
 * Payment Gateway Xendit
 **/

class PGTripay {
    protected $user;
    protected $trx;
    protected $channel;

    public function __construct($trx,$user) {
        $this->user = $user;
        $this->trx = $trx;
        return $this;
    }

    function createInvoice()
    {
        global $_c;
        $json = [
            'external_id' => $this->trx['id'],
            'amount' => $this->trx['price'],
            'description' => $this->trx['plan_name'],
            'customer' => [
                'mobile_number' => $this->user['phonenumber'],
            ],
            'customer_notification_preference' => [
                'invoice_created' => ['whatsapp', 'sms'],
                'invoice_reminder' => ['whatsapp', 'sms'],
                'invoice_paid' => ['whatsapp', 'sms'],
                'invoice_expired' => ['whatsapp', 'sms']
            ],
            'payment_methods ' => explode(',', $_c['xendit_channel']),
            'success_redirect_url' => U . 'order/view/' . $this->trx['id'] . '/check',
            'failure_redirect_url' => U . 'order/view/' . $this->trx['id'] . '/check'
        ];

        return json_decode(Http::postJsonData($this->getServer() . 'invoices', $json, ['Authorization: Basic ' . base64_encode($_c['xendit_secret_key'] . ':')]), true);
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

    function getInvoice($xendittrxID)
    {
        global $_c;
        return json_decode(Http::getData($this->getServer() . 'invoices/' . $xendittrxID, [
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

    private function getServer(){
        global $_app_stage;
        if ($_app_stage == 'Live') {
            return 'https://api.xendit.co/v2/';
        } else {
            return 'https://api.xendit.co/v2/';
        }
    }
}