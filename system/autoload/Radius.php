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

    public static function nasList($search = null){
        if($search == null){
            return ORM::for_table('nas', 'radius')->find_many();
        }else{
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

    public static function planAdd($plan_id, $plan_name, $rate, $pool = null)
    {
        $rates = explode('/', $rate);
        $r = Radius::getTablePackage()->create();
        $r->groupname = $plan_name;
        $r->attribute = 'Ascend-Data-Rate';
        $r->op = ':=';
        $r->value = $rates[1];
        $r->plan_id = $plan_id;
        if ($r->save()) {
            $r = Radius::getTablePackage()->create();
            $r->groupname = $plan_name;
            $r->attribute = 'Ascend-Xmit-Rate';
            $r->op = ':=';
            $r->value = $rates[0];
            $r->plan_id = $plan_id;
            if ($r->save()) {
                if ($pool != null) {
                    $r = Radius::getTablePackage()->create();
                    $r->groupname = $plan_name;
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

    public static function planUpdate($plan_id, $plan_name, $rate, $pool = null)
    {
        $rates = explode('/', $rate);
        if(Radius::getTablePackage()->where_equal('plan_id', $plan_id)->find_one()){
            $r = Radius::getTablePackage()->where_equal('plan_id', $plan_id)->whereEqual('attribute', 'Ascend-Data-Rate')->findOne();
            $r->groupname = $plan_name;
            $r->value = $rates[1];
            if ($r->save()) {
                $r = Radius::getTablePackage()->where_equal('plan_id', $plan_id)->whereEqual('attribute', 'Ascend-Xmit-Rate')->findOne();
                $r->groupname = $plan_name;
                $r->value = $rates[0];
                if ($r->save()) {
                    if ($pool != null) {
                        $r = Radius::getTablePackage()->where_equal('plan_id', $plan_id)->whereEqual('attribute', 'Framed-Pool')->findOne();
                        $r->groupname = $plan_name;
                        $r->value = $pool;
                        if ($r->save()) {
                            return true;
                        }
                    } else {
                        return true;
                    }
                }
            }
        }else{
            if(!empty($plan_id)){
                return Radius::planAdd($plan_id, $plan_name, $rate, $pool);
            }
        }
        return false;
    }
}
