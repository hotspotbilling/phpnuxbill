<?php

class RadiusRest {

    // show Description
    function description()
    {
        return [
            'title' => 'Radius Rest API',
            'description' => 'This devices will handle Radius Connection using Rest API',
            'author' => 'ibnu maksum',
            'url' => [
                'Wiki Tutorial' => 'https://github.com/hotspotbilling/phpnuxbill/wiki/FreeRadius-Rest',
                'Telegram' => 'https://t.me/phpnuxbill',
                'Donate' => 'https://paypal.me/ibnux'
            ]
        ];
    }

    // Add Customer to Mikrotik/Device
    function add_customer($customer, $plan)
    {
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