<?php
session_start();
header('Content-Type: application/json');

$isAdmin  = isset($_SESSION['admin_username']);
$isDriver = isset($_SESSION['delivery_user']) && is_array($_SESSION['delivery_user']);
$driverId = $isDriver ? (int)($_SESSION['delivery_user']['id'] ?? 0) : 0;

function error_out($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function require_admin() {
    global $isAdmin;
    if (!$isAdmin) error_out('Admin access required', 403);
}

function require_driver_or_admin() {
    global $isAdmin, $isDriver;
    if (!$isAdmin && !$isDriver) error_out('Not authenticated', 403);
}

// --- Storage helpers ------------------------------------------------------
function load_json($path) {
    if (!file_exists($path)) return [];
    $raw = file_get_contents($path);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function save_json($path, $data) {
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
}

function staff_file() {
    return __DIR__ . '/delivery_staff.json';
}

function load_staff() {
    $staff = load_json(staff_file());
    if (!is_array($staff)) $staff = [];
    return $staff;
}

function save_staff($staff) {
    save_json(staff_file(), $staff);
}

function seed_staff_if_empty() {
    $file = staff_file();
    if (file_exists($file) && filesize($file) > 0) return;
    $seed = [
        [
            'id' => 1,
            'name' => 'Demo Driver',
            'phone' => '0700000000',
            'username' => 'driver1',
            'status' => 'active',
            'passwordHash' => password_hash('driver123', PASSWORD_DEFAULT),
            'createdAt' => date('c')
        ]
    ];
    save_json($file, $seed);
}

function statuses_file() {
    return __DIR__ . '/order_status.json';
}

function load_statuses_safe() {
    return load_json(statuses_file());
}

function save_statuses_safe($data) {
    save_json(statuses_file(), $data);
}

// --- DB helper ------------------------------------------------------------
function db_connect() {
    $dbHost = getenv('DB_HOST') ?: 'localhost';
    $dbPort = getenv('DB_PORT') ?: 3306;
    $dbUser = getenv('DB_USER') ?: 'root';
    $dbPass = getenv('DB_PASS');
    if ($dbPass === false) $dbPass = '';
    $dbName = getenv('DB_NAME') ?: 'resturent';
    $con = @new mysqli($dbHost, $dbUser, $dbPass, $dbName, (int)$dbPort);
    if ($con->connect_error) return null;
    return $con;
}

function fetch_orders_with_status() {
    $statuses = load_statuses_safe();
    $orders = [];

    $con = db_connect();
    if ($con) {
        // Ensure table exists
        $tableExists = false;
        if ($stmt = $con->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = 'orders'")) {
            $dbName = getenv('DB_NAME') ?: 'resturent';
            $stmt->bind_param('s', $dbName);
            if ($stmt->execute()) {
                $stmt->bind_result($cnt);
                if ($stmt->fetch() && $cnt > 0) $tableExists = true;
            }
            $stmt->close();
        }

        if ($tableExists) {
            $sql = "SELECT orderID, customer, items, total FROM orders ORDER BY orderID DESC";
            if ($res = $con->query($sql)) {
                while ($row = $res->fetch_assoc()) {
                    $oid = (int)$row['orderID'];
                    $st = $statuses[$oid] ?? [];
                    $orders[$oid] = [
                        'orderID' => $oid,
                        'customer' => $row['customer'] ?? ($st['customer'] ?? ''),
                        'items' => isset($row['items']) ? (int)$row['items'] : ($st['items'] ?? 0),
                        'total' => isset($row['total']) ? (float)$row['total'] : ($st['total'] ?? 0),
                        'status' => $st['status'] ?? 'placed',
                        'orderType' => $st['orderType'] ?? 'Pickup',
                        'updatedAt' => $st['updatedAt'] ?? null,
                        'email' => $st['email'] ?? '',
                        'phone' => $st['phone'] ?? '',
                        'address' => $st['address'] ?? '',
                        'delivery' => $st['delivery'] ?? null
                    ];
                }
                $res->close();
            }
        }
        $con->close();
    }

    // Add any statuses not present in DB list (edge cases) preserving existing delivery data
    foreach ($statuses as $oid => $st) {
        if (isset($orders[$oid])) continue;
        $orders[$oid] = [
            'orderID' => (int)$oid,
            'customer' => $st['customer'] ?? '',
            'items' => $st['items'] ?? 0,
            'total' => $st['total'] ?? 0,
            'status' => $st['status'] ?? 'placed',
            'orderType' => $st['orderType'] ?? 'Pickup',
            'updatedAt' => $st['updatedAt'] ?? null,
            'email' => $st['email'] ?? '',
            'phone' => $st['phone'] ?? '',
            'address' => $st['address'] ?? '',
            'delivery' => $st['delivery'] ?? null
        ];
    }

    // Sort by orderID desc
    usort($orders, function($a, $b) { return $b['orderID'] <=> $a['orderID']; });
    return $orders;
}

// --- Actions --------------------------------------------------------------
$action = $_GET['action'] ?? $_POST['action'] ?? 'list_orders';
$allowedDeliveryStatuses = ['pending','out_for_delivery','delivered','cancelled'];

seed_staff_if_empty();

switch ($action) {
    case 'list_staff':
        require_admin();
        echo json_encode(['success' => true, 'staff' => load_staff()]);
        break;

    case 'add_staff':
        require_admin();
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) error_out('Invalid JSON');
        $name = trim($input['name'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';
        if ($name === '' || $username === '' || $password === '') error_out('Name, username, and password are required');

        $staff = load_staff();
        foreach ($staff as $row) {
            if (strcasecmp($row['username'], $username) === 0) error_out('Username already exists');
        }
        $nextId = 1 + max(array_column($staff ?: [['id'=>0]], 'id'));
        $staff[] = [
            'id' => $nextId,
            'name' => $name,
            'phone' => $phone,
            'username' => $username,
            'status' => 'active',
            'passwordHash' => password_hash($password, PASSWORD_DEFAULT),
            'createdAt' => date('c')
        ];
        save_staff($staff);
        echo json_encode(['success' => true, 'id' => $nextId]);
        break;

    case 'list_orders':
        require_driver_or_admin();
        $orders = fetch_orders_with_status();
        echo json_encode(['success' => true, 'orders' => $orders]);
        break;

    case 'assign':
        require_admin();
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) error_out('Invalid JSON');
        $orderId = isset($input['orderId']) ? (int)$input['orderId'] : 0;
        $staffId = isset($input['staffId']) ? (int)$input['staffId'] : 0;
        if ($orderId <= 0 || $staffId <= 0) error_out('Order and staff are required');

        $staff = load_staff();
        $staffRow = null;
        foreach ($staff as $s) {
            if ((int)$s['id'] === $staffId) { $staffRow = $s; break; }
        }
        if (!$staffRow) error_out('Staff not found');

        $statuses = load_statuses_safe();
        if (!isset($statuses[$orderId])) $statuses[$orderId] = ['orderId' => $orderId];
        $statuses[$orderId]['delivery'] = [
            'staffId' => $staffId,
            'staffName' => $staffRow['name'] ?? 'Unknown',
            'staffPhone' => $staffRow['phone'] ?? '',
            'status' => 'pending',
            'updatedAt' => date('c')
        ];
        save_statuses_safe($statuses);
        echo json_encode(['success' => true, 'orderId' => $orderId, 'delivery' => $statuses[$orderId]['delivery']]);
        break;

    case 'update_delivery_status':
        require_driver_or_admin();
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) error_out('Invalid JSON');
        $orderId = isset($input['orderId']) ? (int)$input['orderId'] : 0;
        $newStatus = strtolower(trim($input['status'] ?? ''));
        if ($orderId <= 0 || !in_array($newStatus, $allowedDeliveryStatuses, true)) error_out('Invalid order or status');

        $statuses = load_statuses_safe();
        if (!isset($statuses[$orderId]['delivery'])) error_out('Order not assigned to delivery yet');

        // Driver can only update own assignments
        global $isAdmin, $driverId;
        if (!$isAdmin) {
            $assignedId = (int)($statuses[$orderId]['delivery']['staffId'] ?? 0);
            if (!$driverId || $assignedId !== $driverId) error_out('Not allowed for this order', 403);
        }

        $statuses[$orderId]['delivery']['status'] = $newStatus;
        $statuses[$orderId]['delivery']['updatedAt'] = date('c');
        // Preserve staff phone if it was added later
        if (empty($statuses[$orderId]['delivery']['staffPhone'])) {
            $staff = load_staff();
            foreach ($staff as $s) {
                if ((int)($statuses[$orderId]['delivery']['staffId'] ?? 0) === (int)$s['id']) {
                    $statuses[$orderId]['delivery']['staffPhone'] = $s['phone'] ?? '';
                    break;
                }
            }
        }

        // Sync main order status for admin dashboard
        if ($newStatus === 'out_for_delivery') {
            $statuses[$orderId]['status'] = 'ready';
            $statuses[$orderId]['updatedAt'] = date('c');
        } elseif ($newStatus === 'delivered') {
            $statuses[$orderId]['status'] = 'completed';
            $statuses[$orderId]['updatedAt'] = date('c');
        }

        // If marked delivered, also mark the main order status as completed
        if ($newStatus === 'delivered') {
            $statuses[$orderId]['status'] = 'completed';
            $statuses[$orderId]['updatedAt'] = date('c');
        }
        save_statuses_safe($statuses);
        echo json_encode(['success' => true, 'orderId' => $orderId, 'delivery' => $statuses[$orderId]['delivery']]);
        break;

    case 'list_my_orders':
        if (!$isDriver) error_out('Driver access required', 403);
        $orders = array_values(array_filter(fetch_orders_with_status(), function($o) use ($driverId) {
            $d = $o['delivery'] ?? null;
            return $d && isset($d['staffId']) && (int)$d['staffId'] === $driverId;
        }));
        echo json_encode(['success' => true, 'orders' => $orders]);
        break;

    default:
        error_out('Unknown action');
}
