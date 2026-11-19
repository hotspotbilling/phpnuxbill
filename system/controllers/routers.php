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
    $host = trim((string) $host);
    $port = trim((string) $port);
    if ($port === '' || $host === '') {
        return $host;
    }
    if (preg_match('/^\[(.+)\](?::\d+)?$/', $host, $m)) {
        $host = $m[1]; // "[v6]" or "[v6]:port" typed into the address: the port field wins
    } elseif (substr_count($host, ':') === 1) {
        $host = explode(':', $host)[0]; // a port typed into the address too: the port field wins
    }
    if ((int) $port === 8728) {
        return $host;
    }
    // an IPv6 address has colons of its own, so with a port it is written [address]:port
    return (strpos($host, ':') !== false ? '[' . $host . ']' : $host) . ':' . (int) $port;
};
// "host:port" as the two form fields (an IPv6 address or a plain host keeps everything in the address field)
$splitAddress = function ($addr) {
    $addr = (string) $addr;
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
// without a port being on 8728, so "10.0.0.1" and "10.0.0.1:8728" are one router while "10.0.0.1:8729" is another
$endpointTaken = function ($address, $exceptId = 0) {
    $key = function ($a) {
        [$h, $p] = RouterAddress::split($a);
        return strtolower((string) $h) . '|' . ((int) $p ?: 8728);
    };
    $want = $key($address);
    foreach (ORM::for_table('tbl_routers')->select_many('id', 'ip_address')->find_array() as $r) {
        if ((int) $r['id'] !== (int) $exceptId && trim((string) $r['ip_address']) !== '' && $key($r['ip_address']) === $want) {
            return true;
        }
    }
    return false;
};
$validPort = function ($port) {
    $port = trim((string) $port);
    return $port === '' || (ctype_digit($port) && (int) $port >= 1 && (int) $port <= 65535);
};
// the port that will be used, after the port field has replaced any typed into the address: one written into the address
// ("host:99999", "host:abc", "[v6]:0") must be valid too. A host name or IPv6 address without a port is fine.
$validAddressPort = function ($address) use ($validPort) {
    $address = trim((string) $address);
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

$rosSend = Closure::fromCallable([RouterApi::class, 'send']);
$rosError = Closure::fromCallable([RouterApi::class, 'error']);

// What a router says about itself: identity, board, RouterOS version, uptime, load, memory, disk, temperature and users online.
// Each part is asked for separately, so a router without one (no hotspot, no health sensor) still answers the rest.
$rosHealth = function ($c, $withIfaces = false) use ($rosText, $rosUptime, $rosSend, $rosError) {
    $until = microtime(true) + 8; // every part shares one deadline
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
    $h['uptime'] = $rosUptime($rosText($r->getProperty('uptime')));
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
            $q->setArgument('.proplist', 'name,type,rx-byte,tx-byte,running,disabled');
            foreach ($rosSend($c, $q, $until) as $i) {
                if ($i->getType() !== \PEAR2\Net\RouterOS\Response::TYPE_DATA || $rosText($i->getProperty('running')) !== 'true'
                    || $rosText($i->getProperty('disabled')) === 'true' || count($h['ifaces']) >= 64) {
                    continue;
                }
                $rx = $rosText($i->getProperty('rx-byte'));
                $tx = $rosText($i->getProperty('tx-byte'));
                if (is_numeric($rx) && is_numeric($tx)) {
                    $h['ifaces'][] = ['name' => (string) $rosText($i->getProperty('name')), 'type' => (string) $rosText($i->getProperty('type')), 'rx' => (float) $rx, 'tx' => (float) $tx];
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
            'router_alert_telegram' => ['1', '0'], 'router_alert_email' => ['0', '1'], 'router_alert_via' => ['wa', 'sms'], 'router_backup' => ['0', '1'],
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
        $d->delete();
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
        $user = _post('username');
        $pass = _post('password');
        if ($ip === '' || $user === '') {
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
        foreach (ORM::for_table('tbl_routers')->where_in('id', $ids)->find_many() as $r) {
            // as on the form: an enabled router needs its address and username
            if ($op === 'enable' && (trim((string) $r['ip_address']) === '' || trim((string) $r['username']) === '')) {
                $skipped[] = $r['name'];
                continue;
            }
            $r->enabled = $op === 'enable' ? 1 : 0;
            $r->save();
            $n++;
        }
        _log('[' . $admin['username'] . ']: ' . ($op === 'enable' ? 'Routers enabled' : 'Routers disabled') . ': ' . $n, $admin['user_type'], $admin['id']);
        r2(getUrl('routers/list'), $skipped ? 'w' : 's', ($op === 'enable' ? Lang::T('Routers enabled') : Lang::T('Routers disabled')) . ': ' . $n
            . ($skipped ? '. ' . Lang::T('Not enabled, the IP address or username is missing') . ': ' . htmlspecialchars(implode(', ', $skipped)) : ''));
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
        if (!$d || !in_array($op, ['reboot', 'kick', 'backup', 'sync'], true)) {
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
            foreach (ORM::for_table('tbl_user_recharges')->where('status', 'on')->where('routers', $d['name'])->find_many() as $tur) {
                $p = ORM::for_table('tbl_plans')->find_one($tur['plan_id']);
                $c = ORM::for_table('tbl_customers')->find_one($tur['customer_id']);
                // the device sends a customer to its plan's router: a plan now on another router is not this router's to sync
                if (!$p || !$c || strcasecmp(rtrim((string) $p['routers']), rtrim((string) $d['name'])) !== 0) {
                    $failed[] = $tur['username'];
                    continue;
                }
                try {
                    $dvc = Package::getDevice($p);
                    if (!file_exists($dvc)) {
                        throw new Exception(Lang::T('Devices Not Found'));
                    }
                    require_once $dvc;
                    $dev = new $p['device'];
                    method_exists($dev, 'sync_customer') ? $dev->sync_customer($c, $p) : $dev->add_customer($c, $p);
                    $done++;
                } catch (Throwable $e) {
                    $failed[] = $tur['username'];
                }
            }
            @session_start();
            _log($who . 'Router sync ' . $d['name'] . ': ' . $done . ' done, ' . count($failed) . ' failed', $admin['user_type'], $admin['id']);
            r2($back, $failed ? 'w' : 's', Lang::T('Customers sent to the router') . ': ' . $done
                . ($failed ? '. ' . Lang::T('Not sent') . ': ' . htmlspecialchars(implode(', ', array_slice($failed, 0, 20))) . (count($failed) > 20 ? ' …' : '') : ''));
        }
        [$c, $err] = $rosConnect($d['ip_address'], $d['username'], $d['password'], 8);
        if (!$c) {
            r2($back, 'e', htmlspecialchars($d['name'] . ': ' . $err));
        }
        try {
            if ($op === 'reboot') {
                // three outcomes: the command could not be sent (an error), the router answered (done, or refused: no permission),
                // or it was sent and the connection went quiet, which is what a restarting router does but cannot be confirmed
                $req = new \PEAR2\Net\RouterOS\Request('/system/reboot');
                $req->setTag('reboot');
                $c->sendAsync($req); // a failure here is thrown to the error below: nothing was sent
                $confirmed = false;
                try {
                    $c->loop(5);
                    $res = $c->extractNewResponses('reboot');
                    if (($refused = $rosError($res)) !== null) {
                        throw new Exception($refused);
                    }
                    foreach ($res as $r) {
                        $confirmed = $confirmed || $r->getType() === \PEAR2\Net\RouterOS\Response::TYPE_FINAL;
                    }
                } catch (\PEAR2\Net\Transmitter\SocketException $e) {
                    // the connection dropped after the command went out: the usual sign of a restart
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
                $list = $rosSend($c, $q, microtime(true) + 15);
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
                foreach (array_chunk($ids, 50) as $chunk) {
                    $rm = new \PEAR2\Net\RouterOS\Request('/ip/hotspot/active/remove');
                    $rm->setArgument('numbers', implode(',', $chunk));
                    try {
                        $stopped = $rosError($rosSend($c, $rm, microtime(true) + 15));
                    } catch (Throwable $e) {
                        $stopped = $e->getMessage();
                    }
                    if ($stopped !== null) {
                        break;
                    }
                    $removed += count($chunk);
                }
                _log($who . 'Hotspot users disconnected on ' . $d['name'] . ': ' . $removed . ' of ' . count($ids), $admin['user_type'], $admin['id']);
                if ($stopped !== null) {
                    r2($back, 'e', htmlspecialchars($d['name'] . ': ' . Lang::T('hotspot users disconnected') . ': ' . $removed . ' ' . Lang::T('of') . ' ' . count($ids) . '. ' . $stopped));
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
                // on boards whose root folder is emptied by a restart, saved in flash/ so it survives one
                $file = RouterApi::backupFolder(RouterApi::rows($c, '/file/print', ['name', 'type'], microtime(true) + 15)) . 'phpnuxbill-' . date('Ymd-His');
                $b = new \PEAR2\Net\RouterOS\Request('/system/backup/save');
                $b->setArgument('name', $file);
                $b->setArgument('password', $password);
                if (($err = $rosError($rosSend($c, $b, microtime(true) + 30))) !== null) {
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
        $until = microtime(true) + 15;
        try {
            if ($action === 'sessions') {
                // who is connected now, hotspot and PPPoE, each matched to a customer when the name is one
                // a router without the hotspot (or PPP) package has nobody there; any other refusal (no permission) is said, so
                // it is not mistaken for nobody being online
                $hs = [];
                $pp = [];
                $readErrors = [];
                try {
                    $hs = RouterApi::rows($c, '/ip/hotspot/active/print', ['.id', 'user', 'address', 'mac-address', 'uptime', 'bytes-in', 'bytes-out', 'server'], $until);
                } catch (Throwable $e) {
                    if ($e->getCode() === 408) {
                        throw $e;
                    }
                    if (stripos($e->getMessage(), 'no such command') === false) {
                        $readErrors[] = 'Hotspot: ' . $e->getMessage();
                    }
                }
                try {
                    // PPPoE only: L2TP, SSTP and the other tunnels are not customers
                    $pp = RouterApi::rows($c, '/ppp/active/print', ['.id', 'name', 'service', 'caller-id', 'address', 'uptime'], $until, [], ['service' => 'pppoe']);
                } catch (Throwable $e) {
                    if ($e->getCode() === 408) {
                        throw $e;
                    }
                    if (stripos($e->getMessage(), 'no such command') === false) {
                        $readErrors[] = 'PPPoE: ' . $e->getMessage();
                    }
                }
                // up to 5,000 of each, searched and paged in the page; the totals say when a list was cut
                $totalHs = count($hs);
                $totalPp = count($pp);
                $hs = array_slice($hs, 0, 5000);
                $pp = array_slice($pp, 0, 5000);
                $names = array_values(array_unique(array_filter(array_merge(array_column($hs, 'user'), array_column($pp, 'name')), 'strlen')));
                // each session is matched by the name it signs in with: a hotspot user by the customer's username, a PPPoE one by
                // the PPPoE username when the customer has one and by the username otherwise (as the PPPoE device sets it up), so a
                // PPPoE name that equals another customer's username cannot pick the wrong customer
                $whoHs = [];
                $whoPp = [];
                if ($names) {
                    $in = implode(',', array_fill(0, count($names), '?'));
                    $st = ORM::getDb()->prepare("SELECT id, username, pppoe_username, fullname FROM tbl_customers WHERE username IN ($in) OR pppoe_username IN ($in) ORDER BY id");
                    $st->execute(array_merge($names, $names));
                    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $cu) {
                        $one = ['id' => (int) $cu['id'], 'name' => $cu['fullname']];
                        if ((string) $cu['username'] !== '' && !isset($whoHs[$cu['username']])) {
                            $whoHs[$cu['username']] = $one;
                        }
                        $ppName = (string) $cu['pppoe_username'] !== '' ? $cu['pppoe_username'] : (string) $cu['username'];
                        if ($ppName !== '' && !isset($whoPp[$ppName])) {
                            $whoPp[$ppName] = $one;
                        }
                    }
                }
                $out = ['ok' => true, 'hotspot' => [], 'pppoe' => [], 'total_hotspot' => $totalHs, 'total_pppoe' => $totalPp, 'customer_url' => getUrl('customers/view/'),
                    'read_errors' => $readErrors, 'cfg' => $listedFrom];
                foreach ($hs as $h) {
                    $out['hotspot'][] = ['sid' => $h['.id'], 'user' => $h['user'], 'address' => $h['address'], 'mac' => $h['mac-address'], 'uptime' => $rosUptime($h['uptime']),
                        'in' => (float) $h['bytes-in'], 'out' => (float) $h['bytes-out'], 'server' => $h['server'], 'customer' => $whoHs[$h['user']] ?? null];
                }
                foreach ($pp as $h) {
                    $out['pppoe'][] = ['sid' => $h['.id'], 'user' => $h['name'], 'address' => $h['address'], 'mac' => $h['caller-id'], 'uptime' => $rosUptime($h['uptime']),
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
                if ($kind === 'pppoe') {
                    // a session ID can be reused once that session ends: the live session must still be the one listed (same
                    // name, and a PPPoE one), or it is not removed
                    $live = RouterApi::rows($c, '/ppp/active/print', ['.id', 'name', 'service'], $until, [], ['.id' => $sid]);
                    if (!$live || (string) $live[0]['name'] !== (string) _post('user') || (string) $live[0]['service'] !== 'pppoe') {
                        $say(['ok' => false, 'stale' => true, 'error' => Lang::T('That session has ended or changed. Refresh the list and try again.')]);
                    }
                }
                RouterApi::run($c, $kind === 'hotspot' ? '/ip/hotspot/active/remove' : '/ppp/active/remove', ['numbers' => $sid], $until);
                _log('[' . $admin['username'] . ']: ' . 'Disconnected ' . $kind . ' session ' . mb_substr(_post('user'), 0, 64) . ' on ' . $d['name'], $admin['user_type'], $admin['id']);
                $say(['ok' => true]);
            }

            if ($action === 'interfaces') {
                $rows = RouterApi::rows($c, '/interface/print', ['name', 'type', 'running', 'disabled', 'dynamic', 'comment', 'mac-address', 'mtu', 'rx-byte', 'tx-byte',
                    'rx-error', 'tx-error', 'rx-drop', 'tx-drop', 'link-downs', 'last-link-up-time', 'last-link-down-time'], $until);
                // every PPPoE customer adds a dynamic interface: up to 2,000, searched and paged in the page
                $num = function ($v) {
                    return is_numeric($v) ? (float) $v : null;
                };
                $items = [];
                foreach (array_slice($rows, 0, 2000) as $r) {
                    $items[] = ['name' => $r['name'], 'type' => $r['type'], 'running' => $r['running'] === 'true', 'disabled' => $r['disabled'] === 'true', 'dynamic' => $r['dynamic'] === 'true',
                        'comment' => $r['comment'], 'mac' => $r['mac-address'], 'mtu' => $r['mtu'], 'rx' => $num($r['rx-byte']), 'tx' => $num($r['tx-byte']),
                        'rx_error' => $num($r['rx-error']), 'tx_error' => $num($r['tx-error']), 'rx_drop' => $num($r['rx-drop']), 'tx_drop' => $num($r['tx-drop']),
                        'link_downs' => $num($r['link-downs']), 'last_up' => $r['last-link-up-time'], 'last_down' => $r['last-link-down-time']];
                }
                $say(['ok' => true, 'items' => $items, 'total' => count($rows)]);
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
                    if ($e->getCode() === 408) {
                        throw $e;
                    }
                    $add('warn', Lang::T('The API user\'s rights could not be read'), $e->getMessage());
                }
                // 2. hotspot and PPPoE servers; a router without the hotspot package has none, any other failed read leaves it unknown
                $hsServers = [];
                $ppServers = [];
                $serverErrors = [];
                try {
                    $hsServers = RouterApi::rows($c, '/ip/hotspot/print', ['name', 'interface', 'disabled', 'profile'], $until);
                } catch (Throwable $e) {
                    if ($e->getCode() === 408) {
                        throw $e;
                    }
                    if (stripos($e->getMessage(), 'no such command') === false) {
                        $serverErrors[] = Lang::T('Hotspot') . ': ' . $e->getMessage();
                    }
                }
                try {
                    $ppServers = RouterApi::rows($c, '/interface/pppoe-server/server/print', ['service-name', 'interface', 'disabled'], $until);
                } catch (Throwable $e) {
                    if ($e->getCode() === 408) {
                        throw $e;
                    }
                    if (stripos($e->getMessage(), 'no such command') === false) {
                        $serverErrors[] = 'PPPoE: ' . $e->getMessage();
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
                    $found = false; // true allowed, false not, null cannot tell
                    try {
                        // the rules are tried in order and the first that matches decides: a matching "deny" is no access. A rule
                        // that may match but has a condition this check cannot judge (a path, a method, addresses, a server, a
                        // regular expression) leaves the answer unknown rather than a guess.
                        foreach (RouterApi::rows($c, '/ip/hotspot/walled-garden/print', ['dst-host', 'dst-port', 'path', 'method', 'src-address', 'dst-address', 'server', 'disabled', 'action'], $until) as $w) {
                            if ($w['disabled'] === 'true') {
                                continue;
                            }
                            $pat = strtolower((string) $w['dst-host']);
                            if ($pat !== '' && $pat[0] === ':') {
                                $found = null;
                                break;
                            }
                            if ($pat !== '' && !($pat === $host || fnmatch($pat, $host) || ($pat[0] === '*' && substr($host, -strlen(ltrim($pat, '*'))) === ltrim($pat, '*')))) {
                                continue;
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
                            $found = strtolower((string) $w['action']) !== 'deny';
                            break;
                        }
                        $site = $host . ':' . $port;
                        if ($found === null) {
                            $add('warn', Lang::T('Could not tell whether the billing site is in the walled garden'), Lang::T('A walled garden rule for') . ' ' . $site . ' ' . Lang::T('has conditions this check cannot judge. Check it in Winbox, IP > Hotspot > Walled Garden.'));
                        } else {
                            $add($found ? 'ok' : 'bad', $found ? Lang::T('The billing site is in the walled garden') : Lang::T('The billing site is not in the walled garden'),
                                $found ? $site : Lang::T('Customers cannot open') . ' ' . $site . ' ' . Lang::T('before they sign in. Add it in Winbox, IP > Hotspot > Walled Garden, as') . ' ' . $host);
                        }
                    } catch (Throwable $e) {
                        if ($e->getCode() === 408) {
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
                        $need = array_merge($onHs ? ['hotspot'] : [], $onPp ? ['ppp'] : []) ?: ['hotspot', 'ppp'];
                        $covered = [];
                        foreach ($rad as $x) {
                            $covered = array_merge($covered, array_map('trim', explode(',', strtolower((string) $x['service']))));
                        }
                        $lacking = array_values(array_diff($need, $covered));
                        $list = implode(', ', array_map(function ($x) { return $x['address'] . ' (' . $x['service'] . ')'; }, $rad));
                        if (!$rad) {
                            $add('bad', Lang::T('No RADIUS server is set on the router'), Lang::T('PHPNuxBill uses RADIUS: add the RADIUS server in Winbox, RADIUS, for') . ' ' . implode(' ' . Lang::T('and') . ' ', $need) . '.');
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
                                if ($off) {
                                    $add('bad', Lang::T('RADIUS is set but not used'), Lang::T('Turn on Use RADIUS in') . ': ' . implode(', ', array_unique($off)) . '. ' . $list);
                                } else {
                                    $add('ok', Lang::T('RADIUS is set up'), $list);
                                }
                            } catch (Throwable $e) {
                                if ($e->getCode() === 408) {
                                    throw $e;
                                }
                                $add('warn', Lang::T('Could not tell whether the profiles use RADIUS'), $e->getMessage());
                            }
                        }
                    } catch (Throwable $e) {
                        if ($e->getCode() === 408) {
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
                    if ($e->getCode() === 408) {
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
                        RouterApi::run($c, '/system/package/update/check-for-updates', [], microtime(true) + 20);
                    } catch (Throwable $e) {
                        if ($e->getCode() === 408) {
                            throw $e;
                        }
                        $checkErr = $e->getMessage();
                    }
                    $until = microtime(true) + 5; // the check used its own time: the versions are read after it, within the budget
                }
                $u = RouterApi::rows($c, '/system/package/update/print', ['channel', 'installed-version', 'latest-version', 'status'], $until);
                $fw = [];
                try {
                    $fw = RouterApi::rows($c, '/system/routerboard/print', ['routerboard', 'model', 'current-firmware', 'upgrade-firmware'], $until);
                } catch (Throwable $e) {
                    if ($e->getCode() === 408) {
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
                            'auto' => (bool) preg_match('/(^|\/)phpnuxbill-auto-\d{8}\.backup$/', (string) $f['name'])];
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
        echo json_encode(['ok' => true, 'range' => $range, 'collecting' => RouterMonitor::enabled()] + RouterMonitor::history((int) $d['id'], $range), JSON_INVALID_UTF8_SUBSTITUTE);
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
            if (is_array($cached) && ($cached['key'] ?? '') === $key && $fresh) {
                unset($cached['key']);
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
                $out = ['ok' => false, 'error' => $e->getCode() === 403 ? Lang::T('Connected, but this user cannot read the system details.') : $e->getMessage()];
            }
        } else {
            $out = ['ok' => false, 'error' => $err, 'hint' => $rosHint($err)];
        }
        $out['checked'] = date('Y-m-d H:i:s');
        $out['observed'] = $observed; // full precision: the store below compares it with the next reading's start
        $out['done'] = round(microtime(true), 3); // when this answer was ready, failures included: what the live sharing is timed by
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        // text from an old router can be in a legacy encoding: replaced rather than failing the whole answer
        $json = json_encode($out + ['key' => $key], JSON_INVALID_UTF8_SUBSTITUTE);
        $fh = $json !== false ? @fopen($file, 'c+') : false;
        if ($fh) {
            // read and write under one lock: when two readings of the same router finish together, the newer one stays
            if (flock($fh, LOCK_EX)) {
                $old = json_decode((string) stream_get_contents($fh), true);
                // whichever began last stays, whatever login it used: a slow check with old details cannot replace a newer one
                // (an entry for other details is still never served, as the key is checked when reading)
                $newer = is_array($old) && isset($old['observed']) && (float) $old['observed'] > $observed;
                if (!$newer) {
                    ftruncate($fh, 0);
                    rewind($fh);
                    fwrite($fh, $json);
                    fflush($fh);
                }
                flock($fh, LOCK_UN);
            }
            fclose($fh);
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
        if (Validator::Length($name, 30, 1) == false) {
            $msg .= 'Name should be between 1 to 30 characters' . '<br>';
        }
        if($enabled || _post("testIt")){
            if ($ip_address == '' or $username == '') {
                $msg .= Lang::T('All field is required') . '<br>';
            }

            if ($endpointTaken($ip_address)) {
                $msg .= Lang::T('IP Router Already Exist') . '<br>';
            }
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
        if (Validator::Length($name, 30, 4) == false) {
            $msg .= 'Name should be between 5 to 30 characters' . '<br>';
        }
        if($enabled || _post("testIt")){
            if ($ip_address == '' or $username == '') {
                $msg .= Lang::T('All field is required') . '<br>';
            }
        }

        $id = _post('id');
        $d = ORM::for_table('tbl_routers')->find_one($id);
        if ($d) {
        } else {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }

        if ($d['name'] != $name) {
            $c = ORM::for_table('tbl_routers')->where('name', $name)->where_not_equal('id', $id)->find_one();
            if ($c) {
                $msg .= 'Name Already Exists<br>';
            }
        }
        $oldname = $d['name'];

        if($enabled || _post("testIt")){
            if ($d['ip_address'] != $ip_address) {
                if ($endpointTaken($ip_address, $id)) {
                    $msg .= 'IP Already Exists<br>';
                }
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
            $d->save();
            if ($name != $oldname) {
                $p = ORM::for_table('tbl_plans')->where('routers', $oldname)->find_result_set();
                $p->set('routers', $name);
                $p->save();
                $p = ORM::for_table('tbl_payment_gateway')->where('routers', $oldname)->find_result_set();
                $p->set('routers', $name);
                $p->save();
                $p = ORM::for_table('tbl_pool')->where('routers', $oldname)->find_result_set();
                $p->set('routers', $name);
                $p->save();
                $p = ORM::for_table('tbl_transactions')->where('routers', $oldname)->find_result_set();
                $p->set('routers', $name);
                $p->save();
                $p = ORM::for_table('tbl_user_recharges')->where('routers', $oldname)->find_result_set();
                $p->set('routers', $name);
                $p->save();
                $p = ORM::for_table('tbl_voucher')->where('routers', $oldname)->find_result_set();
                $p->set('routers', $name);
                $p->save();
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
        $ui->assign('routely_vpn', $routelyVpn());
        run_hook('view_list_routers'); #HOOK
        $ui->display('admin/routers/list.tpl');
        break;
}
