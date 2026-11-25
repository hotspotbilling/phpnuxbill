<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 *
 * This is Core, don't modification except you want to contribute
 * better create new plugin
 **/

use PEAR2\Net\RouterOS;

class MikrotikPppoe
{
    // set while sync_customer() runs: a change the router refuses is thrown, so the sync counts that customer as not sent
    private $strict = false;

    /** The code of an exception for a partial update: the secret was saved, a later step was refused */
    const PARTIAL = 207;

    // show Description
    function description()
    {
        return [
            'title' => 'Mikrotik PPPOE',
            'description' => 'To handle connection between PHPNuxBill with Mikrotik PPPOE',
            'author' => 'ibnux',
            'url' => [
                'Github' => 'https://github.com/hotspotbilling/phpnuxbill/',
                'Telegram' => 'https://t.me/phpnuxbill',
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
        $isExp = ORM::for_table('tbl_plans')->select("id")->where('plan_expired', $plan['id'])->find_one();
        if (empty($cid)) {
            //customer not exists, add it
            $this->addPpoeUser($client, $plan, $customer, $isExp);
        }else{
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
            $unsetIP = false;
            if (!empty($customer['pppoe_ip']) && !$isExp){
                $setRequest->setArgument('remote-address', $customer['pppoe_ip']);
            } else {
                $unsetIP = true;
			}
            $setRequest->setArgument('profile', $plan['name_plan']);
            $setRequest->setArgument('comment', $customer['fullname'] . ' | ' . $customer['email'] . ' | ' . implode(', ', User::getBillNames($customer['id'])));
            $setRes = $this->checked($client->sendSync($setRequest));
            $setErr = RouterApi::error($setRes);
            $setTaken = $setErr === null;
            if (!$setTaken) {
                // a refused change (the expired profile included) stops here: no address is cleared, no session ended
                throw new Exception(Lang::T('The router refused the command') . ': ' . $setErr);
            }

            // the fixed IP is cleared only on a secret the router has just taken: after a refused change, nothing was saved
            $unsetErr = null;
            $unsetCause = null;
            $connLost = false;
            if ($unsetIP && $setTaken) {
                try {
                    // checked in every mode: a refusal is said below as a partial update, never as a failed (rolled back) one.
                    // "numbers" is the documented argument; the older code sent ".id", which RouterOS also takes: a refusal of
                    // one is tried again with the other, and the first refusal is the one reported if both are refused
                    $unsetErr = null;
                    foreach (['numbers', '.id'] as $idArg) {
                        $unsetRequest = new RouterOS\Request('/ppp/secret/unset');
                        $unsetRequest->setArgument($idArg, $cid);
                        $unsetRequest->setArgument('value-name', 'remote-address');
                        $e2 = RouterApi::error($client->sendSync($unsetRequest));
                        if ($e2 === null) {
                            $unsetErr = null;
                            break;
                        }
                        $unsetErr = $unsetErr ?? $e2;
                    }
                } catch (Exception $e) {
                    // no answer (the connection is dropped): the secret was saved, whether the address was cleared is not known;
                    // the disconnect below is still tried (on a new connection) before this is reported
                    $unsetErr = $e->getMessage();
                    $unsetCause = $e;
                    $connLost = true;
                }
            }


            //disconnect then: the secret is saved already, so a session that cannot be ended is not a failed update
            if(isset($isChangePlan) && $isChangePlan){
                if ($connLost) {
                    try {
                        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    } catch (Exception $e) {
                        $unsetErr .= '; ' . Lang::T('the active session could not be disconnected') . ': ' . $e->getMessage();
                        $client = null;
                    }
                }
                $discErr = $client ? $this->dropSessions($client, $customer, $mikrotik) : null;
                if ($discErr !== null) {
                    if ($unsetErr === null) {
                        try {
                            _log('PPPoE ' . $customer['username'] . ': ' . Lang::T('the active session could not be disconnected') . ': ' . $discErr->getMessage(), 'Router');
                        } catch (Throwable $ignored) { // diagnostic only: the secret is saved, and a log failure must not turn that into a failed update
                        }
                    } else {
                        $unsetErr .= '; ' . Lang::T('the active session could not be disconnected') . ': ' . $discErr->getMessage();
                    }
                }
            }
            if ($unsetErr !== null) {
                // the router took the secret change and refused (or did not answer) only the clear: the address is still to be cleared
                throw new Exception(Lang::T('Saved, but the fixed IP address could not be cleared') . ': ' . $unsetErr, self::PARTIAL, $unsetCause);
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
        if ($res instanceof RouterOS\ResponseCollection) {
            $res->rewind(); // error() iterated the replies: back to the first, for getProperty()
        }
        return $res;
    }

    function remove_customer($customer, $plan)
    {
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        if (!empty($plan['plan_expired'])) {
            $p = ORM::for_table("tbl_plans")->find_one($plan['plan_expired']);
            if($p){
                $pending = null;
                try {
                    $this->add_customer($customer, $p);
                } catch (Exception $e) {
                    if ($e->getCode() !== self::PARTIAL) {
                        throw $e;
                    }
                    // the expired profile is on the secret, only the address clear is pending: the session is still ended below,
                    // and the message is returned so the cron keeps the recharge on (retried next run) until the clear succeeds
                    $pending = $e->getMessage();
                    _log('PPPoE ' . $customer['username'] . ': ' . $pending, 'Router');
                }
                $e = $this->dropSessions($client, $customer, $mikrotik);
                if ($e !== null) {
                    if ($pending === null) {
                        throw $e;
                    }
                    $pending .= '; ' . Lang::T('the active session could not be disconnected') . ': ' . $e->getMessage();
                }
                return $pending; // null when everything was done, else the partial result
            }
        }
        // each name's secret and each name's session is tried whatever another one did; the first failure is thrown after all
        // were attempted, so the caller keeps the recharge and a retry finishes what was refused
        $first = null;
        foreach (array_unique(array_filter([(string) $customer['username'], (string) ($customer['pppoe_username'] ?? '')])) as $name) {
            try {
                $this->removePpoeUser($client, $name);
            } catch (Exception $e) {
                $first = $first ?? $e;
                if (in_array($e->getCode(), [408, 503], true)) {
                    try {
                        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    } catch (Exception $e2) {
                        $client = null;
                        break;
                    }
                }
            }
        }
        $dropErr = $client === null ? null : $this->dropSessions($client, $customer, $mikrotik);
        $first = $first ?? $dropErr;
        if ($first !== null) {
            throw $first;
        }
    }

    // customer change username
    public function change_username($plan, $from, $to)
    {
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        //check if customer exists
        $cid = $this->secretId($client, $from, true); // a refused lookup is thrown: nothing is renamed or assumed missing
        if (!empty($cid)) {
            $setRequest = new RouterOS\Request('/ppp/secret/set');
            $setRequest->setArgument('numbers', $cid);
            $setRequest->setArgument('name', $to);
            // a refused rename (the new name is taken, say) is thrown, so the caller does not go on as if it had worked
            try {
                $err = RouterApi::error($client->sendSync($setRequest));
            } catch (Exception $lost) {
                // The connection dropped (no answer, or closed: RouterApi's 408 and 503) before the reply: the router may have taken the
                // rename. Looked at on a new connection: the secret under the new name and none under the old one means it was done,
                // and then only the disconnect below is left; anything else is the original failure, so the caller can retry.
                if (!in_array($lost->getCode(), [408, 503], true)) {
                    throw $lost;
                }
                try {
                    $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    // the secret under the new name must be the very one renamed ($cid), not another one that has that name
                    $done = (string) $this->secretId($client, $to, true) === (string) $cid && empty($this->secretId($client, $from, true));
                } catch (Exception $check) {
                    $done = false;
                }
                if (!$done) {
                    throw $lost;
                }
                $err = null;
            }
            if ($err !== null) {
                throw new Exception(Lang::T('The router refused the command') . ': ' . $err);
            }
            //disconnect then (the rename stands if this fails: the session ends by itself on its next login)
            try {
                $this->removePpoeActive($client, $from);
            } catch (Exception $e) {
                try {
                    _log('PPPoE ' . $from . ': ' . $e->getMessage(), 'Router');
                } catch (Throwable $ignored) { // diagnostic only: the router has taken the rename, which the caller must keep
                }
            }
        }
    }

    /**
     * Ends the sessions of the customer's distinct names, each one tried whatever the other did; the first failure comes back
     * (null when none), after every attempt
     */
    private function dropSessions($client, $customer, $mikrotik = null)
    {
        $first = null;
        foreach (array_unique(array_filter([(string) $customer['username'], (string) ($customer['pppoe_username'] ?? '')])) as $name) {
            if ($client === null) {
                break; // no connection to use any more: the first error says it
            }
            // after a lost connection the same login is listed again on a new one, once (a removal whose reply was lost then shows
            // as done); a refusal, or a second failure, is the result for this name
            for ($attempt = 0; $attempt < 2 && $client !== null; $attempt++) {
                try {
                    $this->removePpoeActive($client, $name);
                    break;
                } catch (Exception $e) {
                    $lost = $mikrotik && in_array($e->getCode(), [408, 503], true); // dropped: no answer, or closed (RouterApi)
                    if ($lost) {
                        try {
                            $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        } catch (Exception $e2) {
                            $client = null;
                        }
                    }
                    if (!$lost || $attempt === 1 || $client === null) {
                        $first = $first ?? $e;
                        break;
                    }
                }
            }
        }
        return $first;
    }

    function add_plan($plan)
    {
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);

        //Add Pool

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
        if(!empty(trim($bw['burst']))){
            $rate .= ' '.$bw['burst'];
        }
		if ($bw['rate_up'] == '0' || $bw['rate_down'] == '0') {
			$rate = '';
		}
        $pool = ORM::for_table("tbl_pool")->where("pool_name", $plan['pool'])->find_one();
        $addRequest = new RouterOS\Request('/ppp/profile/add');
        $client->sendSync(
            $addRequest
                ->setArgument('name', $plan['name_plan'])
                ->setArgument('local-address', (!empty($pool['local_ip'])) ? $pool['local_ip']: $pool['pool_name'])
                ->setArgument('remote-address', $pool['pool_name'])
                ->setArgument('rate-limit', $rate)
        );
    }

    /**
     * Function to ID by username from Mikrotik. $checked (by default: during a sync) throws the router's refusal of a lookup
     * (a !trap), so a refusal is never taken for "no such secret" and no secret is made on it; empty means the router answered
     * and has none.
     */
    function getIdByCustomer($customer, $client, $checked = null){
        $checked = $checked ?? $this->strict;
        // the dial login first when there is an explicit one (it is the name the secret is made under), so a portal-named secret
        // of someone else is not mistaken for it; the portal username when there is none, and as the legacy fallback
        $id = '';
        if (!empty($customer['pppoe_username'])) {
            $id = $this->secretId($client, $customer['pppoe_username'], $checked);
        }
        if (empty($id)) {
            if (!empty($customer['pppoe_username'])) {
                // a dial login is set: a secret under the portal name is this customer's only when its comment (written by
                // addPpoeUser: full name | email | ...) says so; someone else's secret of that name is never taken for it
                $id = $this->ownSecretId($client, $customer['username'], $customer, $checked);
            } else {
                $id = $this->secretId($client, $customer['username'], $checked);
            }
        }
        return $id;
    }

    /** The .id of the secret with this name when its comment names this customer (empty otherwise) */
    private function ownSecretId($client, $name, $customer, $checked)
    {
        $printRequest = new RouterOS\Request('/ppp/secret/print');
        $printRequest->setQuery(RouterOS\Query::where('name', $name));
        $res = $client->sendSync($printRequest);
        if ($checked && ($err = RouterApi::error($res)) !== null) {
            throw new Exception($err);
        }
        $res->rewind();
        $id = $res->getProperty('.id');
        if (empty($id)) {
            return '';
        }
        $comment = (string) RouterApi::text($res->getProperty('comment'));
        $mine = $customer['fullname'] . ' | ' . $customer['email'];
        if (strncmp($comment, $mine, strlen($mine)) === 0) {
            return $id;
        }
        // a secret of that name whose owner cannot be established: another customer's own (their dial login or portal name) is left
        // alone, as before. Otherwise it may be this customer's secret from before comments named the owner (or the name or email
        // changed since), and a second secret would be made beside it: reported as a conflict, nothing is changed
        $other = ORM::for_table('tbl_customers')->select('id')->where_not_equal('id', (int) ($customer['id'] ?? 0))
            ->where_raw('(pppoe_username = ? OR username = ?)', [$name, $name])->find_one();
        if ($other) {
            return '';
        }
        throw new Exception(Lang::T('A PPPoE secret named') . ' "' . $name . '" ' . Lang::T('exists on the router but is not recognised as this customer\'s. Nothing was changed: fix its comment or rename it there, then use Sync.'), 409);
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
        $res->rewind(); // error() iterated the replies: back to the first, or getProperty() has no reply to read
        return $res->getProperty('.id');
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
        if (empty($profileID) && $old_name['name_plan'] !== $new_plan['name_plan']) {
            // a retry after the profile was renamed already (or created under the new name): the existing one is updated
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
            if(!empty(trim($bw['burst']))){
                $rate .= ' '.$bw['burst'];
            }
			if ($bw['rate_up'] == '0' || $bw['rate_down'] == '0') {
				$rate = '';
			}
            $pool = ORM::for_table("tbl_pool")->where("pool_name", $new_plan['pool'])->find_one();
            $setRequest = new RouterOS\Request('/ppp/profile/set');
            $client->sendSync(
                $setRequest
                    ->setArgument('numbers', $profileID)
                    ->setArgument('local-address', (!empty($pool['local_ip'])) ? $pool['local_ip']: $pool['pool_name'])
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

    function add_pool($pool){
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

    function update_pool($old_pool, $new_pool){
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

    function remove_pool($pool){
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


    function online_customer($customer, $router_name)
    {
        $mikrotik = $this->info($router_name);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $printRequest = new RouterOS\Request(
            '/ppp active print',
            RouterOS\Query::where('name', $customer['username'])
        );
        $id = $client->sendSync($printRequest)->getProperty('.id');
        if(empty($id)){
            $printRequest = new RouterOS\Request(
                '/ppp active print',
                RouterOS\Query::where('name', $customer['pppoe_username'])
            );
            $id = $client->sendSync($printRequest)->getProperty('.id');
        }
        return $id;
    }

    function info($name)
    {
        return ORM::for_table('tbl_routers')->where('name', $name)->find_one();
    }

    function getClient($ip, $user, $pass)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        [$host, $port] = RouterAddress::split($ip); // "host:port", or "[IPv6]:port"
        return RouterApi::deviceClient($host, $user, $pass, $port); // every read time-limited
    }

    function removePpoeUser($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $printRequest = new RouterOS\Request('/ppp/secret/print');
        //$printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $username));
        $res = $client->sendSync($printRequest);
        if (($err = RouterApi::error($res)) !== null) {
            throw new Exception($err);
        }
        $id = $res->getProperty('.id'); // (error() leaves the collection at its first reply)
        if (empty($id)) {
            return null; // already gone (a retry of a removal that went through): nothing to remove, the rest of the cleanup goes on
        }
        $removeRequest = new RouterOS\Request('/ppp/secret/remove');
        $removeRequest->setArgument('numbers', $id);
        $client->sendSync($removeRequest);
    }

    function addPpoeUser($client, $plan, $customer, $isExp = false)
    {
        $setRequest = new RouterOS\Request('/ppp/secret/add');
        $setRequest->setArgument('service', 'pppoe');
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
        if (!empty($customer['pppoe_ip']) && !$isExp) {
            $setRequest->setArgument('remote-address', $customer['pppoe_ip']);
        }
        // a refused add is thrown in every mode (not only during a sync): the secret is not there, so nothing is done
        $res = $client->sendSync($setRequest);
        if (($err = RouterApi::error($res)) !== null) {
            throw new Exception(Lang::T('The router refused the command') . ': ' . $err);
        }
    }

    /**
     * Only adds the customer's secret (never changes one already there), the router's refusal thrown: a password change uses it
     * for a secret its lookup found missing, and on a refusal looks it up again rather than change its profile or address
     */
    function addPpoeUserChecked($client, $plan, $customer)
    {
        $isExp = ORM::for_table('tbl_plans')->select("id")->where('plan_expired', $plan['id'])->find_one();
        $this->addPpoeUser($client, $plan, $customer, $isExp);
    }

    function removePpoeActive($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $onlineRequest = new RouterOS\Request('/ppp/active/print');
        $onlineRequest->setArgument('.proplist', '.id');
        $onlineRequest->setQuery(RouterOS\Query::where('name', $username));
        $res = $client->sendSync($onlineRequest);
        // a refused lookup or removal is thrown (the callers that carry on after a partial result catch it); no session is a no-op
        if (($err = RouterApi::error($res)) !== null) {
            throw new Exception($err);
        }
        // every matching session (a customer can be connected more than once), not only the reply the collection points at
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
        foreach ($ids as $one) {
            $removeRequest = new RouterOS\Request('/ppp/active/remove');
            $removeRequest->setArgument('numbers', $one);
            if (($err = RouterApi::error($client->sendSync($removeRequest))) !== null) {
                $first = $first ?? $err; // the others are still tried
            }
        }
        if ($first !== null) {
            throw new Exception($first);
        }
    }

    function getIpHotspotUser($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip hotspot active print',
            RouterOS\Query::where('user', $username)
        );
        return $client->sendSync($printRequest)->getProperty('address');
    }

    function addIpToAddressList($client, $ip, $listName, $comment = '')
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

    function removeIpFromAddressList($client, $ip)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
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
}
