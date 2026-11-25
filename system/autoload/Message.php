<?php


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use PEAR2\Net\RouterOS;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;


require "{$root_path}system/autoload/mail/Exception.php";
require "{$root_path}system/autoload/mail/PHPMailer.php";
require "{$root_path}system/autoload/mail/SMTP.php";

class Message
{

    /** $timeout: to connect and to wait for Telegram's answer. Below 100 it is seconds, 100 and up milliseconds (Http::timeoutMs); the default 3000 is milliseconds */
    public static function sendTelegram($txt, $chat_id = null, $topik = '', $timeout = 3000, $runHook = true)
    {
        global $config;
        // converted to milliseconds once, here: a bare 15 is 15 seconds, 3000 and up is already milliseconds
        $timeout = Http::timeoutMs($timeout);
        // Answering FreeRADIUS, from the middle of a login (no budget set by radius.php: a voucher's purchase alert, say): the message is
        // handed to radius.php's alert queue, which sends it once the answer is out. Neither the send_telegram hook (a plugin's code,
        // which PHP cannot time-limit) nor the request runs while the router waits.
        // (a notification, not an error: it is not written to the RADIUS REST log, and it is not limited to one in five minutes)
        if (!empty($GLOBALS['radius_request']) && !isset($GLOBALS['radius_alert_budget_ms']) && empty($chat_id) && $topik === ''
            && function_exists('radius_notify')) {
            radius_notify((string) $txt);
            return false;
        }
        if (!empty($GLOBALS['radius_request']) && !isset($GLOBALS['radius_alert_budget_ms'])) {
            $left = (int) floor((3.5 - (microtime(true) - (float) ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true)))) * 1000);
            if ($left < 500) {
                return false;
            }
        } elseif (!empty($GLOBALS['radius_request']) && (int) $GLOBALS['radius_alert_budget_ms'] < 500) {
            return false;
        }
        $hookStart = microtime(true);
        // a plugin's code cannot be time-limited: never run it while the router waits (before radius.php's post-response budget is set)
        if ($runHook && (empty($GLOBALS['radius_request']) || isset($GLOBALS['radius_alert_budget_ms']))) {
            run_hook('send_telegram', [$txt, $chat_id, $topik]); #HOOK
        }
        $hookMs = (int) ((microtime(true) - $hookStart) * 1000);
        // while answering FreeRADIUS (radius.php) the router is waiting: 3 seconds at most, whatever the caller asked for
        // (a voucher login's purchase alert comes from Package::rechargeUser, in the middle of the login)
        if (!empty($GLOBALS['radius_request'])) {
            // milliseconds. Without a budget set by radius.php (an alert sent in the middle of a login, e.g. a voucher's purchase
            // alert) it is what is left of the 3.5 s FreeRADIUS waits, counted from the start of the request: the send_telegram
            // hook above has already used part of it, so one deadline covers both
            if (isset($GLOBALS['radius_alert_budget_ms'])) {
                // the budget was worked out before the hook ran: what the hook used is taken off it
                $budget = (int) $GLOBALS['radius_alert_budget_ms'] - $hookMs;
            } else {
                $elapsed = microtime(true) - (float) ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
                $budget = (int) floor((3.5 - $elapsed) * 1000);
            }
            // never more than 3 s; 0 or less would mean no limit at all. Under 500 ms left nothing usable can be sent
            $timeout = min($timeout, $budget, 3000);
            if ($timeout < 500) {
                return false;
            }
        }
        if (!empty($config['telegram_bot'])) {
            if (empty($chat_id)) {
                $chat_id = $config['telegram_target_id'];
            }
            if (!empty($topik)) {
                $topik = "message_thread_id=$topik&";
            }
            // one conversion to milliseconds, here (a caller's seconds, or the milliseconds of the RADIUS budget above)
            $ms = ['ms' => $timeout]; // already milliseconds
            return Http::getData('https://api.telegram.org/bot' . $config['telegram_bot'] . '/sendMessage?' . $topik . 'chat_id=' . $chat_id . '&text=' . urlencode($txt), [], $ms, $ms);
        }
    }


    /** Returns the gateway's answer (or true) when the message was handed over, false when it was not. $timeout: seconds for an HTTP gateway */
    public static function sendSMS($phone, $txt, $timeout = null)
    {
        global $config;
        // the default depends on the transport: 30 seconds for a router (MikrotikSendSMS's own), 3000 ms for an HTTP gateway
        $routerSms = !empty($config['sms_url']) && strlen($config['sms_url']) > 4 && substr($config['sms_url'], 0, 4) != "http";
        if ($timeout === null) {
            $timeout = $routerSms ? 30 : 3000;
        }
        if (empty($txt)) {
            return false;
        }
        run_hook('send_sms', [$phone, $txt]); #HOOK
        if (empty($config['sms_url'])) {
            return false;
        }
        if (strlen($config['sms_url']) > 4 && substr($config['sms_url'], 0, 4) != "http") {
            // a Mikrotik router sends it; long texts go out in parts of 160 characters (each part is logged by its own call)
            if (strlen($txt) > 160) {
                $ok = true;
                foreach (str_split($txt, 160) as $part) {
                    $ok = (bool) self::sendSMS($phone, $part, $timeout) && $ok;
                    if (!$ok) {
                        break; // a part that failed (a timeout leaves it unknown): the rest is not sent after it
                    }
                }
                return $ok;
            }
            try {
                if (self::MikrotikSendSMS($config['sms_url'], $phone, $txt, $timeout) !== true) {
                    return false; // Demo mode: nothing was sent, nothing is logged as sent
                }
                self::logMessage('MikroTikSMS', $phone, $txt, 'Success');
                return true;
            } catch (Throwable $e) {
                // 408: the router did not answer in time; the message may have been sent, so it is never retried here
                $unknown = $e->getCode() === 408 ? ' (' . Lang::T('delivery unknown, not resent') . ')' : '';
                self::logMessage('MikroTikSMS', $phone, $txt, 'Error', $e->getMessage() . $unknown);
                return false;
            }
        }
        $smsurl = str_replace('[number]', urlencode($phone), $config['sms_url']);
        $smsurl = str_replace('[text]', urlencode($txt), $smsurl);
        try {
            $ms = ['ms' => Http::timeoutMs($timeout)]; // converted once; passed on as milliseconds
            $response = Http::getData($smsurl, [], $ms, $ms, $httpError, $httpStatus);
            if ($httpError === '' && ($httpStatus < 200 || $httpStatus > 299)) {
                $httpError = 'HTTP ' . $httpStatus; // the gateway did not accept the message: any status but 2xx (an empty 204 is fine)
            }
            if ($httpError !== '') {
                // the gateway was not reached or did not answer in time: the text is cURL's error, not the gateway's answer
                self::logMessage('SMS HTTP Request', $phone, $txt, 'Error', $httpError);
                return false;
            }
            self::logMessage('SMS HTTP Response', $phone, $txt, 'Success', $response);
            return ($response !== '' && $response !== false && $response !== null) ? $response : true;
        } catch (Throwable $e) {
            self::logMessage('SMS HTTP Request', $phone, $txt, 'Error', $e->getMessage());
            return false;
        }
    }

    /** $timeout: seconds the router may take to connect and to answer the command (at most 60) */
    public static function MikrotikSendSMS($router_name, $to, $message, $timeout = 30)
    {
        global $_app_stage, $client_m, $client_m_limit, $client_m_router, $config;
        if ($_app_stage == 'demo') {
            return null;
        }
        $limit = max(1, min(60, (int) $timeout));
        // the connection is kept for the next message, but only with the same router and time limit: a call with another one connects again
        if (isset($client_m) && ($client_m_limit !== $limit || $client_m_router !== $router_name)) {
            method_exists($client_m, 'abort') ? $client_m->abort() : null;
            $client_m = null;
        }
        if (!isset($client_m)) {
            $mikrotik = ORM::for_table('tbl_routers')->where('name', $router_name)->find_one();
            // every read time-limited, so a router that takes the login but never answers the command cannot hold the caller
            [$host, $port] = RouterAddress::split($mikrotik['ip_address']);
            $client_m = RouterApi::deviceClient($host, $mikrotik['username'], $mikrotik['password'], $port, $limit, $limit);
            $client_m_limit = $limit;
            $client_m_router = $router_name;
        }
        if (empty($config['mikrotik_sms_command'])) {
            $config['mikrotik_sms_command'] = "/tool sms send";
        }
        $smsRequest = new RouterOS\Request($config['mikrotik_sms_command']);
        $smsRequest
            ->setArgument('phone-number', $to)
            ->setArgument('message', $message);
        try {
            $res = $client_m->sendSync($smsRequest);
        } catch (Throwable $e) {
            // the connection was dropped: the next message connects again. Abort it so a late reply can never reach it. On a
            // 408 the router may still have sent the SMS: delivery is unknown, so the caller must not send it again
            try {
                method_exists($client_m, 'abort') ? $client_m->abort() : null;
            } catch (Throwable $ignored) {
            }
            $client_m = null;
            throw $e;
        }
        // the router answered but refused the command (!trap): the connection is fine, the message was not sent
        if (($err = RouterApi::error($res)) !== null) {
            throw new \Exception($err, 403);
        }
        return true; // confirmed by the router (null above: Demo mode, nothing sent)
    }

    /** Returns the gateway's answer (or true) when the message was handed over, false when it was not. $timeout: seconds for the gateway */
    public static function sendWhatsapp($phone, $txt, $timeout = 3000)
    {
        global $config;
        if (empty($txt)) {
            return false;
        }

        run_hook('send_whatsapp', [$phone, $txt]); // HOOK

        if (empty($config['wa_url'])) {
            return false;
        }
        $waurl = str_replace('[number]', urlencode(Lang::phoneFormat($phone)), $config['wa_url']);
        $waurl = str_replace('[text]', urlencode($txt), $waurl);

        try {
            $ms = ['ms' => Http::timeoutMs($timeout)]; // converted once; passed on as milliseconds
            $response = Http::getData($waurl, [], $ms, $ms, $httpError, $httpStatus);
            if ($httpError === '' && ($httpStatus < 200 || $httpStatus > 299)) {
                $httpError = 'HTTP ' . $httpStatus; // the gateway did not accept the message: any status but 2xx (an empty 204 is fine)
            }
            if ($httpError !== '') {
                // the gateway was not reached or did not answer in time: the text is cURL's error, not the gateway's answer
                self::logMessage('WhatsApp HTTP Request', $phone, $txt, 'Error', $httpError);
                return false;
            }
            self::logMessage('WhatsApp HTTP Response', $phone, $txt, 'Success', $response);
            return ($response !== '' && $response !== false && $response !== null) ? $response : true;
        } catch (Throwable $e) {
            self::logMessage('WhatsApp HTTP Request', $phone, $txt, 'Error', $e->getMessage());
            return false;
        }
    }

    public static function sendEmail($to, $subject, $body, $attachmentPath = null)
    {
        global $config, $PAGES_PATH, $debug_mail;
        if (empty($body) || empty($to)) {
            return false;
        }
        run_hook('send_email', [$to, $subject, $body]); #HOOK
        if (empty($config['smtp_host'])) {
            $attr = "";
            if (!empty($config['mail_from'])) {
                $attr .= "From: " . $config['mail_from'] . "\r\n";
            }
            if (!empty($config['mail_reply_to'])) {
                $attr .= "Reply-To: " . $config['mail_reply_to'] . "\r\n";
            }
            $sent = mail($to, $subject, $body, $attr);
            self::logMessage('Email', $to, $body, $sent ? 'Success' : 'Error', $sent ? null : 'mail() refused the message');
            return (bool) $sent;
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
            // Attachments
            if (!empty($attachmentPath)) {
                $mail->addAttachment($attachmentPath);
            }

            if (!file_exists($PAGES_PATH . DIRECTORY_SEPARATOR . 'Email.html')) {
                if (!@copy($PAGES_PATH . '_template' . DIRECTORY_SEPARATOR . 'Email.html', $PAGES_PATH . DIRECTORY_SEPARATOR . 'Email.html')) {
                    $tpl = Http::getData('https://raw.githubusercontent.com/hotspotbilling/phpnuxbill/master/pages_template/Email.html', [], 3000, 15000, $tplError, $tplStatus);
                    // cached only when it really came: an error text or error page must never become the template
                    if ($tplError === '' && $tplStatus >= 200 && $tplStatus <= 299 && is_string($tpl) && $tpl !== '') {
                        file_put_contents($PAGES_PATH . DIRECTORY_SEPARATOR . 'Email.html', $tpl);
                    }
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
                $mail->Body = $html;
            } else {
                $mail->isHTML(false);
                $mail->Body = $body;
            }
            if (!$mail->send()) {
                $errorMessage = Lang::T("Email not sent, Mailer Error: ") . $mail->ErrorInfo;
                self::logMessage('Email', $to, $body, 'Error', $errorMessage);
                return false;
            } else {
                self::logMessage('Email', $to, $body, 'Success');
                return true;
            }

            //<p style="font-family: Helvetica, sans-serif; font-size: 16px; font-weight: normal; margin: 0; margin-bottom: 16px;">
        }
    }

    /** Whether the last sendPackageNotification() handed its message over (false when the channel refused or failed, or nothing was sent) */
    public static $lastDelivered = false;
    /** True when the last push had nobody to send to (no subscriptions): nothing was delivered, and there is nothing to retry */
    public static $lastNothingToSend = false;

    public static function sendPackageNotification($customer, $package, $price, $message, $via)
    {
        self::$lastDelivered = false;
        self::$lastNothingToSend = false;
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
            && !empty($message) && in_array($via, ['sms', 'wa', 'email', 'inbox', 'push'])
        ) {
            try {
                $sent = false;
                switch ($via) {
                    case 'sms':
                        $sent = Message::sendSMS($customer['phonenumber'], $msg);
                        break;
                    case 'email':
                        $sent = self::sendEmail($customer['email'], '[' . $config['CompanyName'] . '] ' . Lang::T("Internet Plan Reminder"), $msg);
                        break;
                    case 'wa':
                        $sent = Message::sendWhatsapp($customer['phonenumber'], $msg);
                        break;
                    case 'inbox':
                        $sent = Message::addToInbox($customer['id'], 'Package Expiry Notification', $msg);
                        break;
                    case 'push':
                        $r = Message::sendPush($customer['id'], 'Package Expiry Notification', $msg, $url ?? null);
                        // delivered only when something was really sent: no subscriptions (sent 0) or all failed is not delivered
                        $sent = is_array($r) ? (!empty($r['success']) && (int) ($r['sent'] ?? 0) > 0) : (bool) $r;
                        self::$lastNothingToSend = is_array($r) && !empty($r['success']) && (int) ($r['sent'] ?? 0) === 0 && (int) ($r['failed'] ?? 0) === 0;
                        break;
                }
                self::$lastDelivered = $sent !== false && $sent !== null;
                return "$via: $msg";
            } catch (Exception $e) {
                _log('Error sending message: ' . $e->getMessage());
                return "Failed to send $via: " . $e->getMessage();
            }
        }
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
            && !empty($message) && in_array($via, ['sms', 'wa', 'email', 'inbox', 'push'])
        ) {
            switch ($via) {
                case 'sms':
                    Message::sendSMS($phone, $msg);
                    break;
                case 'email':
                    self::sendEmail($cust['email'], '[' . $config['CompanyName'] . '] ' . Lang::T("Balance Notification"), $msg);
                    break;
                case 'wa':
                    Message::sendWhatsapp($phone, $msg);
                    break;
                case 'push':
                    self::sendPush($cust['id'],  Lang::T("Balance Notification"), $msg, APP_URL);
                    break;

            }
            self::addToInbox($cust['id'], Lang::T('Balance Notification'), $msg);
        }
        return "$via: $msg";
    }

    public static function sendInvoice($cust, $trx)
    {
        global $config, $db_pass;
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

        $inv_url = "?_route=voucher/invoice/$trx[id]/" . md5($trx['id'] . $db_pass);
        $textInvoice = str_replace('[[invoice_link]]', $inv_url, $textInvoice);

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

        if ($config['user_notification_payment_sms']) {
            try {
                Message::sendSMS($cust['phonenumber'], $textInvoice);
            } catch (Exception $e) {
                _log('Error sending SMS: ' . $e->getMessage());
            }
        }

        if ($config['user_notification_payment_email']) {
            try {
                self::sendEmail($cust['email'], '[' . $config['CompanyName'] . '] ' . Lang::T("Invoice") . ' #' . $trx['invoice'], $textInvoice);
            } catch (Exception $e) {
                _log('Error sending email: ' . $e->getMessage());
            }
        }

        if ($config['user_notification_payment_wa']) {
            try {
                Message::sendWhatsapp($cust['phonenumber'], $textInvoice);
            } catch (Exception $e) {
                _log('Error sending WhatsApp message: ' . $e->getMessage());
            }
        }

        if ($config['user_notification_payment_inbox']) {
            try {
                // Assuming you meant to send a message to the inbox
                Message::addToInbox($cust['id'], '[' . $config['CompanyName'] . '] ' . Lang::T("Invoice") . ' #' . $trx['invoice'], $textInvoice);
            } catch (Exception $e) {
                _log('Error adding to inbox: ' . $e->getMessage());
            }
        }

        if ($config['user_notification_payment_push']) {
            try {
                self::sendPush($cust['id'],  Lang::T("Invoice") . ' #' . $trx['invoice'], $textInvoice, APP_URL);
            } catch (Exception $e) {
                _log('Error sending push notification: ' . $e->getMessage());
            }
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
            return true;
        } catch (Throwable $e) {
            $errorMessage = Lang::T("Error adding message to inbox: " . $e->getMessage());
            self::logMessage('Inbox', $user->username, $body, 'Error', $errorMessage);
            return false;
        }
    }

    public static function getMessageType($type, $message)
    {
        if (strpos($message, "<divider>") === false) {
            return $message;
        }
        $msgs = explode("<divider>", $message);
        return match ($type) {
            "PPPOE" => $msgs[1],
            default => $msgs[0]
        };
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


    /**
     * Generate VAPID keys for push notifications.
     *
     * @return array VAPID key pair
     */
    public static function pushKeys(): array
    {
        return VAPID::createVapidKeys();
    }

    /**
     * Send push notifications to subscribed users.
     *
     * @param int|null $customerId Customer ID to filter subscriptions (null for all)
     * @param string|null $title Custom title for the notification
     * @param string|null $body Custom body for the notification
     * @param string|null $url URL to open on notification click
     * @return array Result of the notification sending process
     */
    public static function sendPush(?int $customerId = null, ?string $title = null, ?string $body = null, ?string $url = null): array
    {
        global $config;

        // Validate configuration
        if (empty($config['push_public_key']) || empty($config['push_private_key'])) {
            $message = 'VAPID keys are not configured';
            _log($message);
            return ['success' => false, 'message' => $message];
        }

        // Set VAPID configuration
        $vapid = [
            'subject' => 'mailto:support@focuslinkstech.com.ng',
            'publicKey' => $config['push_public_key'],
            'privateKey' => $config['push_private_key']
        ];

        // Fetch subscriptions
        try {
            $query = ORM::for_table('tbl_push_subscriptions')
                ->select(['customer_id', 'endpoint', 'p256dh', 'auth']);

            if ($customerId !== null) {
                $query->where('customer_id', $customerId);
            }

            $subscriptions = $query->find_array();
            _log("Fetched subscriptions: " . json_encode($subscriptions));
        } catch (Exception $e) {
            $message = "Failed to fetch subscriptions: " . $e->getMessage();
            _log($message);
            return ['success' => false, 'message' => $message];
        }

        if (empty($subscriptions)) {
            return ['success' => true, 'message' => 'No matching subscriptions found'];
        }

        // Initialize WebPush
        $auth = ['VAPID' => $vapid];
        $webPush = new WebPush($auth);

        // Prepare notifications
        $defaultTitle = $title ?? 'New Customer Activity';
        $defaultBody = $body ?? 'A new customer action has been detected!';
        $defaultUrl = $url ?? APP_URL;

        foreach ($subscriptions as $sub) {
            try {
                $subscription = Subscription::create([
                    'endpoint' => $sub['endpoint'],
                    'publicKey' => $sub['p256dh'],
                    'authToken' => $sub['auth'],
                ]);
                $payload = json_encode([
                    'title' => $defaultTitle,
                    'body' => $defaultBody,
                    'url' => $defaultUrl,
                ]);
                $webPush->queueNotification($subscription, $payload);
            } catch (Exception $e) {
                $message = "Failed to queue notification for {$sub['endpoint']}: " . $e->getMessage();
                _log($message);
            }
        }

        // Send notifications in batch
        $results = ['success' => true, 'sent' => 0, 'failed' => 0, 'errors' => []];
        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getEndpoint();
            if ($report->isSuccess()) {
                _log("Notification sent successfully to {$endpoint}");
                $results['sent']++;
            } else {
                $error = "Notification failed to {$endpoint}: {$report->getReason()}";
                _log($error);
                $results['failed']++;
                $results['errors'][] = $error;
                if ($report->getReason() === 'Gone') {
                    ORM::for_table('tbl_push_subscriptions')
                        ->where('endpoint', $endpoint)
                        ->delete_many();
                    _log("Removed expired subscription: {$endpoint}");
                }
            }
        }

        return $results;
    }
}
