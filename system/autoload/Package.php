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
     * @param array $pgids payment gateway ids
     * @return boolean
     */
    public static function rechargeUser($id_customer, $router_name, $plan_id, $gateway, $channel, $note = '')
    {
        global $config, $admin, $c, $p, $b, $t, $d, $zero, $trx;
        $date_now = date("Y-m-d H:i:s");
        $date_only = date("Y-m-d");
        $time_only = date("H:i:s");
        $time = date("H:i:s");
        $inv = "";

        if ($id_customer == '' or $router_name == '' or $plan_id == '') {
            return false;
        }

        $c = ORM::for_table('tbl_customers')->where('id', $id_customer)->find_one();
        $p = ORM::for_table('tbl_plans')->where('id', $plan_id)->find_one();

        $add_cost = 0;
        $bills = [];
        // Zero cost recharge
        if (isset($zero) && $zero == 1) {
            $p['price'] = 0;
        } else {
            // Additional cost
            list($bills, $add_cost) = User::getBills($id_customer);
            if ($add_cost > 0 && $router_name != 'balance') {
                foreach ($bills as $k => $v) {
                    $note .= $k . " : " . Lang::moneyFormat($v) . "\n";
                }
                $note .= $p['name_plan'] . " : " . Lang::moneyFormat($p['price']) . "\n";
            }
        }


        if (!$p['enabled']) {
            if (!isset($admin) || !isset($admin['id']) || empty($admin['id'])) {
                r2(U . 'home', 'e', Lang::T('Plan Not found'));
            }
            if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
                r2(U . 'dashboard', 'e', Lang::T('Plan Not found'));
            }
        }

        if ($p['validity_unit'] == 'Period') {
            $day_exp = User::getAttribute("Expired Date", $c['id']); //ORM::for_table('tbl_customers_fields')->where('field_name', 'Expired Date')->where('customer_id', $c['id'])->find_one();
            if (!$day_exp) {
                $day_exp = 20;
                // $day_exp = date('d', strtotime($c['created_at']));
                // if (empty($day_exp) || $day_exp > 28) {
                //     $day_exp = 1;
                // }
                $f = ORM::for_table('tbl_customers_fields')->create();
                $f->customer_id = $c['id'];
                $f->field_name = 'Expired Date';
                $f->field_value = $day_exp;
                $f->save();
            }
        }

        if ($router_name == 'balance') {
            // insert table transactions
            $t = ORM::for_table('tbl_transactions')->create();
            $t->invoice = $inv = "INV-" . Package::_raid();
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
            if ($admin) {
                $t->admin_id = ($admin['id']) ? $admin['id'] : '0';
            } else {
                $t->admin_id = '0';
            }
            $t->save();

            $balance_before = $c['balance'];
            Balance::plus($id_customer, $p['price']);
            $balance = $c['balance'] + $p['price'];

            $textInvoice = Lang::getNotifText('invoice_balance');
            $textInvoice = str_replace('[[company_name]]', $config['CompanyName'], $textInvoice);
            $textInvoice = str_replace('[[address]]', $config['address'], $textInvoice);
            $textInvoice = str_replace('[[phone]]', $config['phone'], $textInvoice);
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
            $textInvoice = str_replace('[[footer]]', $config['note'], $textInvoice);
            $textInvoice = str_replace('[[balance_before]]', Lang::moneyFormat($balance_before), $textInvoice);
            $textInvoice = str_replace('[[balance]]', Lang::moneyFormat($balance), $textInvoice);

            if ($config['user_notification_payment'] == 'sms') {
                Message::sendSMS($c['phonenumber'], $textInvoice);
            } else if ($config['user_notification_payment'] == 'wa') {
                Message::sendWhatsapp($c['phonenumber'], $textInvoice);
            }

            return true;
        }

        /**
         * 1 Customer only can have 1 PPPOE and 1 Hotspot Plan, 1 prepaid and 1 postpaid
         */
        $b = ORM::for_table('tbl_user_recharges')
            ->select('tbl_user_recharges.id', 'id')
            ->select('customer_id')
            ->select('username')
            ->select('plan_id')
            ->select('namebp')
            ->select('recharged_on')
            ->select('recharged_time')
            ->select('expiration')
            ->select('time')
            ->select('status')
            ->select('method')
            ->select('tbl_user_recharges.routers', 'routers')
            ->select('tbl_user_recharges.type', 'type')
            ->select('admin_id')
            ->select('prepaid')
            ->where('customer_id', $id_customer)
            ->where('tbl_user_recharges.routers', $router_name)
            ->where('tbl_user_recharges.Type', $p['type'])
            # PPPOE or Hotspot only can have 1 per customer prepaid or postpaid
            # because 1 customer can have 1 PPPOE and 1 Hotspot Plan in mikrotik
            //->where('prepaid', $p['prepaid'])
            ->join('tbl_plans', array('tbl_plans.id', '=', 'tbl_user_recharges.plan_id'))
            ->find_one();

        run_hook("recharge_user");


        $mikrotik = Mikrotik::info($router_name);
        if ($p['validity_unit'] == 'Months') {
            $date_exp = date("Y-m-d", strtotime('+' . $p['validity'] . ' month'));
        } else if ($p['validity_unit'] == 'Period') {
            $date_tmp = date("Y-m-$day_exp", strtotime('+' . $p['validity'] . ' month'));
            $dt1 = new DateTime("$date_only");
            $dt2 = new DateTime("$date_tmp");
            $diff = $dt2->diff($dt1);
            $sum =  $diff->format("%a"); // => 453
            if ($sum >= 35 * $p['validity']) {
                $date_exp = date("Y-m-$day_exp", strtotime('+0 month'));
            } else {
                $date_exp = date("Y-m-$day_exp", strtotime('+' . $p['validity'] . ' month'));
            };
            $time = date("23:59:00");
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
        $isChangePlan = false;
        if ($p['type'] == 'Hotspot') {
            if ($b) {
                if ($plan_id != $b['plan_id']) {
                    $isChangePlan = true;
                }
                if ($b['namebp'] == $p['name_plan'] && $b['status'] == 'on') {
                    // if it same internet plan, expired will extend
                    if ($p['validity_unit'] == 'Months') {
                        $date_exp = date("Y-m-d", strtotime($b['expiration'] . ' +' . $p['validity'] . ' months'));
                        $time = $b['time'];
                    } else if ($p['validity_unit'] == 'Period') {
                        $date_exp = date("Y-m-$day_exp", strtotime($b['expiration'] . ' +' . $p['validity'] . ' months'));
                        $time = date("23:59:00");
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

                if ($isChangePlan || $b['status'] == 'off') {
                    if ($p['is_radius']) {
                        Radius::customerAddPlan($c, $p, "$date_exp $time");
                    } else {
                        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        Mikrotik::removeHotspotUser($client, $c['username']);
                        Mikrotik::removeHotspotActiveUser($client, $c['username']);
                        Mikrotik::addHotspotUser($client, $p, $c);
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
                if ($admin) {
                    $b->admin_id = ($admin['id']) ? $admin['id'] : '0';
                } else {
                    $b->admin_id = '0';
                }
                $b->save();

                // insert table transactions
                $t = ORM::for_table('tbl_transactions')->create();
                $t->invoice = $inv = "INV-" . Package::_raid();
                $t->username = $c['username'];
                $t->plan_name = $p['name_plan'];
                if ($p['validity_unit'] == 'Period') {
                    // Postpaid price from field
                    $add_inv = User::getAttribute("Invoice", $id_customer);
                    if (empty($add_inv) or $add_inv == 0) {
                        $t->price = $p['price'] + $add_cost;
                    } else {
                        $t->price = $add_inv + $add_cost;
                    }
                } else {
                    $t->price = $p['price'] + $add_cost;
                }
                $t->recharged_on = $date_only;
                $t->recharged_time = $time_only;
                $t->expiration = $date_exp;
                $t->time = $time;
                $t->method = "$gateway - $channel";
                $t->routers = $router_name;
                $t->note = $note;
                $t->type = "Hotspot";
                if ($admin) {
                    $t->admin_id = ($admin['id']) ? $admin['id'] : '0';
                } else {
                    $t->admin_id = '0';
                }
                $t->save();

                if ($p['validity_unit'] == 'Period') {
                    // insert price to fields for invoice next month
                    $fl = ORM::for_table('tbl_customers_fields')->where('field_name', 'Invoice')->where('customer_id', $c['id'])->find_one();
                    if (!$fl) {
                        $fl = ORM::for_table('tbl_customers_fields')->create();
                        $fl->customer_id = $c['id'];
                        $fl->field_name = 'Invoice';
                        $fl->field_value = $p['price'];
                        $fl->save();
                    } else {
                        $fl->customer_id = $c['id'];
                        $fl->field_value = $p['price'];
                        $fl->save();
                    }
                }


                Message::sendTelegram("#u$c[username] $c[fullname] #recharge #Hotspot \n" . $p['name_plan'] .
                    "\nRouter: " . $router_name .
                    "\nGateway: " . $gateway .
                    "\nChannel: " . $channel .
                    "\nPrice: " . Lang::moneyFormat($p['price'] + $add_cost) .
                    "\nNote:\n" . $note);
            } else {
                if ($p['is_radius']) {
                    Radius::customerAddPlan($c, $p, "$date_exp $time");
                } else {
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::removeHotspotUser($client, $c['username']);
                    Mikrotik::removeHotspotActiveUser($client, $c['username']);
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
                if ($admin) {
                    $d->admin_id = ($admin['id']) ? $admin['id'] : '0';
                } else {
                    $d->admin_id = '0';
                }
                $d->save();

                // insert table transactions
                $t = ORM::for_table('tbl_transactions')->create();
                $t->invoice = $inv = "INV-" . Package::_raid();
                $t->username = $c['username'];
                $t->plan_name = $p['name_plan'];
                if ($p['validity_unit'] == 'Period') {
                    // Postpaid price always zero for first time
                    $t->price = 0 + $add_cost;
                } else {
                    $t->price = $p['price'] + $add_cost;
                }
                $t->recharged_on = $date_only;
                $t->recharged_time = $time_only;
                $t->expiration = $date_exp;
                $t->time = $time;
                $t->method = "$gateway - $channel";
                $t->routers = $router_name;
                $t->note = $note;
                $t->type = "Hotspot";
                if ($admin) {
                    $t->admin_id = ($admin['id']) ? $admin['id'] : '0';
                } else {
                    $t->admin_id = '0';
                }
                $t->save();

                if ($p['validity_unit'] == 'Period' && $p['price'] != 0) {
                    // insert price to fields for invoice next month
                    $fl = ORM::for_table('tbl_customers_fields')->where('field_name', 'Invoice')->where('customer_id', $c['id'])->find_one();
                    if (!$fl) {
                        $fl = ORM::for_table('tbl_customers_fields')->create();
                        $fl->customer_id = $c['id'];
                        $fl->field_name = 'Invoice';
                        // Calculating Price
                        $sd = new DateTime("$date_only");
                        $ed = new DateTime("$date_exp");
                        $td = $ed->diff($sd);
                        $fd = $td->format("%a");
                        $gi = ($p['price'] / (30 * $p['validity'])) * $fd;
                        if ($gi > $p['price']) {
                            $fl->field_value = $p['price'];
                        } else {
                            $fl->field_value = $gi;
                        }
                        $fl->save();
                    } else {
                        $fl->customer_id = $c['id'];
                        $fl->field_value = $p['price'];
                        $fl->save();
                    }
                }

                Message::sendTelegram("#u$c[username] $c[fullname] #buy #Hotspot \n" . $p['name_plan'] .
                    "\nRouter: " . $router_name .
                    "\nGateway: " . $gateway .
                    "\nChannel: " . $channel .
                    "\nPrice: " . Lang::moneyFormat($p['price'] + $add_cost) .
                    "\nNote:\n" . $note);
            }
        } else {

            if ($b) {
                if ($plan_id != $b['plan_id']) {
                    $isChangePlan = true;
                }
                if ($b['namebp'] == $p['name_plan'] && $b['status'] == 'on') {
                    // if it same internet plan, expired will extend
                    if ($p['validity_unit'] == 'Months') {
                        $date_exp = date("Y-m-d", strtotime($b['expiration'] . ' +' . $p['validity'] . ' months'));
                        $time = $b['time'];
                    } else if ($p['validity_unit'] == 'Period') {
                        $date_exp = date("Y-m-$day_exp", strtotime($b['expiration'] . ' +' . $p['validity'] . ' months'));
                        $time = date("23:59:00");
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

                if ($isChangePlan || $b['status'] == 'off') {
                    if ($p['is_radius']) {
                        Radius::customerAddPlan($c, $p, "$date_exp $time");
                    } else {
                        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        Mikrotik::removePpoeUser($client, $c['username']);
                        Mikrotik::removePpoeActive($client, $c['username']);
                        Mikrotik::addPpoeUser($client, $p, $c);
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
                if ($admin) {
                    $b->admin_id = ($admin['id']) ? $admin['id'] : '0';
                } else {
                    $b->admin_id = '0';
                }
                $b->save();

                // insert table transactions
                $t = ORM::for_table('tbl_transactions')->create();
                $t->invoice = $inv = "INV-" . Package::_raid();
                $t->username = $c['username'];
                $t->plan_name = $p['name_plan'];
                if ($p['validity_unit'] == 'Period') {
                    // Postpaid price from field
                    $add_inv = User::getAttribute("Invoice", $id_customer);
                    if (empty($add_inv) or $add_inv == 0) {
                        $t->price = $p['price'] + $add_cost;
                    } else {
                        $t->price = $add_inv + $add_cost;
                    }
                } else {
                    $t->price = $p['price'] + $add_cost;
                }
                $t->recharged_on = $date_only;
                $t->recharged_time = $time_only;
                $t->expiration = $date_exp;
                $t->time = $time;
                $t->method = "$gateway - $channel";
                $t->routers = $router_name;
                $t->note = $note;
                $t->type = "PPPOE";
                if ($admin) {
                    $t->admin_id = ($admin['id']) ? $admin['id'] : '0';
                } else {
                    $t->admin_id = '0';
                }
                $t->save();

                if ($p['validity_unit'] == 'Period' && $p['price'] != 0) {
                    // insert price to fields for invoice next month
                    $fl = ORM::for_table('tbl_customers_fields')->where('field_name', 'Invoice')->where('customer_id', $c['id'])->find_one();
                    if (!$fl) {
                        $fl = ORM::for_table('tbl_customers_fields')->create();
                        $fl->customer_id = $c['id'];
                        $fl->field_name = 'Invoice';
                        $fl->field_value = $p['price'];
                        $fl->save();
                    } else {
                        $fl->customer_id = $c['id'];
                        $fl->field_value = $p['price'];
                        $fl->save();
                    }
                }

                Message::sendTelegram("#u$c[username] $c[fullname] #recharge #PPPOE \n" . $p['name_plan'] .
                    "\nRouter: " . $router_name .
                    "\nGateway: " . $gateway .
                    "\nChannel: " . $channel .
                    "\nPrice: " . Lang::moneyFormat($p['price'] + $add_cost) .
                    "\nNote:\n" . $note);
            } else {
                if ($p['is_radius']) {
                    Radius::customerAddPlan($c, $p, "$date_exp $time");
                } else {
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::removePpoeUser($client, $c['username']);
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
                if ($admin) {
                    $d->admin_id = ($admin['id']) ? $admin['id'] : '0';
                } else {
                    $d->admin_id = '0';
                }
                $d->save();

                // insert table transactions
                $t = ORM::for_table('tbl_transactions')->create();
                $t->invoice = $inv = "INV-" . Package::_raid();
                $t->username = $c['username'];
                $t->plan_name = $p['name_plan'];
                if ($p['validity_unit'] == 'Period') {
                    // Postpaid price always zero for first time
                    $note = '';
                    $bills = [];
                    $t->price = 0;
                } else {
                    $t->price = $p['price'] + $add_cost;
                }
                $t->recharged_on = $date_only;
                $t->recharged_time = $time_only;
                $t->expiration = $date_exp;
                $t->time = $time;
                $t->method = "$gateway - $channel";
                $t->note = $note;
                $t->routers = $router_name;
                if ($admin) {
                    $t->admin_id = ($admin['id']) ? $admin['id'] : '0';
                } else {
                    $t->admin_id = '0';
                }
                $t->type = "PPPOE";
                $t->save();

                if ($p['validity_unit'] == 'Period' && $p['price'] != 0) {
                    // insert price to fields for invoice next month
                    $fl = ORM::for_table('tbl_customers_fields')->where('field_name', 'Invoice')->where('customer_id', $c['id'])->find_one();
                    if (!$fl) {
                        $fl = ORM::for_table('tbl_customers_fields')->create();
                        $fl->customer_id = $c['id'];
                        $fl->field_name = 'Invoice';
                        // Calculating Price
                        $sd = new DateTime("$date_only");
                        $ed = new DateTime("$date_exp");
                        $td = $ed->diff($sd);
                        $fd = $td->format("%a");
                        $gi = ($p['price'] / (30 * $p['validity'])) * $fd;
                        if ($gi > $p['price']) {
                            $fl->field_value = $p['price'];
                        } else {
                            $fl->field_value = $gi;
                        }
                        $fl->save();
                    } else {
                        $fl->customer_id = $c['id'];
                        $fl->field_value = $p['price'];
                        $fl->save();
                    }
                }

                Message::sendTelegram("#u$c[username] $c[fullname] #buy #PPPOE \n" . $p['name_plan'] .
                    "\nRouter: " . $router_name .
                    "\nGateway: " . $gateway .
                    "\nChannel: " . $channel .
                    "\nPrice: " . Lang::moneyFormat($p['price'] + $add_cost) .
                    "\nNote:\n" . $note);
            }
        }
        if (is_array($bills) && count($bills) > 0) {
            User::billsPaid($bills, $id_customer);
        }
        run_hook("recharge_user_finish");
        Message::sendInvoice($c, $t);
        if ($trx) {
            $trx->trx_invoice = $inv;
        }
        return $inv;
    }

    public static function changeTo($username, $plan_id, $from_id)
    {
        $c = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
        $p = ORM::for_table('tbl_plans')->where('id', $plan_id)->find_one();
        $b = ORM::for_table('tbl_user_recharges')->find_one($from_id);
        if ($p['routers'] == $b['routers'] && $b['routers'] != 'radius') {
            $mikrotik = Mikrotik::info($p['routers']);
        } else {
            $mikrotik = Mikrotik::info($b['routers']);
        }
        // delete first
        if ($p['type'] == 'Hotspot') {
            if ($b) {
                if (!$p['is_radius']) {
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::removeHotspotUser($client, $c['username']);
                    Mikrotik::removeHotspotActiveUser($client, $c['username']);
                }
            } else {
                if (!$p['is_radius']) {
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::removeHotspotUser($client, $c['username']);
                    Mikrotik::removeHotspotActiveUser($client, $c['username']);
                }
            }
        } else {
            if ($b) {
                if (!$p['is_radius']) {
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::removePpoeUser($client, $c['username']);
                    Mikrotik::removePpoeActive($client, $c['username']);
                }
            } else {
                if (!$p['is_radius']) {
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::removePpoeUser($client, $c['username']);
                    Mikrotik::removePpoeActive($client, $c['username']);
                }
            }
        }
        // call the next mikrotik
        if ($p['routers'] != $b['routers'] && $p['routers'] != 'radius') {
            $mikrotik = Mikrotik::info($p['routers']);
        }
        if ($p['type'] == 'Hotspot') {
            if ($b) {
                if ($p['is_radius']) {
                    Radius::customerAddPlan($c, $p, $b['expiration'] . '' . $b['time']);
                } else {
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::addHotspotUser($client, $p, $c);
                }
            } else {
                if ($p['is_radius']) {
                    Radius::customerAddPlan($c, $p, $b['expiration'] . '' . $b['time']);
                } else {
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::addHotspotUser($client, $p, $c);
                }
            }
        } else {
            if ($b) {
                if ($p['is_radius']) {
                    Radius::customerAddPlan($c, $p);
                } else {
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::addPpoeUser($client, $p, $c);
                }
            } else {
                if ($p['is_radius']) {
                    Radius::customerAddPlan($c, $p);
                } else {
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::addPpoeUser($client, $p, $c);
                }
            }
        }
    }


    public static function _raid()
    {
        return ORM::for_table('tbl_transactions')->max('id') + 1;
    }

    /**
     * @param in   tbl_transactions
     * @param string $router_name router name for this package
     * @param int   $plan_id plan id for this package
     * @param string $gateway payment gateway name
     * @param string $channel channel payment gateway
     * @return boolean
     */
    public static function createInvoice($in)
    {
        global $config, $admin, $ui;
        $date = Lang::dateAndTimeFormat($in['recharged_on'], $in['recharged_time']);
        if ($admin['id'] != $in['admin_id'] && $in['admin_id'] > 0) {
            $_admin = Admin::_info($in['admin_id']);
            // if admin not deleted
            if ($_admin) $admin = $_admin;
        } else {
            $admin['fullname'] = 'Customer';
        }
        $cust = ORM::for_table('tbl_customers')->where('username', $in['username'])->findOne();

        $note = '';
        //print
        $invoice = Lang::pad($config['CompanyName'], ' ', 2) . "\n";
        $invoice .= Lang::pad($config['address'], ' ', 2) . "\n";
        $invoice .= Lang::pad($config['phone'], ' ', 2) . "\n";
        $invoice .= Lang::pad("", '=') . "\n";
        $invoice .= Lang::pads("Invoice", $in['invoice'], ' ') . "\n";
        $invoice .= Lang::pads(Lang::T('Date'), $date, ' ') . "\n";
        $invoice .= Lang::pads(Lang::T('Sales'), $admin['fullname'], ' ') . "\n";
        $invoice .= Lang::pad("", '=') . "\n";
        $invoice .= Lang::pads(Lang::T('Type'), $in['type'], ' ') . "\n";
        $invoice .= Lang::pads(Lang::T('Plan Name'), $in['plan_name'], ' ') . "\n";
        if (!empty($in['note'])) {
            $in['note'] = str_replace("\r", "", $in['note']);
            $tmp = explode("\n", $in['note']);
            foreach ($tmp as $t) {
                if (strpos($t, " : ") === false) {
                    if (!empty($t)) {
                        $note .= "$t\n";
                    }
                } else {
                    $tmp2 = explode(" : ", $t);
                    $invoice .= Lang::pads($tmp2[0], $tmp2[1], ' ') . "\n";
                }
            }
        }
        $invoice .= Lang::pads(Lang::T('Total'), Lang::moneyFormat($in['price']), ' ') . "\n";
        $method = explode("-", $in['method']);
        $invoice .= Lang::pads($method[0], $method[1], ' ') . "\n";
        if (!empty($note)) {
            $invoice .= Lang::pad("", '=') . "\n";
            $invoice .= Lang::pad($note, ' ', 2) . "\n";
        }
        $invoice .= Lang::pad("", '=') . "\n";
        if ($cust) {
            $invoice .= Lang::pads(Lang::T('Full Name'), $cust['fullname'], ' ') . "\n";
        }
        $invoice .= Lang::pads(Lang::T('Username'), $in['username'], ' ') . "\n";
        $invoice .= Lang::pads(Lang::T('Password'), '**********', ' ') . "\n";
        if ($in['type'] != 'Balance') {
            $invoice .= Lang::pads(Lang::T('Created On'), Lang::dateAndTimeFormat($in['recharged_on'], $in['recharged_time']), ' ') . "\n";
            $invoice .= Lang::pads(Lang::T('Expires On'), Lang::dateAndTimeFormat($in['expiration'], $in['time']), ' ') . "\n";
        }
        $invoice .= Lang::pad("", '=') . "\n";
        $invoice .= Lang::pad($config['note'], ' ', 2) . "\n";
        $ui->assign('invoice', $invoice);
        $config['printer_cols'] = 30;
        //whatsapp
        $invoice = Lang::pad($config['CompanyName'], ' ', 2) . "\n";
        $invoice .= Lang::pad($config['address'], ' ', 2) . "\n";
        $invoice .= Lang::pad($config['phone'], ' ', 2) . "\n";
        $invoice .= Lang::pad("", '=') . "\n";
        $invoice .= Lang::pads("Invoice", $in['invoice'], ' ') . "\n";
        $invoice .= Lang::pads(Lang::T('Date'), $date, ' ') . "\n";
        $invoice .= Lang::pads(Lang::T('Sales'), $admin['fullname'], ' ') . "\n";
        $invoice .= Lang::pad("", '=') . "\n";
        $invoice .= Lang::pads(Lang::T('Type'), $in['type'], ' ') . "\n";
        $invoice .= Lang::pads(Lang::T('Plan Name'), $in['plan_name'], ' ') . "\n";
        if (!empty($in['note'])) {
            $invoice .= Lang::pad("", '=') . "\n";
            foreach ($tmp as $t) {
                if (strpos($t, " : ") === false) {
                    if (!empty($t)) {
                        $invoice .= Lang::pad($t, ' ', 2) . "\n";
                    }
                } else {
                    $tmp2 = explode(" : ", $t);
                    $invoice .= Lang::pads($tmp2[0], $tmp2[1], ' ') . "\n";
                }
            }
        }
        $invoice .= Lang::pads(Lang::T('Total'), Lang::moneyFormat($in['price']), ' ') . "\n";
        $invoice .= Lang::pads($method[0], $method[1], ' ') . "\n";
        if (!empty($note)) {
            $invoice .= Lang::pad("", '=') . "\n";
            $invoice .= Lang::pad($note, ' ', 2) . "\n";
        }
        $invoice .= Lang::pad("", '=') . "\n";
        if ($cust) {
            $invoice .= Lang::pads(Lang::T('Full Name'), $cust['fullname'], ' ') . "\n";
        }
        $invoice .= Lang::pads(Lang::T('Username'), $in['username'], ' ') . "\n";
        $invoice .= Lang::pads(Lang::T('Password'), '**********', ' ') . "\n";
        if ($in['type'] != 'Balance') {
            $invoice .= Lang::pads(Lang::T('Created On'), Lang::dateAndTimeFormat($in['recharged_on'], $in['recharged_time']), ' ') . "\n";
            $invoice .= Lang::pads(Lang::T('Expires On'), Lang::dateAndTimeFormat($in['expiration'], $in['time']), ' ') . "\n";
        }
        $invoice .= Lang::pad("", '=') . "\n";
        $invoice .= Lang::pad($config['note'], ' ', 2) . "\n";
        $ui->assign('whatsapp', urlencode("```$invoice```"));
        $ui->assign('in', $in);
    }
}
