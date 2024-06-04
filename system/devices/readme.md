# How To add new Devices

just follow the template

```php
<?php

class FileName {

    function connect_customer($customer, $plan)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return;
        }
    }

    function disconnect_customer($customer, $plan)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return;
        }
    }

    function change_customer($tur, $customer, $plan)
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

    function add_pool($pool){
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
    }

    function update_pool($old_pool, $new_pool){
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
    }

    function remove_pool($pool){
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
    }
}
```