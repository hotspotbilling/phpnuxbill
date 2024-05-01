<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Recharge Account'));
$ui->assign('_system_menu', 'plan');

$action = $routes['1'];
$ui->assign('_admin', $admin);

$select2_customer = <<<EOT
<script>
document.addEventListener("DOMContentLoaded", function(event) {
    $('#personSelect').select2({
        theme: "bootstrap",
        ajax: {
            url: function(params) {
                if(params.term != undefined){
                    return './index.php?_route=autoload/customer_select2&s='+params.term;
                }else{
                    return './index.php?_route=autoload/customer_select2';
                }
            }
        }
    });
});
</script>
EOT;

switch ($action) {
    case 'sync':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        set_time_limit(-1);
        $plans = ORM::for_table('tbl_user_recharges')->where('status', 'on')->find_many();
        $log = '';
        $router = '';
        foreach ($plans as $plan) {
            if ($router != $plan['routers'] && $plan['routers'] != 'radius') {
                $mikrotik = Mikrotik::info($plan['routers']);
                $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                $router = $plan['routers'];
            }
            $p = ORM::for_table('tbl_plans')->findOne($plan['plan_id']);
            $c = ORM::for_table('tbl_customers')->findOne($plan['customer_id']);
            if ($plan['routers'] == 'radius') {
                Radius::customerAddPlan($c, $p, $plan['expiration'] . ' ' . $plan['time']);
            } else {
                if ($plan['type'] == 'Hotspot') {
                    Mikrotik::addHotspotUser($client, $p, $c);
                } else if ($plan['type'] == 'PPPOE') {
                    Mikrotik::addPpoeUser($client, $p, $c);
                }
            }
            $log .= "DONE : $plan[username], $plan[namebp], $plan[type], $plan[routers]<br>";
        }
        r2(U . 'plan/list', 's', $log);
    case 'recharge':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $ui->assign('xfooter', $select2_customer);
        if (isset($routes['2']) && !empty($routes['2'])) {
            $ui->assign('cust', ORM::for_table('tbl_customers')->find_one($routes['2']));
        }
        run_hook('view_recharge'); #HOOK
        $ui->display('recharge.tpl');
        break;

    case 'recharge-confirm':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $id_customer = _post('id_customer');
        $server = _post('server');
        $planId = _post('plan');
        $using = _post('using');

        $msg = '';
        if ($id_customer == '' or $server == '' or $planId == '' or $using == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }

        if ($msg == '') {
            $gateway = 'Recharge';
            $channel = $admin['fullname'];
            $cust = User::_info($id_customer);
            $plan = ORM::for_table('tbl_plans')->find_one($planId);
            list($bills, $add_cost) = User::getBills($id_customer);
            if ($using == 'balance' && $config['enable_balance'] == 'yes') {
                if (!$cust) {
                    r2(U . 'plan/recharge', 'e', Lang::T('Customer not found'));
                }
                if (!$plan) {
                    r2(U . 'plan/recharge', 'e', Lang::T('Plan not found'));
                }
                if ($cust['balance'] < ($plan['price'] + $add_cost)) {
                    r2(U . 'plan/recharge', 'e', Lang::T('insufficient balance'));
                }
                $gateway = 'Recharge Balance';
            }
            if ($using == 'zero') {
                $zero = 1;
                $gateway = 'Recharge Zero';
            }
            $ui->assign('bills', $bills);
            $ui->assign('add_cost', $add_cost);
            $ui->assign('cust', $cust);
            $ui->assign('gateway', $gateway);
            $ui->assign('channel', $channel);
            $ui->assign('server', $server);
            $ui->assign('using', $using);
            $ui->assign('plan', $plan);
            $ui->display('recharge-confirm.tpl');
        } else {
            r2(U . 'plan/recharge', 'e', $msg);
        }
        break;

    case 'recharge-post':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $id_customer = _post('id_customer');
        $server = _post('server');
        $planId = _post('plan');
        $using = _post('using');
        $stoken = _post('stoken');

        if (!empty(App::getTokenValue($stoken))) {
            $username = App::getTokenValue($stoken);
            $in = ORM::for_table('tbl_transactions')->where('username', $username)->order_by_desc('id')->find_one();
            Package::createInvoice($in);
            $ui->display('invoice.tpl');
            die();
        }

        $msg = '';
        if ($id_customer == '' or $server == '' or $planId == '' or $using == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }

        if ($msg == '') {
            $gateway = 'Recharge';
            $channel = $admin['fullname'];
            $cust = User::_info($id_customer);
            list($bills, $add_cost) = User::getBills($id_customer);
            if ($using == 'balance' && $config['enable_balance'] == 'yes') {
                $plan = ORM::for_table('tbl_plans')->find_one($planId);
                if (!$cust) {
                    r2(U . 'plan/recharge', 'e', Lang::T('Customer not found'));
                }
                if (!$plan) {
                    r2(U . 'plan/recharge', 'e', Lang::T('Plan not found'));
                }
                if ($cust['balance'] < ($plan['price'] + $add_cost)) {
                    r2(U . 'plan/recharge', 'e', Lang::T('insufficient balance'));
                }
                $gateway = 'Recharge Balance';
            }
            if ($using == 'zero') {
                $add_cost = 0;
                $zero = 1;
                $gateway = 'Recharge Zero';
            }
            if (Package::rechargeUser($id_customer, $server, $planId, $gateway, $channel)) {
                if ($using == 'balance') {
                    Balance::min($cust['id'], $plan['price'] + $add_cost);
                }
                $in = ORM::for_table('tbl_transactions')->where('username', $cust['username'])->order_by_desc('id')->find_one();
                Package::createInvoice($in);
                App::setToken($stoken, $cust['username']);
                $ui->display('invoice.tpl');
                _log('[' . $admin['username'] . ']: ' . 'Recharge ' . $cust['username'] . ' [' . $in['plan_name'] . '][' . Lang::moneyFormat($in['price']) . ']', $admin['user_type'], $admin['id']);
            } else {
                r2(U . 'plan/recharge', 'e', "Failed to recharge account");
            }
        } else {
            r2(U . 'plan/recharge', 'e', $msg);
        }
        break;

    case 'view':
        $id = $routes['2'];
        $in = ORM::for_table('tbl_transactions')->where('id', $id)->find_one();
        $ui->assign('in', $in);
        if (!empty($routes['3']) && $routes['3'] == 'send') {
            $c = ORM::for_table('tbl_customers')->where('username', $in['username'])->find_one();
            if ($c) {
                Message::sendInvoice($c, $in);
                r2(U . 'plan/view/' . $id, 's', "Success send to customer");
            }
            r2(U . 'plan/view/' . $id, 'd', "Customer not found");
        }
        Package::createInvoice($in);
        $ui->assign('_title', 'View Invoice');
        $ui->display('invoice.tpl');
        break;


    case 'print':
        $content = $_POST['content'];
        if (!empty($content)) {
            if ($_POST['nux'] == 'print') {
                //header("Location: nux://print?text=".urlencode($content));
                $ui->assign('nuxprint', "nux://print?text=" . urlencode($content));
            }
            $ui->assign('content', $content);
        } else {
            $id = _post('id');
            $d = ORM::for_table('tbl_transactions')->where('id', $id)->find_one();
            $ui->assign('in', $d);
            $ui->assign('date', Lang::dateAndTimeFormat($d['recharged_on'], $d['recharged_time']));
        }

        run_hook('print_invoice'); #HOOK
        $ui->display('invoice-print.tpl');
        break;

    case 'edit':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $id  = $routes['2'];
        $d = ORM::for_table('tbl_user_recharges')->find_one($id);
        if ($d) {
            $ui->assign('d', $d);
            if (in_array($admin['user_type'], array('SuperAdmin', 'Admin'))) {
                $p = ORM::for_table('tbl_plans')->where_not_equal('type', 'Balance')->find_many();
            } else {
                $p = ORM::for_table('tbl_plans')->where('enabled', '1')->where_not_equal('type', 'Balance')->find_many();
            }
            $ui->assign('p', $p);
            run_hook('view_edit_customer_plan'); #HOOK
            $ui->assign('_title', 'Edit Plan');
            $ui->display('plan-edit.tpl');
        } else {
            r2(U . 'plan/list', 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'delete':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $id  = $routes['2'];
        $d = ORM::for_table('tbl_user_recharges')->find_one($id);
        if ($d) {
            run_hook('delete_customer_active_plan'); #HOOK
            $p = ORM::for_table('tbl_plans')->find_one($d['plan_id']);
            if ($p['is_radius']) {
                Radius::customerDeactivate($d['username']);
            } else {
                $mikrotik = Mikrotik::info($d['routers']);
                if ($d['type'] == 'Hotspot') {
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::removeHotspotUser($client, $d['username']);
                    Mikrotik::removeHotspotActiveUser($client, $d['username']);
                } else {
                    $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                    Mikrotik::removePpoeUser($client, $d['username']);
                    Mikrotik::removePpoeActive($client, $d['username']);
                }
            }
            $d->delete();
            _log('[' . $admin['username'] . ']: ' . 'Delete Plan for Customer ' . $c['username'] . '  [' . $in['plan_name'] . '][' . Lang::moneyFormat($in['price']) . ']', $admin['user_type'], $admin['id']);
            r2(U . 'plan/list', 's', Lang::T('Data Deleted Successfully'));
        }
        break;

    case 'edit-post':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $username = _post('username');
        $id_plan = _post('id_plan');
        $recharged_on = _post('recharged_on');
        $expiration = _post('expiration');
        $time = _post('time');

        $id = _post('id');
        $d = ORM::for_table('tbl_user_recharges')->find_one($id);
        if ($d) {
        } else {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }
        $p = ORM::for_table('tbl_plans')->where('id', $id_plan)->where('enabled', '1')->find_one();
        if ($d) {
        } else {
            $msg .= ' Plan Not Found<br>';
        }
        if ($msg == '') {
            run_hook('edit_customer_plan'); #HOOK
            $d->username = $username;
            $d->plan_id = $id_plan;
            $d->namebp = $p['name_plan'];
            //$d->recharged_on = $recharged_on;
            $d->expiration = $expiration;
            $d->time = $time;
            if ($d['status'] == 'off') {
                if (strtotime($expiration . ' ' . $time) > time()) {
                    $d->status = 'on';
                }
            }
            if ($p['is_radius']) {
                $d->routers = 'radius';
            } else {
                $d->routers = $p['routers'];
            }
            $d->save();
            if ($d['status'] == 'on') {
                Package::changeTo($username, $id_plan, $id);
            }
            _log('[' . $admin['username'] . ']: ' . 'Edit Plan for Customer ' . $d['username'] . ' to [' . $d['namebp'] . '][' . Lang::moneyFormat($p['price']) . ']', $admin['user_type'], $admin['id']);
            r2(U . 'plan/list', 's', Lang::T('Data Updated Successfully'));
        } else {
            r2(U . 'plan/edit/' . $id, 'e', $msg);
        }
        break;

    case 'voucher':
        $ui->assign('_title', Lang::T('Vouchers'));
        $search = _req('search');
        if ($search != '') {
            if (in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
                $query = ORM::for_table('tbl_plans')->where('enabled', '1')
                    ->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                    ->where_like('tbl_voucher.code', '%' . $search . '%');
                $d = Paginator::findMany($query, ["search" => $search]);
            } else if ($admin['user_type'] == 'Agent') {
                $sales = [];
                $sls = ORM::for_table('tbl_users')->select('id')->where('root', $admin['id'])->findArray();
                foreach ($sls as $s) {
                    $sales[] = $s['id'];
                }
                $sales[] = $admin['id'];
                $query = ORM::for_table('tbl_plans')
                    ->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                    ->where_in('generated_by', $sales)
                    ->where_like('tbl_voucher.code', '%' . $search . '%');
                $d = Paginator::findMany($query, ["search" => $search]);
            }
        } else {
            if (in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
                $query = ORM::for_table('tbl_plans')->where('enabled', '1')
                    ->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'));
                $d = Paginator::findMany($query);
            } else if ($admin['user_type'] == 'Agent') {
                $sales = [];
                $sls = ORM::for_table('tbl_users')->select('id')->where('root', $admin['id'])->findArray();
                foreach ($sls as $s) {
                    $sales[] = $s['id'];
                }
                $sales[] = $admin['id'];
                $query = ORM::for_table('tbl_plans')
                    ->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                    ->where_in('generated_by', $sales);
                $d = Paginator::findMany($query);
            }
        }
        // extract admin
        $admins = [];
        foreach ($d as $k) {
            if (!empty($k['generated_by'])) {
                $admins[] = $k['generated_by'];
            }
        }
        if (count($admins) > 0) {
            $adms = ORM::for_table('tbl_users')->where_in('id', $admins)->find_many();
            unset($admins);
            foreach ($adms as $adm) {
                $tipe = $adm['user_type'];
                if ($tipe == 'Sales') {
                    $tipe = ' [S]';
                } else if ($tipe == 'Agent') {
                    $tipe = ' [A]';
                } else {
                    $tipe == '';
                }
                $admins[$adm['id']] = $adm['fullname'] . $tipe;
            }
        }
        $ui->assign('admins', $admins);
        $ui->assign('d', $d);
        $ui->assign('search', $search);
        $ui->assign('page', $page);
        run_hook('view_list_voucher'); #HOOK
        $ui->display('voucher.tpl');
        break;

    case 'add-voucher':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $ui->assign('_title', Lang::T('Add Vouchers'));
        $c = ORM::for_table('tbl_customers')->find_many();
        $ui->assign('c', $c);
        $p = ORM::for_table('tbl_plans')->where('enabled', '1')->find_many();
        $ui->assign('p', $p);
        $r = ORM::for_table('tbl_routers')->where('enabled', '1')->find_many();
        $ui->assign('r', $r);
        run_hook('view_add_voucher'); #HOOK
        $ui->display('voucher-add.tpl');
        break;

    case 'remove-voucher':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $d = ORM::for_table('tbl_voucher')->where_equal('status', '1')->findMany();
        if ($d) {
            $jml = 0;
            foreach ($d as $v) {
                if (!ORM::for_table('tbl_user_recharges')->where_equal("method", 'Voucher - ' . $v['code'])->findOne()) {
                    $v->delete();
                    $jml++;
                }
            }
            r2(U . 'plan/voucher', 's', "$jml " . Lang::T('Data Deleted Successfully'));
        }
    case 'print-voucher':
        $from_id = _post('from_id');
        $planid = _post('planid');
        $pagebreak = _post('pagebreak');
        $limit = _post('limit');
        $vpl = _post('vpl');
        if (empty($vpl)) {
            $vpl = 3;
        }
        if ($pagebreak < 1) $pagebreak = 12;

        if ($limit < 1) $limit = $pagebreak * 2;
        if (empty($from_id)) {
            $from_id = 0;
        }

        if ($from_id > 0 && $planid > 0) {
            $v = ORM::for_table('tbl_plans')
                ->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0')
                ->where('tbl_plans.id', $planid)
                ->where_gt('tbl_voucher.id', $from_id)
                ->limit($limit);
            $vc = ORM::for_table('tbl_plans')
                ->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0')
                ->where('tbl_plans.id', $planid)
                ->where_gt('tbl_voucher.id', $from_id);
        } else if ($from_id == 0 && $planid > 0) {
            $v = ORM::for_table('tbl_plans')
                ->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0')
                ->where('tbl_plans.id', $planid)
                ->limit($limit);
            $vc = ORM::for_table('tbl_plans')
                ->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0')
                ->where('tbl_plans.id', $planid);
        } else if ($from_id > 0 && $planid == 0) {
            $v = ORM::for_table('tbl_plans')
                ->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0')
                ->where_gt('tbl_voucher.id', $from_id)
                ->limit($limit);
            $vc = ORM::for_table('tbl_plans')
                ->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0')
                ->where_gt('tbl_voucher.id', $from_id);
        } else {
            $v = ORM::for_table('tbl_plans')
                ->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0')
                ->limit($limit);
            $vc = ORM::for_table('tbl_plans')
                ->join('tbl_voucher', array('tbl_plans.id', '=', 'tbl_voucher.id_plan'))
                ->where('tbl_voucher.status', '0');
        }
        if (in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            $v = $v->find_many();
            $vc = $vc->count();
        } else {
            $sales = [];
            $sls = ORM::for_table('tbl_users')->select('id')->where('root', $admin['id'])->findArray();
            foreach ($sls as $s) {
                $sales[] = $s['id'];
            }
            $sales[] = $admin['id'];
            $v = $v->where_in('generated_by', $sales)->find_many();
            $vc = $vc->where_in('generated_by', $sales)->count();
        }
        $template = file_get_contents("pages/Voucher.html");
        $template = str_replace('[[company_name]]', $config['CompanyName'], $template);

        $ui->assign('_title', Lang::T('Hotspot Voucher'));
        $ui->assign('from_id', $from_id);
        $ui->assign('vpl', $vpl);
        $ui->assign('pagebreak', $pagebreak);

        $plans = ORM::for_table('tbl_plans')->find_many();
        $ui->assign('plans', $plans);
        $ui->assign('limit', $limit);
        $ui->assign('planid', $planid);

        $voucher = [];
        $n = 1;
        foreach ($v as $vs) {
            $temp = $template;
            $temp = str_replace('[[qrcode]]', '<img src="qrcode/?data=' . $vs['code'] . '">', $temp);
            $temp = str_replace('[[price]]', Lang::moneyFormat($vs['price']), $temp);
            $temp = str_replace('[[voucher_code]]', $vs['code'], $temp);
            $temp = str_replace('[[plan]]', $vs['name_plan'], $temp);
            $temp = str_replace('[[counter]]', $n, $temp);
            $voucher[] = $temp;
            $n++;
        }

        $ui->assign('voucher', $voucher);
        $ui->assign('vc', $vc);

        //for counting pagebreak
        $ui->assign('jml', 0);
        run_hook('view_print_voucher'); #HOOK
        $ui->display('print-voucher.tpl');
        break;
    case 'voucher-post':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $type = _post('type');
        $plan = _post('plan');
        $voucher_format = _post('voucher_format');
        $prefix = _post('prefix');
        $server = _post('server');
        $numbervoucher = _post('numbervoucher');
        $lengthcode = _post('lengthcode');

        $msg = '';
        if ($type == '' or $plan == '' or $server == '' or $numbervoucher == '' or $lengthcode == '') {
            $msg .= Lang::T('All field is required') . '<br>';
        }
        if (Validator::UnsignedNumber($numbervoucher) == false) {
            $msg .= 'The Number of Vouchers must be a number' . '<br>';
        }
        if (Validator::UnsignedNumber($lengthcode) == false) {
            $msg .= 'The Length Code must be a number' . '<br>';
        }
        if ($msg == '') {
            if (!empty($prefix)) {
                $d = ORM::for_table('tbl_appconfig')->where('setting', 'voucher_prefix')->find_one();
                if ($d) {
                    $d->value = $prefix;
                    $d->save();
                } else {
                    $d = ORM::for_table('tbl_appconfig')->create();
                    $d->setting = 'voucher_prefix';
                    $d->value = $prefix;
                    $d->save();
                }
            }
            run_hook('create_voucher'); #HOOK
            for ($i = 0; $i < $numbervoucher; $i++) {
                $code = strtoupper(substr(md5(time() . rand(10000, 99999)), 0, $lengthcode));
                if ($voucher_format == 'low') {
                    $code = strtolower($code);
                } else if ($voucher_format == 'rand') {
                    $code = Lang::randomUpLowCase($code);
                }
                $d = ORM::for_table('tbl_voucher')->create();
                $d->type = $type;
                $d->routers = $server;
                $d->id_plan = $plan;
                $d->code = $prefix . $code;
                $d->user = '0';
                $d->status = '0';
                $d->generated_by = $admin['id'];
                $d->save();
            }
            if ($numbervoucher == 1) {
                r2(U . 'plan/voucher-view/' . $d->id(), 's', Lang::T('Create Vouchers Successfully'));
            }

            r2(U . 'plan/voucher', 's', Lang::T('Create Vouchers Successfully'));
        } else {
            r2(U . 'plan/add-voucher/' . $id, 'e', $msg);
        }
        break;

    case 'voucher-view':
        $id = $routes[2];
        if (in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            $voucher = ORM::for_table('tbl_voucher')->find_one($id);
        } else {
            $sales = [];
            $sls = ORM::for_table('tbl_users')->select('id')->where('root', $admin['id'])->findArray();
            foreach ($sls as $s) {
                $sales[] = $s['id'];
            }
            $sales[] = $admin['id'];
            $voucher = ORM::for_table('tbl_voucher')
                ->find_one($id);
            if (!in_array($voucher['generated_by'], $sales)) {
                r2(U . 'plan/voucher/', 'e', Lang::T('Voucher Not Found'));
            }
        }
        if (!$voucher) {
            r2(U . 'plan/voucher/', 'e', Lang::T('Voucher Not Found'));
        }
        $plan = ORM::for_table('tbl_plans')->find_one($voucher['id_plan']);
        if ($voucher && $plan) {
            $content = Lang::pad($config['CompanyName'], ' ', 2) . "\n";
            $content .= Lang::pad($config['address'], ' ', 2) . "\n";
            $content .= Lang::pad($config['phone'], ' ', 2) . "\n";
            $content .= Lang::pad("", '=') . "\n";
            $content .= Lang::pads('ID', $voucher['id'], ' ') . "\n";
            $content .= Lang::pads(Lang::T('Code'), $voucher['code'], ' ') . "\n";
            $content .= Lang::pads(Lang::T('Plan Name'), $plan['name_plan'], ' ') . "\n";
            $content .= Lang::pads(Lang::T('Type'), $voucher['type'], ' ') . "\n";
            $content .= Lang::pads(Lang::T('Plan Price'), Lang::moneyFormat($plan['price']), ' ') . "\n";
            $content .= Lang::pads(Lang::T('Sales'), $admin['fullname'] . ' #' . $admin['id'], ' ') . "\n";
            $content .= Lang::pad("", '=') . "\n";
            $content .= Lang::pad($config['note'], ' ', 2) . "\n";
            $ui->assign('print', $content);
            $config['printer_cols'] = 30;
            $content = Lang::pad($config['CompanyName'], ' ', 2) . "\n";
            $content .= Lang::pad($config['address'], ' ', 2) . "\n";
            $content .= Lang::pad($config['phone'], ' ', 2) . "\n";
            $content .= Lang::pad("", '=') . "\n";
            $content .= Lang::pads('ID', $voucher['id'], ' ') . "\n";
            $content .= Lang::pads(Lang::T('Code'), $voucher['code'], ' ') . "\n";
            $content .= Lang::pads(Lang::T('Plan Name'), $plan['name_plan'], ' ') . "\n";
            $content .= Lang::pads(Lang::T('Type'), $voucher['type'], ' ') . "\n";
            $content .= Lang::pads(Lang::T('Plan Price'), Lang::moneyFormat($plan['price']), ' ') . "\n";
            $content .= Lang::pads(Lang::T('Sales'), $admin['fullname'] . ' #' . $admin['id'], ' ') . "\n";
            $content .= Lang::pad("", '=') . "\n";
            $content .= Lang::pad($config['note'], ' ', 2) . "\n";
            $ui->assign('_title', Lang::T('View'));
            $ui->assign('whatsapp', urlencode("```$content```"));
            $ui->display('voucher-view.tpl');
        } else {
            r2(U . 'plan/voucher/', 'e', Lang::T('Voucher Not Found'));
        }
        break;
    case 'voucher-delete':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $id  = $routes['2'];
        run_hook('delete_voucher'); #HOOK
        $d = ORM::for_table('tbl_voucher')->find_one($id);
        if ($d) {
            $d->delete();
            r2(U . 'plan/voucher', 's', Lang::T('Data Deleted Successfully'));
        }
        break;

    case 'refill':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $ui->assign('xfooter', $select2_customer);
        $ui->assign('_title', Lang::T('Refill Account'));
        run_hook('view_refill'); #HOOK
        $ui->display('refill.tpl');

        break;

    case 'refill-post':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $code = _post('code');
        $user = ORM::for_table('tbl_customers')->where('id', _post('id_customer'))->find_one();
        $v1 = ORM::for_table('tbl_voucher')->where('code', $code)->where('status', 0)->find_one();

        run_hook('refill_customer'); #HOOK
        if ($v1) {
            if (Package::rechargeUser($user['id'], $v1['routers'], $v1['id_plan'], "Voucher", $code)) {
                $v1->status = "1";
                $v1->user = $user['username'];
                $v1->save();
                $in = ORM::for_table('tbl_transactions')->where('username', $user['username'])->order_by_desc('id')->find_one();
                Package::createInvoice($in);
                $ui->display('invoice.tpl');
            } else {
                r2(U . 'plan/refill', 'e', "Failed to refill account");
            }
        } else {
            r2(U . 'plan/refill', 'e', Lang::T('Voucher Not Valid'));
        }
        break;
    case 'deposit':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $ui->assign('_title', Lang::T('Refill Balance'));
        $ui->assign('xfooter', $select2_customer);
        if (in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            $ui->assign('p', ORM::for_table('tbl_plans')->where('type', 'Balance')->find_many());
        } else {
            $ui->assign('p', ORM::for_table('tbl_plans')->where('enabled', '1')->where('type', 'Balance')->find_many());
        }
        run_hook('view_deposit'); #HOOK
        $ui->display('deposit.tpl');
        break;
    case 'deposit-post':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $user = _post('id_customer');
        $plan = _post('id_plan');
        $stoken = _req('stoken');
        if (App::getTokenValue($stoken)) {
            $c = ORM::for_table('tbl_customers')->where('id', $user)->find_one();
            $in = ORM::for_table('tbl_transactions')->where('username', $c['username'])->order_by_desc('id')->find_one();
            Package::createInvoice($in);
            $ui->display('invoice.tpl');
            die();
        }

        run_hook('deposit_customer'); #HOOK
        if (!empty($user) && !empty($plan)) {
            if (Package::rechargeUser($user, 'balance', $plan, "Deposit", $admin['fullname'])) {
                $c = ORM::for_table('tbl_customers')->where('id', $user)->find_one();
                $in = ORM::for_table('tbl_transactions')->where('username', $c['username'])->order_by_desc('id')->find_one();
                Package::createInvoice($in);
                if(!empty($stoken)){
                    App::setToken($stoken, $in['id']);
                }
                $ui->display('invoice.tpl');
            } else {
                r2(U . 'plan/refill', 'e', "Failed to refill account");
            }
        } else {
            r2(U . 'plan/refill', 'e', "All field is required");
        }
        break;
    case 'extend':
        $id = $routes[2];
        $days = $routes[3];
        $stoken = $_GET['stoken'];
        if (App::getTokenValue($stoken)) {
            r2(U . 'plan', 's', "Extend already done");
        }
        $tur = ORM::for_table('tbl_user_recharges')->find_one($id);
        $status = $tur['status'];
        if ($status == 'off') {
            if (strtotime($tur['expiration'] . ' ' . $tur['time']) > time()) {
                // not expired
                $expiration = date('Y-m-d', strtotime($tur['expiration'] . " +$days day"));
            } else {
                //expired
                $expiration = date('Y-m-d', strtotime(" +$days day"));
            }
            $tur->expiration = $expiration;
            $tur->status = "on";
            $tur->save();
            App::setToken($stoken, $id);
            if ($tur['routers'] != 'radius') {
                $mikrotik = Mikrotik::info($tur['routers']);
                $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
                $router = $tur['routers'];
            }
            $p = ORM::for_table('tbl_plans')->findOne($tur['plan_id']);
            $c = ORM::for_table('tbl_customers')->findOne($tur['customer_id']);
            if ($tur['routers'] == 'radius') {
                Radius::customerAddPlan($c, $p, $tur['expiration'] . ' ' . $tur['time']);
            } else {
                if ($tur['type'] == 'Hotspot') {
                    Mikrotik::addHotspotUser($client, $p, $c);
                } else if ($tur['type'] == 'PPPOE') {
                    Mikrotik::addPpoeUser($client, $p, $c);
                }
            }
            _log("$admin[fullname] extend Customer $tur[customer_id] $tur[username] for $days days", $admin['user_type'], $admin['id']);
            r2(U . 'plan', 's', "Extend until $expiration");
        }else{
            r2(U . 'plan', 's', "Customer is not expired yet");
        }
        break;
    default:
        $ui->assign('xfooter', '<script type="text/javascript" src="ui/lib/c/plan.js"></script>');
        $ui->assign('_title', Lang::T('Customer'));
        $search = _post('search');
        if ($search != '') {
            $query = ORM::for_table('tbl_user_recharges')->where_like('username', '%' . $search . '%')->order_by_desc('id');
            $d = Paginator::findMany($query, ['search' => $search]);
        } else {
            $query = ORM::for_table('tbl_user_recharges')->order_by_desc('id');
            $d = Paginator::findMany($query);
        }
        run_hook('view_list_billing'); #HOOK
        $ui->assign('d', $d);
        $ui->assign('search', $search);
        $ui->display('plan.tpl');
        break;
}
