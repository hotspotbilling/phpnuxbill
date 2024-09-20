<?php

use PEAR2\Net\RouterOS;

class MikrotikVpn
{

    function description()
    {
        return [
            'title' => 'Mikrotik Vpn',
            'description' => 'To handle connection between PHPNuxBill with Mikrotik VPN',
            'author' => 'agstr',
            'url' => [
                'Github' => 'https://github.com/agstrxyz',
                'Telegram' => 'https://t.me/agstrxyz',
                'Youtube' => 'https://www.youtube.com/@agstrxyz',
                'Donate' => 'https://paypal.me/ibnux'
            ]
        ];
    }

    function add_customer($customer, $plan)
    {
        global $isChangePlan;
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $cid = self::getIdByCustomer($customer, $client);
        if (empty($cid)) {
            $this->addVpnUser($client, $plan, $customer);
        } else {
            $setRequest = new RouterOS\Request('/ppp/secret/set');
            $setRequest->setArgument('numbers', $cid);
            if (!empty($customer['pppoe_password'])) {
                $setRequest->setArgument('password', $customer['pppoe_password']);
            } else {
                $setRequest->setArgument('password', $customer['password']);
            }
            if (!empty($customer['pppoe_username'])) {
                $setRequest->setArgument('name', $customer['pppoe_username']);
            } else {
                $setRequest->setArgument('name', $customer['username']);
            }
            if (!empty($customer['pppoe_ip'])) {
                $setRequest->setArgument('remote-address', $customer['pppoe_ip']);
            } else {
                $setRequest->setArgument('remote-address', '0.0.0.0');
            }
            $setRequest->setArgument('profile', $plan['name_plan']);
            $setRequest->setArgument('comment', $customer['fullname'] . ' | ' . $customer['email'] . ' | ' . implode(', ', User::getBillNames($customer['id'])));
            $client->sendSync($setRequest);
            if (isset($isChangePlan) && $isChangePlan) {
                $this->removeVpnActive($client, $customer['username']);
                if (!empty($customer['pppoe_username'])) {
                    $this->removeVpnActive($client, $customer['pppoe_username']);
                }
            }
        }
    }

    function remove_customer($customer, $plan)
    {
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        if (!empty($plan['plan_expired'])) {
            $p = ORM::for_table("tbl_plans")->find_one($plan['plan_expired']);
            if ($p) {
                $this->add_customer($customer, $p);
                $this->removeVpnActive($client, $customer['username']);
                if (!empty($customer['pppoe_username'])) {
                    $this->removeVpnActive($client, $customer['pppoe_username']);
                }
                return;
            }
        }
        $this->removeVpnUser($client, $customer['username'], $customer['id']);
        if (!empty($customer['pppoe_username'])) {
            $this->removeVpnUser($client, $customer['pppoe_username'], $customer['id']);
        }
        $this->removeVpnActive($client, $customer['username']);
        if (!empty($customer['pppoe_username'])) {
            $this->removeVpnActive($client, $customer['pppoe_username']);
        }
    }

    public function change_username($plan, $from, $to)
    {
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $printRequest = new RouterOS\Request('/ppp/secret/print');
        $printRequest->setQuery(RouterOS\Query::where('name', $from));
        $cid = $client->sendSync($printRequest)->getProperty('.id');
        if (!empty($cid)) {
            $setRequest = new RouterOS\Request('/ppp/secret/set');
            $setRequest->setArgument('numbers', $cid);
            $setRequest->setArgument('name', $to);
            $client->sendSync($setRequest);
            $this->removeVpnActive($client, $from);
        }
    }

    function add_plan($plan)
    {
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);


        $bw = ORM::for_table("tbl_bandwidth")->find_one($plan['id_bw']);
        if ($bw['rate_down_unit'] == 'Kbps') {
            $unitdown = 'K';
        } else {
            $unitdown = 'M';
        }
        if ($bw['rate_up_unit'] == 'Kbps') {
            $unitup = 'K';
        } else {
            $unitup = 'M';
        }
        $rate = $bw['rate_up'] . $unitup . "/" . $bw['rate_down'] . $unitdown;
        if (!empty(trim($bw['burst']))) {
            $rate .= ' ' . $bw['burst'];
        }
        $pool = ORM::for_table("tbl_pool")->where("pool_name", $plan['pool'])->find_one();
        $addRequest = new RouterOS\Request('/ppp/profile/add');
        $client->sendSync(
            $addRequest
                ->setArgument('name', $plan['name_plan'])
                ->setArgument('local-address', (!empty($pool['local_ip'])) ? $pool['local_ip'] : $pool['pool_name'])
                ->setArgument('remote-address', $pool['pool_name'])
                ->setArgument('rate-limit', $rate)
        );
    }


    function getIdByCustomer($customer, $client)
    {
        $printRequest = new RouterOS\Request('/ppp/secret/print');
        $printRequest->setQuery(RouterOS\Query::where('name', $customer['username']));
        $id = $client->sendSync($printRequest)->getProperty('.id');
        if (empty($id)) {
            if (!empty($customer['pppoe_username'])) {
                $printRequest = new RouterOS\Request('/ppp/secret/print');
                $printRequest->setQuery(RouterOS\Query::where('name', $customer['pppoe_username']));
                $id = $client->sendSync($printRequest)->getProperty('.id');
            }
        }
        return $id;
    }

    function update_plan($old_name, $new_plan)
    {
        $mikrotik = $this->info($new_plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);

        $printRequest = new RouterOS\Request(
            '/ppp profile print .proplist=.id',
            RouterOS\Query::where('name', $old_name['name_plan'])
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');
        if (empty($profileID)) {
            $this->add_plan($new_plan);
        } else {
            $bw = ORM::for_table("tbl_bandwidth")->find_one($new_plan['id_bw']);
            if ($bw['rate_down_unit'] == 'Kbps') {
                $unitdown = 'K';
            } else {
                $unitdown = 'M';
            }
            if ($bw['rate_up_unit'] == 'Kbps') {
                $unitup = 'K';
            } else {
                $unitup = 'M';
            }
            $rate = $bw['rate_up'] . $unitup . "/" . $bw['rate_down'] . $unitdown;
            if (!empty(trim($bw['burst']))) {
                $rate .= ' ' . $bw['burst'];
            }
            $pool = ORM::for_table("tbl_pool")->where("pool_name", $new_plan['pool'])->find_one();
            $setRequest = new RouterOS\Request('/ppp/profile/set');
            $client->sendSync(
                $setRequest
                    ->setArgument('numbers', $profileID)
                    ->setArgument('local-address', (!empty($pool['local_ip'])) ? $pool['local_ip'] : $pool['pool_name'])
                    ->setArgument('remote-address', $pool['pool_name'])
                    ->setArgument('rate-limit', $rate)
                    ->setArgument('on-up', $new_plan['on_login'])
                    ->setArgument('on-down', $new_plan['on_logout'])
            );
        }
    }

    function remove_plan($plan)
    {
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $printRequest = new RouterOS\Request(
            '/ppp profile print .proplist=.id',
            RouterOS\Query::where('name', $plan['name_plan'])
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');

        $removeRequest = new RouterOS\Request('/ppp/profile/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $profileID)
        );
    }

    function add_pool($pool)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $mikrotik = $this->info($pool['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $addRequest = new RouterOS\Request('/ip/pool/add');
        $client->sendSync(
            $addRequest
                ->setArgument('name', $pool['pool_name'])
                ->setArgument('ranges', $pool['range_ip'])
        );
    }

    function update_pool($old_pool, $new_pool)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $mikrotik = $this->info($new_pool['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $printRequest = new RouterOS\Request(
            '/ip pool print .proplist=.id',
            RouterOS\Query::where('name', $old_pool['pool_name'])
        );
        $poolID = $client->sendSync($printRequest)->getProperty('.id');
        if (empty($poolID)) {
            $this->add_pool($new_pool);
        } else {
            $setRequest = new RouterOS\Request('/ip/pool/set');
            $client->sendSync(
                $setRequest
                    ->setArgument('numbers', $poolID)
                    ->setArgument('name', $new_pool['pool_name'])
                    ->setArgument('ranges', $new_pool['range_ip'])
            );
        }
    }

    function remove_pool($pool)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $mikrotik = $this->info($pool['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $printRequest = new RouterOS\Request(
            '/ip pool print .proplist=.id',
            RouterOS\Query::where('name', $pool['pool_name'])
        );
        $poolID = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ip/pool/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $poolID)
        );
    }


    function online_customer($customer, $router_name)
    {
        $mikrotik = $this->info($router_name);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $printRequest = new RouterOS\Request(
            '/ppp active print',
            RouterOS\Query::where('user', $customer['username'])
        );
        return $client->sendSync($printRequest)->getProperty('.id');
    }

    function info($name)
    {
        return ORM::for_table('tbl_routers')->where('name', $name)->find_one();
    }

    function getClient($ip, $user, $pass)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $iport = explode(":", $ip);
        return new RouterOS\Client($iport[0], $user, $pass, ($iport[1]) ? $iport[1] : null);
    }

    function removeVpnUser($client, $username, $cstid)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request('/ppp/secret/print');
        //$printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $username));
        $id = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ppp/secret/remove');
        $removeRequest->setArgument('numbers', $id);
        $client->sendSync($removeRequest);
        $this->rmNat($client, $cstid);
    }

    function addVpnUser($client, $plan, $customer)
    {
        $setRequest = new RouterOS\Request('/ppp/secret/add');
        $setRequest->setArgument('service', 'any');
        $setRequest->setArgument('profile', $plan['name_plan']);
        $setRequest->setArgument('comment', $customer['fullname'] . ' | ' . $customer['email'] . ' | ' . implode(', ', User::getBillNames($customer['id'])));
        if (!empty($customer['pppoe_password'])) {
            $setRequest->setArgument('password', $customer['pppoe_password']);
        } else {
            $setRequest->setArgument('password', $customer['password']);
        }
        if (!empty($customer['pppoe_username'])) {
            $setRequest->setArgument('name', $customer['pppoe_username']);
        } else {
            $setRequest->setArgument('name', $customer['username']);
        }
        if (!empty($customer['pppoe_ip'])) {
            $ips = $customer['pppoe_ip'];
            $setRequest->setArgument('remote-address', $customer['pppoe_ip']);
        } else {
            $ips = $this->checkIpAddr($plan['pool'], $customer['id']);
            $setRequest->setArgument('remote-address', $ips);
        }
        $this->addNat($client, $plan, $customer, $ips);
        $client->sendSync($setRequest);
        $customer->service_type = 'VPN';
        $customer->pppoe_ip = $ips;
        $customer->save();
    }

    function removeVpnActive($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $onlineRequest = new RouterOS\Request('/ppp/active/print');
        $onlineRequest->setArgument('.proplist', '.id');
        $onlineRequest->setQuery(RouterOS\Query::where('name', $username));
        $id = $client->sendSync($onlineRequest)->getProperty('.id');

        $removeRequest = new RouterOS\Request('/ppp/active/remove');
        $removeRequest->setArgument('numbers', $id);
        $client->sendSync($removeRequest);
    }


    function addIpToAddressList($client, $ip, $listName, $comment = '')
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $addRequest = new RouterOS\Request('/ip/firewall/address-list/add');
        $client->sendSync(
            $addRequest
                ->setArgument('address', $ip)
                ->setArgument('comment', $comment)
                ->setArgument('list', $listName)
        );
    }

    function removeIpFromAddressList($client, $ip)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip firewall address-list print .proplist=.id',
            RouterOS\Query::where('address', $ip)
        );
        $id = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ip/firewall/address-list/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $id)
        );
    }

    function addNat($client, $plan, $cust, $ips)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        $this->checkPort($cust['id'], 'Winbox', $plan['routers']);
        $this->checkPort($cust['id'], 'Api', $plan['routers']);
        $this->checkPort($cust['id'], 'Web', $plan['routers']);
        $tcf = ORM::for_table('tbl_customers_fields')
            ->where('customer_id', $cust['id'])
            ->find_many();
        $ip = ORM::for_table('tbl_port_pool')
            ->where('routers', $plan['routers'])
            ->find_one();
        foreach ($tcf as $cf) {
            $dst = $cf['field_value'];
            $cmnt = $cf['field_name'];
            if ($cmnt == 'Winbox') {
                $tp = '8291';
            }
            if ($cmnt == 'Web') {
                $tp = '80';
            }
            if ($cmnt == 'Api') {
                $tp = '8728';
            }
            if ($cmnt == 'Winbox' || $cmnt == 'Web' || $cmnt == 'Api') {
                $addRequest = new RouterOS\Request('/ip/firewall/nat/add');
                $client->sendSync(
                    $addRequest
                        ->setArgument('chain', 'dstnat')
                        ->setArgument('protocol', 'tcp')
                        ->setArgument('dst-port', $dst)
                        ->setArgument('action', 'dst-nat')
                        ->setArgument('to-addresses', $ips)
                        ->setArgument('to-ports', $tp)
                        ->setArgument('dst-address', $ip['public_ip'])
                        ->setArgument('comment', $cmnt . ' || ' . $cust['username'])
                );
            }
        }
    }

    function rmNat($client, $cstid)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }

        $cst = ORM::for_table('tbl_customers')->find_one($cstid);
        $printRequest = new RouterOS\Request('/ip/firewall/nat/print');
        $printRequest->setQuery(RouterOS\Query::where('to-addresses', $cst['pppoe_ip']));
        $nats = $client->sendSync($printRequest);
        foreach ($nats as $nat) {
            $id = $client->sendSync($printRequest)->getProperty('.id');
            $removeRequest = new RouterOS\Request('/ip/firewall/nat/remove');
            $removeRequest->setArgument('numbers', $id);
            $client->sendSync($removeRequest);
        }
    }


    function checkPort($id, $portn, $router)
    {
        $tcf = ORM::for_table('tbl_customers_fields')
            ->where('customer_id', $id)
            ->where('field_name', $portn)
            ->find_one();
        $ports = ORM::for_table('tbl_port_pool')
            ->where('routers', $router)
            ->find_one();
        $port = explode('-', $ports['range_port']);
        if (empty($tcf) && !empty($ports)) {
            repeat:
            $portr = rand($port['0'], $port['1']);
            if (ORM::for_table('tbl_customers_fields')->where('field_value', $portr)->find_one()) {
                if ($portr == $port['1']) {
                    return;
                }
                goto repeat;
            }
            $cf = ORM::for_table('tbl_customers_fields')->create();
            $cf->customer_id = $id;
            $cf->field_name = $portn;
            $cf->field_value = $portr;
            $cf->save();
        }
    }

    function checkIpAddr($pname, $id)
    {
        $c = ORM::for_table('tbl_customers')->find_one($id);
        $ipp = ORM::for_table('tbl_pool')
            ->where('pool_name', $pname)
            ->find_one();
        $ip_r = explode('-', $ipp['range_ip']);
        $ip_1 = explode('.', $ip_r['0']);
        $ip_2 = explode('.', $ip_r['1']);
        repeat:
        $ipt = rand($ip_1['3'], $ip_2['3']);
        $ips = $ip_1['0'] . '.' . $ip_1['1'] . '.' . $ip_1['2'] . '.' . $ipt;
        if (empty($c['pppoe_ip'])) {
            if (ORM::for_table('tbl_customers')->where('pppoe_ip', $ips)->find_one()) {
                if ($ip_2['3'] == $ipt) {
                    return;
                }
                goto repeat;
            }
            return $ips;
        }
    }
}
