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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check(_post('csrf_token'))) {
            r2(getUrl('message/send'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
        }

        $id_customer = (int) _post('id_customer');
        $message = trim((string) ($_POST['message'] ?? ''));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $channels = MessageSender::channelsFrom($_POST);

        if ($id_customer < 1) {
            r2(getUrl('message/send'), 'e', Lang::T('Please select a customer'));
        }
        if (empty($channels)) {
            r2(getUrl('message/send'), 'e', Lang::T('Please select at least one channel type'));
        }
        if ($subject === '' && MessageSender::needsSubject($channels)) {
            r2(getUrl('message/send'), 'e', Lang::T('Subject is required'));
        }
        if ($message === '') {
            r2(getUrl('message/send'), 'e', Lang::T('Message is required'));
        }

        $customer = ORM::for_table('tbl_customers')->find_one($id_customer);
        if (!$customer) {
            r2(getUrl('message/send'), 'e', Lang::T('Customer not found'));
        }

        $entries = MessageSender::deliver($customer->as_array(), $channels, $subject, $message);
        $sent = $problems = [];
        foreach ($entries as $e) {
            if ($e['result'] === 'sent') {
                $sent[] = $e['channel'];
            } else {
                $problems[] = $e['status'];
            }
        }
        if ($sent) {
            r2(getUrl('message/send'), $problems ? 'w' : 's', Lang::T('Message Sent Successfully') . ': ' . implode(', ', $sent) . ($problems ? '. ' . implode('; ', $problems) : ''));
        }
        r2(getUrl('message/send'), 'e', Lang::T('Failed to send message') . ': ' . implode('; ', $problems));
        break;

    case 'send_bulk':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }

        $ui->assign('routers', ORM::forTable('tbl_routers')->where('enabled', '1')->find_many());
        $ui->display('admin/message/bulk.tpl');
        break;

    case 'send_bulk_ajax':
        header('Content-Type: application/json');
        header('Cache-Control: no-store');
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            die(json_encode(['status' => 'error', 'message' => Lang::T('You do not have permission to access this page')]));
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check(_post('csrf_token'))) {
            die(json_encode(['status' => 'error', 'message' => Lang::T('Invalid or Expired CSRF Token') . '. ' . Lang::T('Reload the page and try again') . '.']));
        }
        set_time_limit(0);
        ignore_user_abort(true);

        $group = (string) ($_POST['group'] ?? '');
        $message = trim((string) ($_POST['message'] ?? ''));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $service = (string) ($_POST['service'] ?? '');
        $router = (string) ($_POST['router'] ?? '');
        $test = ($_POST['test'] ?? '') === 'on';
        $batch = (int) ($_POST['batch'] ?? 20);
        if (!in_array($batch, MessageSender::BATCH_SIZES, true)) {
            $batch = 20;
        }
        $page = max(0, (int) ($_POST['page'] ?? 0));
        $selectedChannels = MessageSender::channelsFrom($_POST);

        if (empty($selectedChannels)) {
            die(json_encode(['status' => 'error', 'message' => Lang::T('Please select at least one channel type')]));
        }
        if (!in_array($group, ['all', 'new', 'expired', 'active'], true) || $message === '' || $service === '') {
            die(json_encode(['status' => 'error', 'message' => Lang::T('All fields are required')]));
        }
        if ($subject === '' && MessageSender::needsSubject($selectedChannels)) {
            die(json_encode(['status' => 'error', 'message' => Lang::T('Subject is required') . '.']));
        }

        try {
            [$customers, $totalCustomers, $routerName] = MessageSender::recipients($group, $service, $router, $page * $batch, $batch);
        } catch (InvalidArgumentException $e) {
            die(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }

        $batchStatus = [];
        $count = ['sent' => 0, 'failed' => 0, 'skipped' => 0];
        foreach ($customers as $customer) {
            if ($test) {
                $plain = MessageSender::fill($message, $customer, 'plain');
                foreach ($selectedChannels as $ch) {
                    $batchStatus[] = [
                        'name' => $customer['fullname'],
                        'sent' => $ch === 'email' ? $customer['email'] : ($ch === 'inbox' || $ch === 'push' ? $customer['username'] : $customer['phonenumber']),
                        'channel' => MessageSender::LABELS[$ch],
                        'status' => 'Test Mode',
                        'result' => 'test',
                        'message' => $plain,
                        'service' => $service,
                        'router' => $routerName,
                    ];
                }
                continue;
            }
            $entries = MessageSender::deliver($customer, $selectedChannels, $subject, $message);
            foreach ($entries as $e) {
                $count[$e['result']]++;
                $batchStatus[] = [
                    'name' => $customer['fullname'],
                    'sent' => $e['to'],
                    'channel' => $e['channel'],
                    'status' => $e['status'],
                    'result' => $e['result'],
                    'message' => $e['message'],
                    'service' => $service,
                    'router' => $routerName,
                ];
                if (in_array($e['channel'], ['SMS', 'WhatsApp'], true)) {
                    usleep(250000); // gateways block senders that fire without a pause
                }
            }
        }

        echo json_encode([
            'status' => 'success',
            'page' => $page + 1,
            'batchStatus' => $batchStatus,
            'total' => $totalCustomers,
            'processed' => min($totalCustomers, ($page + 1) * $batch),
            'totalSent' => $count['sent'],
            'totalFailed' => $count['failed'],
            'totalSkipped' => $count['skipped'],
            'hasMore' => (($page + 1) * $batch) < $totalCustomers,
        ]);
        break;

    case 'send_bulk_selected':
        header('Content-Type: application/json');
        header('Cache-Control: no-store');
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
            die(json_encode(['status' => 'error', 'message' => Lang::T('You do not have permission to access this page')]));
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die(json_encode(['status' => 'error', 'message' => Lang::T('Invalid request method.')]));
        }
        if (!Csrf::check(_post('csrf_token'))) {
            die(json_encode(['status' => 'error', 'message' => Lang::T('Invalid or Expired CSRF Token') . '. ' . Lang::T('Reload the page and try again') . '.']));
        }
        set_time_limit(0);
        ignore_user_abort(true);

        $customerIds = $_POST['customer_ids'] ?? [];
        if (is_string($customerIds)) {
            $customerIds = json_decode($customerIds, true);
        }
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        $selectedChannels = MessageSender::channelsFrom($_POST);

        if (empty($customerIds) || !is_array($customerIds) || $message === '') {
            die(json_encode(['status' => 'error', 'message' => Lang::T('Invalid customer IDs, message, or message type.')]));
        }
        if (empty($selectedChannels)) {
            die(json_encode(['status' => 'error', 'message' => Lang::T('Please select at least one channel type.')]));
        }
        if ($subject === '' && MessageSender::needsSubject($selectedChannels)) {
            die(json_encode(['status' => 'error', 'message' => Lang::T('Subject is required.')]));
        }

        $sentCount = $failedCount = $skippedCount = 0;
        $problems = [];
        foreach (array_unique(array_filter(array_map('intval', $customerIds))) as $customerId) {
            $customer = ORM::for_table('tbl_customers')->find_one($customerId);
            if (!$customer) {
                $failedCount++;
                continue;
            }
            $entries = MessageSender::deliver($customer->as_array(), $selectedChannels, $subject, $message);
            $n = MessageSender::tally($entries);
            // a customer counts as sent when at least one channel got through
            if ($n['sent'] > 0) {
                $sentCount++;
            } elseif ($n['failed'] > 0) {
                $failedCount++;
            } else {
                $skippedCount++;
            }
            foreach ($entries as $e) {
                if ($e['result'] === 'failed' && count($problems) < 10) {
                    $problems[] = $customer->fullname . ': ' . $e['status'];
                }
                if (in_array($e['channel'], ['SMS', 'WhatsApp'], true)) {
                    usleep(250000);
                }
            }
        }
        if ($problems) {
            _log('Message to selected customers: ' . implode(' | ', $problems));
        }
        echo json_encode([
            'status' => 'success',
            'totalSent' => $sentCount,
            'totalFailed' => $failedCount,
            'totalSkipped' => $skippedCount,
            'problems' => $problems,
        ]);
        break;

    default:
        r2(getUrl('message/send'), 'e', 'action not defined');
}
