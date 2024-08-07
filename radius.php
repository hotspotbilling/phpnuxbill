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
                }else{
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
                }else{
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
            $d = ORM::for_table('rad_acct')
                ->where('username', $username)
                ->where('macaddr', _post('macAddr'))
                ->where('acctstatustype', _post('acctStatusType'))
                ->findOne();
            if (!$d) {
                $d = ORM::for_table('rad_acct')->create();
            }
            $acctOutputOctets = _post('acctOutputOctets');
            $acctInputOctets = _post('acctInputOctets');
            if ($acctOutputOctets !== false && $acctInputOctets !== false) {
                $d->acctOutputOctets += $acctOutputOctets;
                $d->acctInputOctets += $acctInputOctets;
            } else {
                $d->acctOutputOctets = 0;
                $d->acctInputOctets = 0;
            }
            $d->acctsessionid = _post('acctSessionId');
            $d->username = $username;
            $d->realm = _post('realm');
            $d->nasipaddress = _post('nasip');
            $d->nasid = _post('nasid');
            $d->nasportid = _post('nasPortId');
            $d->nasporttype = _post('nasPortType');
            $d->framedipaddress = _post('framedIPAddress');
            $d->acctstatustype = _post('acctStatusType');
            $d->macaddr = _post('macAddr');
            $d->dateAdded = date('Y-m-d H:i:s');
            $d->save();
            if($d->acctstatustype == 'Start'){
                $tur = ORM::for_table('tbl_user_recharges')->where('username', $username)->where('status', 'on')->where('routers', 'radius')->find_one();
                $plan = ORM::for_table('tbl_plans')->where('id', $tur['plan_id'])->find_one();
                if ($plan['limit_type'] == "Data_Limit" || $plan['limit_type'] == "Both_Limit") {
                    $totalUsage = $d['acctOutputOctets'] + $d['acctInputOctets'];
                    $attrs['reply:Mikrotik-Total-Limit'] = Text::convertDataUnit($plan['data_limit'], $plan['data_unit']) - $totalUsage;
                    if ($attrs['reply:Mikrotik-Total-Limit'] < 0) {
                        $attrs['reply:Mikrotik-Total-Limit'] = 0;
                        show_radius_result(["control:Auth-Type" => "Accept", 'Reply-Message' => 'You have exceeded your data limit.'], 401);
                    }
                }
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
        "Sistem Error.\n" .
            $e->getMessage() . "\n" .
            $e->getTraceAsString()
    );
    show_radius_result(['Reply-Message' => 'Command Failed : ' . $action], 401);
} catch (Exception $e) {
    Message::sendTelegram(
        "Sistem Error.\n" .
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
            $raddact = ORM::for_table('rad_acct')->where('username', $tur['username'])->find_one();
            $totalUsage = $raddact['acctOutputOctets'] + $raddact['acctInputOctets'];
            $attrs['reply:Mikrotik-Total-Limit'] = Text::convertDataUnit($plan['data_limit'], $plan['data_unit']) - $totalUsage;
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
    die();
}
