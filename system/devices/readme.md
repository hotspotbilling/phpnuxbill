# How To add new Devices

just follow the template

```php
<?php

class FileName {

    function add_customer($customer, $plan)
    {
    }

    function remove_customer($customer, $plan)
    {
    }

    function change_customer($customer, $plan)
    {
    }

    function add_plan($plan)
    {
    }

    function update_plan($old_name, $plan)
    {
    }

    function remove_plan($plan)
    {
    }

    function online_customer($customer, $router_name)
    {
    }

    function connect_customer($customer, $ip, $mac_address, $router_name)
    {
    }

    function disconnect_customer($customer, $router_name)
    {
    }

}
```