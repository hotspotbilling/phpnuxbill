<?php

class Radius {

    public static function getTableNas(){
        return ORM::for_table('nas', 'radius');
    }

    public static function getTableCustomer(){
        return ORM::for_table('radcheck', 'radius');
    }

    public static function getTablePackage(){
        return ORM::for_table('radgroupreply', 'radius');
    }

    public static function getTableUserPackage(){
        return ORM::for_table('radusergroup', 'radius');
    }

    public static function addNas($name, $ip, $ports, $secret, $description = "",$type = 'other', $server= null, $community= null){
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

    public static function updateNas($id, $name, $ip, $ports, $secret, $description = "",$type = 'other', $server= null, $community= null){
        $n = Radius::getTableNas()->find_one($id);
        if(empty($n)){
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

}
