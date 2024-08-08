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
        global $config, $admin, $c, $p, $b, $t, $d, $zero, $trx, $_app_stage, $isChangePlan;
        $date_now = date("Y-m-d H:i:s");
        $date_only = date("Y-m-d");
        $time_only = date("H:i:s");
        $time = date("H:i:s");
        $inv = "";
        $isVoucher = false;
        $c = [];
        if ($trx && $trx['status'] == 2) {
            // if its already paid, return it
            return;
        }

        if ($id_customer == '' or $router_name == '' or $plan_id == '') {
            return false;
        }
        if (trim($gateway) == 'Voucher' && $id_customer == 0) {
            $isVoucher = true;
        }

        $p = ORM::for_table('tbl_plans')->where('id', $plan_id)->find_one();

        if (!$isVoucher) {
            $c = ORM::for_table('tbl_customers')->where('id', $id_customer)->find_one();
            if ($c['status'] != 'Active') {
                _alert(Lang::T('This account status') . ' : ' . Lang::T($c['status']), 'danger', "");
            }
        } else {
            $c = [
                'fullname' => $gateway,
                'email' => '',
                'username' => $channel,
                'password' => $channel,
            ];
        }

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
            // if customer has attribute Expired Date use it
            $day_exp = User::getAttribute("Expired Date", $c['id']);
            if (!$day_exp) {
                // if customer no attribute Expired Date use plan expired date
                $day_exp = 20;
                if ($p['prepaid'] == 'no') {
                    $day_exp = $p['expired_date'];
                }
                if (empty($day_exp)) {
                    $day_exp = 20;
                }
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
            } else if ($config['user_notification_payment'] == 'email') {
                Message::sendEmail($c['email'], '[' . $config['CompanyName'] . '] ' . Lang::T("Invoice") . ' ' . $inv, $textInvoice);
            }

            return true;
        }

        /**
         * 1 Customer only can have 1 PPPOE and 1 Hotspot Plan, 1 prepaid and 1 postpaid
         */

        $query = ORM::for_table('tbl_user_recharges')
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
            ->where('tbl_user_recharges.routers', $router_name)
            ->where('tbl_user_recharges.Type', $p['type'])
            # PPPOE or Hotspot only can have 1 per customer prepaid or postpaid
            # because 1 customer can have 1 PPPOE and 1 Hotspot Plan in mikrotik
            //->where('prepaid', $p['prepaid'])
            ->left_outer_join('tbl_plans', array('tbl_plans.id', '=', 'tbl_user_recharges.plan_id'));
        if ($isVoucher) {
            $query->where('username', $c['username']);
        } else {
            $query->where('customer_id', $id_customer);
        }
        $b = $query->find_one();

        run_hook("recharge_user");

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
            $datetime = explode(' ', date("Y-m-d H:i:s", strtotime('+' . $p['validity'] . ' day')));
            $date_exp = $datetime[0];
            $time = $datetime[1];
        } else if ($p['validity_unit'] == 'Hrs') {
            $datetime = explode(' ', date("Y-m-d H:i:s", strtotime('+' . $p['validity'] . ' hour')));
            $date_exp = $datetime[0];
            $time = $datetime[1];
        } else if ($p['validity_unit'] == 'Mins') {
            $datetime = explode(' ', date("Y-m-d H:i:s", strtotime('+' . $p['validity'] . ' minute')));
            $date_exp = $datetime[0];
            $time = $datetime[1];
        }

        if ($b) {
            $lastExpired = Lang::dateAndTimeFormat($b['expiration'], $b['time']);
            $isChangePlan = false;
            if ($config['extend_expiry'] != 'no') {
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
                } else {
                    $isChangePlan = true;
                }
            }

            //if ($b['status'] == 'on') {
            $dvc = Package::getDevice($p);
            if ($_app_stage != 'demo') {
                try {
                    if (file_exists($dvc)) {
                        require_once $dvc;
                        (new $p['device'])->add_customer($c, $p);
                    } else {
                        new Exception(Lang::T("Devices Not Found"));
                    }
                } catch (Throwable $e) {
                    Message::sendTelegram(
                        "Sistem Error. When activate Package. You need to sync manually\n" .
                            "Router: $router_name\n" .
                            "Customer: u$c[username]\n" .
                            "Plan: p$p[name_plan]\n" .
                            $e->getMessage() . "\n" .
                            $e->getTraceAsString()
                    );
                } catch (Exception $e) {
                    Message::sendTelegram(
                        "Sistem Error. When activate Package. You need to sync manually\n" .
                            "Router: $router_name\n" .
                            "Customer: u$c[username]\n" .
                            "Plan: p$p[name_plan]\n" .
                            $e->getMessage() . "\n" .
                            $e->getTraceAsString()
                    );
                }
            }
            //}
            // if started with voucher, don't insert into tbl_user_recharges
            // this is not necessary, but in case a bug come
            if (strlen($p['device']) > 7 && substr($p['device'], 0, 7) != 'Voucher') {
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
                $b->type = $p['type'];
                if ($admin) {
                    $b->admin_id = ($admin['id']) ? $admin['id'] : '0';
                } else {
                    $b->admin_id = '0';
                }
                $b->save();
            }

            // insert table transactions
            $t = ORM::for_table('tbl_transactions')->create();
            $t->invoice = $inv = "INV-" . Package::_raid();
            $t->username = $c['username'];
            $t->plan_name = $p['name_plan'];
            if ($gateway == 'Voucher' && User::isUserVoucher($channel)) {
                //its already paid
                $t->price = 0;
            } else {
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
            }
            $t->recharged_on = $date_only;
            $t->recharged_time = $time_only;
            $t->expiration = $date_exp;
            $t->time = $time;
            $t->method = "$gateway - $channel";
            $t->routers = $router_name;
            $t->note = $note;
            $t->type = $p['type'];
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

            Message::sendTelegram("#u$c[username] $c[fullname] #recharge #$p[type] \n" . $p['name_plan'] .
                "\nRouter: " . $router_name .
                "\nGateway: " . $gateway .
                "\nChannel: " . $channel .
                "\nLast Expired: $lastExpired" .
                "\nNew Expired: " . Lang::dateAndTimeFormat($date_exp, $time) .
                "\nPrice: " . Lang::moneyFormat($p['price'] + $add_cost) .
                "\nNote:\n" . $note);
        } else {
            // active plan not exists
            $dvc = Package::getDevice($p);
            if ($_app_stage != 'demo') {
                try {
                    if (file_exists($dvc)) {
                        require_once $dvc;
                        (new $p['device'])->add_customer($c, $p);
                    } else {
                        new Exception(Lang::T("Devices Not Found"));
                    }
                } catch (Throwable $e) {
                    Message::sendTelegram(
                        "Sistem Error. When activate Package. You need to sync manually\n" .
                            "Router: $router_name\n" .
                            "Customer: u$c[username]\n" .
                            "Plan: p$p[name_plan]\n" .
                            $e->getMessage() . "\n" .
                            $e->getTraceAsString()
                    );
                } catch (Exception $e) {
                    Message::sendTelegram(
                        "Sistem Error. When activate Package. You need to sync manually\n" .
                            "Router: $router_name\n" .
                            "Customer: u$c[username]\n" .
                            "Plan: p$p[name_plan]\n" .
                            $e->getMessage() . "\n" .
                            $e->getTraceAsString()
                    );
                }
            }
            // if started with voucher, don't insert into tbl_user_recharges
            if (strlen($p['device']) > 7 && substr($p['device'], 0, 7) != 'Voucher') {
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
                $d->type = $p['type'];
                if ($admin) {
                    $d->admin_id = ($admin['id']) ? $admin['id'] : '0';
                } else {
                    $d->admin_id = '0';
                }
                $d->save();
            }

            // insert table transactions
            $t = ORM::for_table('tbl_transactions')->create();
            $t->invoice = $inv = "INV-" . Package::_raid();
            $t->username = $c['username'];
            $t->plan_name = $p['name_plan'];
            if ($gateway == 'Voucher' && User::isUserVoucher($channel)) {
                $t->price = 0;
                // its already paid
            } else {
                if ($p['validity_unit'] == 'Period') {
                    // Postpaid price always zero for first time
                    $note = '';
                    $bills = [];
                    $t->price = 0;
                } else {
                    $t->price = $p['price'] + $add_cost;
                }
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
            $t->type = $p['type'];
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

            Message::sendTelegram("#u$c[username] $c[fullname] #buy #$p[type] \n" . $p['name_plan'] .
                "\nRouter: " . $router_name .
                "\nGateway: " . $gateway .
                "\nChannel: " . $channel .
                "\nExpired: " . Lang::dateAndTimeFormat($date_exp, $time) .
                "\nPrice: " . Lang::moneyFormat($p['price'] + $add_cost) .
                "\nNote:\n" . $note);
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
    public static function tax($price, $tax_rate = 1)
    {
        // Convert tax rate to decimal
        $tax_rate_decimal = $tax_rate / 100;
        $tax = $price * $tax_rate_decimal;
        return $tax;
    }

    public static function getDevice($plan)
    {
        global $DEVICE_PATH;
        if ($plan === false) {
            return "none";
        }
        if (!isset($plan['device'])) {
            return "none";
        }
        if (!empty($plan['device'])) {
            return $DEVICE_PATH . DIRECTORY_SEPARATOR . $plan['device'] . '.php';
        }
        if ($plan['is_radius'] == 1) {
            $plan->device = 'Radius';
            $plan->save();
            return $DEVICE_PATH . DIRECTORY_SEPARATOR . 'Radius' . '.php';
        }
        if ($plan['type'] == 'PPPOE') {
            $plan->device = 'MikrotikPppoe';
            $plan->save();
            return $DEVICE_PATH . DIRECTORY_SEPARATOR . 'MikrotikPppoe' . '.php';
        }
        $plan->device = 'MikrotikHotspot';
        $plan->save();
        return $DEVICE_PATH . DIRECTORY_SEPARATOR . 'MikrotikHotspot' . '.php';
    }
}
