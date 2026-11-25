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
    // seconds: a reading further apart than this from the last one does not follow on from it. The cron may run every 5 to 15
    // minutes (Routers > Settings says so); this allows a 15-minute cron and 5 minutes of lateness (a cron started late, or slow
    // routers read before this one in a run and not in the last). A longer gap is a break.
    const GAP = 1200;
    // seconds a router's start time (reading time less uptime) can seem to move between readings without it having restarted. A
    // reading's time is taken as the /system/resource reply arrives, so the start time is off by the rounding of the two whole
    // seconds and the reply's trip only: a few seconds. 45 covers that several times over, yet a restart moves the start time by
    // at least the uptime the router had at the earlier reading plus its own boot (20 seconds or more), so it is missed only when
    // that reading came within about 25 seconds of the previous start. Readings far apart get BOOT_DRIFT more (see restarted()).
    const BOOT_SLACK = 45;
    // the router's uptime and the server's clock run at slightly different rates (a crystal is good to about 50 parts per million,
    // NTP corrects the server): 0.0002 per second elapsed covers readings days apart (a week adds about 2 minutes)
    const BOOT_DRIFT = 0.0002;

    /** More time for the work that follows, where the host allows it (set_time_limit() can be disabled: then the work just goes on) */
    private static function timeLimit($seconds)
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit($seconds);
        }
    }

    /**
     * Times are kept as UTC (so a change of the timezone setting or of daylight saving never shifts or doubles an hour). Rows
     * saved before that are in the server's local time: the first write (under the router edit lock) notes in tbl_appconfig
     * the newest reading and event IDs at that moment, and rows with a higher ID are UTC, the others local. Null: no note yet
     * (or none could be saved), and then everything is still read and written as local time.
     */
    private static $cutValue = null;
    private static $cutLooked = false;

    /** Forget the boundary read before: the next cut() reads it again (history() starts its snapshot with this) */
    private static function resetCut()
    {
        self::$cutValue = null;
        self::$cutLooked = false;
    }

    private static function cut($create = false)
    {
        if (self::$cutValue !== null || (self::$cutLooked && !$create)) {
            return self::$cutValue;
        }
        try {
            self::$cutLooked = true;
            $d = ORM::for_table('tbl_appconfig')->where('setting', 'router_monitor_utc_since')->find_one();
            if ($d && preg_match('/^(\d+),(\d+)$/', (string) $d['value'], $m)) {
                return self::$cutValue = ['s' => (int) $m[1], 'e' => (int) $m[2]];
            }
            if (!$create || $d) {
                return null;
            }
            $s = (int) ORM::for_table('tbl_router_samples')->max('id');
            $e = (int) ORM::for_table('tbl_router_events')->max('id');
            $n = ORM::for_table('tbl_appconfig')->create();
            $n->setting = 'router_monitor_utc_since';
            $n->value = $s . ',' . $e;
            if ($n->save() === false) {
                return null;
            }
            return self::$cutValue = ['s' => $s, 'e' => $e];
        } catch (Throwable $x) {
            // a lookup that failed is not "no boundary": not remembered, and a write (create) stops here, so the reading is not saved
            // with local time over a boundary that exists; the next reading tries again. A strict edit rolls back on it (moved()).
            self::$cutLooked = false;
            if ($create) {
                throw $x;
            }
            return null;
        }
    }

    /** A time as it is stored (UTC once the note of cut() exists, otherwise local); call cut(true) before writing */
    private static function stamp($t)
    {
        return self::cut() ? gmdate('Y-m-d H:i:s', $t) : date('Y-m-d H:i:s', $t);
    }

    /** The time of a stored row ($table 's' readings, 'e' events) as a timestamp */
    private static function ts($table, $row)
    {
        $cut = self::cut();
        return $cut && (int) $row['id'] > $cut[$table] ? strtotime($row['at'] . ' UTC') : strtotime($row['at']);
    }

    /** SQL (and its values) for "at $op the time $t" in the table's mixed local and UTC rows (see cut()) */
    private static function atSql($table, $op, $t)
    {
        $cut = self::cut();
        if (!$cut) {
            return ['at ' . $op . ' ?', [date('Y-m-d H:i:s', $t)]];
        }
        return ['((id <= ? AND at ' . $op . ' ?) OR (id > ? AND at ' . $op . ' ?))', [$cut[$table], date('Y-m-d H:i:s', $t), $cut[$table], gmdate('Y-m-d H:i:s', $t)]];
    }

    private static function atWhere($q, $table, $op, $t)
    {
        [$sql, $vals] = self::atSql($table, $op, $t);
        return $q->where_raw($sql, $vals);
    }

    /** Whether tbl_router_samples has the column for when the interface counters were read (added by an update; an older table lacks it) */
    private static function hasIfat()
    {
        static $has = null;
        if ($has === null) {
            try {
                $has = (bool) ORM::getDb()->query("SHOW COLUMNS FROM tbl_router_samples LIKE 'ifat'")->fetch();
            } catch (Throwable $e) {
                $has = false;
            }
        }
        return $has;
    }

    /** Two readings follow on from each other (no gap in the collection between them, a late cron allowed for) */
    private static function follows($prev, $at)
    {
        if (!$prev) {
            return false;
        }
        $d = $at - self::ts('s', $prev);
        return $d >= 0 && $d <= self::GAP; // (a reading dated before the last one, a clock set back, does not follow on)
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
        return ($at - (int) $uptime) - ($prevAt - (int) $prevUptime) > self::BOOT_SLACK + abs($at - $prevAt) * self::BOOT_DRIFT;
    }

    /** The history tables are installed. $strict: a failed probe throws (and is looked at again) instead of reading as "not installed" */
    public static function ready($strict = false)
    {
        static $ok = null;
        if ($ok === null || ($strict && $ok === false)) {
            try {
                $db = ORM::getDb();
                $ok = (bool) $db->query("SHOW TABLES LIKE 'tbl_router_samples'")->fetch() && (bool) $db->query("SHOW TABLES LIKE 'tbl_router_events'")->fetch();
            } catch (Throwable $e) {
                $ok = false;
                if ($strict) {
                    throw $e;
                }
            }
        }
        return $ok;
    }

    public static function enabled()
    {
        global $config;
        return ($config['router_monitor'] ?? '1') === '1' && self::ready();
    }

    /**
     * The router's address was changed (routers.php, edit): the device there may be another one. Its readings before now are no
     * baseline for the next (traffic speed, restart) and its alert states (offline, high load) are not the new device's, so a
     * "moved" event marks where they stop counting. Kept like the newest state, so it is never pruned away before what it hides.
     * $strict: a failed save throws instead of being ignored, so the caller can save it in the same transaction as the address
     * and roll both back (an address changed with no "moved" event would let the old device's readings and states count).
     */
    public static function moved($id, $strict = false)
    {
        // (strict: only tables known not to be there skip the event; a probe that failed throws, so the edit is rolled back)
        if (!self::ready($strict)) {
            return;
        }
        try {
            // the newest reading so far: the readings after the change are the ones with a higher ID. A time cannot tell them
            // apart from the old device's readings in the same second. (Called with the router edit lock held, which keep()
            // holds too, so no reading is saved between this and the event.)
            self::cut(true);
            $cut = (int) ORM::for_table('tbl_router_samples')->where('router_id', (int) $id)->max('id');
            $e = ORM::for_table('tbl_router_events')->create();
            $e->router_id = (int) $id;
            $e->at = self::stamp(time());
            $e->type = 'moved';
            $e->detail = 'sample:' . $cut;
            // (the connection throws on a database error; a save that reports failure without one counts as failed too, so
            // the caller's transaction is rolled back rather than committing the address with no event)
            if ($e->save() === false) {
                throw new Exception('router moved event not saved');
            }
        } catch (Throwable $e) {
            if ($strict) {
                throw $e;
            }
        }
    }

    /**
     * The router's last address change, or null: readings and states from before it do not count. ['id' => the event's ID,
     * 'at' => the change's time as a timestamp, 'sample' => the ID of the last reading before it, or null for a change saved before that was kept]
     */
    private static function movedAt($id)
    {
        $m = ORM::for_table('tbl_router_events')->where('router_id', (int) $id)->where('type', 'moved')->order_by_desc('id')->find_one();
        if (!$m) {
            return null;
        }
        return ['id' => (int) $m['id'], 'at' => self::ts('e', $m), 'sample' => preg_match('/^sample:(\d+)$/', (string) $m['detail'], $x) ? (int) $x[1] : null];
    }

    /** Only the readings after the address change $since (see movedAt()) */
    private static function sinceMove($q, $since)
    {
        if (!$since) {
            return $q;
        }
        return $since['sample'] !== null ? $q->where_gt('id', $since['sample']) : self::atWhere($q, 's', '>', $since['at']);
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
        try {
            $routers = ORM::for_table('tbl_routers')->where('enabled', 1)->find_many();
        } catch (Throwable $e) {
            // the routers could not be read: no reading this time (the time is kept, so the next try waits as usual), and the
            // rest of the cron run carries on
            $routers = [];
            $log[] = $e->getMessage();
        }
        foreach ($routers as $r) {
            if (trim((string) $r['ip_address']) === '' || strcasecmp((string) $r['name'], 'radius') === 0) {
                continue;
            }
            // each router gets its own time (a reading is at most about 15 seconds), so a slow one does not use up what the
            // rest of the cron run (the other routers, the nightly backups) needs; set_time_limit() starts the count again
            self::timeLimit(60);
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
        global $_app_stage;
        // Demo mode never connects to a router: that is no outage, so nothing is kept (no offline reading, no alert)
        if (strcasecmp(trim((string) $_app_stage), 'demo') === 0) {
            return Lang::T('skipped in Demo mode');
        }
        $id = (int) $r['id'];
        // only readings at the router's present address follow on (see moved())
        $since = self::movedAt($id);
        $q = function () use ($id, $since) {
            return self::sinceMove(ORM::for_table('tbl_router_samples')->where('router_id', $id), $since);
        };
        $prev = $q()->order_by_desc('id')->find_one(); // by ID: the order they were saved in, whichever of local time or UTC a row has
        // the uplink chosen before: from the last reading that had one (a failed reading has none, and must not switch it)
        $lastIf = $q()->where_not_null('iface')->order_by_desc('id')->find_one();
        $s = ['online' => 0, 'cpu' => null, 'mem' => null, 'disk' => null, 'users' => null, 'uptime' => null, 'iface' => null, 'rx' => null, 'tx' => null, 'ifat' => null];
        $why = '';
        $readAt = null; // when the router answered /system/resource: the reading's time (see keep())
        [$c, $err] = RouterApi::connect($r['ip_address'], $r['username'], $r['password'], 5);
        if ($c) {
            // done with the router either way: the connection is dropped (no goodbye, which can wait for ever) before the reading is kept
            try {
                $until = RouterApi::now() + 10;
                try {
                    $res = RouterApi::rows($c, '/system/resource/print', ['cpu-load', 'free-memory', 'total-memory', 'free-hdd-space', 'total-hdd-space', 'uptime'], $until);
                    $readAt = time();
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
                        $ifAt = time();
                        $pick = null;
                        foreach ($ifs as $i) {
                            // (only an interface that is running and has both counters: one that is down or unread is no uplink)
                            if ($i['running'] !== 'true' || !is_numeric($i['rx-byte']) || !is_numeric($i['tx-byte'])) {
                                continue;
                            }
                            if ($lastIf && $i['name'] === $lastIf['iface']) {
                                $pick = $i;
                                break;
                            }
                            if (!$pick || (float) $i['rx-byte'] + (float) $i['tx-byte'] > (float) $pick['rx-byte'] + (float) $pick['tx-byte']) {
                                $pick = $i;
                            }
                        }
                        if ($pick && is_numeric($pick['rx-byte']) && is_numeric($pick['tx-byte'])) {
                            $s['iface'] = mb_substr((string) $pick['name'], 0, 64);
                            $s['rx'] = $pick['rx-byte'];
                            $s['tx'] = $pick['tx-byte'];
                            $s['ifat'] = $ifAt; // (when the counters were read: not when /system/resource was)
                        }
                    } catch (Throwable $e) {
                    }
                }
            } finally {
                method_exists($c, 'abort') ? $c->abort() : null;
            }
        } else {
            $why = $err;
        }
        // the router's address was changed while it was being read (routers.php, edit): the reading is the old device's, and is
        // neither kept nor turned into events for the new one. The check and the writes below hold the lock routers.php takes
        // for its edits (until the address and its "moved" event are saved), so an edit cannot come between them.
        $db = ORM::getDb();
        try {
            $locked = (int) $db->query("SELECT GET_LOCK('phpnuxbill_router_endpoint', 20)")->fetchColumn() === 1;
        } catch (Throwable $e) {
            $locked = false;
        }
        if (!$locked) {
            return Lang::T('the router is being edited, reading not kept');
        }
        self::$pending = [];
        try {
            $now = ORM::for_table('tbl_routers')->select_many('name', 'ip_address', 'username', 'password', 'enabled')->find_one($id);
            $then = self::movedAt($id);
            if (!$now || (string) $now['ip_address'] !== (string) $r['ip_address'] || ($then ? $then['id'] : null) !== ($since ? $since['id'] : null)) {
                return Lang::T('address changed during the reading, not kept');
            }
            // the login was changed while it was being read: the reading (a failed sign-in above all) was made with the old one
            if ((string) $now['username'] !== (string) $r['username'] || (string) $now['password'] !== (string) $r['password']) {
                return Lang::T('login changed during the reading, not kept');
            }
            // disabled while it was being read: no longer watched, so neither the reading nor an alert for it
            if ((int) $now['enabled'] !== 1) {
                return Lang::T('router disabled during the reading, not kept');
            }
            // the name as it is now (it may have been renamed while the router was read): the alert says it
            $r['name'] = (string) $now['name'];
            return self::keep($r, $s, $prev, $why, $since, $readAt);
        } finally {
            try {
                $db->query("SELECT RELEASE_LOCK('phpnuxbill_router_endpoint')");
            } catch (Throwable $e) {
            }
            // the alerts of the events just saved are queued only now (see alert(): they are sent when the run ends), so neither
            // router edits waiting on the lock nor the other routers and the backups wait on a slow channel
            $pending = self::$pending;
            self::$pending = [];
            foreach ($pending as $text) {
                self::alert($text);
            }
        }
    }

    /** Alerts of the events saved under the router edit lock, sent once it is released (see check()) */
    private static $pending = [];

    /** Alerts queued by alert(), sent when the cron run ends (see deliver()) */
    private static $queue = [];

    /** Save a reading and turn it into events (called with the router edit lock held) */
    private static function keep($r, array $s, $prev, $why, $since, $readAt = null)
    {
        $id = (int) $r['id'];
        // one time for the reading: kept with the row and used for the restart test against it. The time the router answered when
        // it did (not now: the other reads and the wait for the lock come between), so its uptime and this time go together
        $at = $readAt ?: time();
        self::cut(true); // (before the first time is written: see cut())
        $row = ORM::for_table('tbl_router_samples')->create();
        $row->router_id = $id;
        $row->at = self::stamp($at);
        foreach ($s as $k => $v) {
            if ($k === 'ifat') {
                if ($v !== null && self::hasIfat()) {
                    $row->ifat = self::stamp($v);
                }
                continue;
            }
            $row->$k = $v;
        }
        // the reading and its events are saved together or not at all: a reading kept without its events would be the "previous"
        // one next time, and its transitions (offline, back online, high load) would never be recorded. The alerts of those
        // events go out only once both are saved (check() sends self::$pending); a rollback drops them.
        $db = ORM::getDb();
        $own = !$db->inTransaction();
        if ($own) {
            $db->beginTransaction();
        }
        try {
            if ($row->save() === false) {
                throw new Exception('reading not saved');
            }
            self::events($r, $s, $prev, $why, (int) $row->id(), $at, $since);
            if ($own) {
                $db->commit();
            }
        } catch (Throwable $e) {
            self::$pending = [];
            if ($own && $db->inTransaction()) {
                try {
                    $db->rollBack();
                } catch (Throwable $x) {
                }
            }
            throw $e;
        }
        return $s['online'] ? 'online' . ($s['cpu'] !== null ? ', CPU ' . $s['cpu'] . '%' : '') : 'offline (' . $why . ')';
    }

    /** The newest event of these types for a router, or null */
    private static function lastOf($id, array $types)
    {
        return ORM::for_table('tbl_router_events')->where('router_id', $id)->where_in('type', $types)->order_by_desc('id')->find_one();
    }

    /** $at: a timestamp (now when null) */
    private static function event($r, $type, $detail, $alert, $at = null)
    {
        $e = ORM::for_table('tbl_router_events')->create();
        $e->router_id = (int) $r['id'];
        $e->at = self::stamp($at ?: time());
        $e->type = $type;
        $e->detail = mb_substr($detail, 0, 255);
        if ($e->save() === false) {
            throw new Exception('router event not saved');
        }
        if ($alert) {
            // sent by check() once the router edit lock is released
            self::$pending[] = $r['name'] . ': ' . $alert;
        }
    }

    private static function events($r, array $s, $prev, $why, $sampleId, $now, $since = null)
    {
        $id = (int) $r['id'];
        // a "moved" event newer than a state ends it: the device at the new address starts with none
        $state = self::lastOf($id, ['offline', 'online', 'moved']);
        $isDown = $state && $state['type'] === 'offline';
        if (!$s['online']) {
            // offline only after the second failed reading in a row (two readings far apart, the cron having stopped meanwhile,
            // are not in a row), dated from the first one: that is when the outage began, and the event keeps it (readings are
            // pruned sooner than events) for the duration at recovery. An outage already confirmed stays so across any gap.
            if (!$isDown && self::follows($prev, $now) && !(int) $prev['online']) {
                self::event($r, 'offline', $why, Lang::T('router is offline') . ' (' . Lang::T('no answer since') . ' ' . date('H:i', self::ts('s', $prev)) . ')', self::ts('s', $prev));
            }
            return;
        }
        if ($isDown) {
            // the offline event is dated from the first missed reading
            $mins = max(1, (int) round(($now - self::ts('e', $state)) / 60));
            self::event($r, 'online', '', Lang::T('router is back online after about') . ' ' . self::duration($mins), $now);
        }
        // a restart: the router's start time moved on since the last reading that had an uptime (failed readings in between have none)
        $lastUp = ORM::for_table('tbl_router_samples')->where('router_id', $id)->where('online', 1)->where_not_null('uptime')
            ->where_lt('id', $sampleId);
        $lastUp = self::sinceMove($lastUp, $since)->order_by_desc('id')->find_one();
        if ($lastUp && self::restarted(self::ts('s', $lastUp), $lastUp['uptime'], $now, $s['uptime'])) {
            self::event($r, 'reboot', '', Lang::T('router restarted') . ' (' . Lang::T('up for') . ' ' . self::duration((int) round($s['uptime'] / 60)) . ')', $now);
        }
        // high load: two readings in a row over the line to alert, and well under it to say it is fine again
        foreach ([['cpu', 'cpu_high', 'cpu_ok', 90, 80, 'CPU'], ['mem', 'mem_high', 'mem_ok', 90, 80, Lang::T('memory')], ['disk', 'disk_high', 'disk_ok', 95, 90, Lang::T('disk')]] as [$k, $hi, $ok, $over, $under, $label]) {
            if ($s[$k] === null) {
                continue;
            }
            $last = self::lastOf($id, [$hi, $ok, 'moved']);
            $high = $last && $last['type'] === $hi;
            $twice = $k === 'disk' || (self::follows($prev, $now) && $prev[$k] !== null && (int) $prev[$k] >= $over);
            if (!$high && $s[$k] >= $over && $twice) {
                self::event($r, $hi, $s[$k] . '%', $label . ' ' . Lang::T('at') . ' ' . $s[$k] . '%', $now);
            } elseif ($high && $s[$k] < $under) {
                self::event($r, $ok, $s[$k] . '%', $label . ' ' . Lang::T('back to normal') . ' (' . $s[$k] . '%)', $now);
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

    /**
     * To the channels chosen in Network > Routers > Settings, when the run ends (deliver()): each channel waits for its answer
     * (Telegram, SMS in parts, WhatsApp up to 15 seconds each, SMTP minutes for each step), and sent here they would hold up the
     * routers read after this one and the nightly backups. Never throws.
     */
    public static function alert($text)
    {
        global $config;
        if (($config['router_alerts'] ?? '0') !== '1') {
            return;
        }
        try {
            if (!self::$queue) {
                register_shutdown_function([self::class, 'deliver']);
            }
            self::$queue[] = '[' . ($config['CompanyName'] ?? 'PHPNuxBill') . '] ' . $text;
        } catch (Throwable $e) {
        }
    }

    /** The alerts queued by alert(), when the run ends, one after another. Never throws. */
    public static function deliver()
    {
        global $config;
        $queue = self::$queue;
        self::$queue = [];
        $phone = preg_replace('/[^0-9+]/', '', (string) ($config['router_alert_phone'] ?? ''));
        foreach ($queue as $text) {
            // each alert gets its own time, like each router reading (a shutdown function still counts against the limit)
            self::timeLimit(60);
            try {
                if (($config['router_alert_telegram'] ?? '1') === '1') {
                    // a short time limit (the default is minutes)
                    Message::sendTelegram($text, null, '', 15);
                }
            } catch (Throwable $e) {
            }
            if ($phone !== '' && strlen($phone) >= 7) {
                try {
                    // the same short time limit as Telegram
                    if (($config['router_alert_via'] ?? 'wa') === 'sms') {
                        Message::sendSMS($phone, $text, 15);
                    } else {
                        Message::sendWhatsapp($phone, $text, 15);
                    }
                } catch (Throwable $e) {
                }
            }
        }
        // emails last: SMTP has no overall time limit and sendEmail() takes none, so a stalled mail server cannot hold up the rest
        if (($config['router_alert_email'] ?? '0') === '1' && !empty($config['mail_from'])) {
            foreach ($queue as $text) {
                self::timeLimit(120);
                try {
                    Message::sendEmail($config['mail_from'], Lang::T('Router alert'), $text);
                } catch (Throwable $e) {
                }
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
            // (the cut-off on the same basis as the stored times: UTC for the rows saved as UTC, local for the older ones)
            [$sql, $vals] = self::atSql('s', '<', time() - self::KEEP_SAMPLES * 86400);
            $db->prepare('DELETE FROM tbl_router_samples WHERE ' . $sql)->execute($vals);
            // old events go, but never the newest state of a router that still exists (online/offline, CPU, memory, disk): that one
            // stops a repeated alert and sends the "back to normal" one, however long ago it began. Restarts and the events of a
            // deleted router age out like the rest.
            [$sql, $vals] = self::atSql('e', '<', time() - self::KEEP_EVENTS * 86400);
            $db->prepare("DELETE FROM tbl_router_events WHERE " . $sql . " AND id NOT IN (SELECT id FROM (SELECT MAX(e.id) AS id FROM tbl_router_events e
                JOIN tbl_routers r ON r.id = e.router_id
                WHERE e.type IN ('offline', 'online', 'cpu_high', 'cpu_ok', 'mem_high', 'mem_ok', 'disk_high', 'disk_ok', 'moved')
                GROUP BY e.router_id, CASE WHEN e.type IN ('offline', 'online') THEN 'state' ELSE SUBSTRING_INDEX(e.type, '_', 1) END) AS keep_newest)")
                ->execute($vals);
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
        // the boundary, the readings and the events are read in one transaction (one snapshot of the database), the boundary
        // first and read afresh: a first write that creates it meanwhile cannot make rows be filtered by one boundary and decoded
        // by another
        $db = ORM::getDb();
        $own = !$db->inTransaction();
        if ($own) {
            try {
                $db->beginTransaction();
            } catch (Throwable $e) {
                $own = false;
            }
        }
        try {
            self::resetCut();
            self::cut();
            return self::historyRead($id, $range);
        } finally {
            if ($own && $db->inTransaction()) {
                $db->commit();
            }
        }
    }

    private static function historyRead($id, $range)
    {
        $spans = ['24h' => 86400, '7d' => 7 * 86400, '30d' => 30 * 86400];
        $span = $spans[$range] ?? 86400;
        $to = time(); // one end for the samples, the events and the answer: a reading saved meanwhile is in none of them
        $from = $to - $span;
        // (oldest first by ID: the same order as the time, whichever of local time or UTC a row was saved in)
        $rows = self::atWhere(self::atWhere(ORM::for_table('tbl_router_samples')->where('router_id', (int) $id), 's', '>=', $from), 's', '<=', $to)
            ->order_by_asc('id')->find_array();
        $bucket = max(300, (int) ceil($span / 300));
        // address changes in the period: no speed between a reading from before one and a reading from after it
        // each as [time, ID of the last reading before it or null for a change saved before that was kept]
        $moves = array_map(function ($m) {
            return ['t' => self::ts('e', $m), 'sample' => preg_match('/^sample:(\d+)$/', (string) $m['detail'], $x) ? (int) $x[1] : null];
        }, self::atWhere(ORM::for_table('tbl_router_events')->select_many('id', 'at', 'detail')->where('router_id', (int) $id)
            ->where('type', 'moved'), 'e', '>=', $from)->find_array());
        $points = [];
        $online = 0;
        $prev = null;
        foreach ($rows as $r) {
            $t = self::ts('s', $r);
            // when the interface counters were read (a moment after the resource reading): the speeds use it, the restart test the
            // reading time above. A row without it (an older one) uses the reading time.
            $ti = !empty($r['ifat']) ? self::ts('s', ['id' => $r['id'], 'at' => $r['ifat']]) : $t;
            $online += (int) $r['online'];
            $down = $up = null;
            // a restart between the two readings resets the counters: no speed across it (the same test as the restart event)
            $restarted = $prev && self::restarted($prev['t'], $prev['uptime'], $t, $r['uptime']);
            // an uptime that went down is a restart even when the start time moved less than BOOT_SLACK (and BOOT_DRIFT, the same
            // allowance through restarted()): the earlier reading was made within that long of the router's start, and it restarted
            // again soon after. Its counters started again too
            if ($prev && $prev['uptime'] !== null && $r['uptime'] !== null && (int) $r['uptime'] < (int) $prev['uptime']) {
                $restarted = true;
            }
            foreach ($moves as $m) {
                if (!$prev) {
                    break;
                }
                if ($m['sample'] !== null) {
                    // the saved cut-off places it exactly, also within one second: only a pair that crosses it is a break
                    $crosses = (int) $prev['id'] <= $m['sample'] && (int) $r['id'] > $m['sample'];
                } else {
                    // an older change has only its time (one in the same second as either reading cannot be placed: a break)
                    $crosses = $m['t'] >= $prev['t'] && $m['t'] <= $t;
                }
                if ($crosses) {
                    $restarted = true; // (another device, or the same one reached another way: its counters do not follow on)
                }
            }
            if ($prev && !$restarted && $prev['iface'] === $r['iface'] && $r['rx'] !== null && $prev['rx'] !== null && $ti > $prev['ti']) {
                $sec = $ti - $prev['ti'];
                $dRx = (float) $r['rx'] - (float) $prev['rx'];
                $dTx = (float) $r['tx'] - (float) $prev['tx'];
                if ($dRx >= 0 && $dTx >= 0 && $sec <= self::GAP) { // a reset counter or a break in the readings gives no speed
                    $down = $dRx * 8 / $sec;
                    $up = $dTx * 8 / $sec;
                }
            }
            $prev = ['id' => $r['id'], 't' => $t, 'ti' => $ti, 'iface' => $r['iface'], 'rx' => $r['rx'], 'tx' => $r['tx'], 'uptime' => $r['uptime']];
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
            // online: any reading in the stretch answered. 'n' and 'on' say how many readings it has and how many answered, so a
            // stretch with failed readings too (n > on > 0) can be told apart from one that was online throughout
            $out[] = ['at' => $b + $bucket / 2, 'online' => $p['on'] > 0 ? 1 : 0, 'n' => $p['n'], 'on' => $p['on'], 'cpu' => $avg($p['cpu']), 'mem' => $avg($p['mem']),
                'users' => $avg($p['users']), 'down' => $avg($p['down']), 'up' => $avg($p['up'])];
        }
        $events = self::atWhere(self::atWhere(ORM::for_table('tbl_router_events')->where('router_id', (int) $id), 'e', '>=', $from), 'e', '<=', $to)
            ->order_by_desc('id')->limit(50)->find_array();
        // step: how long a stretch each point stands for (its 'at' is the middle); gap: how far apart two readings can be and
        // still follow on from each other, so the charts break their lines across a stretch with no readings
        return ['from' => $from, 'to' => $to, 'points' => $out, 'step' => $bucket, 'gap' => self::GAP, 'samples' => count($rows),
            'uptime' => $rows ? round($online / count($rows) * 100, 2) : null,
            'events' => array_map(function ($e) {
                // (a change of address keeps where its readings stop in its detail: nothing to show)
                return ['at' => self::ts('e', $e), 'type' => $e['type'], 'detail' => $e['type'] === 'moved' ? '' : $e['detail']];
            }, $events)];
    }
}
