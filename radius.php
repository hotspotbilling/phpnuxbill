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
            $tur = ORM::for_table('tbl_user_recharges')
                ->where_raw("BINARY username = ?", [$username])
                ->where('routers', 'radius')
                ->find_one();
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
                $tur = ORM::for_table('tbl_user_recharges')
                    ->where_raw("BINARY username = ?", [$username])
                    ->where('routers', 'radius')
                    ->find_one();
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

            // Find existing record by session ID and username (unique session)
            $d = ORM::for_table('rad_acct')
                ->where('acctsessionid', $acctSessionId)
                ->where('username', $username)
                ->find_one();

            if ($d) {
                // Update existing record - NAS continues the session
                $d->acctoutputoctets = $acctOutputOctets;
                $d->acctinputoctets = $acctInputOctets;
                $d->acctsessiontime = $acctSessionTime;
                $d->dateUpdated = date('Y-m-d H:i:s');
            } else {
                // Create new record only if no existing record found for this session ID
                $d = ORM::for_table('rad_acct')->create();
                $d->acctoutputoctets = $acctOutputOctets;
                $d->acctinputoctets = $acctInputOctets;
                $d->acctsessiontime = $acctSessionTime;
                $d->dateAdded = date('Y-m-d H:i:s');
                $d->dateUpdated = date('Y-m-d H:i:s');
                $d->acctsessionid = $acctSessionId;
                $d->username = $username;
                $d->realm = $realm;
                $d->nasipaddress = $nasIpAddress;
                $d->nasid = _req('nasid');
                $d->nasportid = $nasPortId;
                $d->nasporttype = $nasPortType;
                $d->macaddr = $macAddr;
            }

            $d->framedipaddress = $framedIPAddress;
            if (in_array($acctStatusType, ['Start', 'Stop', 'Interim-Update'], true)) {
                $d->acctstatustype = $acctStatusType;
            }

            try {
                $d->save();
                show_radius_result(['Reply-Message' => 'Saved'], 200);
            } catch (Exception $e) {
                // Avoid Mikrotik no-response due server error (500)
                sendTelegram(
                    "Database Save Error on RADIUS Accounting.\n" .
                    "Username: {$username}\n" .
                    "Session ID: {$acctSessionId}\n" .
                    "Error: " . $e->getMessage()
                );
                show_radius_result(['Reply-Message' => 'Database save failed'], 401);
            }
            break;
        default:
            show_radius_result(['Reply-Message' => 'Invalid Command : ' . $action], 401);
            break;
    }
} catch (Throwable $e) {
    Message::sendTelegram(
        "System Error.\n" .
        $e->getMessage() . "\n" .
        $e->getTraceAsString()
    );
    show_radius_result(['Reply-Message' => 'Command Failed : ' . $action], 401);
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
            $row = ORM::for_table('tbl_customers')->where_raw("BINARY $column = ?", [$username])->where('status', 'Active')->find_one();
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
        ->where_raw("(BINARY username = ? OR BINARY pppoe_username = ?)", [$username, $username])
        ->count();
    if ($exists) {
        radius_reject('Your account is not active, please contact customer support.');
    }
    $code = Text::alphanumeric($username, "-_.,");
    if ($code !== $username || !radius_voucher_credentials($code, $cred)) {
        radius_reject();
    }
    $v = ORM::for_table('tbl_voucher')->where_raw("BINARY code = ?", [$code])->where('routers', 'radius')->find_one();
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
        Message::sendTelegram(
            "User {$tur['username']} has no valid internet plan. Please check immediately.\n" .
            "Plan ID: {$tur['plan_id']}, Expiration: {$tur['expiration']} {$tur['time']}, \n" .
            "Error at Radius-Rest authorize."
        );
        show_radius_result(['Reply-Message' => 'Invalid Internet Plan, Please contact customer support.'], 401);
    }
    // Count User Onlines
    $USRon = ORM::for_table('rad_acct')
        ->where_raw(
            "BINARY username = ? AND (acctStatusType = 'Start' OR acctStatusType = 'Interim-Update' OR acctStatusType IS NULL)",
            [$tur['username']]
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
