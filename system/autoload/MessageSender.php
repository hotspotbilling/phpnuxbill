<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * What the Send Message screens (one customer, a group, a ticked selection) have in common: who is in a group, how the
 * placeholders are filled in, and what each channel really did. Message:: does the sending itself.
 */
class MessageSender
{
    const CHANNELS = ['sms', 'wa', 'email', 'inbox', 'push'];
    const LABELS = ['sms' => 'SMS', 'wa' => 'WhatsApp', 'email' => 'Email', 'inbox' => 'Inbox', 'push' => 'Push'];
    const SERVICES = ['PPPoE', 'Hotspot', 'VPN'];
    const BATCH_SIZES = [5, 10, 15, 20, 30, 40, 50, 60, 100];

    /** The channels switched on in a request ("1", "on" or "true"), in a fixed order. */
    public static function channelsFrom(array $req)
    {
        $out = [];
        foreach (self::CHANNELS as $c) {
            if (!empty($req[$c]) && $req[$c] !== '0' && $req[$c] !== 'off' && $req[$c] !== 'false') {
                $out[] = $c;
            }
        }
        return $out;
    }

    public static function needsSubject(array $channels)
    {
        return (bool) array_intersect($channels, ['email', 'inbox', 'push']);
    }

    /**
     * One page of the customers in a group.
     *
     * @param string $group   all | new | expired | active
     * @param string $service all, or PPPoE | Hotspot | VPN
     * @param string $router  '' for every router, 'radius', or a router id
     * @return array [rows, total] - rows carry id, fullname, username, phonenumber, email, service_type; each customer once, in a fixed order
     */
    public static function recipients($group, $service, $router, $offset, $limit)
    {
        $routerName = '';
        if ($router !== '' && $router !== null) {
            if ($router === 'radius') {
                $routerName = 'Radius';
            } else {
                $r = ORM::for_table('tbl_routers')->find_one((int) $router);
                if (!$r) {
                    throw new InvalidArgumentException(Lang::T('Invalid router'));
                }
                $routerName = $r->name;
            }
        }
        $useService = in_array($service, self::SERVICES, true);
        // the recharge table is needed to look at a router, or at who is active or expired
        $needRecharge = $routerName !== '' || in_array($group, ['expired', 'active'], true);

        $from = 'FROM tbl_customers c' . ($needRecharge ? ' JOIN tbl_user_recharges r ON r.customer_id = c.id' : '');
        $where = [];
        $args = [];
        if ($routerName !== '') {
            $where[] = 'r.routers = ?';
            $args[] = $routerName;
        }
        if ($useService) {
            $where[] = ($needRecharge ? 'r.type' : 'c.service_type') . ' = ?';
            $args[] = $service;
        }
        switch ($group) {
            case 'new':
                $where[] = $routerName !== ''
                    ? 'DATE(r.recharged_on) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)'
                    : 'DATE(c.created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)';
                break;
            case 'expired':
                $where[] = "r.status = 'off'";
                break;
            case 'active':
                $where[] = "r.status = 'on'";
                break;
        }
        $w = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = (int) (ORM::for_table('tbl_customers')->raw_query("SELECT COUNT(DISTINCT c.id) AS n $from$w", $args)->find_one()->n ?? 0);
        $rows = ORM::for_table('tbl_customers')->raw_query(
            "SELECT DISTINCT c.id, c.fullname, c.username, c.phonenumber, c.email, c.service_type $from$w ORDER BY c.id LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset,
            $args
        )->find_array();
        return [$rows ?: [], $total, $routerName];
    }

    /**
     * Fill in the placeholders. Customers type their own name when they register, so for HTML channels (email, inbox) the values are
     * escaped; the subject only loses angle brackets because it is also a mail header.
     *
     * @param string $kind plain | html | subject
     */
    public static function fill($text, array $c, $kind = 'plain')
    {
        global $config;
        $vals = [
            '[[name]]' => (string) ($c['fullname'] ?? ''),
            '[[user_name]]' => (string) ($c['username'] ?? ''),
            '[[phone]]' => (string) ($c['phonenumber'] ?? ''),
            '[[company_name]]' => (string) ($config['CompanyName'] ?? ''),
        ];
        foreach ($vals as $k => $v) {
            if ($kind === 'html') {
                $vals[$k] = htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
            } elseif ($kind === 'subject') {
                $vals[$k] = str_replace(['<', '>', "\r", "\n"], '', $v);
            }
        }
        if (strpos($text, '[[payment_link]]') !== false) {
            $vals['[[payment_link]]'] = self::paymentLink((int) ($c['id'] ?? 0));
        }
        return strtr($text, $vals);
    }

    /** A link that opens the customer's recharge (the active one first) after signing them in; their home page when they have none. */
    public static function paymentLink($customerId)
    {
        $home = APP_URL . '/?_route=home';
        if ($customerId < 1) {
            return $home;
        }
        $tur = ORM::for_table('tbl_user_recharges')->where('customer_id', $customerId)->order_by_expr("status = 'on' DESC")->order_by_desc('id')->find_one();
        $token = User::generateToken($customerId, 1);
        if (!$tur || empty($token['token'])) {
            return $home;
        }
        return $home . '&recharge=' . $tur['id'] . '&uid=' . urlencode($token['token']);
    }

    /**
     * Send one message to one customer on the chosen channels.
     *
     * @return array one entry per channel: channel, label, to, result (sent|failed|skipped), status (text), message
     */
    public static function deliver(array $c, array $channels, $subject, $message)
    {
        $plain = self::fill($message, $c, 'plain');
        $html = self::fill($message, $c, 'html');
        $subj = self::fill($subject, $c, 'subject');
        // an empty phone is stored as '0' and an empty email as '1', so a real number needs a few digits
        $phone = preg_replace('/\D/', '', (string) ($c['phonenumber'] ?? ''));
        $phone = strlen($phone) >= 5 ? $phone : '';
        $email = trim((string) ($c['email'] ?? ''));
        $id = (int) $c['id'];
        $out = [];
        foreach ($channels as $ch) {
            $to = $ch === 'email' ? $email : ($ch === 'sms' || $ch === 'wa' ? $c['phonenumber'] : $c['username']);
            $result = 'failed';
            $note = '';
            try {
                switch ($ch) {
                    case 'sms':
                    case 'wa':
                        if ($phone === '') {
                            $result = 'skipped';
                            $note = Lang::T('No phone number');
                        } else {
                            $result = ($ch === 'sms' ? Message::sendSMS($c['phonenumber'], $plain) : Message::sendWhatsapp($c['phonenumber'], $plain)) ? 'sent' : 'failed';
                        }
                        break;
                    case 'email':
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $result = 'skipped';
                            $note = Lang::T('No email address');
                        } else {
                            $result = Message::sendEmail($email, $subj, $html) ? 'sent' : 'failed';
                        }
                        break;
                    case 'inbox':
                        $result = Message::addToInbox($id, $subj, $html, 'Admin') ? 'sent' : 'failed';
                        break;
                    case 'push':
                        $r = Message::sendPush($id, $subj, $plain, APP_URL);
                        if (empty($r['success'])) {
                            $result = 'failed';
                            $note = (string) ($r['message'] ?? '');
                        } elseif (!empty($r['sent'])) {
                            $result = 'sent';
                        } else {
                            $result = 'skipped';
                            $note = Lang::T('No push subscription');
                        }
                        break;
                }
            } catch (Throwable $e) {
                $result = 'failed';
                $note = $e->getMessage();
            }
            $label = self::LABELS[$ch];
            $out[] = [
                'channel' => $label,
                'to' => (string) $to,
                'result' => $result,
                'status' => $label . ' ' . ($result === 'sent' ? 'Sent' : ($result === 'failed' ? 'Failed' : 'Skipped')) . ($note !== '' ? ' (' . $note . ')' : ''),
                'message' => $plain,
            ];
        }
        return $out;
    }

    /** Count a list of deliver() entries: [sent, failed, skipped]. */
    public static function tally(array $entries)
    {
        $n = ['sent' => 0, 'failed' => 0, 'skipped' => 0];
        foreach ($entries as $e) {
            $n[$e['result']]++;
        }
        return $n;
    }
}
