<?php

// Send a notification to customer with ID 123
$result = Message::sendPush(
    customerId: 8,
    title: "Account Update",
    body: "Your account details have been updated.",
    url: "/profile"
);

if ($result['success']) {
    echo "Sent: {$result['sent']}, Failed: {$result['failed']}";
} else {
    echo $result['message'];
}

// Add detailed error logging
if (!empty($result['errors'])) {
    echo "\nDetailed Errors:\n";
    foreach ($result['errors'] as $error) {
        echo $error . "\n";
    }
    _log("Push notification errors for customer_id 8: " . implode('; ', $result['errors']));
}

// Optionally log subscription data for debugging
$subscriptions = ORM::for_table('tbl_push_subscriptions')
    ->where('customer_id', 8)
    ->find_array();
_log("Subscriptions for customer_id 8: " . json_encode($subscriptions));