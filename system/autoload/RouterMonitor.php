<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * Router history and alerts, run by the cron (Network > Routers > Settings). About every 5 minutes each enabled router is read once:
 * online or not, CPU, memory, disk, users online, uptime and the byte counters of its busiest interface (the uplink, usually).
 * The readings are kept 30 days for the History tab; changes become events (kept 90 days) and, when alerts are on, a message:
 *   offline     after two failed readings in a row (one blip is not an outage), and "back online" with how long it was down
 *   reboot      the uptime went back, so the router restarted
 *   cpu / mem   90% or more on two readings in a row, and again when it is back under 80%
 *   disk        95% full or more, and again when it is back under 90%
 * Alerts carry the router's name and what happened, never its address or login. Everything here is best effort: one router
 * failing never stops the others, and nothing here can break the cron.
 */
class RouterMonitor
{
    const EVERY = 270;          // seconds between readings (the cron usually runs every 5 minutes)
    const KEEP_SAMPLES = 30;    // days
    const KEEP_EVENTS = 90;     // days
    const GAP = 600;            // seconds: a reading further apart than this from the last one does not follow on from it
    const BOOT_SLACK = 120;     // seconds a router's start time can seem to move between readings without it having restarted

    /** Two readings follow on from each other (no gap in the collection between them, a late cron allowed for) */
    private static function follows($prev, $at)
    {
        return $prev && $at - strtotime($prev['at']) <= self::GAP;
    }

    /**
     * Whether the router restarted between two readings: its start time (when read, less the uptime) moved later. Uptime alone
     * misses a restart when the router has been up longer since than it was before.
     */
    public static function restarted($prevAt, $prevUptime, $at, $uptime)
    {
        if ($prevUptime === null || $uptime === null) {
            return false;
        }
        return ($at - (int) $uptime) - ($prevAt - (int) $prevUptime) > self::BOOT_SLACK;
    }

    public static function ready()
    {
        static $ok = null;
        if ($ok === null) {
            try {
                $db = ORM::getDb();
                $ok = (bool) $db->query("SHOW TABLES LIKE 'tbl_router_samples'")->fetch() && (bool) $db->query("SHOW TABLES LIKE 'tbl_router_events'")->fetch();
            } catch (Throwable $e) {
                $ok = false;
            }
        }
        return $ok;
    }

    public static function enabled()
    {
        global $config;
        return ($config['router_monitor'] ?? '1') === '1' && self::ready();
    }

    /** From the cron. Returns lines for its output. */
    public static function run()
    {
        global $CACHE_PATH;
        if (!self::enabled()) {
            // collection is off: no router is read, but what was kept still ages out
            if (self::ready()) {
                self::prune();
            }
            return [];
        }
        // one run at a time, and not more often than every EVERY seconds, whatever the cron's own schedule
        // (its own file: the cron's lock is called router_monitor.lock and is deleted at the end of every cron run)
        $lock = @fopen($CACHE_PATH . DIRECTORY_SEPARATOR . 'router_history.lock', 'c+');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            return [];
        }
        $last = (int) stream_get_contents($lock);
        if (time() - $last < self::EVERY) {
            flock($lock, LOCK_UN);
            fclose($lock);
            return [];
        }
        ftruncate($lock, 0);
        rewind($lock);
        fwrite($lock, (string) time());
        fflush($lock);
        $log = [];
        foreach (ORM::for_table('tbl_routers')->where('enabled', 1)->find_many() as $r) {
            if (trim((string) $r['ip_address']) === '' || strcasecmp((string) $r['name'], 'radius') === 0) {
                continue;
            }
            // each router gets its own time (a reading is at most about 15 seconds), so a slow one does not use up what the
            // rest of the cron run (the other routers, the nightly backups) needs; set_time_limit() starts the count again
            @set_time_limit(60);
            try {
                $log[] = $r['name'] . ': ' . self::check($r);
            } catch (Throwable $e) {
                $log[] = $r['name'] . ': ' . $e->getMessage();
            }
        }
        self::prune();
        flock($lock, LOCK_UN);
        fclose($lock);
        return $log;
    }

    /**
     * An uptime as seconds: "1w2d03:04:05" (weeks and days, then a clock), or units in any order, each counted however often it
     * appears ("12d4h31m5s", "1m1m" is 2 minutes). Null for anything else.
     */
    public static function seconds($u)
    {
        $u = trim((string) $u);
        if (preg_match('/^(?:(\d+)w)?(?:(\d+)d)?(\d+):(\d+):(\d+)$/', $u, $m)) {
            return (((int) $m[1] * 7 + (int) $m[2]) * 24 + (int) $m[3]) * 3600 + (int) $m[4] * 60 + (int) $m[5];
        }
        if (!preg_match('/^(\d+[wdhms])+$/i', $u)) {
            return null;
        }
        $per = ['w' => 604800, 'd' => 86400, 'h' => 3600, 'm' => 60, 's' => 1];
        $total = 0;
        preg_match_all('/(\d+)([wdhms])/i', $u, $all, PREG_SET_ORDER);
        foreach ($all as $p) {
            $total += (int) $p[1] * $per[strtolower($p[2])];
        }
        return $total;
    }

    /** Read one router, keep the reading, and turn changes into events (and alerts). */
    private static function check($r)
    {
        $id = (int) $r['id'];
        $prev = ORM::for_table('tbl_router_samples')->where('router_id', $id)->order_by_desc('at')->find_one();
        // the uplink chosen before: from the last reading that had one (a failed reading has none, and must not switch it)
        $lastIf = ORM::for_table('tbl_router_samples')->where('router_id', $id)->where_not_null('iface')->order_by_desc('at')->find_one();
        $s = ['online' => 0, 'cpu' => null, 'mem' => null, 'disk' => null, 'users' => null, 'uptime' => null, 'iface' => null, 'rx' => null, 'tx' => null];
        $why = '';
        [$c, $err] = RouterApi::connect($r['ip_address'], $r['username'], $r['password'], 5);
        if ($c) {
            $until = microtime(true) + 10;
            try {
                $res = RouterApi::rows($c, '/system/resource/print', ['cpu-load', 'free-memory', 'total-memory', 'free-hdd-space', 'total-hdd-space', 'uptime'], $until);
                $x = $res[0] ?? [];
                $pct = function ($free, $total) {
                    return is_numeric($free) && is_numeric($total) && (float) $total > 0 ? (int) round((1 - (float) $free / (float) $total) * 100) : null;
                };
                $s['online'] = 1;
                $s['cpu'] = is_numeric($x['cpu-load'] ?? null) ? (int) $x['cpu-load'] : null;
                $s['mem'] = $pct($x['free-memory'] ?? null, $x['total-memory'] ?? null);
                $s['disk'] = $pct($x['free-hdd-space'] ?? null, $x['total-hdd-space'] ?? null);
                $s['uptime'] = self::seconds($x['uptime'] ?? '');
            } catch (Throwable $e) {
                // the router refused the read (RouterApi marks a refusal with code 403): it answers, so it counts as online. No
                // answer in time (408), a connection that broke (the library's own exceptions) or anything else is offline.
                $s['online'] = $e->getCode() === 403 ? 1 : 0;
                $why = $e->getMessage();
            }
            if ($s['online']) {
                // users online: hotspot plus PPPoE, kept only when both are known. A menu the router does not have (no hotspot
                // package) counts as 0; a refusal or no answer makes the total unknown rather than half of it.
                $users = 0;
                // (PPP sessions are PPPoE only: L2TP, SSTP and the other tunnels are not customers)
                foreach (['/ip/hotspot/active/print' => null, '/ppp/active/print' => 'pppoe'] as $menu => $service) {
                    try {
                        $q = new PEAR2\Net\RouterOS\Request($menu, $service ? PEAR2\Net\RouterOS\Query::where('service', $service) : null);
                        $q->setArgument('count-only', '');
                        $res = RouterApi::send($c, $q, $until);
                        $err = RouterApi::error($res);
                        $n = RouterApi::text($res->end()->getProperty('ret'));
                        if ($err !== null) {
                            if (stripos($err, 'no such command') === false) {
                                $users = null;
                            }
                        } elseif (is_numeric($n)) {
                            $users = $users === null ? null : $users + (int) $n;
                        } else {
                            $users = null;
                        }
                    } catch (Throwable $e) {
                        $users = null;
                    }
                }
                $s['users'] = $users;
                try {
                    // the uplink: the interface used before if it is still there, otherwise the busiest one
                    $ifs = RouterApi::rows($c, '/interface/print', ['name', 'rx-byte', 'tx-byte', 'running'], $until);
                    $pick = null;
                    foreach ($ifs as $i) {
                        if ($lastIf && $i['name'] === $lastIf['iface']) {
                            $pick = $i;
                            break;
                        }
                        if ($i['running'] === 'true' && is_numeric($i['rx-byte']) && (!$pick || (float) $i['rx-byte'] + (float) $i['tx-byte'] > (float) $pick['rx-byte'] + (float) $pick['tx-byte'])) {
                            $pick = $i;
                        }
                    }
                    if ($pick && is_numeric($pick['rx-byte']) && is_numeric($pick['tx-byte'])) {
                        $s['iface'] = mb_substr((string) $pick['name'], 0, 64);
                        $s['rx'] = $pick['rx-byte'];
                        $s['tx'] = $pick['tx-byte'];
                    }
                } catch (Throwable $e) {
                }
            }
        } else {
            $why = $err;
        }
        $row = ORM::for_table('tbl_router_samples')->create();
        $row->router_id = $id;
        $row->at = date('Y-m-d H:i:s');
        foreach ($s as $k => $v) {
            $row->$k = $v;
        }
        $row->save();
        self::events($r, $s, $prev, $why, (int) $row->id());
        return $s['online'] ? 'online' . ($s['cpu'] !== null ? ', CPU ' . $s['cpu'] . '%' : '') : 'offline (' . $why . ')';
    }

    /** The newest event of these types for a router, or null */
    private static function lastOf($id, array $types)
    {
        return ORM::for_table('tbl_router_events')->where('router_id', $id)->where_in('type', $types)->order_by_desc('id')->find_one();
    }

    private static function event($r, $type, $detail, $alert, $at = null)
    {
        $e = ORM::for_table('tbl_router_events')->create();
        $e->router_id = (int) $r['id'];
        $e->at = $at ?: date('Y-m-d H:i:s');
        $e->type = $type;
        $e->detail = mb_substr($detail, 0, 255);
        $e->save();
        if ($alert) {
            self::alert($r['name'] . ': ' . $alert);
        }
    }

    private static function events($r, array $s, $prev, $why, $sampleId)
    {
        $id = (int) $r['id'];
        $state = self::lastOf($id, ['offline', 'online']);
        $isDown = $state && $state['type'] === 'offline';
        if (!$s['online']) {
            // offline only after the second failed reading in a row (two readings far apart, the cron having stopped meanwhile,
            // are not in a row), dated from the first one: that is when the outage began, and the event keeps it (readings are
            // pruned sooner than events) for the duration at recovery. An outage already confirmed stays so across any gap.
            if (!$isDown && self::follows($prev, time()) && !(int) $prev['online']) {
                self::event($r, 'offline', $why, Lang::T('router is offline') . ' (' . Lang::T('no answer since') . ' ' . date('H:i', strtotime($prev['at'])) . ')', $prev['at']);
            }
            return;
        }
        if ($isDown) {
            // the offline event is dated from the first missed reading
            $mins = max(1, (int) round((time() - strtotime($state['at'])) / 60));
            self::event($r, 'online', '', Lang::T('router is back online after about') . ' ' . self::duration($mins));
        }
        // a restart: the router's start time moved on since the last reading that had an uptime (failed readings in between have none)
        $lastUp = ORM::for_table('tbl_router_samples')->where('router_id', $id)->where('online', 1)->where_not_null('uptime')
            ->where_lt('id', $sampleId)->order_by_desc('id')->find_one();
        if ($lastUp && self::restarted(strtotime($lastUp['at']), $lastUp['uptime'], time(), $s['uptime'])) {
            self::event($r, 'reboot', '', Lang::T('router restarted') . ' (' . Lang::T('up for') . ' ' . self::duration((int) round($s['uptime'] / 60)) . ')');
        }
        // high load: two readings in a row over the line to alert, and well under it to say it is fine again
        foreach ([['cpu', 'cpu_high', 'cpu_ok', 90, 80, 'CPU'], ['mem', 'mem_high', 'mem_ok', 90, 80, Lang::T('memory')], ['disk', 'disk_high', 'disk_ok', 95, 90, Lang::T('disk')]] as [$k, $hi, $ok, $over, $under, $label]) {
            if ($s[$k] === null) {
                continue;
            }
            $last = self::lastOf($id, [$hi, $ok]);
            $high = $last && $last['type'] === $hi;
            $twice = $k === 'disk' || (self::follows($prev, time()) && $prev[$k] !== null && (int) $prev[$k] >= $over);
            if (!$high && $s[$k] >= $over && $twice) {
                self::event($r, $hi, $s[$k] . '%', $label . ' ' . Lang::T('at') . ' ' . $s[$k] . '%');
            } elseif ($high && $s[$k] < $under) {
                self::event($r, $ok, $s[$k] . '%', $label . ' ' . Lang::T('back to normal') . ' (' . $s[$k] . '%)');
            }
        }
    }

    private static function duration($mins)
    {
        if ($mins < 60) {
            return $mins . ' min';
        }
        if ($mins < 2880) {
            return round($mins / 60, 1) . ' h';
        }
        return round($mins / 1440, 1) . ' d';
    }

    /** To the channels chosen in Network > Routers > Settings. Never throws. */
    public static function alert($text)
    {
        global $config;
        if (($config['router_alerts'] ?? '0') !== '1') {
            return;
        }
        $text = '[' . ($config['CompanyName'] ?? 'PHPNuxBill') . '] ' . $text;
        try {
            if (($config['router_alert_telegram'] ?? '1') === '1') {
                Message::sendTelegram($text);
            }
        } catch (Throwable $e) {
        }
        try {
            if (($config['router_alert_email'] ?? '0') === '1' && !empty($config['mail_from'])) {
                Message::sendEmail($config['mail_from'], Lang::T('Router alert'), $text);
            }
        } catch (Throwable $e) {
        }
        $phone = preg_replace('/[^0-9+]/', '', (string) ($config['router_alert_phone'] ?? ''));
        if ($phone !== '' && strlen($phone) >= 7) {
            try {
                if (($config['router_alert_via'] ?? 'wa') === 'sms') {
                    Message::sendSMS($phone, $text);
                } else {
                    Message::sendWhatsapp($phone, $text);
                }
            } catch (Throwable $e) {
            }
        }
    }

    /** Once a day: readings older than 30 days and events older than 90 days go. */
    private static function prune()
    {
        global $CACHE_PATH;
        $mark = $CACHE_PATH . DIRECTORY_SEPARATOR . 'router_monitor.pruned';
        if (is_file($mark) && time() - filemtime($mark) < 86400) {
            return;
        }
        try {
            $db = ORM::getDb();
            $db->prepare('DELETE FROM tbl_router_samples WHERE at < ?')->execute([date('Y-m-d H:i:s', time() - self::KEEP_SAMPLES * 86400)]);
            // old events go, but never the newest state of a router that still exists (online/offline, CPU, memory, disk): that one
            // stops a repeated alert and sends the "back to normal" one, however long ago it began. Restarts and the events of a
            // deleted router age out like the rest.
            $db->prepare("DELETE FROM tbl_router_events WHERE at < ? AND id NOT IN (SELECT id FROM (SELECT MAX(e.id) AS id FROM tbl_router_events e
                JOIN tbl_routers r ON r.id = e.router_id
                WHERE e.type IN ('offline', 'online', 'cpu_high', 'cpu_ok', 'mem_high', 'mem_ok', 'disk_high', 'disk_ok')
                GROUP BY e.router_id, CASE WHEN e.type IN ('offline', 'online') THEN 'state' ELSE SUBSTRING_INDEX(e.type, '_', 1) END) AS keep_newest)")
                ->execute([date('Y-m-d H:i:s', time() - self::KEEP_EVENTS * 86400)]);
            @touch($mark); // only once both are done: a failure is tried again on the next run
        } catch (Throwable $e) {
        }
    }

    /**
     * For the History tab: the readings of the last 24 hours, 7 days or 30 days, averaged into at most about 300 points, the
     * download and upload speed of the uplink between readings, the share of readings it was online, and its events.
     */
    public static function history($id, $range)
    {
        $spans = ['24h' => 86400, '7d' => 7 * 86400, '30d' => 30 * 86400];
        $span = $spans[$range] ?? 86400;
        $from = time() - $span;
        $rows = ORM::for_table('tbl_router_samples')->where('router_id', (int) $id)->where_gte('at', date('Y-m-d H:i:s', $from))
            ->order_by_asc('at')->find_array();
        $bucket = max(300, (int) ceil($span / 300));
        $points = [];
        $online = 0;
        $prev = null;
        foreach ($rows as $r) {
            $t = strtotime($r['at']);
            $online += (int) $r['online'];
            $down = $up = null;
            // a restart between the two readings resets the counters: no speed across it (the same test as the restart event)
            $restarted = $prev && self::restarted($prev['t'], $prev['uptime'], $t, $r['uptime']);
            if ($prev && !$restarted && $prev['iface'] === $r['iface'] && $r['rx'] !== null && $prev['rx'] !== null && $t > $prev['t']) {
                $sec = $t - $prev['t'];
                $dRx = (float) $r['rx'] - (float) $prev['rx'];
                $dTx = (float) $r['tx'] - (float) $prev['tx'];
                if ($dRx >= 0 && $dTx >= 0 && $sec <= 3 * self::EVERY) { // a reset counter or a long gap gives no speed
                    $down = $dRx * 8 / $sec;
                    $up = $dTx * 8 / $sec;
                }
            }
            $prev = ['t' => $t, 'iface' => $r['iface'], 'rx' => $r['rx'], 'tx' => $r['tx'], 'uptime' => $r['uptime']];
            $b = (int) floor($t / $bucket) * $bucket;
            if (!isset($points[$b])) {
                $points[$b] = ['n' => 0, 'on' => 0, 'cpu' => [], 'mem' => [], 'users' => [], 'down' => [], 'up' => []];
            }
            $p = &$points[$b];
            $p['n']++;
            $p['on'] += (int) $r['online'];
            foreach (['cpu', 'mem', 'users'] as $k) {
                if ($r[$k] !== null) {
                    $p[$k][] = (float) $r[$k];
                }
            }
            if ($down !== null) {
                $p['down'][] = $down;
                $p['up'][] = $up;
            }
            unset($p);
        }
        $avg = function ($a) {
            return $a ? round(array_sum($a) / count($a), 1) : null;
        };
        $out = [];
        foreach ($points as $b => $p) {
            $out[] = ['at' => $b + $bucket / 2, 'online' => $p['on'] > 0 ? 1 : 0, 'cpu' => $avg($p['cpu']), 'mem' => $avg($p['mem']),
                'users' => $avg($p['users']), 'down' => $avg($p['down']), 'up' => $avg($p['up'])];
        }
        $events = ORM::for_table('tbl_router_events')->where('router_id', (int) $id)->where_gte('at', date('Y-m-d H:i:s', $from))
            ->order_by_desc('id')->limit(50)->find_array();
        return ['from' => $from, 'to' => time(), 'points' => $out, 'samples' => count($rows),
            'uptime' => $rows ? round($online / count($rows) * 100, 2) : null,
            'events' => array_map(function ($e) {
                return ['at' => strtotime($e['at']), 'type' => $e['type'], 'detail' => $e['detail']];
            }, $events)];
    }
}
