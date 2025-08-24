<?php

class Voucher
{

    // show Description
    function description()
    {
        return [
            'title' => 'Voucher',
            'description' => 'This Devices will Create Voucher and send it to customer via email or SMS or whatsapp, then customer can use it to recharge their account, Set Plan for Voucher in the expired section',
            'author' => 'ibnu maksum',
            'url' => [
                'Github' => 'https://github.com/hotspotbilling/phpnuxbill/',
                'Telegram' => 'https://t.me/phpnuxbill',
                'Donate' => 'https://paypal.me/ibnux'
            ]
        ];
    }

    // Add Customer to Mikrotik/Device
    function add_customer($customer, $plan)
    {
        global $config;
        if (!empty($plan['plan_expired'])) {
            $p = ORM::for_table('tbl_plans')->where('id', $plan['plan_expired'])->find_one();
			if ($p['is_radius'] == '1') {
						$router_name = 'radius';
					} else {
						$router_name = $plan['routers'];
						}
            if ($p) {
                repeat:
                if ($config['voucher_format'] == 'numbers') {
                    $code = generateUniqueNumericVouchers(1, 10)[0];
                } else {
                    $code = strtoupper(substr(md5(time() . rand(10000, 99999)), 0, 10));
                    if ($config['voucher_format'] == 'low') {
                        $code = strtolower($code);
                    } else if ($config['voucher_format'] == 'rand') {
                        $code = Lang::randomUpLowCase($code);
                    }
                }
                $code = 'GC'.$customer['id'] . 'C' . $code;
                if (ORM::for_table('tbl_voucher')->whereRaw("BINARY `code` = '$code'")->find_one()) {
                    // if exist, generate another code
                    goto repeat;
                }
                $d = ORM::for_table('tbl_voucher')->create();
                $d->type = $p['type'];
                $d->routers = $router_name;
                $d->id_plan = $p['id'];
                $d->code = $code;
                $d->user = '0';
                $d->status = '0';
                $d->generated_by = $customer['id'];
                if ($d->save()) {
                    $v = ORM::for_table('tbl_customers_inbox')->create();
                    $v->from = "System";
                    $v->customer_id = $customer['id'];
                    $v->subject = Lang::T('New Voucher for '.$p['name_plan'].' Created');
                    $v->date_created = date('Y-m-d H:i:s');
                    $v->body = nl2br("Dear $customer[fullname],\n\nYour Internet Voucher Code is : <span style=\"user-select: all; cursor: pointer; background-color: #000\">$code</span>\n" .
                        "Internet Plan: $p[name_plan]\n" .
                        "\nYou can use this or share it with your friends.\n\nBest Regards");
                    $v->save();
                } else {
                    r2(U . 'order', 'e', "Voucher Failed to create, Please call admin");
                }
            }else{
                r2(U . 'order', 'e', "Plan not found");
            }
        }else{
            r2(U . 'order', 'e', "Plan not found");
        }
    }

    // Remove Customer to Mikrotik/Device
    function remove_customer($customer, $plan)
    {
    }

    // customer change username
    public function change_username($from, $to)
    {
    }


    // Add Plan to Mikrotik/Device
    function add_plan($plan)
    {
    }

    // Update Plan to Mikrotik/Device
    function update_plan($old_name, $plan)
    {
    }

    // Remove Plan from Mikrotik/Device
    function remove_plan($plan)
    {
    }

    // check if customer is online
    function online_customer($customer, $router_name)
    {
    }

    // make customer online
    function connect_customer($customer, $ip, $mac_address, $router_name)
    {
    }

    // make customer disconnect
    function disconnect_customer($customer, $router_name)
    {
    }
}
