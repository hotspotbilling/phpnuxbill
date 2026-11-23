<?php

use PEAR2\Net\RouterOS;

class MikrotikStatic
{
    // set while sync_customer() runs: a change the router refuses is thrown, so the sync counts that customer as not sent
    private $strict = false;

    public function sync_customer($customer, $plan)
    {
        $this->strict = true;
        try {
            $this->add_customer($customer, $plan);
        } finally {
            $this->strict = false;
        }
    }

    /** A command's replies; during a sync, the router's refusal (a !trap) is thrown instead of passing unnoticed */
    private function checked($res)
    {
        if ($this->strict && ($err = RouterApi::error($res)) !== null) {
            throw new Exception($err);
        }
        return $res;
    }
    // show Description
    public function description()
    {
        return [
            'title' => 'Mikrotik STATIC',
            'description' => 'To handle connection between PHPNuxBill with Mikrotik STATIC',
            'author' => 'Gerandonk',
            'url' => [
                'Github' => 'https://github.com/gerandonk/Mikrotik-StaticIP',
                'Telegram' => 'https://t.me/sklitinov',
                'Donate' => 'https://paypal.me/sklitinov'
            ]
        ];
    }

    public function add_customer($customer, $plan)
    {
        global $isChangePlan;
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $cid = self::getIdByCustomer($customer, $client);
        $isExp = ORM::for_table('tbl_plans')->select("id")->where('plan_expired', $plan['id'])->find_one();
        if (empty($cid)) {
            //customer not exists, add it
            $this->addStaticUser($client, $plan, $customer, $isExp);
        } else {
            $bw = ORM::for_table("tbl_bandwidth")->find_one($plan['id_bw']);
            $unitdown = $bw['rate_down_unit'] == 'Kbps' ? 'K' : 'M';
            $unitup = $bw['rate_up_unit'] == 'Kbps' ? 'K' : 'M';
            $rate = $bw['rate_up'] . $unitup . "/" . $bw['rate_down'] . $unitdown;
            if (!empty(trim($bw['burst']))) {
                $rate .= ' ' . $bw['burst'];
            }
            //$setRequest = new RouterOS\Request('/ip/dhcp-server/lease/set');
            $setRequest = new RouterOS\Request('/queue/simple/set');
            $setRequest->setArgument('numbers', $cid);
            if (!empty($customer['pppoe_username'])) {
                $setRequest->setArgument('name', $customer['pppoe_username']);
            } else {
                $setRequest->setArgument('name', $customer['username']);
            }
            $setRequest->setArgument('target', $customer['pppoe_ip']);
            $setRequest->setArgument('max-limit', $rate);
            $setRequest->setArgument('comment', $plan['name_plan'] . ' | ' . $customer['fullname'] . ' | ' . $customer['email'] . ' | ' . implode(', ', User::getBillNames($customer['id'])));
            // the expired entry is cleared only once the router has taken the queue change: a refused change keeps it blocked
            if (RouterApi::error($this->checked($client->sendSync($setRequest))) === null && !$isExp) {
                $this->clearExpired($client, $customer['pppoe_ip']);
            }
        }
    }

    /**
     * Take the customer's address off the Expired list, after the queue for it is in place. During a sync, a cleanup the router
     * refuses is thrown as a partial update (MikrotikPppoe::PARTIAL): the queue was saved, the address may still be blocked.
     */
    private function clearExpired($client, $ip)
    {
        $err = $this->removeIpFromAddressList($client, $ip);
        if ($err !== null && $this->strict) {
            throw new Exception(Lang::T('Saved, but the address could not be taken off the Expired list') . ': ' . $err, 207);
        }
    }

    public function remove_customer($customer, $plan)
    {
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        if (!empty($plan['plan_expired'])) {
            $p = ORM::for_table("tbl_plans")->find_one($plan['plan_expired']);
            if ($p) {
                $this->add_customer($customer, $p);
                $this->addIpToAddressList($client, $customer['pppoe_ip'], $listName = 'Expired', $customer['username']);
                return;
            }
        }
        $this->addIpToAddressList($client, $customer['pppoe_ip'], $listName = 'Expired', $customer['username']);
        $this->removeStaticUser($client, $customer['username']);
        if (!empty($customer['pppoe_username'])) {
            $this->removeStaticUser($client, $customer['pppoe_username']);
        }
    }

    // customer change username
    public function change_username($plan, $from, $to)
    {
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        //check if customer exists
        $printRequest = new RouterOS\Request('/queue/simple/print');
        $printRequest->setQuery(RouterOS\Query::where('name', $from));
        $cid = $client->sendSync($printRequest)->getProperty('.id');
        if (!empty($cid)) {
            $setRequest = new RouterOS\Request('/queue/simple/set');
            $setRequest->setArgument('numbers', $cid);
            $setRequest->setArgument('name', $to);
            $client->sendSync($setRequest);
        }
    }

    public function add_plan($plan)
    {
    }

    /**
     * Function to ID by username from Mikrotik
     */
    public function getIdByCustomer($customer, $client)
    {
        $printRequest = new RouterOS\Request('/queue/simple/print');
        $printRequest->setQuery(RouterOS\Query::where('name', $customer['username']));
        $id = $client->sendSync($printRequest)->getProperty('.id');
        if (empty($id)) {
            if (!empty($customer['pppoe_username'])) {
                $printRequest = new RouterOS\Request('/queue/simple/print');
                $printRequest->setQuery(RouterOS\Query::where('name', $customer['pppoe_username']));
                $id = $client->sendSync($printRequest)->getProperty('.id');
            }
        }
        return $id;
    }

    public function update_plan($old_name, $new_plan)
    {
    }

    function remove_plan($plan)
    {
    }

    public function add_pool($pool)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
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

    public function update_pool($old_pool, $new_pool)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
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

    public function remove_pool($pool)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
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


    public function online_customer($customer, $router_name)
    {
        $mikrotik = $this->info($router_name);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $printRequest = new RouterOS\Request(
            '/queue simple print',
            RouterOS\Query::where('name', $customer['username'])
        );
        return $client->sendSync($printRequest)->getProperty('.id');
    }

    public function info($name)
    {
        return ORM::for_table('tbl_routers')->where('name', $name)->find_one();
    }

    public function getClient($ip, $user, $pass)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        [$host, $port] = RouterAddress::split($ip); // "host:port", or "[IPv6]:port"
        return RouterApi::deviceClient($host, $user, $pass, $port); // every read time-limited
    }

    public function removeStaticUser($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $printRequest = new RouterOS\Request('/queue/simple/print');
        //$printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $username));
        $id = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/queue/simple/remove');
        $removeRequest->setArgument('numbers', $id);
        $client->sendSync($removeRequest);
    }

    public function addStaticUser($client, $plan, $customer, $isExp = false)
    {
        $bw = ORM::for_table("tbl_bandwidth")->find_one($plan['id_bw']);
        $unitdown = $bw['rate_down_unit'] == 'Kbps' ? 'K' : 'M';
        $unitup = $bw['rate_up_unit'] == 'Kbps' ? 'K' : 'M';
        $rate = $bw['rate_up'] . $unitup . "/" . $bw['rate_down'] . $unitdown;
        if (!empty(trim($bw['burst']))) {
            $rate .= ' ' . $bw['burst'];
        }
        $setRequest = new RouterOS\Request('/queue/simple/add');
        if (!empty($customer['pppoe_username'])) {
            $setRequest->setArgument('name', $customer['pppoe_username']);
        } else {
            $setRequest->setArgument('name', $customer['username']);
        }
        $setRequest->setArgument('target', $customer['pppoe_ip']);
        $setRequest->setArgument('max-limit', $rate);
        $setRequest->setArgument('comment', $plan['name_plan'] . ' | ' . $customer['fullname'] . ' | ' . $customer['email'] . ' | ' . implode(', ', User::getBillNames($customer['id'])));
        // the queue first: the customer leaves the Expired list only once the router has confirmed the queue that limits them
        if (RouterApi::error($this->checked($client->sendSync($setRequest))) === null && !$isExp) {
            $this->clearExpired($client, $customer['pppoe_ip']);
        }
    }

    public function removeStaticActive($client, $username)
    {
    }

    public function getIpStaticUser($client, $username)
    {
    }

    public function addIpToAddressList($client, $ip, $listName, $comment = '')
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
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

    public function removeIpFromAddressList($client, $ip)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip firewall address-list print .proplist=.id',
            RouterOS\Query::where('address', $ip)
        );
        $res = $client->sendSync($printRequest);
        if (($err = RouterApi::error($res)) !== null) {
            return $err;
        }
        $id = $res->getProperty('.id');
        if (empty($id)) {
            return null; // not on the list: nothing to take off
        }
        $removeRequest = new RouterOS\Request('/ip/firewall/address-list/remove');
        return RouterApi::error($client->sendSync(
            $removeRequest
                ->setArgument('numbers', $id)
        ));
    }
}
