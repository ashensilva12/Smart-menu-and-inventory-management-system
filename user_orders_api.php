<?php
session_start();
header('Content-Type: application/json');

date_default_timezone_set('Asia/Colombo');

$customerEmail = isset($_SESSION['customer_email']) ? strtolower(trim($_SESSION['customer_email'])) : '';
if ($customerEmail === '') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

function load_statuses(): array {
    $file = __DIR__ . '/order_status.json';
    if (!file_exists($file)) return [];
    $json = file_get_contents($file);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

$statuses = load_statuses();
$orders = [];
foreach ($statuses as $oid => $row) {
    $email = strtolower(trim($row['email'] ?? ''));
    if ($email === '' || $email !== $customerEmail) continue;
    $orders[] = [
        'orderId'   => (int)$oid,
        'customer'  => $row['customer'] ?? 'Guest',
        'status'    => $row['status'] ?? 'placed',
        'orderType' => $row['orderType'] ?? 'Pickup',
        'total'     => $row['total'] ?? 0,
        'updatedAt' => $row['updatedAt'] ?? null,
        'delivery'  => $row['delivery'] ?? null,
    ];
}

usort($orders, function($a,$b){ return $b['orderId'] <=> $a['orderId']; });

echo json_encode([
    'success' => true,
    'email' => $customerEmail,
    'orders' => $orders,
]);
