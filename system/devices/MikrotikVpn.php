<?php

use PEAR2\Net\RouterOS;

class MikrotikVpn
{
    // set while sync_customer() runs: a change the router refuses is thrown, so the sync counts that customer as not sent
    private $strict = false;

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
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        // one request at a time for this customer on this router (a database lock, so on every PHP worker), whether or not an
        // address is already reserved: the reservation and the secret are looked at again under it, so two requests at once
        // never both create the secret, nor does one release an address the other has just used
        $lock = 'phpnuxbill_vpn_c_' . substr(sha1($plan['routers'] . '|' . $customer['id']), 0, 32);
        if (!$this->dbLock($lock)) {
            throw new Exception(Lang::T('This customer is being set up on the router by another request. Try again in a moment.'));
        }
        try {
            $this->provision($client, $customer, $plan);
        } finally {
            $this->dbUnlock($lock);
        }
    }

    /** add_customer() under the customer's lock */
    private function provision($client, $customer, $plan)
    {
        global $isChangePlan;
        // the address saved for this customer now (another request may have reserved one since $customer was read)
        if (empty($customer['pppoe_ip'])) {
            $fresh = ORM::for_table('tbl_customers')->find_one($customer['id']);
            if ($fresh && !empty($fresh['pppoe_ip']) && is_object($customer)) {
                $customer->pppoe_ip = $fresh['pppoe_ip'];
            }
        }
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
            // a secret the router has but whose address was never saved here (its port forwards failed after it was added):
            // the address it was given is kept, and the forwards are finished below
            $resume = '';
            $readFailed = false;
            if (empty($customer['pppoe_ip'])) {
                $readRequest = new RouterOS\Request('/ppp/secret/print');
                $readRequest->setQuery(RouterOS\Query::where('.id', $cid));
                $readRes = $client->sendSync($readRequest);
                if (($readErr = RouterApi::error($readRes)) !== null) {
                    // the secret could not be read, so whether it has a fixed address is unknown: a sync stops here, otherwise the
                    // address on the router is left as it is (never overwritten with the default)
                    if ($this->strict) {
                        throw new Exception($readErr);
                    }
                    $readFailed = true;
                } else {
                    $given = (string) RouterApi::text($readRes->getProperty('remote-address'));
                    if (filter_var($given, FILTER_VALIDATE_IP) && $given !== '0.0.0.0') {
                        $resume = $given;
                    }
                }
            }
            $reserved = '';
            $oldType = $customer['service_type'];
            if (!empty($customer['pppoe_ip'])) {
                $setRequest->setArgument('remote-address', $customer['pppoe_ip']);
            } elseif ($resume !== '') {
                $setRequest->setArgument('remote-address', $resume);
            } elseif (!$readFailed && ORM::for_table('tbl_port_pool')->where('routers', $plan['routers'])->find_one()) {
                // read, and it has no fixed address (0.0.0.0), on a router whose customers get port forwards: one is reserved
                // from the pool and given to it, so the forwards can be made (none free: thrown, never passed as done without them)
                $reserved = $this->reserveIp($plan, $customer);
                $setRequest->setArgument('remote-address', $reserved);
            } elseif (!$readFailed) {
                // read, and it has no fixed address, and no forwards are needed: the default
                $setRequest->setArgument('remote-address', '0.0.0.0');
            }
            $setRequest->setArgument('profile', $plan['name_plan']);
            $setRequest->setArgument('comment', $customer['fullname'] . ' | ' . $customer['email'] . ' | ' . implode(', ', User::getBillNames($customer['id'])));
            // (no answer is thrown by sendSync() and keeps a reserved address, like addVpnUser())
            $res = $client->sendSync($setRequest);
            if ($reserved !== '' && ($err = RouterApi::error($res)) !== null) {
                // refused for certain: the address goes back to the pool
                $this->releaseIp($client, $customer, $reserved, $oldType);
                throw new Exception(Lang::T('The router refused the VPN secret') . ': ' . $err);
            }
            $this->checked($res);
            $addr = !empty($customer['pppoe_ip']) ? (string) $customer['pppoe_ip'] : $resume;
            if ($resume !== '') {
                // the address the router gave this secret (it has it, whether or not this change is taken) is kept for this
                // customer, so it is not handed to another one
                $customer->service_type = 'VPN';
                $customer->pppoe_ip = $resume;
                $customer->save();
            }
            // the forwards are checked against the router each time, not inferred from the saved address: one refused before is
            // added now, and one already there (by its comment) is not added again. Only once the router took the change above:
            // a refused secret gets no forwards
            if ($addr !== '' && RouterApi::error($res) === null
                && !$this->addNat($client, $plan, $customer, $addr, $this->natRules($client, $addr, $customer)) && $this->strict) {
                throw new Exception(Lang::T('The router refused a port forward for this customer'));
            }
            if (isset($isChangePlan) && $isChangePlan) {
                $this->removeVpnActive($client, $customer['username']);
                if (!empty($customer['pppoe_username'])) {
                    $this->removeVpnActive($client, $customer['pppoe_username']);
                }
            }
        }
    }
	
	function sync_customer($customer, $plan)
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
        if (strtolower((string) $_app_stage) === 'demo') {
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
        if (strtolower((string) $_app_stage) === 'demo') {
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
        if (strtolower((string) $_app_stage) === 'demo') {
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
        // the stage is written "Demo" in some places and "demo" in others: either one keeps real routers untouched
        if (strcasecmp(trim((string) $_app_stage), 'demo') === 0) {
            return null;
        }
        [$host, $port] = RouterAddress::split($ip); // "host:port", or "[IPv6]:port"
        return RouterApi::deviceClient($host, $user, $pass, $port); // every read time-limited
    }

    function removeVpnUser($client, $username, $cstid)
    {
        global $_app_stage;
        if (strtolower((string) $_app_stage) === 'demo') {
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
        // called under the customer's lock (add_customer()), with the reservation already looked at again
        $ips = $customer['pppoe_ip'];
        $oldType = $customer['service_type'];
        $reserved = false;
        if (empty($ips)) {
            // the new address is saved for this customer before the router is asked: it is then never handed to another
            // customer, whether the secret below is taken, refused or its answer is lost. Released only when the router refuses
            // the secret: after no answer it is kept, and the next add_customer() first looks for the secret
            // (getIdByCustomer()) and either updates it or adds it again with this same address. None left: no secret is made
            // (it would have no fixed address, and no forwards could point at it).
            $ips = $this->reserveIp($plan, $customer);
            $reserved = true;
        }
        if (!is_string($ips) || !filter_var($ips, FILTER_VALIDATE_IP)) {
            throw new Exception(Lang::T('No free IP address is left in the pool') . ' ' . $plan['pool']);
        }
        $setRequest->setArgument('remote-address', $ips);
        // the secret first: the port forwards (and the ports reserved for them) are made only for a customer the router took,
        // so a refused secret leaves no rules pointing at an address that was never given out
        $res = $client->sendSync($setRequest);
        if (($err = RouterApi::error($res)) !== null) {
            if ($reserved) {
                $this->releaseIp($client, $customer, $ips, $oldType);
            }
            // thrown (not only during a sync), so a recharge records it and sends its alert instead of passing as done
            throw new Exception(Lang::T('The router refused the VPN secret') . ': ' . $err);
        }
        if (!$reserved) {
            // a customer who already had an address: only the type is saved, as before. Forwards are not tracked here: each
            // later add_customer() checks them on the router and adds the missing ones.
            $customer->service_type = 'VPN';
            $customer->pppoe_ip = $ips;
            $customer->save();
        }
        if (!$this->addNat($client, $plan, $customer, $ips, $this->natRules($client, $ips, $customer)) && $this->strict) {
            throw new Exception(Lang::T('The router refused a port forward for this customer'));
        }
    }

    /**
     * Reserve a free address from the plan's pool for this customer and save it (with the VPN type) before the router is asked.
     * One reservation at a time across every pool and router (a database lock): pools are not checked for overlap and an
     * address is free only when no customer holds it, so two requests drawing from overlapping pools would otherwise both pick
     * the same address. The lock is held only until the address is saved. Thrown when the lock is not got or none is free.
     */
    private function reserveIp($plan, $customer)
    {
        $lock = 'phpnuxbill_vpn_ip';
        if (!$this->dbLock($lock)) {
            throw new Exception(Lang::T('Another customer is being given an address from this pool. Try again in a moment.'));
        }
        try {
            $ips = $this->checkIpAddr($plan['pool'], $customer['id']);
            if (!is_string($ips) || !filter_var($ips, FILTER_VALIDATE_IP)) {
                throw new Exception(Lang::T('No free IP address is left in the pool') . ' ' . $plan['pool']);
            }
            $customer->service_type = 'VPN';
            $customer->pppoe_ip = $ips;
            $customer->save();
            return $ips;
        } finally {
            $this->dbUnlock($lock);
        }
    }

    /**
     * Give back an address reserved by reserveIp() after the router refused the secret, once it is certain the address is not in
     * use: it is still this customer's saved address, and the router has no secret for the customer (when that cannot be read,
     * the address is kept).
     */
    private function releaseIp($client, $customer, $ips, $oldType)
    {
        $fresh = ORM::for_table('tbl_customers')->find_one($customer['id']);
        if (!$fresh || (string) $fresh['pppoe_ip'] !== (string) $ips) {
            return;
        }
        foreach (array_unique(array_filter([(string) $customer['username'], (string) $customer['pppoe_username']])) as $name) {
            $printRequest = new RouterOS\Request('/ppp/secret/print');
            $printRequest->setArgument('.proplist', '.id');
            $printRequest->setQuery(RouterOS\Query::where('name', $name));
            try {
                $res = $client->sendSync($printRequest);
            } catch (Throwable $e) {
                return;
            }
            if (RouterApi::error($res) !== null || !empty($res->getProperty('.id'))) {
                return;
            }
        }
        $customer->service_type = $oldType;
        $customer->pppoe_ip = '';
        $customer->save();
    }

    /**
     * The port forwards on the router that point to this address, and this customer's own forwards (by the id at the end of
     * their comment) wherever they point (an older address), as arrays of their fields; null when the router refused to list
     * them (an empty list is no forwards at all)
     */
    private function natRules($client, $addr, $cust)
    {
        $natRequest = new RouterOS\Request('/ip/firewall/nat/print');
        $natRequest->setQuery(RouterOS\Query::where('chain', 'dstnat'));
        $res = $client->sendSync($natRequest);
        if (RouterApi::error($res) !== null) {
            return null;
        }
        $owned = '/^(Winbox|Api|Web) \|\| .* \|\| ' . preg_quote((string) $cust['id'], '/') . '$/';
        $rules = [];
        foreach ($res as $nat) {
            if ($nat->getType() === RouterOS\Response::TYPE_DATA) {
                $row = [];
                foreach (['.id', 'comment', 'chain', 'action', 'protocol', 'dst-port', 'dst-address', 'to-addresses', 'to-ports', 'disabled'] as $k) {
                    $row[$k] = (string) RouterApi::text($nat->getProperty($k));
                }
                if ($row['to-addresses'] === (string) $addr || preg_match($owned, $row['comment'])) {
                    $rules[] = $row;
                }
            }
        }
        return $rules;
    }

    function removeVpnActive($client, $username)
    {
        global $_app_stage;
        if (strtolower((string) $_app_stage) === 'demo') {
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
        if (strtolower((string) $_app_stage) === 'demo') {
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
        if (strtolower((string) $_app_stage) === 'demo') {
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

    /**
     * $rules: the forwards already on the router for this address (natRules()), or null when they could not be read. A forward
     * that is there, enabled and as it should be is left alone; one of this customer's (by the id in its comment) that differs
     * (an old public port, disabled) is changed in place, or, when that is refused, replaced by a new one that is removed only
     * after the new one is taken. Returns whether every forward is in place: false when the rules could not be read (nothing
     * is added then, so nothing is doubled), when a port could not be reserved, or when the router refused a change (a refusal
     * comes back as a !trap reply, not as an exception).
     */
    function addNat($client, $plan, $cust, $ips, $rules = [])
    {
        global $_app_stage;
        if (strtolower((string) $_app_stage) === 'demo') {
            return true;
        }
        if ($rules === null) {
            if ($this->strict) {
                throw new Exception(Lang::T('The port forwards on the router could not be read'));
            }
            return false;
        }
        $ok = true;
        $this->checkPort($cust['id'], 'Winbox', $plan['routers']);
        $this->checkPort($cust['id'], 'Api', $plan['routers']);
        $this->checkPort($cust['id'], 'Web', $plan['routers']);
        $tcf = ORM::for_table('tbl_customers_fields')
            ->where('customer_id', $cust['id'])
            ->find_many();
        $ip = ORM::for_table('tbl_port_pool')
            ->where('routers', $plan['routers'])
            ->find_one();
        $toPorts = ['Winbox' => '8291', 'Web' => '80', 'Api' => '8728'];
        if ($ip) {
            // with a port pool for this router, all three forwards are needed: one whose port could not be reserved (the range
            // is full) is missing, and is tried again next time (the ports already reserved are kept)
            $named = [];
            foreach ($tcf as $cf) {
                $named[$cf['field_name']] = true;
            }
            if (count(array_intersect_key($toPorts, $named)) < count($toPorts)) {
                $ok = false;
            }
        }
        $pub = $ip ? (string) $ip['public_ip'] : '';
        foreach ($tcf as $cf) {
            $dst = (string) $cf['field_value'];
            $cmnt = $cf['field_name'];
            if (!isset($toPorts[$cmnt])) {
                continue;
            }
            $tp = $toPorts[$cmnt];
            // a forward is this customer's by the id at the end of its comment, which stays when the username changes
            $want = $cmnt . ' || ' . $cust['username'] . ' || ' . $cust['id'];
            $mine = '/^' . preg_quote($cmnt . ' || ', '/') . '.* \|\| ' . preg_quote((string) $cust['id'], '/') . '$/';
            // forwards made before the id was added carry only the username
            $legacy = $cmnt . ' || ' . $cust['username'];
            $exact = function ($r) use ($dst, $tp, $pub, $ips) {
                return $r['disabled'] !== 'true' && $r['chain'] === 'dstnat' && $r['action'] === 'dst-nat' && $r['protocol'] === 'tcp'
                    && $r['dst-port'] === $dst && $r['to-ports'] === $tp && $r['dst-address'] === $pub
                    && (!isset($r['to-addresses']) || $r['to-addresses'] === (string) $ips);
            };
            $keep = null;   // the forward that is in place
            $stale = [];    // this customer's forwards that differ from it, or second copies
            $old = [];      // username-only forwards that are exactly this customer's reserved port to this address
            foreach ($rules as $r) {
                if (preg_match($mine, $r['comment'])) {
                    if ($keep === null && $exact($r)) {
                        $keep = $r;
                    } else {
                        $stale[] = $r;
                    }
                } elseif ($r['comment'] === $legacy && (!isset($r['to-addresses']) || $r['to-addresses'] === (string) $ips)) {
                    // made before the id was added: this customer's too, as it has their username and service and points at
                    // their address (every rule here does). As it should be, it is kept; otherwise (an old public port, say)
                    // it is changed or removed like their other forwards, so a port given to someone else later is not
                    // claimed twice
                    if ($exact($r)) {
                        $old[] = $r;
                    } else {
                        $stale[] = $r;
                    }
                }
                // any other forward (another comment, or a username-only one to another address) is not known to be this
                // customer's, and is left untouched
            }
            if ($keep === null && $old) {
                // an older forward that is exactly as it should be: kept, and given the id in its comment (if the router
                // refuses that, it still works and is tried again next time)
                $keep = array_shift($old);
                $setRequest = new RouterOS\Request('/ip/firewall/nat/set');
                $setRequest->setArgument('numbers', $keep['.id']);
                $setRequest->setArgument('comment', $want);
                $client->sendSync($setRequest);
            }
            if ($keep === null && $stale && $pub !== '') {
                // this customer's forward, but not as it should be (an old public port, disabled): changed in place, so the
                // forward is never missing
                $setRequest = new RouterOS\Request('/ip/firewall/nat/set');
                $res = $client->sendSync(
                    $setRequest
                        ->setArgument('numbers', $stale[0]['.id'])
                        ->setArgument('chain', 'dstnat')
                        ->setArgument('protocol', 'tcp')
                        ->setArgument('dst-port', $dst)
                        ->setArgument('action', 'dst-nat')
                        ->setArgument('to-addresses', $ips)
                        ->setArgument('to-ports', $tp)
                        ->setArgument('dst-address', $pub)
                        ->setArgument('disabled', 'no')
                        ->setArgument('comment', $want)
                );
                if (RouterApi::error($res) === null) {
                    $keep = array_shift($stale);
                }
            }
            if ($keep === null) {
                $addRequest = new RouterOS\Request('/ip/firewall/nat/add');
                $res = $client->sendSync(
                    $addRequest
                        ->setArgument('chain', 'dstnat')
                        ->setArgument('protocol', 'tcp')
                        ->setArgument('dst-port', $dst)
                        ->setArgument('action', 'dst-nat')
                        ->setArgument('to-addresses', $ips)
                        ->setArgument('to-ports', $tp)
                        ->setArgument('dst-address', $ip['public_ip'])
                        ->setArgument('comment', $want)
                );
                if (RouterApi::error($res) !== null) {
                    // refused: the forwards already there are kept as they are (an old one is better than none)
                    $ok = false;
                    continue;
                }
            }
            // the intended forward is confirmed in place: only now are the other copies of this customer's removed
            foreach (array_merge($stale, $old) as $r) {
                $removeRequest = new RouterOS\Request('/ip/firewall/nat/remove');
                $removeRequest->setArgument('numbers', $r['.id']);
                if (RouterApi::error($client->sendSync($removeRequest)) !== null) {
                    $ok = false;
                }
            }
        }
        return $ok;
    }

    function rmNat($client, $cstid)
    {
        global $_app_stage;
        if (strtolower((string) $_app_stage) === 'demo') {
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

    /** A database lock (GET_LOCK), so one PHP worker at a time hands out from the same range; false when not got in time */
    private function dbLock($name, $wait = 30)
    {
        try {
            return (int) ORM::getDb()->query('SELECT GET_LOCK(' . ORM::getDb()->quote($name) . ', ' . (int) $wait . ')')->fetchColumn() === 1;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function dbUnlock($name)
    {
        try {
            ORM::getDb()->query('SELECT RELEASE_LOCK(' . ORM::getDb()->quote($name) . ')');
        } catch (Throwable $e) {
        }
    }

    /** One free value, at random, from $candidates that are not keys of $taken; null when every one is taken */
    private static function pickFree(array $candidates, array $taken)
    {
        $free = [];
        foreach ($candidates as $c) {
            if (!isset($taken[$c])) {
                $free[] = $c;
            }
        }
        return $free ? $free[random_int(0, count($free) - 1)] : null;
    }

    /**
     * Reserve a public port for this customer's forward ($portn: Winbox, Api or Web) from the router's port pool, unless one is
     * reserved already. One request at a time per router (a database lock): two customers at once would otherwise both take
     * the same free port before either is saved. Every free port in the range is tried, not only a few drawn at random; with
     * none left (or the lock not got in time) nothing is reserved, and addNat() reports the missing forward.
     */
    function checkPort($id, $portn, $router)
    {
        $ports = ORM::for_table('tbl_port_pool')
            ->where('routers', $router)
            ->find_one();
        if (empty($ports)) {
            return;
        }
        $range = explode('-', (string) $ports['range_port']);
        $lo = (int) trim($range[0]);
        $hi = isset($range[1]) ? (int) trim($range[1]) : $lo;
        if ($lo < 1 || $hi > 65535 || $lo > $hi) {
            return;
        }
        $lock = 'phpnuxbill_vpn_port_' . substr(sha1((string) $router), 0, 32);
        if (!$this->dbLock($lock)) {
            return;
        }
        try {
            // read under the lock: a port another request has just reserved is seen
            $tcf = ORM::for_table('tbl_customers_fields')
                ->where('customer_id', $id)
                ->where('field_name', $portn)
                ->find_one();
            if (!empty($tcf)) {
                return;
            }
            // a port is taken when any field holds it, as before (MySQL compares a text value that starts with digits as that number)
            $taken = [];
            $rows = ORM::for_table('tbl_customers_fields')
                ->select('field_value')
                ->where_raw("field_value REGEXP '^[[:space:]]*[0-9]'")
                ->find_array();
            foreach ($rows as $row) {
                $taken[(int) trim($row['field_value'])] = true;
            }
            $portr = self::pickFree(range($lo, $hi), $taken);
            if ($portr === null) {
                return;
            }
            $cf = ORM::for_table('tbl_customers_fields')->create();
            $cf->customer_id = $id;
            $cf->field_name = $portn;
            $cf->field_value = $portr;
            $cf->save();
        } finally {
            $this->dbUnlock($lock);
        }
    }

    /**
     * A free address from the pool for a customer who has none (null when they have one, or none is left). Called while the
     * address lock is held (reserveIp()). Every address in the range is considered, not only a few drawn at random.
     */
    function checkIpAddr($pname, $id)
    {
        $c = ORM::for_table('tbl_customers')->find_one($id);
        if (!empty($c['pppoe_ip'])) {
            return null;
        }
        $ipp = ORM::for_table('tbl_pool')
            ->where('pool_name', $pname)
            ->find_one();
        $ip_r = explode('-', (string) ($ipp ? $ipp['range_ip'] : ''));
        if (count($ip_r) !== 2 || !filter_var(trim($ip_r[0]), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            || !filter_var(trim($ip_r[1]), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return null;
        }
        // every address from the start to the end of the range, across the last part's boundaries too (10.0.0.250-10.0.1.5),
        // and never one outside it; written the other way round, the range is read from the lower to the higher address
        $a = ip2long(trim($ip_r[0]));
        $b = ip2long(trim($ip_r[1]));
        $from = min($a, $b);
        $to = min(max($a, $b), $from + 65535); // a very large range: its first 65536 addresses
        $taken = [];
        foreach (ORM::for_table('tbl_customers')->select('pppoe_ip')->where_not_equal('pppoe_ip', '')->find_array() as $row) {
            $taken[trim((string) $row['pppoe_ip'])] = true;
        }
        $candidates = [];
        for ($n = $from; $n <= $to; $n++) {
            $candidates[] = long2ip($n);
        }
        return self::pickFree($candidates, $taken);
    }
}
