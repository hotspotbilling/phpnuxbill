# How To add new Devices

just follow the template

```php
<?php

class FileName {

    function add_customer($customer, $plan)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return;
        }
    }

    function remove_customer($customer, $plan)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return;
        }
    }

    function change_customer($customer, $plan)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return;
        }
    }

    function add_plan($plan)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return;
        }
    }

    function update_plan($old_name, $plan)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return;
        }
    }

    function remove_plan($plan)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return;
        }
    }

    function online_customer($customer, $router_name)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return;
        }
    }

    function connect_customer($customer, $ip, $mac_address, $router_name)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return;
        }
    }

    function disconnect_customer($customer, $router_name)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return;
        }
    }

}
```