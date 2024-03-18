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
        $c = ORM::for_table('tbl_customers')->find_many();
        $ui->assign('c', $c);
        $ui->assign('xfooter', $select2_customer);
        $ui->display('message.tpl');
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
            r2(U . 'message/send', 'e', Lang::T('All field is required'));
        } else {
            // Get customer details from the database
            $c = ORM::for_table('tbl_customers')->find_one($id_customer);

            // Replace placeholders in the message with actual values
            $message = str_replace('[[name]]', $c['fullname'], $message);
            $message = str_replace('[[user_name]]', $c['username'], $message);
            $message = str_replace('[[phone]]', $c['phonenumber'], $message);
            $message = str_replace('[[company_name]]', $config['CompanyName'], $message);


            //Send the message
            if ($via == 'sms' || $via == 'both') {
                $smsSent = Message::sendSMS($c['phonenumber'], $message);
            }

            if ($via == 'wa' || $via == 'both') {
                $waSent = Message::sendWhatsapp($c['phonenumber'], $message);
            }

            if (isset($smsSent) || isset($waSent)) {
                r2(U . 'message/send', 's', Lang::T('Message Sent Successfully'));
            } else {
                r2(U . 'message/send', 'e', Lang::T('Failed to send message'));
            }
        }
        break;

    case 'send_bulk':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $ui->display('message-bulk.tpl');
        break;
    case 'send_bulk-post':
        // Check user permissions
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }

        // Get form data
        $group = $_POST['group'];
        $message = $_POST['message'];
        $via = $_POST['via'];

        // Initialize counters
        $successCount = 0;
        $failCount = 0;
        $successMessages = [];
        $failMessages = [];

        // Check if fields are empty
        if ($group == '' or $message == '' or $via == '') {
            r2(U . 'message/send_bulk', 'e', Lang::T('All fields are required'));
        } else {
            // Get customer details from the database based on the selected group
            if ($group == 'all') {
                $customers = ORM::for_table('tbl_customers')->find_many();
            } elseif ($group == 'new') {
                // Get customers created just a month ago
                $customers = ORM::for_table('tbl_customers')->where_raw("DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)")->find_many();
            } elseif ($group == 'expired') {
                // Get expired user recharges where status is 'off'
                $expired = ORM::for_table('tbl_user_recharges')->where('status', 'off')->find_many();
                $customer_ids = [];
                foreach ($expired as $recharge) {
                    $customer_ids[] = $recharge->customer_id;
                }
                $customers = ORM::for_table('tbl_customers')->where_in('id', $customer_ids)->find_many();
            } elseif ($group == 'active') {
                // Get active user recharges where status is 'on'
                $active = ORM::for_table('tbl_user_recharges')->where('status', 'on')->find_many();
                $customer_ids = [];
                foreach ($active as $recharge) {
                    $customer_ids[] = $recharge->customer_id;
                }
                $customers = ORM::for_table('tbl_customers')->where_in('id', $customer_ids)->find_many();
            }

            // Loop through customers and send messages
            foreach ($customers as $customer) {
                // Replace placeholders in the message with actual values for each customer
                $message = str_replace('[[name]]', $customer['fullname'], $message);
                $message = str_replace('[[user_name]]', $customer['username'], $message);
                $message = str_replace('[[phone]]', $customer['phonenumber'], $message);
                $message = str_replace('[[company_name]]', $config['CompanyName'], $message);

                // Send the message based on the selected method
                if ($via == 'sms' || $via == 'both') {
                    $smsSent = Message::sendSMS($customer['phonenumber'], $message);
                    if ($smsSent) {
                        $successCount++;
                        $successMessages[] = "SMS sent to {$customer['fullname']}: {$customer['phonenumber']}";
                    } else {
                        $failCount++;
                        $failMessages[] = "Failed to send SMS to {$customer['fullname']}: {$customer['phonenumber']}";
                    }
                    // Introduce a delay of 5 seconds between each SMS
                    sleep(5);
                }

                if ($via == 'wa' || $via == 'both') {
                    $waSent = Message::sendWhatsapp($customer['phonenumber'], $message);
                    if ($waSent) {
                        $successCount++;
                        $successMessages[] = "WhatsApp message sent to {$customer['fullname']}: {$customer['phonenumber']}";
                    } else {
                        $failCount++;
                        $failMessages[] = "Failed to send WhatsApp message to {$customer['fullname']}: {$customer['phonenumber']}";
                    }
                    // Introduce a delay of 5 seconds between each WhatsApp message
                    sleep(5);
                }
            }

            $responseMessage = '';

            if ($successCount > 0) {
                $responseMessage .= "Messages Sent Successfully: {$successCount}<br>";
                $responseMessage .= "<ul>";
                foreach ($successMessages as $successMessage) {
                    $responseMessage .= "<li>{$successMessage}</li>";
                }
                $responseMessage .= "</ul>";
            }

            if ($failCount > 0) {
                $responseMessage .= "Failed to send messages: {$failCount}<br>";
                $responseMessage .= "<ul>";
                foreach ($failMessages as $failMessage) {
                    $responseMessage .= "<li>{$failMessage}</li>";
                }
                $responseMessage .= "</ul>";
            }

            if ($responseMessage != '') {
                r2(U . 'message/send_bulk', 's', $responseMessage);
            } else {
                r2(U . 'message/send_bulk', 'e', Lang::T('No messages sent'));
            }
        }

        break;




    default:
        r2(U . 'message/send_sms', 'e', 'action not defined');
}
