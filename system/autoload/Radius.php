<?php

class Radius {

    public function getTableNas(){
        return ORM::for_table('nas', 'radius');
    }

    public function getTableCustomer(){
        return ORM::for_table('radcheck', 'radius');
    }

    public function getTablePackage(){
        return ORM::for_table('radgroupreply', 'radius');
    }

    public function getTableUserPackage(){
        return ORM::for_table('radusergroup', 'radius');
    }

    public function addNas($name, $ip, $ports, $secret, $description = "",$type = 'other', $server= null, $community= null){
        $n = $this->getTableNas()->create();
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
    public function updateNas($id, $name, $ip, $ports, $secret, $description = "",$type = 'other', $server= null, $community= null){
        $n = $this->getTableNas()->find_one($id);
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
        $n->save();
        return true;
    }

}
