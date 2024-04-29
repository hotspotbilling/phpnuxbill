<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 * by https://t.me/ibnux
 **/

_admin();
$ui->assign('_system_menu', 'map');

$action = $routes['1'];
$ui->assign('_admin', $admin);

if (empty($action)) {
    $action = 'customer';
}

switch ($action) {
    case 'customer':
        if(!empty(_req('search'))){
            $search = _req('search');
            $query = ORM::for_table('tbl_customers')->whereRaw("coordinates != '' AND fullname LIKE '%$search%' OR username LIKE '%$search%' OR email LIKE '%$search%' OR phonenumber LIKE '%$search%'")->order_by_desc('fullname');
            $c = Paginator::findMany($query, ['search' => $search], 50);
        }else{
            $query = ORM::for_table('tbl_customers')->where_not_equal('coordinates','');
            $c = Paginator::findMany($query, ['search'=>''], 50);
        }
        $customerData = [];

        foreach ($c as $customer) {
            if (!empty($customer->coordinates)) {
                $customerData[] = [
                    'id' => $customer->id,
                    'name' => $customer->fullname,
                    'balance' => $customer->balance,
                    'address' => $customer->address,
                    'direction' => $customer->coordinates,
                    'info' => Lang::T("Username") . ": " . $customer->username .  " - "  . Lang::T("Full Name") . ": " . $customer->fullname . " - "  . Lang::T("Email") . ": " . $customer->email . " - "  . Lang::T("Phone") . ": " . $customer->phonenumber . " - "  . Lang::T("Service Type") . ": " . $customer->service_type,
                    'coordinates' => '[' . $customer->coordinates . ']',
                ];
            }
        }
        $ui->assign('search', $search);
        $ui->assign('customers', $customerData);
        $ui->assign('xheader', '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css">');
        $ui->assign('_title', Lang::T('Customer Geo Location Information'));
        $ui->assign('xfooter', '<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>');
        $ui->display('customers-map.tpl');
        break;

    default:
        r2(U . 'map/customer', 'e', 'action not defined');
        break;
}
