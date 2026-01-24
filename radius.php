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
                $c = ORM::for_table('tbl_customers')->select('password')->select('pppoe_password')->where_raw("BINARY username = ?", [$username])->where('status', 'Active')->find_one();
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
                    $c = ORM::for_table('tbl_customers')->select('password')->select('pppoe_password')->where_raw("BINARY pppoe_username = ?", [$username])->where('status', 'Active')->find_one();
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
                    show_radius_result(['Reply-Message' => 'Login invalid......'], 401);
                }
            }
            if ($username == $password) {
                $username = Text::alphanumeric($username, "-_.,");
                $d = ORM::for_table('tbl_voucher')->where_raw("BINARY code = ?", [$username])->find_one();
            } else {
                $d = ORM::for_table('tbl_customers')->where_raw("BINARY username = ?", [$username])->where('status', 'Active')->find_one();
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
                show_radius_result(['Reply-Message' => 'Login invalid......'], 401);
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
                $c = ORM::for_table('tbl_customers')->select('password')->select('pppoe_password')->where_raw("BINARY username = ?", [$username])->where('status', 'Active')->find_one();
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
                    $c = ORM::for_table('tbl_customers')->select('password')->select('username')->select('pppoe_password')->where_raw("BINARY pppoe_username = ?", [$username])->where('status', 'Active')->find_one();
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
                    show_radius_result(['Reply-Message' => 'Login invalid......'], 401);
                }
            }
            $tur = ORM::for_table('tbl_user_recharges')
                ->where_raw("BINARY username = ?", [$username])
                ->where('routers', 'radius')
                ->find_one();
            if (!$tur) {
                // if check if pppoe_username
                $c = ORM::for_table('tbl_customers')->select('username')->select('pppoe_password')->where_raw("BINARY pppoe_username = ?", [$username])->find_one();
                if ($c) {
                    $username = $c['username'];
                    $tur = ORM::for_table('tbl_user_recharges')
                        ->where_raw("BINARY username = ?", [$username])
                        ->where('routers', 'radius')
                        ->find_one();
                }
            }
            if ($tur) {
                if (!$isVoucher && !$isCHAP) {
                    $d = ORM::for_table('tbl_customers')->select('password')->select('pppoe_password')->where_raw("BINARY username = ?", [$username])->where('status', 'Active')->find_one();
                    if ($d) {
                        if ($d['password'] != $password) {
                            if ($d['pppoe_password'] != $password) {
                                show_radius_result(['Reply-Message' => 'Username or Password is wrong'], 401);
                            }
                        }
                    } else {
                        $d = ORM::for_table('tbl_customers')->select('password')->select('pppoe_password')->where_raw("BINARY pppoe_username = ?", [$username])->where('status', 'Active')->find_one();
                        if ($d) {
                            if ($d['password'] != $password) {
                                if ($d['pppoe_password'] != $password) {
                                    show_radius_result(['Reply-Message' => 'Username or Password is wrong'], 401);
                                }
                            }
                        }
                    }
                }

                $plan = ORM::for_table('tbl_plans')->where('id', $tur['plan_id'])->find_one();

                if (!$plan) {
                    _log("User {$tur['username']} has no valid internet plan. Plan ID: {$tur['plan_id']}, Expiration: {$tur['expiration']} {$tur['time']}", 'RADIUS');
                    sendTelegram(
                        "User {$tur['username']} has no valid internet plan. Please check immediately.\n" .
                        "Plan ID: {$tur['plan_id']}, Expiration: {$tur['expiration']} {$tur['time']}, \n" .
                        "Error at Radius-Rest authorize."
                    );
                    show_radius_result(['Reply-Message' => 'Invalid Internet Plan, Please contact customer support.'], 401);
                } else {
                    if ($plan['typebp'] == "Limited") {
                        // window for this recharge only
                        $startAt = $tur['recharged_on'] . ' ' . $tur['recharged_time'];
                        $endAt   = $tur['expiration'] . ' ' . $tur['time'];
                
                        // --- DATA LIMIT: use remaining bytes for THIS recharge only ---
                        if ($plan['limit_type'] == "Data_Limit" || $plan['limit_type'] == "Both_Limit") {
                            $raddacts = ORM::for_table('rad_acct')
                                ->where('username', $tur['username'])
                                ->where_raw('dateAdded >= ? AND dateAdded <= ?', [$startAt, $endAt])
                                ->find_many();
                
                            $totalUsage = 0;
                            foreach ($raddacts as $acct) {
                                $totalUsage += intval($acct['acctinputoctets']) + intval($acct['acctoutputoctets']);
                            }
                
                            $limitBytes = Text::convertDataUnit($plan['data_limit'], $plan['data_unit']);
                            $remainingBytes = $limitBytes - $totalUsage;
                
                            if ($remainingBytes <= 0) {
                                show_radius_result(['Reply-Message' => 'You have exhausted your data limit.'], 401);
                            }
                        }
                
                        // --- TIME LIMIT: use remaining seconds for THIS recharge only ---
                        if ($plan['limit_type'] == "Time_Limit" || $plan['limit_type'] == "Both_Limit") {
                            $timeActs = ORM::for_table('rad_acct')
                                ->where('username', $tur['username'])
                                ->where_raw('dateAdded >= ? AND dateAdded <= ?', [$startAt, $endAt])
                                ->find_many();
                
                            $usedSeconds = 0;
                            foreach ($timeActs as $acct) {
                                $usedSeconds += intval($acct['acctsessiontime']);
                            }
                
                            if ($plan['time_unit'] == 'Hrs') {
                                $limitSeconds = $plan['time_limit'] * 60 * 60;
                            } else {
                                $limitSeconds = $plan['time_limit'] * 60;
                            }
                
                            $remainingSeconds = $limitSeconds - $usedSeconds;
                
                            if ($remainingSeconds <= 0) {
                                show_radius_result(['Reply-Message' => 'You have exceeded your time limit.'], 401);
                            }
                        }
                    }
                }
                process_radiust_rest($tur, $code);
            } else {
                if ($isVoucher) {
                    $username = Text::alphanumeric($username, "-_.,");
                    $v = ORM::for_table('tbl_voucher')->where_raw("BINARY code = ?", [$username])->where('routers', 'radius')->find_one();
                    if ($v) {
                        if ($v['status'] == 0) {
                            if (Package::rechargeUser(0, $v['routers'], $v['id_plan'], "Voucher", $username)) {
                                $v->status = "1";
                                $v->used_date = date('Y-m-d H:i:s');
                                $v->save();
                                $tur = ORM::for_table('tbl_user_recharges')
                                    ->where_raw("BINARY username = ?", [$username])
                                    ->where('routers', 'radius')
                                    ->find_one();
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
                show_radius_result(['Reply-Message' => 'Username empty'], 401);
                die();
            }
            header("HTTP/1.1 200 ok");

            $acctSessionId = _post('acctSessionId');
            $acctStatusType = _post('acctStatusType');

            if (empty($acctSessionId)) {
                show_radius_result(['Reply-Message' => 'Session ID empty'], 401);
                die();
            }

            // Find existing record by session ID (unique per session)
            // NAS is source of truth - if NAS sends update for same session ID, update the record
            // even if cron marked it as 'Stop' (user was idle but session still active on NAS)
            $d = ORM::for_table('rad_acct')
                ->where('username', $username)
                ->where('acctsessionid', $acctSessionId)
                ->find_one();

            if ($d) {
                // Update existing record - NAS continues the session
                $d->acctoutputoctets = intval(_post('acctOutputOctets', 0));
                $d->acctinputoctets = intval(_post('acctInputOctets', 0));
                $d->acctsessiontime = intval(_post('acctSessionTime', 0));
                $d->dateUpdated = date('Y-m-d H:i:s');
            } else {
                // Create new record only if no existing record found for this session ID
                $d = ORM::for_table('rad_acct')->create();
                $d->acctoutputoctets = intval(_post('acctOutputOctets', 0));
                $d->acctinputoctets = intval(_post('acctInputOctets', 0));
                $d->acctsessiontime = intval(_post('acctSessionTime', 0));
                $d->dateAdded = date('Y-m-d H:i:s');
                $d->acctsessionid = $acctSessionId;
                $d->username = $username;
                $d->realm = _post('realm');
                $d->nasipaddress = _post('nasIpAddress');
                $d->nasid = _post('nasid');
                $d->nasportid = _post('nasPortId');
                $d->nasporttype = _post('nasPortType');
                $d->macaddr = _post('macAddr');
            }

            $d->framedipaddress = _post('framedIPAddress');
            if (in_array($acctStatusType, ['Start', 'Stop', 'Interim-Update'])) {
                $d->acctstatustype = $acctStatusType;
            }
            $d->save();
            show_radius_result(['Reply-Message' => 'Saved'], 200);
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
} catch (Exception $e) {
    Message::sendTelegram(
        "System Error.\n" .
        $e->getMessage() . "\n" .
        $e->getTraceAsString()
    );
    show_radius_result(['Reply-Message' => 'Command Failed : ' . $action], 401);
}

function process_radiust_rest($tur, $code)
{
    global $config;
    $plan = ORM::for_table('tbl_plans')->where('id', $tur['plan_id'])->find_one();
    $bw = ORM::for_table("tbl_bandwidth")->find_one($plan['id_bw']);
    // Count User Onlines
    $USRon = ORM::for_table('rad_acct')
        ->where_raw(
            "BINARY username = ? AND (acctStatusType = 'Start' OR acctStatusType = 'Interim-Update' OR acctStatusType IS NULL)",
            [$tur['username']]
        )
        ->find_array();

    // get all the IP
    $ips = array_column($USRon, 'framedipaddress');

    // Check if user reached shared_users limit, Hotspot only, and current IP is not already active
    if ($plan['type'] == 'Hotspot') {
        if ($tur['status'] == 'on') {
            if (count($USRon) >= (int) $plan['shared_users'] && !in_array(_post('framedIPAddress'), $ips, true)) {
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
        // window for this recharge only
        $startAt = $tur['recharged_on'] . ' ' . $tur['recharged_time'];
        $endAt   = $tur['expiration'] . ' ' . $tur['time'];
    
        // --- DATA LIMIT: use remaining bytes based on rad_acct for THIS recharge ---
        if ($plan['limit_type'] == "Data_Limit" || $plan['limit_type'] == "Both_Limit") {
            $raddacts = ORM::for_table('rad_acct')
                ->where('username', $tur['username'])
                ->where_raw('dateAdded >= ? AND dateAdded <= ?', [$startAt, $endAt])
                ->find_many();
    
            $totalUsage = 0;
            foreach ($raddacts as $acct) {
                $totalUsage += intval($acct['acctoutputoctets']) + intval($acct['acctinputoctets']);
            }
    
            $limitBytes = Text::convertDataUnit($plan['data_limit'], $plan['data_unit']);
            $remainingBytes = $limitBytes - $totalUsage;
    
            if ($remainingBytes <= 0) {
                show_radius_result(['Reply-Message' => 'You have exhausted your data limit..'], 401);
            }
    
            // Tell Mikrotik remaining bytes so it can auto‑cut session.
            $attrs['reply:Mikrotik-Total-Limit'] = max(1, $remainingBytes);
        }
    
        // --- TIME LIMIT: use remaining seconds based on rad_acct for THIS recharge ---
        if ($plan['limit_type'] == "Time_Limit" || $plan['limit_type'] == "Both_Limit") {
            $timeActs = ORM::for_table('rad_acct')
                ->where('username', $tur['username'])
                ->where_raw('dateAdded >= ? AND dateAdded <= ?', [$startAt, $endAt])
                ->find_many();
    
            $usedSeconds = 0;
            foreach ($timeActs as $acct) {
                $usedSeconds += intval($acct['acctsessiontime']);
            }
    
            if ($plan['time_unit'] == 'Hrs') {
                $limitSeconds = $plan['time_limit'] * 60 * 60;
            } else {
                $limitSeconds = $plan['time_limit'] * 60;
            }
    
            $remainingSeconds = $limitSeconds - $usedSeconds;
    
            if ($remainingSeconds <= 0) {
                show_radius_result(['Reply-Message' => 'You have exceeded your time limit.'], 401);
            }
    
            // Tell Mikrotik remaining seconds so it can auto‑cut session
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
