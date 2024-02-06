<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/


class Message
{

    public static function sendTelegram($txt)
    {
        global $config;
        run_hook('send_telegram'); #HOOK
        if (!empty($config['telegram_bot']) && !empty($config['telegram_target_id'])) {
            return Http::getData('https://api.telegram.org/bot' . $config['telegram_bot'] . '/sendMessage?chat_id=' . $config['telegram_target_id'] . '&text=' . urlencode($txt));
        }
    }


    public static function sendSMS($phone, $txt)
    {
        global $config;
        run_hook('send_sms'); #HOOK
        if (!empty($config['sms_url'])) {
            if (strlen($config['sms_url']) > 4 && substr($config['sms_url'], 0, 4) != "http") {
                if (strlen($txt) > 160) {
                    $txts = str_split($txt, 160);
                    try {
                        $mikrotik = Mikrotik::info($config['sms_url']);
                        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        foreach ($txts as $txt) {
                            Mikrotik::sendSMS($client, $phone, $txt);
                        }
                    } catch (Exception $e) {
                        // ignore, add to logs
                        _log("Failed to send SMS using Mikrotik.\n" . $e->getMessage(), 'SMS', 0);
                    }
                } else {
                    try {
                        $mikrotik = Mikrotik::info($config['sms_url']);
                        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        Mikrotik::sendSMS($client, $phone, $txt);
                    } catch (Exception $e) {
                        // ignore, add to logs
                        _log("Failed to send SMS using Mikrotik.\n" . $e->getMessage(), 'SMS', 0);
                    }
                }
            } else {
                $smsurl = str_replace('[number]', urlencode($phone), $config['sms_url']);
                $smsurl = str_replace('[text]', urlencode($txt), $smsurl);
                return Http::getData($smsurl);
            }
        }
    }

    public static function sendWhatsapp($phone, $txt)
    {
        global $config;
        run_hook('send_whatsapp'); #HOOK
        if (!empty($config['wa_url'])) {
            $waurl = str_replace('[number]', urlencode($phone), $config['wa_url']);
            $waurl = str_replace('[text]', urlencode($txt), $waurl);
            return Http::getData($waurl);
        }
    }

    public static function sendPackageNotification($phone, $name, $package, $price, $message, $via)
    {
        $msg = str_replace('[[name]]', $name, $message);
        $msg = str_replace('[[package]]', $package, $msg);
        $msg = str_replace('[[price]]', $price, $msg);
        if (
            !empty($phone) && strlen($phone) > 5
            && !empty($message) && in_array($via, ['sms', 'wa'])
        ) {
            if ($via == 'sms') {
                Message::sendSMS($phone, $msg);
            } else if ($via == 'wa') {
                Message::sendWhatsapp($phone, $msg);
            }
        }
        return "$via: $msg";
    }

    public static function sendBalanceNotification($phone, $name, $balance, $balance_now, $message, $via)
    {
        $msg = str_replace('[[name]]', $name, $message);
        $msg = str_replace('[[current_balance]]', Lang::moneyFormat($balance_now), $msg);
        $msg = str_replace('[[balance]]', Lang::moneyFormat($balance), $msg);
        if (
            !empty($phone) && strlen($phone) > 5
            && !empty($message) && in_array($via, ['sms', 'wa'])
        ) {
            if ($via == 'sms') {
                Message::sendSMS($phone, $msg);
            } else if ($via == 'wa') {
                Message::sendWhatsapp($phone, $msg);
            }
        }
        return "$via: $msg";
    }

    public static function sendInvoice($cust, $trx)
    {
        global $config;
        $textInvoice = Lang::getNotifText('invoice_paid');
        $textInvoice = str_replace('[[company_name]]', $config['CompanyName'], $textInvoice);
        $textInvoice = str_replace('[[address]]', $config['address'], $textInvoice);
        $textInvoice = str_replace('[[phone]]', $config['phone'], $textInvoice);
        $textInvoice = str_replace('[[invoice]]', $trx['invoice'], $textInvoice);
        $textInvoice = str_replace('[[date]]', Lang::dateAndTimeFormat($trx['recharged_on'], $trx['recharged_time']), $textInvoice);
        $gc = explode(" - ", $trx['method']);
        $textInvoice = str_replace('[[payment_gateway]]', $gc[0], $textInvoice);
        $textInvoice = str_replace('[[payment_channel]]', $gc[1], $textInvoice);
        $textInvoice = str_replace('[[type]]', $trx['type'], $textInvoice);
        $textInvoice = str_replace('[[plan_name]]', $trx['plan_name'], $textInvoice);
        $textInvoice = str_replace('[[plan_price]]',  Lang::moneyFormat($trx['price']), $textInvoice);
        $textInvoice = str_replace('[[name]]', $cust['fullname'], $textInvoice);
        $textInvoice = str_replace('[[user_name]]', $trx['username'], $textInvoice);
        $textInvoice = str_replace('[[user_password]]', $cust['password'], $textInvoice);
        $textInvoice = str_replace('[[expired_date]]', Lang::dateAndTimeFormat($trx['expiration'], $trx['time']), $textInvoice);
        $textInvoice = str_replace('[[footer]]', $config['note'], $textInvoice);

        if ($config['user_notification_payment'] == 'sms') {
            Message::sendSMS($cust['phonenumber'], $textInvoice);
        } else if ($config['user_notification_payment'] == 'wa') {
            Message::sendWhatsapp($cust['phonenumber'], $textInvoice);
        }
    }
}
