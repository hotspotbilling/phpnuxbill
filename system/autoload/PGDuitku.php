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

    function getSignature($amount,$datetime)
    {
        global $_c;
        return hash('sha256', $_c['duitku_merchant_id'] . $amount . $datetime . $_c['duitku_merchant_key']);
    }


    function createTransaction($channel)
    {
    }

    function getStatus($trxID)
    {
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