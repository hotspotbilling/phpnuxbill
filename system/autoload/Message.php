<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use PEAR2\Net\RouterOS;

require $root_path . 'system/autoload/mail/Exception.php';
require $root_path . 'system/autoload/mail/PHPMailer.php';
require $root_path . 'system/autoload/mail/SMTP.php';

class Message
{

    public static function sendTelegram($txt, $chat_id = null, $topik = '')
    {
        global $config;
        run_hook('send_telegram', [$txt, $chat_id, $topik]); #HOOK
        if (!empty($config['telegram_bot'])) {
            if (empty($chat_id)) {
                $chat_id = $config['telegram_target_id'];
            }
            if (!empty($topik)) {
                $topik = "message_thread_id=$topik&";
            }
            return Http::getData('https://api.telegram.org/bot' . $config['telegram_bot'] . '/sendMessage?'.$topik.'chat_id=' . $chat_id . '&text=' . urlencode($txt));
        }
    }


    public static function sendSMS($phone, $txt)
    {
        global $config;
        if (empty($txt)) {
            return "";
        }
        run_hook('send_sms', [$phone, $txt]); #HOOK
        if (!empty($config['sms_url'])) {
            if (strlen($config['sms_url']) > 4 && substr($config['sms_url'], 0, 4) != "http") {
                if (strlen($txt) > 160) {
                    $txts = str_split($txt, 160);
                    try {
                        foreach ($txts as $txt) {
                            self::sendSMS($config['sms_url'], $phone, $txt);
                            self::logMessage('SMS', $phone, $txt, 'Success');
                        }
                    } catch (Throwable $e) {
                        // ignore, add to logs
                        self::logMessage('SMS', $phone, $txt, 'Error', $e->getMessage());
                    }
                } else {
                    try {
                        self::MikrotikSendSMS($config['sms_url'], $phone, $txt);
                        self::logMessage('MikroTikSMS', $phone, $txt, 'Success');
                    } catch (Throwable $e) {
                        // ignore, add to logs
                        self::logMessage('MikroTikSMS', $phone, $txt, 'Error', $e->getMessage());
                    }
                }
            } else {
                $smsurl = str_replace('[number]', urlencode($phone), $config['sms_url']);
                $smsurl = str_replace('[text]', urlencode($txt), $smsurl);
                try {
                    $response = Http::getData($smsurl);
                    self::logMessage('SMS HTTP Response', $phone, $txt, 'Success', $response);
                    return $response;
                } catch (Throwable $e) {
                    self::logMessage('SMS HTTP Request', $phone, $txt, 'Error', $e->getMessage());
                }
            }
        }
    }

    public static function MikrotikSendSMS($router_name, $to, $message)
    {
        global $_app_stage, $client_m, $config;
        if ($_app_stage == 'demo') {
            return null;
        }
        if (!isset($client_m)) {
            $mikrotik = ORM::for_table('tbl_routers')->where('name', $router_name)->find_one();
            $iport = explode(":", $mikrotik['ip_address']);
            $client_m = new RouterOS\Client($iport[0], $mikrotik['username'], $mikrotik['password'], ($iport[1]) ? $iport[1] : null);
        }
        if (empty($config['mikrotik_sms_command'])) {
            $config['mikrotik_sms_command'] = "/tool sms send";
        }
        $smsRequest = new RouterOS\Request($config['mikrotik_sms_command']);
        $smsRequest
            ->setArgument('phone-number', $to)
            ->setArgument('message', $message);
        $client_m->sendSync($smsRequest);
    }

    public static function sendWhatsapp($phone, $txt)
    {
        global $config;
        if (empty($txt)) {
            return "kosong";
        }

        run_hook('send_whatsapp', [$phone, $txt]); // HOOK

        if (!empty($config['wa_url'])) {
            $waurl = str_replace('[number]', urlencode(Lang::phoneFormat($phone)), $config['wa_url']);
            $waurl = str_replace('[text]', urlencode($txt), $waurl);

            try {
                $response = Http::getData($waurl);
                self::logMessage('WhatsApp HTTP Response', $phone, $txt, 'Success', $response);
                return $response;
            } catch (Throwable $e) {
                self::logMessage('WhatsApp HTTP Request', $phone, $txt, 'Error', $e->getMessage());
            }
        }
    }

    public static function sendEmail($to, $subject, $body)
    {
        global $config, $PAGES_PATH, $debug_mail;
        if (empty($body)) {
            return "";
        }
        if (empty($to)) {
            return "";
        }
        run_hook('send_email', [$to, $subject, $body]); #HOOK
        self::logMessage('Email', $to, $body, 'Success');
        if (empty($config['smtp_host'])) {
            $attr = "";
            if (!empty($config['mail_from'])) {
                $attr .= "From: " . $config['mail_from'] . "\r\n";
            }
            if (!empty($config['mail_reply_to'])) {
                $attr .= "Reply-To: " . $config['mail_reply_to'] . "\r\n";
            }
            mail($to, $subject, $body, $attr);
        } else {
            $mail = new PHPMailer();
            $mail->isSMTP();
            if (isset($debug_mail) && $debug_mail == 'Dev') {
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            }
            $mail->Host = $config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtp_user'];
            $mail->Password = $config['smtp_pass'];
            $mail->SMTPSecure = $config['smtp_ssltls'];
            $mail->Port = $config['smtp_port'];
            if (!empty($config['mail_from'])) {
                $mail->setFrom($config['mail_from']);
            }
            if (!empty($config['mail_reply_to'])) {
                $mail->addReplyTo($config['mail_reply_to']);
            }

            $mail->addAddress($to);
            $mail->Subject = $subject;

            if (!file_exists($PAGES_PATH . DIRECTORY_SEPARATOR . 'Email.html')) {
                if (!copy($PAGES_PATH . '_template' . DIRECTORY_SEPARATOR . 'Email.html', $PAGES_PATH . DIRECTORY_SEPARATOR . 'Email.html')) {
                    file_put_contents($PAGES_PATH . DIRECTORY_SEPARATOR . 'Email.html', Http::getData('https://raw.githubusercontent.com/hotspotbilling/phpnuxbill/master/pages_template/Email.html'));
                }
            }

            if (file_exists($PAGES_PATH . DIRECTORY_SEPARATOR . 'Email.html')) {
                $html = file_get_contents($PAGES_PATH . DIRECTORY_SEPARATOR . 'Email.html');
                $html = str_replace('[[Subject]]', $subject, $html);
                $html = str_replace('[[Company_Address]]', nl2br($config['address']), $html);
                $html = str_replace('[[Company_Name]]', nl2br($config['CompanyName']), $html);
                $html = str_replace('[[Body]]', nl2br($body), $html);
                $mail->isHTML(true);
                $mail->Body = $html;
            } else {
                $mail->isHTML(false);
                $mail->Body = $body;
            }
            if (!$mail->send()) {
                $errorMessage = Lang::T("Email not sent, Mailer Error: ") . $mail->ErrorInfo;
                self::logMessage('Email', $to, $body, 'Error', $errorMessage);
            } else {
                self::logMessage('Email', $to, $body, 'Success');
            }

            //<p style="font-family: Helvetica, sans-serif; font-size: 16px; font-weight: normal; margin: 0; margin-bottom: 16px;">
        }
    }

    public static function sendPackageNotification($customer, $package, $price, $message, $via)
    {
        global $ds, $config;
        if (empty($message)) {
            return "";
        }
        $msg = str_replace('[[name]]', $customer['fullname'], $message);
        $msg = str_replace('[[username]]', $customer['username'], $msg);
        $msg = str_replace('[[plan]]', $package, $msg);
        $msg = str_replace('[[package]]', $package, $msg);
        $msg = str_replace('[[price]]', Lang::moneyFormat($price), $msg);
        // Calculate bills and additional costs
        list($bills, $add_cost) = User::getBills($customer['id']);

        // Initialize note and total variables
        $note = "";
        $total = $price;

        // Add bills to the note if there are any additional costs
        if ($add_cost != 0) {
            foreach ($bills as $k => $v) {
                $note .= $k . " : " . Lang::moneyFormat($v) . "\n";
            }
            $total += $add_cost;
        }

        // Calculate tax
        $tax = 0;
        $tax_enable = isset($config['enable_tax']) ? $config['enable_tax'] : 'no';
        if ($tax_enable === 'yes') {
            $tax_rate_setting = isset($config['tax_rate']) ? $config['tax_rate'] : null;
            $custom_tax_rate = isset($config['custom_tax_rate']) ? (float) $config['custom_tax_rate'] : null;

            $tax_rate = ($tax_rate_setting === 'custom') ? $custom_tax_rate : $tax_rate_setting;
            $tax = Package::tax($price, $tax_rate);

            if ($tax != 0) {
                $note .= "Tax : " . Lang::moneyFormat($tax) . "\n";
                $total += $tax;
            }
        }

        // Add total to the note
        $note .= "Total : " . Lang::moneyFormat($total) . "\n";

        // Replace placeholders in the message
        $msg = str_replace('[[bills]]', $note, $msg);

        if ($ds) {
            $msg = str_replace('[[expired_date]]', Lang::dateAndTimeFormat($ds['expiration'], $ds['time']), $msg);
        } else {
            $msg = str_replace('[[expired_date]]', "", $msg);
        }

        if (strpos($msg, '[[payment_link]]') !== false) {
            // token only valid for 1 day, for security reason
            $token = User::generateToken($customer['id'], 1);
            if (!empty($token['token'])) {
                $tur = ORM::for_table('tbl_user_recharges')
                    ->where('customer_id', $customer['id'])
                    ->where('namebp', $package)
                    ->find_one();
                if ($tur) {
                    $url = '?_route=home&recharge=' . $tur['id'] . '&uid=' . urlencode($token['token']);
                    $msg = str_replace('[[payment_link]]', $url, $msg);
                }
            } else {
                $msg = str_replace('[[payment_link]]', '', $msg);
            }
        }


        if (
            !empty($customer['phonenumber']) && strlen($customer['phonenumber']) > 5
            && !empty($message) && in_array($via, ['sms', 'wa'])
        ) {
            if ($via == 'sms') {
                Message::sendSMS($customer['phonenumber'], $msg);
            } else if ($via == 'email') {
                self::sendEmail($customer['email'], '[' . $config['CompanyName'] . '] ' . Lang::T("Internet Plan Reminder"), $msg);
            } else if ($via == 'wa') {
                Message::sendWhatsapp($customer['phonenumber'], $msg);
            }
        }
        return "$via: $msg";
    }

    public static function sendBalanceNotification($cust, $target, $balance, $balance_now, $message, $via)
    {
        global $config;
        $msg = str_replace('[[name]]', $target['fullname'] . ' (' . $target['username'] . ')', $message);
        $msg = str_replace('[[current_balance]]', Lang::moneyFormat($balance_now), $msg);
        $msg = str_replace('[[balance]]', Lang::moneyFormat($balance), $msg);
        $phone = $cust['phonenumber'];
        if (
            !empty($phone) && strlen($phone) > 5
            && !empty($message) && in_array($via, ['sms', 'wa', 'email'])
        ) {
            if ($via == 'sms') {
                Message::sendSMS($phone, $msg);
            } else if ($via == 'email') {
                self::sendEmail($cust['email'], '[' . $config['CompanyName'] . '] ' . Lang::T("Balance Notification"), $msg);
            } else if ($via == 'wa') {
                Message::sendWhatsapp($phone, $msg);
            }
            self::addToInbox($cust['id'], Lang::T('Balance Notification'), $msg);
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
        $textInvoice = str_replace('[[trx_date]]', Lang::dateAndTimeFormat($trx['recharged_on'], $trx['recharged_time']), $textInvoice);
        if (!empty($trx['note'])) {
            $textInvoice = str_replace('[[note]]', $trx['note'], $textInvoice);
        }
        $gc = explode("-", $trx['method']);
        $textInvoice = str_replace('[[payment_gateway]]', trim($gc[0]), $textInvoice);
        $textInvoice = str_replace('[[payment_channel]]', trim($gc[1]), $textInvoice);
        $textInvoice = str_replace('[[type]]', $trx['type'], $textInvoice);
        $textInvoice = str_replace('[[plan_name]]', $trx['plan_name'], $textInvoice);
        $textInvoice = str_replace('[[plan_price]]', Lang::moneyFormat($trx['price']), $textInvoice);
        $textInvoice = str_replace('[[name]]', $cust['fullname'], $textInvoice);
        $textInvoice = str_replace('[[note]]', $cust['note'], $textInvoice);
        $textInvoice = str_replace('[[user_name]]', $trx['username'], $textInvoice);
        $textInvoice = str_replace('[[user_password]]', $cust['password'], $textInvoice);
        $textInvoice = str_replace('[[username]]', $trx['username'], $textInvoice);
        $textInvoice = str_replace('[[password]]', $cust['password'], $textInvoice);
        $textInvoice = str_replace('[[expired_date]]', Lang::dateAndTimeFormat($trx['expiration'], $trx['time']), $textInvoice);
        $textInvoice = str_replace('[[footer]]', $config['note'], $textInvoice);
		// Calculate bills and additional costs
        list($bills, $add_cost) = User::getBills($cust['id']);

        // Initialize note and total variables
        $note = "";
        $total = $trx['price'];

        // Add bills to the note if there are any additional costs
        if ($add_cost != 0) {
            foreach ($bills as $k => $v) {
                $note .= $k . " : " . Lang::moneyFormat($v) . "\n";
            }
            $total += $add_cost;
        }

        // Calculate tax
        $tax = 0;
        $tax_enable = isset($config['enable_tax']) ? $config['enable_tax'] : 'no';
        if ($tax_enable === 'yes') {
            $tax_rate_setting = isset($config['tax_rate']) ? $config['tax_rate'] : null;
            $custom_tax_rate = isset($config['custom_tax_rate']) ? (float) $config['custom_tax_rate'] : null;

            $tax_rate = ($tax_rate_setting === 'custom') ? $custom_tax_rate : $tax_rate_setting;
            $tax = Package::tax($trx['price'], $tax_rate);

            if ($tax != 0) {
                $note .= "Tax : " . Lang::moneyFormat($tax) . "\n";
                $total += $tax;
            }
        }

        // Add total to the note
        $note .= "Total : " . Lang::moneyFormat($total) . "\n";

		// Replace placeholders in the message
        $textInvoice = str_replace('[[bills]]', $note, $textInvoice);

        if ($config['user_notification_payment'] == 'sms') {
            Message::sendSMS($cust['phonenumber'], $textInvoice);
        } else if ($config['user_notification_payment'] == 'email') {
            self::sendEmail($cust['email'], '[' . $config['CompanyName'] . '] ' . Lang::T("Invoice") . ' #' . $trx['invoice'], $textInvoice);
        } else if ($config['user_notification_payment'] == 'wa') {
            Message::sendWhatsapp($cust['phonenumber'], $textInvoice);
        }
    }


    public static function addToInbox($to_customer_id, $subject, $body, $from = 'System')
    {
        $user = User::find($to_customer_id);
        try {
            $v = ORM::for_table('tbl_customers_inbox')->create();
            $v->from = $from;
            $v->customer_id = $to_customer_id;
            $v->subject = $subject;
            $v->date_created = date('Y-m-d H:i:s');
            $v->body = nl2br($body);
            $v->save();
            self::logMessage("Inbox", $user->username, $body, "Success");
        } catch (Throwable $e) {
            $errorMessage = Lang::T("Error adding message to inbox: " . $e->getMessage());
            self::logMessage('Inbox', $user->username, $body, 'Error', $errorMessage);
        }
    }

    public static function getMessageType($type, $message){
        if(strpos($message, "<divider>") === false){
            return $message;
        }
        $msgs = explode("<divider>", $message);
        if($type == "PPPOE"){
            return $msgs[1];
        }else{
            return $msgs[0];
        }
    }

    public static function logMessage($messageType, $recipient, $messageContent, $status, $errorMessage = null)
    {
        $log = ORM::for_table('tbl_message_logs')->create();
        $log->message_type = $messageType;
        $log->recipient = $recipient;
        $log->message_content = $messageContent;
        $log->status = $status;
        $log->error_message = $errorMessage;
        $log->save();
    }
}
