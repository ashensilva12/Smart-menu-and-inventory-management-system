<?php
header('Content-Type: application/json');

date_default_timezone_set('Asia/Colombo');

function load_statuses(): array {
    $file = __DIR__ . '/order_status.json';
    if (!file_exists($file)) return [];
    $json = file_get_contents($file);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function save_statuses(array $statuses): void {
    $file = __DIR__ . '/order_status.json';
    file_put_contents($file, json_encode($statuses, JSON_PRETTY_PRINT), LOCK_EX);
}

function normalize_phone(string $phone): string {
    return preg_replace('/\D+/', '', $phone);
}

function contacts_match(array $record, string $email, string $phone): bool {
    $recEmail = strtolower(trim($record['email'] ?? ''));
    $recPhone = normalize_phone((string)($record['phone'] ?? ''));

    $emailMatch = ($email !== '') && ($recEmail !== '') && (strtolower($email) === $recEmail);
    $phoneMatch = ($phone !== '') && ($recPhone !== '') && (normalize_phone($phone) === $recPhone);

    // Require at least one provided piece of contact info to match
    return $emailMatch || $phoneMatch;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$action = $input['action'] ?? 'lookup';
$orderId = isset($input['orderId']) ? (int)$input['orderId'] : 0;
$email = strtolower(trim($input['email'] ?? ''));
$phone = normalize_phone((string)($input['phone'] ?? ''));

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit;
}

if ($email === '' && $phone === '') {
    echo json_encode(['success' => false, 'message' => 'Please provide the email or phone used for the order']);
    exit;
}

$statuses = load_statuses();
if (!isset($statuses[$orderId])) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

$record = $statuses[$orderId];
if (!contacts_match($record, $email, $phone)) {
    echo json_encode(['success' => false, 'message' => 'Contact does not match this order']);
    exit;
}

if ($action === 'lookup') {
    echo json_encode([
        'success' => true,
        'order' => [
            'orderId'   => $orderId,
            'customer'  => $record['customer'] ?? 'Guest',
            'status'    => $record['status'] ?? 'placed',
            'orderType' => $record['orderType'] ?? 'Pickup',
            'total'     => $record['total'] ?? null,
            'updatedAt' => $record['updatedAt'] ?? null,
            'delivery'  => $record['delivery'] ?? null,
        ]
    ]);
    exit;
}

if ($action === 'confirm_delivered') {
    $now = date('c');
    if (!isset($record['delivery']) || !is_array($record['delivery'])) {
        $record['delivery'] = [
            'status' => $record['delivery']['status'] ?? 'pending',
            'updatedAt' => $now,
            'staffName' => $record['delivery']['staffName'] ?? '',
            'staffPhone' => $record['delivery']['staffPhone'] ?? '',
        ];
    }
    $record['delivery']['customerConfirmed'] = true;
    $record['delivery']['customerConfirmedAt'] = $now;
    if (empty($record['delivery']['staffName'])) {
        $record['delivery']['staffName'] = 'Customer confirmed';
    }
    if (!isset($record['delivery']['staffPhone'])) {
        $record['delivery']['staffPhone'] = '';
    }
    // Do NOT auto-complete here; delivery staff marks delivered to complete
    $record['updatedAt'] = $now;
    $statuses[$orderId] = $record;
    save_statuses($statuses);

    echo json_encode(['success' => true, 'orderId' => $orderId, 'status' => $record['status'] ?? 'placed']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
