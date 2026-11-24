<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill)
 *  by https://t.me/ibnux
 *
 * Authorize
 *    - Voucher activation
 * Authenticate
 *    - is it allow to login
 * Accounting
 *    - log
 **/


header("Content-Type: application/json");

// Accounting is most of the traffic (every online user, every interim update) and only writes rad_acct: start it
// without plugins and translations, which are most of the cost of a request and are not used by it.
// Same choice as the dispatcher below: the header when it is not empty, otherwise ?action= (trimmed like _get()).
$radiusLight = $_SERVER['HTTP_X_FREERADIUS_SECTION'] ?? '';
if (empty($radiusLight)) {
    $radiusLight = trim((string) ($_GET['action'] ?? ''));
}
$radiusLight = $radiusLight === 'accounting';
// FreeRADIUS (and the router behind it) waits on every answer: an alert sent while answering must never hold it
$GLOBALS['radius_request'] = true;

include "init.php";

// Optional shared secret (Settings > FreeRadius > REST secret). When one is set, every request must carry it as
// the X-Radius-Token header or the "token" parameter, otherwise anybody could post forged accounting/authorize calls.
$radiusSecret = (string) ($config['radius_rest_secret'] ?? '');
if ($radiusSecret !== '') {
    $sentToken = (string) ($_SERVER['HTTP_X_RADIUS_TOKEN'] ?? _req('token'));
    if (!hash_equals($radiusSecret, $sentToken)) {
        show_radius_result(['Reply-Message' => 'Forbidden'], 403);
    }
}

$action = $_SERVER['HTTP_X_FREERADIUS_SECTION'] ?? '';
if (empty($action)) {
    $action = _get('action');
}

// the dashboard reads this to show the FreeRADIUS engine as online (REST setups have no radius database to ping)
if (class_exists('SystemInfo')) {
    SystemInfo::radiusTouch();
}

//debug
// if (!empty($action)) {
//     file_put_contents("$action.json", json_encode([
//         'header' => $_SERVER,
//         'get' => $_GET,
//         'post' => $_POST,
//         'time' => time()
//     ]));
// }

try {
    switch ($action) {
        case 'authenticate':
            // proves who the caller is (customer password, PPPoE password or a real RADIUS voucher); dies with 401 otherwise
            radius_identify(radius_credentials());
            header("HTTP/1.1 204 No Content");
            die();
            break;
        case 'authorize':
            $who = radius_identify(radius_credentials());
            $username = $who['username'];
            $tur = radius_find_recharge($username);
            if ($tur) {
                process_radiust_rest($tur);
            } else if ($who['voucher']) {
                $v = $who['voucher_row'];
                // claim the voucher atomically so two simultaneous logins cannot both activate it
                ORM::raw_execute("UPDATE tbl_voucher SET status = '1', used_date = ? WHERE id = ? AND status = '0'", [date('Y-m-d H:i:s'), $v['id']]);
                if (ORM::get_last_statement()->rowCount() !== 1) {
                    show_radius_result(['Reply-Message' => 'Voucher Expired...'], 401);
                }
                if (!Package::rechargeUser(0, $v['routers'], $v['id_plan'], "Voucher", $username)) {
                    ORM::raw_execute("UPDATE tbl_voucher SET status = '0', used_date = NULL WHERE id = ?", [$v['id']]);
                    show_radius_result(['Reply-Message' => 'Voucher activation failed.'], 401);
                }
                $tur = radius_find_recharge($username);
                if ($tur) {
                    process_radiust_rest($tur);
                } else {
                    show_radius_result(['Reply-Message' => 'Voucher activation failed'], 401);
                }
            } else {
                show_radius_result(['Reply-Message' => 'Internet Plan Expired..'], 401);
            }
            break;
        case 'accounting':
            $username = _req('username');
            if (empty($username)) {
                show_radius_result(['Reply-Message' => 'Username empty'], 401);
                die();
            }
            header("HTTP/1.1 200 OK");

            $acctSessionId = _req('acctSessionId', _req('acctsessionid'));
            $acctStatusType = _req('acctStatusType', _req('acctstatustype'));
            // 32-bit octet counters wrap every 4 GiB; the NAS reports the number of wraps in the Gigawords attributes
            $acctInputOctets = intval(_req('acctInputOctets', _req('acctinputoctets', 0)))
                + intval(_req('acctInputGigawords', _req('acctinputgigawords', 0))) * 4294967296;
            $acctOutputOctets = intval(_req('acctOutputOctets', _req('acctoutputoctets', 0)))
                + intval(_req('acctOutputGigawords', _req('acctoutputgigawords', 0))) * 4294967296;
            $acctSessionTime = intval(_req('acctSessionTime', _req('acctsessiontime', 0)));
            $framedIPAddress = _req('framedIPAddress', _req('framedipaddress'));
            $nasIpAddress = _req('nasIpAddress', _req('nasipaddress'));
            $nasPortId = _req('nasPortId', _req('nasportid'));
            $nasPortType = _req('nasPortType', _req('nasporttype'));
            $macAddr = _req('macAddr', _req('macaddr'));
            $realm = _req('realm');

            if (empty($acctSessionId)) {
                show_radius_result(['Reply-Message' => 'Session ID empty'], 401);
                die();
            }

            // One statement for every accounting request, keyed on the unique (username, acctsessionid): a new session is
            // added, a known one updated (RadiusSessions::saveAccounting). Atomic, so a Start and the NAS's resend of it (or an
            // Interim arriving with it) can never both insert. Dates in PHP's timezone, as the cron and the dashboard compare them.
            $now = date('Y-m-d H:i:s');
            $packet = [
                'acctsessionid' => (string) $acctSessionId, 'username' => (string) $username, 'realm' => (string) $realm,
                'nasid' => (string) _req('nasid'), 'nasipaddress' => (string) $nasIpAddress, 'nasportid' => $nasPortId,
                'nasporttype' => $nasPortType, 'framedipaddress' => (string) $framedIPAddress, 'acctsessiontime' => $acctSessionTime,
                'acctinputoctets' => $acctInputOctets, 'acctoutputoctets' => $acctOutputOctets,
                // the status changes only for a known type; the start fields stay as first written
                'acctstatustype' => in_array($acctStatusType, ['Start', 'Stop', 'Interim-Update'], true) ? $acctStatusType : null,
                'macaddr' => (string) $macAddr, 'dateAdded' => $now, 'dateUpdated' => $now,
                // when it was received, to the microsecond: the order of packets that arrive within one second
                'receivedAt' => (int) round(microtime(true) * 1000000),
            ];
            try {
                RadiusSessions::saveAccounting($packet);
                show_radius_result(['Reply-Message' => 'Saved'], 200);
            } catch (Throwable $e) {
                // Not written: the packet is queued on disk (the cron writes it later, RadiusSessions::replayAccounting) and
                // the router is answered 200, since without an Accounting-Response it logs "no response" and resends, which
                // only adds load. Only a Stop that could not be queued either is refused, so the NAS sends it again: it
                // carries the session's final usage. A lost Start or Interim-Update is made up by the next update.
                $queued = RadiusSessions::queueAccounting($packet);
                radius_alert("Database save error on RADIUS accounting (" . ($queued ? 'queued for retry' : 'NOT queued') . ").\nUsername: {$username}\nSession ID: {$acctSessionId}\nError: " . $e->getMessage());
                if (!$queued && $packet['acctstatustype'] === 'Stop') {
                    show_radius_result(['Reply-Message' => 'Not saved'], 500);
                }
                show_radius_result(['Reply-Message' => $queued ? 'Queued' : 'Not saved'], 200);
            }
            break;
        case 'post-auth':
            // FreeRADIUS's rest module calls this after every login when post-auth is in its config: nothing to record here.
            // Answered "no content" (ok), never 401: a reject in post-auth can turn an accepted login into a rejected one.
            header("HTTP/1.1 204 No Content");
            die();
        default:
            show_radius_result(['Reply-Message' => 'Invalid Command : ' . $action], 401);
            break;
    }
} catch (Throwable $e) {
    radius_alert("System Error.\n" . $e->getMessage() . "\n" . $e->getTraceAsString());
    // accounting is answered either way (see above); a login that failed is refused
    // except a Stop that may not have been recorded: a failure status makes FreeRADIUS/the NAS send it again
    $unsavedStop = false;
    if ($action === 'accounting') {
        try {
            $type = (string) ($_REQUEST['acctStatusType'] ?? $_REQUEST['acctstatustype'] ?? '');
            $unsavedStop = strcasecmp(trim($type), 'Stop') === 0;
        } catch (Throwable $ignored) {
        }
    }
    show_radius_result(['Reply-Message' => 'Command Failed : ' . $action], $action === 'accounting' ? ($unsavedStop ? 500 : 200) : 401);
}

/**
 * An error while answering FreeRADIUS: written to the log, and told on Telegram at most once every 5 minutes, after the answer is
 * sent and within a short time limit (Message::sendTelegram keeps it short in a RADIUS request). A burst of errors, or a Telegram that is slow or
 * blocked, must never hold the requests FreeRADIUS and the routers are waiting for. Never throws.
 */
function radius_alert($text)
{
    global $CACHE_PATH;
    try {
        _log(mb_substr((string) $text, 0, 1000), 'RADIUS');
    } catch (Throwable $e) {
    }
    try {
        $mark = rtrim((string) $CACHE_PATH, '/\\') . DIRECTORY_SEPARATOR . 'radius_alert.last';
        $fh = @fopen($mark, 'c+');
        if (!$fh) {
            return;
        }
        $send = false;
        if (flock($fh, LOCK_EX | LOCK_NB)) {
            $last = (int) stream_get_contents($fh);
            if (time() - $last >= 300) {
                ftruncate($fh, 0);
                rewind($fh);
                fwrite($fh, (string) time());
                fflush($fh);
                $send = true;
            }
            flock($fh, LOCK_UN);
        }
        fclose($fh);
        if ($send) {
            // Not now: the answer goes out first. After the response is finished (fastcgi_finish_request) the Telegram call can
            // take its few seconds without holding FreeRADIUS (its rest module gives up after 4 seconds). Without FastCGI the
            // response cannot be released early, so only the time left of that window is spent (Message::sendTelegram
            // honours $GLOBALS['radius_alert_budget_ms']).
            $GLOBALS['radius_alert_text'] = mb_substr((string) $text, 0, 3000);
            if (empty($GLOBALS['radius_alert_registered'])) {
                $GLOBALS['radius_alert_registered'] = true;
                register_shutdown_function('radius_alert_deliver');
            }
        }
    } catch (Throwable $e) {
    }
}

/** Shutdown step of radius_alert(): sends the pending alert once the RADIUS answer is out. Never throws. */
function radius_alert_deliver()
{
    try {
        $text = (string) ($GLOBALS['radius_alert_text'] ?? '');
        if ($text === '') {
            return;
        }
        $GLOBALS['radius_alert_text'] = '';
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
            $GLOBALS['radius_alert_budget_ms'] = 3000;
        } else {
            @flush();
            $elapsed = microtime(true) - (float) ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
            $left = (int) floor((3.5 - $elapsed) * 1000); // FreeRADIUS gives up after 4 s
            if ($left < 500) {
                return; // no time left to send without holding the answer: it is in the log
            }
            $GLOBALS['radius_alert_budget_ms'] = $left;
        }
        Message::sendTelegram($text);
    } catch (Throwable $e) {
    }
}

function radius_credentials()
{
    return [
        'username' => _req('username'),
        'password' => _req('password'),
        'chap' => _req('CHAPassword'),
        'challenge' => _req('CHAPchallenge'),
    ];
}

function radius_reject($message = 'Username or Password is wrong')
{
    show_radius_result(['Reply-Message' => $message], 401);
}

/**
 * The recharge a RADIUS login is checked against. A username can have more than one row (an old expired plan next to the
 * new one, for example when two customer records share a phone number): the live one must win, otherwise a customer
 * with an active plan is told it has expired.
 */
function radius_find_recharge($username)
{
    return ORM::for_table('tbl_user_recharges')
        ->where_raw("username = ? AND BINARY username = ?", [$username, $username]) // the index, then the exact match
        ->where('routers', 'radius')
        ->order_by_expr("status = 'on' DESC")
        ->order_by_desc('id')
        ->find_one();
}

/** Does the PAP password or CHAP response in the request match this stored password? Never true for an empty one. */
function radius_password_ok($real, $cred)
{
    $real = (string) $real;
    if ($real === '') {
        return false;
    }
    if ($cred['chap'] !== '') {
        return Password::chap_verify($real, $cred['chap'], $cred['challenge']);
    }
    return $cred['password'] !== '' && hash_equals($real, (string) $cred['password']);
}

/** A voucher login uses the code as its own password (or sends none) */
function radius_voucher_credentials($code, $cred)
{
    if ($cred['chap'] !== '') {
        return Password::chap_verify($code, $cred['chap'], $cred['challenge'])
            || Password::chap_verify('', $cred['chap'], $cred['challenge']);
    }
    return $cred['password'] === '' || hash_equals($code, (string) $cred['password']);
}

/** Active customer by username, then by PPPoE username (cached when the RADIUS cache is on) */
function radius_find_customer($username)
{
    $useCache = class_exists('RedisCache') && RedisCache::isRadiusCacheEnabled();
    foreach (['username' => 'rad:cust_auth:', 'pppoe_username' => 'rad:cust_pppoe:'] as $column => $prefix) {
        $load = function () use ($column, $username) {
            $row = ORM::for_table('tbl_customers')->where_raw("$column = ? AND BINARY $column = ?", [$username, $username])->where('status', 'Active')->find_one();
            return $row ? $row->as_array() : null;
        };
        $c = $useCache ? RedisCache::remember($prefix . md5($username), RedisCache::getRadiusTTL(), $load) : $load();
        if ($c) {
            return $c;
        }
    }
    return null;
}

/**
 * Decide who is logging in. Returns ['voucher'=>bool, 'username'=>string (the customer's main username or the
 * voucher code), 'voucher_row'=>ORM|null] or answers 401 and stops. A customer account is only ever accepted
 * with its own password, an inactive account never falls back to being treated as a voucher.
 */
function radius_identify($cred)
{
    $username = $cred['username'];
    if ($username === '') {
        radius_reject('Login invalid......');
    }
    $c = radius_find_customer($username);
    if ($c) {
        if (radius_password_ok($c['password'], $cred) || radius_password_ok($c['pppoe_password'] ?? '', $cred)) {
            return ['voucher' => false, 'username' => $c['username'], 'voucher_row' => null];
        }
        radius_reject();
    }
    $exists = ORM::for_table('tbl_customers')
        ->where_raw("((username = ? AND BINARY username = ?) OR (pppoe_username = ? AND BINARY pppoe_username = ?))", [$username, $username, $username, $username])
        ->count();
    if ($exists) {
        radius_reject('Your account is not active, please contact customer support.');
    }
    $code = Text::alphanumeric($username, "-_.,");
    if ($code !== $username || !radius_voucher_credentials($code, $cred)) {
        radius_reject();
    }
    $v = ORM::for_table('tbl_voucher')->where_raw("code = ? AND BINARY code = ?", [$code, $code])->where('routers', 'radius')->find_one();
    if (!$v) {
        radius_reject('Invalid Voucher..');
    }
    return ['voucher' => true, 'username' => $code, 'voucher_row' => $v];
}

/** Bytes and seconds used by this username inside the current recharge window */
function radius_usage($tur)
{
    $startAt = $tur['recharged_on'] . ' ' . $tur['recharged_time'];
    $endAt = $tur['expiration'] . ' ' . $tur['time'];
    $row = ORM::for_table('rad_acct')
        ->select_expr('COALESCE(SUM(acctinputoctets + acctoutputoctets), 0)', 'used_bytes')
        ->select_expr('COALESCE(SUM(acctsessiontime), 0)', 'used_seconds')
        ->where('username', $tur['username'])
        ->where_raw('dateAdded >= ? AND dateAdded <= ?', [$startAt, $endAt])
        ->find_one();
    return [$row ? (int) $row['used_bytes'] : 0, $row ? (int) $row['used_seconds'] : 0];
}

function process_radiust_rest($tur)
{
    global $config;
    if (class_exists('RedisCache') && RedisCache::isRadiusCacheEnabled()) {
        $ttl = RedisCache::getRadiusTTL();
        $plan = RedisCache::remember('rad:plan:' . (int)$tur['plan_id'], $ttl, function () use ($tur) {
            $p = ORM::for_table('tbl_plans')->where('id', $tur['plan_id'])->find_one();
            return $p ? $p->as_array() : null;
        });
        $bw = ($plan && !empty($plan['id_bw'])) ? RedisCache::remember('rad:bw:' . (int)$plan['id_bw'], $ttl, function () use ($plan) {
            $b = ORM::for_table("tbl_bandwidth")->find_one($plan['id_bw']);
            return $b ? $b->as_array() : null;
        }) : null;
    } else {
        $plan = ORM::for_table('tbl_plans')->where('id', $tur['plan_id'])->find_one();
        $bw = $plan ? ORM::for_table("tbl_bandwidth")->find_one($plan['id_bw']) : null;
    }
    if (!$plan || !$bw) {
        _log("User {$tur['username']} has no valid internet plan or bandwidth. Plan ID: {$tur['plan_id']}, Expiration: {$tur['expiration']} {$tur['time']}", 'RADIUS');
        radius_alert(
            "User {$tur['username']} has no valid internet plan. Please check immediately.\n" .
            "Plan ID: {$tur['plan_id']}, Expiration: {$tur['expiration']} {$tur['time']}, \n" .
            "Error at Radius-Rest authorize."
        );
        show_radius_result(['Reply-Message' => 'Invalid Internet Plan, Please contact customer support.'], 401);
    }
    // Count User Onlines
    $USRon = ORM::for_table('rad_acct')
        ->where_raw(
            "username = ? AND BINARY username = ? AND (acctStatusType = 'Start' OR acctStatusType = 'Interim-Update' OR acctStatusType IS NULL)",
            [$tur['username'], $tur['username']]
        )
        ->find_array();

    // devices that already have a live session: the caller is identified by its MAC (Calling-Station-Id), which
    // FreeRADIUS sends on authorize, and not by anything the user can pick freely
    $normMac = function ($m) {
        return strtolower(str_replace('-', ':', trim((string) $m)));
    };
    $callerMac = $normMac(_req('macAddr'));
    $liveMacs = array_map($normMac, array_column($USRon, 'macaddr'));

    // Check if user reached shared_users limit, Hotspot only, and this device does not already have a session
    if ($plan['type'] == 'Hotspot') {
        if ($tur['status'] == 'on') {
            if (count($USRon) >= (int) $plan['shared_users'] && ($callerMac === '' || !in_array($callerMac, $liveMacs, true))) {
                if ((int) $plan['shared_users'] == 1) {
                    show_radius_result(['Reply-Message' => 'Your previous session is still active, Kindly disconnect it first then try again, or wait for 5 minutes. (If you are not the one who logged in, please contact customer support.)'], 401);
                } else {
                    show_radius_result(['Reply-Message' => 'You have reached your shared device limit, Kindly disconnect other devices and try again.'], 401);
                }
            }
        } else {
            show_radius_result(['Reply-Message' => 'Your internet Voucher/Plan has been expired. For more info please contact customer support.'], 401);
        }
    }

    if ($bw['rate_down_unit'] == 'Kbps') {
        $unitdown = 'K';
    } else {
        $unitdown = 'M';
    }
    if ($bw['rate_up_unit'] == 'Kbps') {
        $unitup = 'K';
    } else {
        $unitup = 'M';
    }
    $rate = $bw['rate_up'] . $unitup . "/" . $bw['rate_down'] . $unitdown;
    $rates = explode('/', $rate);

    if (!empty(trim($bw['burst']))) {
        $ratos = $rate . ' ' . $bw['burst'];
    } else {
        $ratos = $rates[0] . '/' . $rates[1];
    }

    $attrs = [];
    $timeexp = strtotime($tur['expiration'] . ' ' . $tur['time']);
    $attrs['Simultaneous-Use'] = $plan['shared_users'];
    $attrs['reply:Mikrotik-Wireless-Comment'] = $plan['name_plan'] . ' | ' . $tur['expiration'] . ' ' . $tur['time'];
    $attrs['reply:Mikrotik-Rate-Limit'] = $ratos;
    $attrs['reply:WISPr-Bandwidth-Max-Up'] = str_replace('M', '000000', str_replace('K', '000', $rates[0]));
    $attrs['reply:WISPr-Bandwidth-Max-Down'] = str_replace('M', '000000', str_replace('K', '000', $rates[1]));
    $attrs['reply:expiration'] = date('d M Y H:i:s', $timeexp);
    $attrs['reply:WISPr-Session-Terminate-Time'] = date('Y-m-d', $timeexp) . 'T' . date('H:i:sP', $timeexp);

    if ($plan['type'] == 'PPPOE') {
        $attrs['reply:Framed-Pool'] = $plan['pool'];
    }

    if ($plan['typebp'] == "Limited") {
        // usage inside this recharge's window only
        list($usedBytes, $usedSeconds) = radius_usage($tur);

        if ($plan['limit_type'] == "Data_Limit" || $plan['limit_type'] == "Both_Limit") {
            $remainingBytes = Text::convertDataUnit($plan['data_limit'], $plan['data_unit']) - $usedBytes;
            if ($remainingBytes <= 0) {
                show_radius_result(['Reply-Message' => 'You have exhausted your data limit.'], 401);
            }
            // Tell Mikrotik remaining bytes so it can auto-cut session. The attribute is 32 bit: the part above
            // 4 GiB goes in the Gigawords attribute.
            $remainingBytes = max(1, (int) $remainingBytes);
            if ($remainingBytes >= 4294967296) {
                $attrs['reply:Mikrotik-Total-Limit'] = $remainingBytes % 4294967296;
                $attrs['reply:Mikrotik-Total-Limit-Gigawords'] = intdiv($remainingBytes, 4294967296);
            } else {
                $attrs['reply:Mikrotik-Total-Limit'] = $remainingBytes;
            }
        }

        if ($plan['limit_type'] == "Time_Limit" || $plan['limit_type'] == "Both_Limit") {
            if ($plan['time_unit'] == 'Hrs') {
                $limitSeconds = $plan['time_limit'] * 60 * 60;
            } else {
                $limitSeconds = $plan['time_limit'] * 60;
            }
            $remainingSeconds = $limitSeconds - $usedSeconds;
            if ($remainingSeconds <= 0) {
                show_radius_result(['Reply-Message' => 'You have exceeded your time limit.'], 401);
            }
            // Tell Mikrotik remaining seconds so it can auto-cut session
            $attrs['reply:Max-All-Session'] = max(1, $remainingSeconds);
        }
    }

    $attrs['Reply-Message'] = 'success';
    $result = array_merge([
        "control:Auth-Type" => "Accept"
    ], $attrs);
    show_radius_result($result, 200);
}

function show_radius_result($array, $code = 200)
{
    if ($code == 204) {
        header("HTTP/1.1 204 No Content");
        die();
    }
    http_response_code((int) $code);
    die(json_encode($array));
}
