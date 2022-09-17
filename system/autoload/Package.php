<?php

/**
 * PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
 **/


use PEAR2\Net\RouterOS;

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
        global $_c, $_L;
        $date_now = date("Y-m-d H:i:s");
        $date_only = date("Y-m-d");
        $time = date("H:i:s");

        if ($id_customer == '' or $router_name == '' or $plan_id == '') {
            return false;
        }

        $c = ORM::for_table('tbl_customers')->where('id', $id_customer)->find_one();
        $p = ORM::for_table('tbl_plans')->where('id', $plan_id)->where('enabled', '1')->find_one();
        $b = ORM::for_table('tbl_user_recharges')->where('customer_id', $id_customer)->find_one();

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
                if (!$_c['radius_mode']) {
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::removeHotspotUser($client,$c['username']);
                    Mikrotik::addHotspotUser($client,$p,$c);
                }

                $b->customer_id = $id_customer;
                $b->username = $c['username'];
                $b->plan_id = $plan_id;
                $b->namebp = $p['name_plan'];
                $b->recharged_on = $date_only;
                $b->expiration = $date_exp;
                $b->time = $time;
                $b->status = "on";
                $b->method = "$gateway - $channel";
                $b->routers = $router_name;
                $b->type = "Hotspot";
                $b->save();

                // insert table transactions
                $t = ORM::for_table('tbl_transactions')->create();
                $t->invoice = "INV-" . _raid(5);
                $t->username = $c['username'];
                $t->plan_name = $p['name_plan'];
                $t->price = $p['price'];
                $t->recharged_on = $date_only;
                $t->expiration = $date_exp;
                $t->time = $time;
                $t->method = "$gateway - $channel";
                $t->routers = $router_name;
                $t->type = "Hotspot";
                $t->save();
            } else {
                if (!$_c['radius_mode']) {
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::addHotspotUser($client,$p,$c);
                }

                $d = ORM::for_table('tbl_user_recharges')->create();
                $d->customer_id = $id_customer;
                $d->username = $c['username'];
                $d->plan_id = $plan_id;
                $d->namebp = $p['name_plan'];
                $d->recharged_on = $date_only;
                $d->expiration = $date_exp;
                $d->time = $time;
                $d->status = "on";
                $d->method = "$gateway - $channel";
                $d->routers = $router_name;
                $d->type = "Hotspot";
                $d->save();

                // insert table transactions
                $t = ORM::for_table('tbl_transactions')->create();
                $t->invoice = "INV-" . _raid(5);
                $t->username = $c['username'];
                $t->plan_name = $p['name_plan'];
                $t->price = $p['price'];
                $t->recharged_on = $date_only;
                $t->expiration = $date_exp;
                $t->time = $time;
                $t->method = "$gateway - $channel";
                $t->routers = $router_name;
                $t->type = "Hotspot";
                $t->save();
            }
            sendTelegram("#u$c[username] #buy #Hotspot \n" . $p['name_plan'] .
                "\nRouter: " . $router_name .
                "\nGateway: " . $gateway .
                "\nChannel: " . $channel .
                "\nPrice: " . $p['price']);
        } else {

            if ($b) {
                if (!$_c['radius_mode']) {
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::removePpoeUser($client,$c['username']);
                    Mikrotik::addPpoeUser($client,$p,$c);
                }

                $b->customer_id = $id_customer;
                $b->username = $c['username'];
                $b->plan_id = $plan_id;
                $b->namebp = $p['name_plan'];
                $b->recharged_on = $date_only;
                $b->expiration = $date_exp;
                $b->time = $time;
                $b->status = "on";
                $b->method = "$gateway - $channel";
                $b->routers = $router_name;
                $b->type = "PPPOE";
                $b->save();

                // insert table transactions
                $t = ORM::for_table('tbl_transactions')->create();
                $t->invoice = "INV-" . _raid(5);
                $t->username = $c['username'];
                $t->plan_name = $p['name_plan'];
                $t->price = $p['price'];
                $t->recharged_on = $date_only;
                $t->expiration = $date_exp;
                $t->time = $time;
                $t->method = "$gateway - $channel";
                $t->routers = $router_name;
                $t->type = "PPPOE";
                $t->save();
            } else {
                if (!$_c['radius_mode']) {
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::addPpoeUser($client,$p,$c);
                }

                $d = ORM::for_table('tbl_user_recharges')->create();
                $d->customer_id = $id_customer;
                $d->username = $c['username'];
                $d->plan_id = $plan_id;
                $d->namebp = $p['name_plan'];
                $d->recharged_on = $date_only;
                $d->expiration = $date_exp;
                $d->time = $time;
                $d->status = "on";
                $d->method = "$gateway - $channel";
                $d->routers = $router_name;
                $d->type = "PPPOE";
                $d->save();

                // insert table transactions
                $t = ORM::for_table('tbl_transactions')->create();
                $t->invoice = "INV-" . _raid(5);
                $t->username = $c['username'];
                $t->plan_name = $p['name_plan'];
                $t->price = $p['price'];
                $t->recharged_on = $date_only;
                $t->expiration = $date_exp;
                $t->time = $time;
                $t->method = "$gateway - $channel";
                $t->routers = $router_name;
                $t->type = "PPPOE";
                $t->save();
            }
            sendTelegram("#u$c[username] #buy #PPPOE \n" . $p['name_plan'] .
                "\nRouter: " . $router_name .
                "\nGateway: " . $gateway .
                "\nChannel: " . $channel .
                "\nPrice: " . $p['price']);
        }

        $in = ORM::for_table('tbl_transactions')->where('username', $c['username'])->order_by_desc('id')->find_one();

        sendWhatsapp($c['username'], "*$_c[CompanyName]*\n" .
            "$_c[address]\n" .
            "$_c[phone]\n" .
            "\n\n" .
            "INVOICE: *$in[invoice]*\n" .
            "$_L[Date] : $date_now\n" .
            "$gateway $channel\n" .
            "\n\n" .
            "$_L[Type] : *$in[type]*\n" .
            "$_L[Plan_Name] : *$in[plan_name]*\n" .
            "$_L[Plan_Price] : *$_c[currency_code] " . number_format($in['price'], 2, $_c['dec_point'], $_c['thousands_sep']) . "*\n\n" .
            "$_L[Username] : *$in[username]*\n" .
            "$_L[Password] : **********\n\n" .
            "$_L[Created_On] :\n*" . date($_c['date_format'], strtotime($in['recharged_on'])) . " $in[time]*\n" .
            "$_L[Expires_On] :\n*" . date($_c['date_format'], strtotime($in['expiration'])) . " $in[time]*\n" .
            "\n\n" .
            "$_c[note]");
        return true;
    }
}
