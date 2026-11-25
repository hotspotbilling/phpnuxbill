<?php

use PEAR2\Net\RouterOS;

class MikrotikVpn
{
    // set while sync_customer() runs: a change the router refuses is thrown, so the sync counts that customer as not sent
    private $strict = false;
    // set while remove_customer() applies the expired plan: only the secret's profile is changed, no address is reserved and
    // no port forward is touched, so a full pool never keeps an expired customer on their paid profile
    private $expiring = false;
    // set while add_customer() holds the one lock for every VPN request (a database that cannot hold two locks at once)
    private $allLock = false;

    /** The code of an exception for a partial update: the secret was saved, a later step was refused (like MikrotikPppoe) */
    const PARTIAL = 207;

    // match fields an operator may add to a forward to restrict who reaches it: copied when a forward is replaced. A forward
    // with any other field set (one not in NAT_KNOWN either) is never replaced: its full match cannot be copied
    const NAT_MATCH = ['src-address', 'src-address-list', 'src-port', 'src-mac-address', 'src-address-type', 'in-interface',
        'in-interface-list', 'dst-address-list', 'dst-address-type', 'connection-state', 'connection-type', 'connection-mark',
        'packet-mark', 'routing-mark', 'ipsec-policy', 'hotspot', 'time', 'tls-host', 'layer7-protocol', 'content'];
    // the fields a forward is made of (set here), and those that only describe it (counters, flags, logging)
    const NAT_KNOWN = ['.id', '.nextid', 'comment', 'chain', 'action', 'protocol', 'dst-port', 'dst-address', 'to-addresses',
        'to-ports', 'disabled', 'dynamic', 'invalid', 'bytes', 'packets', 'log', 'log-prefix'];

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
        if (self::isDemo()) {
            return null; // no client in the demo stage: nothing is sent
        }
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        // one request at a time for this customer on this router (a database lock, so on every PHP worker), whether or not an
        // address is already reserved: the reservation and the secret are looked at again under it, so two requests at once
        // never both create the secret, nor does one release an address the other has just used
        // On a database that releases the lock it holds when it takes another (MySQL before 5.7.5, MariaDB before 10.0.2) the
        // address and port locks taken below would free this one: there, one lock covers every VPN request instead.
        $nested = self::nestedLocks();
        $lock = $nested ? self::customerLock($plan['routers'], $customer['id']) : 'phpnuxbill_vpn_all';
        if (!$this->dbLock($lock)) {
            throw new Exception(Lang::T('This customer is being set up on the router by another request. Try again in a moment.'));
        }
        $this->allLock = !$nested;
        try {
            $this->provision($client, $customer, $plan);
        } finally {
            $this->allLock = false;
            $this->dbUnlock($lock);
        }
    }

    /** The name of the lock add_customer() holds for this customer on this router */
    private static function customerLock($router, $customerId)
    {
        return 'phpnuxbill_vpn_c_' . substr(sha1($router . '|' . $customerId), 0, 32);
    }

    /**
     * For an edit of a customer's saved address: the locks provisioning takes, in the same order (the customer's lock on each of
     * these routers, then the address lock; where locks cannot be nested, the one lock for every VPN request). Held while the
     * new address is checked and saved, so the old one is never freed while a provisioning still uses it, and no reservation
     * hands out the new one at the same time. Returns the names taken, or false when one was not got in time (none held then).
     */
    public static function lockAddressEdit($customerId, array $routers)
    {
        if (self::nestedLocks()) {
            $names = [];
            foreach (array_unique(array_map('strval', $routers)) as $r) {
                $names[] = self::customerLock($r, $customerId);
            }
            sort($names);
            $names[] = 'phpnuxbill_vpn_ip';
        } else {
            $names = ['phpnuxbill_vpn_all'];
        }
        $dev = new self();
        $held = [];
        foreach ($names as $n) {
            if (!$dev->dbLock($n)) {
                self::unlockAddressEdit($held);
                return false;
            }
            $held[] = $n;
        }
        return $held;
    }

    /** Release what lockAddressEdit() took */
    public static function unlockAddressEdit($held)
    {
        $dev = new self();
        foreach (array_reverse((array) $held) as $n) {
            $dev->dbUnlock($n);
        }
    }

    /**
     * Keep an address that has just left a customer's saved record unavailable to checkIpAddr() until the router's secret has
     * been changed (releaseHeld()), or for $ttl seconds when that is not confirmed (a failed or partial device update): the
     * database save alone does not free it, the old secret may still use it. Call under the locks lockAddressEdit() took.
     */
    public static function holdAddress($ip, $ttl = 21600)
    {
        $ip = trim((string) $ip);
        if ($ip === '') {
            return;
        }
        $row = ORM::for_table('tbl_appconfig')->where('setting', 'vpn_held_' . $ip)->find_one();
        if (!$row) {
            $row = ORM::for_table('tbl_appconfig')->create();
            $row->setting = 'vpn_held_' . $ip;
        }
        $row->value = (string) (time() + (int) $ttl);
        $row->save();
    }

    /** The address is free again (the router no longer uses it) */
    public static function releaseHeld($ip)
    {
        $row = ORM::for_table('tbl_appconfig')->where('setting', 'vpn_held_' . trim((string) $ip))->find_one();
        if ($row) {
            $row->delete();
        }
    }

    /** add_customer() under the customer's lock */
    private function provision($client, $customer, $plan)
    {
        global $isChangePlan;
        // the address saved for this customer now (another request may have reserved, adopted or released one since $customer
        // was read): only the address is taken from the database, the caller's other changes are kept
        $fresh = ORM::for_table('tbl_customers')->find_one($customer['id']);
        if ($fresh) {
            // and the credentials as saved now: a password reset that finished while this waited for the lock is not overwritten on
            // the router with the value the caller read earlier
            foreach (['pppoe_ip', 'password', 'pppoe_password'] as $col) {
                if (is_object($customer)) {
                    $customer->$col = (string) $fresh[$col];
                } else {
                    $customer[$col] = (string) $fresh[$col];
                }
            }
        }
        // the lookup must have been accepted in every mode: a refused one is thrown, never taken for "no secret" (and one made on it)
        $cid = self::getIdByCustomer($customer, $client, true);
        if (empty($cid)) {
            $this->addVpnUser($client, $plan, $customer);
        } else {
            $setRequest = new RouterOS\Request('/ppp/secret/set');
            $setRequest->setArgument('numbers', $cid);
            // the expired plan changes the profile only: the secret's name and password stay as they are
            if ($this->expiring) {
                // (nothing else is written here)
            } elseif (!empty($customer['pppoe_password'])) {
                $setRequest->setArgument('password', $customer['pppoe_password']);
            } else {
                $setRequest->setArgument('password', $customer['password']);
            }
            if ($this->expiring) {
                // (name left as it is)
            } elseif (!empty($customer['pppoe_username'])) {
                $setRequest->setArgument('name', $customer['pppoe_username']);
            } else {
                $setRequest->setArgument('name', $customer['username']);
            }
            // a secret the router has but whose address was never saved here (its port forwards failed after it was added):
            // the address it was given is kept, and the forwards are finished below
            $resume = '';
            // the expired plan changes only the profile: the address on the router is left as it is
            $keepAddr = $this->expiring;
            // the secret could not be read and no address is saved: its forwards cannot be checked
            $addrUnknown = false;
            if (empty($customer['pppoe_ip']) && !$this->expiring) {
                $readRequest = new RouterOS\Request('/ppp/secret/print');
                $readRequest->setQuery(RouterOS\Query::where('.id', $cid));
                $readRes = $client->sendSync($readRequest);
                if (($readErr = RouterApi::error($readRes)) !== null) {
                    // the secret could not be read, so whether it has a fixed address is unknown: a sync stops here, otherwise the
                    // address on the router is left as it is (never overwritten with the default)
                    if ($this->strict) {
                        throw new Exception($readErr);
                    }
                    $keepAddr = true;
                    $addrUnknown = true;
                } else {
                    $given = (string) self::prop($readRes, 'remote-address');
                    if (filter_var($given, FILTER_VALIDATE_IP) && $given !== '0.0.0.0') {
                        $resume = $given;
                    }
                }
            }
            $reserved = '';
            $oldType = $customer['service_type'];
            // an address reserved by an earlier request of this customer whose outcome was never confirmed (see autoPending())
            $pending = (!$this->expiring && !empty($customer['pppoe_ip']) && self::autoPending($customer, (string) $customer['pppoe_ip']))
                ? (string) $customer['pppoe_ip'] : '';
            if (!$this->expiring) {
                // who holds the address is settled before the secret is changed: an address saved for another customer as
                // well (an edit, say) is not written, nor are forwards made to it; one the router gave this secret is saved
                // for this customer first (under the address lock), unless another customer holds it
                if (!empty($customer['pppoe_ip']) && $this->heldByOther($customer, (string) $customer['pppoe_ip'])) {
                    throw new Exception(Lang::T('This IP address is already saved for another customer') . ': ' . $customer['pppoe_ip']);
                }
                if ($resume !== '' && !$this->claimIp($customer, $resume)) {
                    // nothing is sent: it is reported to be sorted out by hand
                    throw new Exception(Lang::T('The address of this VPN secret on the router is saved for another customer') . ': ' . $resume);
                }
            }
            if ($this->expiring) {
                // the expired plan changes only the profile: no address is written, not even the saved one
            } elseif (!empty($customer['pppoe_ip'])) {
                $setRequest->setArgument('remote-address', $customer['pppoe_ip']);
            } elseif ($resume !== '') {
                $setRequest->setArgument('remote-address', $resume);
            } elseif (!$keepAddr && ORM::for_table('tbl_port_pool')->where('routers', $plan['routers'])->find_one()) {
                // read, and it has no fixed address (0.0.0.0), on a router whose customers get port forwards: one is reserved
                // from the pool and given to it, so the forwards can be made (none free: thrown, never passed as done without them)
                $reserved = $this->reserveIp($plan, $customer);
                $setRequest->setArgument('remote-address', $reserved);
            } elseif (!$keepAddr) {
                // read, and it has no fixed address, and no forwards are needed: the default
                $setRequest->setArgument('remote-address', '0.0.0.0');
            }
            $setRequest->setArgument('profile', $plan['name_plan']);
            if (!$this->expiring) {
                $setRequest->setArgument('comment', $customer['fullname'] . ' | ' . $customer['email'] . ' | ' . implode(', ', User::getBillNames($customer['id'])));
            }
            // (no answer is thrown by sendSync() and keeps a reserved address, like addVpnUser())
            $res = $client->sendSync($setRequest);
            if (($err = RouterApi::error($res)) !== null) {
                // a refusal is thrown on every path (not only in a sync, nor only for a new address): the customer is not
                // disconnected and the update is not reported as done, the expired plan's included
                $auto = $reserved !== '' ? $reserved : $pending;
                if ($auto !== '') {
                    // refused: the address goes back to the pool only when the secret is confirmed to have no fixed address (the
                    // refused change was not applied); when it has the reserved one, or that cannot be read, it is kept
                    $now = $this->secretAddress($client, $cid);
                    if ($now === '' || $now === '0.0.0.0') {
                        $this->dropReservation($customer, $auto, $oldType, (string) $plan['routers']);
                    }
                }
                throw new Exception(Lang::T('The router refused the VPN secret') . ': ' . $err);
            }
            $taken = true;
            self::autoClear($customer); // the router took the secret: the reservation is no longer pending
            $addr = !empty($customer['pppoe_ip']) ? (string) $customer['pppoe_ip'] : $resume;
            // the forwards are checked against the router each time, not inferred from the saved address: one refused before is
            // added now, and one already there (by its comment) is not added again. Only once the router took the change above:
            // a refused secret gets no forwards. The expired plan touches no forwards.
            $natErr = null;
            if ($addr !== '' && $taken && !$this->expiring) {
                $natErr = $this->natAfterSave($client, $plan, $customer, $addr);
            } elseif ($addrUnknown && $taken && ORM::for_table('tbl_port_pool')->where('routers', $plan['routers'])->find_one()) {
                // the profile was changed, but the secret's address is unknown, so its forwards could not be checked
                $natErr = new Exception(Lang::T('Saved, but the address of this VPN secret could not be read, so its port forwards were not checked'), self::PARTIAL);
            }
            $discErr = null;
            if (isset($isChangePlan) && $isChangePlan) {
                // every session is tried; a failure here never replaces the partial result recorded above
                foreach (array_unique(array_filter([(string) $customer['username'], (string) $customer['pppoe_username']])) as $sessName) {
                    try {
                        $this->removeVpnActive($client, $sessName);
                    } catch (Exception $e) {
                        $discErr = $discErr ?? $e;
                    }
                }
            }
            if ($natErr !== null) {
                if ($discErr !== null) {
                    $natErr = new Exception($natErr->getMessage() . '; ' . Lang::T('the active session could not be disconnected') . ': ' . $discErr->getMessage(), (int) $natErr->getCode(), $natErr->getPrevious() ?? $natErr);
                }
                throw $natErr;
            }
            if ($discErr !== null) {
                // the secret is saved: a session that could not be ended is a partial result, with the cause kept
                throw new Exception($discErr->getMessage(), self::PARTIAL, $discErr);
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
        if (self::isDemo()) {
            return null; // no client in the demo stage: nothing is sent
        }
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        if (!empty($plan['plan_expired'])) {
            $p = ORM::for_table("tbl_plans")->find_one($plan['plan_expired']);
            if ($p) {
                $this->expiring = true;
                try {
                    $this->add_customer($customer, $p);
                } finally {
                    $this->expiring = false;
                }
                // the expired profile is on the secret: a session that cannot be ended now is a partial result (207), not a failed
                // update, as the refused case below; both names are tried
                $dropErr = $this->dropActive($client, $customer);
                if ($dropErr !== null) {
                    throw new Exception(Lang::T('the active session could not be disconnected') . ': ' . $dropErr->getMessage(), self::PARTIAL, $dropErr);
                }
                return;
            }
        }
        // the same lock add_customer() takes for this customer on this router (where locks cannot be nested, the one lock for
        // every VPN request): a sync cannot make the forwards for a secret between this removing the secret and its forwards
        $nested = self::nestedLocks();
        $lock = $nested ? self::customerLock($plan['routers'], $customer['id']) : 'phpnuxbill_vpn_all';
        if (!$this->dbLock($lock)) {
            throw new Exception(Lang::T('This customer is being set up on the router by another request. Try again in a moment.'));
        }
        try {
            $this->removeVpnUser($client, $customer['username'], $customer['id']);
            if (!empty($customer['pppoe_username'])) {
                $this->removeVpnUser($client, $customer['pppoe_username'], $customer['id']);
            }
            // each name's session is tried whatever the other did; the first failure is thrown after both
            $dropErr = $this->dropActive($client, $customer);
            if ($dropErr !== null) {
                throw $dropErr;
            }
        } finally {
            $this->dbUnlock($lock);
        }
    }

    /** Ends the sessions of the customer's distinct names, each tried whatever the other did; the first failure (or null) */
    private function dropActive($client, $customer)
    {
        $first = null;
        foreach (array_unique(array_filter([(string) $customer['username'], (string) ($customer['pppoe_username'] ?? '')])) as $name) {
            try {
                $this->removeVpnActive($client, $name);
            } catch (Exception $e) {
                $first = $first ?? $e;
            }
        }
        return $first;
    }

    public function change_username($plan, $from, $to)
    {
        if (self::isDemo()) {
            return null; // no client in the demo stage: nothing is sent
        }
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $printRequest = new RouterOS\Request('/ppp/secret/print');
        $printRequest->setQuery(RouterOS\Query::where('name', $from));
        $cid = $this->secretId($client, $from, true); // a refused lookup is thrown
        if (!empty($cid)) {
            $setRequest = new RouterOS\Request('/ppp/secret/set');
            $setRequest->setArgument('numbers', $cid);
            $setRequest->setArgument('name', $to);
            // a refused rename is thrown, so the caller does not go on as if it had worked
            if (($err = RouterApi::error($client->sendSync($setRequest))) !== null) {
                throw new Exception(Lang::T('The router refused the command') . ': ' . $err);
            }
            try {
                $this->removeVpnActive($client, $from);
            } catch (Exception $e) {
                try {
                    _log('VPN ' . $from . ': ' . $e->getMessage(), 'Router');
                } catch (Throwable $ignored) { // diagnostic only: the router has taken the rename, which the caller must keep
                }
            }
        }
    }

    function add_plan($plan)
    {
        if (self::isDemo()) {
            return null; // no client in the demo stage: nothing is sent
        }
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


    /**
     * The .id of the customer's secret. $checked (by default: during a sync) throws the router's refusal of a lookup (a !trap),
     * so a refusal is never taken for "no such secret" and no secret is made on it; empty means the router has none.
     */
    function getIdByCustomer($customer, $client, $checked = null)
    {
        if (self::isDemo()) {
            return null; // no client in the demo stage: nothing is sent
        }
        $checked = $checked ?? $this->strict;
        $id = $this->secretId($client, $customer['username'], $checked);
        if (empty($id)) {
            if (!empty($customer['pppoe_username'])) {
                $id = $this->secretId($client, $customer['pppoe_username'], $checked);
            }
        }
        return $id;
    }

    /** The .id of the secret with this name (empty when none); with $checked, the router's refusal is thrown */
    private function secretId($client, $name, $checked)
    {
        $printRequest = new RouterOS\Request('/ppp/secret/print');
        $printRequest->setQuery(RouterOS\Query::where('name', $name));
        $res = $client->sendSync($printRequest);
        if ($checked && ($err = RouterApi::error($res)) !== null) {
            throw new Exception($err);
        }
        return self::prop($res, '.id');
    }

    /**
     * A property of the first data item (!re) of a reply, as a string; null when there is none. Read by going through the
     * items, as RouterApi::error() moves the collection's pointer (getProperty() on the collection reads the current item).
     */
    private static function prop($res, $name)
    {
        foreach ($res as $r) {
            if ($r->getType() === RouterOS\Response::TYPE_DATA) {
                return RouterApi::text($r->getProperty($name));
            }
        }
        return null;
    }

    function update_plan($old_name, $new_plan)
    {
        if (self::isDemo()) {
            return null; // no client in the demo stage: nothing is sent
        }
        $mikrotik = $this->info($new_plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);

        $printRequest = new RouterOS\Request(
            '/ppp profile print .proplist=.id',
            RouterOS\Query::where('name', $old_name['name_plan'])
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');
        if (empty($profileID) && $old_name['name_plan'] !== $new_plan['name_plan']) {
            // a retry after the profile was renamed already (or created under the new name): the existing one is updated, not added again
            $printNew = new RouterOS\Request('/ppp profile print .proplist=.id', RouterOS\Query::where('name', $new_plan['name_plan']));
            $profileID = $client->sendSync($printNew)->getProperty('.id');
            // a profile already under the new name is ours only when an earlier save of this plan failed after the rename (the note that
            // services.php keeps, plan_rename_pending_<id>): otherwise it is someone else's, and is reported, never overwritten
            if (!empty($profileID) && !ORM::for_table('tbl_appconfig')->where('setting', 'plan_rename_pending_' . (int) $new_plan['id'])->find_one()) {
                throw new Exception(Lang::T('A profile named') . ' "' . $new_plan['name_plan'] . '" ' . Lang::T('already exists on the router and was not made by this plan, so nothing was changed. Rename or remove it there, or choose another plan name.'), 409);
            }
        }
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
        if (self::isDemo()) {
            return null; // no client in the demo stage: nothing is sent
        }
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
        if (self::isDemo()) {
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
        if (self::isDemo()) {
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
        if (self::isDemo()) {
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
        if (self::isDemo()) {
            return null; // no client in the demo stage: nothing is sent
        }
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

    /** The demo stage ("Demo", "demo", with spaces around it or not): no router is ever asked anything */
    private static function isDemo()
    {
        global $_app_stage;
        return strcasecmp(trim((string) $_app_stage), 'demo') === 0;
    }

    function getClient($ip, $user, $pass)
    {
        if (self::isDemo()) {
            return null;
        }
        [$host, $port] = RouterAddress::split($ip); // "host:port", or "[IPv6]:port"
        return RouterApi::deviceClient($host, $user, $pass, $port); // every read time-limited
    }

    function removeVpnUser($client, $username, $cstid)
    {
        if (self::isDemo()) {
            return null;
        }
        $printRequest = new RouterOS\Request('/ppp/secret/print');
        //$printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $username));
        $res = $client->sendSync($printRequest);
        if (($err = RouterApi::error($res)) !== null) {
            throw new Exception(Lang::T('The router refused the command') . ': ' . $err); // a refused lookup is not "no secret"
        }
        $id = $res->getProperty('.id'); // (error() leaves the collection at its first reply)
        if (!empty($id)) { // none: already gone, and the forwards below are still cleared
            $removeRequest = new RouterOS\Request('/ppp/secret/remove');
            $removeRequest->setArgument('numbers', $id);
            if (($err = RouterApi::error($client->sendSync($removeRequest))) !== null) {
                throw new Exception(Lang::T('The router refused the command') . ': ' . $err); // the secret is still there: not a done removal
            }
        }
        $this->rmNat($client, $cstid);
    }

    function addVpnUser($client, $plan, $customer)
    {
        if (self::isDemo()) {
            return null; // no client in the demo stage: nothing is sent
        }
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
        $ips = (string) $customer['pppoe_ip'];
        $oldType = $customer['service_type'];
        $reserved = false;
        if ($ips === '' && !$this->expiring) {
            // the new address is saved for this customer before the router is asked: it is then never handed to another
            // customer, whether the secret below is taken, refused or its answer is lost. Released only when the router refuses
            // the secret: after no answer it is kept, and the next add_customer() first looks for the secret
            // (getIdByCustomer()) and either updates it or adds it again with this same address. None left: no secret is made
            // (it would have no fixed address, and no forwards could point at it).
            $ips = $this->reserveIp($plan, $customer);
            $reserved = true;
        }
        // an address an earlier request reserved and never confirmed counts as reserved for a refusal below
        $autoRes = $reserved || (!$this->expiring && $ips !== '' && self::autoPending($customer, $ips));
        if ($this->expiring) {
            // the expired plan writes no address, not even the saved one
            $ips = '';
        } elseif (!$reserved && $this->heldByOther($customer, $ips)) {
            // an address saved for another customer as well: no secret is made with it, nor forwards to it
            throw new Exception(Lang::T('This IP address is already saved for another customer') . ': ' . $ips);
        }
        if (!is_string($ips) || !filter_var($ips, FILTER_VALIDATE_IP)) {
            if (!$this->expiring) {
                throw new Exception(Lang::T('No free IP address is left in the pool') . ' ' . $plan['pool']);
            }
            // the expired plan: the secret is made without a fixed address rather than not at all
            $ips = '';
        } else {
            $setRequest->setArgument('remote-address', $ips);
        }
        // the secret first: the port forwards (and the ports reserved for them) are made only for a customer the router took,
        // so a refused secret leaves no rules pointing at an address that was never given out
        $res = $client->sendSync($setRequest);
        if (($err = RouterApi::error($res)) !== null) {
            if ($autoRes && $ips !== '') {
                $this->releaseIp($client, $customer, $ips, $oldType, (string) $plan['routers']);
            }
            // thrown (not only during a sync), so a recharge records it and sends its alert instead of passing as done
            throw new Exception(Lang::T('The router refused the VPN secret') . ': ' . $err);
        }
        self::autoClear($customer); // the router took the secret: the reservation is confirmed
        if (!$reserved && $ips !== '') {
            // a customer who already had an address: only the type is saved, as before. Forwards are not tracked here: each
            // later add_customer() checks them on the router and adds the missing ones.
            $customer->service_type = 'VPN';
            $customer->pppoe_ip = $ips;
            $customer->save();
        }
        if ($this->expiring || $ips === '') {
            return; // the expired plan touches no forwards
        }
        $natErr = $this->natAfterSave($client, $plan, $customer, $ips);
        if ($natErr !== null) {
            throw $natErr;
        }
    }

    /**
     * addNat() once the secret is saved: null when every forward is in place; otherwise a partial update (PARTIAL), during a
     * sync too, as the secret stands. The cause (a refusal, or an exception) is kept as the previous exception.
     */
    private function natAfterSave($client, $plan, $customer, $addr)
    {
        $msg = Lang::T('Saved, but a port forward for this customer could not be made');
        try {
            if ($this->addNat($client, $plan, $customer, $addr)) {
                return null;
            }
            $cause = new Exception(Lang::T('The router refused a port forward for this customer'));
        } catch (Throwable $e) {
            $cause = $e;
        }
        return new Exception($msg . ': ' . $cause->getMessage(), self::PARTIAL, $cause);
    }

    /**
     * An address reserved automatically (reserveIp()) is marked in tbl_appconfig (vpn_auto_<customer id> = address|expiry) until
     * the router has taken the secret, so a later request can tell it from an address an admin assigned. The mark is short-lived.
     */
    private static function autoSet($customer, $ip, $type = '', $router = '')
    {
        try {
            $key = 'vpn_auto_' . (int) $customer['id'];
            $row = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
            if (!$row) {
                $row = ORM::for_table('tbl_appconfig')->create();
                $row->setting = $key;
            }
            // address|expiry|the service type before the reservation|the router asked: a retry gives back the original type, and
            // only on the router the reservation was made for (another one cannot tell whether this router took the secret)
            $row->value = $ip . '|' . (time() + 21600) . '|' . str_replace('|', '', (string) $type) . '|' . str_replace('|', '', (string) $router);
            $row->save();
        } catch (Throwable $e) {
        }
    }

    /** Whether $ip is this customer's reservation still waiting for the router's confirmation */
    private static function autoPending($customer, $ip)
    {
        try {
            $row = ORM::for_table('tbl_appconfig')->where('setting', 'vpn_auto_' . (int) $customer['id'])->find_one();
            if (!$row) {
                return false;
            }
            [$addr, $until] = array_pad(explode('|', (string) $row['value'], 3), 2, '0');
            if ((int) $until < time()) {
                $row->delete();
                return false;
            }
            return $addr === (string) $ip;
        } catch (Throwable $e) {
            return false;
        }
    }

    /** [type, router] a reservation was made with, or ['', ''] when none was recorded (an older one) */
    private static function autoMeta($customer, $ip)
    {
        try {
            $row = ORM::for_table('tbl_appconfig')->where('setting', 'vpn_auto_' . (int) $customer['id'])->find_one();
            if ($row) {
                $f = array_pad(explode('|', (string) $row['value']), 4, '');
                if ($f[0] === (string) $ip) {
                    return [$f[2], $f[3]];
                }
            }
        } catch (Throwable $e) {
        }
        return ['', ''];
    }

    private static function autoClear($customer)
    {
        try {
            $row = ORM::for_table('tbl_appconfig')->where('setting', 'vpn_auto_' . (int) $customer['id'])->find_one();
            if ($row) {
                $row->delete();
            }
        } catch (Throwable $e) {
        }
    }

    /** Whether another customer has this address saved */
    private function heldByOther($customer, $ip)
    {
        $ip = trim((string) $ip);
        if ($ip === '') {
            return false;
        }
        if (ORM::for_table('tbl_customers')->where('pppoe_ip', $ip)->where_not_equal('id', $customer['id'])->find_one()) {
            return true;
        }
        // an address another customer was moved off stays reserved (holdAddress) while no row has it: not this customer's to take
        // unless it is the one they have saved themselves
        return $ip !== trim((string) ($customer['pppoe_ip'] ?? ''))
            && (bool) ORM::for_table('tbl_appconfig')->where('setting', 'vpn_held_' . $ip)->find_one();
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
        if (!$this->lockIn($lock)) {
            throw new Exception(Lang::T('Another customer is being given an address from this pool. Try again in a moment.'));
        }
        try {
            $ips = $this->checkIpAddr($plan['pool'], $customer['id']);
            if (!is_string($ips) || !filter_var($ips, FILTER_VALIDATE_IP)) {
                throw new Exception(Lang::T('No free IP address is left in the pool') . ' ' . $plan['pool']);
            }
            $before = (string) $customer->service_type;
            $customer->service_type = 'VPN';
            $customer->pppoe_ip = $ips;
            if ($customer->save() === false) {
                // a save that reports failure without throwing: the address is not reserved, so nothing is sent to the router
                throw new Exception(Lang::T('The address could not be reserved for this customer. Try again in a moment.'));
            }
            self::autoSet($customer, $ips, $before, (string) $plan['routers']);
            return $ips;
        } finally {
            $this->unlockIn($lock);
        }
    }

    /**
     * Save, under the same lock as reserveIp(), an address the router already gave this customer's secret, unless another
     * customer holds it (false then, and nothing is saved). Thrown when the lock is not got.
     */
    private function claimIp($customer, $ips)
    {
        $lock = 'phpnuxbill_vpn_ip';
        if (!$this->lockIn($lock)) {
            throw new Exception(Lang::T('Another customer is being given an address from this pool. Try again in a moment.'));
        }
        try {
            $other = ORM::for_table('tbl_customers')
                ->where('pppoe_ip', $ips)
                ->where_not_equal('id', $customer['id'])
                ->find_one();
            if ($other) {
                return false;
            }
            $customer->service_type = 'VPN';
            $customer->pppoe_ip = $ips;
            if ($customer->save() === false) {
                throw new Exception(Lang::T('The address could not be saved for this customer.')); // never provision with an address that is not recorded
            }
            return true;
        } finally {
            $this->unlockIn($lock);
        }
    }

    /** The remote-address of the secret with this .id ('' when it has none); null when it cannot be read or is not there */
    private function secretAddress($client, $cid)
    {
        $readRequest = new RouterOS\Request('/ppp/secret/print');
        $readRequest->setQuery(RouterOS\Query::where('.id', $cid));
        try {
            $res = $client->sendSync($readRequest);
        } catch (Throwable $e) {
            return null;
        }
        if (RouterApi::error($res) !== null) {
            return null;
        }
        foreach ($res as $r) {
            if ($r->getType() === RouterOS\Response::TYPE_DATA) {
                return trim((string) RouterApi::text($r->getProperty('remote-address')));
            }
        }
        return null;
    }

    /**
     * Give back an address reserved by reserveIp() after the router refused the secret, once it is certain the address is not in
     * use: it is still this customer's saved address, and the router has no secret for the customer (when that cannot be read,
     * the address is kept).
     */
    private function releaseIp($client, $customer, $ips, $oldType, $router = null)
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
            if (RouterApi::error($res) !== null || !empty(self::prop($res, '.id'))) {
                return;
            }
        }
        // and no secret at all uses the address (a secret under another name, say)
        $useRequest = new RouterOS\Request('/ppp/secret/print');
        $useRequest->setArgument('.proplist', '.id');
        $useRequest->setQuery(RouterOS\Query::where('remote-address', $ips));
        try {
            $res = $client->sendSync($useRequest);
        } catch (Throwable $e) {
            return;
        }
        if (RouterApi::error($res) !== null || !empty(self::prop($res, '.id'))) {
            return;
        }
        $this->dropReservation($customer, $ips, $oldType, $router);
    }

    /** Give the reserved address back (and the old type), while it is still this customer's saved address */
    private function dropReservation($customer, $ips, $oldType, $router = null)
    {
        [$savedType, $savedRouter] = self::autoMeta($customer, $ips);
        if ($router !== null && $savedRouter !== '' && $savedRouter !== (string) $router) {
            return; // reserved for another router, which may have taken the secret: kept until that one is reconciled
        }
        if ($savedType !== '') {
            $oldType = $savedType; // the type before the first attempt, not the one a retry found
        }
        $fresh = ORM::for_table('tbl_customers')->find_one($customer['id']);
        if (!$fresh || (string) $fresh['pppoe_ip'] !== (string) $ips) {
            return;
        }
        $customer->service_type = $oldType;
        $customer->pppoe_ip = '';
        $customer->save();
        self::autoClear($customer);
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
                foreach (array_merge(['.id', 'comment', 'chain', 'action', 'protocol', 'dst-port', 'dst-address', 'to-addresses', 'to-ports', 'disabled', 'log', 'log-prefix'], self::NAT_MATCH) as $k) {
                    $row[$k] = (string) RouterApi::text($nat->getProperty($k));
                }
                // RouterOS says a flag "true"/"false" over the API, some versions "yes"/"no": one form here
                $row['disabled'] = in_array(strtolower(trim($row['disabled'])), ['true', 'yes'], true) ? 'true' : 'false';
                // a field set on it that is neither copied nor known (another match, a newer RouterOS one): it cannot be
                // replaced by a copy
                $row['@uncopied'] = [];
                foreach ($nat->getIterator() as $k => $v) {
                    $k = (string) $k;
                    if (!in_array($k, self::NAT_KNOWN, true) && !in_array($k, self::NAT_MATCH, true)
                        && trim((string) RouterApi::text($v)) !== '') {
                        $row['@uncopied'][] = $k;
                    }
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
        if (self::isDemo()) {
            return null;
        }
        $onlineRequest = new RouterOS\Request('/ppp/active/print');
        $onlineRequest->setArgument('.proplist', '.id');
        $onlineRequest->setQuery(RouterOS\Query::where('name', $username));
        $res = $client->sendSync($onlineRequest);
        // a refusal is reported as a partial update (the secret was saved before this), never taken for done
        if (($err = RouterApi::error($res)) !== null) {
            throw new Exception($err, self::PARTIAL);
        }
        // every matching session (a customer can be connected more than once), each one tried; the first refusal is reported after all
        $ids = [];
        foreach ($res as $r) {
            if ($r->getType() === RouterOS\Response::TYPE_DATA) {
                $one = RouterApi::text($r->getProperty('.id'));
                if ($one !== null && $one !== '') {
                    $ids[] = $one;
                }
            }
        }
        $first = null;
        foreach ($ids as $one) { // none: no active session, nothing to remove
            $removeRequest = new RouterOS\Request('/ppp/active/remove');
            $removeRequest->setArgument('numbers', $one);
            if (($err = RouterApi::error($client->sendSync($removeRequest))) !== null) {
                $first = $first ?? $err;
            }
        }
        if ($first !== null) {
            throw new Exception($first, self::PARTIAL);
        }
    }


    function addIpToAddressList($client, $ip, $listName, $comment = '')
    {
        if (self::isDemo()) {
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
        if (self::isDemo()) {
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
     * Nothing is done (and nothing read) when the router has no port pool; with one whose public address is not valid, nothing
     * is sent and false is returned. $rules: the forwards already on the router for this address (natRules(); read here when
     * false), or null when they could not be read. A forward
     * that is there, enabled and as it should be is left alone; one of this customer's (by the id in its comment) that differs
     * (an old public port, disabled) is changed in place, or, when that is refused, replaced by a new one that is removed only
     * after the new one is taken. Returns whether every forward is in place: false when the rules could not be read (nothing
     * is added then, so nothing is doubled), when a port could not be reserved, or when the router refused a change (a refusal
     * comes back as a !trap reply, not as an exception).
     */
    function addNat($client, $plan, $cust, $ips, $rules = false)
    {
        if (self::isDemo()) {
            return true;
        }
        $ip = ORM::for_table('tbl_port_pool')
            ->where('routers', $plan['routers'])
            ->find_one();
        if (!$ip) {
            return true; // no port pool for this router: its customers get no forwards
        }
        $pub = trim((string) $ip['public_ip']);
        if (!filter_var($pub, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) { // the NAT rules below are IPv4 only
            if ($this->strict) {
                throw new Exception(Lang::T('The port pool of this router has no valid public IP address'));
            }
            return false;
        }
        if ($rules === false) {
            $rules = $this->natRules($client, $ips, $cust);
        }
        if ($rules === null) {
            if ($this->strict) {
                throw new Exception(Lang::T('The port forwards on the router could not be read'));
            }
            return false;
        }
        $ok = true;
        $this->reservePorts($cust['id'], $plan['routers'], ['Winbox', 'Api', 'Web']);
        $tcf = ORM::for_table('tbl_customers_fields')
            ->where('customer_id', $cust['id'])
            ->find_many();
        $toPorts = ['Winbox' => '8291', 'Web' => '80', 'Api' => '8728'];
        // all three forwards are needed: one whose port could not be reserved (the range is full) is missing, and is tried
        // again next time (the ports already reserved are kept)
        // one reservation per service, settled before any rule is changed: each service is handled once, with that one port.
        // Two rows for a service with different ports (or a value that is not a port) are a conflict: that service's rules
        // are left as they are and it is reported, never changed by one row and then undone by the other
        $wanted = [];
        foreach ($tcf as $cf) {
            $name = (string) $cf['field_name'];
            if (isset($toPorts[$name])) {
                $wanted[$name][trim((string) $cf['field_value'])] = true;
            }
        }
        foreach ($toPorts as $cmnt => $tp) {
            if (empty($wanted[$cmnt])) {
                $ok = false; // missing: tried again next time
                continue;
            }
            if (count($wanted[$cmnt]) !== 1) {
                $ok = false;
                continue;
            }
            $dst = (string) array_key_first($wanted[$cmnt]);
            if (!preg_match('/^\d{1,5}$/', $dst) || (int) $dst < 1 || (int) $dst > 65535) {
                $ok = false;
                continue;
            }
            $dst = (string) (int) $dst;
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
            if ($keep === null && $stale) {
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
            if ($keep === null && $stale && !empty($stale[0]['@uncopied'])) {
                // it could not be changed in place, and a copy would lose a match it has (it could then be reached by more
                // than it is now): the old one stays as it is, and the repair is reported as not done
                $ok = false;
                continue;
            }
            if ($keep === null) {
                $addRequest = new RouterOS\Request('/ip/firewall/nat/add');
                $addRequest
                    ->setArgument('chain', 'dstnat')
                    ->setArgument('protocol', 'tcp')
                    ->setArgument('dst-port', $dst)
                    ->setArgument('action', 'dst-nat')
                    ->setArgument('to-addresses', $ips)
                    ->setArgument('to-ports', $tp)
                    ->setArgument('dst-address', $pub)
                    ->setArgument('comment', $want);
                // replacing a forward of this customer's: the operator's restrictions on it (who may reach it) go on the new
                // one too; if the router refuses that, the old one stays as it is (below)
                $keep = ['@uncopied' => []];
                if ($stale) {
                    foreach (self::NAT_MATCH as $k) {
                        if (isset($stale[0][$k]) && $stale[0][$k] !== '') {
                            $addRequest->setArgument($k, $stale[0][$k]);
                            $keep[$k] = $stale[0][$k];
                        }
                    }
                    // the old rule's logging goes on the replacement too (a refusal of either keeps the old rule, below)
                    foreach (['log', 'log-prefix'] as $k) {
                        if (isset($stale[0][$k]) && $stale[0][$k] !== '') {
                            $addRequest->setArgument($k, $stale[0][$k]);
                        }
                    }
                    // the replacement takes the original's place in the chain (rule order decides which rule matches first)
                    $addRequest->setArgument('place-before', $stale[0]['.id']);
                }
                $res = $client->sendSync($addRequest);
                if (RouterApi::error($res) !== null) {
                    // refused (a placement included): the forwards already there are kept as they are (an old one is better
                    // than none), and the repair is reported as incomplete
                    $ok = false;
                    $keep = null;
                    continue;
                }
            }
            // the intended forward is confirmed in place: only now are the other copies of this customer's removed
            // only a rule with the same match conditions as the one in place (every NAT_MATCH field, src-address included) is
            // demonstrably the same forward; one that differs, or whose full match cannot be compared, is kept and reported
            $sameMatch = function ($r) use ($keep) {
                if (!empty($r['@uncopied']) || !empty($keep['@uncopied'])) {
                    return false;
                }
                foreach (self::NAT_MATCH as $k) {
                    if ((string) ($r[$k] ?? '') !== (string) ($keep[$k] ?? '')) {
                        return false;
                    }
                }
                return true;
            };
            foreach (array_merge($stale, $old) as $r) {
                if (!$sameMatch($r)) {
                    $ok = false;
                    continue;
                }
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
        if (self::isDemo()) {
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

    /** A lock taken while add_customer() holds its own: none is needed when that one already covers every VPN request */
    private function lockIn($name)
    {
        return $this->allLock ? true : $this->dbLock($name);
    }

    private function unlockIn($name)
    {
        if (!$this->allLock) {
            $this->dbUnlock($name);
        }
    }

    /**
     * Whether the database keeps the locks a connection holds when it takes another (MySQL 5.7.5+, MariaDB 10.0.2+). Before
     * that, GET_LOCK() released the lock already held, so locks must never be nested there. Unknown: taken as no.
     */
    public static function nestedLocks()
    {
        static $ok = null;
        if ($ok === null) {
            try {
                $v = (string) ORM::getDb()->query('SELECT VERSION()')->fetchColumn();
            } catch (Throwable $e) {
                $v = '';
            }
            $maria = stripos($v, 'mariadb') !== false;
            if ($maria) {
                $v = preg_replace('/^5\.5\.5-/', '', $v); // the prefix some MariaDB builds report
            }
            if (preg_match('/^(\d+)\.(\d+)\.(\d+)/', $v, $m)) {
                $n = [(int) $m[1], (int) $m[2], (int) $m[3]];
                $ok = $n >= ($maria ? [10, 0, 2] : [5, 7, 5]);
            } else {
                $ok = false;
            }
        }
        return $ok;
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
     * reserved already (see reservePorts()).
     */
    function checkPort($id, $portn, $router)
    {
        $this->reservePorts($id, $router, [$portn]);
    }

    /**
     * Reserve a public port from the router's port pool for each of this customer's services ($names: Winbox, Api, Web) that has
     * none. One request at a time across every router (a database lock): a port counts as taken whatever router it is on, and a
     * customer's port is looked up by customer and service only, so two requests at once (two customers, or the same one being
     * moved between routers) would otherwise take the same free port, or both add a row for that service. The ports in use in
     * the range are read once under the lock and the set is kept up to date as each service takes one, so the customer fields
     * are not read again for every service. Every free port in the range is tried, not only a few drawn at random; with none
     * left (or the lock not got in time) nothing is reserved, and addNat() reports the missing forward.
     */
    function reservePorts($id, $router, array $names)
    {
        $ports = ORM::for_table('tbl_port_pool')
            ->where('routers', $router)
            ->find_one();
        if (empty($ports)) {
            return;
        }
        // one port, or two separated by a single hyphen (spaces allowed around them); anything else is not read
        if (!preg_match('/^\s*(\d{1,5})\s*(?:-\s*(\d{1,5})\s*)?$/', (string) $ports['range_port'], $range)) {
            return;
        }
        $lo = (int) $range[1];
        $hi = isset($range[2]) && $range[2] !== '' ? (int) $range[2] : $lo;
        if ($lo < 1 || $hi > 65535 || $lo > $hi) {
            return;
        }
        $lock = 'phpnuxbill_vpn_port';
        if (!$this->lockIn($lock)) {
            return;
        }
        try {
            // read under the lock: a port another request has just reserved is seen
            $have = [];
            foreach (ORM::for_table('tbl_customers_fields')->select('field_name')->where('customer_id', $id)->where_in('field_name', $names)->find_array() as $row) {
                $have[$row['field_name']] = true;
            }
            $missing = array_values(array_filter($names, function ($n) use ($have) {
                return !isset($have[$n]);
            }));
            if (!$missing) {
                return;
            }
            // a port is taken when any field holds it, as before (MySQL compares a text value that starts with digits as that
            // number): read once, only those in the range
            $taken = [];
            $rows = ORM::for_table('tbl_customers_fields')
                ->select('field_value')
                ->where_raw("field_value REGEXP '^[[:space:]]*[0-9]'")
                ->find_array();
            foreach ($rows as $row) {
                $n = (int) trim($row['field_value']);
                if ($n >= $lo && $n <= $hi) {
                    $taken[$n] = true;
                }
            }
            foreach ($missing as $portn) {
                $portr = self::pickFree(range($lo, $hi), $taken);
                if ($portr === null) {
                    return; // none left: the others cannot get one either
                }
                $cf = ORM::for_table('tbl_customers_fields')->create();
                $cf->customer_id = $id;
                $cf->field_name = $portn;
                $cf->field_value = $portr;
                $cf->save();
                $taken[$portr] = true; // the next service does not take it
            }
        } finally {
            $this->unlockIn($lock);
        }
    }

    /**
     * A free address from the pool for a customer who has none (null when they have one, or none is left). Called while the
     * address lock is held (reserveIp()). Every address in the range is considered, not only a few drawn at random.
     */
    /**
     * Whether any router of a VPN plan still has a secret on $ip, or cannot be asked (then it counts as used): an expired hold
     * is let go only once every router has been read and none uses the address.
     */
    private function holdStillUsed($ip)
    {
        $names = [];
        foreach (ORM::for_table('tbl_plans')->select('routers')->where('type', 'VPN')->where_not_equal('routers', '')->find_array() as $row) {
            $names[(string) $row['routers']] = true;
        }
        // a router whose last VPN plan was moved or deleted may still hold secrets: its recharges keep it asked
        foreach (ORM::for_table('tbl_user_recharges')->distinct()->select('routers')->where('type', 'VPN')->where_not_equal('routers', '')->find_array() as $row) {
            $names[(string) $row['routers']] = true;
        }
        foreach (array_keys($names) as $name) {
            try {
                $m = $this->info($name);
                $client = $this->getClient($m['ip_address'], $m['username'], $m['password']);
                $req = new RouterOS\Request('/ppp/secret/print');
                $req->setArgument('.proplist', '.id');
                $req->setQuery(RouterOS\Query::where('remote-address', $ip));
                $res = $client->sendSync($req);
                if (RouterApi::error($res) !== null || !empty(self::prop($res, '.id'))) {
                    return true;
                }
            } catch (Throwable $e) {
                return true;
            }
        }
        return false;
    }

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
        // unsigned values (on 32-bit PHP ip2long() gives a negative one above 127.255.255.255)
        $a = self::ipUnsigned(trim($ip_r[0]));
        $b = self::ipUnsigned(trim($ip_r[1]));
        $from = min($a, $b);
        $to = max($a, $b);
        $taken = [];
        foreach (ORM::for_table('tbl_customers')->select('pppoe_ip')->where_not_equal('pppoe_ip', '')->find_array() as $row) {
            $taken[trim((string) $row['pppoe_ip'])] = true;
        }
        // an address an admin has just moved a customer off stays taken until the router's secret has let go of it
        foreach (ORM::for_table('tbl_appconfig')->where_like('setting', 'vpn_held\\_%')->find_many() as $held) {
            $heldIp = substr((string) $held['setting'], 9);
            if ((int) $held['value'] > time()) {
                $taken[$heldIp] = true;
            } elseif (($hu = self::ipUnsigned($heldIp)) < $from || $hu > $to) {
                // another pool's address: not asked of a router while allocating here, and kept reserved until it is looked at
                $taken[$heldIp] = true;
            } elseif ($this->holdStillUsed($heldIp)) {
                // the time is up, but a router still has a secret on the address (or cannot be asked): kept, and looked at again later
                $taken[$heldIp] = true;
                $held->value = (string) (time() + 600);
                $held->save();
            } else {
                $held->delete();
            }
        }
        // the whole range is walked, from a random address and wrapping, until a free one is found (no cap on the range's size)
        $size = $to - $from + 1;
        $start = random_int(0, (int) min($size - 1, PHP_INT_MAX));
        for ($i = 0; $i < $size; $i++) {
            $off = $start + $i;
            if ($off >= $size) {
                $off -= $size;
            }
            $ipStr = self::unsignedToIp($from + $off);
            if (!isset($taken[$ipStr])) {
                return $ipStr;
            }
        }
        return null;

    }

    /** An IPv4 address as an unsigned number (an int on 64-bit PHP, a float above 2^31 on 32-bit) */
    private static function ipUnsigned($ip)
    {
        $n = ip2long($ip);
        return $n < 0 ? $n + 4294967296 : $n;
    }

    private static function unsignedToIp($n)
    {
        if (PHP_INT_SIZE === 4 && $n >= 2147483648) {
            $n -= 4294967296;
        }
        return long2ip((int) $n);
    }
}
