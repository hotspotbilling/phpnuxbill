<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/



class Package
{
    /**
     * @param int   $id_customer String user identifier
     * @param string $router_name router name for this package
     * @param int   $plan_id plan id for this package
     * @param string $gateway payment gateway name
     * @param string $channel channel payment gateway
     * @return boolean
     */
    public static function rechargeUser($id_customer, $router_name, $plan_id, $gateway, $channel)
    {
        global $_c;
        $date_now = date("Y-m-d H:i:s");
        $date_only = date("Y-m-d");
        $time_only = date("H:i:s");
        $time = date("H:i:s");

        if ($id_customer == '' or $router_name == '' or $plan_id == '') {
            return false;
        }

        $c = ORM::for_table('tbl_customers')->where('id', $id_customer)->find_one();
        $p = ORM::for_table('tbl_plans')->where('id', $plan_id)->where('enabled', '1')->find_one();

        if ($router_name == 'balance') {
            // insert table transactions
            $inv = "INV-" . Package::_raid(5);
            $t = ORM::for_table('tbl_transactions')->create();
            $t->invoice = $inv;
            $t->username = $c['username'];
            $t->plan_name = $p['name_plan'];
            $t->price = $p['price'];
            $t->recharged_on = $date_only;
            $t->recharged_time = date("H:i:s");
            $t->expiration = $date_only;
            $t->time = $time;
            $t->method = "$gateway - $channel";
            $t->routers = $router_name;
            $t->type = "Balance";
            $t->save();

            $balance_before = $c['balance'];
            Balance::plus($id_customer, $p['price']);
            $balance = $c['balance'] + $p['price'];

            $textInvoice = Lang::getNotifText('invoice_balance');
            $textInvoice = str_replace('[[company_name]]', $_c['CompanyName'], $textInvoice);
            $textInvoice = str_replace('[[address]]', $_c['address'], $textInvoice);
            $textInvoice = str_replace('[[phone]]', $_c['phone'], $textInvoice);
            $textInvoice = str_replace('[[invoice]]', $inv, $textInvoice);
            $textInvoice = str_replace('[[date]]', Lang::dateTimeFormat($date_now), $textInvoice);
            $textInvoice = str_replace('[[payment_gateway]]', $gateway, $textInvoice);
            $textInvoice = str_replace('[[payment_channel]]', $channel, $textInvoice);
            $textInvoice = str_replace('[[type]]', 'Balance', $textInvoice);
            $textInvoice = str_replace('[[plan_name]]', $p['name_plan'], $textInvoice);
            $textInvoice = str_replace('[[plan_price]]', Lang::moneyFormat($p['price']), $textInvoice);
            $textInvoice = str_replace('[[name]]', $c['fullname'], $textInvoice);
            $textInvoice = str_replace('[[user_name]]', $c['username'], $textInvoice);
            $textInvoice = str_replace('[[user_password]]', $c['password'], $textInvoice);
            $textInvoice = str_replace('[[footer]]', $_c['note'], $textInvoice);
            $textInvoice = str_replace('[[balance_before]]', Lang::moneyFormat($balance_before), $textInvoice);
            $textInvoice = str_replace('[[balance]]', Lang::moneyFormat($balance), $textInvoice);

            if ($_c['user_notification_payment'] == 'sms') {
                Message::sendSMS($c['phonenumber'], $textInvoice);
            } else if ($_c['user_notification_payment'] == 'wa') {
                Message::sendWhatsapp($c['phonenumber'], $textInvoice);
            }

            return true;
        }


        $b = ORM::for_table('tbl_user_recharges')->where('customer_id', $id_customer)->where('routers', $router_name)->find_one();

        $mikrotik = Mikrotik::info($router_name);
        if ($p['validity_unit'] == 'Months') {
            $date_exp = date("Y-m-d", strtotime('+' . $p['validity'] . ' month'));
        } else if ($p['validity_unit'] == 'Days') {
            $date_exp = date("Y-m-d", strtotime('+' . $p['validity'] . ' day'));
        } else if ($p['validity_unit'] == 'Hrs') {
            $datetime = explode(' ', date("Y-m-d H:i:s", strtotime('+' . $p['validity'] . ' hour')));
            $date_exp = $datetime[0];
            $time = $datetime[1];
        } else if ($p['validity_unit'] == 'Mins') {
            $datetime = explode(' ', date("Y-m-d H:i:s", strtotime('+' . $p['validity'] . ' minute')));
            $date_exp = $datetime[0];
            $time = $datetime[1];
        }

        if ($p['type'] == 'Hotspot') {
            if ($b) {
                if ($p['is_radius']) {
                    Radius::customerAddPlan($c, $p, "$date_exp $time");
                }else{
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::removeHotspotUser($client, $c['username']);
                    Mikrotik::removePpoeUser($client, $c['username']);
                    Mikrotik::removeHotspotActiveUser($client, $c['username']);
                    Mikrotik::removePpoeActive($client, $c['username']);
                    Mikrotik::addHotspotUser($client, $p, $c);
                }

                if ($b['namebp'] == $p['name_plan'] && $b['status'] == 'on') {
                    // if it same internet plan, expired will extend
                    if ($p['validity_unit'] == 'Months') {
                        $date_exp = date("Y-m-d", strtotime($b['expiration'] . ' +' . $p['validity'] . ' months'));
                        $time = $b['time'];
                    } else if ($p['validity_unit'] == 'Days') {
                        $date_exp = date("Y-m-d", strtotime($b['expiration'] . ' +' . $p['validity'] . ' days'));
                        $time = $b['time'];
                    } else if ($p['validity_unit'] == 'Hrs') {
                        $datetime = explode(' ', date("Y-m-d H:i:s", strtotime($b['expiration'] . ' ' . $b['time'] . ' +' . $p['validity'] . ' hours')));
                        $date_exp = $datetime[0];
                        $time = $datetime[1];
                    } else if ($p['validity_unit'] == 'Mins') {
                        $datetime = explode(' ', date("Y-m-d H:i:s", strtotime($b['expiration'] . ' ' . $b['time'] . ' +' . $p['validity'] . ' minutes')));
                        $date_exp = $datetime[0];
                        $time = $datetime[1];
                    }
                }

                $b->customer_id = $id_customer;
                $b->username = $c['username'];
                $b->plan_id = $plan_id;
                $b->namebp = $p['name_plan'];
                $b->recharged_on = $date_only;
                $b->recharged_time = $time_only;
                $b->expiration = $date_exp;
                $b->time = $time;
                $b->status = "on";
                $b->method = "$gateway - $channel";
                $b->routers = $router_name;
                $b->type = "Hotspot";
                $b->save();

                // insert table transactions
                $t = ORM::for_table('tbl_transactions')->create();
                $t->invoice = "INV-" . Package::_raid(5);
                $t->username = $c['username'];
                $t->plan_name = $p['name_plan'];
                $t->price = $p['price'];
                $t->recharged_on = $date_only;
                $t->recharged_time = $time_only;
                $t->expiration = $date_exp;
                $t->time = $time;
                $t->method = "$gateway - $channel";
                $t->routers = $router_name;
                $t->type = "Hotspot";
                $t->save();
            } else {
                if ($p['is_radius']) {
                    Radius::customerAddPlan($c, $p, "$date_exp $time");
                }else{
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::removeHotspotUser($client, $c['username']);
                    Mikrotik::removePpoeUser($client, $c['username']);
                    Mikrotik::removeHotspotActiveUser($client, $c['username']);
                    Mikrotik::removePpoeActive($client, $c['username']);
                    Mikrotik::addHotspotUser($client, $p, $c);
                }

                $d = ORM::for_table('tbl_user_recharges')->create();
                $d->customer_id = $id_customer;
                $d->username = $c['username'];
                $d->plan_id = $plan_id;
                $d->namebp = $p['name_plan'];
                $d->recharged_on = $date_only;
                $d->recharged_time = $time_only;
                $d->expiration = $date_exp;
                $d->time = $time;
                $d->status = "on";
                $d->method = "$gateway - $channel";
                $d->routers = $router_name;
                $d->type = "Hotspot";
                $d->save();

                // insert table transactions
                $t = ORM::for_table('tbl_transactions')->create();
                $t->invoice = "INV-" . Package::_raid(5);
                $t->username = $c['username'];
                $t->plan_name = $p['name_plan'];
                $t->price = $p['price'];
                $t->recharged_on = $date_only;
                $t->recharged_time = $time_only;
                $t->expiration = $date_exp;
                $t->time = $time;
                $t->method = "$gateway - $channel";
                $t->routers = $router_name;
                $t->type = "Hotspot";
                $t->save();
            }
            Message::sendTelegram("#u$c[username] #buy #Hotspot \n" . $p['name_plan'] .
                "\nRouter: " . $router_name .
                "\nGateway: " . $gateway .
                "\nChannel: " . $channel .
                "\nPrice: " . Lang::moneyFormat($p['price']));
        } else {

            if ($b) {
                if ($p['is_radius']) {
                    Radius::customerAddPlan($c, $p, "$date_exp $time");
                }else{
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::removeHotspotUser($client, $c['username']);
                    Mikrotik::removePpoeUser($client, $c['username']);
                    Mikrotik::removeHotspotActiveUser($client, $c['username']);
                    Mikrotik::removePpoeActive($client, $c['username']);
                    Mikrotik::addPpoeUser($client, $p, $c);
                }


                if ($b['namebp'] == $p['name_plan'] && $b['status'] == 'on') {
                    // if it same internet plan, expired will extend
                    if ($p['validity_unit'] == 'Months') {
                        $date_exp = date("Y-m-d", strtotime($b['expiration'] . ' +' . $p['validity'] . ' months'));
                        $time = $b['time'];
                    } else if ($p['validity_unit'] == 'Days') {
                        $date_exp = date("Y-m-d", strtotime($b['expiration'] . ' +' . $p['validity'] . ' days'));
                        $time = $b['time'];
                    } else if ($p['validity_unit'] == 'Hrs') {
                        $datetime = explode(' ', date("Y-m-d H:i:s", strtotime($b['expiration'] . ' ' . $b['time'] . ' +' . $p['validity'] . ' hours')));
                        $date_exp = $datetime[0];
                        $time = $datetime[1];
                    } else if ($p['validity_unit'] == 'Mins') {
                        $datetime = explode(' ', date("Y-m-d H:i:s", strtotime($b['expiration'] . ' ' . $b['time'] . ' +' . $p['validity'] . ' minutes')));
                        $date_exp = $datetime[0];
                        $time = $datetime[1];
                    }
                }

                $b->customer_id = $id_customer;
                $b->username = $c['username'];
                $b->plan_id = $plan_id;
                $b->namebp = $p['name_plan'];
                $b->recharged_on = $date_only;
                $b->recharged_time = $time_only;
                $b->expiration = $date_exp;
                $b->time = $time;
                $b->status = "on";
                $b->method = "$gateway - $channel";
                $b->routers = $router_name;
                $b->type = "PPPOE";
                $b->save();

                // insert table transactions
                $t = ORM::for_table('tbl_transactions')->create();
                $t->invoice = "INV-" . Package::_raid(5);
                $t->username = $c['username'];
                $t->plan_name = $p['name_plan'];
                $t->price = $p['price'];
                $t->recharged_on = $date_only;
                $t->recharged_time = $time_only;
                $t->expiration = $date_exp;
                $t->time = $time;
                $t->method = "$gateway - $channel";
                $t->routers = $router_name;
                $t->type = "PPPOE";
                $t->save();
            } else {
                if ($p['is_radius']) {
                    Radius::customerAddPlan($c, $p, "$date_exp $time");
                }else{
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::removeHotspotUser($client, $c['username']);
                    Mikrotik::removePpoeUser($client, $c['username']);
                    Mikrotik::removeHotspotActiveUser($client, $c['username']);
                    Mikrotik::removePpoeActive($client, $c['username']);
                    Mikrotik::addPpoeUser($client, $p, $c);
                }

                $d = ORM::for_table('tbl_user_recharges')->create();
                $d->customer_id = $id_customer;
                $d->username = $c['username'];
                $d->plan_id = $plan_id;
                $d->namebp = $p['name_plan'];
                $d->recharged_on = $date_only;
                $d->recharged_time = $time_only;
                $d->expiration = $date_exp;
                $d->time = $time;
                $d->status = "on";
                $d->method = "$gateway - $channel";
                $d->routers = $router_name;
                $d->type = "PPPOE";
                $d->save();

                // insert table transactions
                $t = ORM::for_table('tbl_transactions')->create();
                $t->invoice = "INV-" . Package::_raid(5);
                $t->username = $c['username'];
                $t->plan_name = $p['name_plan'];
                $t->price = $p['price'];
                $t->recharged_on = $date_only;
                $t->recharged_time = $time_only;
                $t->expiration = $date_exp;
                $t->time = $time;
                $t->method = "$gateway - $channel";
                $t->routers = $router_name;
                $t->type = "PPPOE";
                $t->save();
            }
            Message::sendTelegram("#u$c[username] #buy #PPPOE \n" . $p['name_plan'] .
                "\nRouter: " . $router_name .
                "\nGateway: " . $gateway .
                "\nChannel: " . $channel .
                "\nPrice: " . Lang::moneyFormat($p['price']));
        }

        Message::sendInvoice($c, $t);
        return true;
    }

    public static function changeTo($username, $plan_id, $from_id)
    {
        $c = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
        $p = ORM::for_table('tbl_plans')->where('id', $plan_id)->where('enabled', '1')->find_one();
        $b = ORM::for_table('tbl_user_recharges')->find_one($from_id);
        if($p['routers'] == $b['routers'] && $b['routers'] != 'radius'){
            $mikrotik = Mikrotik::info($p['routers']);
        }else{
            $mikrotik = Mikrotik::info($b['routers']);
        }
        // delete first
        if ($p['type'] == 'Hotspot') {
            if ($b) {
                if (!$p['is_radius']) {
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::removeHotspotUser($client, $c['username']);
                    Mikrotik::removePpoeUser($client, $c['username']);
                    Mikrotik::removeHotspotActiveUser($client, $c['username']);
                    Mikrotik::removePpoeActive($client, $c['username']);
                }
            } else {
                if (!$p['is_radius']) {
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::removeHotspotUser($client, $c['username']);
                    Mikrotik::removePpoeUser($client, $c['username']);
                    Mikrotik::removeHotspotActiveUser($client, $c['username']);
                    Mikrotik::removePpoeActive($client, $c['username']);
                }
            }
        } else {
            if ($b) {
                if (!$p['is_radius']) {
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::removeHotspotUser($client, $c['username']);
                    Mikrotik::removePpoeUser($client, $c['username']);
                    Mikrotik::removeHotspotActiveUser($client, $c['username']);
                    Mikrotik::removePpoeActive($client, $c['username']);
                }
            } else {
                if (!$p['is_radius']) {
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::removeHotspotUser($client, $c['username']);
                    Mikrotik::removePpoeUser($client, $c['username']);
                    Mikrotik::removeHotspotActiveUser($client, $c['username']);
                    Mikrotik::removePpoeActive($client, $c['username']);
                }
            }
        }
        // call the next mikrotik
        if($p['routers'] != $b['routers'] && $p['routers'] != 'radius'){
            $mikrotik = Mikrotik::info($p['routers']);
        }
        if ($p['type'] == 'Hotspot') {
            if ($b) {
                if ($p['is_radius']) {
                    Radius::customerAddPlan($c, $p, $b['expiration'].''.$b['time']);
                }else{
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::addHotspotUser($client, $p, $c);
                }
            } else {
                if ($p['is_radius']) {
                    Radius::customerAddPlan($c, $p, $b['expiration'].''.$b['time']);
                }else{
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::addHotspotUser($client, $p, $c);
                }
            }
        } else {
            if ($b) {
                if ($p['is_radius']) {
                    Radius::customerAddPlan($c, $p);
                }else{
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::addPpoeUser($client, $p, $c);
                }
            } else {
                if ($p['is_radius']) {
                    Radius::customerAddPlan($c, $p);
                }else{
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::addPpoeUser($client, $p, $c);
                }
            }
        }
    }


    public static function _raid($l)
    {
        return substr(str_shuffle(str_repeat('0123456789', $l)), 0, $l);
    }
}
