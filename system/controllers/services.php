<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/
_admin();
$ui->assign('_title', Lang::T('Hotspot Plans'));
$ui->assign('_system_menu', 'services');

$action = $routes['1'];
$ui->assign('_admin', $admin);

if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
}

use PEAR2\Net\RouterOS;

require_once 'system/autoload/PEAR2/Autoload.php';

switch ($action) {
    case 'sync':
        set_time_limit(-1);
        if ($routes['2'] == 'hotspot') {
            $plans = ORM::for_table('tbl_bandwidth')
                ->join('tbl_plans', array('tbl_bandwidth.id', '=', 'tbl_plans.id_bw'))
                ->where('tbl_plans.type', 'Hotspot')
                ->where('tbl_plans.enabled', '1')
                ->find_many();
            $log = '';
            $router = '';
            foreach ($plans as $plan) {
                if ($plan['is_radius']) {
                    if ($plan['rate_down_unit'] == 'Kbps') {
                        $raddown = '000';
                    } else {
                        $raddown = '000000';
                    }
                    if ($plan['rate_up_unit'] == 'Kbps') {
                        $radup = '000';
                    } else {
                        $radup = '000000';
                    }
                    $radiusRate = $plan['rate_up'] . $radup . '/' . $plan['rate_down'] . $raddown;
                    Radius::planUpSert($plan['id'], $radiusRate);
                    $log .= "DONE : Radius $plan[name_plan], $plan[shared_users], $radiusRate<br>";
                } else {
                    if ($router != $plan['routers']) {
                        $mikrotik = Mikrotik::info($plan['routers']);
                        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        $router = $plan['routers'];
                    }
                    if ($plan['rate_down_unit'] == 'Kbps') {
                        $unitdown = 'k';
                    } else {
                        $unitdown = 'M';
                    }
                    if ($plan['rate_up_unit'] == 'Kbps') {
                        $unitup = 'k';
                    } else {
                        $unitup = 'M';
                    }
                    $rate = $plan['rate_up'] . $unitup . "/" . $plan['rate_down'] . $unitdown;
    
                    // Get the burst limit
                    $burst_limit_up = $plan['burst_limit_up'];
                    $burst_limit_up_unit = $plan['burst_limit_up_unit'] == 'Kbps' ? 'k' : 'M';
                    $burst_limit_down = $plan['burst_limit_down'];
                    $burst_limit_down_unit = $plan['burst_limit_down_unit'] == 'Kbps' ? 'k' : 'M';
                    $burst_limit = $burst_limit_up . $burst_limit_up_unit . "/" . $burst_limit_down . $burst_limit_down_unit;
    
                    // Get the burst threshold
                    $burst_threshold_up = $plan['burst_threshold_up'];
                    $burst_threshold_up_unit = $plan['burst_threshold_up_unit'] == 'Kbps' ? 'k' : 'M';
                    $burst_threshold_down = $plan['burst_threshold_down'];
                    $burst_threshold_down_unit = $plan['burst_threshold_down_unit'] == 'Kbps' ? 'k' : 'M';
                    $burst_threshold = $burst_threshold_up . $burst_threshold_up_unit . "/" . $burst_threshold_down . $burst_threshold_down_unit;
    
                    // Get the burst time
                    $burst_time = $plan['burst_time'];
    
                    // Construct the rate limit string with burst information
                    $rate = $rate . " " . $burst_limit . " " . $burst_threshold . " " . $burst_time . "/" . $burst_time;
    
                    Mikrotik::addHotspotPlan($client, $plan['name_plan'], $plan['shared_users'], $rate);
                    $log .= "DONE : $plan[name_plan], $plan[shared_users], $rate<br>";
                    if (!empty($plan['pool_expired'])) {
                        Mikrotik::setHotspotExpiredPlan($client, 'EXPIRED NUXBILL ' . $plan['pool_expired'], $plan['pool_expired']);
                        $log .= "DONE Expired : EXPIRED NUXBILL $plan[pool_expired]<br>";
                    }
                }
            }
            r2(U . 'services/hotspot', 's', $log);
        }
        
        else if ($routes['2'] == 'pppoe') {
            $plans = ORM::for_table('tbl_bandwidth')
                ->join('tbl_plans', array('tbl_bandwidth.id', '=', 'tbl_plans.id_bw'))
                ->where('tbl_plans.type', 'PPPOE')
                ->where('tbl_plans.enabled', '1')
                ->find_many();
            $log = '';
            $router = '';
            foreach ($plans as $plan) {
                if ($plan['is_radius']) {
                    if ($plan['rate_down_unit'] == 'Kbps') {
                        $raddown = '000';
                    } else {
                        $raddown = '000000';
                    }
                    if ($plan['rate_up_unit'] == 'Kbps') {
                        $radup = '000';
                    } else {
                        $radup = '000000';
                    }
                    $radiusRate = $plan['rate_up'] . $radup . '/' . $plan['rate_down'] . $raddown;
                    Radius::planUpSert($plan['id'], $radiusRate, $plan['pool']);
                    $log .= "DONE : RADIUS $plan[name_plan], $plan[pool], $radiusRate<br>";
                } else {
                    if ($router != $plan['routers']) {
                        $mikrotik = Mikrotik::info($plan['routers']);
                        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        $router = $plan['routers'];
                    }
                    if ($plan['rate_down_unit'] == 'Kbps') {
                        $unitdown = 'k';
                    } else {
                        $unitdown = 'M';
                    }
                    if ($plan['rate_up_unit'] == 'Kbps') {
                        $unitup = 'k';
                    } else {
                        $unitup = 'M';
                    }
                    $rate = $plan['rate_up'] . $unitup . "/" . $plan['rate_down'] . $unitdown;
        
                    // Get the burst limit
                    $burst_limit_up = $plan['burst_limit_up'];
                    $burst_limit_up_unit = $plan['burst_limit_up_unit'] == 'Kbps' ? 'k' : 'M';
                    $burst_limit_down = $plan['burst_limit_down'];
                    $burst_limit_down_unit = $plan['burst_limit_down_unit'] == 'Kbps' ? 'k' : 'M';
                    $burst_limit = $burst_limit_up . $burst_limit_up_unit . "/" . $burst_limit_down . $burst_limit_down_unit;
        
                    // Get the burst threshold
                    $burst_threshold_up = $plan['burst_threshold_up'];
                    $burst_threshold_up_unit = $plan['burst_threshold_up_unit'] == 'Kbps' ? 'k' : 'M';
                    $burst_threshold_down = $plan['burst_threshold_down'];
                    $burst_threshold_down_unit = $plan['burst_threshold_down_unit'] == 'Kbps' ? 'k' : 'M';
                    $burst_threshold = $burst_threshold_up . $burst_threshold_up_unit . "/" . $burst_threshold_down . $burst_threshold_down_unit;
        
                    // Get the burst time
                    $burst_time = $plan['burst_time'];
        
                    // Construct the rate limit string with burst information
                    $rate = $rate . "/" . $burst_limit . "/" . $burst_threshold . "/" . $burst_time . "/" . $burst_time;
        
                    Mikrotik::addPpoePlan($client, $plan['name_plan'], $plan['pool'], $rate);
                    $log .= "DONE : $plan[name_plan], $plan[pool], $rate<br>";
                    if (!empty($plan['pool_expired'])) {
                        Mikrotik::setPpoePlan($client, 'EXPIRED NUXBILL ' . $plan['pool_expired'], $plan['pool_expired'], '512k/512k');
                        $log .= "DONE Expired : EXPIRED NUXBILL $plan[pool_expired]<br>";
                    }
                }
            }
            r2(U . 'services/pppoe', 's', $log);
        }
        r2(U . 'services/hotspot', 'w', 'Unknown command');
    case 'hotspot':
        $ui->assign('xfooter', '<script type="text/javascript" src="ui/lib/c/hotspot.js"></script>');

        $name = _post('name');
        if ($name != '') {
            $query = ORM::for_table('tbl_bandwidth')->join('tbl_plans', array('tbl_bandwidth.id', '=', 'tbl_plans.id_bw'))->where('tbl_plans.type', 'Hotspot')->where_like('tbl_plans.name_plan', '%' . $name . '%');
            $d = Paginator::findMany($query, ['name' => $name]);
        } else {
            $query = ORM::for_table('tbl_bandwidth')->join('tbl_plans', array('tbl_bandwidth.id', '=', 'tbl_plans.id_bw'))->where('tbl_plans.type', 'Hotspot');
            $d = Paginator::findMany($query);
        }

        $ui->assign('d', $d);
        run_hook('view_list_plans'); #HOOK
        $ui->display('hotspot.tpl');
        break;

    case 'add':
        $d = ORM::for_table('tbl_bandwidth')->find_many();
        $ui->assign('d', $d);
        $r = ORM::for_table('tbl_routers')->find_many();
        $ui->assign('r', $r);
        run_hook('view_add_plan'); #HOOK
        $ui->display('hotspot-add.tpl');
        break;

    case 'edit':
        $id = $routes['2'];
        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            $ui->assign('d', $d);
            $p = ORM::for_table('tbl_pool')->where('routers', $d['routers'])->find_many();
            $ui->assign('p', $p);
            $b = ORM::for_table('tbl_bandwidth')->find_many();
            $ui->assign('b', $b);
            run_hook('view_edit_plan'); #HOOK
            $ui->display('hotspot-edit.tpl');
        } else {
            r2(U . 'services/hotspot', 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'delete':
        $id = $routes['2'];

        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            run_hook('delete_plan'); #HOOK
            if ($d['is_radius']) {
                Radius::planDelete($d['id']);
            } else {
                try {
                    $mikrotik = Mikrotik::info($d['routers']);
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::removeHotspotPlan($client, $d['name_plan']);
                } catch (Exception $e) {
                    //ignore exception, it means router has already deleted
                } catch (Throwable $e) {
                    //ignore exception, it means router has already deleted
                }
            }

            $d->delete();

            r2(U . 'services/hotspot', 's', Lang::T('Data Deleted Successfully'));
        }
        break;
        case 'add-post':
            $name = _post('name');
            $plan_type = _post('plan_type'); //Personal / Business
            $radius = _post('radius');
            $typebp = _post('typebp');
            $limit_type = _post('limit_type');
            $time_limit = _post('time_limit');
            $time_unit = _post('time_unit');
            $data_limit = _post('data_limit');
            $data_unit = _post('data_unit');
            $id_bw = _post('id_bw');
            $price = _post('price');
            $sharedusers = _post('sharedusers');
            $validity = _post('validity');
            $validity_unit = _post('validity_unit');
            $routers = _post('routers');
            $pool_expired = _post('pool_expired');
            $list_expired = _post('list_expired');
            $enabled = _post('enabled');
            $prepaid = _post('prepaid');
        
            $msg = '';
            if (Validator::UnsignedNumber($validity) == false) {
                $msg .= 'The validity must be a number' . '<br>';
            }
            if (Validator::UnsignedNumber($price) == false) {
                $msg .= 'The price must be a number' . '<br>';
            }
            if ($name == '' or $id_bw == '' or $price == '' or $validity == '') {
                $msg .= Lang::T('All field is required') . '<br>';
            }
            if (empty($radius)) {
                if ($routers == '') {
                    $msg .= Lang::T('All field is required') . '<br>';
                }
            }
            $d = ORM::for_table('tbl_plans')->where('name_plan', $name)->where('type', 'Hotspot')->find_one();
            if ($d) {
                $msg .= Lang::T('Name Plan Already Exist') . '<br>';
            }
        
            run_hook('add_plan'); #HOOK
        
            if ($msg == '') {
                $b = ORM::for_table('tbl_bandwidth')->where('id', $id_bw)->find_one();
                if ($b['rate_down_unit'] == 'Kbps') {
                    $unitdown = 'k';
                    $raddown = '000';
                } else {
                    $unitdown = 'M';
                    $raddown = '000000';
                }
                if ($b['rate_up_unit'] == 'Kbps') {
                    $unitup = 'k';
                    $radup = '000';
                } else {
                    $unitup = 'M';
                    $radup = '000000';
                }
                $rate = $b['rate_up'] . $unitup . "/" . $b['rate_down'] . $unitdown;
                $radiusRate = $b['rate_up'] . $radup . '/' . $b['rate_down'] . $raddown;
        
                // Check if all burst fields are entered
                if (!empty($b['burst_limit_up']) && !empty($b['burst_limit_down']) && !empty($b['burst_threshold_up']) && !empty($b['burst_threshold_down']) && !empty($b['burst_time'])) {
                    // Get the burst limit
                    $burst_limit_up = $b['burst_limit_up'];
                    $burst_limit_up_unit = $b['burst_limit_up_unit'] == 'Kbps' ? 'k' : 'M';
                    $burst_limit_down = $b['burst_limit_down'];
                    $burst_limit_down_unit = $b['burst_limit_down_unit'] == 'Kbps' ? 'k' : 'M';
                    $burst_limit = $burst_limit_up . $burst_limit_up_unit . "/" . $burst_limit_down . $burst_limit_down_unit;
        
                    // Get the burst threshold
                    $burst_threshold_up = $b['burst_threshold_up'];
                    $burst_threshold_up_unit = $b['burst_threshold_up_unit'] == 'Kbps' ? 'k' : 'M';
                    $burst_threshold_down = $b['burst_threshold_down'];
                    $burst_threshold_down_unit = $b['burst_threshold_down_unit'] == 'Kbps' ? 'k' : 'M';
                    $burst_threshold = $burst_threshold_up . $burst_threshold_up_unit . "/" . $burst_threshold_down . $burst_threshold_down_unit;
        
                    // Get the burst time
                    $burst_time = $b['burst_time'];
        
                    // Construct the rate limit string with burst information
                    $rate = $rate . " " . $burst_limit . " " . $burst_threshold . " " . $burst_time . "/" . $burst_time;
                }
        
                // Log the generated rate limit string
                $logFile = __DIR__ . '/rate_limit.log';
                $timestamp = date('Y-m-d H:i:s');
                $logMessage = "[$timestamp] Generated Rate Limit String: $rate\n";
                file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
        
                // Check if tax is enabled in config
                $tax_enable = isset($config['enable_tax']) ? $config['enable_tax'] : 'no';
        
                // Default tax rate
                $default_tax_rate = 0.01; // Default tax rate 1%
        
                // Check if tax rate is set to custom in config
                $tax_rate_setting = isset($config['tax_rate']) ? $config['tax_rate'] : $default_tax_rate;
        
                // Check if tax rate is custom
                if ($tax_rate_setting === 'custom') {
                    // Check if custom tax rate is set in config
                    $custom_tax_rate = isset($config['custom_tax_rate']) ? (float)$config['custom_tax_rate'] : $default_tax_rate;
                    // Convert custom tax rate to decimal
                    $custom_tax_rate_decimal = $custom_tax_rate / 100;
                    $tax_rate = $custom_tax_rate_decimal;
                } else {
                    // Use tax rate
                    $tax_rate = $tax_rate_setting;
                }
        
                // Calculate the new price with tax if tax is enabled
                if ($tax_enable === 'yes') {
                    $price_with_tax = $price + ($price * $tax_rate);
                } else {
                    // If tax is not enabled, use the original price
                    $price_with_tax = $price;
                }
        
                // Create new plan
                $d = ORM::for_table('tbl_plans')->create();
                $d->name_plan = $name;
                $d->id_bw = $id_bw;
                $d->price = $price_with_tax; // Set price with or without tax based on configuration
                $d->type = 'Hotspot';
                $d->typebp = $typebp;
                $d->plan_type = $plan_type;
                $d->limit_type = $limit_type;
                $d->time_limit = $time_limit;
                $d->time_unit = $time_unit;
                $d->data_limit = $data_limit;
                $d->data_unit = $data_unit;
                $d->validity = $validity;
                $d->validity_unit = $validity_unit;
                $d->shared_users = $sharedusers;
                if (!empty($radius)) {
                    $d->is_radius = 1;
                    $d->routers = '';
                } else {
                    $d->is_radius = 0;
                    $d->routers = $routers;
                }
                $d->pool_expired = $pool_expired;
                $d->list_expired = $list_expired;
                $d->enabled = $enabled;
                $d->prepaid = $prepaid;
                $d->save();
                $plan_id = $d->id();
        
                if ($d['is_radius']) {
                    Radius::planUpSert($plan_id, $radiusRate);
                } else {
                    $mikrotik = Mikrotik::info($routers);
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::addHotspotPlan($client, $name, $sharedusers, $rate);
                    if (!empty($pool_expired)) {
                        Mikrotik::setHotspotExpiredPlan($client, 'EXPIRED NUXBILL ' . $pool_expired, $pool_expired);
                    }
                }
        
                r2(U . 'services/hotspot', 's', Lang::T('Data Created Successfully'));
            } else {
                r2(U . 'services/add', 'e', $msg);
            }
            break;



            case 'edit-post':
                $id = _post('id');
                $name = _post('name');
                $plan_type = _post('plan_type');
                $id_bw = _post('id_bw');
                $typebp = _post('typebp');
                $price = _post('price');
                $limit_type = _post('limit_type');
                $time_limit = _post('time_limit');
                $time_unit = _post('time_unit');
                $data_limit = _post('data_limit');
                $data_unit = _post('data_unit');
                $sharedusers = _post('sharedusers');
                $validity = _post('validity');
                $validity_unit = _post('validity_unit');
                $pool_expired = _post('pool_expired');
                $list_expired = _post('list_expired');
                $enabled = _post('enabled');
                $prepaid = _post('prepaid');
                $routers = _post('routers');
                $msg = '';
                if (Validator::UnsignedNumber($validity) == false) {
                    $msg .= 'The validity must be a number' . '<br>';
                }
                if (Validator::UnsignedNumber($price) == false) {
                    $msg .= 'The price must be a number' . '<br>';
                }
                if ($name == '' or $id_bw == '' or $price == '' or $validity == '') {
                    $msg .= Lang::T('All field is required') . '<br>';
                }
                $d = ORM::for_table('tbl_plans')->where('id', $id)->find_one();
                if ($d) {
                } else {
                    $msg .= Lang::T('Data Not Found') . '<br>';
                }
                run_hook('edit_plan'); #HOOK
                if ($msg == '') {
                    $b = ORM::for_table('tbl_bandwidth')->where('id', $id_bw)->find_one();
                    if ($b['rate_down_unit'] == 'Kbps') {
                        $unitdown = 'k';
                        $raddown = '000';
                    } else {
                        $unitdown = 'M';
                        $raddown = '000000';
                    }
                    if ($b['rate_up_unit'] == 'Kbps') {
                        $unitup = 'k';
                        $radup = '000';
                    } else {
                        $unitup = 'M';
                        $radup = '000000';
                    }
                    $rate = $b['rate_up'] . $unitup . "/" . $b['rate_down'] . $unitdown;
                    $radiusRate = $b['rate_up'] . $radup . '/' . $b['rate_down'] . $raddown;
            
                    // Check if all burst fields are entered
                    if (!empty($b['burst_limit_up']) && !empty($b['burst_limit_down']) && !empty($b['burst_threshold_up']) && !empty($b['burst_threshold_down']) && !empty($b['burst_time'])) {
                        // Burst Limit
                        if ($b['burst_limit_up_unit'] == 'Kbps') {
                            $burstlimitup = $b['burst_limit_up'] . 'k';
                        } else {
                            $burstlimitup = $b['burst_limit_up'] . 'M';
                        }
                        if ($b['burst_limit_down_unit'] == 'Kbps') {
                            $burstlimitdown = $b['burst_limit_down'] . 'k';
                        } else {
                            $burstlimitdown = $b['burst_limit_down'] . 'M';
                        }
                        $burstlimit = $burstlimitup . "/" . $burstlimitdown;
            
                        // Burst Threshold
                        if ($b['burst_threshold_up_unit'] == 'Kbps') {
                            $burstthresholdup = $b['burst_threshold_up'] . 'k';
                        } else {
                            $burstthresholdup = $b['burst_threshold_up'] . 'M';
                        }
                        if ($b['burst_threshold_down_unit'] == 'Kbps') {
                            $burstthresholddown = $b['burst_threshold_down'] . 'k';
                        } else {
                            $burstthresholddown = $b['burst_threshold_down'] . 'M';
                        }
                        $burstthreshold = $burstthresholdup . "/" . $burstthresholddown;
            
                        // Burst Time
                        $bursttime = $b['burst_time'];
            
                        // Priority
                        $priority = $b['priority'];
            
                        // Append burst parameters to the rate
                        $rate .= " " . $burstlimit . " " . $burstthreshold . " " . $bursttime . "/" . $bursttime;
                    }
            
                    if ($d['is_radius']) {
                        Radius::planUpSert($id, $radiusRate);
                    } else {
                        $mikrotik = Mikrotik::info($routers);
                        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        Mikrotik::setHotspotPlan($client, $name, $sharedusers, $rate);
                        if (!empty($pool_expired)) {
                            Mikrotik::setHotspotExpiredPlan($client, 'EXPIRED NUXBILL ' . $pool_expired, $pool_expired);
                        }
                    }
            
                    // Check if tax is enabled in config
                    $tax_enable = isset($config['enable_tax']) ? $config['enable_tax'] : 'no';
            
                    // Default tax rate
                    $default_tax_rate = 0.01; // Default tax rate 1%
            
                    // Check if tax rate is set to custom in config
                    $tax_rate_setting = isset($config['tax_rate']) ? $config['tax_rate'] : $default_tax_rate;
            
                    // Check if tax rate is custom
                    if ($tax_rate_setting === 'custom') {
                        // Check if custom tax rate is set in config
                        $custom_tax_rate = isset($config['custom_tax_rate']) ? (float)$config['custom_tax_rate'] : $default_tax_rate;
                        // Convert custom tax rate to decimal
                        $custom_tax_rate_decimal = $custom_tax_rate / 100;
                        $tax_rate = $custom_tax_rate_decimal;
                    } else {
                        // Use tax rate
                        $tax_rate = $tax_rate_setting;
                    }
            
                    // Calculate the new price with tax if tax is enabled
                    if ($tax_enable === 'yes') {
                        $price_with_tax = $price + ($price * $tax_rate);
                    } else {
                        // If tax is not enabled, use the original price
                        $price_with_tax = $price;
                    }
            
                    $d->name_plan = $name;
                    $d->id_bw = $id_bw;
                    $d->price = $price_with_tax; // Set price with or without tax based on configuration
                    $d->typebp = $typebp;
                    $d->limit_type = $limit_type;
                    $d->time_limit = $time_limit;
                    $d->time_unit = $time_unit;
                    $d->data_limit = $data_limit;
                    $d->plan_type = $plan_type;
                    $d->data_unit = $data_unit;
                    $d->validity = $validity;
                    $d->validity_unit = $validity_unit;
                    $d->shared_users = $sharedusers;
                    $d->pool_expired = $pool_expired;
                    $d->list_expired = $list_expired;
                    $d->enabled = $enabled;
                    $d->prepaid = $prepaid;
                    $d->save();
            
                    r2(U . 'services/hotspot', 's', Lang::T('Data Updated Successfully'));
                } else {
                    r2(U . 'services/edit/' . $id, 'e', $msg);
                }
                break;

    case 'pppoe':
        $ui->assign('_title', Lang::T('PPPOE Plans'));
        $ui->assign('xfooter', '<script type="text/javascript" src="ui/lib/c/pppoe.js"></script>');

        $name = _post('name');
        if ($name != '') {
            $query = ORM::for_table('tbl_bandwidth')->join('tbl_plans', array('tbl_bandwidth.id', '=', 'tbl_plans.id_bw'))->where('tbl_plans.type', 'PPPOE')->where_like('tbl_plans.name_plan', '%' . $name . '%');
            $d = Paginator::findMany($query, ['name' => $name]);
        } else {
            $query = ORM::for_table('tbl_bandwidth')->join('tbl_plans', array('tbl_bandwidth.id', '=', 'tbl_plans.id_bw'))->where('tbl_plans.type', 'PPPOE');
            $d = Paginator::findMany($query);
        }

        $ui->assign('d', $d);
        run_hook('view_list_ppoe'); #HOOK
        $ui->display('pppoe.tpl');
        break;

    case 'pppoe-add':
        $ui->assign('_title', Lang::T('PPPOE Plans'));
        $d = ORM::for_table('tbl_bandwidth')->find_many();
        $ui->assign('d', $d);
        $r = ORM::for_table('tbl_routers')->find_many();
        $ui->assign('r', $r);
        run_hook('view_add_ppoe'); #HOOK
        $ui->display('pppoe-add.tpl');
        break;

    case 'pppoe-edit':
        $ui->assign('_title', Lang::T('PPPOE Plans'));
        $id = $routes['2'];
        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            $ui->assign('d', $d);
            $p = ORM::for_table('tbl_pool')->where('routers', ($d['is_radius']) ? 'radius' : $d['routers'])->find_many();
            $ui->assign('p', $p);
            $b = ORM::for_table('tbl_bandwidth')->find_many();
            $ui->assign('b', $b);
            $r = [];
            if ($d['is_radius']) {
                $r = ORM::for_table('tbl_routers')->find_many();
            }
            $ui->assign('r', $r);
            run_hook('view_edit_ppoe'); #HOOK
            $ui->display('pppoe-edit.tpl');
        } else {
            r2(U . 'services/pppoe', 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'pppoe-delete':
        $id = $routes['2'];

        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            run_hook('delete_ppoe'); #HOOK
            if ($d['is_radius']) {
                Radius::planDelete($d['id']);
            } else {
                try {
                    $mikrotik = Mikrotik::info($d['routers']);
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::removePpoePlan($client, $d['name_plan']);
                } catch (Exception $e) {
                    //ignore exception, it means router has already deleted
                } catch (Throwable $e) {
                    //ignore exception, it means router has already deleted
                }
            }
            $d->delete();

            r2(U . 'services/pppoe', 's', Lang::T('Data Deleted Successfully'));
        }
        break;

        case 'pppoe-add-post':
            $name = _post('name_plan');
            $plan_type = _post('plan_type');
            $radius = _post('radius');
            $id_bw = _post('id_bw');
            $price = _post('price');
            $validity = _post('validity');
            $validity_unit = _post('validity_unit');
            $routers = _post('routers');
            $pool = _post('pool_name');
            $pool_expired = _post('pool_expired');
            $list_expired = _post('list_expired');
            $enabled = _post('enabled');
            $prepaid = _post('prepaid');
        
        
            $msg = '';
            if (Validator::UnsignedNumber($validity) == false) {
                $msg .= 'The validity must be a number' . '<br>';
            }
            if (Validator::UnsignedNumber($price) == false) {
                $msg .= 'The price must be a number' . '<br>';
            }
            if ($name == '' or $id_bw == '' or $price == '' or $validity == '' or $pool == '') {
                $msg .= Lang::T('All field is required') . '<br>';
            }
            if (empty($radius)) {
                if ($routers == '') {
                    $msg .= Lang::T('All field is required') . '<br>';
                }
            }
        
            $d = ORM::for_table('tbl_plans')->where('name_plan', $name)->find_one();
            if ($d) {
                $msg .= Lang::T('Name Plan Already Exist') . '<br>';
            }
            run_hook('add_ppoe'); #HOOK
            if ($msg == '') {
                $b = ORM::for_table('tbl_bandwidth')->where('id', $id_bw)->find_one();
                if ($b['rate_down_unit'] == 'Kbps') {
                    $unitdown = 'K';
                    $raddown = '000';
                } else {
                    $unitdown = 'M';
                    $raddown = '000000';
                }
                if ($b['rate_up_unit'] == 'Kbps') {
                    $unitup = 'K';
                    $radup = '000';
                } else {
                    $unitup = 'M';
                    $radup = '000000';
                }
                $rate = $b['rate_up'] . $unitup . "/" . $b['rate_down'] . $unitdown;
                $radiusRate = $b['rate_up'] . $radup . '/' . $b['rate_down'] . $raddown;
                
                // Check if all burst fields are entered
                if (!empty($b['burst_limit_up']) && !empty($b['burst_limit_down']) && !empty($b['burst_threshold_up']) && !empty($b['burst_threshold_down']) && !empty($b['burst_time'])) {
                    // Burst Limit
                    if ($b['burst_limit_up_unit'] == 'Kbps') {
                        $burstlimitup = $b['burst_limit_up'] . 'K';
                    } else {
                        $burstlimitup = $b['burst_limit_up'] . 'M';
                    }
                    if ($b['burst_limit_down_unit'] == 'Kbps') {
                        $burstlimitdown = $b['burst_limit_down'] . 'K';
                    } else {
                        $burstlimitdown = $b['burst_limit_down'] . 'M';
                    }
                    $burstlimit = $burstlimitup . "/" . $burstlimitdown;
                    
                    // Burst Threshold
                    if ($b['burst_threshold_up_unit'] == 'Kbps') {
                        $burstthresholdup = $b['burst_threshold_up'] . 'K';
                    } else {
                        $burstthresholdup = $b['burst_threshold_up'] . 'M';
                    }
                    if ($b['burst_threshold_down_unit'] == 'Kbps') {
                        $burstthresholddown = $b['burst_threshold_down'] . 'K';
                    } else {
                        $burstthresholddown = $b['burst_threshold_down'] . 'M';
                    }
                    $burstthreshold = $burstthresholdup . "/" . $burstthresholddown;
                    
                    // Burst Time
                    $bursttime = $b['burst_time'];
                    
                    // Priority
                    $priority = $b['priority'];
                    
                    // Append burst parameters to the rate
                    $rate .= "/" . $burstlimit . "/" . $burstthreshold . "/" . $bursttime . "/" . $priority;
                }
        
                // Check if tax is enabled in config
                $tax_enable = isset($config['enable_tax']) ? $config['enable_tax'] : 'no';
        
                // Default tax rate
                $default_tax_rate = 0.01; // Default tax rate 1%
        
                // Check if tax rate is set to custom in config
                $tax_rate_setting = isset($config['tax_rate']) ? $config['tax_rate'] : $default_tax_rate;
        
                // Check if tax rate is custom
                if ($tax_rate_setting === 'custom') {
                    // Check if custom tax rate is set in config
                    $custom_tax_rate = isset($config['custom_tax_rate']) ? (float)$config['custom_tax_rate'] : $default_tax_rate;
                    // Convert custom tax rate to decimal
                    $custom_tax_rate_decimal = $custom_tax_rate / 100;
                    $tax_rate = $custom_tax_rate_decimal;
                } else {
                    // Use tax rate
                    $tax_rate = $tax_rate_setting;
                }
        
        
                // Calculate the new price with tax if tax is enabled
                if ($tax_enable === 'yes') {
                    $price_with_tax = $price + ($price * $tax_rate);
                } else {
                    // If tax is not enabled, use the original price
                    $price_with_tax = $price;
                }
        
        
                $d = ORM::for_table('tbl_plans')->create();
                $d->type = 'PPPOE';
                $d->name_plan = $name;
                $d->id_bw = $id_bw;
                $d->price = $price_with_tax;
                $d->plan_type = $plan_type;
                $d->validity = $validity;
                $d->validity_unit = $validity_unit;
                $d->pool = $pool;
                if (!empty($radius)) {
                    $d->is_radius = 1;
                    $d->routers = '';
                } else {
                    $d->is_radius = 0;
                    $d->routers = $routers;
                }
                $d->pool_expired = $pool_expired;
                $d->list_expired = $list_expired;
                $d->enabled = $enabled;
                $d->prepaid = $prepaid;
                $d->save();
                $plan_id = $d->id();
        
                if ($d['is_radius']) {
                    Radius::planUpSert($plan_id, $radiusRate, $pool);
                } else {
                    $mikrotik = Mikrotik::info($routers);
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::addPpoePlan($client, $name, $pool, $rate);
                    if (!empty($pool_expired)) {
                        Mikrotik::setPpoePlan($client, 'EXPIRED NUXBILL ' . $pool_expired, $pool_expired, '512K/512K');
                    }
                }
        
                r2(U . 'services/pppoe', 's', Lang::T('Data Created Successfully'));
            } else {
                r2(U . 'services/pppoe-add', 'e', $msg);
            }
            break;

            case 'edit-pppoe-post':
                $id = _post('id');
                $plan_type = _post('plan_type');
                $name = _post('name_plan');
                $id_bw = _post('id_bw');
                $price = _post('price');
                $validity = _post('validity');
                $validity_unit = _post('validity_unit');
                $routers = _post('routers');
                $pool = _post('pool_name');
                $pool_expired = _post('pool_expired');
                $list_expired = _post('list_expired');
                $enabled = _post('enabled');
                $prepaid = _post('prepaid');
            
                $msg = '';
                if (Validator::UnsignedNumber($validity) == false) {
                    $msg .= 'The validity must be a number' . '<br>';
                }
                if (Validator::UnsignedNumber($price) == false) {
                    $msg .= 'The price must be a number' . '<br>';
                }
                if ($name == '' or $id_bw == '' or $price == '' or $validity == '' or $pool == '') {
                    $msg .= Lang::T('All field is required') . '<br>';
                }
            
                $d = ORM::for_table('tbl_plans')->where('id', $id)->find_one();
                if ($d) {
                } else {
                    $msg .= Lang::T('Data Not Found') . '<br>';
                }
                run_hook('edit_ppoe'); #HOOK
                if ($msg == '') {
                    $b = ORM::for_table('tbl_bandwidth')->where('id', $id_bw)->find_one();
                    if ($b['rate_down_unit'] == 'Kbps') {
                        $unitdown = 'K';
                        $raddown = '000';
                    } else {
                        $unitdown = 'M';
                        $raddown = '000000';
                    }
                    if ($b['rate_up_unit'] == 'Kbps') {
                        $unitup = 'K';
                        $radup = '000';
                    } else {
                        $unitup = 'M';
                        $radup = '000000';
                    }
                    $rate = $b['rate_up'] . $unitup . "/" . $b['rate_down'] . $unitdown;
                    $radiusRate = $b['rate_up'] . $radup . '/' . $b['rate_down'] . $raddown;
                    
                    // Check if all burst fields are entered
                    if (!empty($b['burst_limit_up']) && !empty($b['burst_limit_down']) && !empty($b['burst_threshold_up']) && !empty($b['burst_threshold_down']) && !empty($b['burst_time'])) {
                        // Burst Limit
                        if ($b['burst_limit_up_unit'] == 'Kbps') {
                            $burstlimitup = $b['burst_limit_up'] . 'K';
                        } else {
                            $burstlimitup = $b['burst_limit_up'] . 'M';
                        }
                        if ($b['burst_limit_down_unit'] == 'Kbps') {
                            $burstlimitdown = $b['burst_limit_down'] . 'K';
                        } else {
                            $burstlimitdown = $b['burst_limit_down'] . 'M';
                        }
                        $burstlimit = $burstlimitup . "/" . $burstlimitdown;
                        
                        // Burst Threshold
                        if ($b['burst_threshold_up_unit'] == 'Kbps') {
                            $burstthresholdup = $b['burst_threshold_up'] . 'K';
                        } else {
                            $burstthresholdup = $b['burst_threshold_up'] . 'M';
                        }
                        if ($b['burst_threshold_down_unit'] == 'Kbps') {
                            $burstthresholddown = $b['burst_threshold_down'] . 'K';
                        } else {
                            $burstthresholddown = $b['burst_threshold_down'] . 'M';
                        }
                        $burstthreshold = $burstthresholdup . "/" . $burstthresholddown;
                        
                        // Burst Time
                        $bursttime = $b['burst_time'];
                        
                        // Priority
                        $priority = $b['priority'];
                        
                        // Append burst parameters to the rate
                        $rate .= "/" . $burstlimit . "/" . $burstthreshold . "/" . $bursttime . "/" . $priority;
                    }
            
                    if ($d['is_radius']) {
                        Radius::planUpSert($id, $radiusRate, $pool);
                    } else {
                        $mikrotik = Mikrotik::info($routers);
                        $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                        Mikrotik::setPpoePlan($client, $name, $pool, $rate);
                        if (!empty($pool_expired)) {
                            Mikrotik::setPpoePlan($client, 'EXPIRED NUXBILL ' . $pool_expired, $pool_expired, '512K/512K');
                        }
                    }
            
                    // Check if tax is enabled in config
                    $tax_enable = isset($config['enable_tax']) ? $config['enable_tax'] : 'no';
            
                    // Default tax rate
                    $default_tax_rate = 0.01; // Default tax rate 1%
            
                    // Check if tax rate is set to custom in config
                    $tax_rate_setting = isset($config['tax_rate']) ? $config['tax_rate'] : $default_tax_rate;
            
                    // Check if tax rate is custom
                    if ($tax_rate_setting === 'custom') {
                        // Check if custom tax rate is set in config
                        $custom_tax_rate = isset($config['custom_tax_rate']) ? (float)$config['custom_tax_rate'] : $default_tax_rate;
                        // Convert custom tax rate to decimal
                        $custom_tax_rate_decimal = $custom_tax_rate / 100;
                        $tax_rate = $custom_tax_rate_decimal;
                    } else {
                        // Use tax rate
                        $tax_rate = $tax_rate_setting;
                    }
            
            
                    // Calculate the new price with tax if tax is enabled
                    if ($tax_enable === 'yes') {
                        $price_with_tax = $price + ($price * $tax_rate);
                    } else {
                        // If tax is not enabled, use the original price
                        $price_with_tax = $price;
                    }
            
                    $d->name_plan = $name;
                    $d->id_bw = $id_bw;
                    $d->price = $price_with_tax;
                    $d->plan_type = $plan_type;
                    $d->validity = $validity;
                    $d->validity_unit = $validity_unit;
                    $d->routers = $routers;
                    $d->pool = $pool;
                    $d->pool_expired = $pool_expired;
                    $d->list_expired = $list_expired;
                    $d->enabled = $enabled;
                    $d->prepaid = $prepaid;
                    $d->save();
            
                    r2(U . 'services/pppoe', 's', Lang::T('Data Updated Successfully'));
                } else {
                    r2(U . 'services/pppoe-edit/' . $id, 'e', $msg);
                }
                break;
    case 'balance':
        $ui->assign('_title', Lang::T('Balance Plans'));
        $name = _post('name');
        if ($name != '') {
            $query = ORM::for_table('tbl_plans')->where('tbl_plans.type', 'Balance')->where_like('tbl_plans.name_plan', '%' . $name . '%');
            $d = Paginator::findMany($query, ['name' => $name]);
        } else {
            $query = ORM::for_table('tbl_plans')->where('tbl_plans.type', 'Balance');
            $d = Paginator::findMany($query);
        }

        $ui->assign('d', $d);
        run_hook('view_list_balance'); #HOOK
        $ui->display('balance.tpl');
        break;
    case 'balance-add':
        $ui->assign('_title', Lang::T('Balance Plans'));
        run_hook('view_add_balance'); #HOOK
        $ui->display('balance-add.tpl');
        break;
    case 'balance-edit':
        $ui->assign('_title', Lang::T('Balance Plans'));
        $id = $routes['2'];
        $d = ORM::for_table('tbl_plans')->find_one($id);
        $ui->assign('d', $d);
        run_hook('view_edit_balance'); #HOOK
        $ui->display('balance-edit.tpl');
        break;
    case 'balance-delete':
        $id = $routes['2'];

        $d = ORM::for_table('tbl_plans')->find_one($id);
        if ($d) {
            run_hook('delete_balance'); #HOOK
            $d->delete();
            r2(U . 'services/balance', 's', Lang::T('Data Deleted Successfully'));
        }
        break;
    case 'balance-edit-post':
        $id = _post('id');
        $name = _post('name');
        $price = _post('price');
        $enabled = _post('enabled');
        $prepaid = _post('prepaid');

        $msg = '';
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }

        $d = ORM::for_table('tbl_plans')->where('id', $id)->find_one();
        if ($d) {
        } else {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }
        run_hook('edit_ppoe'); #HOOK
        if ($msg == '') {
            // Check if tax is enabled in config
            $tax_enable = isset($config['enable_tax']) ? $config['enable_tax'] : 'no';

            // Default tax rate
            $default_tax_rate = 0.01; // Default tax rate 1%

            // Check if tax rate is set to custom in config
            $tax_rate_setting = isset($config['tax_rate']) ? $config['tax_rate'] : $default_tax_rate;

            // Check if tax rate is custom
            if ($tax_rate_setting === 'custom') {
                // Check if custom tax rate is set in config
                $custom_tax_rate = isset($config['custom_tax_rate']) ? (float)$config['custom_tax_rate'] : $default_tax_rate;
                // Convert custom tax rate to decimal
                $custom_tax_rate_decimal = $custom_tax_rate / 100;
                $tax_rate = $custom_tax_rate_decimal;
            } else {
                // Use tax rate
                $tax_rate = $tax_rate_setting;
            }


            // Calculate the new price with tax if tax is enabled
            if ($tax_enable === 'yes') {
                $price_with_tax = $price + ($price * $tax_rate);
            } else {
                // If tax is not enabled, use the original price
                $price_with_tax = $price;
            }
            $d->name_plan = $name;
            $d->price = $price_with_tax;
            $d->enabled = $enabled;
            $d->prepaid = 'yes';
            $d->save();

            r2(U . 'services/balance', 's', Lang::T('Data Updated Successfully'));
        } else {
            r2(U . 'services/balance-edit/' . $id, 'e', $msg);
        }
        break;
    case 'balance-add-post':
        $name = _post('name');
        $price = _post('price');
        $enabled = _post('enabled');

        $msg = '';
        if (Validator::UnsignedNumber($price) == false) {
            $msg .= 'The price must be a number' . '<br>';
        }
        if ($name == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }

        $d = ORM::for_table('tbl_plans')->where('name_plan', $name)->find_one();
        if ($d) {
            $msg .= Lang::T('Name Plan Already Exist') . '<br>';
        }
        run_hook('add_ppoe'); #HOOK
        if ($msg == '') {

            // Check if tax is enabled in config
            $tax_enable = isset($config['enable_tax']) ? $config['enable_tax'] : 'no';

            // Default tax rate
            $default_tax_rate = 0.01; // Default tax rate 1%

            // Check if tax rate is set to custom in config
            $tax_rate_setting = isset($config['tax_rate']) ? $config['tax_rate'] : $default_tax_rate;

            // Check if tax rate is custom
            if ($tax_rate_setting === 'custom') {
                // Check if custom tax rate is set in config
                $custom_tax_rate = isset($config['custom_tax_rate']) ? (float)$config['custom_tax_rate'] : $default_tax_rate;
                // Convert custom tax rate to decimal
                $custom_tax_rate_decimal = $custom_tax_rate / 100;
                $tax_rate = $custom_tax_rate_decimal;
            } else {
                // Use tax rate
                $tax_rate = $tax_rate_setting;
            }


            // Calculate the new price with tax if tax is enabled
            if ($tax_enable === 'yes') {
                $price_with_tax = $price + ($price * $tax_rate);
            } else {
                // If tax is not enabled, use the original price
                $price_with_tax = $price;
            }


            $d = ORM::for_table('tbl_plans')->create();
            $d->type = 'Balance';
            $d->name_plan = $name;
            $d->id_bw = 0;
            $d->price = $price_with_tax;
            $d->validity = 0;
            $d->validity_unit = 'Months';
            $d->routers = '';
            $d->pool = '';
            $d->enabled = $enabled;
            $d->prepaid = 'yes';
            $d->save();

            r2(U . 'services/balance', 's', Lang::T('Data Created Successfully'));
        } else {
            r2(U . 'services/balance-add', 'e', $msg);
        }
        break;
    default:
        $ui->display('a404.tpl');
}
