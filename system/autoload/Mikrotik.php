<?php

use PEAR2\Net\RouterOS;

class Mikrotik
{
    public static function info($name)
    {
        $d = ORM::for_table('tbl_routers')->where('name', $name)->find_one();
        return $d;
    }

    public static function getClient($ip, $user, $pass)
    {
        global $ui;
        try {
            $iport = explode(":", $ip);
            return new RouterOS\Client($iport[0], $user, $pass, ($iport[1]) ? $iport[1] : null);
        } catch (Exception $e) {
            $ui->assign("error_meesage", "Unable to connect to the router.<br>" . $e->getMessage());
            $ui->display('router-error.tpl');
            die();
        }
    }

    public static function isUserLogin($client, $username)
    {
        $printRequest = new RouterOS\Request(
            '/ip hotspot active print',
            RouterOS\Query::where('user', $username)
        );
        return $client->sendSync($printRequest)->getProperty('.id');
    }

    public static function logMeIn($client, $user, $pass, $ip, $mac)
    {
        $addRequest = new RouterOS\Request('/ip/hotspot/active/login');
        $client->sendSync(
            $addRequest
                ->setArgument('user', $user)
                ->setArgument('password', $pass)
                ->setArgument('ip', $ip)
                ->setArgument('mac-address', $mac)
        );
    }

    public static function logMeOut($client, $user)
    {
        $printRequest = new RouterOS\Request(
            '/ip hotspot active print',
            RouterOS\Query::where('user', $user)
        );
        $id = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ip/hotspot/active/remove');
        $client(
            $removeRequest
                ->setArgument('numbers', $id)
        );
    }

    public static function addHotspotPlan($client, $name, $sharedusers, $rate)
    {
        $addRequest = new RouterOS\Request('/ip/hotspot/user/profile/add');
        $client->sendSync(
            $addRequest
                ->setArgument('name', $name)
                ->setArgument('shared-users', $sharedusers)
                ->setArgument('rate-limit', $rate)
        );
    }

    public static function setHotspotPlan($client, $name, $sharedusers, $rate)
    {
        $printRequest = new RouterOS\Request(
            '/ip hotspot user profile print .proplist=name',
            RouterOS\Query::where('name', $name)
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');
        if (empty($profileID)) {
            Mikrotik::addHotspotPlan($client, $name, $sharedusers, $rate);
        }else{
            $setRequest = new RouterOS\Request('/ip/hotspot/user/profile/set');
            $client(
                $setRequest
                    ->setArgument('numbers', $profileID)
                    ->setArgument('shared-users', $sharedusers)
                    ->setArgument('rate-limit', $rate)
            );
        }
    }

    public static function setHotspotExpiredPlan($client, $name, $pool)
    {
        $printRequest = new RouterOS\Request(
            '/ip hotspot user profile print .proplist=name',
            RouterOS\Query::where('name', $name)
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');
        if (empty($profileID)) {
            $addRequest = new RouterOS\Request('/ip/hotspot/user/profile/add');
            $client->sendSync(
                $addRequest
                    ->setArgument('name', $name)
                    ->setArgument('shared-users', 3)
                    ->setArgument('address-pool', $pool)
                    ->setArgument('rate-limit', '512K/512K')
            );
        }else{
            $setRequest = new RouterOS\Request('/ip/hotspot/user/profile/set');
            $client(
                $setRequest
                    ->setArgument('numbers', $profileID)
                    ->setArgument('shared-users', 3)
                    ->setArgument('address-pool', $pool)
                    ->setArgument('rate-limit', '512K/512K')
            );
        }
    }

    public static function removeHotspotPlan($client, $name)
    {
        $printRequest = new RouterOS\Request(
            '/ip hotspot user profile print .proplist=name',
            RouterOS\Query::where('name', $name)
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');

        $removeRequest = new RouterOS\Request('/ip/hotspot/user/profile/remove');
        $client(
            $removeRequest
                ->setArgument('numbers', $profileID)
        );
    }

    public static function removeHotspotUser($client, $username)
    {
        $printRequest = new RouterOS\Request(
            '/ip hotspot user print .proplist=name',
            RouterOS\Query::where('name', $username)
        );
        $userID = $client->sendSync($printRequest)->getProperty('.id');
        $removeRequest = new RouterOS\Request('/ip/hotspot/user/remove');
        $client(
            $removeRequest
                ->setArgument('numbers', $userID)
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
                        ->setArgument('comment', $customer['fullname'])
                        ->setArgument('email', $customer['email'])
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
                        ->setArgument('comment', $customer['fullname'])
                        ->setArgument('email', $customer['email'])
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
                        ->setArgument('comment', $customer['fullname'])
                        ->setArgument('email', $customer['email'])
                        ->setArgument('limit-uptime', $timelimit)
                        ->setArgument('limit-bytes-total', $datalimit)
                );
            }
        } else {
            $client->sendSync(
                $addRequest
                    ->setArgument('name', $customer['username'])
                    ->setArgument('profile', $plan['name_plan'])
                    ->setArgument('comment', $customer['fullname'])
                    ->setArgument('email', $customer['email'])
                    ->setArgument('password', $customer['password'])
            );
        }
    }

    public static function setHotspotUser($client, $user, $pass)
    {
        $printRequest = new RouterOS\Request('/ip/hotspot/user/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $user));
        $id = $client->sendSync($printRequest)->getProperty('.id');

        $setRequest = new RouterOS\Request('/ip/hotspot/user/set');
        $setRequest->setArgument('numbers', $id);
        $setRequest->setArgument('password', $pass);
        $client->sendSync($setRequest);
    }

    public static function setHotspotUserPackage($client, $user, $plan)
    {
        $printRequest = new RouterOS\Request('/ip/hotspot/user/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $user));
        $id = $client->sendSync($printRequest)->getProperty('.id');

        $setRequest = new RouterOS\Request('/ip/hotspot/user/set');
        $setRequest->setArgument('numbers', $id);
        $setRequest->setArgument('profile', $plan);
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

    public static function removePpoeUser($client, $username)
    {

        $printRequest = new RouterOS\Request('/ppp/secret/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $username));
        $id = $client->sendSync($printRequest)->getProperty('.id');

        $removeRequest = new RouterOS\Request('/ppp/secret/remove');
        $client(
            $removeRequest
                ->setArgument('numbers', $id)
        );
    }

    public static function addPpoeUser($client, $plan, $customer)
    {
        $addRequest = new RouterOS\Request('/ppp/secret/add');
        if (!empty($customer['pppoe_password'])) {
            $pass = $customer['pppoe_password'];
        } else {
            $pass = $customer['password'];
        }
        $client->sendSync(
            $addRequest
                ->setArgument('name', $customer['username'])
                ->setArgument('service', 'pppoe')
                ->setArgument('profile', $plan['name_plan'])
                ->setArgument('comment', $customer['fullname'] . ' | ' . $customer['email'])
                ->setArgument('password', $pass)
        );
    }

    public static function setPpoeUser($client, $user, $pass)
    {
        $printRequest = new RouterOS\Request('/ppp/secret/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $user));
        $id = $client->sendSync($printRequest)->getProperty('.id');

        $setRequest = new RouterOS\Request('/ppp/secret/set');
        $setRequest->setArgument('numbers', $id);
        $setRequest->setArgument('password', $pass);
        $client->sendSync($setRequest);
    }

    public static function setPpoeUserPlan($client, $user, $plan)
    {
        $printRequest = new RouterOS\Request('/ppp/secret/print');
        $printRequest->setArgument('.proplist', '.id');
        $printRequest->setQuery(RouterOS\Query::where('name', $user));
        $id = $client->sendSync($printRequest)->getProperty('.id');

        $setRequest = new RouterOS\Request('/ppp/secret/set');
        $setRequest->setArgument('numbers', $id);
        $setRequest->setArgument('profile', $plan);
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

    public static function removePool($client, $name)
    {
        $printRequest = new RouterOS\Request(
            '/ip pool print .proplist=name',
            RouterOS\Query::where('name', $name)
        );
        $poolID = $client->sendSync($printRequest)->getProperty('.id');

        $removeRequest = new RouterOS\Request('/ip/pool/remove');
        $client(
            $removeRequest
                ->setArgument('numbers', $poolID)
        );
    }

    public static function addPool($client, $name, $ip_address)
    {
        $addRequest = new RouterOS\Request('/ip/pool/add');
        $client->sendSync(
            $addRequest
                ->setArgument('name', $name)
                ->setArgument('ranges', $ip_address)
        );
    }

    public static function setPool($client, $name, $ip_address)
    {
        $printRequest = new RouterOS\Request(
            '/ip pool print .proplist=name',
            RouterOS\Query::where('name', $name)
        );
        $poolID = $client->sendSync($printRequest)->getProperty('id');

        if (empty($poolID)) {
            self::addPool($client, $name, $ip_address);
        } else {
            $setRequest = new RouterOS\Request('/ip/pool/set');
            $client(
                $setRequest
                    ->setArgument('numbers', $poolID)
                    ->setArgument('ranges', $ip_address)
            );
        }
    }


    public static function addPpoePlan($client, $name, $pool, $rate)
    {
        $addRequest = new RouterOS\Request('/ppp/profile/add');
        $client->sendSync(
            $addRequest
                ->setArgument('name', $name)
                ->setArgument('local-address', $pool)
                ->setArgument('remote-address', $pool)
                ->setArgument('rate-limit', $rate)
        );
    }

    public static function setPpoePlan($client, $name, $pool, $rate)
    {
        $printRequest = new RouterOS\Request(
            '/ppp profile print .proplist=name',
            RouterOS\Query::where('name', $name)
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');
        if (empty($profileID)) {
            self::addPpoePlan($client, $name, $pool, $rate);
        } else {
            $setRequest = new RouterOS\Request('/ppp/profile/set');
            $client(
                $setRequest
                    ->setArgument('numbers', $profileID)
                    ->setArgument('local-address', $pool)
                    ->setArgument('remote-address', $pool)
                    ->setArgument('rate-limit', $rate)
            );
        }
    }

    public static function removePpoePlan($client, $name)
    {
        $printRequest = new RouterOS\Request(
            '/ppp profile print .proplist=name',
            RouterOS\Query::where('name', $name)
        );
        $profileID = $client->sendSync($printRequest)->getProperty('.id');

        $removeRequest = new RouterOS\Request('/ppp/profile/remove');
        $client(
            $removeRequest
                ->setArgument('numbers', $profileID)
        );
    }
}
