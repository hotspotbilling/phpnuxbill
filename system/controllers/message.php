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

        $id_customer = $_POST['id_customer'] ?? '';
        $message = $_POST['message'] ?? '';
        $subject = $_POST['subject'] ?? '';
        $channels = ['email', 'sms', 'wa', 'inbox'];


        // Validate subject based on the selected channel
        if (empty($id_customer)) {
            r2(getUrl('message/send'), 'e', Lang::T('Please select a customer'));
        }

        if (empty($subject) && (isset($_POST['email']) || isset($_POST['inbox']))) {
            r2(getUrl('message/send'), 'e', Lang::T('Subject is required'));
        }

        if (empty($message)) {
            r2(getUrl('message/send'), 'e', Lang::T('Message is required'));
        }

        if (count(array_intersect_key(array_flip($channels), $_POST)) === 0) {
            r2(getUrl('message/send'), 'e', Lang::T('Please select at least one channel type'));
        }

        $customer = ORM::for_table('tbl_customers')->find_one($id_customer);
        if (!$customer) {
            r2(getUrl('message/send'), 'e', Lang::T('Customer not found'));
        }

        // Replace placeholders in message and subject
        $currentMessage = str_replace(
            ['[[name]]', '[[user_name]]', '[[phone]]', '[[company_name]]'],
            [$customer['fullname'], $customer['username'], $customer['phonenumber'], $config['CompanyName']],
            $message
        );

        $currentSubject = str_replace(
            ['[[name]]', '[[user_name]]', '[[phone]]', '[[company_name]]'],
            [$customer['fullname'], $customer['username'], $customer['phonenumber'], $config['CompanyName']],
            $subject
        );

        if (strpos($message, '[[payment_link]]') !== false) {
            $token = User::generateToken($customer['id'], 1);
            if (!empty($token['token'])) {
                $tur = ORM::for_table('tbl_user_recharges')
                    ->where('customer_id', $customer['id'])
                    ->find_one();
                if ($tur) {
                    $url = '?_route=home&recharge=' . $tur['id'] . '&uid=' . urlencode($token['token']);
                    $currentMessage = str_replace('[[payment_link]]', $url, $currentMessage);
                }
            } else {
                $currentMessage = str_replace('[[payment_link]]', '', $currentMessage);
            }
        }

        // Send the message through the selected channels
        $smsSent = $waSent = $emailSent = $inboxSent = false;

        if (isset($_POST['sms'])) {
            $smsSent = Message::sendSMS($customer['phonenumber'], $currentMessage);
        }

        if (isset($_POST['wa'])) {
            $waSent = Message::sendWhatsapp($customer['phonenumber'], $currentMessage);
        }

        if (isset($_POST['email'])) {
            $emailSent = Message::sendEmail($customer['email'], $currentSubject, $currentMessage);
        }

        if (isset($_POST['inbox'])) {
            $inboxSent = Message::addToInbox($customer['id'], $currentSubject, $currentMessage, 'Admin');
        }

        // Check if any message was sent successfully
        if ($smsSent || $waSent || $emailSent || $inboxSent) {
            r2(getUrl('message/send'), 's', Lang::T('Message Sent Successfully'));
        } else {
            r2(getUrl('message/send'), 'e', Lang::T('Failed to send message'));
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
        $batch = $_REQUEST['batch'] ?? 100;
        $page = $_REQUEST['page'] ?? 0;
        $router = $_REQUEST['router'] ?? null;
        $test = isset($_REQUEST['test']) && $_REQUEST['test'] === 'on';
        $service = $_REQUEST['service'] ?? '';
        $subject = $_REQUEST['subject'] ?? '';
        $channels = ['email', 'sms', 'wa', 'inbox'];
        $selectedChannels = [];

        foreach ($channels as $channel) {
            if (isset($_REQUEST[$channel]) && $_REQUEST[$channel] == '1') {
                $selectedChannels[] = $channel;
            }
        }

        if (empty($selectedChannels)) {
            die(json_encode(['status' => 'error', 'message' => Lang::T('Please select at least one channel type')]));
        }

        if (empty($group) || empty($message) || empty($service)) {
            die(json_encode(['status' => 'error', 'message' => LANG::T('All fields are required')]));
        }

        if (array_intersect($selectedChannels, ['email', 'inbox']) && empty($subject)) {
            die(json_encode(['status' => 'error', 'message' => LANG::T('Subject is required') . '.']));
        }

        // Get batch of customers based on group
        $startpoint = $page * $batch;
        $customers = [];
        $totalCustomers = 0;

        if (isset($router) && !empty($router)) {
            switch ($router) {
                case 'radius':
                    $routerName = 'Radius';
                    break;
                default:
                    $router = ORM::for_table('tbl_routers')->find_one($router);
                    if (!$router) {
                        die(json_encode(['status' => 'error', 'message' => LANG::T('Invalid router')]));
                    }
                    $routerName = $router->name;
                    break;
            }
        }

        if (isset($router) && !empty($router)) {
            $query = ORM::for_table('tbl_user_recharges')
                ->left_outer_join('tbl_customers', 'tbl_user_recharges.customer_id = tbl_customers.id')
                ->where('tbl_user_recharges.routers', $routerName);

            switch ($service) {
                case 'all':
                    break;
                default:
                    $validServices = ['PPPoE', 'Hotspot', 'VPN'];
                    if (in_array($service, $validServices)) {
                        $query->where('type', $service);
                    }
                    break;
            }

            $totalCustomers = $query->count();

            $query->offset($startpoint)
                ->limit($batch);

            switch ($group) {
                case 'all':
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

            // Fetch the customers
            $query->selects([
                ['tbl_customers.phonenumber', 'phonenumber'],
                ['tbl_user_recharges.customer_id', 'customer_id'],
                ['tbl_customers.fullname', 'fullname'],
                ['tbl_customers.username', 'username'],
                ['tbl_customers.email', 'email'],
                ['tbl_customers.service_type', 'service_type'],
            ]);
            $customers = $query->find_array();
        } else {
            switch ($group) {
                case 'all':
                    $totalCustomersQuery = ORM::for_table('tbl_customers');

                    switch ($service) {
                        case 'all':
                            break;
                        default:
                            $validServices = ['PPPoE', 'Hotspot', 'VPN'];
                            if (in_array($service, $validServices)) {
                                $totalCustomersQuery->where('service_type', $service);
                            }
                            break;
                    }
                    $totalCustomers = $totalCustomersQuery->count();
                    $customers = $totalCustomersQuery->offset($startpoint)->limit($batch)->find_array();
                    break;

                case 'new':
                    $totalCustomersQuery = ORM::for_table('tbl_customers')
                        ->where_raw("DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)");

                    switch ($service) {
                        case 'all':
                            break;
                        default:
                            $validServices = ['PPPoE', 'Hotspot', 'VPN'];
                            if (in_array($service, $validServices)) {
                                $totalCustomersQuery->where('service_type', $service);
                            }
                            break;
                    }
                    $totalCustomers = $totalCustomersQuery->count();
                    $customers = $totalCustomersQuery->offset($startpoint)->limit($batch)->find_array();
                    break;

                case 'expired':
                    $totalCustomersQuery = ORM::for_table('tbl_user_recharges')
                        ->where('status', 'off');

                    switch ($service) {
                        case 'all':
                            break;
                        default:
                            $validServices = ['PPPoE', 'Hotspot', 'VPN'];
                            if (in_array($service, $validServices)) {
                                $totalCustomersQuery->where('type', $service);
                            }
                            break;
                    }
                    $totalCustomers = $totalCustomersQuery->count();
                    $customers = $totalCustomersQuery->select('customer_id')->offset($startpoint)->limit($batch)->find_array();
                    break;

                case 'active':
                    $totalCustomersQuery = ORM::for_table('tbl_user_recharges')
                        ->where('status', 'on');

                    switch ($service) {
                        case 'all':
                            break;
                        default:
                            $validServices = ['PPPoE', 'Hotspot', 'VPN'];
                            if (in_array($service, $validServices)) {
                                $totalCustomersQuery->where('type', $service);
                            }
                            break;
                    }
                    $totalCustomers = $totalCustomersQuery->count();
                    $customers = $totalCustomersQuery->select('customer_id')->offset($startpoint)->limit($batch)->find_array(); // Get customer data
                    break;
            }
        }

        // Ensure $customers is always an array
        if (!$customers) {
            $customers = [];
        }

        // Send messages
        $totalSMSSent = 0;
        $totalSMSFailed = 0;
        $totalWhatsappSent = 0;
        $totalWhatsappFailed = 0;
        $totalEmailSent = 0;
        $totalEmailFailed = 0;
        $totalInboxSent = 0;
        $totalInboxFailed = 0;
        $batchStatus = [];
        //$subject = $config['CompanyName'] . ' ' . Lang::T('Notification Message');
        $from = 'Admin';

        foreach ($customers as $customer) {
            $currentMessage = str_replace(
                ['[[name]]', '[[user_name]]', '[[phone]]', '[[company_name]]'],
                [$customer['fullname'], $customer['username'], $customer['phonenumber'], $config['CompanyName']],
                $message
            );

            $currentSubject = str_replace(
                ['[[name]]', '[[user_name]]', '[[phone]]', '[[company_name]]'],
                [$customer['fullname'], $customer['username'], $customer['phonenumber'], $config['CompanyName']],
                $subject
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
                    'sent' => $customer['phonenumber'],
                    'channel' => implode(', ', array_map('ucfirst', $selectedChannels)),
                    'status' => 'Test Mode',
                    'message' => $currentMessage,
                    'service' => $service,
                    'router' => $routerName,
                ];
            } else {
                if (isset($_REQUEST['sms']) && $_REQUEST['sms'] == '1') {
                    if (Message::sendSMS($customer['phonenumber'], $currentMessage)) {
                        $totalSMSSent++;
                        $batchStatus[] = [
                            'name' => $customer['fullname'],
                            'sent' => $customer['phonenumber'],
                            'channel' => 'SMS',
                            'status' => 'SMS Sent',
                            'message' => $currentMessage,
                            'service' => $service,
                            'router' => $routerName,
                        ];
                    } else {
                        $totalSMSFailed++;
                        $batchStatus[] = [
                            'name' => $customer['fullname'],
                            'sent' => $customer['phonenumber'],
                            'channel' => 'SMS',
                            'status' => 'SMS Failed',
                            'message' => $currentMessage,
                            'service' => $service,
                            'router' => $routerName,
                        ];
                    }
                }

                if (isset($_REQUEST['wa']) && $_REQUEST['wa'] == '1') {
                    if (Message::sendWhatsapp($customer['phonenumber'], $currentMessage)) {
                        $totalWhatsappSent++;
                        $batchStatus[] = [
                            'name' => $customer['fullname'],
                            'sent' => $customer['phonenumber'],
                            'channel' => 'WhatsApp',
                            'status' => 'WhatsApp Sent',
                            'message' => $currentMessage,
                            'service' => $service,
                            'router' => $routerName,
                        ];
                    } else {
                        $totalWhatsappFailed++;
                        $batchStatus[] = [
                            'name' => $customer['fullname'],
                            'sent' => $customer['phonenumber'],
                            'channel' => 'WhatsApp',
                            'status' => 'WhatsApp Failed',
                            'message' => $currentMessage,
                            'service' => $service,
                            'router' => $routerName,
                        ];
                    }
                }

                if (isset($_REQUEST['email']) && $_REQUEST['email'] == '1') {
                    if (Message::sendEmail($customer['email'], $currentSubject, $currentMessage)) {
                        $totalEmailSent++;
                        $batchStatus[] = [
                            'name' => $customer['fullname'],
                            'sent' => $customer['email'],
                            'channel' => 'Email',
                            'status' => 'Email Sent',
                            'message' => $currentMessage,
                            'service' => $service,
                            'router' => $routerName,
                        ];
                    } else {
                        $totalEmailFailed++;
                        $batchStatus[] = [
                            'name' => $customer['fullname'],
                            'sent' => $customer['email'],
                            'channel' => 'Email',
                            'status' => 'Email Failed',
                            'message' => $currentMessage,
                            'service' => $service,
                            'router' => $routerName,
                        ];
                    }
                }

                if (isset($_REQUEST['inbox']) && $_REQUEST['inbox'] == '1') {
                    if (Message::addToInbox($customer['customer_id'], $currentSubject, $currentMessage, $from)) {
                        $totalInboxSent++;
                        $batchStatus[] = [
                            'name' => $customer['fullname'],
                            'sent' => $customer['username'],
                            'channel' => 'Inbox',
                            'status' => 'Inbox Message Sent',
                            'message' => $currentMessage,
                            'service' => $service,
                            'router' => $routerName,
                        ];
                    } else {
                        $totalInboxFailed++;
                        $batchStatus[] = [
                            'name' => $customer['fullname'],
                            'sent' => $customer['username'],
                            'channel' => 'Inbox',
                            'status' => 'Inbox Message Failed',
                            'message' => $currentMessage,
                            'service' => $service,
                            'router' => $routerName,
                        ];
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
            'totalSent' => $totalSMSSent + $totalWhatsappSent + $totalEmailSent + $totalInboxSent,
            'totalFailed' => $totalSMSFailed + $totalWhatsappFailed + $totalEmailFailed + $totalInboxFailed,
            'hasMore' => $hasMore,
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
            $subject = $_POST['subject'] ?? '';
            $message = isset($_POST['message']) ? trim($_POST['message']) : '';
            if (empty($customerIds) || empty($message) || empty($via)) {
                echo json_encode(['status' => 'error', 'message' => Lang::T('Invalid customer IDs, Message, or Message Type.')]);
                exit;
            }

            if ($via === 'all' || $via === 'email' || $via === 'inbox' && empty($subject)) {
                die(json_encode(['status' => 'error', 'message' => LANG::T('Subject is required to send message using') . ' ' . $via . '.']));
            }

            // Prepare to send messages
            $sentCount = 0;
            $failedCount = 0;
            $from = 'Admin';

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
                            Message::addToInbox($customer['id'], $subject, $message, $from);
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
