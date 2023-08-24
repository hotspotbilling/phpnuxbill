<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 **/

class Message
{

    public static function sendTelegram($txt)
    {
        global $config;
        run_hook('send_telegram'); #HOOK
        if (!empty($config['telegram_bot']) && !empty($config['telegram_target_id'])) {
            file_get_contents('https://api.telegram.org/bot' . $config['telegram_bot'] . '/sendMessage?chat_id=' . $config['telegram_target_id'] . '&text=' . urlencode($txt));
        }
    }


    public static function sendSMS($phone, $txt)
    {
        global $config;
        run_hook('send_sms'); #HOOK
        if (!empty($config['sms_url'])) {
            $smsurl = str_replace('[number]', urlencode($phone), $config['sms_url']);
            $smsurl = str_replace('[text]', urlencode($txt), $smsurl);
            file_get_contents($smsurl);
        }
    }

    public static function sendWhatsapp($phone, $txt)
    {
        global $config;
        run_hook('send_whatsapp'); #HOOK
        if (!empty($config['wa_url'])) {
            $waurl = str_replace('[number]', urlencode($phone), $config['wa_url']);
            $waurl = str_replace('[text]', urlencode($txt), $waurl);
            file_get_contents($waurl);
        }
    }

    public static function sendPackageNotification($phone, $name, $package, $message, $via)
    {
        $msg = str_replace('[[name]]', "*$name*", $message);
        $msg = str_replace('[[package]]', "*$package*", $msg);
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

    public static function sendBalanceNotification($phone, $name, $balance, $message, $via)
    {
        $msg = str_replace('[[name]]', "*$name*", $message);
        $msg = str_replace('[[balance]]', "*" . Lang::moneyFormat($balance) . "*", $msg);
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
}
