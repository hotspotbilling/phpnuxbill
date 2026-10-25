<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_system_menu', 'paymentgateway');

$action = alphanumeric($routes[1], '_');
$ui->assign('_admin', $admin);
switch ($action) {
    case 'delete':
        $pg = alphanumeric($routes[2], '_');
        if (GatewayLoader::exists($pg)) {
            // goes through the installer so the gateway is backed up first and folder-style gateways are removed whole
            $res = ExtensionInstaller::uninstall('gateway', $pg);
            if (!$res['ok']) {
                r2(getUrl('paymentgateway'), 'e', $res['message']);
            }
        }
        r2(getUrl('paymentgateway'), 's', Lang::T('Payment Gateway Deleted'));

    case 'audit':
        $pg = alphanumeric($routes[2], '_');
        $q = alphanumeric(_req('q'), '-._ ');
        $query = ORM::for_table('tbl_payment_gateway')->order_by_desc("id");
        $query->selects('id', 'username', 'gateway', 'gateway_trx_id', 'plan_id', 'plan_name', 'routers_id', 'routers', 'price', 'pg_url_payment', 'payment_method', 'payment_channel', 'expired_date', 'created_date', 'paid_date', 'trx_invoice', 'status');
        $query->where('gateway', $pg);
        if (!empty($q)) {
            $query->whereRaw("(gateway_trx_id LIKE '%$q%' OR username LIKE '%$q%' OR routers LIKE '%$q%' OR plan_name LIKE '%$q%')");
            $append_url = 'q=' . urlencode($q);
        }
        $pgs = Paginator::findMany($query, ["search" => $search], 50, $append_url);

        $ui->assign('_title', 'Payment Gateway Audit');
        $ui->assign('pgs', $pgs);
        $ui->assign('pg', $pg);
        $ui->assign('q', $q);
        $ui->display('admin/paymentgateway/audit.tpl');
        break;
    case 'auditview':
        $pg = alphanumeric($routes[2], '_');
        $d = ORM::for_table('tbl_payment_gateway')->find_one($pg);
        $d['pg_request'] = (!empty($d['pg_request']))? Text::jsonArray21Array(json_decode($d['pg_request'], true)) : [];
        $d['pg_paid_response'] = (!empty($d['pg_paid_response']))? Text::jsonArray21Array(json_decode($d['pg_paid_response'], true)) : [];
        $ui->assign('_title', 'Payment Gateway Audit View');
        $ui->assign('pg', $d);
        $ui->display('admin/paymentgateway/audit-view.tpl');
        break;
    default:
        if (_post('save') == 'actives') {
            $pgs = '';
            if (is_array($_POST['pgs'])) {
                $pgs = implode(',', $_POST['pgs']);
            }
            $d = ORM::for_table('tbl_appconfig')->where('setting', 'payment_gateway')->find_one();
            if ($d) {
                $d->value = $pgs;
                $d->save();
            } else {
                $d = ORM::for_table('tbl_appconfig')->create();
                $d->setting = 'payment_gateway';
                $d->value = $pgs;
                $d->save();
            }
            r2(getUrl('paymentgateway'), 's', Lang::T('Payment Gateway saved successfully'));
        }

        if (GatewayLoader::file($action) !== null) {
            include GatewayLoader::file($action);
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (function_exists($action . '_save_config')) {
                    call_user_func($action . '_save_config');
                } else {
                    $ui->display('admin/404.tpl');
                }
            } else {
                if (function_exists($action . '_show_config')) {
                    call_user_func($action . '_show_config');
                } else {
                    $ui->display('admin/404.tpl');
                }
            }
        } else {
            if (!empty($action)) {
                r2(getUrl('paymentgateway'), 'w', Lang::T('Payment Gateway Not Found'));
            } else {
                $pgs = GatewayLoader::names();
                $ui->assign('_title', 'Payment Gateway Settings');
                $ui->assign('pgs', $pgs);
                $ui->assign('actives', explode(',', $config['payment_gateway']));
                $ui->display('admin/paymentgateway/list.tpl');
            }
        }
}


function deleteFile($path, $name)
{
    $files = scandir($path);
    foreach ($files as $file) {
        if (is_file($path . $file) && strpos($file, $name) !== false) {
            unlink($path . $file);
        } else if (is_dir($path . $file) && !in_array($file, ['.', '..'])) {
            deleteFile($path . $file . DIRECTORY_SEPARATOR, $name);
        }
    }
}
