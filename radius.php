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
            if (!empty($CHAPassword)) {
                $c = ORM::for_table('tbl_customers')->select('password')->where('username', $username)->find_one();
                //if verified
                if (Password::chap_verify($c['password'], $CHAPassword, $CHAPchallenge)) {
                    $password = $c['password'];
                    $isVoucher = false;
                } else {
                    // check if voucher
                    if (Password::chap_verify($username, $CHAPassword, $CHAPchallenge)) {
                        $isVoucher = true;
                        $password = $username;
                    } else {
                        show_radius_result(['Reply-Message' => 'Username or Password is wrong'], 401);
                    }
                }
            } else {
                if (empty($username) || empty($password)) {
                    show_radius_result([
                        "control:Auth-Type" => "Reject",
                        "reply:Reply-Message" => 'Login invalid......'
                    ], 401);
                }
            }

            // Check if the user has exceeded their data limit
            $tur = ORM::for_table('tbl_user_recharges')->where('username', $username)->where('status', 'on')->find_one();
            if (!$tur) {
                show_radius_result(["control:Auth-Type" => "Accept", 'Reply-Message' => 'You don\'t have active plan'], 401);
                die();
            } else {
                $plan = ORM::for_table('tbl_plans')->where('id', $tur['plan_id'])->find_one();
                if ($plan['limit_type'] == "Data_Limit" || $plan['limit_type'] == "Both_Limit") {
                    $remaining_data = getTotalUsageAndRemainingData($username, Text::convertDataUnit($plan['data_limit'], $plan['data_unit']))->remaining_data;
                    if ($remaining_data <= 0) {
                        show_radius_result(["control:Auth-Type" => "Accept", 'Reply-Message' => 'You have exceeded your data limit.'], 401);
                    }
                }
            }


            if ($username == $password) {
                $d = ORM::for_table('tbl_voucher')->where('code', $username)->find_one();
            } else {
                $d = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
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
            if (!empty($CHAPassword)) {
                $c = ORM::for_table('tbl_customers')->select('password')->where('username', $username)->find_one();
                //if verified
                if (Password::chap_verify($c['password'], $CHAPassword, $CHAPchallenge)) {
                    $password = $c['password'];
                    $isVoucher = false;
                } else {
                    // check if voucher
                    if (Password::chap_verify($username, $CHAPassword, $CHAPchallenge)) {
                        $isVoucher = true;
                        $password = $username;
                    } else {
                        show_radius_result(['Reply-Message' => 'Username or Password is wrong'], 401);
                    }
                }
                //if ($response == $CHAPr) { echo 'ok betul 100'; }else{ echo 'salah'; } // untuk keperluan debug
            } else { //kalo chappassword kosong brrti eksekusi yg ini

                if (empty($username) || empty($password)) {
                    show_radius_result([
                        "control:Auth-Type" => "Reject",
                        "reply:Reply-Message" => 'Login invalid......'
                    ], 401);
                }
            }
            $tur = ORM::for_table('tbl_user_recharges')->where('username', $username)->find_one();
            if ($tur) {
                $plan = ORM::for_table('tbl_plans')->where('id', $tur['plan_id'])->find_one();
                if ($plan['limit_type'] == "Data_Limit" || $plan['limit_type'] == "Both_Limit") {
                    $remaining_data = getTotalUsageAndRemainingData($username, Text::convertDataUnit($plan['data_limit'], $plan['data_unit']))->remaining_data;
                    if ($remaining_data <= 0) {
                        show_radius_result(["control:Auth-Type" => "Accept", 'Reply-Message' => 'You have exceeded your data limit.'], 401);
                    }
                }
            }


            if ($tur) {
                if (!$isVoucher) {
                    $d = ORM::for_table('tbl_customers')->select('password')->where('username', $username)->find_one();
                    if ($d['password'] != $password) {
                        if ($d['pppoe_password'] != $password) {
                            show_radius_result(['Reply-Message' => 'Username or Password is wrong'], 401);
                        }
                    }
                }
                process_radiust_rest($tur, $code);
            } else {
                if ($isVoucher) {
                    $v = ORM::for_table('tbl_voucher')->where('code', $username)->where('routers', 'radius')->find_one();
                    if ($v) {
                        if ($v['status'] == 0) {
                            if (Package::rechargeUser(0, $v['routers'], $v['id_plan'], "Voucher", $username)) {
                                $v->status = "1";
                                $v->used_date = date('Y-m-d H:i:s');
                                $v->save();
                                $tur = ORM::for_table('tbl_user_recharges')->where('username', $username)->find_one();
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
                        show_radius_result(['Reply-Message' => 'Voucher Expired..'], 401);
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

            $acctStatusType = _post('acctStatusType');

            // Handle Interim-Update: update the existing session
            if ($acctStatusType === 'Interim-Update') {
                // Find the existing session record by acctsessionid
                $d = ORM::for_table('rad_acct')
                    ->where('username', $username)
                    ->where('acctsessionid', _post('acctSessionId'))
                    ->find_one();
                if ($d) {
                    $d->acctoutputoctets = floatval(_post('acctOutputOctets')) ?? 0;
                    $d->acctinputoctets = floatval(_post('acctInputOctets')) ?? 0;
                    $d->save();
                }
            } else {
                // For other acctStatusType values, create a new record
                $d = ORM::for_table('rad_acct')->create();
                sendTelegram(json_encode($_POST));
                $d->acctsessionid = _post('acctSessionId');
                $d->username = $username;
                $d->realm = _post('realm');
                $d->nasipaddress = _post('nasIpAddress');
                $d->nasid = _post('nasid');
                $d->nasportid = _post('nasPortId');
                $d->nasporttype = _post('nasPortType');
                $d->framedipaddress = _post('framedIPAddress');
                $d->acctstatustype = $acctStatusType;
                $d->acctoutputoctets = floatval(_post('acctOutputOctets')) ?? 0;
                $d->acctinputoctets = floatval(_post('acctInputOctets')) ?? 0;
                $d->macaddr = _post('macAddr');
                $d->dateAdded = date('Y-m-d H:i:s');
                $d->save();
            }

            // Check if the user has exceeded their data limit after logging the session data
            if ($d->acctstatustype) {
                $tur = ORM::for_table('tbl_user_recharges')->where('username', $username)->where('status', 'on')->where('routers', 'radius')->find_one();
                $plan = ORM::for_table('tbl_plans')->where('id', $tur['plan_id'])->find_one();
                if ($plan['limit_type'] == "Data_Limit" || $plan['limit_type'] == "Both_Limit") {
                    $remaining_data = getTotalUsageAndRemainingData($tur['username'], Text::convertDataUnit($plan['data_limit'], $plan['data_unit']))->remaining_data;
                    if ($remaining_data <= 0) {
                        // change the status of the user recharge to off if the user has exceeded their data limit
                        $tur->status = 'off';
                        $tur->save();
                        show_radius_result(["control:Auth-Type" => "Accept", 'Reply-Message' => 'You have exceeded your data limit.'], 401);
                    }
                    $attrs['reply:Mikrotik-Total-Limit'] = $remaining_data;
                }
            }

            show_radius_result([
                "control:Auth-Type" => "Accept",
                "reply:Reply-Message" => 'Saved'
            ], 200);
            break;

    }
    die();
} catch (Exception $e) {
    Message::sendTelegram(
        "System Error.\n" .
        $e->getMessage() . "\n" .
        $e->getTraceAsString()
    );
    show_radius_result(['Reply-Message' => 'Command Failed : ' . $action], 401);
} catch (Throwable $e) {
    Message::sendTelegram(
        "Throwable system Error.\n" .
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
            $remaining_data = getTotalUsageAndRemainingData($tur['username'], Text::convertDataUnit($plan['data_limit'], $plan['data_unit']))->remaining_data;
            sendTelegram("Remaining Data : " . $remaining_data);
            $attrs['reply:Mikrotik-Total-Limit'] = $remaining_data;
            if ($attrs['reply:Mikrotik-Total-Limit'] < 0) {
                $attrs['reply:Mikrotik-Total-Limit'] = 0;
                show_radius_result(["control:Auth-Type" => "Accept", 'Reply-Message' => 'You have exceeded your data limit.'], 401);
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
            $attrs['reply:Max-Data'] = $remaining_data;
            $attrs['reply:Mikrotik-Recv-Limit-Gigawords'] = $remaining_data;
            $attrs['reply:Mikrotik-Xmit-Limit-Gigawords'] = $remaining_data;
        } else if ($plan['limit_type'] == "Both_Limit") {
            if ($plan['time_unit'] == 'Hrs')
                $timelimit = $plan['time_limit'] * 60 * 60;
            else
                $timelimit = $plan['time_limit'] * 60;

            $attrs['reply:Max-All-Session'] = $timelimit;
            $attrs['reply:Max-Data'] = $remaining_data;
            $attrs['reply:Mikrotik-Recv-Limit-Gigawords'] = $remaining_data;
            $attrs['reply:Mikrotik-Xmit-Limit-Gigawords'] = $remaining_data;
        }
    }
    $result = array_merge([
        "control:Auth-Type" => "Accept",
        "reply" => ["Reply-Message" => ['value' => 'success']]
    ], $attrs);
    show_radius_result($result, $code);
}

function show_radius_result($array, $code = 200)
{
    if ($code == 401) {
        sendTelegram("Radius Error : " . json_encode($array));
        header("HTTP/1.1 401 Unauthorized");
    } else if ($code == 200) {
        sendTelegram(json_encode($array));
        header("HTTP/1.1 200 OK");
    } else if ($code == 204) {
        sendTelegram(json_encode($array));
        header("HTTP/1.1 204 No Content");
        die();
    }
    die(json_encode($array));
}

function getTotalUsageAndRemainingData($username, $dataLimit)
{
    // Retrieve the last recharge date and time from the user's active plan
    $lastRecharge = ORM::for_table('tbl_user_recharges')
        ->where('username', $username)
        ->where('status', 'on') // Assuming 'on' indicates an active plan
        ->find_one();
    if (!$lastRecharge) {
        return (object)[
            'total_usage' => 0,
            'remaining_data' => 0,
            'recharged_on' => null,
            'recharged_time' => null
        ];
    }
    // Combine recharged_on and recharged_time to form a timestamp
    $lastPlanStart = $lastRecharge->recharged_on . ' ' . $lastRecharge->recharged_time;
    // Calculate total usage (input + output) since the last plan activation
    $usage = ORM::for_table('rad_acct')
        ->where('username', $username)
        ->where_raw('dateAdded >= ?', [$lastPlanStart])
        ->select_expr('SUM(acctinputoctets + acctoutputoctets)', 'total_usage')
        ->find_one();

    $totalUsage = $usage->total_usage ?? 0;

    // Calculate remaining data based on the total data limit
    $remainingData = $dataLimit - $totalUsage;

    // Ensure that remaining data cannot go below zero
    if ($remainingData <= 0) {
        $remainingData = 0;
    }

    return (object)[
        'total_usage' => $totalUsage,
        'remaining_data' => $remainingData,
        'recharged_on' => $lastRecharge->recharged_on,
        'recharged_time' => $lastRecharge->recharged_time
    ];
}
