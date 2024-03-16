<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * Radius Class
 * based https://gist.github.com/nasirhafeez/6669b24aab0bda545f60f9da5ed14f25
 */
class Radius
{

    public static function getClient()
    {
        global $config;
        if(empty($config['radius_client'])){
            if(function_exists("shell_exec")){
                shell_exec('which radclient');
            }else{
                return "";
            }
        }else{
            $config['radius_client'];
        }
    }

    public static function getTableNas()
    {
        return ORM::for_table('nas', 'radius');
    }

    public static function getTableCustomer()
    {
        return ORM::for_table('radcheck', 'radius');
    }

    public static function getTableCustomerAttr()
    {
        return ORM::for_table('radreply', 'radius');
    }

    public static function getTablePackage()
    {
        return ORM::for_table('radgroupreply', 'radius');
    }

    public static function getTableUserPackage()
    {
        return ORM::for_table('radusergroup', 'radius');
    }

    public static function nasAdd($name, $ip, $ports, $secret, $routers = "", $description = "", $type = 'other', $server = null, $community = null)
    {
        $n = Radius::getTableNas()->create();
        $n->nasname = $ip;
        $n->shortname = $name;
        $n->type = $type;
        $n->ports = $ports;
        $n->secret = $secret;
        $n->description = $description;
        $n->server = $server;
        $n->community = $community;
        $n->routers = $routers;
        $n->save();
        return $n->id();
    }

    public static function nasUpdate($id, $name, $ip, $ports, $secret, $routers = "", $description = "", $type = 'other', $server = null, $community = null)
    {
        $n = Radius::getTableNas()->find_one($id);
        if (empty($n)) {
            return false;
        }
        $n->nasname = $ip;
        $n->shortname = $name;
        $n->type = $type;
        $n->ports = $ports;
        $n->secret = $secret;
        $n->description = $description;
        $n->server = $server;
        $n->community = $community;
        $n->routers = $routers;
        return $n->save();
    }

    public static function planUpSert($plan_id, $rate, $pool = null)
    {
        $rates = explode('/', $rate);
        Radius::upsertPackage($plan_id, 'Ascend-Data-Rate', $rates[1], ':=');
        Radius::upsertPackage($plan_id, 'Ascend-Xmit-Rate', $rates[0], ':=');
        Radius::upsertPackage($plan_id, 'Mikrotik-Rate-Limit', $rate, ':=');
        // if ($pool != null) {
        //     Radius::upsertPackage($plan_id, 'Framed-Pool', $pool, ':=');
        // }
    }

    public static function planDelete($plan_id)
    {
        // Delete Plan
        Radius::getTablePackage()->where_equal('plan_id', "plan_" . $plan_id)->delete_many();
        // Reset User Plan
        $c = Radius::getTableUserPackage()->where_equal('groupname', "plan_" . $plan_id)->findMany();
        if ($c) {
            foreach ($c as $u) {
                $u->groupname = '';
                $u->save();
            }
        }
    }


    public static function customerChangeUsername($from, $to)
    {
        $c = Radius::getTableCustomer()->where_equal('username', $from)->findMany();
        if ($c) {
            foreach ($c as $u) {
                $u->username = $to;
                $u->save();
            }
        }
        $c = Radius::getTableUserPackage()->where_equal('username', $from)->findMany();
        if ($c) {
            foreach ($c as $u) {
                $u->username = $to;
                $u->save();
            }
        }
    }

    public static function customerDeactivate($username, $radiusDisconnect = true)
    { {
            global $radius_pass;
            $r = Radius::getTableCustomer()->where_equal('username', $username)->whereEqual('attribute', 'Cleartext-Password')->findOne();
            if ($r) {
                // no need to delete, because it will make ID got higher
                // we just change the password
                $r->value = md5(time() . $username . $radius_pass);
                $r->save();
                if ($radiusDisconnect)
                    return Radius::disconnectCustomer($username);
            }
        }
        return '';
    }

    public static function customerDelete($username)
    {
        Radius::getTableCustomer()->where_equal('username', $username)->delete_many();
        Radius::getTableUserPackage()->where_equal('username', $username)->delete_many();
    }

    /**
     * When add a plan to Customer, use this
     */
    public static function customerAddPlan($customer, $plan, $expired = null)
    {
        global $config;
        if (Radius::customerUpsert($customer, $plan)) {
            $p = Radius::getTableUserPackage()->where_equal('username', $customer['username'])->findOne();
            if ($p) {
                // if exists
                $p->groupname = "plan_" . $plan['id'];
                $p->save();
            } else {
                $p = Radius::getTableUserPackage()->create();
                $p->username = $customer['username'];
                $p->groupname = "plan_" . $plan['id'];
                $p->priority = 1;
                $p->save();
            }
            if ($plan['type'] == 'Hotspot' && $plan['typebp'] == "Limited") {
                if ($plan['limit_type'] == "Time_Limit") {
                    if ($plan['time_unit'] == 'Hrs')
                        $timelimit = $plan['time_limit'] * 60 * 60;
                    else
                        $timelimit = $plan['time_limit'] * 60;
                    Radius::upsertCustomer($customer['username'], 'Expire-After', $timelimit);
                } else if ($plan['limit_type'] == "Data_Limit") {
                    if ($plan['data_unit'] == 'GB')
                        $datalimit = $plan['data_limit'] . "000000000";
                    else
                        $datalimit = $plan['data_limit'] . "000000";
                    //Radius::upsertCustomer($customer['username'], 'Max-Volume', $datalimit);
                    // Mikrotik Spesific
                    Radius::upsertCustomer($customer['username'], 'Mikrotik-Total-Limit', $datalimit);
                } else if ($plan['limit_type'] == "Both_Limit") {
                    if ($plan['time_unit'] == 'Hrs')
                        $timelimit = $plan['time_limit'] * 60 * 60;
                    else
                        $timelimit = $plan['time_limit'] . ":00";
                    if ($plan['data_unit'] == 'GB')
                        $datalimit = $plan['data_limit'] . "000000000";
                    else
                        $datalimit = $plan['data_limit'] . "000000";
                    //Radius::upsertCustomer($customer['username'], 'Max-Volume', $datalimit);
                    Radius::upsertCustomer($customer['username'], 'Expire-After', $timelimit);
                    // Mikrotik Spesific
                    Radius::upsertCustomer($customer['username'], 'Mikrotik-Total-Limit', $datalimit);
                }
            } else {
                //Radius::delAtribute(Radius::getTableCustomer(), 'Max-Volume', 'username', $customer['username']);
                Radius::delAtribute(Radius::getTableCustomer(), 'Expire-After', 'username', $customer['username']);
                Radius::delAtribute(Radius::getTableCustomer(), 'Mikrotik-Total-Limit', 'username', $customer['username']);
            }
            // expired user
            if ($expired != null) {
                //Radius::upsertCustomer($customer['username'], 'access-period', strtotime($expired) - time());
                Radius::upsertCustomer($customer['username'], 'expiration', date('d M Y H:i:s', strtotime($expired)));
                // Mikrotik Spesific
                Radius::upsertCustomer(
                    $customer['username'],
                    'WISPr-Session-Terminate-Time',
                    date('Y-m-d', strtotime($expired)) . 'T' . date('H:i:s', strtotime($expired)) . Timezone::getTimeOffset($config['timezone'])
                );
            } else {
                //Radius::delAtribute(Radius::getTableCustomer(), 'access-period', 'username', $customer['username']);
                Radius::delAtribute(Radius::getTableCustomer(), 'expiration', 'username', $customer['username']);
            }

            if ($plan['type'] == 'PPPOE') {
                Radius::upsertCustomerAttr($customer['username'], 'Framed-Pool', $plan['pool'], ':=');
            }
            return true;
        }
        return false;
    }

    public static function customerUpsert($customer, $plan)
    {
        if ($plan['type'] == 'PPPOE') {
            Radius::upsertCustomer($customer['username'], 'Cleartext-Password', (empty($customer['pppoe_password'])) ? $customer['password'] : $customer['pppoe_password']);
        } else {
            Radius::upsertCustomer($customer['username'], 'Cleartext-Password',  $customer['password']);
        }
        Radius::upsertCustomer($customer['username'], 'Simultaneous-Use', ($plan['type'] == 'PPPOE') ? 1 : $plan['shared_users']);
        // Mikrotik Spesific
        Radius::upsertCustomer($customer['username'], 'Port-Limit', ($plan['type'] == 'PPPOE') ? 1 : $plan['shared_users']);
        Radius::upsertCustomer($customer['username'], 'Mikrotik-Wireless-Comment', $customer['fullname']);
        return true;
    }

    private static function delAtribute($tabel, $attribute, $key, $value)
    {
        $r = $tabel->where_equal($key, $value)->whereEqual('attribute', $attribute)->findOne();
        if ($r) $r->delete();
    }

    /**
     * To insert or update existing plan
     */
    private static function upsertPackage($plan_id, $attr, $value, $op = ':=')
    {
        $r = Radius::getTablePackage()->where_equal('plan_id', $plan_id)->whereEqual('attribute', $attr)->find_one();
        if (!$r) {
            $r = Radius::getTablePackage()->create();
            $r->groupname = "plan_" . $plan_id;
            $r->plan_id = $plan_id;
        }
        $r->attribute = $attr;
        $r->op = $op;
        $r->value = $value;
        return $r->save();
    }

    /**
     * To insert or update existing customer
     */
    private static function upsertCustomer($username, $attr, $value, $op = ':=')
    {
        $r = Radius::getTableCustomer()->where_equal('username', $username)->whereEqual('attribute', $attr)->find_one();
        if (!$r) {
            $r = Radius::getTableCustomer()->create();
            $r->username = $username;
        }
        $r->attribute = $attr;
        $r->op = $op;
        $r->value = $value;
        return $r->save();
    }
    /**
     * To insert or update existing customer Attribute
     */
    public static function upsertCustomerAttr($username, $attr, $value, $op = ':=')
    {
        $r = Radius::getTableCustomerAttr()->where_equal('username', $username)->whereEqual('attribute', $attr)->find_one();
        if (!$r) {
            $r = Radius::getTableCustomerAttr()->create();
            $r->username = $username;
        }
        $r->attribute = $attr;
        $r->op = $op;
        $r->value = $value;
        return $r->save();
    }

    public static function disconnectCustomer($username)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
		/**
		* Fix loop to all Nas but still detecting Hotspot Multylogin from other Nas
		*/
		$act = ORM::for_table('radacct')->where_raw("acctstoptime IS NULL")->where('username', $username)->find_one();
        $nas = Radius::getTableNas()->where('nasname', $act['nasipaddress'])->find_many();
        $count = count($nas) * 15;
        set_time_limit($count);
        $result = [];
        foreach ($nas as $n) {
            $port = 3799;
            if (!empty($n['ports'])) {
                $port = $n['ports'];
            }
            $result[] = $n['nasname'] . ': ' . @shell_exec("echo 'User-Name = $username,Framed-IP-Address = " . $act['framedipaddress'] . "' | radclient -x " . trim($n['nasname']) . ":$port disconnect '" . $n['secret'] . "'");
        }
        return $result;
    }
}
