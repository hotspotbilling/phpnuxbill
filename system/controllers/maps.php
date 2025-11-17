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

$ui->assign('xheader', '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css">');
$ui->assign('xfooter', '<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>');

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
        $ui->assign('_title', Lang::T('Customer Geo Location Information'));
        $ui->display('admin/maps/customers.tpl');
        break;
    case 'routers':
            $name = _post('name');
            $query = ORM::for_table('tbl_routers')->where_not_equal('coordinates', '')->order_by_desc('id');
            $query->selects(['id', 'name', 'coordinates', 'description', 'coverage', 'enabled']);
            if ($name != '') {
                $query->where_like('name', '%' . $name . '%');
            }
            $d = Paginator::findMany($query, ['name' => $name], '20', '', true);
            $ui->assign('name', $name);
            $ui->assign('d', $d);
            $ui->assign('_title', Lang::T('Routers Geo Location Information'));
            $ui->display('admin/maps/routers.tpl');
            break;
    /*   ODPs Geo Location  */
    /*  Added by ItsLiLxyzx  */
    case 'odp':
            $name = _post('name');
            $query = ORM::for_table('tbl_odps')->where_not_equal('coordinates', '')->order_by_desc('id');
            $query->selects(['id', 'name', 'port_amount', 'coordinates', 'address', 'attenuation', 'coverage']);
            if ($name != '') {
                $query->where_like('name', '%' . $name . '%');
            }
            $d = Paginator::findMany($query, ['name' => $name], '20', '', true);
            $ui->assign('name', $name);
            $ui->assign('d', $d);
            $ui->assign('_title', Lang::T('ODP Geo Location Information'));
            $ui->display('admin/maps/odps.tpl');
            break;
    default:
        r2(getUrl('map/customer'), 'e', 'action not defined');
        break;
}
