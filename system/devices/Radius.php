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
        $p = ORM::for_table('tbl_plans')
            ->where('id', $plan['id'])
            ->findOne();

        $date_only = date("Y-m-d");
        if ($p['validity_unit'] == 'Period') {
            // if customer has attribute Expired Date use it
            $day_exp = User::getAttribute("Expired Date", $customer['id']);
            if (!$day_exp) {
                // if customer no attribute Expired Date use plan expired date
                $day_exp = 20;
                if ($p['prepaid'] == 'no') {
                    $day_exp = $p['expired_date'];
                }
                if (empty($day_exp)) {
                    $day_exp = 20;
                }
            }
        }

        if ($p['validity_unit'] == 'Months') {
            $date_exp = date("Y-m-d", strtotime('+' . $p['validity'] . ' month'));
        } else if ($p['validity_unit'] == 'Period') {
            $date_tmp = date("Y-m-$day_exp", strtotime('+' . $p['validity'] . ' month'));
            $dt1 = new DateTime("$date_only");
            $dt2 = new DateTime("$date_tmp");
            $diff = $dt2->diff($dt1);
            $sum =  $diff->format("%a"); // => 453
            if ($sum >= 35 * $p['validity']) {
                $date_exp = date("Y-m-$day_exp", strtotime('+0 month'));
            } else {
                $date_exp = date("Y-m-$day_exp", strtotime('+' . $p['validity'] . ' month'));
            };
            $time = date("23:59:00");
        } else if ($p['validity_unit'] == 'Days') {
            $datetime = explode(' ', date("Y-m-d H:i:s", strtotime('+' . $p['validity'] . ' day')));
            $date_exp = $datetime[0];
            $time = $datetime[1];
        } else if ($p['validity_unit'] == 'Hrs') {
            $datetime = explode(' ', date("Y-m-d H:i:s", strtotime('+' . $p['validity'] . ' hour')));
            $date_exp = $datetime[0];
            $time = $datetime[1];
        } else if ($p['validity_unit'] == 'Mins') {
            $datetime = explode(' ', date("Y-m-d H:i:s", strtotime('+' . $p['validity'] . ' minute')));
            $date_exp = $datetime[0];
            $time = $datetime[1];
        }

        if ($p) {
            $this->customerAddPlan($customer, $plan, $date_exp . ' ' . $time);
        }
    }

    function remove_customer($customer, $plan)
    {
        if (!empty($plan['plan_expired'])) {
            $p = ORM::for_table("tbl_plans")->find_one($plan['plan_expired']);
            if ($p) {
                $this->customerAddPlan($customer, $p);
            }
        }
        $this->customerDeactivate($customer['username'], true);
    }

    public function change_username($plan, $from, $to)
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
        $this->getTablePackage()->where_equal('plan_id', $plan['id'])->delete_many();
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
    public function customerAddPlan($customer, $plan, $expired = '')
    {
        global $config;
        if ($this->customerUpsert($customer, $plan)) {
            $p = $this->getTableUserPackage()->where_equal('username', $customer['username'])->findOne();
            if ($p) {
                // if exists
                // session timeout [it reset everyday, am still making my research] we can use it for something like 1H/Day - since it reset daily, and Max-All-Session clear everything
                //$this->delAtribute($customer['username'], 'Session-Timeout', 3600); // 3600 = 1 hour
                $this->delAtribute($this->getTableCustomer(), 'Max-All-Session', 'username', $customer['username']);
                $this->delAtribute($this->getTableCustomer(), 'Max-Data', 'username', $customer['username']);
                $this->delAtribute($this->getTableCustomer(), 'Mikrotik-Rate-Limit', 'username', $customer['username']);
                $this->delAtribute($this->getTableCustomer(), 'WISPr-Session-Terminate-Time', 'username', $customer['username']);
                //$this->delAtribute($this->getTableCustomer(), 'Ascend-Data-Rate', 'username', $customer['username']);
                //we are removing the below in the next two updates, some users may have that attribute, it will remove them before we remove it
                $this->delAtribute($this->getTableCustomer(), 'access-period', 'username', $customer['username']);
                $this->delAtribute($this->getTableCustomer(), 'Max-Volume', 'username', $customer['username']);
                $p->groupname = "plan_" . $plan['id'];
                $p->save();
            } else {
                $p = $this->getTableUserPackage()->create();
                $p->username = $customer['username'];
                $p->groupname = "plan_" . $plan['id'];
                $p->priority = 1;
                $p->save();
            }


            $this->addBandwidth($customer, $plan);

            if ($plan['type'] == 'Hotspot' && $plan['typebp'] == "Limited") {
                if ($plan['limit_type'] == "Time_Limit") {
                    if ($plan['time_unit'] == 'Hrs')
                        $timelimit = $plan['time_limit'] * 60 * 60;
                    else
                        $timelimit = $plan['time_limit'] * 60;
                    // session timeout [it reset everyday, am still making my research] we can use it for something like 1H/Day - since it reset daily, and Max-All-Session clear everything
                    //$this->upsertCustomer($customer['username'], 'Session-Timeout', 3600); // 3600 = 1 hour
                    $this->upsertCustomer($customer['username'], 'Max-All-Session', $timelimit);
                } else if ($plan['limit_type'] == "Data_Limit") {
                    if ($plan['data_unit'] == 'GB')
                        $datalimit = $plan['data_limit'] . "000000000";
                    else
                        $datalimit = $plan['data_limit'] . "000000";
                    $this->upsertCustomer($customer['username'], 'Max-Data', $datalimit);
                } else if ($plan['limit_type'] == "Both_Limit") {
                    if ($plan['time_unit'] == 'Hrs')
                        $timelimit = $plan['time_limit'] * 60 * 60;
                    else
                        $timelimit = $plan['time_limit'] * 60;
                    // session timeout [it reset everyday, am still making my research] we can use it for something like 1H/Day - since it reset daily, and Max-All-Session clear everything
                    //$this->upsertCustomer($customer['username'], 'Session-Timeout', 3600); // 3600 = 1 hour
                    $this->upsertCustomer($customer['username'], 'Max-All-Session', $timelimit);
                    if ($plan['data_unit'] == 'GB')
                        $datalimit = $plan['data_limit'] . "000000000";
                    else
                        $datalimit = $plan['data_limit'] . "000000";
                    // Mikrotik Spesific
                    $this->upsertCustomer($customer['username'], 'Max-Data', $datalimit);
                }
            } else {
                // session timeout [it reset everyday, am still making my research] we can use it for something like 1H/Day - since it reset daily, and Max-All-Session clear everything
                //$this->delAtribute($customer['username'], 'Session-Timeout', 3600); // 3600 = 1 hour
                $this->delAtribute($this->getTableCustomer(), 'Max-All-Session', 'username', $customer['username']);
                $this->delAtribute($this->getTableCustomer(), 'Max-Data', 'username', $customer['username']);
                $this->delAtribute($this->getTableCustomer(), 'Mikrotik-Rate-Limit', 'username', $customer['username']);
                $this->delAtribute($this->getTableCustomer(), 'WISPr-Session-Terminate-Time', 'username', $customer['username']);
                //we are removing the below in the next two updates, some users may have that attribute, it will remove them before we remove it
                $this->delAtribute($this->getTableCustomer(), 'access-period', 'username', $customer['username']);
                $this->delAtribute($this->getTableCustomer(), 'Max-Volume', 'username', $customer['username']);
            }

            $this->disconnectCustomer($customer['username']);
            $this->getTableAcct()->where_equal('username', $customer['username'])->delete_many();
            // expired user
            if ($expired != '') {
                //extend session time only if the plan are the same
                if ($plan['plan_id'] == $p['plan_id'] && $config['extend_expiry'] != 'no') {
                    // session timeout [it reset everyday, am still making my research] we can use it for something like 1H/Day - since it reset daily, and Max-All-Session clear everything
                    //$this->upsertCustomer($customer['username'], 'Session-Timeout', 3600); // 3600 = 1 hour
                    $this->upsertCustomer($customer['username'], 'Max-All-Session', strtotime($expired) - time());
                    $this->upsertCustomer($customer['username'], 'Expiration', date('d M Y H:i:s', strtotime($expired)));
                }
                // Mikrotik Spesific
                $this->upsertCustomer(
                    $customer['username'],
                    'WISPr-Session-Terminate-Time',
                    date('Y-m-d', strtotime($expired)) . 'T' . date('H:i:s', strtotime($expired)) . Timezone::getTimeOffset($config['timezone'])
                );
            } else {
                $this->delAtribute($this->getTableCustomer(), 'Max-All-Session', 'username', $customer['username']);
                $this->delAtribute($this->getTableCustomer(), 'Expiration', 'username', $customer['username']);
                $this->delAtribute($this->getTableCustomer(), 'Mikrotik-Rate-Limit', 'username', $customer['username']);
                $this->delAtribute($this->getTableCustomer(), 'WISPr-Session-Terminate-Time', 'username', $customer['username']);
                //we are removing the below in the next two updates, some users may have that attribute, it will remove them before we remove it
                $this->delAtribute($this->getTableCustomer(), 'access-period', 'username', $customer['username']);
            }

            if ($plan['type'] == 'PPPOE') {
                $this->upsertCustomerAttr($customer['username'], 'Framed-Pool', $plan['pool'], ':=');
            }


            return true;
        }
        return false;
    }


    public function customerUpsert($customer, $plan) //Update or Insert customer plan
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

    private function delAtribute($table, $attribute, $key, $value)
    {
        $r = $table->where_equal($key, $value)->whereEqual('attribute', $attribute)->findOne();
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
        $r->save();
        return true;
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

    public function addBandwidth($customer, $plan)
    {
        $bw = ORM::for_table("tbl_bandwidth")->find_one($plan['id_bw']);
        $unitdown = ($bw['rate_down_unit'] == 'Kbps') ? 'K' : 'M';
        $unitup = ($bw['rate_up_unit'] == 'Kbps') ? 'K' : 'M';

        // TODO Burst mode [ 2M/1M 256K/128K 128K/64K 1s 1 64K/32K]

        if (!empty(trim($bw['burst']))) {
            // burst format: 2M/1M 256K/128K 128K/64K 1s 1 64K/32K
            $pattern = '/(\d+[KM])\/(\d+[KM]) (\d+[KM])\/(\d+[KM]) (\d+) (\d+) (\d+[KM])\/(\d+[KM])/';
            preg_match($pattern, $bw['burst'], $matches);
            if (count($matches) == 9) {

                $burst = $bw['rate_up'] . $unitup . "/" . $bw['rate_down'] . $unitdown . ' ' . $matches[1] . '/' . $matches[2] . ' ' . $matches[3] . '/' . $matches[4] . ' ' . $matches[5] . ' ' . $matches[6] . ' ' . $matches[7] . '/' . $matches[8];
                $this->upsertCustomer($customer['username'], 'Mikrotik-Rate-Limit', $burst);
            } else {
                _log("Unexpected burst format for customer " . $customer['username']);
            }
        } else {
            //$this->upsertCustomer($customer['username'], 'Ascend-Data-Rate', $this->stringToInteger($bw['rate_up'] . $unitup) . "/" . $this->stringToInteger($bw['rate_down'] . $unitdown));
            $this->upsertCustomer($customer['username'], 'Mikrotik-Rate-Limit', $bw['rate_up'] . $unitup . "/" . $bw['rate_down'] . $unitdown);
        }

        return true;
    }
}
