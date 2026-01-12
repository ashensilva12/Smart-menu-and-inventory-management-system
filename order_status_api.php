<?php
require_once __DIR__ . '/admin_only.php';
header('Content-Type: application/json');

$allowedStatuses = ['placed','preparing','ready','completed'];

function load_statuses() {
    $file = __DIR__ . '/order_status.json';
    if (!file_exists($file)) return [];
    $json = file_get_contents($file);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function save_statuses($statuses) {
    $file = __DIR__ . '/order_status.json';
    file_put_contents($file, json_encode($statuses, JSON_PRETTY_PRINT), LOCK_EX);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(['success' => true, 'statuses' => load_statuses()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$orderId = isset($data['orderId']) ? (int)$data['orderId'] : 0;
$status = isset($data['status']) ? strtolower(trim($data['status'])) : '';

if ($orderId <= 0 || !in_array($status, $allowedStatuses, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid order id or status']);
    exit;
}

$statuses = load_statuses();
$existing = $statuses[$orderId] ?? [];
$existing['orderId'] = $orderId;
$existing['status'] = $status;
$existing['updatedAt'] = date('c');
$statuses[$orderId] = $existing;

save_statuses($statuses);

echo json_encode(['success' => true, 'orderId' => $orderId, 'status' => $status]);
