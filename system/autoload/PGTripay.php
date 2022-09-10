<?php


/**
 * PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
 *
 * Payment Gateway Tripay
 **/

class PGTripay
{
    protected $user;
    protected $trx;

    public function __construct($trx, $user)
    {
        $this->user = $user;
        $this->trx = $trx;
    }

    function getSignature()
    {
        global $_c;
        return hash_hmac('sha256', $_c['tripay_merchant'] . $this->trx['id'] . $this->trx['price'], $_c['tripay_secret_key']);
    }

    function createTransaction($channel) //$trxID, $channel, $amount, $user, $description)
    {
        global $_c;
        $json = [
            'method' => $channel,
            'amount' => $this->trx['price'],
            'merchant_ref' => $this->trx['id'],
            'customer_name' =>  $this->user['fullname'],
            'customer_email' => (empty($this->user['email'])) ? $this->user['username'] . '@' . $_SERVER['HTTP_HOST'] : $this->user['email'],
            'customer_phone' => $this->user['phonenumber'],
            'order_items' => [
                [
                    'name' => $this->trx['plan_name'],
                    'price' => $this->trx['price'],
                    'quantity' => 1
                ]
            ],
            'return_url' => U . 'order/view/' . $this->trx['id'] . '/check',
            'signature' => $this->getSignature()
        ];
        return json_decode(Http::postJsonData($this->getServer() . 'transaction/create', $json, ['Authorization: Bearer ' . $_c['tripay_api_key']]), true);
        /*
        {
            "success": true,
            "message": "",
            "data": {
                "reference": "T0001000000000000006",
                "merchant_ref": "INV345675",
                "payment_selection_type": "static",
                "payment_method": "BRIVA",
                "payment_name": "BRI Virtual Account",
                "customer_name": "Nama Pelanggan",
                "customer_email": "emailpelanggan@domain.com",
                "customer_phone": "081234567890",
                "callback_url": "https://domainanda.com/callback",
                "return_url": "https://domainanda.com/redirect",
                "amount": 1000000,
                "fee_merchant": 1500,
                "fee_customer": 0,
                "total_fee": 1500,
                "amount_received": 998500,
                "pay_code": "57585748548596587",
                "pay_url": null,
                "checkout_url": "https://tripay.co.id/checkout/T0001000000000000006",
                "status": "UNPAID",
                "expired_time": 1582855837,
            }
        }
        */
    }

    function getStatus($trxID)
    {
        global $_c;
        return json_decode(Http::getData($this->getServer() . 'transaction/detail?'.http_build_query(['reference'=>$trxID]), [
            'Authorization: Bearer ' . $_c['tripay_api_key']
        ]), true);
        /*
        {
            "success": true,
            "message": "",
            "data": {
                "reference": "T0001000000000000006",
                "merchant_ref": "INV345675",
                "payment_selection_type": "static",
                "payment_method": "BRIVA",
                "payment_name": "BRI Virtual Account",
                "customer_name": "Nama Pelanggan",
                "customer_email": "emailpelanggan@domain.com",
                "customer_phone": "081234567890",
                "callback_url": "https://domainanda.com/callback",
                "return_url": "https://domainanda.com/redirect",
                "amount": 1000000,
                "fee_merchant": 1500,
                "fee_customer": 0,
                "total_fee": 1500,
                "amount_received": 998500,
                "pay_code": "57585748548596587",
                "pay_url": null,
                "checkout_url": "https://tripay.co.id/checkout/T0001000000000000006",
                "status": "PAID",
                "expired_time": 1582855837,
            }
        }
        */
    }

    private function getServer()
    {
        global $_app_stage;
        if ($_app_stage == 'Live') {
            return 'https://tripay.co.id/api/';
        } else {
            return 'https://tripay.co.id/api-sandbox/';
        }
    }
}
