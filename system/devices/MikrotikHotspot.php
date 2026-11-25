<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 *
 * This is Core, don't modification except you want to contribute
 * better create new plugin
 **/

use PEAR2\Net\RouterOS;

class MikrotikHotspot
{
    // set while sync_customer() runs: a change the router refuses is thrown, so the sync counts that customer as not sent
    private $strict = false;

    // show Description
    function description()
    {
        return [
            'title' => 'Mikrotik Hotspot',
            'description' => 'To handle connection between PHPNuxBill with Mikrotik Hotspot',
            'author' => 'ibnux',
            'url' => [
                'Github' => 'https://github.com/hotspotbilling/phpnuxbill/',
                'Telegram' => 'https://t.me/phpnuxbill',
                'Donate' => 'https://paypal.me/ibnux'
            ]
        ];
    }


    /** A Limited plan must name a known limit type: otherwise it would be sent as unlimited (checked before the router is touched) */
    private function assertLimitType($plan)
    {
        if ($plan['typebp'] == 'Limited' && !in_array($plan['limit_type'], ['Time_Limit', 'Data_Limit', 'Both_Limit'], true)) {
            throw new Exception(Lang::T('The plan is Limited but has no valid limit type: nothing was sent to the router.'));
        }
    }

    function add_customer($customer, $plan)
    {
        $this->assertLimitType($plan);
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
		$isExp = ORM::for_table('tbl_plans')->select("id")->where('plan_expired', $plan['id'])->find_one();
        $this->removeHotspotUser($client, $customer['username']);
		if ($isExp){
        $this->removeHotspotActiveUser($client, $customer['username']);
		}
        $this->addHotspotUser($client, $plan, $customer);
    }
	
	function sync_customer($customer, $plan)
	{
		$this->strict = true;
		try {
			$this->syncCustomer($customer, $plan);
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

	/** The plan's limit-uptime as RouterOS writes it ("2:00:00", "00:30:00"), or null when the plan has no time limit */
	private function uptimeLimit($plan)
	{
		if ($plan['typebp'] != "Limited" || !in_array($plan['limit_type'], ['Time_Limit', 'Both_Limit'], true)) {
			return null;
		}
		return ($plan['time_unit'] == 'Hrs') ? $plan['time_limit'] . ":00:00" : "00:" . $plan['time_limit'] . ":00";
	}

	/** The plan's limit-bytes-total in bytes, or null when the plan has no data limit */
	private function bytesLimit($plan)
	{
		if ($plan['typebp'] != "Limited" || !in_array($plan['limit_type'], ['Data_Limit', 'Both_Limit'], true)) {
			return null;
		}
		return ($plan['data_unit'] == 'GB') ? $plan['data_limit'] . "000000000" : $plan['data_limit'] . "000000";
	}

	/** What a sync writes: both limits, "0" (no limit) for one the plan has not, so an obsolete limit is cleared */
	private function planLimits($plan)
	{
		return ['limit-uptime' => $this->uptimeLimit($plan) ?? '0', 'limit-bytes-total' => $this->bytesLimit($plan) ?? '0'];
	}

	private function syncCustomer($customer, $plan)
	{
		$this->assertLimitType($plan);
		$mikrotik = $this->info($plan['routers']);
		$client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
		// the active recharge of this plan (not any recharge of the username, which may be a PPPoE one)
		$t = ORM::for_table('tbl_user_recharges')->where('username', $customer['username'])->where('plan_id', $plan['id'])->where('status', 'on')->find_one();
		if ($t) {
			$printRequest = new RouterOS\Request('/ip/hotspot/user/print');
			$printRequest->setArgument('.proplist', '.id');
			$printRequest->setQuery(RouterOS\Query::where('name', $customer['username']));
			// a refused lookup (!trap) is thrown here, before anything is removed or added; an empty answer means no such user
			$userInfo = $this->checked($client->sendSync($printRequest));
			$id = $userInfo->getProperty('.id');
			if (!empty($id)) {
				// an existing user is changed in place (never removed first), so its usage counters are kept; the plan's limits
				// are set too ("0" is no limit), so a plan whose limits changed is followed
				$setRequest = new RouterOS\Request('/ip/hotspot/user/set');
				$setRequest->setArgument('numbers', $id);
				$setRequest->setArgument('profile', $plan['name_plan']);
				$setRequest->setArgument('password', $customer['password']);
				foreach ($this->planLimits($plan) as $k => $v) {
					$setRequest->setArgument($k, $v);
				}
				$this->checked($client->sendSync($setRequest));
			} else {
				// a missing user only is made, and nothing is removed (the add is refused if one appeared meanwhile)
				$isExp = ORM::for_table('tbl_plans')->select("id")->where('plan_expired', $plan['id'])->find_one();
				if ($isExp) {
					$this->removeHotspotActiveUser($client, $customer['username']);
				}
				$this->addHotspotUserChecked($client, $plan, $customer);
			}
		}
	}


    function remove_customer($customer, $plan)
    {
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        if (!empty($plan['plan_expired'])) {
            $p = ORM::for_table("tbl_plans")->find_one($plan['plan_expired']);
            if($p){
                $this->add_customer($customer, $p);
                $this->removeHotspotActiveUser($client, $customer['username']);
                return;
            }
        }
        $this->removeHotspotUser($client, $customer['username']);
        $this->removeHotspotActiveUser($client, $customer['username']);
    }

    // customer change username
    public function change_username($plan, $from, $to)
    {
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        //check if customer exists
        $printRequest = new RouterOS\Request('/ip/hotspot/user/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $from));
        $res = $client->sendSync($printRequest);
        // a refused lookup is thrown: carrying on would let the caller's add_customer() replace whatever user has the new name
        if (($err = RouterApi::error($res)) !== null) {
            throw new Exception(Lang::T('The router refused the command') . ': ' . $err);
        }
        $id = $res->getProperty('.id');

        if (!empty($id)) {
            $setRequest = new RouterOS\Request('/ip/hotspot/user/set');
            $setRequest->setArgument('numbers', $id);
            $setRequest->setArgument('name', $to);
            // a refused rename (a user of the new name already there, say) stops the edit, and the old session stays connected
            if (($err = RouterApi::error($client->sendSync($setRequest))) !== null) {
                throw new Exception(Lang::T('The router refused the command') . ': ' . $err);
            }
            // disconnect then: the rename stands if this fails (the session ends by itself on its next login)
            try {
                $this->removeHotspotActiveUser($client, $from, true);
            } catch (Exception $e) {
                try {
                    _log('Hotspot ' . $from . ': ' . $e->getMessage(), 'Router');
                } catch (Throwable $ignored) { // diagnostic only: the rename is done
                }
            }
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
		if ($bw['rate_up'] == '0' || $bw['rate_down'] == '0') {
			$rate = '';
		}
        $addRequest = new RouterOS\Request('/ip/hotspot/user/profile/add');
        $client->sendSync(
            $addRequest
                ->setArgument('name', $plan['name_plan'])
                ->setArgument('shared-users', $plan['shared_users'])
                ->setArgument('rate-limit', $rate)
        );
    }

    function online_customer($customer, $router_name)
    {
        $mikrotik = $this->info($router_name);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $printRequest = new RouterOS\Request(
            '/ip hotspot active print',
            RouterOS\Query::where('user', $customer['username'])
        );
        $id =  $client->sendSync($printRequest)->getProperty('.id');
        return $id;
    }

    function connect_customer($customer, $ip, $mac_address, $router_name)
    {
        $mikrotik = $this->info($router_name);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $addRequest = new RouterOS\Request('/ip/hotspot/active/login');
        $client->sendSync(
            $addRequest
                ->setArgument('user', $customer['username'])
                ->setArgument('password', $customer['password'])
                ->setArgument('ip', $ip)
                ->setArgument('mac-address', $mac_address)
        );
    }

    function disconnect_customer($customer, $router_name)
    {
        $mikrotik = $this->info($router_name);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $printRequest = new RouterOS\Request(
            '/ip hotspot active print',
            RouterOS\Query::where('user', $customer['username'])
        );
        $id = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ip/hotspot/active/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $id)
        );
    }


    function update_plan($old_plan, $new_plan)
    {
        $mikrotik = $this->info($new_plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);

        $printRequest = new RouterOS\Request(
            '/ip hotspot user profile print .proplist=.id',
            RouterOS\Query::where('name', $old_plan['name_plan'])
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');
        if (empty($profileID) && $old_plan['name_plan'] !== $new_plan['name_plan']) {
            // a retry after the profile was renamed already (or created under the new name): the existing one is updated
            $printNew = new RouterOS\Request('/ip hotspot user profile print .proplist=.id', RouterOS\Query::where('name', $new_plan['name_plan']));
            $profileID = $client->sendSync($printNew)->getProperty('.id');
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
			if ($bw['rate_up'] == '0' || $bw['rate_down'] == '0') {
				$rate = '';
			}
            $setRequest = new RouterOS\Request('/ip/hotspot/user/profile/set');
            $client->sendSync(
                $setRequest
                    ->setArgument('numbers', $profileID)
                    ->setArgument('name', $new_plan['name_plan'])
                    ->setArgument('shared-users', $new_plan['shared_users'])
                    ->setArgument('rate-limit', $rate)
                    ->setArgument('on-login', $new_plan['on_login'])
                    ->setArgument('on-logout', $new_plan['on_logout'])
            );
        }
    }

    function remove_plan($plan)
    {
        $mikrotik = $this->info($plan['routers']);
        $client = $this->getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
        $printRequest = new RouterOS\Request(
            '/ip hotspot user profile print .proplist=.id',
            RouterOS\Query::where('name', $plan['name_plan'])
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ip/hotspot/user/profile/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $profileID)
        );
    }

    function info($name)
    {
        return ORM::for_table('tbl_routers')->where('name', $name)->find_one();
    }

    function getClient($ip, $user, $pass, $port = null, $timeout = 8)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        [$host, $saved] = RouterAddress::split($ip); // "host:port", or "[IPv6]:port"
        $p = ($port) ? $port : $saved; // an explicit port, else the saved one, else null (the default API port)
        // $timeout holds for the connection and for each command's answer (the read limit would otherwise be 30 seconds)
        return RouterApi::deviceClient($host, $user, $pass, $p, $timeout, $timeout);
    }

    function removeHotspotUser($client, $username)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $printRequest = new RouterOS\Request(
            '/ip hotspot user print .proplist=.id',
            RouterOS\Query::where('name', $username)
        );
        $userID = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ip/hotspot/user/remove');
        $client->sendSync(
            $removeRequest
                ->setArgument('numbers', $userID)
        );
    }

    function addHotspotUser($client, $plan, $customer)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $addRequest = new RouterOS\Request('/ip/hotspot/user/add');
        $addRequest
            ->setArgument('name', $customer['username'])
            ->setArgument('profile', $plan['name_plan'])
            ->setArgument('password', $customer['password'])
            ->setArgument('comment', $customer['fullname'] . ' | ' . implode(', ', User::getBillNames($customer['id'])))
            ->setArgument('email', $customer['email']);
        // a new user only gets the limits the plan has (a plan with none, or a Limited one with an unknown type, adds no limit)
        if (($timelimit = $this->uptimeLimit($plan)) !== null) {
            $addRequest->setArgument('limit-uptime', $timelimit);
        }
        if (($datalimit = $this->bytesLimit($plan)) !== null) {
            $addRequest->setArgument('limit-bytes-total', $datalimit);
        }
        $this->checked($client->sendSync($addRequest));
    }

    /**
     * addHotspotUser() with the router's refusal thrown (a user of that name already there, say), whether or not a sync runs.
     * Nothing is removed first, unlike add_customer().
     */
    function addHotspotUserChecked($client, $plan, $customer)
    {
        $this->assertLimitType($plan); // a Limited plan with no valid limit type would be made as unlimited: refused before the router is touched
        $was = $this->strict;
        $this->strict = true;
        try {
            $this->addHotspotUser($client, $plan, $customer);
        } finally {
            $this->strict = $was;
        }
    }

    function setHotspotUser($client, $user, $pass)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $printRequest = new RouterOS\Request('/ip/hotspot/user/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $user));
        $id = $client->sendSync($printRequest)->getProperty('.id');

        $setRequest = new RouterOS\Request('/ip/hotspot/user/set');
        $setRequest->setArgument('numbers', $id);
        $setRequest->setArgument('password', $pass);
        $client->sendSync($setRequest);
    }

    function setHotspotUserPackage($client, $username, $plan_name)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $printRequest = new RouterOS\Request('/ip/hotspot/user/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $username));
        $id = $client->sendSync($printRequest)->getProperty('.id');

        $setRequest = new RouterOS\Request('/ip/hotspot/user/set');
        $setRequest->setArgument('numbers', $id);
        $setRequest->setArgument('profile', $plan_name);
        $client->sendSync($setRequest);
    }

    function removeHotspotActiveUser($client, $username, $throw = false)
    {
        global $_app_stage;
        if ($_app_stage == 'Demo') {
            return null;
        }
        $onlineRequest = new RouterOS\Request('/ip/hotspot/active/print');
        $onlineRequest->setArgument('.proplist', '.id');
        $onlineRequest->setQuery(RouterOS\Query::where('user', $username));
        // in a sync a refused lookup or removal is thrown (checked()), so a later success cannot mask it
        $res = $client->sendSync($onlineRequest);
        // $throw: the router's refusal of the lookup or the removal is thrown, whatever the mode (the caller logs it)
        if ($throw && ($err = RouterApi::error($res)) !== null) {
            throw new Exception($err);
        }
        $id = $this->checked($res)->getProperty('.id');
        if (empty($id)) {
            return; // no active session: nothing to remove
        }

        $removeRequest = new RouterOS\Request('/ip/hotspot/active/remove');
        $removeRequest->setArgument('numbers', $id);
        $res = $client->sendSync($removeRequest);
        if ($throw && ($err = RouterApi::error($res)) !== null) {
            throw new Exception($err);
        }
        $this->checked($res);
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
}
