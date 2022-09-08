<?php

/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)
**/


use PEAR2\Net\RouterOS;

function rechargeUser($id_customer, $router_name, $plan_id, $gateway, $channel){
    global $_c,$_L;
    $date_now = date("Y-m-d H:i:s");
    $date_only = date("Y-m-d");
    $time = date("H:i:s");

    if ($id_customer == '' or $router_name == '' or $plan_id == '') {
        return false;
    }

    $c = ORM::for_table('tbl_customers')->where('id', $id_customer)->find_one();
    $p = ORM::for_table('tbl_plans')->where('id', $plan_id)->where('enabled', '1')->find_one();
    $b = ORM::for_table('tbl_user_recharges')->where('customer_id', $id_customer)->find_one();

    $mikrotik = Router::_info($router_name);
    if($p['validity_unit']=='Months'){
        $date_exp = date("Y-m-d", strtotime('+'.$p['validity'].' month'));
    }else if($p['validity_unit']=='Days'){
        $date_exp = date("Y-m-d", strtotime('+'.$p['validity'].' day'));
    }else if($p['validity_unit']=='Hrs'){
        $datetime = explode(' ',date("Y-m-d H:i:s", strtotime('+'.$p['validity'].' hour')));
        $date_exp = $datetime[0];
        $time = $datetime[1];
    }else if($p['validity_unit']=='Mins'){
        $datetime = explode(' ',date("Y-m-d H:i:s", strtotime('+'.$p['validity'].' minute')));
        $date_exp = $datetime[0];
        $time = $datetime[1];
    }

    if ($p['type'] == 'Hotspot') {
        if ($b) {
            if(!$_c['radius_mode']){
                try {
                    $iport = explode(":", $mikrotik['ip_address']);
                    $client = new RouterOS\Client($iport[0], $mikrotik['username'], $mikrotik['password'], ($iport[1]) ? $iport[1] : null);
                } catch (Exception $e) {
                    die("Unable to connect to the router.<br>".$e->getMessage());
                }

                $printRequest = new RouterOS\Request(
                    '/ip hotspot user print .proplist=name',
                    RouterOS\Query::where('name', $c['username'])
                );
                $userName = $client->sendSync($printRequest)->getProperty('name');
                $removeRequest = new RouterOS\Request('/ip/hotspot/user/remove');
                $client(
                    $removeRequest
                        ->setArgument('numbers', $userName)
                );
                /* iBNuX Added:
                * 	Time limit to Mikrotik
                *	'Time_Limit', 'Data_Limit', 'Both_Limit'
                */
                $addRequest = new RouterOS\Request('/ip/hotspot/user/add');
                if ($p['typebp'] == "Limited") {
                    if ($p['limit_type'] == "Time_Limit") {
                        if ($p['time_unit'] == 'Hrs')
                            $timelimit = $p['time_limit'] . ":00:00";
                        else
                            $timelimit = "00:" . $p['time_limit'] . ":00";
                        $client->sendSync(
                            $addRequest
                                ->setArgument('name', $c['username'])
                                ->setArgument('profile', $p['name_plan'])
                                ->setArgument('password', $c['password'])
                                ->setArgument('limit-uptime', $timelimit)
                        );
                    } else if ($p['limit_type'] == "Data_Limit") {
                        if ($p['data_unit'] == 'GB')
                            $datalimit = $p['data_limit'] . "000000000";
                        else
                            $datalimit = $p['data_limit'] . "000000";
                        $client->sendSync(
                            $addRequest
                                ->setArgument('name', $c['username'])
                                ->setArgument('profile', $p['name_plan'])
                                ->setArgument('password', $c['password'])
                                ->setArgument('limit-bytes-total', $datalimit)
                        );
                    } else if ($p['limit_type'] == "Both_Limit") {
                        if ($p['time_unit'] == 'Hrs')
                            $timelimit = $p['time_limit'] . ":00:00";
                        else
                            $timelimit = "00:" . $p['time_limit'] . ":00";
                        if ($p['data_unit'] == 'GB')
                            $datalimit = $p['data_limit'] . "000000000";
                        else
                            $datalimit = $p['data_limit'] . "000000";
                        $client->sendSync(
                            $addRequest
                                ->setArgument('name', $c['username'])
                                ->setArgument('profile', $p['name_plan'])
                                ->setArgument('password', $c['password'])
                                ->setArgument('limit-uptime', $timelimit)
                                ->setArgument('limit-bytes-total', $datalimit)
                        );
                    }
                } else {
                    $client->sendSync(
                        $addRequest
                            ->setArgument('name', $c['username'])
                            ->setArgument('profile', $p['name_plan'])
                            ->setArgument('password', $c['password'])
                    );
                }
            }

            $b->customer_id = $id_customer;
            $b->username = $c['username'];
            $b->plan_id = $plan_id;
            $b->namebp = $p['name_plan'];
            $b->recharged_on = $date_only;
            $b->expiration = $date_exp;
            $b->time = $time;
            $b->status = "on";
            $b->method = "admin";
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
            $t->method = "admin";
            $t->routers = $router_name;
            $t->type = "Hotspot";
            $t->save();
        } else {
            if(!$_c['radius_mode']){
                try {
                    $iport = explode(":", $mikrotik['ip_address']);
                    $client = new RouterOS\Client($iport[0], $mikrotik['username'], $mikrotik['password'], ($iport[1]) ? $iport[1] : null);
                } catch (Exception $e) {
                    die("Unable to connect to the router.<br>".$e->getMessage());
                }

                /* iBNuX Added:
                * 	Time limit to Mikrotik
                *	'Time_Limit', 'Data_Limit', 'Both_Limit'
                */
                $addRequest = new RouterOS\Request('/ip/hotspot/user/add');
                if ($p['typebp'] == "Limited") {
                    if ($p['limit_type'] == "Time_Limit") {
                        if ($p['time_unit'] == 'Hrs')
                            $timelimit = $p['time_limit'] . ":00:00";
                        else
                            $timelimit = "00:" . $p['time_limit'] . ":00";
                        $client->sendSync(
                            $addRequest
                                ->setArgument('name', $c['username'])
                                ->setArgument('profile', $p['name_plan'])
                                ->setArgument('password', $c['password'])
                                ->setArgument('limit-uptime', $timelimit)
                        );
                    } else if ($p['limit_type'] == "Data_Limit") {
                        if ($p['data_unit'] == 'GB')
                            $datalimit = $p['data_limit'] . "000000000";
                        else
                            $datalimit = $p['data_limit'] . "000000";
                        $client->sendSync(
                            $addRequest
                                ->setArgument('name', $c['username'])
                                ->setArgument('profile', $p['name_plan'])
                                ->setArgument('password', $c['password'])
                                ->setArgument('limit-bytes-total', $datalimit)
                        );
                    } else if ($p['limit_type'] == "Both_Limit") {
                        if ($p['time_unit'] == 'Hrs')
                            $timelimit = $p['time_limit'] . ":00:00";
                        else
                            $timelimit = "00:" . $p['time_limit'] . ":00";
                        if ($p['data_unit'] == 'GB')
                            $datalimit = $p['data_limit'] . "000000000";
                        else
                            $datalimit = $p['data_limit'] . "000000";
                        $client->sendSync(
                            $addRequest
                                ->setArgument('name', $c['username'])
                                ->setArgument('profile', $p['name_plan'])
                                ->setArgument('password', $c['password'])
                                ->setArgument('limit-uptime', $timelimit)
                                ->setArgument('limit-bytes-total', $datalimit)
                        );
                    }
                } else {
                    $client->sendSync(
                        $addRequest
                            ->setArgument('name', $c['username'])
                            ->setArgument('profile', $p['name_plan'])
                            ->setArgument('password', $c['password'])
                    );
                }
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
            $d->method = "admin";
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
            $t->method = "admin";
            $t->routers = $router_name;
            $t->type = "Hotspot";
            $t->save();
        }
        sendTelegram( "#$c[username] #buy #Hotspot \n".$p['name_plan'].
        "\nRouter: ".$router_name.
        "\nGateway: ".$gateway.
        "\nChannel: ".$channel.
        "\nPrice: ".$p['price']);
    } else {

        if ($b) {
            if(!$_c['radius_mode']){
                try {
                    $iport = explode(":", $mikrotik['ip_address']);
                    $client = new RouterOS\Client($iport[0], $mikrotik['username'], $mikrotik['password'], ($iport[1]) ? $iport[1] : null);
                } catch (Exception $e) {
                    die("Unable to connect to the router.<br>".$e->getMessage());
                }
                $printRequest = new RouterOS\Request(
                    '/ppp secret print .proplist=name',
                    RouterOS\Query::where('name', $c['username'])
                );
                $userName = $client->sendSync($printRequest)->getProperty('name');

                $removeRequest = new RouterOS\Request('/ppp/secret/remove');
                $client(
                    $removeRequest
                        ->setArgument('numbers', $userName)
                );

                $addRequest = new RouterOS\Request('/ppp/secret/add');
                $client->sendSync(
                    $addRequest
                        ->setArgument('name', $c['username'])
                        ->setArgument('service', 'pppoe')
                        ->setArgument('profile', $p['name_plan'])
                        ->setArgument('password', $c['password'])
                );
            }

            $b->customer_id = $id_customer;
            $b->username = $c['username'];
            $b->plan_id = $plan_id;
            $b->namebp = $p['name_plan'];
            $b->recharged_on = $date_only;
            $b->expiration = $date_exp;
            $b->time = $time;
            $b->status = "on";
            $b->method = "admin";
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
            $t->method = "admin";
            $t->routers = $router_name;
            $t->type = "PPPOE";
            $t->save();
        } else {
            if(!$_c['radius_mode']){
                try {
                    $iport = explode(":", $mikrotik['ip_address']);
                    $client = new RouterOS\Client($iport[0], $mikrotik['username'], $mikrotik['password'], ($iport[1]) ? $iport[1] : null);
                } catch (Exception $e) {
                    die("Unable to connect to the router.<br>".$e->getMessage());
                }
                $addRequest = new RouterOS\Request('/ppp/secret/add');
                $client->sendSync(
                    $addRequest
                        ->setArgument('name', $c['username'])
                        ->setArgument('service', 'pppoe')
                        ->setArgument('profile', $p['name_plan'])
                        ->setArgument('password', $c['password'])
                );
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
            $d->method = "admin";
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
            $t->method = "admin";
            $t->routers = $router_name;
            $t->type = "PPPOE";
            $t->save();
        }
        sendTelegram( "#$c[username] #buy #PPPOE \n".$p['name_plan'].
        "\nRouter: ".$router_name.
        "\nGateway: ".$gateway.
        "\nChannel: ".$channel.
        "\nPrice: ".$p['price']);
    }

    $in = ORM::for_table('tbl_transactions')->where('username', $c['username'])->order_by_desc('id')->find_one();

    sendWhatsapp($c['username'], "*$_c[CompanyName]*\n".
            "$_c[address]\n".
            "$_c[phone]\n".
            "\n\n".
            "INVOICE: *$in[invoice]*\n".
            "$_L[Date] : $date_now\n".
            "$gateway $channel\n".
            "\n\n".
            "$_L[Type] : *$in[type]*\n".
            "$_L[Plan_Name] : *$in[plan_name]*\n".
            "$_L[Plan_Price] : *$_c[currency_code] ".number_format($in['price'],2,$_c['dec_point'],$_c['thousands_sep'])."*\n\n".
            "$_L[Username] : *$in[username]*\n".
            "$_L[Password] : **********\n\n".
            "$_L[Created_On] :\n*".date($_c['date_format'], strtotime($in['recharged_on']))." $in[time]*\n".
            "$_L[Expires_On] :\n*".date($_c['date_format'], strtotime($in['expiration']))." $in[time]*\n".
            "\n\n".
            "$_c[note]");
    return true;
}