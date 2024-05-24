<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 *
 * This is Core, don't modification except you want to contribute
 * better create new plugin
 **/

/**
 * Establishes a connection between a MikroTik router and a customer.
 *
 * This function takes two parameters: $routers and $customer.
 *
 * @param array $routers An array containing information about the MikroTik routers.
 *                       This can include IP addresses or connection details.
 * @param mixed $customer An object or array representing a specific customer.
 *                        This can contain relevant information about the customer,
 *                        such as their username or account details.
 * @return void
 */
function mikrotik_connect_customer($routers, $customer)
{
}

/**
 * Disconnects a customer from a MikroTik router.
 *
 * This function takes two parameters: $routers and $customer.
 *
 * @param array $routers An array containing information about the MikroTik routers.
 *                       This can include IP addresses or connection details.
 * @param mixed $customer An object or array representing a specific customer.
 *                        This can contain relevant information about the customer,
 *                        such as their username or account details.
 * @return void
 */
function mikrotik_disconnect_customer($routers, $customer)
{
}
