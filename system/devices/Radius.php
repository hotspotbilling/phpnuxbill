<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 *
 * This is Core, don't modification except you want to contribute
 * better create new plugin
 **/

class Radius
{

    // show Description
    function description()
    {
        return [
            'title' => 'Radius',
            'description' => 'Radius system with Mysql/Mariadb as database',
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
        $b = ORM::for_table('tbl_user_recharges')
        ->where('customer_id', $customer['id'])
        ->where('plan_id', $plan['id'])
        ->where('status', 'on')
        ->findMany();

        if($b){
            $this->customerAddPlan($customer, $plan, $b['expiration'] . ' ' . $b['time']);
        }
    }

    function remove_customer($customer, $plan)
    {
        if (!empty($plan['plan_expired'])) {
            $p = ORM::for_table("tbl_plans")->find_one($plan['plan_expired']);
            $this->customerAddPlan($customer, $p);
        } else {
            $this->customerDeactivate($customer['username'], true);
        }
    }

    public function change_username($from, $to)
    {
        $c = $this->getTableCustomer()->where_equal('username', $from)->findMany();
        if ($c) {
            foreach ($c as $u) {
                $u->username = $to;
                $u->save();
            }
        }
        $c = $this->getTableUserPackage()->where_equal('username', $from)->findMany();
        if ($c) {
            foreach ($c as $u) {
                $u->username = $to;
                $u->save();
            }
        }
    }

    function add_plan($plan)
    {
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
        $rates = explode('/', $rate);

        // cek jika punya burst
        if (!empty(trim($bw['burst']))) {
            $ratos = $rate . ' ' . $bw['burst'];
        } else {
            $ratos = $rates[0] . '/' . $rates[1];
        }

        $this->upsertPackage($plan['id'], 'Ascend-Data-Rate', $this->stringToInteger($rates[1]), ':=');
        $this->upsertPackage($plan['id'], 'Ascend-Xmit-Rate', $this->stringToInteger($rates[1]), ':=');
        $this->upsertPackage($plan['id'], 'Mikrotik-Rate-Limit', $ratos, ':=');
    }

    function stringToInteger($str)
    {
        return str_replace('G', '000000000', str_replace('M', '000000', str_replace('K', '000', $str)));
    }

    function update_plan($old_name, $plan)
    {
        $this->add_plan($plan);
    }

    function remove_plan($plan)
    {
        // Delete Plan
        $this->getTablePackage()->where_equal('plan_id', "plan_" . $plan['id'])->delete_many();
        // Reset User Plan
        $c = $this->getTableUserPackage()->where_equal('groupname', "plan_" . $plan['id'])->findMany();
        if ($c) {
            foreach ($c as $u) {
                $u->groupname = '';
                $u->save();
            }
        }
    }

    function online_customer($customer, $router_name)
    {
    }

    function connect_customer($customer, $ip, $mac_address, $router_name)
    {
    }

    function disconnect_customer($customer, $router_name)
    {
        $this->disconnectCustomer($customer['username']);
    }

    public function getTableNas()
    {
        return ORM::for_table('nas', 'radius');
    }
    public function getTableAcct()
    {
        return ORM::for_table('radacct', 'radius');
    }
    public function getTableCustomer()
    {
        return ORM::for_table('radcheck', 'radius');
    }

    public function getTableCustomerAttr()
    {
        return ORM::for_table('radreply', 'radius');
    }

    public function getTablePackage()
    {
        return ORM::for_table('radgroupreply', 'radius');
    }

    public function getTableUserPackage()
    {
        return ORM::for_table('radusergroup', 'radius');
    }

    public function nasAdd($name, $ip, $ports, $secret, $routers = "", $description = "", $type = 'other', $server = null, $community = null)
    {
        $n = $this->getTableNas()->create();
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

    public function nasUpdate($id, $name, $ip, $ports, $secret, $routers = "", $description = "", $type = 'other', $server = null, $community = null)
    {
        $n = $this->getTableNas()->find_one($id);
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

    public function customerDeactivate($username, $radiusDisconnect = true)
    { {
            global $radius_pass;
            $r = $this->getTableCustomer()->where_equal('username', $username)->whereEqual('attribute', 'Cleartext-Password')->findOne();
            if ($r) {
                // no need to delete, because it will make ID got higher
                // we just change the password
                $r->value = md5(time() . $username . $radius_pass);
                $r->save();
                if ($radiusDisconnect)
                    return $this->disconnectCustomer($username);
            }
        }
        return '';
    }

    public function customerDelete($username)
    {
        $this->getTableCustomer()->where_equal('username', $username)->delete_many();
        $this->getTableUserPackage()->where_equal('username', $username)->delete_many();
    }

    /**
     * When add a plan to Customer, use this
     */
    public function customerAddPlan($customer, $plan, $expired = null)
    {
        global $config;
        if ($this->customerUpsert($customer, $plan)) {
            $p = $this->getTableUserPackage()->where_equal('username', $customer['username'])->findOne();
            if ($p) {
                // if exists
                $this->delAtribute($this->getTableCustomer(), 'Max-All-Session', 'username', $customer['username']);
                $this->delAtribute($this->getTableCustomer(), 'Max-Volume', 'username', $customer['username']);
                $this->delAtribute($this->getTableCustomer(), 'Max-Data', 'username', $customer['username']);
                $p->groupname = "plan_" . $plan['id'];
                $p->save();
            } else {
                $p = $this->getTableUserPackage()->create();
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
                    $this->upsertCustomer($customer['username'], 'Max-All-Session', $timelimit);
                    $this->upsertCustomer($customer['username'], 'Expire-After', $timelimit);
                } else if ($plan['limit_type'] == "Data_Limit") {
                    if ($plan['data_unit'] == 'GB')
                        $datalimit = $plan['data_limit'] . "000000000";
                    else
                        $datalimit = $plan['data_limit'] . "000000";
                    //$this->upsertCustomer($customer['username'], 'Max-Volume', $datalimit);
                    // Mikrotik Spesific
                    $this->upsertCustomer($customer['username'], 'Max-Data', $datalimit);
                    //$this->upsertCustomer($customer['username'], 'Mikrotik-Total-Limit', $datalimit);
                } else if ($plan['limit_type'] == "Both_Limit") {
                    if ($plan['time_unit'] == 'Hrs')
                        $timelimit = $plan['time_limit'] * 60 * 60;
                    else
                        $timelimit = $plan['time_limit'] * 60;
                    if ($plan['data_unit'] == 'GB')
                        $datalimit = $plan['data_limit'] . "000000000";
                    else
                        $datalimit = $plan['data_limit'] . "000000";
                    //$this->upsertCustomer($customer['username'], 'Max-Volume', $datalimit);
                    $this->upsertCustomer($customer['username'], 'Max-All-Session', $timelimit);
                    // Mikrotik Spesific
                    $this->upsertCustomer($customer['username'], 'Max-Data', $datalimit);
                    //$this->upsertCustomer($customer['username'], 'Mikrotik-Total-Limit', $datalimit);
                }
            } else {
                //$this->delAtribute($this->getTableCustomer(), 'Max-Volume', 'username', $customer['username']);
                $this->delAtribute($this->getTableCustomer(), 'Max-All-Session', 'username', $customer['username']);
                $this->delAtribute($this->getTableCustomer(), 'Max-Data', 'username', $customer['username']);
            }

            $this->disconnectCustomer($customer['username']);
            $this->getTableAcct()->where_equal('username', $customer['username'])->delete_many();


            // expired user
            if ($expired != null) {
                //$this->upsertCustomer($customer['username'], 'access-period', strtotime($expired) - time());
                $this->upsertCustomer($customer['username'], 'Max-All-Session', strtotime($expired) - time());
                $this->upsertCustomer($customer['username'], 'expiration', date('d M Y H:i:s', strtotime($expired)));
                // Mikrotik Spesific
                $this->upsertCustomer(
                    $customer['username'],
                    'WISPr-Session-Terminate-Time',
                    date('Y-m-d', strtotime($expired)) . 'T' . date('H:i:s', strtotime($expired)) . Timezone::getTimeOffset($config['timezone'])
                );
            } else {
                $this->delAtribute($this->getTableCustomer(), 'Max-All-Session', 'username', $customer['username']);
                //$this->delAtribute($this->getTableCustomer(), 'access-period', 'username', $customer['username']);
                $this->delAtribute($this->getTableCustomer(), 'expiration', 'username', $customer['username']);
            }

            if ($plan['type'] == 'PPPOE') {
                $this->upsertCustomerAttr($customer['username'], 'Framed-Pool', $plan['pool'], ':=');
            }


            return true;
        }
        return false;
    }

    public function customerUpsert($customer, $plan)
    {
        if ($plan['type'] == 'PPPOE') {
            $this->upsertCustomer($customer['username'], 'Cleartext-Password', (empty($customer['pppoe_password'])) ? $customer['password'] : $customer['pppoe_password']);
        } else {
            $this->upsertCustomer($customer['username'], 'Cleartext-Password',  $customer['password']);
        }
        $this->upsertCustomer($customer['username'], 'Simultaneous-Use', ($plan['type'] == 'PPPOE') ? 1 : $plan['shared_users']);
        // Mikrotik Spesific
        $this->upsertCustomer($customer['username'], 'Port-Limit', ($plan['type'] == 'PPPOE') ? 1 : $plan['shared_users']);
        $this->upsertCustomer($customer['username'], 'Mikrotik-Wireless-Comment', $customer['fullname']);
        return true;
    }

    private function delAtribute($tabel, $attribute, $key, $value)
    {
        $r = $tabel->where_equal($key, $value)->whereEqual('attribute', $attribute)->findOne();
        if ($r) $r->delete();
    }

    /**
     * To insert or update existing plan
     */
    private function upsertPackage($plan_id, $attr, $value, $op = ':=')
    {
        $r = $this->getTablePackage()->where_equal('plan_id', $plan_id)->whereEqual('attribute', $attr)->find_one();
        if (!$r) {
            $r = $this->getTablePackage()->create();
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
    public function upsertCustomer($username, $attr, $value, $op = ':=')
    {
        $r = $this->getTableCustomer()->where_equal('username', $username)->whereEqual('attribute', $attr)->find_one();
        if (!$r) {
            $r = $this->getTableCustomer()->create();
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
    public function upsertCustomerAttr($username, $attr, $value, $op = ':=')
    {
        $r = $this->getTableCustomerAttr()->where_equal('username', $username)->whereEqual('attribute', $attr)->find_one();
        if (!$r) {
            $r = $this->getTableCustomerAttr()->create();
            $r->username = $username;
        }
        $r->attribute = $attr;
        $r->op = $op;
        $r->value = $value;
        return $r->save();
    }

    public function disconnectCustomer($username)
    {
        global $_app_stage;
        if ($_app_stage == 'demo') {
            return null;
        }
        /**
         * Fix loop to all Nas but still detecting Hotspot Multylogin from other Nas
         */
        $act = ORM::for_table('radacct')->where_raw("acctstoptime IS NULL")->where('username', $username)->find_one();
        $nas = $this->getTableNas()->where('nasname', $act['nasipaddress'])->find_many();
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
