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

$action = $_SERVER['HTTP_X_FREERADIUS_SECTION'];
if (empty($action)) {
    $action = _get('action');
}

$code = 200;

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
            $username = _req('username');
            $password = _req('password');
            $CHAPassword = _req('CHAPassword');
            $CHAPchallenge = _req('CHAPchallenge');
            $isCHAP = false;
            if (!empty($CHAPassword)) {
                $c = ORM::for_table('tbl_customers')->select('password')->select('pppoe_password')->whereRaw("BINARY username = '$username' AND status = 'Active'")->find_one();
                if ($c) {
                    if (Password::chap_verify($c['password'], $CHAPassword, $CHAPchallenge)) {
                        $password = $c['password'];
                        $isVoucher = false;
                        $isCHAP = true;
                    } else if (!empty($c['pppoe_password']) && Password::chap_verify($c['pppoe_password'], $CHAPassword, $CHAPchallenge)) {
                        $password = $c['pppoe_password'];
                        $isVoucher = false;
                        $isCHAP = true;
                    } else {
                        // check if voucher
                        if (Password::chap_verify($username, $CHAPassword, $CHAPchallenge)) {
                            $isVoucher = true;
                            $password = $username;
                        } else {
                            // no password is voucher
                            if (Password::chap_verify('', $CHAPassword, $CHAPchallenge)) {
                                $isVoucher = true;
                                $password = $username;
                            } else {
                                show_radius_result(['Reply-Message' => 'Username or Password is wrong'], 401);
                            }
                        }
                    }
                } else {
                    $c = ORM::for_table('tbl_customers')->select('password')->select('pppoe_password')->whereRaw("BINARY pppoe_username = '$username' AND status = 'Active'")->find_one();
                    if ($c) {
                        if (Password::chap_verify($c['password'], $CHAPassword, $CHAPchallenge)) {
                            $password = $c['password'];
                            $isVoucher = false;
                            $isCHAP = true;
                        } else if (!empty($c['pppoe_password']) && Password::chap_verify($c['pppoe_password'], $CHAPassword, $CHAPchallenge)) {
                            $password = $c['pppoe_password'];
                            $isVoucher = false;
                            $isCHAP = true;
                        } else {
                            // check if voucher
                            if (Password::chap_verify($username, $CHAPassword, $CHAPchallenge)) {
                                $isVoucher = true;
                                $password = $username;
                            } else {
                                // no password is voucher
                                if (Password::chap_verify('', $CHAPassword, $CHAPchallenge)) {
                                    $isVoucher = true;
                                    $password = $username;
                                } else {
                                    show_radius_result(['Reply-Message' => 'Username or Password is wrong'], 401);
                                }
                            }
                        }
                    }
                }
            } else {
                if (!empty($username) && empty($password)) {
                    // Voucher with empty password
                    $isVoucher = true;
                    $password = $username;
                } else if (empty($username) || empty($password)) {
                    show_radius_result([
                        "control:Auth-Type" => "Reject",
                        "reply:Reply-Message" => 'Login invalid......'
                    ], 401);
                }
            }
            if ($username == $password) {
                $username = Text::alphanumeric($username, "-_.,");
                $d = ORM::for_table('tbl_voucher')->whereRaw("BINARY code = '$username'")->find_one();
            } else {
                $d = ORM::for_table('tbl_customers')->whereRaw("BINARY username = '$username' AND status = 'Active'")->find_one();
                if ($d['password'] != $password) {
                    if ($d['pppoe_password'] != $password) {
                        unset($d);
                    }
                }
            }
            if ($d) {
                header("HTTP/1.1 204 No Content");
                die();
            } else {
                show_radius_result([
                    "control:Auth-Type" => "Reject",
                    "reply:Reply-Message" => 'Login invalid......'
                ], 401);
            }
            break;
        case 'authorize':
            $username = _req('username');
            $password = _req('password');
            $isVoucher = ($username == $password);
            $CHAPassword = _req('CHAPassword');
            $CHAPchallenge = _req('CHAPchallenge');
            $isCHAP = false;
            if (!empty($CHAPassword)) {
                $c = ORM::for_table('tbl_customers')->select('password')->select('pppoe_password')->whereRaw("BINARY username = '$username' AND status = 'Active'")->find_one();
                if ($c) {
                    if (Password::chap_verify($c['password'], $CHAPassword, $CHAPchallenge)) {
                        $password = $c['password'];
                        $isVoucher = false;
                        $isCHAP = true;
                    } else if (!empty($c['pppoe_password']) && Password::chap_verify($c['pppoe_password'], $CHAPassword, $CHAPchallenge)) {
                        $password = $c['pppoe_password'];
                        $isVoucher = false;
                        $isCHAP = true;
                    } else {
                        // check if voucher
                        if (Password::chap_verify($username, $CHAPassword, $CHAPchallenge)) {
                            $isVoucher = true;
                            $password = $username;
                        } else {
                            // no password is voucher
                            if (Password::chap_verify('', $CHAPassword, $CHAPchallenge)) {
                                $isVoucher = true;
                                $password = $username;
                            } else {
                                show_radius_result(['Reply-Message' => 'Username or Password is wrong'], 401);
                            }
                        }
                    }
                } else {
                    $c = ORM::for_table('tbl_customers')->select('password')->select('username')->select('pppoe_password')->whereRaw("BINARY pppoe_username = '$username' AND status = 'Active'")->find_one();
                    if ($c) {
                        if (Password::chap_verify($c['password'], $CHAPassword, $CHAPchallenge)) {
                            $password = $c['password'];
                            $username = $c['username'];
                            $isVoucher = false;
                            $isCHAP = true;
                        } else if (!empty($c['pppoe_password']) && Password::chap_verify($c['pppoe_password'], $CHAPassword, $CHAPchallenge)) {
                            $password = $c['pppoe_password'];
                            $username = $c['username'];
                            $isVoucher = false;
                            $isCHAP = true;
                        } else {
                            // check if voucher
                            if (Password::chap_verify($username, $CHAPassword, $CHAPchallenge)) {
                                $isVoucher = true;
                                $password = $username;
                            } else {
                                // no password is voucher
                                if (Password::chap_verify('', $CHAPassword, $CHAPchallenge)) {
                                    $isVoucher = true;
                                    $password = $username;
                                } else {
                                    show_radius_result(['Reply-Message' => 'Username or Password is wrong'], 401);
                                }
                            }
                        }
                    }
                }
            } else {
                if (!empty($username) && empty($password)) {
                    // Voucher with empty password
                    $isVoucher = true;
                    $password = $username;
                } else if (empty($username) || empty($password)) {
                    show_radius_result([
                        "control:Auth-Type" => "Reject",
                        "reply:Reply-Message" => 'Login invalid......'
                    ], 401);
                }
            }
            $tur = ORM::for_table('tbl_user_recharges')->whereRaw("BINARY username = '$username'")->find_one();
            if (!$tur) {
                // if check if pppoe_username
                $c = ORM::for_table('tbl_customers')->select('username')->select('pppoe_password')->whereRaw("BINARY pppoe_username = '$username'")->find_one();
                if ($c) {
                    $username = $c['username'];
                    $tur = ORM::for_table('tbl_user_recharges')->whereRaw("BINARY username = '$username'")->find_one();
                }
            }
            if ($tur) {
                if (!$isVoucher && !$isCHAP) {
                    $d = ORM::for_table('tbl_customers')->select('password')->select('pppoe_password')->whereRaw("BINARY username = '$username' AND status = 'Active'")->find_one();
                    if ($d) {
                        if ($d['password'] != $password) {
                            if ($d['pppoe_password'] != $password) {
                                show_radius_result(['Reply-Message' => 'Username or Password is wrong'], 401);
                            }
                        }
                    } else {
                        $d = ORM::for_table('tbl_customers')->select('password')->select('pppoe_password')->whereRaw("BINARY pppoe_username = '$username' AND status = 'Active'")->find_one();
                        if ($d) {
                            if ($d['password'] != $password) {
                                if ($d['pppoe_password'] != $password) {
                                    show_radius_result(['Reply-Message' => 'Username or Password is wrong'], 401);
                                }
                            }
                        }
                    }
                }
                process_radiust_rest($tur, $code);
            } else {
                if ($isVoucher) {
                    $username = Text::alphanumeric($username, "-_.,");
                    $v = ORM::for_table('tbl_voucher')->whereRaw("BINARY code = '$username' AND routers = 'radius'")->find_one();
                    if ($v) {
                        if ($v['status'] == 0) {
                            if (Package::rechargeUser(0, $v['routers'], $v['id_plan'], "Voucher", $username)) {
                                $v->status = "1";
                                $v->used_date = date('Y-m-d H:i:s');
                                $v->save();
                                $tur = ORM::for_table('tbl_user_recharges')->whereRaw("BINARY username = '$username'")->find_one();
                                if ($tur) {
                                    process_radiust_rest($tur, $code);
                                } else {
                                    show_radius_result(['Reply-Message' => 'Voucher activation failed'], 401);
                                }
                            } else {
                                show_radius_result(['Reply-Message' => 'Voucher activation failed.'], 401);
                            }
                        } else {
                            show_radius_result(['Reply-Message' => 'Voucher Expired...'], 401);
                        }
                    } else {
                        show_radius_result(['Reply-Message' => 'Invalid Voucher..'], 401);
                    }
                } else {
                    show_radius_result(['Reply-Message' => 'Internet Plan Expired..'], 401);
                }
            }
            break;
        case 'accounting':
            $username = _req('username');
            if (empty($username)) {
                show_radius_result([
                    "control:Auth-Type" => "Reject",
                    "reply:Reply-Message" => 'Username empty'
                ], 200);
                die();
            }
            header("HTTP/1.1 200 ok");
            $d = ORM::for_table('rad_acct')
                ->whereRaw("BINARY username = '$username' AND macaddr = '" . _post('macAddr') . "' AND nasid = '" . _post('nasid') . "'")
                ->findOne();
            if (!$d) {
                $d = ORM::for_table('rad_acct')->create();
            }
            $acctOutputOctets = _post('acctOutputOctets', 0);
            $acctInputOctets = _post('acctInputOctets', 0);
            if ($acctOutputOctets !== false && $acctInputOctets !== false) {
                $d->acctOutputOctets += intval($acctOutputOctets);
                $d->acctInputOctets += intval($acctInputOctets);
            } else {
                $d->acctOutputOctets = 0;
                $d->acctInputOctets = 0;
            }
            $d->acctsessionid = _post('acctSessionId');
            $d->username = $username;
            $d->realm = _post('realm');
            $d->nasipaddress = _post('nasIpAddress');
            $d->acctsessiontime = intval(_post('acctSessionTime'));
            $d->nasid = _post('nasid');
            $d->nasportid = _post('nasPortId');
            $d->nasporttype = _post('nasPortType');
            $d->framedipaddress = _post('framedIPAddress');
            if (in_array(_post('acctStatusType'), ['Start', 'Stop'])) {
                $d->acctstatustype = _post('acctStatusType');
            }
            $d->macaddr = _post('macAddr');
            $d->dateAdded = date('Y-m-d H:i:s');
            // pastikan data akunting yang disimpan memang customer aktif phpnuxbill
            $tur = ORM::for_table('tbl_user_recharges')->whereRaw("BINARY username = '$username' AND `status` = 'on' AND (`routers` = 'radius' OR `routers` = 'Radius')")->find_one();
            if (!$tur) {
                // check if pppoe_username
                $c = ORM::for_table('tbl_customers')->select('username')->whereRaw("BINARY pppoe_username = '$username'")->find_one();
                if ($c) {
                    $username = $c['username'];
                    $tur = ORM::for_table('tbl_user_recharges')->whereRaw("BINARY username = '$username'")->find_one();
                }
            }
            if ($tur) {
                $d->save();
                if (_post('acctStatusType') == 'Start') {
                    $plan = ORM::for_table('tbl_plans')->where('id', $tur['plan_id'])->find_one();
                    if ($plan['limit_type'] == "Data_Limit" || $plan['limit_type'] == "Both_Limit") {
                        $totalUsage = $d['acctOutputOctets'] + $d['acctInputOctets'];
                        $attrs['reply:Mikrotik-Total-Limit'] = Text::convertDataUnit($plan['data_limit'], $plan['data_unit']) - $totalUsage;
                        if ($attrs['reply:Mikrotik-Total-Limit'] < 0) {
                            $attrs['reply:Mikrotik-Total-Limit'] = 0;
                            show_radius_result(["control:Auth-Type" => "Reject", 'Reply-Message' => 'You have exceeded your data limit.'], 401);
                        }
                    }
                }
                process_radiust_rest($tur, 200);
            }
            show_radius_result([
                "control:Auth-Type" => "Accept",
                "reply:Reply-Message" => 'Saved'
            ], 200);
            break;
    }
    die();
} catch (Throwable $e) {
    Message::sendTelegram(
        "System Error.\n" .
            $e->getMessage() . "\n" .
            $e->getTraceAsString()
    );
    show_radius_result(['Reply-Message' => 'Command Failed : ' . $action], 401);
} catch (Exception $e) {
    Message::sendTelegram(
        "System Error.\n" .
            $e->getMessage() . "\n" .
            $e->getTraceAsString()
    );
    show_radius_result(['Reply-Message' => 'Command Failed : ' . $action], 401);
}
show_radius_result(['Reply-Message' => 'Invalid Command : ' . $action], 401);

function process_radiust_rest($tur, $code)
{
    global $config;
    $plan = ORM::for_table('tbl_plans')->where('id', $tur['plan_id'])->find_one();
    $bw = ORM::for_table("tbl_bandwidth")->find_one($plan['id_bw']);
    // Count User Onlines across both tables
    $USRonAll = get_online_sessions_bi($tur['username']);
    $ips = array_column($USRonAll, 'ip');

    // Check if user reached shared_users limit, Hotspot only, and current IP is not already active
    if ($plan['type'] == 'Hotspot' && count($USRonAll) >= (int)$plan['shared_users'] && !in_array(_post('framedIPAddress'), $ips, true)) {
        if ((int)$plan['shared_users'] == 1) {
            // We'll stop the newest existing session to favor current login
            usort($USRonAll, fn($a, $b) => $b['date'] <=> $a['date']);
            $toStop = $USRonAll[0];
            stop_session_bi($toStop, intval(_post('acctSessionTime')));
            _log("Terminated previous session for {$tur['username']} on {$toStop['source']} IP {$toStop['ip']}", 'RADIUS');
        } else {
            // Multiple shared users: if exhausted, stop the oldest session and allow current
            usort($USRonAll, fn($a, $b) => $a['date'] <=> $b['date']); // oldest first
            $toStop = $USRonAll[0];
            stop_session_bi($toStop, intval(_post('acctSessionTime')));
            _log("Terminated oldest session for {$tur['username']} on {$toStop['source']} IP {$toStop['ip']}", 'RADIUS');
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
    $attrs['reply:Reply-Message'] = 'success';
    $attrs['Simultaneous-Use'] = $plan['shared_users'];
    $attrs['reply:Mikrotik-Wireless-Comment'] = $plan['name_plan'] . ' | ' . $tur['expiration'] . ' ' . $tur['time'];

    $attrs['reply:Ascend-Data-Rate'] = str_replace('M', '000000', str_replace('K', '000', $rates[1]));
    $attrs['reply:Ascend-Xmit-Rate'] = str_replace('M', '000000', str_replace('K', '000', $rates[0]));
    $attrs['reply:Mikrotik-Rate-Limit'] = $ratos;
    $attrs['reply:WISPr-Bandwidth-Max-Up'] = str_replace('M', '000000', str_replace('K', '000', $rates[0]));
    $attrs['reply:WISPr-Bandwidth-Max-Down'] = str_replace('M', '000000', str_replace('K', '000', $rates[1]));
    $attrs['reply:expiration'] = date('d M Y H:i:s', $timeexp);
    $attrs['reply:WISPr-Session-Terminate-Time'] = date('Y-m-d', $timeexp) . 'T' . date('H:i:sP', $timeexp);

    if ($plan['type'] == 'PPPOE') {
        $attrs['reply:Framed-Pool'] = $plan['pool'];
    }

    if ($plan['typebp'] == "Limited") {
        if ($plan['limit_type'] == "Data_Limit" || $plan['limit_type'] == "Both_Limit") {
            // Usage fallback: prefer REST active usage; if empty, use MySQL active usage
            $totalUsage = 0;

            $restActive = ORM::for_table('rad_acct')
                ->whereRaw("BINARY username = '" . addslashes($tur['username']) . "'")
                ->where('acctstatustype', 'Start')
                ->find_array();

            if (!empty($restActive)) {
                foreach ($restActive as $r) {
                    $totalUsage += intval($r['acctOutputOctets']) + intval($r['acctInputOctets']);
                }
            } else {
                $mysqlActive = ORM::for_table('radacct')
                    ->whereRaw("BINARY username = '" . addslashes($tur['username']) . "' AND acctstoptime IS NULL")
                    ->find_array();
                foreach ($mysqlActive as $m) {
                    $totalUsage += intval($m['acctoutputoctets']) + intval($m['acctinputoctets']);
                }
            }

            $attrs['reply:Mikrotik-Total-Limit'] = Text::convertDataUnit($plan['data_limit'], $plan['data_unit']) - $totalUsage;
            if ($attrs['reply:Mikrotik-Total-Limit'] < 0) {
                $attrs['reply:Mikrotik-Total-Limit'] = 0;
                show_radius_result(["control:Auth-Type" => "Reject", 'Reply-Message' => 'You have exceeded your data limit.'], 401);
            }
        }

        if ($plan['limit_type'] == "Time_Limit") {
            if ($plan['time_unit'] == 'Hrs')
                $timelimit = $plan['time_limit'] * 60 * 60;
            else
                $timelimit = $plan['time_limit'] * 60;
            $attrs['reply:Max-All-Session'] = $timelimit;
            $attrs['reply:Expire-After'] = $timelimit;
        } else if ($plan['limit_type'] == "Data_Limit") {
            if ($plan['data_unit'] == 'GB')
                $datalimit = $plan['data_limit'] . "000000000";
            else
                $datalimit = $plan['data_limit'] . "000000";
            $attrs['reply:Max-Data'] = $datalimit;
            $attrs['reply:Mikrotik-Recv-Limit-Gigawords'] = $datalimit;
            $attrs['reply:Mikrotik-Xmit-Limit-Gigawords'] = $datalimit;
        } else if ($plan['limit_type'] == "Both_Limit") {
            if ($plan['time_unit'] == 'Hrs')
                $timelimit = $plan['time_limit'] * 60 * 60;
            else
                $timelimit = $plan['time_limit'] * 60;
            if ($plan['data_unit'] == 'GB')
                $datalimit = $plan['data_limit'] . "000000000";
            else
                $datalimit = $plan['data_limit'] . "000000";
            $attrs['reply:Max-All-Session'] = $timelimit;
            $attrs['reply:Max-Data'] = $datalimit;
            $attrs['reply:Mikrotik-Recv-Limit-Gigawords'] = $datalimit;
            $attrs['reply:Mikrotik-Xmit-Limit-Gigawords'] = $datalimit;
        }
    }

    $result = array_merge([
        "control:Auth-Type" => "Accept",
        "reply" =>  ["Reply-Message" => ['value' => 'success']]
    ], $attrs);
    show_radius_result($result, $code);
}

function show_radius_result($array, $code = 200)
{
    if ($code == 401) {
        header("HTTP/1.1 401 Unauthorized");
    } else if ($code == 200) {
        header("HTTP/1.1 200 OK");
    } else if ($code == 204) {
        header("HTTP/1.1 204 No Content");
        die();
    }
    die(json_encode($array));
}


/**
 * Collect active sessions from both REST (rad_acct) and MySQL RADIUS (radacct)
 * Returns unified array: [ ['source'=>'rest'|'mysql', 'ip'=>'x.x.x.x', 'date'=>timestamp, 'username'=>'...', 'id'=>int|null] ]
 */
function get_online_sessions_bi($username)
{
    $sessions = [];

    // REST sessions (rad_acct): active if acctStatusType = 'Start'
    $rest = ORM::for_table('rad_acct')
        ->whereRaw("BINARY username = '$username' AND acctStatusType = 'Start'")
        ->find_array();
    foreach ($rest as $r) {
        $sessions[] = [
            'source'   => 'rest',
            'ip'       => $r['framedipaddress'] ?? '',
            'date'     => strtotime($r['dateAdded'] ?? 'now'),
            'username' => $username,
            'id'       => isset($r['id']) ? (int)$r['id'] : null
        ];
    }

    // MySQL RADIUS sessions (radacct): active if acctstoptime IS NULL
    $sql = ORM::for_table('radacct')
        ->whereRaw("BINARY username = '$username' AND acctstoptime IS NULL")
        ->find_array();
    foreach ($sql as $s) {
        // prefer acctupdatetime, fallback to acctstarttime
        $ts = !empty($s['acctupdatetime']) ? strtotime($s['acctupdatetime']) : (!empty($s['acctstarttime']) ? strtotime($s['acctstarttime']) : time());
        $sessions[] = [
            'source'   => 'mysql',
            'ip'       => $s['framedipaddress'] ?? '',
            'date'     => $ts,
            'username' => $username,
            'id'       => isset($s['radacctid']) ? (int)$s['radacctid'] : null // standard PK in radacct
        ];
    }

    // Remove empty-IP entries just for IP-based comparison
    return array_values(array_filter($sessions, fn($x) => !empty($x['ip'])));
}

/**
 * Stop an active session in its source table
 */

function stop_session_bi(array $sess, ?int $acctSessionTime = null)
{
    if ($sess['source'] === 'rest') {
        $q = ORM::for_table('rad_acct')
            ->whereRaw("BINARY username = '" . addslashes($sess['username']) . "' AND framedipaddress = '" . addslashes($sess['ip']) . "' AND acctstatustype = 'Start'")
            ->order_by_desc('dateAdded')
            ->find_one();
        if ($q) {
            $q->acctstatustype = 'Stop';
            if ($acctSessionTime !== null) $q->acctsessiontime = $acctSessionTime;
            $q->save();
        }
    } else {
        // mysql radacct
        // Prefer updating by primary key if present
        if (!empty($sess['id'])) {
            $q = ORM::for_table('radacct')->find_one($sess['id']);
        } else {
            $q = ORM::for_table('radacct')
                ->whereRaw("BINARY username = '" . addslashes($sess['username']) . "' AND framedipaddress = '" . addslashes($sess['ip']) . "' AND acctstoptime IS NULL")
                ->order_by_desc('acctupdatetime')
                ->find_one();
        }
        if ($q) {
            $q->acctstoptime = date('Y-m-d H:i:s');
            // Optional: mark terminate cause
            if (isset($q->acctterminatecause)) {
                $q->acctterminatecause = 'Admin-Reset';
            }
            if ($acctSessionTime !== null && isset($q->acctsessiontime)) {
                $q->acctsessiontime = $acctSessionTime;
            }
            $q->save();
        }
    }
}
