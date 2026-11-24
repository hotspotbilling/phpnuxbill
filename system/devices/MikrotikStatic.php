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
        if ($res instanceof RouterOS\ResponseCollection) {
            $res->rewind(); // error() iterated the replies: back to the first, for getProperty()
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
        $cid = $this->getIdByCustomer($customer, $client, true); // a refused lookup is thrown, never taken for a missing queue
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
            $this->queueWritten($client->sendSync($setRequest));
            if (!$isExp) {
                $this->clearExpired($client, $customer['pppoe_ip']);
            }
        }
    }

    /**
     * A queue add or set the router refused (a !trap) is thrown as a failed provisioning, in every mode (not only during a sync):
     * nothing was saved, so the customer stays on the Expired list and no cleanup follows.
     */
    private function queueWritten($res)
    {
        if (($err = RouterApi::error($res)) !== null) {
            throw new Exception(Lang::T('The router refused the command') . ': ' . $err);
        }
        return $res;
    }

    /**
     * Take the customer's address off the Expired list, after the queue for it is in place. A cleanup the router refuses or does
     * not answer is thrown as a partial update (MikrotikPppoe::PARTIAL), in every mode: the queue was saved, the address may
     * still be blocked.
     */
    private function clearExpired($client, $ip)
    {
        try {
            $err = $this->removeIpFromAddressList($client, $ip);
        } catch (Exception $e) {
            // no answer (the connection is dropped): the queue was saved, whether the address left the list is not known
            throw new Exception(Lang::T('Saved, but the address could not be taken off the Expired list') . ': ' . $e->getMessage(), 207, $e);
        }
        if ($err !== null) {
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
                // the address goes on the Expired list even when the expired plan's queue was refused (thrown below)
                $queueErr = null;
                try {
                    $this->add_customer($customer, $p);
                } catch (Exception $e) {
                    $queueErr = $e;
                }
                $listErr = $this->expiredListAdd($client, $customer);
                if ($queueErr !== null) {
                    if ($listErr !== null) {
                        // both refused: both are said
                        throw new Exception($queueErr->getMessage() . '; ' . Lang::T('the address could not be put on the Expired list') . ': ' . $listErr, (int) $queueErr->getCode(), $queueErr);
                    }
                    throw $queueErr;
                }
                $this->throwListPartial($listErr);
                return;
            }
        }
        $listErr = $this->expiredListAdd($client, $customer);
        $this->removeStaticUser($client, $customer['username']);
        if (!empty($customer['pppoe_username'])) {
            $this->removeStaticUser($client, $customer['pppoe_username']);
        }
        $this->throwListPartial($listErr); // the queues are gone, only the address is not blocked: a partial result
    }

    /** The router's refusal of the address-list add (or the lost connection's message), null when the address is on the list */
    private function expiredListAdd($client, $customer)
    {
        try {
            return $this->addIpToAddressList($client, $customer['pppoe_ip'], 'Expired', $customer['username']);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    private function throwListPartial($listErr)
    {
        if ($listErr !== null) {
            throw new Exception(Lang::T('Saved, but the address could not be put on the Expired list') . ': ' . $listErr, 207);
        }
    }

    // customer change username
    public function change_username($plan, $from, $to)
    {
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        //check if customer exists
        $cid = $this->queueId($client, $from, true);
        if (!empty($cid)) {
            $setRequest = new RouterOS\Request('/queue/simple/set');
            $setRequest->setArgument('numbers', $cid);
            $setRequest->setArgument('name', $to);
            // a refused rename is thrown, so the caller does not go on as if it had worked
            $this->queueWritten($client->sendSync($setRequest));
        }
    }

    public function add_plan($plan)
    {
    }

    /**
     * Function to ID by username from Mikrotik. A lookup the router refuses (a !trap) is thrown (unless $checked is false), so a
     * refusal is never taken for "no such queue" and no queue is made on it; empty means the router answered and has none.
     */
    public function getIdByCustomer($customer, $client, $checked = true)
    {
        $id = $this->queueId($client, $customer['username'], $checked);
        if (empty($id) && !empty($customer['pppoe_username'])) {
            $id = $this->queueId($client, $customer['pppoe_username'], $checked);
        }
        return $id;
    }

    /** The .id of the queue with this name (empty when none); with $checked, the router's refusal is thrown */
    private function queueId($client, $name, $checked)
    {
        $printRequest = new RouterOS\Request('/queue/simple/print');
        $printRequest->setQuery(RouterOS\Query::where('name', $name));
        $res = $client->sendSync($printRequest);
        if ($checked && ($err = RouterApi::error($res)) !== null) {
            throw new Exception(Lang::T('The router refused the command') . ': ' . $err);
        }
        if ($res instanceof RouterOS\ResponseCollection) {
            $res->rewind(); // error() iterated the replies: back to the first, for getProperty()
        }
        return $res->getProperty('.id');
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
        $this->queueWritten($client->sendSync($setRequest));
        if (!$isExp) {
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
        $err = RouterApi::error($client->sendSync(
            $addRequest
                ->setArgument('address', $ip)
                ->setArgument('comment', $comment)
                ->setArgument('list', $listName)
        ));
        // an address already on the list is what was asked for
        if ($err !== null && stripos($err, 'already have') !== false) {
            return null;
        }
        return $err; // the router's refusal, or null when added
    }

    public function removeIpFromAddressList($client, $ip, $listName = 'Expired')
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        // only the entry for this address on this list: the same address on another list is left alone
        $printRequest = new RouterOS\Request(
            '/ip firewall address-list print .proplist=.id',
            RouterOS\Query::where('address', $ip)->andWhere('list', $listName)
        );
        $res = $client->sendSync($printRequest);
        if (($err = RouterApi::error($res)) !== null) {
            return $err;
        }
        $res->rewind(); // error() iterated the replies: back to the first, or getProperty() has no reply to read
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
