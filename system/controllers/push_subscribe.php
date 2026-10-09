<?php

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$allowed_origins = [
    'https://' . $host,
    'http://' . $host
];

if (!empty($origin) && in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} elseif (!empty($origin)) {
    // If behind reverse proxy or port mismatch, allow request from same host
    $originHost = parse_url($origin, PHP_URL_HOST);
    $serverHost = explode(':', $host)[0];
    if ($originHost && strcasecmp($originHost, $serverHost) === 0) {
        header('Access-Control-Allow-Origin: ' . $origin);
    } else {
        header('Access-Control-Allow-Origin: ' . (defined('APP_URL') ? APP_URL : '*'));
    }
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
    // Auto-create table if missing or broken on the server
    try {
        ORM::raw_execute("CREATE TABLE IF NOT EXISTS `tbl_push_subscriptions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `customer_id` int(11) NOT NULL,
            `endpoint` text DEFAULT NULL,
            `p256dh` varchar(255) DEFAULT NULL,
            `auth` varchar(255) DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    } catch (Throwable $t) {
        // Table probably already exists
    }

    // Check if subscription already exists for this endpoint or customer
    $endpoint = (string) $subscription['endpoint'];
    $customerId = (int) $subscription['customer_id'];

    $sub = ORM::for_table('tbl_push_subscriptions')
        ->where('endpoint', $endpoint)
        ->find_one();

    if (!$sub) {
        $sub = ORM::for_table('tbl_push_subscriptions')->create();
    }

    $sub->customer_id = $customerId;
    $sub->endpoint = $endpoint;
    $sub->p256dh = $subscription['keys']['p256dh'];
    $sub->auth = $subscription['keys']['auth'];

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
} catch (Throwable $e) {
    _log(Lang::T("Error saving subscription: ") . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'title' => Lang::T('An error occurred'),
        'message' =>  Lang::T('Failed to save subscription, Please try again later.') . ' (' . $e->getMessage() . ')'
    ]);
}