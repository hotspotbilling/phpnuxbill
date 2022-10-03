<?php

use PEAR2\Net\RouterOS;

class Mikrotik
{
    public static function info($name){
		$d = ORM::for_table('tbl_routers')->where('name',$name)->find_one();
        return $d;
    }

    public static function getClient($ip, $user, $pass)
    {
        try {
            $iport = explode(":", $ip);
            return new RouterOS\Client($iport[0], $user, $pass, ($iport[1]) ? $iport[1] : null);
        } catch (Exception $e) {
            die("Unable to connect to the router.<br>" . $e->getMessage());
        }
    }

    public static function addHotspotPlan($client, $name, $sharedusers, $rate){
        $addRequest = new RouterOS\Request('/ip/hotspot/user/profile/add');
        $client->sendSync(
            $addRequest
                ->setArgument('name', $name)
                ->setArgument('shared-users', $sharedusers)
                ->setArgument('rate-limit', $rate)
        );
    }

    public static function setHotspotPlan($client, $name, $sharedusers, $rate){
        $printRequest = new RouterOS\Request(
            '/ip hotspot user profile print .proplist=name',
            RouterOS\Query::where('name', $name)
        );
        $profileName = $client->sendSync($printRequest)->getProperty('name');

        $setRequest = new RouterOS\Request('/ip/hotspot/user/profile/set');
        $client(
            $setRequest
                ->setArgument('numbers', $profileName)
                ->setArgument('shared-users', $sharedusers)
                ->setArgument('rate-limit', $rate)
        );
    }

    public static function removeHotspotPlan($client, $name){
        $printRequest = new RouterOS\Request(
            '/ip hotspot user profile print .proplist=name',
            RouterOS\Query::where('name', $name)
        );
        $profileName = $client->sendSync($printRequest)->getProperty('name');

        $removeRequest = new RouterOS\Request('/ip/hotspot/user/profile/remove');
        $client(
            $removeRequest
                ->setArgument('numbers', $profileName)
        );
    }

    public static function removeHotspotUser($client, $username)
    {
        $printRequest = new RouterOS\Request(
            '/ip hotspot user print .proplist=name',
            RouterOS\Query::where('name', $username)
        );
        $userName = $client->sendSync($printRequest)->getProperty('name');
        $removeRequest = new RouterOS\Request('/ip/hotspot/user/remove');
        $client(
            $removeRequest
                ->setArgument('numbers', $userName)
        );
    }

    public static function addHotspotUser($client, $plan, $customer)
    {
        $addRequest = new RouterOS\Request('/ip/hotspot/user/add');
        if ($plan['typebp'] == "Limited") {
            if ($plan['limit_type'] == "Time_Limit") {
                if ($plan['time_unit'] == 'Hrs')
                    $timelimit = $plan['time_limit'] . ":00:00";
                else
                    $timelimit = "00:" . $plan['time_limit'] . ":00";
                $client->sendSync(
                    $addRequest
                        ->setArgument('name', $customer['username'])
                        ->setArgument('profile', $plan['name_plan'])
                        ->setArgument('password', $customer['password'])
                        ->setArgument('limit-uptime', $timelimit)
                );
            } else if ($plan['limit_type'] == "Data_Limit") {
                if ($plan['data_unit'] == 'GB')
                    $datalimit = $plan['data_limit'] . "000000000";
                else
                    $datalimit = $plan['data_limit'] . "000000";
                $client->sendSync(
                    $addRequest
                        ->setArgument('name', $customer['username'])
                        ->setArgument('profile', $plan['name_plan'])
                        ->setArgument('password', $customer['password'])
                        ->setArgument('limit-bytes-total', $datalimit)
                );
            } else if ($plan['limit_type'] == "Both_Limit") {
                if ($plan['time_unit'] == 'Hrs')
                    $timelimit = $plan['time_limit'] . ":00:00";
                else
                    $timelimit = "00:" . $plan['time_limit'] . ":00";
                if ($plan['data_unit'] == 'GB')
                    $datalimit = $plan['data_limit'] . "000000000";
                else
                    $datalimit = $plan['data_limit'] . "000000";
                $client->sendSync(
                    $addRequest
                        ->setArgument('name', $customer['username'])
                        ->setArgument('profile', $plan['name_plan'])
                        ->setArgument('password', $customer['password'])
                        ->setArgument('limit-uptime', $timelimit)
                        ->setArgument('limit-bytes-total', $datalimit)
                );
            }
        } else {
            $client->sendSync(
                $addRequest
                    ->setArgument('name', $customer['username'])
                    ->setArgument('profile', $plan['name_plan'])
                    ->setArgument('password', $customer['password'])
            );
        }
    }

    public static function setHotspotUser($client, $user, $pass, $nuser= null){
        $printRequest = new RouterOS\Request('/ip/hotspot/user/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $user));
        $id = $client->sendSync($printRequest)->getProperty('.id');

        $setRequest = new RouterOS\Request('/ip/hotspot/user/set');
        $setRequest->setArgument('numbers', $id);
        $setRequest->setArgument('password', $pass);
        $client->sendSync($setRequest);
    }

    public static function removeHotspotActiveUser($client, $username)
    {
        $onlineRequest = new RouterOS\Request('/ip/hotspot/active/print');
        $onlineRequest->setArgument('.proplist', '.id');
        $onlineRequest->setQuery(RouterOS\Query::where('user', $username));
        $id = $client->sendSync($onlineRequest)->getProperty('.id');

        $removeRequest = new RouterOS\Request('/ip/hotspot/active/remove');
        $removeRequest->setArgument('numbers', $id);
        $client->sendSync($removeRequest);
    }

    public static function setHotspotLimitUptime($client, $username)
    {
        $printRequest = new RouterOS\Request('/ip/hotspot/user/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $username));
        $id = $client->sendSync($printRequest)->getProperty('.id');

        $setRequest = new RouterOS\Request('/ip/hotspot/user/set');
        $setRequest->setArgument('numbers', $id);
        $setRequest->setArgument('limit-uptime', '00:00:05');
        $client->sendSync($setRequest);
    }

    public static function removePpoeUser($client, $username)
    {
        $printRequest = new RouterOS\Request(
            '/ppp secret print .proplist=name',
            RouterOS\Query::where('name', $username)
        );
        $userName = $client->sendSync($printRequest)->getProperty('name');

        $removeRequest = new RouterOS\Request('/ppp/secret/remove');
        $client(
            $removeRequest
                ->setArgument('numbers', $userName)
        );
    }

    public static function addPpoeUser($client, $plan, $customer)
    {
        $addRequest = new RouterOS\Request('/ppp/secret/add');
        $client->sendSync(
            $addRequest
                ->setArgument('name', $customer['username'])
                ->setArgument('service', 'pppoe')
                ->setArgument('profile', $plan['name_plan'])
                ->setArgument('password', $customer['password'])
        );
    }

    public static function setPpoeUser($client, $user, $pass, $nuser= null){
        $printRequest = new RouterOS\Request('/ppp/secret/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $user['username']));
        $id = $client->sendSync($printRequest)->getProperty('.id');

        $setRequest = new RouterOS\Request('/ppp/secret/set');
        $setRequest->setArgument('numbers', $id);
        $setRequest->setArgument('password', $pass);
        $client->sendSync($setRequest);
    }

    public static function disablePpoeUser($client, $username)
    {
        $printRequest = new RouterOS\Request('/ppp/secret/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $username));
        $id = $client->sendSync($printRequest)->getProperty('.id');

        $setRequest = new RouterOS\Request('/ppp/secret/disable');
        $setRequest->setArgument('numbers', $id);
        $client->sendSync($setRequest);
    }

    public static function removePpoeActive($client, $username)
    {
        $onlineRequest = new RouterOS\Request('/ppp/active/print');
        $onlineRequest->setArgument('.proplist', '.id');
        $onlineRequest->setQuery(RouterOS\Query::where('name', $username));
        $id = $client->sendSync($onlineRequest)->getProperty('.id');

        $removeRequest = new RouterOS\Request('/ppp/active/remove');
        $removeRequest->setArgument('numbers', $id);
        $client->sendSync($removeRequest);
    }

    public static function removePool($client, $name){
        $printRequest = new RouterOS\Request(
            '/ip pool print .proplist=name',
            RouterOS\Query::where('name', $name)
        );
        $poolName = $client->sendSync($printRequest)->getProperty('name');

        $removeRequest = new RouterOS\Request('/ip/pool/remove');
        $client($removeRequest
            ->setArgument('numbers', $poolName)
        );
    }

    public static function addPool($client, $name, $ip_address){
        $addRequest = new RouterOS\Request('/ip/pool/add');
        $client->sendSync($addRequest
            ->setArgument('name', $name)
            ->setArgument('ranges', $ip_address)
        );
    }

    public static function setPool($client, $name, $ip_address){
        $printRequest = new RouterOS\Request(
            '/ip pool print .proplist=name',
            RouterOS\Query::where('name', $name)
        );
        $poolName = $client->sendSync($printRequest)->getProperty('name');

        if(empty($poolName)){
            self::addPool($client, $name, $ip_address);
        }else{
            $setRequest = new RouterOS\Request('/ip/pool/set');
            $client(
                $setRequest
                    ->setArgument('numbers', $poolName)
                    ->setArgument('ranges', $ip_address)
            );
        }
    }


    public static function addPpoePlan($client, $name, $pool, $rate){
        $addRequest = new RouterOS\Request('/ppp/profile/add');
        $client->sendSync(
            $addRequest
                ->setArgument('name', $name)
                ->setArgument('local-address', $pool)
                ->setArgument('remote-address', $pool)
                ->setArgument('rate-limit', $rate)
        );
    }

    public static function setPpoePlan($client, $name, $pool, $rate){
        $printRequest = new RouterOS\Request(
            '/ppp profile print .proplist=name',
            RouterOS\Query::where('name', $name)
        );
        $profileName = $client->sendSync($printRequest)->getProperty('name');
        if(empty($profileName)){
            self::addPpoePlan($client, $name, $pool, $rate);
        }else{
            $setRequest = new RouterOS\Request('/ppp/profile/set');
            $client(
                $setRequest
                    ->setArgument('numbers', $profileName)
                    ->setArgument('local-address', $pool)
                    ->setArgument('remote-address', $pool)
                    ->setArgument('rate-limit', $rate)
            );
        }
    }

    public static function removePpoePlan($client, $name){
        $printRequest = new RouterOS\Request(
            '/ppp profile print .proplist=name',
            RouterOS\Query::where('name', $name)
        );
        $profileName = $client->sendSync($printRequest)->getProperty('name');

        $removeRequest = new RouterOS\Request('/ppp/profile/remove');
        $client(
            $removeRequest
                ->setArgument('numbers', $profileName)
        );
    }
}
