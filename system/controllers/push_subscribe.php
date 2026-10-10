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
    } elseif (defined('APP_URL') && !empty(APP_URL) && rtrim($origin, '/') === rtrim(APP_URL, '/')) {
        header('Access-Control-Allow-Origin: ' . APP_URL);
    }
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

header('Content-Type: application/json');

$customerId = (int) User::getID();
if (!$customerId) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'title' => Lang::T('Unauthorized'),
        'message' => Lang::T('Please log in to continue.')
    ]);
    exit;
}

// Decode the incoming JSON payload
$subscription = json_decode(file_get_contents('php://input'), true);

// Log the incoming subscription data for debugging
_log("Received subscription: " . json_encode($subscription));

// Validate required fields
$requiredFields = ['endpoint', 'keys'];
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

    // Check if subscription already exists for this endpoint and authenticated customer
    $endpoint = (string) $subscription['endpoint'];

    $sub = ORM::for_table('tbl_push_subscriptions')
        ->where('endpoint', $endpoint)
        ->where('customer_id', $customerId)
        ->find_one();

    if (!$sub) {
        $sub = ORM::for_table('tbl_push_subscriptions')->create();
    }

    $sub->customer_id = $customerId;
    $sub->endpoint = $endpoint;
    $sub->p256dh = $subscription['keys']['p256dh'];
    $sub->auth = $subscription['keys']['auth'];

    if ($sub->save()) {
        _log("Subscription saved for customer_id: " . $customerId);
        echo json_encode([
            'success' => true,
            'title' => Lang::T('Subscribed!'),
            'message' => Lang::T('You will now receive updates.'),
            'customer_id' => $customerId
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
        'message' => Lang::T('Failed to save subscription, Please try again later.')
    ]);
}