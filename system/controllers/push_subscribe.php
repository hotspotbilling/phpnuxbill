<?php

/// Allow requests from same origin only (more secure)
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
$allowed_origins = [
    'https://' . $_SERVER['HTTP_HOST'],
    'http://' . $_SERVER['HTTP_HOST']  // for localhost development
];

if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: https://' . $_SERVER['HTTP_HOST']);
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

header('Content-Type: application/json');

// Decode the incoming JSON payload
$subscription = json_decode(file_get_contents('php://input'), true);

// Log the incoming subscription data for debugging
_log("Received subscription: " . json_encode($subscription));

// Validate required fields
$requiredFields = ['customer_id', 'endpoint', 'keys'];
$requiredKeys = ['p256dh', 'auth'];

foreach ($requiredFields as $field) {
    if (!isset($subscription[$field])) {
        _log("Missing field: $field");
        http_response_code(400);
        echo json_encode(['success' => false, 'title' => Lang::T('An error occurred'), 'message' => Lang::T("Missing required field: $field")]);
        exit;
    }
} 

foreach ($requiredKeys as $key) {
    if (!isset($subscription['keys'][$key])) {
        _log("Missing key: $key in keys array");
        http_response_code(400);
        echo json_encode(['success' => false, 'title' => Lang::T('An error occurred'), 'message' => Lang::T("Missing required key: $key")]);
        exit;
    }
}

// Validate customer_id (ensure it's an integer)
if (!filter_var($subscription['customer_id'], FILTER_VALIDATE_INT)) {
    _log("Invalid customer_id: " . $subscription['customer_id']);
    http_response_code(400);
    echo json_encode(['success' => false, 'title' => Lang::T('An error occurred'), 'message' => Lang::T('Invalid customer id')]);
    exit;
}

try {
    // Create and save the subscription
    $sub = ORM::for_table('tbl_push_subscriptions')->create([
        'customer_id' => (int) $subscription['customer_id'], // Cast to integer for safety
        'endpoint' => $subscription['endpoint'],
        'p256dh' => $subscription['keys']['p256dh'],
        'auth' => $subscription['keys']['auth'],
    ]);

    if ($sub->save()) {
        _log(Lang::T("Subscription saved for customer_id: " . $subscription['customer_id']));
        echo json_encode([
            'success' => true,
            'title' => Lang::T('Subscribed!'),
            'message' => Lang::T('You will now receive updates.'),
            'customer_id' => $subscription['customer_id']
        ]);
    } else {
        throw new Exception('Failed to save subscription');
    }
} catch (Exception $e) {
    _log(Lang::T("Error saving subscription: ") . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'title' => Lang::T('An error occurred'),
        'message' =>  Lang::T('Failed to save subscription, Please try again later.')
    ]);
}