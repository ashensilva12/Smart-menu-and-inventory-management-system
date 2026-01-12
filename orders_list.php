<?php
require_once __DIR__ . '/admin_only.php';
header('Content-Type: application/json');

function load_statuses() {
    $file = __DIR__ . '/order_status.json';
    if (!file_exists($file)) return [];
    $json = file_get_contents($file);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

$statuses = load_statuses();

// Allow env overrides; default to local XAMPP
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = getenv('DB_PORT') ?: 3306;
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS');
if ($dbPass === false) {
    $dbPass = '';
}
$dbName = getenv('DB_NAME') ?: 'resturent';

$con = @new mysqli($dbHost, $dbUser, $dbPass, $dbName, (int)$dbPort);
if ($con->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $con->connect_error]);
    exit;
}

// If orders table is missing, return empty list gracefully
$tableExists = false;
if ($stmt = $con->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = 'orders'")) {
    $stmt->bind_param('s', $dbName);
    if ($stmt->execute()) {
        $stmt->bind_result($cnt);
        if ($stmt->fetch() && $cnt > 0) $tableExists = true;
    }
    $stmt->close();
}

if (!$tableExists) {
    echo json_encode(['success' => true, 'orders' => [], 'message' => 'Orders table not found']);
    $con->close();
    exit;
}

$orders = [];
$sql = "SELECT orderID, customer, items, total FROM orders ORDER BY orderID DESC";
if ($result = $con->query($sql)) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $oid = (int)$row['orderID'];
            $orders[] = [
                'orderID' => $oid,
                'customer' => $row['customer'],
                'items' => (int)$row['items'],
                'total' => (float)$row['total'],
                'status' => $statuses[$oid]['status'] ?? 'placed',
                'orderType' => $statuses[$oid]['orderType'] ?? 'Pickup',
                'updatedAt' => $statuses[$oid]['updatedAt'] ?? null
            ];
        }
    }
    $result->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Query failed: ' . $con->error]);
    $con->close();
    exit;
}

$con->close();

echo json_encode(['success' => true, 'orders' => $orders]);
