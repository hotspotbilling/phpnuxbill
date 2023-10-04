<?php

class Radius
{

    public static function getTableNas()
    {
        return ORM::for_table('nas', 'radius');
    }

    public static function getTableCustomer()
    {
        return ORM::for_table('radcheck', 'radius');
    }

    public static function getTablePackage()
    {
        return ORM::for_table('radgroupreply', 'radius');
    }

    public static function getTableUserPackage()
    {
        return ORM::for_table('radusergroup', 'radius');
    }

    public static function nasList($search = null)
    {
        if ($search == null) {
            return ORM::for_table('nas', 'radius')->find_many();
        } else {
            return ORM::for_table('nas', 'radius')
                ->where_like('nasname', $search)
                ->where_like('shortname', $search)
                ->where_like('description', $search)
                ->find_many();
        }
    }

    public static function nasAdd($name, $ip, $ports, $secret, $description = "", $type = 'other', $server = null, $community = null)
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
        $n->save();
        return $n->id();
    }

    public static function nasUpdate($id, $name, $ip, $ports, $secret, $description = "", $type = 'other', $server = null, $community = null)
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
        return $n->save();
    }

    public static function planAdd($plan_id, $rate, $pool = null)
    {
        $rates = explode('/', $rate);
        $r = Radius::getTablePackage()->create();
        $r->groupname = "plan_".$plan_id;
        $r->attribute = 'Ascend-Data-Rate';
        $r->op = ':=';
        $r->value = $rates[1];
        $r->plan_id = $plan_id;
        if ($r->save()) {
            $r = Radius::getTablePackage()->create();
            $r->groupname = "plan_".$plan_id;
            $r->attribute = 'Ascend-Xmit-Rate';
            $r->op = ':=';
            $r->value = $rates[0];
            $r->plan_id = $plan_id;
            if ($r->save()) {
                if ($pool != null) {
                    $r = Radius::getTablePackage()->create();
                    $r->groupname = "plan_".$plan_id;
                    $r->attribute = 'Framed-Pool';
                    $r->op = ':=';
                    $r->value = $pool;
                    $r->plan_id = $plan_id;
                    if ($r->save()) {
                        return true;
                    }
                } else {
                    return true;
                }
            }
        }
        return false;
    }

    public static function planUpdate($plan_id, $rate, $pool = null)
    {
        $rates = explode('/', $rate);
        if (Radius::getTablePackage()->where_equal('plan_id', $plan_id)->find_one()) {
            $r = Radius::getTablePackage()->where_equal('plan_id', $plan_id)->whereEqual('attribute', 'Ascend-Data-Rate')->findOne();
            $r->groupname = "plan_".$plan_id;
            $r->value = $rates[1];
            if ($r->save()) {
                $r = Radius::getTablePackage()->where_equal('plan_id', $plan_id)->whereEqual('attribute', 'Ascend-Xmit-Rate')->findOne();
                $r->groupname = "plan_".$plan_id;
                $r->value = $rates[0];
                if ($r->save()) {
                    if ($pool != null) {
                        $r = Radius::getTablePackage()->where_equal('plan_id', $plan_id)->whereEqual('attribute', 'Framed-Pool')->findOne();
                        $r->groupname = "plan_".$plan_id;
                        $r->value = $pool;
                        if ($r->save()) {
                            return true;
                        }
                    } else {
                        return true;
                    }
                }
            }
        } else {
            if (!empty($plan_id)) {
                return Radius::planAdd($plan_id, $rate, $pool);
            }
        }
        return false;
    }
    public static function customerChangeUsername($from, $to){
        $c = Radius::getTableCustomer()->where_equal('username', $from)->findMany();
        if ($c) {
            foreach($c as $u){
                $u->username = $to;
                $u->save();
            }
        }
        $c = Radius::getTableUserPackage()->where_equal('username', $from)->findMany();
        if ($c) {
            foreach($c as $u){
                $u->username = $to;
                $u->save();
            }
        }
    }

    public static function customerDeactivate($customer){
        global $radius_pass;
        $r = Radius::getTableCustomer()->where_equal('username', $customer['username'])->whereEqual('attribute', 'Cleartext-Password')->findOne();
        if($r){
            // no need to delete, because it will make ID got higher
            // we just change the password
            $r->value = md5(time().$customer['username'].$radius_pass);
            $r->save();
        }
    }

    /**
     * When add a plan to Customer, use this
     */
    public static function customerAddPlan($customer, $plan){
        if(Radius::customerAdd($customer, $plan)){
            $p = Radius::getTableUserPackage()->where_equal('username', $customer['username'])->findOne();
            if ($p) {
                // if exists
                $p->groupname = "plan_".$plan['id'];
                return $p->save();
            }else{
                $p = Radius::getTableUserPackage()->create();
                $p->username = $customer['username'];
                $p->groupname = "plan_".$plan['id'];
                $p->priority = 1;
                return $p->save();
            }
        }
        return false;
    }

    public static function customerAdd($customer, $plan)
    {
        if (Radius::getTableCustomer()->where_equal('username', $customer['username'])->findOne()) {
            // Edit if exists
            $r = Radius::getTableCustomer()->where_equal('username', $customer['username'])->whereEqual('attribute', 'Cleartext-Password')->findOne();
            if($r){
                if($plan['type']=='PPPOE'){
                    if(empty($customer['pppoe_password'])){
                        $r->value = $customer['password'];
                    }else{
                        $r->value = $customer['pppoe_password'];
                    }
                }else{
                    $r->value = $customer['password'];
                }
                $r->save();
            }else{
                $r = Radius::getTableCustomer()->create();
                $r->username = $customer['username'];
                $r->attribute = 'Cleartext-Password';
                $r->op = ':=';
                if($plan['type']=='PPPOE'){
                    if(empty($customer['pppoe_password'])){
                        $r->value = $customer['password'];
                    }else{
                        $r->value = $customer['pppoe_password'];
                    }
                }else{
                    $r->value = $customer['password'];
                }
                $r->save();
            }
            $r = Radius::getTableCustomer()->where_equal('username', $customer['username'])->whereEqual('attribute', 'Simultaneous-Use')->findOne();
            if($r){
                if($plan['type']=='PPPOE'){
                    $r->value = 1;
                }else{
                    $r->value = $plan['shared_users'];
                }
                $r->save();
            }else{
                $r = Radius::getTableCustomer()->create();
                $r->username = $customer['username'];
                $r->attribute = 'Simultaneous-Use';
                $r->op = ':=';
                if($plan['type']=='PPPOE'){
                    $r->value = 1;
                }else{
                    $r->value = $plan['shared_users'];
                }
                $r->save();
            }
            return true;
        } else {
            // add if not exists
            $r = Radius::getTableCustomer()->create();
            $r->username = $customer['username'];
            $r->attribute = 'Cleartext-Password';
            $r->op = ':=';
            if($plan['type']=='PPPOE'){
                if(empty($customer['pppoe_password'])){
                    $r->value = $customer['password'];
                }else{
                    $r->value = $customer['pppoe_password'];
                }
            }else{
                $r->value = $customer['password'];
            }
            if ($r->save()) {
                $r = Radius::getTableCustomer()->create();
                $r->username = $customer['username'];
                $r->attribute = 'Simultaneous-Use';
                $r->op = ':=';
                if($plan['type']=='PPPOE'){
                    $r->value = 1;
                }else{
                    $r->value = $plan['shared_users'];
                }
                $r->save();
                return true;
            }
        }
        return false;
    }
}
