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

// How many plans and active customers use each router (by name): delete refuses a router that is still in use, and the list says so
$routerUsage = function (array $names) {
    $use = [];
    $names = array_values(array_unique(array_filter(array_map('strval', $names), 'strlen')));
    foreach ($names as $n) {
        $use[$n] = ['plans' => 0, 'active' => 0, 'expired' => 0];
    }
    if (!$names) {
        return $use;
    }
    $in = implode(',', array_fill(0, count($names), '?'));
    $db = ORM::getDb();
    $st = $db->prepare("SELECT routers, COUNT(*) FROM tbl_plans WHERE routers IN ($in) GROUP BY routers");
    $st->execute($names);
    foreach ($st->fetchAll(PDO::FETCH_NUM) as $r) {
        $use[$r[0]]['plans'] = (int) $r[1];
    }
    $st = $db->prepare("SELECT routers, status, COUNT(*) FROM tbl_user_recharges WHERE status IN ('on', 'off') AND routers IN ($in) GROUP BY routers, status");
    $st->execute($names);
    foreach ($st->fetchAll(PDO::FETCH_NUM) as $r) {
        $use[$r[0]][$r[1] === 'on' ? 'active' : 'expired'] = (int) $r[2];
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
    if (substr_count($host, ':') === 1) {
        $host = explode(':', $host)[0]; // a port typed into the address too: the port field wins
    }
    return (int) $port === 8728 ? $host : $host . ':' . (int) $port;
};
// "host:port" as the two form fields (an IPv6 address or a plain host keeps everything in the address field)
$splitAddress = function ($addr) {
    $addr = (string) $addr;
    if (substr_count($addr, ':') === 1) {
        [$h, $p] = explode(':', $addr);
        if (ctype_digit($p)) {
            return [$h, $p];
        }
    }
    return [$addr, ''];
};
$validPort = function ($port) {
    $port = trim((string) $port);
    return $port === '' || (ctype_digit($port) && (int) $port >= 1 && (int) $port <= 65535);
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

// One answer from the router as plain text (a long value can arrive as a stream)
$rosText = function ($v) {
    if (is_resource($v)) {
        $v = stream_get_contents($v);
    }
    return $v === null ? null : (string) $v;
};

// "1w2d03:04:05" or "12d4h31m5s" (RouterOS uptime) as "9d 3h 4m"
$rosUptime = function ($u) {
    $u = (string) $u;
    if (!preg_match('/^(?:(\d+)w)?(?:(\d+)d)?(?:(\d+):(\d+):(\d+)|(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?)$/', $u, $m) || $u === '') {
        return $u;
    }
    $days = (int) ($m[1] ?? 0) * 7 + (int) ($m[2] ?? 0);
    $hours = (int) (($m[3] ?? '') !== '' ? $m[3] : ($m[6] ?? 0));
    $mins = (int) (($m[4] ?? '') !== '' ? $m[4] : ($m[7] ?? 0));
    return trim(($days ? $days . 'd ' : '') . ($days || $hours ? $hours . 'h ' : '') . $mins . 'm');
};

// Connect to a router; returns [client, null] or [null, the reason it failed]
$rosConnect = function ($ip, $user, $pass, $timeout = 5) {
    try {
        $c = (new MikrotikHotspot())->getClient($ip, $user, $pass, null, $timeout);
        return $c ? [$c, null] : [null, Lang::T('Not available in Demo mode')];
    } catch (Throwable $e) {
        // the library wraps the reason (refused, timed out, wrong login) in a general message: show both
        $parts = [];
        for ($x = $e; $x && count($parts) < 3; $x = $x->getPrevious()) {
            $m = trim(preg_replace('/\s+/', ' ', strip_tags($x->getMessage())));
            if ($m !== '' && !in_array($m, $parts, true)) {
                $parts[] = $m;
            }
        }
        $msg = implode(': ', $parts);
        return [null, $msg !== '' ? mb_substr($msg, 0, 160) : Lang::T('Could not connect to the router')];
    }
};

// What a router says about itself: identity, board, RouterOS version, uptime, load, memory, disk, temperature and users online.
// Each part is asked for separately, so a router without one (no hotspot, no health sensor) still answers the rest.
$rosHealth = function ($c, $withIfaces = false) use ($rosText, $rosUptime) {
    $h = ['identity' => '', 'board' => '', 'version' => '', 'uptime' => '', 'cpu' => null, 'cores' => null,
        'mem_used' => null, 'mem_total' => null, 'disk_used' => null, 'disk_total' => null, 'temp' => null,
        'hotspot' => null, 'pppoe' => null];
    try {
        $h['identity'] = (string) $rosText($c->sendSync(new \PEAR2\Net\RouterOS\Request('/system/identity/print'))->getProperty('name'));
    } catch (Throwable $e) {
    }
    $r = $c->sendSync(new \PEAR2\Net\RouterOS\Request('/system/resource/print'));
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
    try {
        // RouterOS 7 lists one sensor per item (name, value); RouterOS 6 gives them as properties of one item
        foreach ($c->sendSync(new \PEAR2\Net\RouterOS\Request('/system/health/print')) as $s) {
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
    foreach (['hotspot' => '/ip/hotspot/active/print', 'pppoe' => '/ppp/active/print'] as $k => $menu) {
        try {
            $q = new \PEAR2\Net\RouterOS\Request($menu);
            $q->setArgument('count-only', '');
            $n = $rosText($c->sendSync($q)->end()->getProperty('ret'));
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
            foreach ($c->sendSync($q) as $i) {
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

// Live health is on unless turned off in Settings > Miscellaneous
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
        $use = $routerUsage([$d['name']])[$d['name']] ?? ['plans' => 0, 'active' => 0];
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
        $user = _post('username');
        $pass = _post('password');
        if ($ip === '' || $user === '') {
            echo json_encode(['ok' => false, 'error' => Lang::T('Enter the IP address and username first.')]);
            exit;
        }
        if ($pass === '' && (int) _post('id') > 0) {
            // the edit form may be sent with the secret left empty: use the saved one
            $saved = ORM::for_table('tbl_routers')->find_one((int) _post('id'));
            $pass = $saved ? (string) $saved['password'] : '';
        }
        session_write_close(); // the router may take seconds: do not keep the admin's other pages waiting on the session
        [$c, $err] = $rosConnect($ip, $user, $pass, 8);
        if (!$c) {
            echo json_encode(['ok' => false, 'error' => $err, 'hint' => $rosHint($err)]);
            exit;
        }
        try {
            $h = $rosHealth($c);
            echo json_encode(['ok' => true, 'identity' => $h['identity'], 'board' => $h['board'], 'version' => $h['version'], 'uptime' => $h['uptime']]);
        } catch (Throwable $e) {
            // logged in, but this user may not read /system: still a working connection
            echo json_encode(['ok' => true, 'identity' => '', 'board' => '', 'version' => '', 'uptime' => '', 'note' => Lang::T('Connected, but this user cannot read the system details.')]);
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
        foreach (ORM::for_table('tbl_routers')->where_in('id', $ids)->find_many() as $r) {
            $r->enabled = $op === 'enable' ? 1 : 0;
            $r->save();
            $n++;
        }
        _log('[' . $admin['username'] . ']: ' . ($op === 'enable' ? 'Routers enabled' : 'Routers disabled') . ': ' . $n, $admin['user_type'], $admin['id']);
        r2(getUrl('routers/list'), 's', ($op === 'enable' ? Lang::T('Routers enabled') : Lang::T('Routers disabled')) . ': ' . $n);
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
            // send every active customer of this router to it again, as Plans > Sync does for all routers
            set_time_limit(0);
            $done = 0;
            $failed = [];
            foreach (ORM::for_table('tbl_user_recharges')->where('status', 'on')->where('routers', $d['name'])->find_many() as $tur) {
                $p = ORM::for_table('tbl_plans')->find_one($tur['plan_id']);
                $c = ORM::for_table('tbl_customers')->find_one($tur['customer_id']);
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
                    $dev = new $p['device'];
                    method_exists($dev, 'sync_customer') ? $dev->sync_customer($c, $p) : $dev->add_customer($c, $p);
                    $done++;
                } catch (Throwable $e) {
                    $failed[] = $tur['username'];
                }
            }
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
                try {
                    $c->sendSync(new \PEAR2\Net\RouterOS\Request('/system/reboot'));
                } catch (Throwable $e) {
                    // the router drops the connection as it restarts, often before it answers
                }
                _log($who . 'Router rebooted: ' . $d['name'], $admin['user_type'], $admin['id']);
                r2($back, 's', htmlspecialchars($d['name']) . ': ' . Lang::T('restarting. It is back in a minute or two.'));
            }
            if ($op === 'kick') {
                $q = new \PEAR2\Net\RouterOS\Request('/ip/hotspot/active/print');
                $q->setArgument('.proplist', '.id');
                $ids = [];
                foreach ($c->sendSync($q) as $a) {
                    if ($a->getType() === \PEAR2\Net\RouterOS\Response::TYPE_DATA && ($i = $rosText($a->getProperty('.id'))) !== null) {
                        $ids[] = $i;
                    }
                }
                foreach (array_chunk($ids, 50) as $chunk) {
                    $rm = new \PEAR2\Net\RouterOS\Request('/ip/hotspot/active/remove');
                    $rm->setArgument('numbers', implode(',', $chunk));
                    $c->sendSync($rm);
                }
                _log($who . 'Hotspot users disconnected on ' . $d['name'] . ': ' . count($ids), $admin['user_type'], $admin['id']);
                r2($back, 's', htmlspecialchars($d['name']) . ': ' . Lang::T('hotspot users disconnected') . ': ' . count($ids) . '. ' . Lang::T('They can sign in again at once.'));
            }
            if ($op === 'backup') {
                $file = 'phpnuxbill-' . date('Ymd-His');
                $b = new \PEAR2\Net\RouterOS\Request('/system/backup/save');
                $b->setArgument('name', $file);
                $res = $c->sendSync($b);
                foreach ($res as $r) {
                    if ($r->getType() === \PEAR2\Net\RouterOS\Response::TYPE_ERROR) {
                        throw new Exception((string) $rosText($r->getProperty('message')));
                    }
                }
                _log($who . 'Backup saved on ' . $d['name'] . ': ' . $file . '.backup', $admin['user_type'], $admin['id']);
                r2($back, 's', htmlspecialchars($d['name']) . ': ' . Lang::T('backup saved on the router as') . ' ' . $file . '.backup. '
                    . Lang::T('Download it from Files in Winbox or WebFig.'));
            }
        } catch (Throwable $e) {
            r2($back, 'e', htmlspecialchars($d['name'] . ': ' . $e->getMessage()));
        }
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
            $cached = json_decode((string) @file_get_contents($file), true);
            // a live reading is shared for under a second (several admins on one router), timed by when it was read, since the
            // file time only counts whole seconds
            $fresh = !$live || (isset($cached['at']) && microtime(true) - (float) $cached['at'] < 0.9);
            if (is_array($cached) && ($cached['key'] ?? '') === $key && $fresh) {
                unset($cached['key']);
                $cached['age'] = time() - filemtime($file);
                echo json_encode($cached);
                exit;
            }
        }
        [$c, $err] = $rosConnect($d['ip_address'], $d['username'], $d['password'], 5);
        if ($c) {
            try {
                $out = ['ok' => true] + $rosHealth($c, $live);
            } catch (Throwable $e) {
                $out = ['ok' => false, 'error' => Lang::T('Connected, but this user cannot read the system details.')];
            }
        } else {
            $out = ['ok' => false, 'error' => $err, 'hint' => $rosHint($err)];
        }
        $out['checked'] = date('Y-m-d H:i:s');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($file, json_encode($out + ['key' => $key]), LOCK_EX);
        $out['age'] = 0;
        echo json_encode($out);
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
        if (!$validPort(_post('api_port'))) {
            $msg .= Lang::T('The API port must be a number from 1 to 65535') . '<br>';
        }
        if (Validator::Length($name, 30, 1) == false) {
            $msg .= 'Name should be between 1 to 30 characters' . '<br>';
        }
        if($enabled || _post("testIt")){
            if ($ip_address == '' or $username == '') {
                $msg .= Lang::T('All field is required') . '<br>';
            }

            $d = ORM::for_table('tbl_routers')->where('ip_address', $ip_address)->find_one();
            if ($d) {
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
        if (!$validPort(_post('api_port'))) {
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
                $c = ORM::for_table('tbl_routers')->where('ip_address', $ip_address)->where_not_equal('id', $id)->find_one();
                if ($c) {
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

        $name = _post('name');
        $name = _post('name');
        $site = $hasSite() ? _req('site') : '';
        $query = ORM::for_table('tbl_routers')->order_by_desc('id');
        if ($name != '') {
            $query->where_like('name', '%' . $name . '%');
        }
        if ($site !== '') {
            $query->where('site', $site);
        }
        $d = Paginator::findMany($query, ['name' => $name, 'site' => $site]);
        $ui->assign('site', $site);
        $ui->assign('sites', $siteNames());
        $ui->assign('d', $d);
        $names = [];
        foreach ($d as $r) {
            $names[] = $r['name'];
        }
        $ui->assign('usage', $routerUsage($names));
        $ui->assign('health_on', $healthOn);
        $ui->assign('routely_vpn', $routelyVpn());
        run_hook('view_list_routers'); #HOOK
        $ui->display('admin/routers/list.tpl');
        break;
}
