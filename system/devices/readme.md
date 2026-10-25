# How To add new Devices

just follow the template

```php
<?php

class FileName {

    // show Description
    function description()
    {
        return [
            'title' => 'Device',
            'description' => '',
            'author' => 'ibnu maksum',
            'url' => [
                'Github' => 'https://github.com/hotspotbilling/phpnuxbill/',
                'Telegram' => 'https://t.me/ibnux',
                'Donate' => 'https://paypal.me/ibnux',
                'any text' => 'Any Url'
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
    public function change_username($plan, $from, $to)
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
```

## Folder layout

Besides `system/devices/<Class>.php`, a driver can live in its own folder:

```
system/devices/<Class>/device.json
system/devices/<Class>/<Class>.php
```

`device.json` holds `name`, `slug` (= class name), `version`, `description`, `author`, `category`, `main`, and the same
`requires_*`, `update_url`, `github`, `requires_license` keys as other extensions. Both layouts are resolved by
`DeviceLoader`; use `DeviceLoader::file($class)` / `DeviceLoader::names()` instead of scanning the folder.
Core drivers (`MikrotikHotspot`, `MikrotikPppoe`, `MikrotikStatic`, `MikrotikVpn`, `Radius*`, `Voucher`, `Dummy`) stay single
files and cannot be uninstalled. A driver that plans still use cannot be uninstalled.
