<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/



class Package
{
    /** The customer credential lock of the recharge running: 'none' (not needed), 'held', 'busy' (not got in time), 'released' */
    private static $pwState = 'none';

    /** Whether the recharge running has gone past its checks to where it changes anything (a device, the recharge or the transaction) */
    private static $wrote = false;

    /** Progress markers of the voucher claims being redeemed, innermost last (see redeemVoucher): each claim has its own */
    private static $claims = [];

    /** How many rechargeUser() calls are running inside each other (a hook of the finished recharge can start another) */
    private static $depth = 0;

    /** True once the last recharge started to change things: a voucher it was for must then stay used, whatever happened after */
    public static function rechargeWrote()
    {
        return self::$wrote;
    }

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
        // A recharge reads the customer's credential and sends it to the device (add_customer), so it takes the lock a password
        // change holds (passwordLock) before reading the customer and keeps it through the device write: a change that commits
        // meanwhile is not overwritten on the device with the old password, and the recharge does not send one half-changed.
        // The wait is bounded: a password change holds it for the time its devices take, and never waits for a recharge. Not got
        // in time, the device is not written (said as the failure to sync by hand, like any device error), the recharge is saved.
        $was = self::$pwState;
        // a recharge started from a hook of another one must not clear what the outer one already wrote: the outer voucher
        // is released at shutdown by that flag, and an inner refusal would otherwise give back a voucher already saved
        $outerWrote = self::$depth > 0 && self::$wrote;
        self::$depth++;
        self::$wrote = false;
        self::$pwState = 'none';
        $voucher = trim((string) $gateway) == 'Voucher' && $id_customer == 0;
        if (!$voucher && (int) $id_customer > 0 && !in_array($router_name, ['balance', 'Custom Balance'], true)) {
            self::$pwState = self::passwordLock($id_customer, true, 10) ? 'held' : 'busy';
        }
        try {
            return self::rechargeUserRun($id_customer, $router_name, $plan_id, $gateway, $channel, $note);
        } finally {
            self::$depth--;
            if ($outerWrote) {
                self::$wrote = true;
            }
            if (self::$pwState === 'held') {
                self::passwordLock($id_customer, false);
            }
            self::$pwState = $was;
        }
    }

    /**
     * Redeems a voucher: claimed first, by one update that only an unused voucher passes (so two requests with the same code
     * cannot both use it), and recharged only by the request that got the claim. Returns false when it was claimed by another
     * request or the recharge was refused (the voucher is then unused again). A recharge that ends the request before it wrote
     * anything (a disabled plan or an inactive customer redirects with r2() or _alert()) also gives the voucher back, at the end
     * of the request. An exception from the recharge keeps it used: part of the recharge may have happened, so an admin checks it.
     * $username: the customer's username saved on the voucher, null to leave it as it is.
     */
    public static function redeemVoucher($v, $customerId, $username, $code)
    {
        $db = ORM::get_db();
        $claimedAt = date('Y-m-d H:i:s');
        $st = $db->prepare("UPDATE `tbl_voucher` SET `status` = '1', `used_date` = ?" . ($username !== null ? ', `user` = ?' : '') . " WHERE `id` = ? AND `status` = '0'");
        $st->execute(array_merge([$claimedAt], $username !== null ? [$username] : [], [(int) $v['id']]));
        if ($st->rowCount() !== 1) {
            return false;
        }
        $id = (int) $v['id'];
        $finished = false;
        $mark = new stdClass();
        $mark->wrote = false;
        $claimAt = (string) $claimedAt;
        // Gives the voucher back, only while this operation's own claim still holds it (same used date). True when it is unused
        // again; false when that could not be confirmed (an admin must reconcile it).
        $release = function () use (&$finished, $db, $id, $mark, $claimAt) {
            // only a refusal before anything was changed gives the voucher back; once the recharge began to write it stays used
            if ($finished || $mark->wrote) {
                return true;
            }
            try {
                $st = $db->prepare("UPDATE `tbl_voucher` SET `status` = '0', `used_date` = NULL, `user` = '0' WHERE `id` = ? AND `status` = '1' AND `used_date` = ?");
                $st->execute([$id, $claimAt]);
                if ($st->rowCount() === 1) {
                    $finished = true; // only once it is given back: a failed reset is tried again by the next call
                    return true;
                }
                $finished = true; // no longer this claim's to reset
                $why = 'the claim is no longer held by this request';
            } catch (Throwable $e) {
                $finished = true; // not tried again at shutdown after an error, but never silent
                $why = $e->getMessage();
            }
            try {
                _log("Voucher #{$id} could not be given back after a refused recharge, check it: " . $why, 'Voucher');
            } catch (Throwable $ignored) {
            }
            return false;
        };
        register_shutdown_function($release);
        self::$claims[] = $mark;
        try {
            $ok = self::rechargeUser($customerId, $v['routers'], $v['id_plan'], 'Voucher', $code);
        } catch (Throwable $e) {
            // refused before anything was written: given back, so a retry is not turned away by a claim that is kept
            if (!$release() && !$mark->wrote) {
                $finished = true;
                throw new RuntimeException(Lang::T('The voucher could not be restored after an error. Please contact support.'), 0, $e);
            }
            $finished = true; // once it began to write it is kept used (part of the recharge may have happened)
            throw $e;
        } finally {
            array_pop(self::$claims);
        }
        if (!$ok) {
            if (!$release()) {
                throw new RuntimeException(Lang::T('The voucher could not be restored after an error. Please contact support.'));
            }
            return false;
        }
        $finished = true;
        return true;
    }

    private static function rechargeUserRun($id_customer, $router_name, $plan_id, $gateway, $channel, $note = '')
    {
        global $config, $admin, $c, $p, $b, $t, $d, $zero, $trx, $_app_stage, $isChangePlan;
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

        // the plan and the id of its router in one statement (one snapshot): read apart, a rename between the two reads would
        // give the plan's old name no router, or a router that took the name afterwards. The device is sent to that router under
        // the name it has when it is called, so a rename made meanwhile is followed (see onRouterNow)
        $p = ORM::for_table('tbl_plans')
            ->select('tbl_plans.*')
            ->select('tbl_routers.id', '_router_id')
            ->left_outer_join('tbl_routers', ['tbl_routers.name', '=', 'tbl_plans.routers'])
            ->where('tbl_plans.id', $plan_id)
            ->find_one();
        $plan_routers = $p ? (string) $p['routers'] : null;
        $plan_router_id = ($p && $p['_router_id'] !== null && $p['_router_id'] !== '') ? (int) $p['_router_id'] : null;
        if ($p) {
            unset($p['_router_id']); // not a column of the plan: the object is saved later (getDevice)
        }

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
            if ($add_cost != 0 && $router_name != 'balance') {
                foreach ($bills as $k => $v) {
                    $note .= $k . " : " . Lang::moneyFormat($v) . "\n";
                }
                $note .= $p['name_plan'] . " : " . Lang::moneyFormat($p['price']) . "\n";
            }
        }


        if (!$p['enabled']) {
            if (!isset($admin) || !isset($admin['id']) || empty($admin['id'])) {
                r2(getUrl('home'), 'e', Lang::T('Plan Not found'));
            }
            if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
                r2(getUrl('dashboard'), 'e', Lang::T('You do not have permission to access this page'));
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



        // Marks the recharge as having (possibly) changed something, so a voucher is not given back. Called right before the first
        // operation that can change state: the read-only checks below (the active-recharge lookup) can still fail and leave things as
        // they were, and then the voucher stays unused.
        $markWrote = function () {
            self::$wrote = true;
            if (self::$claims) {
                end(self::$claims)->wrote = true; // only the claim whose recharge this is
            }
        };

        if ($router_name == 'balance') {
            $markWrote();
            return self::rechargeBalance($c, $p, $gateway, $channel);
        }

        if ($router_name == 'Custom Balance') {
            $markWrote();
            return self::rechargeCustomBalance($c, $p, $gateway, $channel);
        }

        /**
         * 1 Customer only can have 1 PPPOE and 1 Hotspot Plan, 1 prepaid and 1 postpaid
         */

        // the router this recharge is for, by its id: a rename keeps it, so a rename made while this runs can be told from a
        // plan moved to another router (see followRename)
        // (the plan's own router, as read with the plan, is not looked up twice)
        $router_id = ($plan_routers !== null && (string) $router_name === $plan_routers) ? $plan_router_id : self::routerId($router_name);
        $findActive = function ($routers) use ($p, $isVoucher, $c, $id_customer) {
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
                ->where('tbl_user_recharges.routers', $routers)
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
            return $query->find_one();
        };
        // the router by its id when it is known: renamed since the caller read its name, its active recharge was moved to the new
        // name with it, so it is found there and extended (followRename then moves this save to the new name too) rather than a
        // second one; and a router that took the old name afterwards is not mistaken for it
        // both reads in one transaction (one snapshot): a rename between them would find no active recharge and make a second one
        // with a fresh expiry. Without a known router id the name is used as given
        $snapDb = ORM::get_db();
        $snapOwn = !$snapDb->inTransaction();
        if ($snapOwn) {
            try {
                // the snapshot is taken by the first read: both reads see the same state
                $snapDb->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
                $snapDb->beginTransaction();
            } catch (Throwable $e) {
                $snapOwn = false;
            }
        }
        try {
            $renamed = self::renamedTo($router_name, $router_id, $plan_id);
            $b = $findActive($renamed !== null ? $renamed : $router_name);
        } finally {
            if ($snapOwn && $snapDb->inTransaction()) {
                $snapDb->commit();
            }
        }

        $markWrote(); // the hook below may write
        run_hook("recharge_user");

        if ($p['validity_unit'] == 'Months') {
            $date_exp = date("Y-m-d", strtotime('+' . $p['validity'] . ' month'));
        } else if ($p['validity_unit'] == 'Period') {
            $current_date = new DateTime($date_only);
            $exp_date = clone $current_date;
            $exp_date->modify('first day of next month');
            $exp_date->setDate($exp_date->format('Y'), $exp_date->format('m'), $day_exp);

            $min_days = 7 * $p['validity'];
            $max_days = 35 * $p['validity'];

            $days_until_exp = $exp_date->diff($current_date)->days;

            // If less than min_days away, move to the next period
            while ($days_until_exp < $min_days) {
                $exp_date->modify('+1 month');
                $days_until_exp = $exp_date->diff($current_date)->days;
            }

            // If more than max_days away, move to the previous period
            while ($days_until_exp > $max_days) {
                $exp_date->modify('-1 month');
                $days_until_exp = $exp_date->diff($current_date)->days;
            }

            // Final check to ensure we're not less than min_days or in the past
            if ($days_until_exp < $min_days || $exp_date <= $current_date) {
                $exp_date->modify('+1 month');
            }

            // Adjust for multiple periods
            if ($p['validity'] > 1) {
                $exp_date->modify('+' . ($p['validity'] - 1) . ' months');
            }

            $date_exp = $exp_date->format('Y-m-d');
            $time = "23:59:59";
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
            if ($b['namebp'] == $p['name_plan'] && $b['status'] == 'on' && $config['extend_expiry'] == 'yes') {
                // if it same internet plan, expired will extend
                switch ($p['validity_unit']) {
                    case 'Months':
                        $date_exp = date("Y-m-d", strtotime($b['expiration'] . ' +' . $p['validity'] . ' months'));
                        $time = $b['time'];
                        break;
                    case 'Period':
                        $date_exp = date("Y-m-$day_exp", strtotime($b['expiration'] . ' +' . $p['validity'] . ' months'));
                        $time = date("23:59:00");
                        break;
                    case 'Days':
                        $date_exp = date("Y-m-d", strtotime($b['expiration'] . ' +' . $p['validity'] . ' days'));
                        $time = $b['time'];
                        break;
                    case 'Hrs':
                        $datetime = explode(' ', date("Y-m-d H:i:s", strtotime($b['expiration'] . ' ' . $b['time'] . ' +' . $p['validity'] . ' hours')));
                        $date_exp = $datetime[0];
                        $time = $datetime[1];
                        break;
                    case 'Mins':
                        $datetime = explode(' ', date("Y-m-d H:i:s", strtotime($b['expiration'] . ' ' . $b['time'] . ' +' . $p['validity'] . ' minutes')));
                        $date_exp = $datetime[0];
                        $time = $datetime[1];
                        break;
                }
            } else {
                $isChangePlan = true;
            }

            //if ($b['status'] == 'on') {
            $dvc = Package::getDevice($p);
            if ($_app_stage != 'Demo') {
                try {
                    if (file_exists($dvc)) {
                        // No router lock here on purpose: it is one lock for every router, and would make all recharges wait
                        // on each other. What holds instead: onRouterNow() gives the device the plan's router by its id, under
                        // the name it has at that moment, so a rename made before then is followed; a rename in the few
                        // milliseconds between that and the device's own lookup makes the lookup miss, and the failure is sent
                        // as the alert below to sync by hand. The rows saved below follow a rename through followRename().
                        self::dispatchCustomer($dvc, $p, $c, $plan_router_id, $id_customer);
                    } else {
                        new Exception(Lang::T("Devices Not Found"));
                    }
                } catch (Throwable $e) {
                    Message::sendTelegram(
                        "System Error. When activate Package. You need to sync manually\n" .
                            "Router: $router_name\n" .
                            "Customer: u$c[username]\n" .
                            "Plan: p$p[name_plan]\n" .
                            $e->getMessage() . "\n" .
                            $e->getTraceAsString()
                    );
                } catch (Exception $e) {
                    Message::sendTelegram(
                        "System Error. When activate Package. You need to sync manually\n" .
                            "Router: $router_name\n" .
                            "Customer: u$c[username]\n" .
                            "Plan: p$p[name_plan]\n" .
                            $e->getMessage() . "\n" .
                            $e->getTraceAsString()
                    );
                }
            }
            //}

            // if contains 'mikrotik', 'hotspot', 'pppoe', 'radius' then recharge it
            if (Validator::containsKeyword($p['device'])) {
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
                self::followRename('tbl_user_recharges', $b->id(), $router_name, $plan_id, $router_id);
            }

            // insert table transactions
            $t = ORM::for_table('tbl_transactions')->create();
            $t->invoice = $inv = "INV-" . Package::_raid();
            $t->username = $c['username'];
            $t->user_id = $id_customer;
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
            self::followRename('tbl_transactions', $t->id(), $router_name, $plan_id, $router_id);

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
            if ($_app_stage != 'Demo') {
                try {
                    if (file_exists($dvc)) {
                        // No router lock here on purpose: it is one lock for every router, and would make all recharges wait
                        // on each other. What holds instead: onRouterNow() gives the device the plan's router by its id, under
                        // the name it has at that moment, so a rename made before then is followed; a rename in the few
                        // milliseconds between that and the device's own lookup makes the lookup miss, and the failure is sent
                        // as the alert below to sync by hand. The rows saved below follow a rename through followRename().
                        self::dispatchCustomer($dvc, $p, $c, $plan_router_id, $id_customer);
                    } else {
                        new Exception(Lang::T("Devices Not Found"));
                    }
                } catch (Throwable $e) {
                    Message::sendTelegram(
                        "System Error. When activate Package. You need to sync manually\n" .
                            "Router: $router_name\n" .
                            "Customer: u$c[username]\n" .
                            "Plan: p$p[name_plan]\n" .
                            $e->getMessage() . "\n" .
                            $e->getTraceAsString()
                    );
                } catch (Exception $e) {
                    Message::sendTelegram(
                        "System Error. When activate Package. You need to sync manually\n" .
                            "Router: $router_name\n" .
                            "Customer: u$c[username]\n" .
                            "Plan: p$p[name_plan]\n" .
                            $e->getMessage() . "\n" .
                            $e->getTraceAsString()
                    );
                }
            }

            // if contains 'mikrotik', 'hotspot', 'pppoe', 'radius' then recharge it
            if (Validator::containsKeyword($p['device'])) {
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
                self::followRename('tbl_user_recharges', $d->id(), $router_name, $plan_id, $router_id);
            }

            // insert table transactions
            $t = ORM::for_table('tbl_transactions')->create();
            $t->invoice = $inv = "INV-" . Package::_raid();
            $t->username = $c['username'];
            $t->user_id = $id_customer;
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
            self::followRename('tbl_transactions', $t->id(), $router_name, $plan_id, $router_id);

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
        
        // Process referral rewards if the class exists and it's not a balance recharge
        if (!$isVoucher 
            && class_exists('Referral') 
            && Referral::getSetting('enable_referral', 'no') === 'yes' 
            && $router_name != 'balance'
        ) {
            try {
                // Get the price of the plan for purchase reward calculation
                $price = isset($p['price']) ? $p['price'] : 0;
                
                // Check if purchase meets minimum requirement
                if (Referral::purchaseQualifiesForReward($price)) {
                    // Check if the user was referred
                    $referrer = Referral::getReferrer($id_customer);
                    if ($referrer) {
                        // If require_purchase is yes, create the rewards now
                        if (Referral::getSetting('require_purchase', 'yes') === 'yes') {
                            // Create rewards for both referrer and referred user with purchase amount
                            Referral::createReward($referrer->id, $referrer->referrer_id, $id_customer, $price, 'referrer');
                            Referral::createReward($referrer->id, $referrer->referrer_id, $id_customer, $price, 'referred');
                        }
        
                        // Find and process pending referrer rewards
                        $referrerReward = ORM::for_table('tbl_referral_rewards')
                            ->where('referred_id', $id_customer)
                            ->where('referrer_id', $referrer->referrer_id)
                            ->where('status', 'pending')
                            ->where('reward_type', 'balance')
                            ->where('reward_for', 'referrer')
                            ->find_one();
        
                        if ($referrerReward) {
                            Referral::processReferrerReward($referrerReward->id);
                        }
                        
                        // Find and process pending referred user rewards
                        $referredReward = ORM::for_table('tbl_referral_rewards')
                            ->where('referred_id', $id_customer)
                            ->where('status', 'pending')
                            ->where('reward_type', 'balance')
                            ->where('reward_for', 'referred')
                            ->find_one();
                            
                        if ($referredReward) {
                            Referral::processReferredReward($referredReward->id);
                        }
                    }
                }
            } catch (Exception $e) {
                Message::sendTelegram(
                    "Referral Reward Processing Error\n" .
                    "Customer ID: $id_customer\n" .
                    "Plan: {$p['name_plan']}\n" .
                    $e->getMessage() . "\n" .
                    $e->getTraceAsString()
                );
            }
        }
        
        Message::sendInvoice($c, $t);
        if ($trx) {
            $trx->trx_invoice = $inv;
        }
        return $inv;
    }

    public static function rechargeBalance($customer, $plan, $gateway, $channel, $note = '')
    {
        global $admin, $config;
        // insert table transactions
        $t = ORM::for_table('tbl_transactions')->create();
        $t->invoice = $inv = "INV-" . Package::_raid();
        $t->username = $customer['username'];
        $t->user_id = $customer['id'];
        $t->plan_name = $plan['name_plan'];
        $t->price = $plan['price'];
        $t->recharged_on = date("Y-m-d");
        $t->recharged_time = date("H:i:s");
        $t->expiration = date("Y-m-d");
        $t->time = date("H:i:s");
        $t->method = "$gateway - $channel";
        $t->routers = 'balance';
        $t->type = "Balance";
        $t->note = $note;
        if ($admin) {
            $t->admin_id = ($admin['id']) ? $admin['id'] : '0';
        } else {
            $t->admin_id = '0';
        }
        $t->save();

        $balance_before = $customer['balance'];
        Balance::plus($customer['id'], $plan['price']);
        $balance = $customer['balance'] + $plan['price'];

        $textInvoice = Lang::getNotifText('invoice_balance');
        $textInvoice = str_replace('[[company_name]]', $config['CompanyName'], $textInvoice);
        $textInvoice = str_replace('[[address]]', $config['address'], $textInvoice);
        $textInvoice = str_replace('[[phone]]', $config['phone'], $textInvoice);
        $textInvoice = str_replace('[[invoice]]', $inv, $textInvoice);
        $textInvoice = str_replace('[[date]]', Lang::dateTimeFormat(date("Y-m-d H:i:s")), $textInvoice);
        $textInvoice = str_replace('[[trx_date]]', Lang::dateTimeFormat(date("Y-m-d H:i:s")), $textInvoice);
        $textInvoice = str_replace('[[payment_gateway]]', $gateway, $textInvoice);
        $textInvoice = str_replace('[[payment_channel]]', $channel, $textInvoice);
        $textInvoice = str_replace('[[type]]', 'Balance', $textInvoice);
        $textInvoice = str_replace('[[plan_name]]', $plan['name_plan'], $textInvoice);
        $textInvoice = str_replace('[[plan_price]]', Lang::moneyFormat($plan['price']), $textInvoice);
        $textInvoice = str_replace('[[name]]', $customer['fullname'], $textInvoice);
        $textInvoice = str_replace('[[user_name]]', $customer['username'], $textInvoice);
        $textInvoice = str_replace('[[user_password]]', $customer['password'], $textInvoice);
        $textInvoice = str_replace('[[footer]]', $config['note'], $textInvoice);
        $textInvoice = str_replace('[[balance_before]]', Lang::moneyFormat($balance_before), $textInvoice);
        $textInvoice = str_replace('[[balance]]', Lang::moneyFormat($balance), $textInvoice);

        if ($config['user_notification_payment'] == 'sms') {
            Message::sendSMS($customer['phonenumber'], $textInvoice);
        } else if ($config['user_notification_payment'] == 'wa') {
            Message::sendWhatsapp($customer['phonenumber'], $textInvoice);
        } else if ($config['user_notification_payment'] == 'email') {
            Message::sendEmail($customer['email'], '[' . $config['CompanyName'] . '] ' . Lang::T("Invoice") . ' ' . $inv, $textInvoice);
        }
        return $t->id();
    }

    public static function rechargeCustomBalance($customer, $plan, $gateway, $channel, $note = '')
    {
        global $admin, $config;
        $plan = ORM::for_table('tbl_payment_gateway')
            ->where('username', $customer['username'])
            ->where('routers', 'Custom Balance')
            ->where('status', '1')
            ->find_one();
        if (!$plan) {
            return false;
        }
        // insert table transactions
        $t = ORM::for_table('tbl_transactions')->create();
        $t->invoice = $inv = "INV-" . Package::_raid();
        $t->username = $customer['username'];
        $t->user_id = $customer['id'];
        $t->plan_name = 'Custom Balance';
        $t->price = $plan['price'];
        $t->recharged_on = date("Y-m-d");
        $t->recharged_time = date("H:i:s");
        $t->expiration = date("Y-m-d");
        $t->time = date("H:i:s");
        $t->method = "$gateway - $channel";
        $t->routers = 'balance';
        $t->type = "Balance";
        $t->note = $note;
        if ($admin) {
            $t->admin_id = ($admin['id']) ? $admin['id'] : '0';
        } else {
            $t->admin_id = '0';
        }
        $t->save();

        $balance_before = $customer['balance'];
        Balance::plus($customer['id'], $plan['price']);
        $balance = $customer['balance'] + $plan['price'];

        $textInvoice = Lang::getNotifText('invoice_balance');
        $textInvoice = str_replace('[[company_name]]', $config['CompanyName'], $textInvoice);
        $textInvoice = str_replace('[[address]]', $config['address'], $textInvoice);
        $textInvoice = str_replace('[[phone]]', $config['phone'], $textInvoice);
        $textInvoice = str_replace('[[invoice]]', $inv, $textInvoice);
        $textInvoice = str_replace('[[date]]', Lang::dateTimeFormat(date("Y-m-d H:i:s")), $textInvoice);
        $textInvoice = str_replace('[[trx_date]]', Lang::dateTimeFormat(date("Y-m-d H:i:s")), $textInvoice);
        $textInvoice = str_replace('[[payment_gateway]]', $gateway, $textInvoice);
        $textInvoice = str_replace('[[payment_channel]]', $channel, $textInvoice);
        $textInvoice = str_replace('[[type]]', 'Balance', $textInvoice);
        $textInvoice = str_replace('[[plan_name]]', $plan['name_plan'], $textInvoice);
        $textInvoice = str_replace('[[plan_price]]', Lang::moneyFormat($plan['price']), $textInvoice);
        $textInvoice = str_replace('[[name]]', $customer['fullname'], $textInvoice);
        $textInvoice = str_replace('[[user_name]]', $customer['username'], $textInvoice);
        $textInvoice = str_replace('[[user_password]]', $customer['password'], $textInvoice);
        $textInvoice = str_replace('[[footer]]', $config['note'], $textInvoice);
        $textInvoice = str_replace('[[balance_before]]', Lang::moneyFormat($balance_before), $textInvoice);
        $textInvoice = str_replace('[[balance]]', Lang::moneyFormat($balance), $textInvoice);

        if ($config['user_notification_payment'] == 'sms') {
            Message::sendSMS($customer['phonenumber'], $textInvoice);
        } else if ($config['user_notification_payment'] == 'wa') {
            Message::sendWhatsapp($customer['phonenumber'], $textInvoice);
        } else if ($config['user_notification_payment'] == 'email') {
            Message::sendEmail($customer['email'], '[' . $config['CompanyName'] . '] ' . Lang::T("Invoice") . ' ' . $inv, $textInvoice);
        }
        return $t->id();
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

    /**
     * A plan on a Mikrotik router that is disabled (Network > Routers): PHPNuxBill does not use that router until it is enabled
     * again, so no command is sent to it. The recharge is still saved; Sync customers on the router sends it later.
     */
    public static function routerOff($plan)
    {
        $name = (string) ($plan['routers'] ?? '');
        if ($name === '' || stripos((string) ($plan['device'] ?? ''), 'Mikrotik') !== 0) {
            return false;
        }
        $r = ORM::for_table('tbl_routers')->select('enabled')->where('name', $name)->find_one();
        return $r && (int) $r['enabled'] !== 1;
    }

    /** The id of the router with this name, or null (no such router: "radius", a balance, or a name no longer in use) */
    public static function routerId($router_name)
    {
        $name = (string) $router_name;
        if ($name === '' || in_array($name, ['radius', 'balance', 'Custom Balance'], true)) {
            return null;
        }
        try {
            $r = ORM::for_table('tbl_routers')->select('id')->where('name', $name)->find_one();
            return $r ? (int) $r['id'] : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * The name a router has now when it no longer has $router_name (renamed since the caller read it), else null. Followed only
     * by the router's id, when it was known by that name here. Without one nothing is followed: a name no router has could be
     * a router renamed or one deleted after its plan was moved to another router (a payment or voucher made for it before),
     * and the plan cannot tell the two apart, so the request keeps the name it was made for rather than go to another router.
     */
    private static function renamedTo($router_name, $router_id, $plan_id)
    {
        $name = (string) $router_name;
        if (!$router_id || $name === '' || in_array($name, ['radius', 'balance', 'Custom Balance'], true)) {
            return null;
        }
        try {
            $r = ORM::for_table('tbl_routers')->select('name')->find_one($router_id);
            $now = $r ? (string) $r['name'] : '';
        } catch (Throwable $e) {
            return null;
        }
        return ($now !== '' && $now !== $name) ? $now : null;
    }

    /**
     * The plan as the device is to be given it: when the plan's router (by its id) has been renamed since the plan was read, a
     * copy naming the router as it is called now (the plan itself is not changed or saved); otherwise the plan as it is
     */
    private static function onRouterNow($plan, $router_id)
    {
        if (!$router_id || !is_object($plan)) {
            return $plan;
        }
        // The device finds the router by NAME. A router known by its id that cannot be read now (deleted, or the lookup
        // failed) is not answered with the plan's old name: a router created since under that name would take the customer.
        // Thrown instead: the caller sends it as the failure to sync by hand.
        try {
            $r = ORM::for_table('tbl_routers')->select('name')->find_one($router_id);
        } catch (Throwable $e) {
            throw new Exception(Lang::T('Router not found') . ': ' . $plan['routers']);
        }
        if (!$r) {
            throw new Exception(Lang::T('Router not found') . ': ' . $plan['routers']);
        }
        if ((string) $r['name'] === (string) $plan['routers']) {
            return $plan;
        }
        $copy = clone $plan;
        $copy->routers = (string) $r['name'];
        return $copy;
    }

    /**
     * Sends a recharge's customer to its plan's device (add_customer), the router named as it is now (onRouterNow), under the
     * customer credential lock rechargeUser() holds. MikrotikVpn::add_customer takes its own GET_LOCKs: on a database that
     * releases the lock it holds when it takes another (MikrotikVpn::nestedLocks() false) that would silently free this one, so
     * there the credential lock is released before a VPN device is called (the one case a password change that commits during
     * the device write is not excluded; the VPN device's own locks hold instead).
     */
    private static function dispatchCustomer($dvc, $plan, $customer, $router_id, $customer_id)
    {
        $dp = self::onRouterNow($plan, $router_id);
        // the device finds the router by name: it must still be the router read by id (a name reused meanwhile is refused)
        if ($router_id && stripos((string) ($dp['device'] ?? ''), 'Mikrotik') === 0 && self::routerId($dp['routers']) !== (int) $router_id) {
            throw new Exception(Lang::T('Router not found') . ': ' . $dp['routers']);
        }
        if (self::routerOff($dp)) {
            throw new Exception(Lang::T('The router is disabled: nothing was sent to it. Use Sync customers once it is enabled again.'));
        }
        if (self::$pwState === 'busy') {
            throw new Exception(Lang::T('A password change for this customer is in progress: nothing was sent to the device. Use Sync customers to send it.'));
        }
        require_once $dvc;
        if (self::$pwState === 'held' && $plan['device'] === 'MikrotikVpn' && !MikrotikVpn::nestedLocks()) {
            self::passwordLock($customer_id, false);
            self::$pwState = 'released';
        }
        (new $plan['device'])->add_customer($customer, $dp);
    }

    /**
     * A router renamed while this recharge was being saved: the rename moves every row saved before it to the new name, but a
     * row written after it with the name read earlier would keep the old one. Checked once the row is saved, so one or the
     * other catches it: a row on the old name follows its plan to the router the plan names now, as long as that router is the one
     * (by id) the caller read, even when another router has taken the old name since. $id is one row's id or a list of them (a voucher batch).
     * $router_id is the id the router had under $router_name when the caller read it: the row follows only that router
     * (renamed), never a plan moved to another router while the old one was deleted. Without it nothing is moved (the two
     * cannot be told apart, see renamedTo), so the row keeps the router it was made for.
     */
    public static function followRename($table, $id, $router_name, $plan_id, $router_id = null)
    {
        $ids = array_values(array_filter(array_map('intval', (array) $id)));
        if (!$ids || !$router_id || !in_array($table, ['tbl_user_recharges', 'tbl_transactions', 'tbl_voucher', 'tbl_payment_gateway'], true)
            || in_array($router_name, ['radius', 'balance', 'Custom Balance'], true)) {
            return;
        }
        $args = [$plan_id, $router_name, (int) $router_id];
        try {
            // the row follows the router it was made for, by its id, to the name that router has now: not to wherever the plan
            // points today (the plan may have moved to another router since)
            ORM::raw_execute("UPDATE `$table` t JOIN `tbl_routers` r2 ON r2.id = ? SET t.routers = r2.name
                WHERE t.id IN (" . implode(',', $ids) . ") AND t.routers = ? AND r2.name <> t.routers", [(int) $router_id, $router_name]);
        } catch (Throwable $e) {
            // the recharge itself is saved. A failed move is tried once more (a lock wait or a dropped connection is usually brief),
            // so a rename that committed meanwhile does not leave the row on the old name; still failing, it keeps the saved row
            try {
                usleep(200000);
                ORM::raw_execute("UPDATE `$table` t JOIN `tbl_routers` r2 ON r2.id = ? SET t.routers = r2.name
                    WHERE t.id IN (" . implode(',', $ids) . ") AND t.routers = ? AND r2.name <> t.routers", [(int) $router_id, $router_name]);
            } catch (Throwable $e2) {
                _log('Router rename not followed by ' . $table . ' ' . implode(',', $ids) . ': ' . $e2->getMessage(), 'Package');
            }
        }
    }

    /**
     * Call before a customer's portal password is changed (Change password, Forgot password). A PPPoE or VPN connection is
     * dialled by the customer's own router or ONU with the password saved in it; without a separate PPPoE password that is the
     * portal password, so changing it would lock the connection out at its next reconnect. The current password is kept as the
     * PPPoE password first, so the connection goes on working and only the portal password changes (an admin can change the
     * PPPoE one on the customer's page). Each device then keeps its own credential: a Mikrotik secret takes the PPPoE password,
     * a Mikrotik or RADIUS hotspot user the new one. A PPPoE plan on RADIUS alone is not kept when a hotspot plan signs in through
     * the same RADIUS database (they share one password entry there: the PPPoE plan writes the PPPoE password into it, which
     * would take the new one away from the hotspot sign-in), but a PPPoE or VPN plan on its own device (a Mikrotik secret) is
     * kept whatever else is shared: sendPassword then says the shared RADIUS entry holds the PPPoE password. Does not save: the caller saves with the new password.
     */
    public static function keepDialPassword($customer)
    {
        if (!empty($customer['pppoe_password']) || (string) $customer['password'] === '') {
            return;
        }
        $dialOther = $radiusDial = $radiusOther = false;
        foreach (ORM::for_table('tbl_user_recharges')->where('customer_id', $customer['id'])->where('status', 'on')->find_many() as $tur) {
            $p = ORM::for_table('tbl_plans')->where('id', $tur['plan_id'])->find_one();
            if (!$p) {
                continue;
            }
            $type = strtoupper((string) $p['type']);
            $device = (string) $p['device'] !== '' ? (string) $p['device'] : (!empty($p['is_radius']) ? 'Radius' : '');
            if (($type === 'PPPOE' || $type === 'VPN') && in_array($device, ['Radius', 'RadiusALE', 'RadiusChillispot'], true)) {
                $radiusDial = true; // writes the PPPoE password (when there is one) as the account's Cleartext-Password
            } else {
                if ($type !== 'PPPOE' && $type !== 'VPN' && in_array($device, ['Radius', 'RadiusALE'], true)) {
                    $radiusOther = true; // writes the portal password as that same Cleartext-Password
                }
                if ($type === 'PPPOE' || $type === 'VPN') {
                    $dialOther = true; // a dial password of its own (Mikrotik PPPoE / VPN secret, or a VPN on a RADIUS device)
                }
            }
        }
        // Kept for a dial connection with a credential of its own always (the shared RADIUS row only means that plan's
        // PPPoE and hotspot logins cannot both be told, which sendPassword says); for a RADIUS-only dial connection kept unless
        // a RADIUS hotspot plan shares its row. sendPassword's secrets use the kept PPPoE password, never the portal one.
        if ($dialOther || ($radiusDial && !$radiusOther)) {
            $customer->pppoe_password = $customer['password'];
        }
    }

    /**
     * Sends a customer's new password to the device of each active plan, after a password change or reset. Returns the errors
     * (empty when every device took it), one per plan, so a plan that fails does not stop the others. Each kind of device is
     * told in the way that changes only the credentials, with the customer as given (the new password, even before it is saved):
     * - Voucher keeps no account (its add_customer would issue the customer a new voucher): skipped;
     * - a plan on a disabled router: nothing is sent, said as an error (Sync customers sends it once the router is enabled);
     * - a RADIUS device's add_customer starts a new period from today, and its sync_customer (customerAddPlan) disconnects the
     *   customer and deletes their accounting: an account gets only its sign-in password (customerPassword, leaving
     *   Simultaneous-Use, Port-Limit and the rest alone). One without its plan group is not re-added (that would disconnect
     *   the customer and delete their accounting): its password is written and said as an error, Sync customers restores it;
     * - a Mikrotik hotspot user is looked up and given the new password in place (its usage counters kept); a missing one
     *   is added (never by removing one first), and one made meanwhile is changed in place;
     * - a Mikrotik PPPoE secret is given its dial password in place, the router's refusal said as an error (a refused lookup
     *   too: nothing is made then); a missing one is created (one made meanwhile is changed in place);
     * - a Mikrotik VPN secret likewise (its profile, address and forwards untouched); a missing one is made by add_customer;
     * - any other device: add_customer.
     * The caller holds passwordLock() for the customer from reading it to saving it.
     */
    public static function sendPassword($customer)
    {
        global $_app_stage;
        $errors = [];
        if (strtolower((string) $_app_stage) === 'demo') {
            return $errors;
        }
        $turs = ORM::for_table('tbl_user_recharges')->where('customer_id', $customer['id'])->where('status', 'on')->find_many();
        // a RADIUS PPPoE plan and a RADIUS hotspot plan of one username share one Cleartext-Password row: the PPPoE plan writes
        // the PPPoE password into it, the hotspot plan the portal one. When they differ, the PPPoE password is written for both
        // (the connection its router dials keeps working; a hotspot sign-in is typed, and can be told), and said as an error
        // each plan is read once, for the check below and for the sending after it; one that cannot be read keeps its exception,
        // said as that plan's own error while the others are still sent
        $plans = [];
        foreach ($turs as $tur) {
            try {
                $plans[$tur['id']] = [ORM::for_table('tbl_plans')->where('id', $tur['plan_id'])->find_one(), null];
            } catch (Throwable $e) {
                $plans[$tur['id']] = [null, $e];
            }
        }
        $hsCustomer = null;
        $pppoePass = (string) $customer['pppoe_password'];
        if ($pppoePass !== '' && $pppoePass !== (string) $customer['password']) {
            $share = [];
            foreach ($turs as $tur) {
                try {
                    // only a recharge that is sent the password below counts: an expired one is skipped there, so it cannot conflict
                    if ($plans[$tur['id']][1] === null && strtotime($tur['expiration'] . ' ' . $tur['time']) > time()) {
                        $share[(string) self::radiusShare($plans[$tur['id']][0])] = true;
                    }
                } catch (Throwable $e) {
                    // a plan that cannot be read is said as its own error below
                }
            }
            if (isset($share['dial'], $share['other'])) {
                $errors[] = Lang::T('Hotspot and PPPoE share one RADIUS password: the PPPoE password was written, so the hotspot sign-in uses the PPPoE password, not the new one. Give the customer one password for both, or change the PPPoE password on the customer\'s page.');
                if (is_object($customer)) {
                    $hsCustomer = clone $customer; // not saved: only what is sent to the RADIUS hotspot plan
                    $hsCustomer->password = $pppoePass;
                } else {
                    $hsCustomer = $customer;
                    $hsCustomer['password'] = $pppoePass;
                }
            }
        }
        foreach ($turs as $tur) {
            $p = null;
            try {
                // read above, so a plan that cannot be read is said as that plan's error and the others are still sent
                if ($plans[$tur['id']][1] !== null) {
                    throw $plans[$tur['id']][1];
                }
                $p = $plans[$tur['id']][0];
                if (!$p) {
                    // an active recharge whose plan is gone is a failure to say (it may hold a device account), not to skip
                    throw new Exception(Lang::T('The plan of this recharge no longer exists') . ' (' . Lang::T('Plan') . ' #' . $tur['plan_id'] . ')');
                }
                // a plan saved before the device column existed has none yet: filled in (from its type) now, so the checks below
                // and the router of the recharge apply to it as to any other
                if ((string) $p['device'] === '' && is_object($p)) {
                    self::getDevice($p);
                }
                if ($p['device'] === 'Voucher') {
                    continue;
                }
                // a Mikrotik account is on the router its recharge was made for: a plan moved to another router meanwhile does not
                // make that one the account's destination until the recharge itself is migrated
                if (is_object($p) && stripos((string) $p['device'], 'Mikrotik') === 0 && (string) $tur['routers'] !== ''
                    && (string) $tur['routers'] !== (string) $p['routers'] && !in_array((string) $tur['routers'], ['radius', 'balance', 'Custom Balance'], true)) {
                    $p = clone $p;
                    $p->routers = (string) $tur['routers'];
                }
                $dvc = self::getDevice($p);
                if ($dvc === '' || $dvc === 'none' || !file_exists($dvc)) {
                    throw new Exception(Lang::T('Devices Not Found'));
                }
                if (self::routerOff($p)) {
                    throw new Exception(Lang::T('The router is disabled: nothing was sent to it. Use Sync customers once it is enabled again.'));
                }
                require_once $dvc;
                $dev = new $p['device'];
                // a recharge past its expiry (the cron has not switched it off yet) has no account to bring back: a missing secret
                // or user is not created for it, only one that exists gets the new password
                $mayAdd = strtotime($tur['expiration'] . ' ' . $tur['time']) > time();
                if ($p['device'] === 'MikrotikHotspot') {
                    self::hotspotPassword($dev, $customer, $p, $mayAdd);
                } elseif ($p['device'] === 'MikrotikPppoe') {
                    self::pppoePassword($dev, $customer, $p, $mayAdd);
                } elseif ($p['device'] === 'MikrotikVpn') {
                    self::vpnPassword($dev, $customer, $p, $mayAdd);
                } elseif (method_exists($dev, 'customerUpsert') || method_exists($dev, 'customerPassword')) {
                    // past its expiry the account was switched off: writing the password would bring its credential back
                    if ($mayAdd) {
                        self::radiusPassword($dev, ($hsCustomer !== null && self::radiusShare($p) === 'other') ? $hsCustomer : $customer, $p, $tur);
                    }
                } elseif ($mayAdd) {
                    // any other driver has no in-place password change: add_customer() would create the account or renew its access,
                    // which a recharge past its expiry must not get back
                    $dev->add_customer($customer, $p);
                }
            } catch (Throwable $e) {
                $errors[] = ($p ? $p['name_plan'] : Lang::T('Recharge') . ' #' . $tur['id']) . ': ' . $e->getMessage();
            }
        }
        return $errors;
    }

    /**
     * One password change at a time for a customer (a database lock, so across every PHP worker): taken before the customer is
     * read for a change, held until it is saved and sent, so two changes at once (account page, Forgot password) cannot leave
     * one password saved and the other on the routers. False when not got within $wait seconds. passwordLock($id, false)
     * releases it (it also ends with the request: the connection is not persistent). While it is held nothing else may take a
     * GET_LOCK on a database that releases the held lock when it takes another (MySQL before 5.7.5, MariaDB before 10.0.2):
     * sendPassword's VPN branch checks MikrotikVpn::nestedLocks() for that, the only lock taken on its way.
     */
    public static function passwordLock($customer_id, $on = true, $wait = 20)
    {
        $name = 'phpnuxbill_pw_c_' . (int) $customer_id;
        // taken again by the same request (a recharge started from a recharge_user hook): counted here instead of asking the database
        // again, which on a database that holds one lock at a time (MySQL before 5.7.5, MariaDB before 10.0.2) would free the
        // outer one, and whose first release would free it for the outer recharge on any database
        static $held = [];
        try {
            if (!$on) {
                if (($held[$name] ?? 0) > 1) {
                    $held[$name]--;
                    return true;
                }
                unset($held[$name]);
                ORM::getDb()->query("SELECT RELEASE_LOCK('$name')");
                return true;
            }
            if (($held[$name] ?? 0) > 0) {
                $held[$name]++;
                return true;
            }
            $got = (int) ORM::getDb()->query("SELECT GET_LOCK('$name', " . (int) $wait . ")")->fetchColumn() === 1;
            if ($got) {
                $held[$name] = 1;
            }
            return $got;
        } catch (Throwable $e) {
            return !$on;
        }
    }

    /**
     * A RADIUS account's new password, and only that (customerPassword: the Cleartext-Password row; the plan's
     * Simultaneous-Use, Port-Limit, limits and expiry stay as they are). An account without its plan group is not restored here:
     * customerAddPlan (sync_customer) disconnects the customer and deletes their accounting history, so the password is
     * written and the missing group is said as an error, for Sync customers to restore. A device without customerPassword
     * (a third-party one): customerUpsert, as before.
     */
    private static function radiusPassword($dev, $customer, $plan, $tur)
    {
        $set = function () use ($dev, $customer, $plan) {
            if (method_exists($dev, 'customerPassword')) {
                $dev->customerPassword($customer, $plan);
            } else {
                $dev->customerUpsert($customer, $plan);
            }
        };
        if (!method_exists($dev, 'getTableUserPackage')) {
            $set();
            return;
        }
        // the built-in driver names a plan's group plan_<id>: that group must be the account's, not any other. A third-party driver
        // names its groups its own way, so for it any group counts
        $groupQuery = $dev->getTableUserPackage()->where_equal('username', $customer['username']);
        $hasGroup = (get_class($dev) === 'Radius' && !empty($plan['id']))
            ? $groupQuery->where_equal('groupname', 'plan_' . $plan['id'])->count() > 0
            : $groupQuery->where_not_equal('groupname', '')->count() > 0;
        $set();
        if (!$hasGroup) {
            throw new Exception(Lang::T('The new password was written to RADIUS, but the account has no plan group there. Use Sync customers to restore it.'));
        }
    }

    /** The client for a Mikrotik plan's router, through the device's own info() and getClient() */
    private static function deviceClient($dev, $plan)
    {
        $router = $dev->info($plan['routers']);
        if (!$router) {
            throw new Exception(Lang::T('Router not found') . ': ' . $plan['routers']);
        }
        return $dev->getClient($router['ip_address'], $router['username'], $router['password']);
    }

    /** The .id of the hotspot user with this name ('' when there is none); a refused lookup is thrown */
    private static function hotspotUserId($client, $username)
    {
        $print = new \PEAR2\Net\RouterOS\Request('/ip/hotspot/user/print');
        $print->setArgument('.proplist', '.id');
        $print->setQuery(\PEAR2\Net\RouterOS\Query::where('name', $username));
        $res = $client->sendSync($print);
        if (($err = RouterApi::error($res)) !== null) {
            throw new Exception($err);
        }
        $res->rewind(); // error() iterated the replies: back to the first, or getProperty() has no reply to read
        return (string) $res->getProperty('.id');
    }

    /**
     * A Mikrotik hotspot user's password changed in place; the user is added only when the lookup says it is missing, and never
     * by removing one first (add_customer does): when the router refuses the add because a user of that name was made since
     * the lookup, that user is looked up again and changed in place, so the usage it has recorded meanwhile is kept
     */
    private static function hotspotPassword($dev, $customer, $plan, $mayAdd = true)
    {
        $client = self::deviceClient($dev, $plan);
        $id = self::hotspotUserId($client, $customer['username']);
        if ($id === '') {
            if (!$mayAdd) {
                return;
            }
            if (!method_exists($dev, 'addHotspotUserChecked')) {
                throw new Exception(Lang::T('Devices Not Found'));
            }
            $addErr = null;
            try {
                $dev->addHotspotUserChecked($client, $plan, $customer); // the router's refusal is thrown
            } catch (Exception $e) {
                $addErr = $e;
            }
            // Looked up again whatever the add said: a plan whose limit type is not known adds nothing, and a user made
            // meanwhile by someone else has its OLD password. Success only when a user is there and the set below, with the new
            // password, is accepted (the set is also what confirms a user the add just made).
            $id = self::hotspotUserId($client, $customer['username']);
            if ($id === '') {
                throw $addErr ?? new Exception(Lang::T('The router refused the command')); // nothing was made
            }
        }
        $set = new \PEAR2\Net\RouterOS\Request('/ip/hotspot/user/set');
        $set->setArgument('numbers', $id);
        $set->setArgument('password', $customer['password']);
        if (($err = RouterApi::error($client->sendSync($set))) !== null) {
            throw new Exception($err); // the user is left as it was, with its old password
        }
    }

    /**
     * A Mikrotik PPPoE secret given its dial password in place (the PPPoE password, else the portal one), the reply checked.
     * A missing one is created; when the router refuses that because a secret of that name was made since the lookup, that
     * secret is looked up again and given only its password (never a profile change or its address cleared)
     */
    private static function pppoePassword($dev, $customer, $plan, $mayAdd = true)
    {
        $client = self::deviceClient($dev, $plan);
        $id = $dev->getIdByCustomer($customer, $client, true); // a refused lookup is thrown: nothing is made then
        if (empty($id)) {
            if (!$mayAdd) {
                return;
            }
            if (!method_exists($dev, 'addPpoeUserChecked')) {
                throw new Exception(Lang::T('Devices Not Found'));
            }
            try {
                $dev->addPpoeUserChecked($client, $plan, $customer); // the router's refusal is thrown
                return;
            } catch (Exception $e) {
                $id = $dev->getIdByCustomer($customer, $client, true);
                if (empty($id)) {
                    throw $e; // refused for another reason: nothing was made
                }
            }
        }
        self::secretPassword($client, $id, $customer);
    }

    /**
     * A Mikrotik VPN secret given its dial password in place, like a PPPoE one: its profile, address and port forwards are left
     * as they are, so a port pool with no address free cannot fail a password change. A missing secret is made by add_customer
     * (which takes the VPN locks). On a database that releases the lock it holds when it takes another (see
     * MikrotikVpn::nestedLocks) that would free the caller's passwordLock, so there it is not made here: said as an error, and
     * Sync customers (or the next recharge) makes it.
     */
    private static function vpnPassword($dev, $customer, $plan, $mayAdd = true)
    {
        $client = self::deviceClient($dev, $plan);
        $id = $dev->getIdByCustomer($customer, $client, true); // a refused lookup is thrown: nothing is made then
        if (!empty($id)) {
            self::secretPassword($client, $id, $customer);
            return;
        }
        if (!$mayAdd) {
            return;
        }
        if (!MikrotikVpn::nestedLocks()) {
            throw new Exception(Lang::T('The VPN secret is not on the router. Use Sync customers to create it.'));
        }
        try {
            $dev->add_customer($customer, $plan);
        } catch (Exception $e) {
            if ($e->getCode() !== MikrotikVpn::PARTIAL) {
                throw $e;
            }
            // the secret was made with the password; only a port forward is missing, finished by the next recharge or sync
        }
    }

    /** /ppp/secret/set of the password only (the PPPoE password, else the portal one), the router's refusal thrown */
    private static function secretPassword($client, $id, $customer)
    {
        $set = new \PEAR2\Net\RouterOS\Request('/ppp/secret/set');
        $set->setArgument('numbers', $id);
        $set->setArgument('password', !empty($customer['pppoe_password']) ? $customer['pppoe_password'] : $customer['password']);
        if (($err = RouterApi::error($client->sendSync($set))) !== null) {
            throw new Exception($err);
        }
    }

    /**
     * Which RADIUS password a plan writes: 'dial' for a PPPoE plan (the PPPoE password, when there is one), 'other' for a
     * hotspot (or other) plan that writes the portal password into that same row, null for anything else
     */
    private static function radiusShare($p)
    {
        if (!$p) {
            return null;
        }
        $type = strtoupper((string) $p['type']);
        $device = (string) $p['device'] !== '' ? (string) $p['device'] : (!empty($p['is_radius']) ? 'Radius' : '');
        // a dialled connection (PPPoE, VPN) writes the dial password, as Radius::customerUpsert() and customerPassword() do
        if (($type === 'PPPOE' || $type === 'VPN') && in_array($device, ['Radius', 'RadiusALE', 'RadiusChillispot'], true)) {
            return 'dial';
        }
        if ($type !== 'PPPOE' && $type !== 'VPN' && in_array($device, ['Radius', 'RadiusALE'], true)) {
            return 'other';
        }
        return null;
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
            // flat (Device.php) or folder style (Device/Device.php)
            if (DeviceLoader::exists($plan['device'])) {
                // a known driver that is not allowed to run (no valid licence) is a denial: never fall back to a flat file path
                return DeviceLoader::runFile($plan['device']) ?? "none";
            }
            return $DEVICE_PATH . DIRECTORY_SEPARATOR . $plan['device'] . '.php'; // legacy: a device the loader does not know
        }
        if ($plan['is_radius'] == 1) {
            $plan->device = 'Radius';
            $plan->save();
            return $DEVICE_PATH . DIRECTORY_SEPARATOR . 'Radius' . '.php';
        }
        if ($plan['type'] == 'VPN') {
            $plan->device = 'MikrotikVpn';
            $plan->save();
            return $DEVICE_PATH . DIRECTORY_SEPARATOR . 'MikrotikVpn' . '.php';
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
