<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Network'));
$ui->assign('_system_menu', 'network');

$action = $routes['1'];
$ui->assign('_admin', $admin);

require_once $DEVICE_PATH . DIRECTORY_SEPARATOR . "MikrotikHotspot.php";

if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
}

$leafletpickerHeader = <<<EOT
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css">
EOT;

$routelyVpn = function () {
    // Routely (an optional plugin): the card that offers its VPN for a router with no public IP, and which addresses lead through it
    return function_exists('routely_vpn_core_info') ? routely_vpn_core_info() : ['installed' => function_exists('routely'), 'devices' => 0, 'addresses' => []];
};

// How many plans and active customers use each router: delete refuses a router that is still in use, and the list says so.
// Counted by the database itself against each router's row (plans and recharges name their router), so the comparison follows
// its own rules for names (case, trailing spaces, accents), and the result is keyed by router id.
$routerUsage = function (array $ids) {
    $use = [];
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    foreach ($ids as $id) {
        $use[$id] = ['plans' => 0, 'active' => 0, 'expired' => 0];
    }
    if (!$ids) {
        return $use;
    }
    $in = implode(',', array_fill(0, count($ids), '?'));
    $db = ORM::getDb();
    $st = $db->prepare("SELECT r.id, COUNT(p.id) FROM tbl_routers r JOIN tbl_plans p ON p.routers = r.name WHERE r.id IN ($in) GROUP BY r.id");
    $st->execute($ids);
    foreach ($st->fetchAll(PDO::FETCH_NUM) as $r) {
        $use[(int) $r[0]]['plans'] = (int) $r[1];
    }
    $st = $db->prepare("SELECT r.id, u.status, COUNT(u.id) FROM tbl_routers r JOIN tbl_user_recharges u ON u.routers = r.name AND u.status IN ('on', 'off')
        WHERE r.id IN ($in) GROUP BY r.id, u.status");
    $st->execute($ids);
    foreach ($st->fetchAll(PDO::FETCH_NUM) as $r) {
        $use[(int) $r[0]][$r[1] === 'on' ? 'active' : 'expired'] = (int) $r[2];
    }
    return $use;
};

// The address and the API port are separate fields on the form, but one value in the database ("host" or "host:port"), which is
// what every part of PHPNuxBill reads. An empty port keeps the address as typed; 8728 (the default) is left off.
$joinAddress = function ($host, $port) {
    $host = RouterAddress::normalize($host); // "[v6] : 8877" or "host : 99" saved as one spelling, as it is connected to
    $port = trim((string) $port);
    if ($port === '' || $host === '') {
        return $host;
    }
    // a port typed into the address is checked before it is dropped in favour of the port field: only whole digits 1 to 65535
    // (or none, or 0) are a port; anything else is kept as typed and refused by the address checks
    $embeddedOk = function ($p) {
        return $p === '' || (ctype_digit($p) && (int) $p <= 65535);
    };
    if (preg_match('/^\[([^\[\]]+)\](?::(\d+))?$/', $host, $m)) {
        if (!filter_var($m[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) || !$embeddedOk($m[2] ?? '')) {
            return $host; // brackets hold only an IPv6 address: "[router.example]" is kept as typed and refused by the address checks
        }
        $host = $m[1]; // "[v6]" or "[v6]:port" typed into the address: the port field wins
    } elseif ($host[0] === '[') {
        return $host; // brackets around something else ("[v6]:abc"): kept as typed, and refused by the address checks
    } elseif (substr_count($host, ':') === 1) {
        [$h0, $p0] = explode(':', $host);
        if (!$embeddedOk(trim($p0))) {
            return $host;
        }
        $host = $h0; // a port typed into the address too: the port field wins
    }
    if ((int) $port === 8728) {
        return $host;
    }
    // an IPv6 address has colons of its own, so with a port it is written [address]:port
    return (strpos($host, ':') !== false ? '[' . $host . ']' : $host) . ':' . (int) $port;
};
// "host:port" as the two form fields (an IPv6 address or a plain host keeps everything in the address field)
$splitAddress = function ($addr) {
    $addr = RouterAddress::normalize($addr);
    if (preg_match('/^\[(.+)\]:(\d+)$/', $addr, $m)) {
        return [$m[1], (int) $m[2] > 0 ? $m[2] : '']; // [IPv6]:port
    }
    if (substr_count($addr, ':') === 1) {
        [$h, $p] = explode(':', $addr);
        if (ctype_digit($p)) {
            return [$h, (int) $p > 0 ? $p : '']; // a stored ":0" is no port
        }
    }
    return [$addr, ''];
};
// whether another router already uses this API endpoint: the same host (letter case aside) and the same port, an address
// without a port being on 8728, so "10.0.0.1" and "10.0.0.1:8728" are one router while "10.0.0.1:8729" is another.
// $enabledOnly: only an enabled router counts (enabling one: a disabled draft with the same address is no conflict)
$endpointKey = function ($a) {
    [$h, $p] = RouterAddress::split($a);
    $h = strtolower(trim((string) $h));
    // an IPv6 address has many spellings ("2001:db8::1", "2001:0db8:0:0::1"): compared in one form
    if (filter_var($h, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && ($bin = @inet_pton($h)) !== false) {
        $h = inet_ntop($bin);
    }
    return $h . '|' . ((int) $p ?: 8728);
};
$endpointTaken = function ($address, $exceptId = 0, $enabledOnly = false) use ($endpointKey) {
    $key = $endpointKey;
    $want = $key($address);
    foreach (ORM::for_table('tbl_routers')->select_many('id', 'ip_address', 'enabled')->find_array() as $r) {
        if ((int) $r['id'] !== (int) $exceptId && trim((string) $r['ip_address']) !== '' && (!$enabledOnly || (int) $r['enabled'] === 1)
            && $key($r['ip_address']) === $want) {
            return true;
        }
    }
    return false;
};
// Add, edit, delete and bulk enable each check and then write (an address is free, a router is not in use, a rename and the
// plans that follow it): one at a time (a database lock, so on every PHP worker), or two requests could each find the way clear
// and both write. Taking it returns whether it was got (false after 15 seconds of waiting, or on an error: the caller stops and
// asks to try again). Released when the request ends at the latest.
$endpointLock = function ($on = true) {
    try {
        if (!$on) {
            ORM::getDb()->query("SELECT RELEASE_LOCK('phpnuxbill_router_endpoint')");
            return true;
        }
        $got = (int) ORM::getDb()->query("SELECT GET_LOCK('phpnuxbill_router_endpoint', 15)")->fetchColumn() === 1;
        if ($got) {
            // r2() and _alert() exit, and a finally block does not run on exit: released at shutdown on this same connection too
            // (idempotent: releasing a lock that is no longer held does nothing)
            static $shutdownSet = false;
            if (!$shutdownSet) {
                $shutdownSet = true;
                register_shutdown_function(function () {
                    try {
                        ORM::getDb()->query("SELECT RELEASE_LOCK('phpnuxbill_router_endpoint')");
                    } catch (Throwable $ignored) {
                    }
                });
            }
        }
        return $got;
    } catch (Throwable $e) {
        return !$on;
    }
};
// the saved address column is varchar(128): the joined host and port must fit it, or storage would cut or refuse what Test now just used
$addressTooLong = function ($address) {
    return mb_strlen((string) $address) > 128;
};
// whether a lock may be taken while another one is held (MikrotikVpn::nestedLocks(): false on a database that frees the held lock
// when it takes another)
$expLocksOk = function () {
    if (DeviceLoader::load('MikrotikVpn') === null) {
        return false;
    }
    return MikrotikVpn::nestedLocks();
};
// Cached health readings (system/cache/router_health) are dropped whenever a router's enabled state changes, and a reading that
// began before the change is refused by the writer (it compares with the moment written here): a router disabled and enabled
// again is read afresh, never from what was read before.
$healthReset = function ($id) {
    global $CACHE_PATH;
    $dir = $CACHE_PATH . DIRECTORY_SEPARATOR . 'router_health';
    $id = (int) $id;
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    @file_put_contents($dir . DIRECTORY_SEPARATOR . $id . '.reset', sprintf('%.6F', microtime(true)), LOCK_EX);
    foreach ([$id . '.json', $id . '.live.json'] as $f) {
        @unlink($dir . DIRECTORY_SEPARATOR . $f);
    }
};
$busyText = function () {
    return Lang::T('Another change to the routers is being saved. Try again in a moment.');
};
// an address whose host is missing (":8729", "[]:8729") or is not an IPv4 address, an IPv6 address or a host name ("router
// example", "10.0.0.256", "[router.example]"): not an address. An empty one is allowed (a disabled draft).
$hostMissing = function ($address) {
    $address = RouterAddress::normalize($address);
    if ($address === '') {
        return false;
    }
    [$h] = RouterAddress::split($address);
    $h = (string) $h;
    if ($h === '' || trim($h) !== $h) {
        return true;
    }
    if ($address[0] === '[') {
        // brackets only ever hold an IPv6 address
        return !filter_var($h, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }
    if (filter_var($h, FILTER_VALIDATE_IP)) {
        return false;
    }
    // digits and dots only, yet not an IPv4 address: a mistyped address, not a host name
    if (preg_match('/^[0-9.]+$/', $h)) {
        return true;
    }
    return !filter_var($h, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
};
$validPort = function ($port) {
    $port = trim((string) $port);
    return $port === '' || (ctype_digit($port) && (int) $port >= 1 && (int) $port <= 65535);
};
// the port that will be used, after the port field has replaced any typed into the address: one written into the address
// ("host:99999", "host:abc", "[v6]:0") must be valid too. A host name or IPv6 address without a port is fine.
$validAddressPort = function ($address) use ($validPort) {
    $address = RouterAddress::normalize($address); // checked in the spelling that is saved and connected to
    if (preg_match('/^\[[^\]]+\](?::(.*))?$/', $address, $m)) {
        return !isset($m[1]) || ($m[1] !== '' && $validPort($m[1]));
    }
    if (substr_count($address, ':') === 1) {
        $p = explode(':', $address)[1];
        return $p !== '' && $validPort($p);
    }
    return true;
};

// The "site" column (a group for filtering many routers) arrives with the 2026.11.18 update: until the database has it, the field
// is not offered and nothing tries to save it
$hasSite = function () {
    static $has = null;
    if ($has === null) {
        try {
            $has = (bool) ORM::getDb()->query("SHOW COLUMNS FROM `tbl_routers` LIKE 'site'")->fetch();
        } catch (Throwable $e) {
            $has = false;
        }
    }
    return $has;
};

// The RouterOS helpers live in RouterApi (the cron's nightly backups use them too)
$rosText = Closure::fromCallable([RouterApi::class, 'text']);

// "1w2d03:04:05" or "12d4h31m5s" (RouterOS uptime) as "9d 3h 4m"
// an uptime as "2d 3h 4m" (the same reading of RouterOS uptimes as the monitor's, units added up in any order)
$rosUptime = function ($u) {
    $sec = RouterMonitor::seconds($u);
    if ($sec === null) {
        return (string) $u;
    }
    $days = intdiv($sec, 86400);
    $hours = intdiv($sec % 86400, 3600);
    $mins = intdiv($sec % 3600, 60);
    return trim(($days ? $days . 'd ' : '') . ($days || $hours ? $hours . 'h ' : '') . $mins . 'm');
};

// a connection for this page: dropped when the page ends, however it ends (an answer, a redirect, an error), without the
// library's goodbye, which waits for the router to reply and would hold the page on a router that stopped answering
$rosConnect = function ($ip, $user, $pass, $timeout = 5) {
    [$c, $err] = RouterApi::connect($ip, $user, $pass, $timeout);
    if ($c) {
        register_shutdown_function(function () use ($c) {
            method_exists($c, 'abort') ? $c->abort() : null;
        });
    }
    return [$c, $err];
};

// whether the router is still enabled and still has the endpoint and login it was chosen with (read now, for the actions that
// change something on it: called with the endpoint lock held, so an edit cannot come between this and the command)
$rosSame = function ($d) use ($endpointKey) {
    $live = ORM::for_table('tbl_routers')->find_one($d['id']);
    return $live && (int) $live['enabled'] === 1 && trim((string) $live['ip_address']) !== ''
        && [(string) $live['name'], $endpointKey($live['ip_address']), (string) $live['username'], (string) $live['password']]
        === [(string) $d['name'], $endpointKey($d['ip_address']), (string) $d['username'], (string) $d['password']];
};

$rosSend = Closure::fromCallable([RouterApi::class, 'send']);
$rosError = Closure::fromCallable([RouterApi::class, 'error']);

// RouterApi::rows() for a list that can be long (sessions, interfaces): only the first $cap items are made into rows, the rest
// are only counted, so the page keeps no second full copy of the reply. Returns [rows, how many the router listed]; a refusal is
// thrown (code 403), as rows() does.
$rowsCapped = function ($c, $command, array $props, $deadline, $cap, array $where = []) use ($rosSend, $rosError, $rosText) {
    $q = null;
    foreach ($where as $k => $v) {
        $q = $q ? $q->andWhere($k, $v) : \PEAR2\Net\RouterOS\Query::where($k, $v);
    }
    $req = new \PEAR2\Net\RouterOS\Request($command, $q);
    $req->setArgument('.proplist', implode(',', $props));
    $res = $rosSend($c, $req, $deadline);
    if (($err = $rosError($res)) !== null) {
        throw new Exception($err, 403);
    }
    $out = [];
    $total = 0;
    foreach ($res as $r) {
        if ($r->getType() !== \PEAR2\Net\RouterOS\Response::TYPE_DATA || ++$total > $cap) {
            continue;
        }
        $row = [];
        foreach ($props as $p) {
            $row[$p] = $rosText($r->getProperty($p));
        }
        $out[] = $row;
    }
    return [$out, $total];
};

// What a router says about itself: identity, board, RouterOS version, uptime, load, memory, disk, temperature and users online.
// Each part is asked for separately, so a router without one (no hotspot, no health sensor) still answers the rest.
$rosHealth = function ($c, $withIfaces = false) use ($rosText, $rosUptime, $rosSend, $rosError) {
    $until = RouterApi::now() + 8; // every part shares one deadline
    $h = ['identity' => '', 'board' => '', 'version' => '', 'uptime' => '', 'cpu' => null, 'cores' => null,
        'mem_used' => null, 'mem_total' => null, 'disk_used' => null, 'disk_total' => null, 'temp' => null,
        'hotspot' => null, 'pppoe' => null];
    // the one part that must answer: a refusal (this user may not read /system) or no data is the "cannot read" error
    $res = $rosSend($c, new \PEAR2\Net\RouterOS\Request('/system/resource/print'), $until);
    if (($err = $rosError($res)) !== null) {
        throw new Exception($err, 403); // refused (no permission): told apart from no answer (408)
    }
    $r = null;
    foreach ($res as $item) {
        if ($item->getType() === \PEAR2\Net\RouterOS\Response::TYPE_DATA) {
            $r = $item;
            break;
        }
    }
    if (!$r) {
        throw new Exception(Lang::T('The router sent no system details'));
    }
    $h['board'] = (string) $rosText($r->getProperty('board-name'));
    $h['version'] = (string) $rosText($r->getProperty('version'));
    $rawUptime = $rosText($r->getProperty('uptime'));
    $h['uptime'] = $rosUptime($rawUptime);
    $h['uptime_s'] = RouterMonitor::seconds($rawUptime); // exact seconds, for restart detection (the text above is to the minute)
    $num = function ($k) use ($r, $rosText) {
        $v = $rosText($r->getProperty($k));
        return is_numeric($v) ? (float) $v : null;
    };
    $h['cpu'] = $num('cpu-load');
    $h['cores'] = $num('cpu-count');
    $memFree = $num('free-memory');
    $h['mem_total'] = $num('total-memory');
    $h['mem_used'] = $h['mem_total'] !== null && $memFree !== null ? $h['mem_total'] - $memFree : null;
    $diskFree = $num('free-hdd-space');
    $h['disk_total'] = $num('total-hdd-space');
    $h['disk_used'] = $h['disk_total'] !== null && $diskFree !== null ? $h['disk_total'] - $diskFree : null;
    // the rest is optional and asked after the figures that matter: if one of them times out (and so drops the connection),
    // the readings already taken are still returned
    try {
        foreach ($rosSend($c, new \PEAR2\Net\RouterOS\Request('/system/identity/print'), $until) as $item) {
            if ($item->getType() === \PEAR2\Net\RouterOS\Response::TYPE_DATA) {
                $h['identity'] = (string) $rosText($item->getProperty('name'));
                break;
            }
        }
    } catch (Throwable $e) {
    }
    try {
        // RouterOS 7 lists one sensor per item (name, value); RouterOS 6 gives them as properties of one item
        foreach ($rosSend($c, new \PEAR2\Net\RouterOS\Request('/system/health/print'), $until) as $s) {
            if ($s->getType() !== \PEAR2\Net\RouterOS\Response::TYPE_DATA) {
                continue;
            }
            $name = (string) $rosText($s->getProperty('name'));
            $val = in_array($name, ['temperature', 'cpu-temperature', 'board-temperature1'], true) ? $rosText($s->getProperty('value'))
                : ($rosText($s->getProperty('cpu-temperature')) ?? $rosText($s->getProperty('temperature')));
            if (is_numeric($val)) {
                $h['temp'] = (float) $val;
                break;
            }
        }
    } catch (Throwable $e) {
    }
    // (PPP sessions are PPPoE only: L2TP, SSTP and the other tunnels are not customers)
    foreach (['hotspot' => '/ip/hotspot/active/print', 'pppoe' => '/ppp/active/print'] as $k => $menu) {
        try {
            $q = new \PEAR2\Net\RouterOS\Request($menu, $k === 'pppoe' ? \PEAR2\Net\RouterOS\Query::where('service', 'pppoe') : null);
            $q->setArgument('count-only', '');
            $n = $rosText($rosSend($c, $q, $until)->end()->getProperty('ret'));
            $h[$k] = is_numeric($n) ? (int) $n : null;
        } catch (Throwable $e) {
        }
    }
    if ($withIfaces) {
        // byte counters of the running interfaces; the page turns two readings into download and upload speeds
        $h['ifaces'] = [];
        try {
            $q = new \PEAR2\Net\RouterOS\Request('/interface/print');
            $q->setArgument('.proplist', '.id,name,type,rx-byte,tx-byte,running,disabled');
            foreach ($rosSend($c, $q, $until) as $i) {
                if ($i->getType() !== \PEAR2\Net\RouterOS\Response::TYPE_DATA || $rosText($i->getProperty('running')) !== 'true'
                    || $rosText($i->getProperty('disabled')) === 'true' || count($h['ifaces']) >= 64) {
                    continue;
                }
                $rx = $rosText($i->getProperty('rx-byte'));
                $tx = $rosText($i->getProperty('tx-byte'));
                if (is_numeric($rx) && is_numeric($tx)) {
                    $h['ifaces'][] = ['id' => (string) $rosText($i->getProperty('.id')), 'name' => (string) $rosText($i->getProperty('name')), 'type' => (string) $rosText($i->getProperty('type')), 'rx' => (float) $rx, 'tx' => (float) $tx];
                }
            }
        } catch (Throwable $e) {
        }
    }
    $h['at'] = round(microtime(true), 3); // when it was read: speeds are measured between two readings
    return $h;
};

// Shared hosting (cPanel) often blocks outgoing connections to the router's API port: say so when the connection itself failed
$rosHint = function ($err) {
    if (preg_match('/refused|timed out|time limit|failed to connect|no route|unreachable|network is|getaddrinfo/i', (string) $err)) {
        return Lang::T('If PHPNuxBill runs on shared hosting (cPanel), the host may block outgoing connections to this port: ask them to open it, or reach the router through a VPN.');
    }
    return '';
};

// Live health is on unless turned off in Network > Routers > Settings
$healthOn = ($config['router_health'] ?? '1') !== '0';

// the sites already in use, offered as suggestions on the form and as the filter on the list
$siteNames = function () use ($hasSite) {
    if (!$hasSite()) {
        return [];
    }
    return array_values(array_filter(array_column(ORM::for_table('tbl_routers')->distinct()->select('site')->where_not_equal('site', '')->order_by_asc('site')->find_array(), 'site'), 'strlen'));
};
$ui->assign('has_site', $hasSite());

switch ($action) {
    case 'settings':
        // everything about routers in one place: Router Check, live health, history, alerts and nightly backups. Only these
        // settings can be saved here, each checked before it is kept.
        $fields = [
            'router_check' => ['0', '1'], 'router_health' => ['1', '0'], 'router_monitor' => ['1', '0'], 'router_alerts' => ['0', '1'],
            'router_alert_telegram' => ['1', '0'], 'router_alert_email' => ['0', '1'], 'router_alert_via' => ['wa', 'sms'], 'router_backup' => ['0', '1'], 'router_backup_plain_ok' => ['0', '1'],
        ];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($_app_stage == 'Demo') {
                r2(getUrl('routers/settings'), 'e', 'You cannot perform this action in Demo mode');
            }
            if (!Csrf::check(_post('csrf_token'))) {
                r2(getUrl('routers/settings'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
            }
            $values = [];
            foreach ($fields as $k => $allowed) {
                $v = _post($k, $allowed[0]);
                $values[$k] = in_array($v, $allowed, true) ? $v : $allowed[0];
            }
            $phone = preg_replace('/[^0-9+]/', '', _post('router_alert_phone'));
            if ($phone !== '' && !preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
                r2(getUrl('routers/settings'), 'e', Lang::T('The alert phone number must be 7 to 15 digits, with an optional + in front.'));
            }
            $values['router_alert_phone'] = $phone;
            $values['router_backup_keep'] = (string) max(1, min(30, (int) _post('router_backup_keep', '7')));
            // the backup password: kept as typed (not trimmed: spaces count), never shown again; empty keeps the saved one
            $backupPass = (string) ($_POST['router_backup_password'] ?? '');
            if ($backupPass !== '') {
                if (mb_strlen($backupPass, 'UTF-8') < 8 || mb_strlen($backupPass, 'UTF-8') > 128) { // characters, not bytes
                    r2(getUrl('routers/settings'), 'e', Lang::T('The backup password must be 8 to 128 characters.'));
                }
                $values['router_backup_password'] = $backupPass;
            }
            // no backup is made without a password: nightly backups are not turned on until one is saved or typed here
            if ($values['router_backup'] === '1' && $backupPass === '' && (string) ($config['router_backup_password'] ?? '') === '') {
                r2(getUrl('routers/settings'), 'e', Lang::T('Nightly backups need a backup password. Type one to turn them on.'));
            }
            // all or nothing: a write that fails leaves the settings as they were
            $db = ORM::get_db();
            try {
                $db->beginTransaction();
                foreach ($values as $k => $v) {
                    $row = ORM::for_table('tbl_appconfig')->where('setting', $k)->find_one() ?: ORM::for_table('tbl_appconfig')->create();
                    $row->setting = $k;
                    $row->value = $v;
                    $row->save();
                }
                $db->commit();
            } catch (Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                r2(getUrl('routers/settings'), 'e', Lang::T('The settings could not be saved') . ': ' . $e->getMessage());
            }
            _log('[' . $admin['username'] . ']: Router settings saved', $admin['user_type'], $admin['id']);
            r2(getUrl('routers/settings'), 's', Lang::T('Settings Saved Successfully'));
        }
        $ui->assign('_title', Lang::T('Router Settings'));
        $ui->assign('history_ready', RouterMonitor::ready());
        $ui->display('admin/routers/settings.tpl');
        break;

    case 'add':
        run_hook('view_add_routers'); #HOOK
        $ui->assign('routely_vpn', $routelyVpn());
        $ui->assign('sites', $siteNames());
        $ui->assign('nf_host', '');
        $ui->assign('nf_port', '');
        // Routely's VPN page opens this form for a port forward (?address=host:port&name=...): start with them filled in
        $pre = trim((string) _get('address'));
        if (preg_match('/^[A-Za-z0-9.-]{1,120}:\d{1,5}$/', $pre)) {
            [$preHost, $prePort] = $splitAddress($pre);
            $ui->assign('d', ['ip_address' => $pre, 'name' => mb_substr(trim((string) _get('name')), 0, 32)]);
            $ui->assign('nf_host', $preHost);
            $ui->assign('nf_port', $prePort);
        }
        $ui->display('admin/routers/add.tpl');
        break;

    case 'edit':
        $id  = $routes['2'];
        $d = ORM::for_table('tbl_routers')->find_one($id);
        if (!$d) {
            $d = ORM::for_table('tbl_routers')->where_equal('name', _get('name'))->find_one();
        }
        $ui->assign('xheader', $leafletpickerHeader);
        if ($d) {
            $ui->assign('d', $d);
            run_hook('view_router_edit'); #HOOK
            $ui->assign('routely_vpn', $routelyVpn());
            $ui->assign('sites', $siteNames());
            [$nfHost, $nfPort] = $splitAddress($d['ip_address']);
            $ui->assign('nf_host', $nfHost);
            $ui->assign('nf_port', $nfPort);
            $ui->display('admin/routers/edit.tpl');
        } else {
            r2(getUrl('routers/list'), 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'delete':
        // a form post with the CSRF token: a plain link could be followed by another site
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check(_post('csrf_token'))) {
            r2(getUrl('routers/list'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $id  = $routes['2'];
        // one at a time with the edits: a rename moving the plans to the new name cannot run between the check and the delete
        if (!$endpointLock()) {
            r2(getUrl('routers/list'), 'e', $busyText());
        }
        $d = ORM::for_table('tbl_routers')->find_one($id);
        if (!$d) {
            r2(getUrl('routers/list'), 'e', Lang::T('Data Not Found'));
        }
        // plans and active customers point at a router by its name; deleting it would leave them pointing at nothing
        $use = $routerUsage([$d['id']])[(int) $d['id']] ?? ['plans' => 0, 'active' => 0];
        if ($use['plans'] > 0 || $use['active'] > 0) {
            r2(getUrl('routers/list'), 'e', Lang::T('This router is still in use') . ': ' . htmlspecialchars($d['name']) . ' (' . $use['plans'] . ' ' . Lang::T('plans') . ', '
                . $use['active'] . ' ' . Lang::T('active customers') . '). ' . Lang::T('Move them to another router first, or disable this router instead.'));
        }
        run_hook('router_delete'); #HOOK
        // checked again in the statement that deletes, so a plan or recharge saved since the check above keeps the router
        $st = ORM::getDb()->prepare("DELETE FROM tbl_routers WHERE id = ?
            AND NOT EXISTS (SELECT 1 FROM tbl_plans p WHERE p.routers = tbl_routers.name)
            AND NOT EXISTS (SELECT 1 FROM tbl_user_recharges u WHERE u.routers = tbl_routers.name AND u.status = 'on')");
        $st->execute([(int) $d['id']]);
        if ($st->rowCount() === 0) {
            r2(getUrl('routers/list'), 'e', Lang::T('This router is still in use') . ': ' . htmlspecialchars($d['name']) . '. '
                . Lang::T('Move them to another router first, or disable this router instead.'));
        }
        _log('[' . $admin['username'] . ']: ' . Lang::T('Router deleted') . ' ' . $d['name'], $admin['user_type'], $admin['id']);
        r2(getUrl('routers/list'), 's', Lang::T('Data Deleted Successfully'));
        break;

    case 'test':
        // "Test now" on the router form: try these details without saving anything, and say what answered
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check(_post('csrf_token'))) {
            echo json_encode(['ok' => false, 'error' => Lang::T('Invalid or Expired CSRF Token') . '. ' . Lang::T('Reload the page and try again.')]);
            exit;
        }
        $ip = $joinAddress(_post('ip_address'), _post('api_port'));
        if (!$validPort(_post('api_port')) || !$validAddressPort($ip)) {
            echo json_encode(['ok' => false, 'error' => Lang::T('The API port must be a number from 1 to 65535')]);
            exit;
        }
        if ($addressTooLong($ip)) {
            echo json_encode(['ok' => false, 'error' => Lang::T('The address is too long (128 characters at most)')]);
            exit;
        }
        $user = _post('username');
        $pass = _post('password');
        if ($ip === '' || $user === '' || $hostMissing($ip)) {
            echo json_encode(['ok' => false, 'error' => Lang::T('Enter the IP address and username first.')]);
            exit;
        }
        // the secret is tested as typed: the edit form shows the saved one, and an emptied field is saved empty, so the test
        // tries exactly what Save would keep
        session_write_close(); // the router may take seconds: do not keep the admin's other pages waiting on the session
        [$c, $err] = $rosConnect($ip, $user, $pass, 8);
        if (!$c) {
            echo json_encode(['ok' => false, 'error' => $err, 'hint' => $rosHint($err)]);
            exit;
        }
        try {
            $h = $rosHealth($c);
            // a router name in an old encoding is not valid UTF-8: replaced character by character instead of failing the answer
            echo json_encode(['ok' => true, 'identity' => $h['identity'], 'board' => $h['board'], 'version' => $h['version'], 'uptime' => $h['uptime']], JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (Throwable $e) {
            if ($e->getCode() === 403) {
                // logged in, but this user may not read /system: the router refused, so the connection works
                echo json_encode(['ok' => true, 'identity' => '', 'board' => '', 'version' => '', 'uptime' => '', 'note' => Lang::T('Connected, but this user cannot read the system details.')]);
            } else {
                // no answer in time, or the connection broke: the test failed
                echo json_encode(['ok' => false, 'error' => $e->getMessage(), 'hint' => $rosHint($e->getMessage())], JSON_INVALID_UTF8_SUBSTITUTE);
            }
        }
        exit;

    case 'bulk':
        // enable or disable the routers ticked on the list
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check(_post('csrf_token'))) {
            r2(getUrl('routers/list'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $op = _post('op');
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])))));
        if (!in_array($op, ['enable', 'disable'], true) || !$ids) {
            r2(getUrl('routers/list'), 'e', Lang::T('Choose at least one router'));
        }
        $n = 0;
        $skipped = [];
        $taken = [];
        $badAddr = [];
        // both operations wait for the lock: a router sync checks each customer against the router's enabled state under it
        if (!$endpointLock()) {
            r2(getUrl('routers/list'), 'e', $busyText());
        }
        foreach (ORM::for_table('tbl_routers')->where_in('id', $ids)->find_many() as $r) {
            // as on the form: an enabled router needs its address and username
            if ($op === 'enable' && (trim((string) $r['ip_address']) === '' || trim((string) $r['username']) === '')) {
                $skipped[] = $r['name'];
                continue;
            }
            // and an address the form would accept: a host, and a valid API port when one is written into it. A legacy ":0"
            // ("router.example:0", "[v6]:0") is the default port, as Edit and the connection read it, so it is checked without it
            $addr = preg_replace('/^(\[[^\[\]]*\]|[^:]*):0+$/', '$1', RouterAddress::normalize($r['ip_address']));
            if ($op === 'enable' && (trim((string) $addr) === '' || $hostMissing($addr) || !$validAddressPort($addr))) {
                $badAddr[] = $r['name'];
                continue;
            }
            // nor is a router enabled on the address of another enabled one (checked one by one, so of two ticked routers on
            // one address only the first is enabled)
            if ($op === 'enable' && (int) $r['enabled'] !== 1 && $endpointTaken($r['ip_address'], $r['id'], true)) {
                $taken[] = $r['name'];
                continue;
            }
            $changed = (int) $r['enabled'] !== ($op === 'enable' ? 1 : 0);
            if ($op === 'enable' && (string) $r['ip_address'] !== $addr) {
                $r->ip_address = $addr; // the legacy ":0" is saved away too: what was validated is what is stored and connected to
            }
            $r->enabled = $op === 'enable' ? 1 : 0;
            $r->save();
            if ($changed) {
                $healthReset($r['id']);
            }
            $n++;
        }
        $endpointLock(false);
        _log('[' . $admin['username'] . ']: ' . ($op === 'enable' ? 'Routers enabled' : 'Routers disabled') . ': ' . $n, $admin['user_type'], $admin['id']);
        r2(getUrl('routers/list'), $skipped || $taken || $badAddr ? 'w' : 's', ($op === 'enable' ? Lang::T('Routers enabled') : Lang::T('Routers disabled')) . ': ' . $n
            . ($badAddr ? '. ' . Lang::T('Not enabled, the address or its API port is not valid') . ': ' . htmlspecialchars(implode(', ', $badAddr)) : '')
            . ($skipped ? '. ' . Lang::T('Not enabled, the IP address or username is missing') . ': ' . htmlspecialchars(implode(', ', $skipped)) : '')
            . ($taken ? '. ' . Lang::T('Not enabled, another enabled router has the same address') . ': ' . htmlspecialchars(implode(', ', $taken)) : ''));
        break;

    case 'action':
        // things done on the router itself, for the SuperAdmin only, each confirmed on the page first
        if ($admin['user_type'] !== 'SuperAdmin') {
            r2(getUrl('routers/list'), 'e', Lang::T('Only a SuperAdmin can do this'));
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check(_post('csrf_token'))) {
            r2(getUrl('routers/list'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $op = _post('op');
        $d = ORM::for_table('tbl_routers')->find_one((int) ($routes['2'] ?? 0));
        // as the detail tabs: a disabled router (or one without an address) is not connected to or sent anything
        if (!$d || !in_array($op, ['reboot', 'kick', 'backup', 'sync'], true) || (int) $d['enabled'] !== 1 || trim((string) $d['ip_address']) === '') {
            r2(getUrl('routers/list'), 'e', Lang::T('Data Not Found'));
        }
        if ($_app_stage == 'Demo') {
            r2(getUrl('routers/list'), 'e', Lang::T('Not available in Demo mode'));
        }
        $back = getUrl('routers/list');
        $who = '[' . $admin['username'] . ']: ';
        if ($op === 'sync') {
            // send every active customer of this router to it again, as Plans > Sync does for all routers. The session is let
            // go meanwhile (every read from the router is time-limited, but there can be many), so the admin's other pages do not
            // wait; it is taken again for the result message.
            set_time_limit(0);
            session_write_close();
            $done = 0;
            $failed = [];
            $unsure = []; // sent by a device that cannot say whether the router took it (no sync_customer of its own)
            $partial = []; // saved, but a later step was refused (clearing a fixed IP address, or an address off the Expired list)
                // the device finds its router by the plan's router name: with another router of the same name (saved before names
            // were checked) it could be sent to that one instead, so the sync is refused until one of them is renamed
            if (ORM::for_table('tbl_routers')->where('name', $d['name'])->where_not_equal('id', $d['id'])->count() > 0) {
                @session_start();
                r2($back, 'e', htmlspecialchars($d['name']) . ': ' . Lang::T('Another router has the same name, so its customers cannot be told apart. Rename one of them first.'));
            }
            // the device connects to whichever router has the plan's router name when it is called: after a rename (this router's
            // name given to another one) that would be another router. So each customer is sent under the routers lock (renames,
            // moves and deletes wait meanwhile), after checking this router still has the name and endpoint it had when chosen;
            // when it has not (or the lock cannot be got), the sync stops there
            $selected = [(string) $d['name'], $endpointKey($d['ip_address']), (string) $d['username']];
            $stoppedWhy = null;
            foreach (ORM::for_table('tbl_user_recharges')->where('status', 'on')->where('routers', $d['name'])->find_many() as $tur) {
                $endpointLock(false); // the previous customer's
                if (!$endpointLock()) {
                    $stoppedWhy = $busyText();
                    break;
                }
                $live = ORM::for_table('tbl_routers')->find_one($d['id']);
                if (!$live || (int) $live['enabled'] !== 1 || [(string) $live['name'], $endpointKey($live['ip_address']), (string) $live['username']] !== $selected) {
                    $stoppedWhy = Lang::T('The router was changed during the sync, so the rest of its customers were not sent. Run the sync again.');
                    break;
                }
                // the cron's expiry of this customer's recharge takes the same per-customer lock (phpnuxbill_exp_c_<id>) across taking
                // the access off the router and marking the recharge off: held here across the re-read and the device call, a sync
                // cannot send access back after an expiry that read the recharge as on. A wait of a few seconds at most. Taken only
                // where the database keeps the locks a connection already holds (the endpoint lock is held): on one that frees it
                // when another is taken it is not nested
                $expLock = null;
                if ($expLocksOk()) {
                    $expLock = 'phpnuxbill_exp_c_' . (int) $tur['customer_id'];
                    try {
                        $gotExp = (int) ORM::getDb()->query('SELECT GET_LOCK(' . ORM::getDb()->quote($expLock) . ', 5)')->fetchColumn() === 1;
                    } catch (Throwable $e) {
                        $gotExp = false;
                    }
                    if (!$gotExp) {
                        $expLock = null;
                        $failed[] = $tur['username']; // expiring right now (or the lock is unavailable): not sent, run the sync again
                        continue;
                    }
                }
                try {
                    // the list was read at the start: a recharge that has expired since (the cron) is not sent back to the router
                    // read again in full: its plan or router may have changed since too (a renewal, a move), and the current ones are used
                    // by the same bound predicate as the list above (not a PHP comparison of names): still on, and still on this router
                    $now = ORM::for_table('tbl_user_recharges')->where('id', $tur['id'])->where('status', 'on')->where('routers', $d['name'])->find_one();
                    if (!$now) {
                        continue; // turned off, or moved to another router since the list was read: not this router's to sync
                    }
                    $tur = $now;
                    // past its expiry but still "on" (the cron is retrying an unfinished clean-up): access is not sent back
                    if (strtotime($tur['expiration'] . ' ' . $tur['time']) <= time()) {
                        continue;
                    }
                    $p = ORM::for_table('tbl_plans')->where('id', $tur['plan_id'])->where('routers', $d['name'])->find_one();
                    $c = ORM::for_table('tbl_customers')->find_one($tur['customer_id']);
                    // the device sends a customer to its plan's router: a plan now on another router is not this router's to sync
                    if (!$p || !$c) {
                        $failed[] = $tur['username'];
                        continue;
                    }
                    try {
                        $dvc = Package::getDevice($p);
                        if (!file_exists($dvc)) {
                            throw new Exception(Lang::T('Devices Not Found'));
                        }
                        require_once $dvc;
                        // MikrotikVpn takes named locks of its own: where the database frees the lock held when it takes another
                        // (before MySQL 5.7.5 / MariaDB 10.0.2) the endpoint lock would be let go mid-sync, so VPN is not synced there
                        if ($p['device'] === 'MikrotikVpn' && !MikrotikVpn::nestedLocks()) {
                            throw new Exception(Lang::T('VPN customers cannot be synced on this database version while the router is protected from changes'));
                        }
                        $dev = new $p['device'];
                        if (method_exists($dev, 'sync_customer')) {
                            $dev->sync_customer($c, $p); // a change the router refuses is thrown (caught below)
                            $done++;
                        } else {
                            $dev->add_customer($c, $p);
                            $unsure[] = $tur['username'];
                        }
                    } catch (Throwable $e) {
                        if ($e->getCode() === 207) { // the Mikrotik drivers' "saved, but a follow-up step failed" (MikrotikPppoe::PARTIAL)
                            $partial[] = $tur['username'];
                        } elseif ($e->getCode() === 409) {
                            // no active recharge of this plan on this router any more: nothing was sent, and nothing failed
                        } else {
                            $failed[] = $tur['username'];
                        }
                    }
                } finally {
                    if ($expLock !== null) {
                        try {
                            ORM::getDb()->query('SELECT RELEASE_LOCK(' . ORM::getDb()->quote($expLock) . ')');
                        } catch (Throwable $e) {
                        }
                    }
                }
            }
            $endpointLock(false);
            @session_start();
            _log($who . 'Router sync ' . $d['name'] . ': ' . $done . ' done, ' . count($partial) . ' partly done, ' . count($failed) . ' failed, ' . count($unsure) . ' not confirmed' . ($stoppedWhy !== null ? ', stopped: ' . $stoppedWhy : ''), $admin['user_type'], $admin['id']);
            r2($back, $stoppedWhy !== null ? 'e' : ($failed || $unsure || $partial ? 'w' : 's'), ($stoppedWhy !== null ? htmlspecialchars($stoppedWhy) . ' ' : '') . Lang::T('Customers sent to the router') . ': ' . $done
                . ($partial ? '. ' . Lang::T('Saved, but a follow-up step failed') . ': ' . htmlspecialchars(implode(', ', array_slice($partial, 0, 20))) . (count($partial) > 20 ? ' …' : '') : '')
                . ($failed ? '. ' . Lang::T('Not sent') . ': ' . htmlspecialchars(implode(', ', array_slice($failed, 0, 20))) . (count($failed) > 20 ? ' …' : '') : '')
                . ($unsure ? '. ' . Lang::T('Sent, but the router did not confirm them') . ': ' . htmlspecialchars(implode(', ', array_slice($unsure, 0, 20))) . (count($unsure) > 20 ? ' …' : '') : ''));
        }
        // checked again right before the connection: a disable or a move (name, address, user or password changed) saved since the
        // router was read cancels the action, and an unchanged router goes on with what it has now
        $liveR = ORM::for_table('tbl_routers')->find_one($d['id']);
        if (!$liveR || (int) $liveR['enabled'] !== 1 || trim((string) $liveR['ip_address']) === ''
            || [(string) $liveR['name'], $endpointKey($liveR['ip_address']), (string) $liveR['username'], (string) $liveR['password']]
            !== [(string) $d['name'], $endpointKey($d['ip_address']), (string) $d['username'], (string) $d['password']]) {
            r2($back, 'e', Lang::T('The router was changed just now, so nothing was done. Try again.'));
        }
        [$c, $err] = $rosConnect($d['ip_address'], $d['username'], $d['password'], 8);
        if (!$c) {
            r2($back, 'e', htmlspecialchars($d['name'] . ': ' . $err));
        }
        // between the connection and the command: the endpoint lock is taken (held to the end of the request) and the router
        // checked again, so an edit, a disable or a move saved meanwhile cancels the command before it is sent
        if (!$endpointLock()) {
            r2($back, 'e', $busyText());
        }
        if (!$rosSame($d)) {
            $endpointLock(false);
            r2($back, 'e', Lang::T('The router was changed just now, so nothing was done. Try again.'));
        }
        try {
            if ($op === 'reboot') {
                // three outcomes: the command could not be sent (an error), the router answered (done, or refused: no permission),
                // or it was sent and the connection went quiet, which is what a restarting router does but cannot be confirmed
                $req = new \PEAR2\Net\RouterOS\Request('/system/reboot');
                $req->setTag('reboot');
                // one limit of five seconds for the whole of it: writing the command, waiting for the reply and every read
                // inside a reply that arrives in parts (as RouterApi::send does)
                $deadline = RouterApi::now() + 5;
                $limited = method_exists($c, 'readDeadline');
                $confirmed = false;
                if ($limited) {
                    $c->readDeadline($deadline);
                }
                try {
                    $c->sendAsync($req); // a failure here is thrown to the error below: the command did not go out whole
                    $res = [];
                    try {
                        $left = $deadline - RouterApi::now();
                        if ($left > 0) {
                            $c->loop((int) floor($left), (int) (($left - floor($left)) * 1000000));
                        }
                    } catch (\PEAR2\Net\Transmitter\Exception | \PEAR2\Net\RouterOS\SocketException $e) {
                        // the connection dropped (or the time ran out) after the command went out: the usual sign of a restart
                    }
                    try {
                        $res = $c->extractNewResponses('reboot'); // what arrived before any drop still counts
                    } catch (Throwable $e) {
                    }
                    if (($refused = $rosError($res)) !== null) {
                        throw new Exception($refused);
                    }
                    foreach ($res as $r) {
                        $confirmed = $confirmed || $r->getType() === \PEAR2\Net\RouterOS\Response::TYPE_FINAL;
                    }
                } finally {
                    if ($limited) {
                        $c->readDeadline(null);
                    }
                }
                // confirmed or not, the router is going down: drop the connection now, so the client's goodbye (sent when it is
                // destroyed, waiting for a reply) does not hang on it. A refusal was thrown above and keeps the normal close.
                method_exists($c, 'abort') ? $c->abort() : null;
                if (!$confirmed) {
                    _log($who . 'Router reboot sent, not confirmed: ' . $d['name'], $admin['user_type'], $admin['id']);
                    r2($back, 'w', htmlspecialchars($d['name']) . ': ' . Lang::T('the restart was sent, but the router did not confirm it. If it is restarting, it is back in a minute or two.'));
                }
                _log($who . 'Router rebooted: ' . $d['name'], $admin['user_type'], $admin['id']);
                r2($back, 's', htmlspecialchars($d['name']) . ': ' . Lang::T('restarting. It is back in a minute or two.'));
            }
            if ($op === 'kick') {
                $q = new \PEAR2\Net\RouterOS\Request('/ip/hotspot/active/print');
                $q->setArgument('.proplist', '.id');
                $ids = [];
                $kickUntil = RouterApi::now() + 15; // one deadline for the whole action: the list and every batch
                $list = $rosSend($c, $q, $kickUntil);
                if (($err = $rosError($list)) !== null) {
                    throw new Exception($err);
                }
                foreach ($list as $a) {
                    if ($a->getType() === \PEAR2\Net\RouterOS\Response::TYPE_DATA && ($i = $rosText($a->getProperty('.id'))) !== null) {
                        $ids[] = $i;
                    }
                }
                // removed in batches; a batch the router refuses (or does not answer) stops there, and only what was removed is counted
                $removed = 0;
                $stopped = null;
                $unsure = 0; // a batch sent without an answer: the router may have removed it, or not
                $notSent = 0; // batches never sent: out of time first
                foreach (array_chunk($ids, 50) as $ci => $chunk) {
                    if ($kickUntil - RouterApi::now() <= 0) {
                        $stopped = Lang::T('Out of time: the rest were not sent');
                        $notSent = count($ids) - $ci * 50; // this batch and every one after it
                        break;
                    }
                    $rm = new \PEAR2\Net\RouterOS\Request('/ip/hotspot/active/remove');
                    $rm->setArgument('numbers', implode(',', $chunk));
                    try {
                        $stopped = $rosError($rosSend($c, $rm, $kickUntil));
                    } catch (Throwable $e) {
                        $stopped = $e->getMessage();
                        $unsure = count($chunk);
                    }
                    if ($stopped !== null) {
                        break;
                    }
                    $removed += count($chunk);
                }
                _log($who . 'Hotspot users disconnected on ' . $d['name'] . ': ' . $removed . ' of ' . count($ids) . ($unsure ? ', ' . $unsure . ' not confirmed' : ''), $admin['user_type'], $admin['id']);
                if ($stopped !== null && $unsure) {
                    r2($back, 'e', htmlspecialchars($d['name'] . ': ' . Lang::T('hotspot users disconnected, confirmed by the router') . ': ' . $removed . ' ' . Lang::T('of') . ' ' . count($ids) . '. '
                        . Lang::T('The router did not answer for the last') . ' ' . $unsure . ', ' . Lang::T('so they may or may not have been disconnected') . '. ' . $stopped));
                }
                if ($stopped !== null) {
                    r2($back, 'e', htmlspecialchars($d['name'] . ': ' . Lang::T('hotspot users disconnected') . ': ' . $removed . ' ' . Lang::T('of') . ' ' . count($ids) . '. ' . $stopped
                        . ($notSent ? ' (' . $notSent . ' ' . Lang::T('not sent') . ')' : '')));
                }
                r2($back, 's', htmlspecialchars($d['name']) . ': ' . Lang::T('hotspot users disconnected') . ': ' . $removed . '. ' . Lang::T('They can sign in again at once.'));
            }
            if ($op === 'backup') {
                // encrypted with the backup password, or not made at all (an unencrypted backup is every login on the router in
                // readable form); the password is never logged or shown
                $password = RouterApi::backupPassword();
                if ($password === '') {
                    method_exists($c, 'abort') ? $c->abort() : null;
                    r2(getUrl('routers/settings'), 'e', Lang::T('Set a backup password in Router Settings first: backups are encrypted with it.'));
                }
                // the password goes in the save command: not over a plain connection that crosses the internet (see RouterApi)
                if (($why = RouterApi::backupTransportError($c)) !== null) {
                    method_exists($c, 'abort') ? $c->abort() : null;
                    r2($back, 'e', htmlspecialchars($d['name'] . ': ' . $why));
                }
                // on boards whose root folder is emptied by a restart, saved in flash/ so it survives one
                $file = RouterApi::backupFolder(RouterApi::rows($c, '/file/print', ['name', 'type'], RouterApi::now() + 15)) . 'phpnuxbill-' . date('Ymd-His');
                $b = new \PEAR2\Net\RouterOS\Request('/system/backup/save');
                $b->setArgument('name', $file);
                $b->setArgument('password', $password);
                if (($err = $rosError($rosSend($c, $b, RouterApi::now() + 30))) !== null) {
                    throw new Exception($err);
                }
                _log($who . 'Backup saved on ' . $d['name'] . ': ' . $file . '.backup', $admin['user_type'], $admin['id']);
                r2($back, 's', htmlspecialchars($d['name']) . ': ' . Lang::T('backup saved on the router as') . ' ' . htmlspecialchars($file) . '.backup. '
                    . Lang::T('Download it from Files in Winbox or WebFig.'));
            }
        } catch (Throwable $e) {
            r2($back, 'e', htmlspecialchars($d['name'] . ': ' . $e->getMessage()));
        }
        break;

    case 'sessions':
    case 'interfaces':
    case 'check':
    case 'updates':
    case 'backups':
    case 'drop':
        // the tabs under a router's details: read (GET) or, for drop and "check for updates", change (POST with the CSRF token).
        // Each answers JSON and asks the router with a time limit; the session is released first so other pages do not wait.
        header('Content-Type: application/json');
        header('Cache-Control: no-store');
        $say = function ($a) {
            echo json_encode($a, JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        };
        $posting = $_SERVER['REQUEST_METHOD'] === 'POST';
        if (($action === 'drop' || ($action === 'updates' && $posting)) && (!$posting || !Csrf::check(_post('csrf_token')))) {
            $say(['ok' => false, 'error' => Lang::T('Invalid or Expired CSRF Token') . '. ' . Lang::T('Reload the page and try again.')]);
        }
        session_write_close();
        $d = ORM::for_table('tbl_routers')->find_one((int) ($routes['2'] ?? 0));
        if (!$d || (int) $d['enabled'] !== 1 || trim((string) $d['ip_address']) === '') {
            $say(['ok' => false, 'error' => Lang::T('Data Not Found')]);
        }
        // a session ID belongs to one router: the list says which router it came from, and a disconnect is refused when the
        // router's address or user has changed since (the ID could now name someone else's session on another router)
        $listedFrom = substr(hash('sha256', 'routers/sessions|' . $d['id'] . '|' . $d['ip_address'] . '|' . $d['username']), 0, 32);
        if ($action === 'drop' && !hash_equals($listedFrom, (string) _post('cfg'))) {
            $say(['ok' => false, 'stale' => true, 'error' => Lang::T('The router settings changed since this list was loaded. Refresh the list and try again.')]);
        }
        [$c, $err] = $rosConnect($d['ip_address'], $d['username'], $d['password'], 6);
        if (!$c) {
            $say(['ok' => false, 'error' => $err, 'hint' => $rosHint($err)]);
        }
        if ($action === 'drop' || ($action === 'updates' && $posting)) {
            // a command that changes something: sent only while the router is the one chosen (lock held to the end of the request)
            if (!$endpointLock()) {
                $say(['ok' => false, 'error' => $busyText()]);
            }
            if (!$rosSame($d)) {
                $endpointLock(false);
                $say(['ok' => false, 'stale' => true, 'error' => Lang::T('The router was changed just now, so nothing was done. Try again.')]);
            }
        }
        $until = RouterApi::now() + 15;
        try {
            if ($action === 'sessions') {
                // who is connected now, hotspot and PPPoE, each matched to a customer when the name is one
                // a router without the hotspot (or PPP) package has nobody there; any other refusal (no permission) is said, so
                // it is not mistaken for nobody being online
                // up to 5,000 of each, searched and paged in the page; the totals say when a list was cut
                $hs = [];
                $pp = [];
                $totalHs = 0;
                $totalPp = 0;
                // when each list was read: a session's start is that moment less the uptime the router gave then
                // the moment each list was observed is the middle of the time its read took (the router builds the answer somewhere
                // between the request and the end of it), so a start is worked out against its own list's time, not the other's
                $hsAt = $ppAt = time();
                $readErrors = [];
                $read = ['hotspot' => true, 'pppoe' => true]; // whether each list was read (a missing package counts: nobody there)
                try {
                    $hsFrom = microtime(true);
                    [$hs, $totalHs] = $rowsCapped($c, '/ip/hotspot/active/print', ['.id', 'user', 'address', 'mac-address', 'uptime', 'bytes-in', 'bytes-out', 'server'], $until, 5000);
                    $hsAt = (int) round(($hsFrom + microtime(true)) / 2);
                } catch (Throwable $e) {
                    if (in_array($e->getCode(), [408, 503], true)) { // the connection is gone (no answer, or closed)
                        throw $e;
                    }
                    if (stripos($e->getMessage(), 'no such command') === false) {
                        $readErrors[] = 'Hotspot: ' . $e->getMessage();
                        $read['hotspot'] = false;
                    }
                }
                try {
                    $ppFrom = microtime(true);
                    // PPPoE only: L2TP, SSTP and the other tunnels are not customers
                    [$pp, $totalPp] = $rowsCapped($c, '/ppp/active/print', ['.id', 'name', 'service', 'caller-id', 'address', 'uptime'], $until, 5000, ['service' => 'pppoe']);
                    $ppAt = (int) round(($ppFrom + microtime(true)) / 2);
                } catch (Throwable $e) {
                    if (in_array($e->getCode(), [408, 503], true)) { // the connection is gone (no answer, or closed)
                        throw $e;
                    }
                    if (stripos($e->getMessage(), 'no such command') === false) {
                        $readErrors[] = 'PPPoE: ' . $e->getMessage();
                        $read['pppoe'] = false;
                    }
                }
                $names = array_values(array_unique(array_filter(array_merge(array_column($hs, 'user'), array_column($pp, 'name')), 'strlen')));
                // each session is matched by the name it signs in with: a hotspot user by the customer's username, a PPPoE one by
                // the PPPoE username when the customer has one and by the username otherwise (as the PPPoE device sets it up), so a
                // PPPoE name that equals another customer's username cannot pick the wrong customer. PPPoE names are not kept
                // unique (an alias can equal another customer's username): of several customers with one name, the one with an
                // active plan on this router is meant; when that still leaves more than one, the session is not matched to any
                $whoHs = [];
                $whoPp = [];
                if ($names) {
                    $in = implode(',', array_fill(0, count($names), '?'));
                    $st = ORM::getDb()->prepare("SELECT id, username, pppoe_username, fullname FROM tbl_customers WHERE username IN ($in) OR pppoe_username IN ($in) ORDER BY id");
                    $st->execute(array_merge($names, $names));
                    $found = $st->fetchAll(PDO::FETCH_ASSOC);
                    $here = [];
                    if ($found) {
                        $ids = array_map('intval', array_column($found, 'id'));
                        $st = ORM::getDb()->prepare("SELECT DISTINCT customer_id FROM tbl_user_recharges WHERE status = 'on' AND routers = ? AND customer_id IN ("
                            . implode(',', array_fill(0, count($ids), '?')) . ")");
                        $st->execute(array_merge([$d['name']], $ids));
                        $here = array_flip(array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN)));
                    }
                    $ppCand = [];
                    foreach ($found as $cu) {
                        $one = ['id' => (int) $cu['id'], 'name' => $cu['fullname']];
                        if ((string) $cu['username'] !== '' && !isset($whoHs[$cu['username']])) {
                            $whoHs[$cu['username']] = $one;
                        }
                        // the name the PPPoE device signs it in with (an empty alias, "0" too, falls back to the username)
                        $ppName = !empty($cu['pppoe_username']) ? (string) $cu['pppoe_username'] : (string) $cu['username'];
                        if ($ppName !== '') {
                            $ppCand[$ppName][isset($here[(int) $cu['id']]) ? 0 : 1][] = $one;
                        }
                    }
                    foreach ($ppCand as $ppName => $tiers) {
                        ksort($tiers);
                        $best = reset($tiers);
                        $whoPp[$ppName] = count($best) === 1 ? $best[0] : null;
                    }
                }
                // when each session began (the moment its list was read less its uptime, to the second): the user, address and MAC
                // can all be the same for a new session that the router gives an ended one's ID, its start cannot
                $startedAt = function ($uptime, $readAt) {
                    $secs = RouterMonitor::seconds($uptime);
                    return $secs === null ? null : $readAt - $secs;
                };
                $out = ['ok' => true, 'hotspot' => [], 'pppoe' => [], 'total_hotspot' => $totalHs, 'total_pppoe' => $totalPp, 'customer_url' => getUrl('customers/view/'),
                    'read_errors' => $readErrors, 'read' => $read, 'cfg' => $listedFrom];
                foreach ($hs as $h) {
                    $out['hotspot'][] = ['sid' => $h['.id'], 'user' => $h['user'], 'address' => $h['address'], 'mac' => $h['mac-address'], 'uptime' => $rosUptime($h['uptime']),
                        'started' => $startedAt($h['uptime'], $hsAt),
                        'in' => (float) $h['bytes-in'], 'out' => (float) $h['bytes-out'], 'server' => $h['server'], 'customer' => $whoHs[$h['user']] ?? null];
                }
                foreach ($pp as $h) {
                    $out['pppoe'][] = ['sid' => $h['.id'], 'user' => $h['name'], 'address' => $h['address'], 'mac' => $h['caller-id'], 'uptime' => $rosUptime($h['uptime']),
                        'started' => $startedAt($h['uptime'], $ppAt),
                        'service' => $h['service'], 'customer' => $whoPp[$h['name']] ?? null];
                }
                $say($out);
            }

            if ($action === 'drop') {
                // disconnect one session; they can sign in again at once
                $kind = _post('kind');
                $sid = _post('sid');
                if (!in_array($kind, ['hotspot', 'pppoe'], true) || !preg_match('/^\*[0-9A-Fa-f]+$/', $sid)) {
                    $say(['ok' => false, 'error' => Lang::T('Data Not Found')]);
                }
                // a session ID can be reused once that session ends (and they start again after a restart): the live session must
                // still be the one listed (the same user; for PPPoE, a PPPoE one), or it is not removed
                // the same user can sign in again under a reused ID (another device, after a restart): the address and the
                // MAC / caller ID listed must match too, when the list had them
                // (exactly as listed: an address or caller ID that was empty then must still be empty, not match anything)
                $match = function ($got, $listed) {
                    return strcasecmp((string) $got, (string) $listed) === 0;
                };
                if ($kind === 'pppoe') {
                    $liveFrom = microtime(true);
                    $live = RouterApi::rows($c, '/ppp/active/print', ['.id', 'name', 'service', 'address', 'caller-id', 'uptime'], $until, [], ['.id' => $sid]);
                    $same = count($live) === 1 && (string) $live[0]['name'] === (string) _post('user') && (string) $live[0]['service'] === 'pppoe'
                        && $match($live[0]['address'], _post('address')) && $match($live[0]['caller-id'], _post('mac'));
                } else {
                    $liveFrom = microtime(true);
                    $live = RouterApi::rows($c, '/ip/hotspot/active/print', ['.id', 'user', 'address', 'mac-address', 'uptime'], $until, [], ['.id' => $sid]);
                    $same = count($live) === 1 && (string) $live[0]['user'] === (string) _post('user')
                        && $match($live[0]['address'], _post('address')) && $match($live[0]['mac-address'], _post('mac'));
                }
                // and it began when the listed one did (a few seconds apart at most: both are worked out from an uptime read at
                // a different moment). Without a start on both sides (the list's, and the live session's) nothing proves it is the
                // same session, so it is not removed
                $listedStart = trim((string) _post('started'));
                if ($same) {
                    $liveSecs = RouterMonitor::seconds($live[0]['uptime']);
                    $liveAt = (int) round(($liveFrom + microtime(true)) / 2);
                    $same = preg_match('/^\d{9,11}$/', $listedStart) === 1 && $liveSecs !== null
                        && abs(($liveAt - $liveSecs) - (int) $listedStart) <= 5;
                }
                if (!$same) {
                    $say(['ok' => false, 'stale' => true, 'error' => Lang::T('That session has ended or changed. Refresh the list and try again.')]);
                }
                RouterApi::run($c, $kind === 'hotspot' ? '/ip/hotspot/active/remove' : '/ppp/active/remove', ['numbers' => $sid], $until);
                _log('[' . $admin['username'] . ']: ' . 'Disconnected ' . $kind . ' session ' . mb_substr(_post('user'), 0, 64) . ' on ' . $d['name'], $admin['user_type'], $admin['id']);
                $say(['ok' => true]);
            }

            if ($action === 'interfaces') {
                // every PPPoE customer adds a dynamic interface: up to 2,000, searched and paged in the page
                [$rows, $total] = $rowsCapped($c, '/interface/print', ['name', 'type', 'running', 'disabled', 'dynamic', 'comment', 'mac-address', 'mtu', 'rx-byte', 'tx-byte',
                    'rx-error', 'tx-error', 'rx-drop', 'tx-drop', 'link-downs', 'last-link-up-time', 'last-link-down-time'], $until, 2000);
                $num = function ($v) {
                    return is_numeric($v) ? (float) $v : null;
                };
                $items = [];
                foreach ($rows as $r) {
                    $items[] = ['name' => $r['name'], 'type' => $r['type'], 'running' => $r['running'] === 'true', 'disabled' => $r['disabled'] === 'true', 'dynamic' => $r['dynamic'] === 'true',
                        'comment' => $r['comment'], 'mac' => $r['mac-address'], 'mtu' => $r['mtu'], 'rx' => $num($r['rx-byte']), 'tx' => $num($r['tx-byte']),
                        'rx_error' => $num($r['rx-error']), 'tx_error' => $num($r['tx-error']), 'rx_drop' => $num($r['rx-drop']), 'tx_drop' => $num($r['tx-drop']),
                        'link_downs' => $num($r['link-downs']), 'last_up' => $r['last-link-up-time'], 'last_down' => $r['last-link-down-time']];
                }
                $say(['ok' => true, 'items' => $items, 'total' => $total]);
            }

            if ($action === 'check') {
                // what usually stops a router working with PHPNuxBill, each with what to do about it
                $items = [];
                $add = function ($state, $title, $detail = '') use (&$items) {
                    $items[] = ['state' => $state, 'title' => $title, 'detail' => $detail];
                };
                // 1. the API user's rights
                try {
                    $u = RouterApi::rows($c, '/user/print', ['name', 'group'], $until, [], ['name' => $d['username']]);
                    $g = $u ? RouterApi::rows($c, '/user/group/print', ['name', 'policy'], $until, [], ['name' => (string) $u[0]['group']]) : [];
                    $policy = $g ? array_map('trim', explode(',', (string) $g[0]['policy'])) : [];
                    $has = function ($p) use ($policy) {
                        return in_array($p, $policy, true);
                    };
                    if (!$g) {
                        $add('warn', Lang::T('The API user\'s rights could not be read'), Lang::T('Check in Winbox, System > Users, that the user is in a group with the api, read and write policies.'));
                    } elseif (!$has('api') || !$has('read') || !$has('write')) {
                        $add('bad', Lang::T('The API user cannot change the router'), Lang::T('Its group') . ' "' . $g[0]['name'] . '" ' . Lang::T('needs the api, read and write policies, or customers cannot be added or removed.'));
                    } else {
                        $missing = array_values(array_filter(['reboot', 'sensitive'], function ($p) use ($has) {
                            return !$has($p);
                        }));
                        $add($missing ? 'warn' : 'ok', Lang::T('The API user can manage customers'), $missing
                            ? Lang::T('Group') . ' "' . $g[0]['name'] . '" ' . Lang::T('also lacks') . ': ' . implode(', ', $missing) . '. ' . Lang::T('reboot is needed for Reboot, sensitive for backups that include passwords. Backup files are downloaded from Files in Winbox or WebFig.')
                            : Lang::T('Group') . ' "' . $g[0]['name'] . '"');
                    }
                } catch (Throwable $e) {
                    if (in_array($e->getCode(), [408, 503], true)) { // the connection is gone (no answer, or closed)
                        throw $e;
                    }
                    $add('warn', Lang::T('The API user\'s rights could not be read'), $e->getMessage());
                }
                // 2. hotspot and PPPoE servers; a router without the hotspot package has none, any other failed read leaves it unknown
                $hsServers = [];
                $ppServers = [];
                $serverErrors = [];
                $unread = []; // the services whose servers could not be read: whether RADIUS must cover them is not known
                try {
                    $hsServers = RouterApi::rows($c, '/ip/hotspot/print', ['name', 'interface', 'disabled', 'profile'], $until);
                } catch (Throwable $e) {
                    if (in_array($e->getCode(), [408, 503], true)) { // the connection is gone (no answer, or closed)
                        throw $e;
                    }
                    if (stripos($e->getMessage(), 'no such command') === false) {
                        $serverErrors[] = Lang::T('Hotspot') . ': ' . $e->getMessage();
                        $unread[] = 'hotspot';
                    }
                }
                try {
                    $ppServers = RouterApi::rows($c, '/interface/pppoe-server/server/print', ['service-name', 'interface', 'disabled'], $until);
                } catch (Throwable $e) {
                    if (in_array($e->getCode(), [408, 503], true)) { // the connection is gone (no answer, or closed)
                        throw $e;
                    }
                    if (stripos($e->getMessage(), 'no such command') === false) {
                        $serverErrors[] = 'PPPoE: ' . $e->getMessage();
                        $unread[] = 'ppp';
                    }
                }
                $onHs = array_filter($hsServers, function ($x) { return $x['disabled'] !== 'true'; });
                $onPp = array_filter($ppServers, function ($x) { return $x['disabled'] !== 'true'; });
                if ($onHs || $onPp) {
                    $add('ok', Lang::T('Servers running'), trim((count($onHs) ? Lang::T('Hotspot') . ': ' . implode(', ', array_column($onHs, 'name')) . ' ' : '') . (count($onPp) ? 'PPPoE: ' . implode(', ', array_column($onPp, 'service-name')) : '')));
                } elseif ($serverErrors) {
                    $add('warn', Lang::T('Could not tell whether a hotspot or PPPoE server is running'), implode('; ', $serverErrors));
                } else {
                    $add('warn', Lang::T('No hotspot or PPPoE server is running'), Lang::T('Customers connect through one of them: set one up in Winbox, IP > Hotspot or PPP > PPPoE Servers.'));
                }
                // 3. the billing site in the hotspot walled garden (the login page must reach it before sign-in)
                if ($onHs) {
                    $host = strtolower((string) parse_url(APP_URL, PHP_URL_HOST));
                    $port = (int) (parse_url(APP_URL, PHP_URL_PORT) ?: (strtolower((string) parse_url(APP_URL, PHP_URL_SCHEME)) === 'https' ? 443 : 80));
                    // a rule's ports: empty is any, otherwise a list of ports and ranges ("80,443,8000-8100"); null when unreadable
                    $portIn = function ($spec) use ($port) {
                        $spec = trim((string) $spec);
                        if ($spec === '') {
                            return true;
                        }
                        $neg = $spec[0] === '!';
                        $hit = false;
                        foreach (explode(',', ltrim($spec, '!')) as $part) {
                            if (!preg_match('/^\s*(\d+)\s*(?:-\s*(\d+)\s*)?$/', $part, $m)) {
                                return null;
                            }
                            $hit = $hit || ($port >= (int) $m[1] && $port <= (int) ($m[2] ?? $m[1]));
                        }
                        return $neg ? !$hit : $hit;
                    };
                    // whether a rule's dst-host covers the billing host: empty is any host, "*" a wildcard, and a leading "!" (how
                    // RouterOS prints a negated value, in the same property) turns it round, so "!other.example" covers every host but
                    // that one; null for one this check cannot judge (a ":" regular expression)
                    $hostIn = function ($pat) use ($host) {
                        $pat = strtolower(trim((string) $pat));
                        if ($pat === '') {
                            return true;
                        }
                        $neg = $pat[0] === '!';
                        $pat = $neg ? ltrim(substr($pat, 1)) : $pat;
                        if ($pat === '' || $pat[0] === ':') {
                            return null;
                        }
                        // only "*" (any run of characters) and "?" (one character) are wildcards, all else literal: no bracket expressions
                        $hit = (bool) preg_match('/^' . str_replace(['\\*', '\\?'], ['.*', '.'], preg_quote($pat, '/')) . '$/Ds', $host);
                        return $neg ? !$hit : $hit;
                    };
                    // HTTPS is encrypted: the hotspot sees no path or method in it, so a rule that needs one never matches it
                    $https = strtolower((string) parse_url(APP_URL, PHP_URL_SCHEME)) === 'https';
                    $found = false; // true allowed, false not, null cannot tell, 'deny' a rule that blocks it
                    $denyRule = '';
                    try {
                        // the rules are tried in order and the first that matches decides: a matching "deny" is no access. A rule
                        // that may match but has a condition this check cannot judge (a path, a method, addresses, a server, a
                        // regular expression) leaves the answer unknown rather than a guess.
                        foreach (RouterApi::rows($c, '/ip/hotspot/walled-garden/print', ['dst-host', 'dst-port', 'path', 'method', 'src-address', 'dst-address', 'server', 'disabled', 'action'], $until) as $w) {
                            if ($w['disabled'] === 'true') {
                                continue;
                            }
                            // (a negated deny that covers the host is applied here in its turn, before any later allow)
                            $inHost = $hostIn($w['dst-host']);
                            if ($inHost === null) {
                                $found = null;
                                break;
                            }
                            if (!$inHost) {
                                continue;
                            }
                            if ($https && (trim((string) $w['path']) !== '' || trim((string) $w['method']) !== '')) {
                                continue; // not matched for an HTTPS request: the next rule is asked
                            }
                            $inPort = $portIn($w['dst-port']);
                            if ($inPort === false) {
                                continue;
                            }
                            $other = false;
                            foreach (['path', 'method', 'src-address', 'dst-address', 'server'] as $k) {
                                $other = $other || trim((string) $w[$k]) !== '';
                            }
                            if ($inPort === null || $other) {
                                $found = null;
                                break;
                            }
                            // a rule that matches and denies is told apart from no rule at all: it is what to look at
                            $found = strtolower((string) $w['action']) !== 'deny' ? true : 'deny';
                            $denyRule = (string) $w['dst-host'];
                            break;
                        }
                        $site = $host . ':' . $port;
                        if ($found === 'deny') {
                            // an IP walled garden rule that accepts the traffic lets it past the hotspot before the web walled
                            // garden is asked: the deny is a definite block only when no such rule can apply (or they cannot be read)
                            try {
                                foreach (RouterApi::rows($c, '/ip/hotspot/walled-garden/ip/print', ['dst-host', 'disabled', 'action'], $until) as $w) {
                                    if ($w['disabled'] !== 'true' && in_array(strtolower((string) $w['action']), ['', 'accept'], true)
                                        && $hostIn($w['dst-host']) !== false) { // (one that may cover it leaves the deny uncertain too)
                                        $found = null;
                                        break;
                                    }
                                }
                            } catch (Throwable $e) {
                                if (in_array($e->getCode(), [408, 503], true)) { // the connection is gone (no answer, or closed)
                                    throw $e;
                                }
                                $found = null;
                            }
                        }
                        $ipRule = '';
                        $ipUnsure = false;
                        if ($found === false) {
                            // no web rule: an IP walled garden accept rule that surely covers the site (its host, and no other
                            // condition this check cannot judge) is what lets customers open it; with none, the warning stays
                            try {
                                foreach (RouterApi::rows($c, '/ip/hotspot/walled-garden/ip/print', ['dst-host', 'dst-address', 'src-address', 'src-address-list', 'dst-address-list', 'protocol', 'dst-port', 'server', 'disabled', 'action'], $until) as $w) {
                                    if ($w['disabled'] === 'true') {
                                        continue;
                                    }
                                    // rules are tried in order: a drop or reject that may cover the site, met before an accept, means a later
                                    // accept does not let it through, so the warning stays
                                    if (!in_array(strtolower((string) $w['action']), ['', 'accept'], true)) {
                                        // a rule for another protocol (udp, icmp) cannot block this TCP site: skipped, as the accept branch does
                                        $dp = strtolower(trim((string) $w['protocol']));
                                        if ($dp !== '' && (($dp[0] === '!') === in_array(ltrim($dp, '!'), ['tcp', '6'], true))) {
                                            continue;
                                        }
                                        if ($hostIn($w['dst-host']) !== false && $portIn($w['dst-port']) !== false) {
                                            break;
                                        }
                                        continue;
                                    }
                                    if ($hostIn($w['dst-host']) !== true) {
                                        continue;
                                    }
                                    $proto = strtolower(trim((string) $w['protocol']));
                                    $other = false;
                                    foreach (['dst-address', 'src-address', 'src-address-list', 'dst-address-list', 'server'] as $k) {
                                        $other = $other || trim((string) $w[$k]) !== '';
                                    }
                                    if (!$other && ($proto === '' || in_array($proto, ['tcp', '6'], true)) && $portIn($w['dst-port']) === true) {
                                        $found = 'ipok';
                                        $ipRule = (string) $w['dst-host'];
                                        break;
                                    }
                                }
                            } catch (Throwable $e) {
                                if (in_array($e->getCode(), [408, 503], true)) { // the connection is gone (no answer, or closed)
                                    throw $e;
                                }
                            }
                        }
                        if ($found === true) {
                            // the IP walled garden is asked before the web one: a rule there that drops or rejects the traffic blocks
                            // the site even though a web rule allows it. Its rules are tried in order and the first that matches
                            // decides; one with conditions this check cannot judge leaves the answer unknown when it would block
                            // whether a rule applies to the billing site: false when a condition this check can judge (the protocol,
                            // the port) rules it out, null when one it cannot judge (addresses, address lists, a server) is set
                            $ipApplies = function ($w) use ($portIn) {
                                $proto = strtolower(trim((string) $w['protocol']));
                                if ($proto !== '' && ($proto[0] === '!') === in_array(ltrim($proto, '!'), ['tcp', '6'], true)) {
                                    return false; // the site is reached over TCP
                                }
                                $inPort = $portIn($w['dst-port']);
                                if ($inPort === false) {
                                    return false;
                                }
                                $unsure = $inPort === null;
                                foreach (['dst-address', 'src-address', 'src-address-list', 'dst-address-list', 'server'] as $k) {
                                    $unsure = $unsure || trim((string) $w[$k]) !== '';
                                }
                                return $unsure ? null : true;
                            };
                            $mayAccept = false; // an earlier accept rule that may apply: a later drop is then not certain
                            try {
                                foreach (RouterApi::rows($c, '/ip/hotspot/walled-garden/ip/print', ['dst-host', 'dst-address', 'src-address', 'src-address-list', 'dst-address-list', 'protocol', 'dst-port', 'server', 'disabled', 'action'], $until) as $w) {
                                    $inHost = $hostIn($w['dst-host']);
                                    if ($w['disabled'] === 'true' || $inHost === false) {
                                        continue;
                                    }
                                    $applies = $ipApplies($w);
                                    if ($applies === false) {
                                        continue;
                                    }
                                    if ($inHost === null) {
                                        $applies = null; // a host this check cannot judge: the rule may apply
                                    }
                                    if (in_array(strtolower((string) $w['action']), ['', 'accept'], true)) {
                                        if ($applies === true) {
                                            break; // let through before any later rule is asked
                                        }
                                        $mayAccept = true;
                                        continue;
                                    }
                                    if ($applies === null || $mayAccept) {
                                        $found = null;
                                        $ipUnsure = true;
                                    } else {
                                        $found = 'ipblock';
                                        $ipRule = trim((string) $w['dst-host']) === '' ? '*' : (string) $w['dst-host'];
                                    }
                                    break;
                                }
                            } catch (Throwable $e) {
                                if (in_array($e->getCode(), [408, 503], true)) { // the connection is gone (no answer, or closed)
                                    throw $e;
                                }
                                $found = null; // not known to be reachable while the IP list could not be read
                                $ipUnsure = true;
                            }
                        }
                        if ($found === 'ipok') {
                            $add('ok', Lang::T('The billing site is in the walled garden'), $site . ' (' . Lang::T('IP walled garden rule for') . ' ' . $ipRule . ')');
                        } elseif ($found === 'ipblock') {
                            $add('bad', Lang::T('An IP walled garden rule blocks the billing site'), Lang::T('The IP walled garden rule for') . ' ' . $ipRule . ' ' . Lang::T('drops or rejects the traffic, so customers cannot open') . ' ' . $site
                                . ' ' . Lang::T('before they sign in. Change it in Winbox, IP > Hotspot > Walled Garden.'));
                        } elseif ($ipUnsure) {
                            $add('warn', Lang::T('Could not tell whether the billing site is in the walled garden'), Lang::T('A web walled garden rule allows') . ' ' . $site . ', ' . Lang::T('but an IP walled garden rule may block it, or the IP list could not be read. Check both in Winbox, IP > Hotspot > Walled Garden.'));
                        } elseif ($found === 'deny') {
                            $add('bad', Lang::T('A walled garden rule blocks the billing site'), Lang::T('The rule for') . ' ' . $denyRule . ' ' . Lang::T('has the action deny, so customers cannot open') . ' ' . $site
                                . ' ' . Lang::T('before they sign in. Change it in Winbox, IP > Hotspot > Walled Garden.'));
                        } elseif ($found === null && $denyRule !== '') {
                            $add('warn', Lang::T('Could not tell whether the billing site is in the walled garden'), Lang::T('The rule for') . ' ' . $denyRule . ' ' . Lang::T('has the action deny, but an IP walled garden rule may still let customers open') . ' ' . $site
                                . '. ' . Lang::T('Check both in Winbox, IP > Hotspot > Walled Garden.'));
                        } elseif ($found === null) {
                            $add('warn', Lang::T('Could not tell whether the billing site is in the walled garden'), Lang::T('A walled garden rule for') . ' ' . $site . ' ' . Lang::T('has conditions this check cannot judge. Check it in Winbox, IP > Hotspot > Walled Garden.'));
                        } else {
                            // (when no web rule was found, an IP walled garden rule may still let the site through)
                            $add($found ? 'ok' : 'warn', $found ? Lang::T('The billing site is in the walled garden') : Lang::T('No walled garden rule for the billing site was found'),
                                $found ? $site : Lang::T('Unless an IP walled garden rule allows it, customers cannot open') . ' ' . $site . ' ' . Lang::T('before they sign in. Add it in Winbox, IP > Hotspot > Walled Garden, as') . ' ' . $host);
                        }
                    } catch (Throwable $e) {
                        if (in_array($e->getCode(), [408, 503], true)) { // the connection is gone (no answer, or closed)
                            throw $e;
                        }
                        $add('warn', Lang::T('The walled garden could not be read'), $e->getMessage());
                    }
                }
                // 4. RADIUS, when PHPNuxBill uses it
                if (!empty($config['radius_enable'])) {
                    try {
                        $rad = array_filter(RouterApi::rows($c, '/radius/print', ['address', 'service', 'disabled'], $until), function ($x) { return $x['disabled'] !== 'true'; });
                        // the services this router bills through (a running hotspot server, a running PPPoE server) must each be in
                        // an enabled RADIUS entry's service list; with neither running, both are expected
                        // (a service whose servers could not be read is not required here: it is reported as unknown below)
                        $need = array_merge($onHs ? ['hotspot'] : [], $onPp ? ['ppp'] : []);
                        if (!$need && !$unread) {
                            $need = ['hotspot', 'ppp'];
                        }
                        $covered = [];
                        foreach ($rad as $x) {
                            $covered = array_merge($covered, array_map('trim', explode(',', strtolower((string) $x['service']))));
                        }
                        $lacking = array_values(array_diff($need, $covered));
                        $list = implode(', ', array_map(function ($x) { return $x['address'] . ' (' . $x['service'] . ')'; }, $rad));
                        if (!$rad) {
                            $add('bad', Lang::T('No RADIUS server is set on the router'), Lang::T('PHPNuxBill uses RADIUS: add the RADIUS server in Winbox, RADIUS, for') . ' ' . implode(' ' . Lang::T('and') . ' ', $need ?: array_values(array_unique($unread))) . '.');
                        } elseif ($lacking) {
                            $add('bad', Lang::T('RADIUS does not cover every service'), Lang::T('Missing') . ': ' . implode(', ', $lacking) . '. '
                                . Lang::T('Tick it in the RADIUS server Service list in Winbox, RADIUS.') . ' ' . $list);
                        } else {
                            // the entries are there; the hotspot profile of each running server, and PPP's AAA, must also use them
                            $off = [];
                            try {
                                if ($onHs) {
                                    $useRadius = [];
                                    foreach (RouterApi::rows($c, '/ip/hotspot/profile/print', ['name', 'use-radius'], $until) as $pf) {
                                        $useRadius[$pf['name']] = $pf['use-radius'] === 'true';
                                    }
                                    foreach ($onHs as $srv) {
                                        if (empty($useRadius[(string) $srv['profile']])) {
                                            $off[] = Lang::T('hotspot profile') . ' "' . $srv['profile'] . '"';
                                        }
                                    }
                                }
                                if ($onPp) {
                                    $aaa = RouterApi::rows($c, '/ppp/aaa/print', ['use-radius'], $until);
                                    if (($aaa[0]['use-radius'] ?? '') !== 'true') {
                                        $off[] = 'PPP > AAA';
                                    }
                                }
                                // a service whose servers could not be read is not known to be set up, even when a RADIUS entry
                                // lists it: whether its servers (and their profiles) use RADIUS was never seen
                                $unknown = array_values(array_unique($unread));
                                if ($off) {
                                    $add('bad', Lang::T('RADIUS is set but not used'), Lang::T('Turn on Use RADIUS in') . ': ' . implode(', ', array_unique($off)) . '. ' . $list);
                                } elseif ($unknown) {
                                    $add('warn', Lang::T('RADIUS is set up for the servers that could be read'), Lang::T('Could not tell whether these need it too') . ': ' . implode(', ', $unknown) . '. ' . $list);
                                } else {
                                    $add('ok', Lang::T('RADIUS is set up'), $list);
                                }
                            } catch (Throwable $e) {
                                if (in_array($e->getCode(), [408, 503], true)) { // the connection is gone (no answer, or closed)
                                    throw $e;
                                }
                                $add('warn', Lang::T('Could not tell whether the profiles use RADIUS'), $e->getMessage());
                            }
                        }
                    } catch (Throwable $e) {
                        if (in_array($e->getCode(), [408, 503], true)) { // the connection is gone (no answer, or closed)
                            throw $e;
                        }
                        $add('warn', Lang::T('The RADIUS settings could not be read'), $e->getMessage());
                    }
                }
                // 5. the router's clock (expiry and sessions are timed by it)
                try {
                    $clk = RouterApi::rows($c, '/system/clock/print', ['date', 'time', 'gmt-offset'], $until);
                    $routerTs = null;
                    if ($clk) {
                        $date = (string) $clk[0]['date'];
                        if (preg_match('/^([a-z]{3})\/(\d{2})\/(\d{4})$/i', $date, $m)) { // RouterOS 6 and early 7: nov/19/2026
                            $date = $m[3] . '-' . str_pad((string) (array_search(strtolower($m[1]), ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec']) + 1), 2, '0', STR_PAD_LEFT) . '-' . $m[2];
                        }
                        $off = 0;
                        if (preg_match('/^([+-])(\d{1,2}):(\d{2})$/', (string) $clk[0]['gmt-offset'], $m)) {
                            $off = ($m[1] === '-' ? -1 : 1) * ((int) $m[2] * 3600 + (int) $m[3] * 60);
                        } elseif (is_numeric($clk[0]['gmt-offset'])) {
                            $off = (int) $clk[0]['gmt-offset'];
                        }
                        $local = strtotime($date . ' ' . $clk[0]['time'] . ' UTC');
                        $routerTs = $local !== false ? $local - $off : null;
                    }
                    if ($routerTs === null) {
                        $add('warn', Lang::T('The router\'s clock could not be read'));
                    } else {
                        $diff = abs(time() - $routerTs);
                        $add($diff > 300 ? 'bad' : 'ok', $diff > 300 ? Lang::T('The router\'s clock is off by') . ' ' . round($diff / 60) . ' ' . Lang::T('minutes') : Lang::T('The router\'s clock is right'),
                            $diff > 300 ? Lang::T('Times on the router (sessions, expiry) will be wrong. Turn on System > NTP Client, or set System > Clock.') : '');
                    }
                } catch (Throwable $e) {
                    if (in_array($e->getCode(), [408, 503], true)) { // the connection is gone (no answer, or closed)
                        throw $e;
                    }
                    $add('warn', Lang::T('The router\'s clock could not be read'), $e->getMessage());
                }
                $say(['ok' => true, 'items' => $items, 'host' => parse_url(APP_URL, PHP_URL_HOST)]);
            }

            if ($action === 'updates') {
                // RouterOS and firmware versions. "Check for updates" (POST) asks MikroTik's server first; nothing is installed.
                if ($posting) {
                    try {
                        RouterApi::run($c, '/system/package/update/check-for-updates', [], RouterApi::now() + 20);
                    } catch (Throwable $e) {
                        if (in_array($e->getCode(), [408, 503], true)) { // the connection is gone (no answer, or closed)
                            throw $e;
                        }
                        $checkErr = $e->getMessage();
                    }
                    $until = RouterApi::now() + 5; // the check used its own time: the versions are read after it, within the budget
                }
                $u = RouterApi::rows($c, '/system/package/update/print', ['channel', 'installed-version', 'latest-version', 'status'], $until);
                $fw = [];
                try {
                    $fw = RouterApi::rows($c, '/system/routerboard/print', ['routerboard', 'model', 'current-firmware', 'upgrade-firmware'], $until);
                } catch (Throwable $e) {
                    if (in_array($e->getCode(), [408, 503], true)) { // the connection is gone (no answer, or closed)
                        throw $e;
                    }
                }
                $u = $u[0] ?? [];
                $fw = $fw[0] ?? [];
                $installed = (string) ($u['installed-version'] ?? '');
                $latest = (string) ($u['latest-version'] ?? '');
                $say(['ok' => true, 'channel' => $u['channel'] ?? '', 'installed' => $installed, 'latest' => $latest, 'status' => $u['status'] ?? '',
                    'newer' => $latest !== '' && $installed !== '' && version_compare($latest, $installed, '>'),
                    'fw_current' => $fw['current-firmware'] ?? '', 'fw_upgrade' => $fw['upgrade-firmware'] ?? '',
                    'fw_newer' => ($fw['upgrade-firmware'] ?? '') !== '' && ($fw['current-firmware'] ?? '') !== '' && version_compare($fw['upgrade-firmware'], $fw['current-firmware'], '>'),
                    'check_error' => $checkErr ?? null]);
            }

            if ($action === 'backups') {
                // the backup files on the router, newest first
                // RouterOS 7.16 and later have last-modified instead of creation-time: both are asked for, the first one present is used
                $files = RouterApi::rows($c, '/file/print', ['name', 'type', 'size', 'last-modified', 'creation-time'], $until);
                $items = [];
                foreach ($files as $f) {
                    if (substr((string) $f['name'], -7) === '.backup' || $f['type'] === 'backup') {
                        $stamp = (string) ($f['last-modified'] ?? '') !== '' ? $f['last-modified'] : $f['creation-time'];
                        $items[] = ['name' => $f['name'], 'size' => is_numeric($f['size']) ? (float) $f['size'] : null, 'created' => $stamp,
                            'auto' => (bool) preg_match('/^(flash\/)?phpnuxbill-auto-\d{8}(?:-\d{1,2}|-r[0-9a-f]{8})?\.backup$/i', (string) $f['name'])]; // where the cron keeps them
                    }
                }
                // newest first by creation time ("2026-11-19 03:00:01" on RouterOS 7, "nov/19/2026 03:00:01" on 6), then by name
                $when = function ($t) {
                    $t = (string) $t;
                    if (preg_match('/^([a-z]{3})\/(\d{2})\/(\d{4}) (\d{2}:\d{2}:\d{2})$/i', $t, $m)) {
                        $t = $m[3] . '-' . $m[1] . '-' . $m[2] . ' ' . $m[4];
                    }
                    $ts = strtotime($t);
                    return $ts === false ? 0 : $ts;
                };
                usort($items, function ($a, $b) use ($when) {
                    return [$when($b['created']), (string) $b['name']] <=> [$when($a['created']), (string) $a['name']];
                });
                $say(['ok' => true, 'items' => array_slice($items, 0, 100), 'nightly' => ($config['router_backup'] ?? '0') === '1']);
            }
        } catch (Throwable $e) {
            $say(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'history':
        // the History tab: readings kept by the cron (RouterMonitor), nothing asked of the router itself
        header('Content-Type: application/json');
        header('Cache-Control: no-store');
        session_write_close();
        $d = ORM::for_table('tbl_routers')->find_one((int) ($routes['2'] ?? 0));
        if (!$d) {
            echo json_encode(['ok' => false, 'error' => Lang::T('Data Not Found')]);
            exit;
        }
        if (!RouterMonitor::ready()) {
            echo json_encode(['ok' => false, 'error' => Lang::T('History needs the database update: run update.php (or php system/cron/migrate.php).')]);
            exit;
        }
        $range = in_array(_get('range'), ['24h', '7d', '30d'], true) ? _get('range') : '24h';
        try {
            echo json_encode(['ok' => true, 'range' => $range, 'collecting' => RouterMonitor::enabled()] + RouterMonitor::history((int) $d['id'], $range), JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'error' => Lang::T('The history could not be read') . ': ' . mb_substr($e->getMessage(), 0, 160)]);
        }
        exit;

    case 'backup-download':
        // removed: the router's FTP service sends its login and the backup unencrypted, and no encrypted way (SFTP) is
        // available here. Backups are taken from the router itself (Files in Winbox or WebFig).
        r2(getUrl('routers/list'), 'e', Lang::T('Download backups from Files in Winbox or WebFig.'));
        break;

    case 'health':
        // the live figures for one router on the list, kept for 60 seconds so reloading or several admins do not ask it again.
        // live=1 (the open details panel, every second) also reads the interface counters and is shared for under a second.
        header('Content-Type: application/json');
        header('Cache-Control: no-store');
        // PHP holds the session file locked for the whole request: release it, or the three checks the page runs at once (and
        // every other page the admin opens) would wait for each other while a router is slow
        session_write_close();
        $live = _get('live') === '1';
        $id = (int) ($routes['2'] ?? 0);
        $d = $id ? ORM::for_table('tbl_routers')->find_one($id) : null;
        if (!$healthOn || !$d) {
            echo json_encode(['ok' => false, 'error' => Lang::T('Not available')]);
            exit;
        }
        if ((int) $d['enabled'] !== 1 || trim((string) $d['ip_address']) === '') {
            echo json_encode(['ok' => false, 'skip' => true, 'error' => Lang::T('Disabled')]);
            exit;
        }
        $dir = $CACHE_PATH . DIRECTORY_SEPARATOR . 'router_health';
        $file = $dir . DIRECTORY_SEPARATOR . $id . ($live ? '.live' : '') . '.json';
        // the cache belongs to these details: a changed address or login is asked again at once
        $key = hash('sha256', $d['ip_address'] . "\n" . $d['username'] . "\n" . $d['password']);
        if (!_get('fresh') && is_file($file) && time() - filemtime($file) < ($live ? 2 : 60)) {
            $raw = '';
            if ($rfh = @fopen($file, 'r')) {
                // read under a shared lock: the writer rewrites the file in place under an exclusive one, so a half-written
                // file is never read (a file that cannot be read whole counts as missing)
                if (flock($rfh, LOCK_SH)) {
                    $raw = (string) stream_get_contents($rfh);
                    flock($rfh, LOCK_UN);
                }
                fclose($rfh);
            }
            $cached = json_decode($raw, true);
            // a live reading is shared for under a second (several admins on one router), timed by when it was read, since the
            // file time only counts whole seconds
            $fresh = !$live || (isset($cached['done']) && microtime(true) - (float) $cached['done'] < 0.9);
            // a reading begun before the router was last reset (enabled or disabled, details changed) is not handed out, even when
            // the details are the same again: it is the same check the fresh answer below goes through
            $resetSince = (float) @file_get_contents($dir . DIRECTORY_SEPARATOR . $id . '.reset');
            // the router read again right before the answer is handed out: disabled, given other details or reset since the request began
            // (an edit, a disable) makes the stored answer a miss, and a fresh reading is made from the current details
            $stillSame = false;
            if (is_array($cached) && ($cached['key'] ?? '') === $key && $fresh && $resetSince <= (float) ($cached['observed'] ?? 0)) {
                $curRow = ORM::for_table('tbl_routers')->find_one($id);
                $stillSame = $curRow && (int) $curRow['enabled'] === 1
                    && hash('sha256', $curRow['ip_address'] . "\n" . $curRow['username'] . "\n" . $curRow['password']) === $key
                    && (float) @file_get_contents($dir . DIRECTORY_SEPARATOR . $id . '.reset') <= (float) ($cached['observed'] ?? 0);
            }
            if ($stillSame) {
                unset($cached['key']);
                // the stored answer is shared by every admin: its words are put in this admin's language here
                if (($cached['code'] ?? '') === 'no_read') {
                    $cached['error'] = Lang::T('Connected, but this user cannot read the system details.');
                } elseif (empty($cached['ok']) && isset($cached['error'])) {
                    $cached['hint'] = $rosHint($cached['error']);
                }
                $cached['age'] = time() - filemtime($file);
                echo json_encode($cached);
                exit;
            }
        }
        // when this reading began: the order of readings, for the store below ("at" is when the interface counters were read,
        // which the speeds need, and a slow reading that began earlier can finish later)
        $observed = microtime(true);
        [$c, $err] = $rosConnect($d['ip_address'], $d['username'], $d['password'], 5);
        if ($c) {
            try {
                $out = ['ok' => true] + $rosHealth($c, $live);
            } catch (Throwable $e) {
                // refused (403): connected, but not allowed to read; anything else (no answer, a broken connection) is said as it is
                $out = $e->getCode() === 403 ? ['ok' => false, 'code' => 'no_read', 'error' => Lang::T('Connected, but this user cannot read the system details.')]
                    : ['ok' => false, 'error' => $e->getMessage()];
            }
        } else {
            $out = ['ok' => false, 'error' => $err, 'hint' => $rosHint($err)];
        }
        $out['checked'] = date('Y-m-d H:i:s');
        $out['observed'] = $observed; // full precision: the store below compares it with the next reading's start
        $out['done'] = round(microtime(true), 3); // when this answer was ready, failures included: what the live sharing is timed by
        // which address the readings came from, without the login: the live graphs start their speeds again when it changes, so
        // byte counters of two different routers are never compared
        $out['rev'] = substr(hash('sha256', 'routers/rev|' . $d['id'] . '|' . $d['ip_address']), 0, 12);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        // text from an old router can be in a legacy encoding: replaced rather than failing the whole answer
        $json = json_encode($out + ['key' => $key], JSON_INVALID_UTF8_SUBSTITUTE);
        $now = false; // the router as it is now: read under the file lock below, or after it when the file cannot be opened
        $fh = $json !== false ? @fopen($file, 'c+') : false;
        if ($fh) {
            // read and write under one lock: when two readings of the same router finish together, the newer one stays
            if (flock($fh, LOCK_EX)) {
                $old = json_decode((string) stream_get_contents($fh), true);
                // kept only for the router's details as they are now: a reading made with details changed since (another address
                // or login) is not stored over the entry for the present ones. Among readings of the present details, whichever
                // began last stays.
                $now = ORM::for_table('tbl_routers')->find_one($id);
                // and only while the router is enabled (a disabled one has no reading), and not when its enabled state changed since
                // this reading began
                $resetAt = (float) @file_get_contents($dir . DIRECTORY_SEPARATOR . $id . '.reset');
                $current = $now && (int) $now['enabled'] === 1 && trim((string) $now['ip_address']) !== '' && $resetAt <= $observed
                    && hash('sha256', $now['ip_address'] . "\n" . $now['username'] . "\n" . $now['password']) === $key;
                $newer = is_array($old) && ($old['key'] ?? '') === $key && isset($old['observed']) && (float) $old['observed'] > $observed;
                if ($current && !$newer) {
                    ftruncate($fh, 0);
                    rewind($fh);
                    fwrite($fh, $json);
                    fflush($fh);
                }
                flock($fh, LOCK_UN);
            }
            fclose($fh);
        }
        // the answer too is only for the router's details as they are now: a router disabled (or left without an address)
        // meanwhile gets the disabled answer, one whose address or login changed meanwhile is asked to try again, not handed a
        // reading of the old details
        if ($now === false) {
            $now = ORM::for_table('tbl_routers')->find_one($id);
        }
        if (!$now || (int) $now['enabled'] !== 1 || trim((string) $now['ip_address']) === '') {
            echo json_encode(['ok' => false, 'skip' => true, 'error' => Lang::T('Disabled')]);
            exit;
        }
        if (hash('sha256', $now['ip_address'] . "\n" . $now['username'] . "\n" . $now['password']) !== $key) {
            echo json_encode(['ok' => false, 'error' => Lang::T('The router settings changed since this check began. Check it again.')]);
            exit;
        }
        // a reset (the router's enabled state or details changed) after this reading began: it is for the old details, whether or
        // not the store above could be written
        if ((float) @file_get_contents($dir . DIRECTORY_SEPARATOR . $id . '.reset') > $observed) {
            echo json_encode(['ok' => false, 'error' => Lang::T('The router settings changed since this check began. Check it again.')]);
            exit;
        }
        $out['age'] = 0;
        echo json_encode($out, JSON_INVALID_UTF8_SUBSTITUTE);
        exit;

    case 'add-post':
        if (!Csrf::check(_post('csrf_token'))) {
            r2(getUrl('routers/add'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $name = _post('name');
        $ip_address = $joinAddress(_post('ip_address'), _post('api_port'));
        $username = _post('username');
        $password = _post('password');
        $description = _post('description');
        $enabled = _post('enabled');
        $site = mb_substr(_post('site'), 0, 32);

        $msg = '';
        if (!$validPort(_post('api_port')) || !$validAddressPort($ip_address)) {
            $msg .= Lang::T('The API port must be a number from 1 to 65535') . '<br>';
        }
        if ($hostMissing($ip_address)) {
            $msg .= Lang::T('Enter the router\'s IP address or host name') . '<br>';
        }
        if ($addressTooLong($ip_address)) {
            $msg .= Lang::T('The address is too long (128 characters at most)') . '<br>';
        }
        if (Validator::Length($name, 30, 1) == false) {
            $msg .= 'Name should be between 1 to 30 characters' . '<br>';
        }
        if (!$endpointLock()) { // held until the router is saved (or the request ends with the error)
            r2(getUrl('routers/add'), 'e', $busyText());
        }
        if($enabled || _post("testIt")){
            if ($ip_address == '' or $username == '') {
                $msg .= Lang::T('All field is required') . '<br>';
            }

            if ($endpointTaken($ip_address)) {
                $msg .= Lang::T('IP Router Already Exist') . '<br>';
            }
        }
        // plans, recharges and the rest name their router: two routers with one name could not be told apart
        if ($name !== '' && ORM::for_table('tbl_routers')->where('name', $name)->find_one()) {
            $msg .= 'Name Already Exists<br>';
        }
        if (strtolower($name) == 'radius') {
            $msg .= '<b>Radius</b> name is reserved<br>';
        }

        if ($msg == '') {
            run_hook('add_router'); #HOOK
            if (_post("testIt")) {
                (new MikrotikHotspot())->getClient($ip_address, $username, $password);
            }
            $d = ORM::for_table('tbl_routers')->create();
            $d->name = $name;
            $d->ip_address = $ip_address;
            $d->username = $username;
            $d->password = $password;
            $d->description = $description;
            $d->enabled = $enabled;
            if ($hasSite()) {
                $d->site = $site;
            }
            $d->save();
            $endpointLock(false);

            r2(getUrl('routers/edit/') . $d->id(), 's', Lang::T('Data Created Successfully'));
        } else {
            r2(getUrl('routers/add'), 'e', $msg);
        }
        break;


    case 'edit-post':
        if (!Csrf::check(_post('csrf_token'))) {
            r2(getUrl('routers/edit/') . (int) _post('id'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $name = _post('name');
        $ip_address = $joinAddress(_post('ip_address'), _post('api_port'));
        $username = _post('username');
        $password = _post('password');
        $description = _post('description');
        $coordinates = _post('coordinates');
        $coverage = _post('coverage');
        $enabled = $_POST['enabled'];
        $site = mb_substr(_post('site'), 0, 32);
        $msg = '';
        if (!$validPort(_post('api_port')) || !$validAddressPort($ip_address)) {
            $msg .= Lang::T('The API port must be a number from 1 to 65535') . '<br>';
        }
        if ($hostMissing($ip_address)) {
            $msg .= Lang::T('Enter the router\'s IP address or host name') . '<br>';
        }
        if ($addressTooLong($ip_address)) {
            $msg .= Lang::T('The address is too long (128 characters at most)') . '<br>';
        }
        if (Validator::Length($name, 30, 4) == false) {
            $msg .= 'Name should be between 5 to 30 characters' . '<br>';
        }
        if($enabled || _post("testIt")){
            if ($ip_address == '' or $username == '') {
                $msg .= Lang::T('All field is required') . '<br>';
            }
        }

        $id = _post('id');
        // held until the change is saved (or the request ends with the error): one at a time with other edits, adds and deletes
        if (!$endpointLock()) {
            r2(getUrl('routers/edit/') . (int) $id, 'e', $busyText());
        }
        $d = ORM::for_table('tbl_routers')->find_one($id);
        if (!$d) {
            // nothing to compare the address with: stop here rather than check endpoints against a router that is not there
            $endpointLock(false);
            r2(getUrl('routers/list'), 'e', Lang::T('Data Not Found'));
        }

        $oldname = (string) $d['name'];
        // compared as text, strictly: "1000" and "01000" (equal as numbers to PHP's ==) are two names, and so a rename
        $renamed = $oldname !== (string) $name;
        if ($renamed) {
            $c = ORM::for_table('tbl_routers')->where('name', $name)->where_not_equal('id', $id)->find_one();
            if ($c) {
                $msg .= 'Name Already Exists<br>';
            }
        }

        if($enabled || _post("testIt")){
            // a new address must be free; enabling (even with the address unchanged) needs no other enabled router on it. "New"
            // as an endpoint: the same host and port written another way (":8728" added or dropped, letter case) is not new
            if (($endpointKey($d['ip_address']) !== $endpointKey($ip_address) && $endpointTaken($ip_address, $id)) || ($enabled && $endpointTaken($ip_address, $id, true))) {
                $msg .= 'IP Already Exists<br>';
            }
        }

        if (strtolower($name) == 'radius') {
            $msg .= '<b>Radius</b> name is reserved<br>';
        }

        if ($msg == '') {
            run_hook('router_edit'); #HOOK
            if (_post("testIt")) {
                (new MikrotikHotspot())->getClient($ip_address, $username, $password);
            }
            // another router answers at a new address: what the last check said (Online, last seen) was about the old one, and
            // the history's readings and alert states are too (RouterMonitor starts again from the change)
            $moved = $endpointKey($d['ip_address']) !== $endpointKey($ip_address);
            $enabledChanged = (int) $d['enabled'] !== (int) $enabled;
            // enabling a router whose address did not change: with Router Check off nothing else writes its status, so it is read now
            $refreshStatus = !$moved && $enabledChanged && $enabled && empty($config['router_check']);
            if ($moved || $refreshStatus) {
                $d->status = null;
                $d->last_seen = null;
                // with Router Check off nothing else ever writes the status, and the dashboard counts only routers that are
                // Online: an enabled router whose new endpoint answers is marked Online here (a plain connect, no login).
                // With Router Check on the cron's next check sets it.
                if ($enabled && empty($config['router_check']) && strcasecmp(trim((string) $_app_stage), 'demo') !== 0) {
                    [$pHost, $pPort] = RouterAddress::split($ip_address);
                    $sock = $pHost !== '' ? @stream_socket_client('tcp://' . (strpos($pHost, ':') !== false ? '[' . $pHost . ']' : $pHost) . ':' . ((int) $pPort ?: 8728), $pe, $ps, 2) : false;
                    if ($sock) {
                        fclose($sock);
                        $d->status = 'Online';
                        $d->last_seen = date('Y-m-d H:i:s');
                    }
                }
            }
            $d->name = $name;
            $d->ip_address = $ip_address;
            $d->username = $username;
            $d->password = $password;
            $d->description = $description;
            $d->coordinates = $coordinates;
            $d->coverage = $coverage;
            $d->enabled = $enabled;
            if ($hasSite()) {
                $d->site = $site;
            }
            // the router and, on a rename, everything that names it are saved together: no other request sees the new name
            // with plans still on the old one
            $db = ORM::getDb();
            $db->beginTransaction();
            try {
                $d->save();
                // routers saved before names were checked may share one: then the rows naming it cannot be told apart, and
                // they stay with the name (the other router) rather than perhaps moving that router's customers to this one
                $shared = $renamed && ORM::for_table('tbl_routers')->where('name', $oldname)->where_not_equal('id', $d['id'])->count() > 0;
                if ($renamed && !$shared) {
                    // one UPDATE per table rather than a read and then a save of each row: inside the transaction a read sees
                    // the database as it was at its first read, so a recharge (or voucher, payment) saved since would keep the
                    // old name. An UPDATE reads the rows as they are now, those included.
                    foreach (['tbl_plans', 'tbl_payment_gateway', 'tbl_pool', 'tbl_port_pool', 'tbl_transactions', 'tbl_user_recharges', 'tbl_voucher'] as $tbl) {
                        ORM::raw_execute("UPDATE `$tbl` SET `routers` = ? WHERE `routers` = ?", [$name, $oldname]);
                    }
                }
                // a new address starts a new history: the marker is saved with the address or neither is (a failed marker
                // undoes the edit, so readings of the old address are never shown as the new one's)
                if ($moved) {
                    RouterMonitor::moved($d['id'], true);
                }
                $db->commit();
            } catch (Throwable $e) {
                $db->rollBack();
                throw $e;
            }
            if ($enabledChanged || $moved) {
                $healthReset($d['id']);
            }
            $endpointLock(false);
            if ($shared) {
                r2(getUrl('routers/list'), 'w', Lang::T('Data Updated Successfully') . '. ' . Lang::T('Another router has the old name too, so its plans and customers were not moved to the new name. Move them on the plans that belong to this router.'));
            }
            r2(getUrl('routers/list'), 's', Lang::T('Data Updated Successfully'));
        } else {
            r2(getUrl('routers/edit/') . $id, 'e', $msg);
        }
        break;

    default:

        $name = _req('name'); // the search box posts it, the page links carry it: read from either, as the site filter is
        $site = $hasSite() ? _req('site') : '';
        $query = ORM::for_table('tbl_routers')->order_by_desc('id');
        if ($name != '') {
            $query->where_like('name', '%' . $name . '%');
        }
        if ($site !== '') {
            $query->where('site', $site);
        }
        $d = Paginator::findMany($query, ['name' => $name, 'site' => $site]) ?: [];
        $ui->assign('name', $name);
        $ui->assign('site', $site);
        $ui->assign('sites', $siteNames());
        $ui->assign('d', $d);
        $ids = [];
        foreach ($d as $r) {
            $ids[] = $r['id'];
        }
        $ui->assign('usage', $routerUsage($ids));
        $ui->assign('health_on', $healthOn);
        // a disabled router keeps its History tab: the saved readings are read from the database, not from the router
        $ui->assign('history_ready', RouterMonitor::ready());
        $ui->assign('routely_vpn', $routelyVpn());
        run_hook('view_list_routers'); #HOOK
        $ui->display('admin/routers/list.tpl');
        break;
}
