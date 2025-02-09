<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Send Message'));
$ui->assign('_system_menu', 'message');

$action = $routes['1'];
$ui->assign('_admin', $admin);

if (empty($action)) {
    $action = 'send';
}

switch ($action) {
    case 'send':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }

        $appUrl = APP_URL;

        $select2_customer = <<<EOT
<script>
document.addEventListener("DOMContentLoaded", function(event) {
    $('#personSelect').select2({
        theme: "bootstrap",
        ajax: {
            url: function(params) {
                if(params.term != undefined){
                    return '{$appUrl}/?_route=autoload/customer_select2&s='+params.term;
                }else{
                    return '{$appUrl}/?_route=autoload/customer_select2';
                }
            }
        }
    });
});
</script>
EOT;
        if (isset($routes['2']) && !empty($routes['2'])) {
            $ui->assign('cust', ORM::for_table('tbl_customers')->find_one($routes['2']));
        }
        $id = $routes['2'];
        $ui->assign('id', $id);
        $ui->assign('xfooter', $select2_customer);
        $ui->display('admin/message/single.tpl');
        break;

    case 'send-post':
        // Check user permissions
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }

        // Get form data
        $id_customer = $_POST['id_customer'];
        $message = $_POST['message'];
        $via = $_POST['via'];

        // Check if fields are empty
        if ($id_customer == '' or $message == '' or $via == '') {
            r2(getUrl('message/send'), 'e', Lang::T('All field is required'));
        } else {
            // Get customer details from the database
            $c = ORM::for_table('tbl_customers')->find_one($id_customer);

            // Replace placeholders in the message with actual values
            $message = str_replace('[[name]]', $c['fullname'], $message);
            $message = str_replace('[[user_name]]', $c['username'], $message);
            $message = str_replace('[[phone]]', $c['phonenumber'], $message);
            $message = str_replace('[[company_name]]', $config['CompanyName'], $message);
            if (strpos($message, '[[payment_link]]') !== false) {
                // token only valid for 1 day, for security reason
                $token = User::generateToken($c['id'], 1);
                if (!empty($token['token'])) {
                    $tur = ORM::for_table('tbl_user_recharges')
                        ->where('customer_id', $c['id'])
                        //->where('namebp', $package)
                        ->find_one();
                    if ($tur) {
                        $url = '?_route=home&recharge=' . $tur['id'] . '&uid=' . urlencode($token['token']);
                        $message = str_replace('[[payment_link]]', $url, $message);
                    }
                } else {
                    $message = str_replace('[[payment_link]]', '', $message);
                }
            }


            //Send the message
            if ($via == 'sms' || $via == 'both') {
                $smsSent = Message::sendSMS($c['phonenumber'], $message);
            }

            if ($via == 'wa' || $via == 'both') {
                $waSent = Message::sendWhatsapp($c['phonenumber'], $message);
            }

            if (isset($smsSent) || isset($waSent)) {
                r2(getUrl('message/send'), 's', Lang::T('Message Sent Successfully'));
            } else {
                r2(getUrl('message/send'), 'e', Lang::T('Failed to send message'));
            }
        }
        break;

    case 'send_bulk':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }

        $ui->assign('routers', ORM::forTable('tbl_routers')->where('enabled', '1')->find_many());
        $ui->display('admin/message/bulk.tpl');
        break;

    case 'send_bulk_ajax':
        // Check user permissions
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            die(json_encode(['status' => 'error', 'message' => 'Permission denied']));
        }

        set_time_limit(0);

        // Get request parameters
        $group = $_REQUEST['group'] ?? '';
        $message = $_REQUEST['message'] ?? '';
        $via = $_REQUEST['via'] ?? '';
        $batch = $_REQUEST['batch'] ?? 100;
        $page = $_REQUEST['page'] ?? 0;
        $router = $_REQUEST['router'] ?? null;
        $test = isset($_REQUEST['test']) && $_REQUEST['test'] === 'on' ? true : false;

        if (empty($group) || empty($message) || empty($via)) {
            die(json_encode(['status' => 'error', 'message' => 'All fields are required']));
        }

        // Get batch of customers based on group
        $startpoint = $page * $batch;
        $customers = [];

        if (isset($router) && !empty($router)) {
            $router = ORM::for_table('tbl_routers')->find_one($router);
            if (!$router) {
                die(json_encode(['status' => 'error', 'message' => 'Invalid router']));
            }

            $query = ORM::for_table('tbl_user_recharges')
                ->left_outer_join('tbl_customers', 'tbl_user_recharges.customer_id = tbl_customers.id')
                ->where('tbl_user_recharges.routers', $router->name)
                ->offset($startpoint)
                ->limit($batch);

            switch ($group) {
                case 'all':
                    // No additional conditions needed
                    break;
                case 'new':
                    $query->where_raw("DATE(recharged_on) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
                    break;
                case 'expired':
                    $query->where('tbl_user_recharges.status', 'off');
                    break;
                case 'active':
                    $query->where('tbl_user_recharges.status', 'on');
                    break;
            }

            $query->selects([
                ['tbl_customers.phonenumber', 'phonenumber'],
                ['tbl_user_recharges.customer_id', 'customer_id'],
                ['tbl_customers.fullname', 'fullname'],
            ]);
            $customers = $query->find_array();
        } else {
            switch ($group) {
                case 'all':
                    $customers = ORM::for_table('tbl_customers')->offset($startpoint)->limit($batch)->find_array();
                    break;
                case 'new':
                    $customers = ORM::for_table('tbl_customers')
                        ->where_raw("DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)")
                        ->offset($startpoint)->limit($batch)->find_array();
                    break;
                case 'expired':
                    $customers = ORM::for_table('tbl_user_recharges')->where('status', 'off')
                        ->select('customer_id')->offset($startpoint)->limit($batch)->find_array();
                    break;
                case 'active':
                    $customers = ORM::for_table('tbl_user_recharges')->where('status', 'on')
                        ->select('customer_id')->offset($startpoint)->limit($batch)->find_array();
                    break;
            }
        }

        // Ensure $customers is always an array
        if (!$customers) {
            $customers = [];
        }

        // Calculate total customers for the group
        $totalCustomers = 0;
        if ($router) {
            switch ($group) {
                case 'all':
                    $totalCustomers = ORM::for_table('tbl_user_recharges')->where('routers', $router->routers)->count();
                    break;
                case 'new':
                    $totalCustomers = ORM::for_table('tbl_user_recharges')
                        ->where_raw("DATE(recharged_on) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")
                        ->where('routers', $router->routers)
                        ->count();
                    break;
                case 'expired':
                    $totalCustomers = ORM::for_table('tbl_user_recharges')->where('status', 'off')->where('routers', $router->routers)->count();
                    break;
                case 'active':
                    $totalCustomers = ORM::for_table('tbl_user_recharges')->where('status', 'on')->where('routers', $router->routers)->count();
                    break;
            }
        } else {
            switch ($group) {
                case 'all':
                    $totalCustomers = ORM::for_table('tbl_customers')->count();
                    break;
                case 'new':
                    $totalCustomers = ORM::for_table('tbl_customers')
                        ->where_raw("DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)")
                        ->count();
                    break;
                case 'expired':
                    $totalCustomers = ORM::for_table('tbl_user_recharges')->where('status', 'off')->count();
                    break;
                case 'active':
                    $totalCustomers = ORM::for_table('tbl_user_recharges')->where('status', 'on')->count();
                    break;
            }
        }

        // Send messages
        $totalSMSSent = 0;
        $totalSMSFailed = 0;
        $totalWhatsappSent = 0;
        $totalWhatsappFailed = 0;
        $batchStatus = [];

        foreach ($customers as $customer) {
            $currentMessage = str_replace(
                ['[[name]]', '[[user_name]]', '[[phone]]', '[[company_name]]'],
                [$customer['fullname'], $customer['username'], $customer['phonenumber'], $config['CompanyName']],
                $message
            );

            $phoneNumber = preg_replace('/\D/', '', $customer['phonenumber']);

            if (empty($phoneNumber)) {
                $batchStatus[] = [
                    'name' => $customer['fullname'],
                    'phone' => '',
                    'status' => 'No Phone Number'
                ];
                continue;
            }

            if ($test) {
                $batchStatus[] = [
                    'name' => $customer['fullname'],
                    'phone' => $customer['phonenumber'],
                    'status' => 'Test Mode',
                    'message' => $currentMessage
                ];
            } else {
                if ($via == 'sms' || $via == 'both') {
                    if (Message::sendSMS($customer['phonenumber'], $currentMessage)) {
                        $totalSMSSent++;
                        $batchStatus[] = ['name' => $customer['fullname'], 'phone' => $customer['phonenumber'], 'status' => 'SMS Sent', 'message' => $currentMessage];
                    } else {
                        $totalSMSFailed++;
                        $batchStatus[] = ['name' => $customer['fullname'], 'phone' => $customer['phonenumber'], 'status' => 'SMS Failed', 'message' => $currentMessage];
                    }
                }

                if ($via == 'wa' || $via == 'both') {
                    if (Message::sendWhatsapp($customer['phonenumber'], $currentMessage)) {
                        $totalWhatsappSent++;
                        $batchStatus[] = ['name' => $customer['fullname'], 'phone' => $customer['phonenumber'], 'status' => 'WhatsApp Sent', 'message' => $currentMessage];
                    } else {
                        $totalWhatsappFailed++;
                        $batchStatus[] = ['name' => $customer['fullname'], 'phone' => $customer['phonenumber'], 'status' => 'WhatsApp Failed', 'message' => $currentMessage];
                    }
                }
            }
        }

        // Calculate if there are more customers to process
        $hasMore = ($startpoint + $batch) < $totalCustomers;

        // Return JSON response
        echo json_encode([
            'status' => 'success',
            'page' => $page + 1,
            'batchStatus' => $batchStatus,
            'message' => $currentMessage,
            'totalSent' => $totalSMSSent + $totalWhatsappSent,
            'totalFailed' => $totalSMSFailed + $totalWhatsappFailed,
            'hasMore' => $hasMore
        ]);
        break;

    case 'send_bulk_selected':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Set headers
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, no-store, must-revalidate');

            // Get the posted data
            $customerIds = $_POST['customer_ids'] ?? [];
            $via = $_POST['message_type'] ?? '';
            $message = isset($_POST['message']) ? trim($_POST['message']) : '';
            if (empty($customerIds) || empty($message) || empty($via)) {
                echo json_encode(['status' => 'error', 'message' => Lang::T('Invalid customer IDs, Message, or Message Type.')]);
                exit;
            }

            // Prepare to send messages
            $sentCount = 0;
            $failedCount = 0;
            $subject = Lang::T('Notification Message');
            $form = 'Admin';

            foreach ($customerIds as $customerId) {
                $customer = ORM::for_table('tbl_customers')->where('id', $customerId)->find_one();
                if ($customer) {
                    $messageSent = false;

                    // Check the message type and send accordingly
                    try {
                        if ($via === 'sms' || $via === 'all') {
                            $messageSent = Message::sendSMS($customer['phonenumber'], $message);
                        }
                        if (!$messageSent && ($via === 'wa' || $via === 'all')) {
                            $messageSent = Message::sendWhatsapp($customer['phonenumber'], $message);
                        }
                        if (!$messageSent && ($via === 'inbox' || $via === 'all')) {
                            Message::addToInbox($customer['id'], $subject, $message, $form);
                            $messageSent = true;
                        }
                        if (!$messageSent && ($via === 'email' || $via === 'all')) {
                            $messageSent = Message::sendEmail($customer['email'], $subject, $message);
                        }
                    } catch (Throwable $e) {
                        $messageSent = false;
                        $failedCount++;
                        sendTelegram('Failed to send message to ' . $e->getMessage());
                        _log('Failed to send message to ' . $customer['fullname'] . ': ' . $e->getMessage());
                        continue;
                    }

                    if ($messageSent) {
                        $sentCount++;
                    } else {
                        $failedCount++;
                    }
                } else {
                    $failedCount++;
                }
            }

            // Prepare the response
            echo json_encode([
                'status' => 'success',
                'totalSent' => $sentCount,
                'totalFailed' => $failedCount
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => Lang::T('Invalid request method.')]);
        }
        break;
    default:
        r2(getUrl('message/send_sms'), 'e', 'action not defined');
}
