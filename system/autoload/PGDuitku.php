<?php


/**
 * PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
 *
 * Payment Gateway duitku.com
 **/

class PGDuitku
{
    protected $user;
    protected $trx;

    public function __construct($trx, $user)
    {
        $this->user = $user;
        $this->trx = $trx;
    }


    function createTransaction($channel)
    {
        global $_c;
        $json = [
            'paymentMethod' => $channel,
            'paymentAmount' => $this->trx['price'],
            'merchantCode' => $_c['duitku_merchant_id'],
            'merchantOrderId' => $this->trx['id'],
            'productDetails' => $this->trx['plan_name'],
            'merchantUserInfo' =>  $this->user['fullname'],
            'customerVaName' =>  $this->user['fullname'],
            'email' => (empty($this->user['email'])) ? $this->user['username'] . '@' . $_SERVER['HTTP_HOST'] : $this->user['email'],
            'phoneNumber' => $this->user['phonenumber'],
            'itemDetails' => [
                [
                    'name' => $this->trx['plan_name'],
                    'price' => $this->trx['price'],
                    'quantity' => 1
                ]
            ],
            'returnUrl' => U . 'order/view/' . $this->trx['id'] . '/check',
            'signature' => md5($_c['duitku_merchant_id'] . $this->trx['id'] . $this->trx['price'] . $_c['duitku_merchant_key'])
        ];
        return json_decode(Http::postJsonData($this->getServer() . 'v2/inquiry', $json), true);
        /*
        {
            "merchantCode": "DXXXX",
            "reference": "DXXXXCX80TZJ85Q70QCI",
            "paymentUrl": "https://sandbox.duitku.com/topup/topupdirectv2.aspx?ref=BCA7WZ7EIDXXXXWEC",
            "vaNumber": "7007014001444348",
            "qrString": "",
            "amount": "40000",
            "statusCode": "00",
            "statusMessage": "SUCCESS"
        }
        00 - Success
        01 - Pending
        02 - Canceled
         */
    }

    function getStatus()
    {
        global $_c;
        $json = [
            'merchantCode' => $_c['duitku_merchant_id'],
            'merchantOrderId' => $this->trx['id'],
            'signature' => md5($_c['duitku_merchant_id'] . $this->trx['id'] . $_c['duitku_merchant_key'])
        ];
        return json_decode(Http::postJsonData($this->getServer() . 'transactionStatus', $json), true);
        /*
        {
            "merchantOrderId": "abcde12345",
            "reference": "DXXXXCX80TZJ85Q70QCI",
            "amount": "100000",
            "statusCode": "00",
            "statusMessage": "SUCCESS"
        }
        00 - Success
        01 - Pending
        02 - Canceled
         */
    }

    private function getServer()
    {
        global $_app_stage;
        if ($_app_stage == 'Live') {
            return 'https://passport.duitku.com/webapi/api/merchant/';
        } else {
            return 'https://sandbox.duitku.com/webapi/api/merchant/';
        }
    }
}