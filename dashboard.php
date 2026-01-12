<?php
// Allow env overrides; fall back to local defaults
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = getenv('DB_PORT') ?: 3306;
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS');
if ($dbPass === false) {
    // XAMPP default root password is usually blank; override via DB_PASS if set
    $dbPass = '';
}
$dbName = getenv('DB_NAME') ?: 'resturent';

$con = new mysqli($dbHost, $dbUser, $dbPass, $dbName, (int)$dbPort);
if ($con->connect_error) {
    die("<tr><td colspan='4'>DB error: " . htmlspecialchars($con->connect_error) . "</td></tr>");
}

// Ensure orders table exists (minimal schema); avoid tablespace errors if it already exists
$tableExists = false;
if ($stmt = $con->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = 'orders'")) {
    $stmt->bind_param('s', $dbName);
    if ($stmt->execute()) {
        $stmt->bind_result($cnt);
        if ($stmt->fetch() && $cnt > 0) {
            $tableExists = true;
        }
    }
    $stmt->close();
}

if (!$tableExists) {
    $createSql = "CREATE TABLE IF NOT EXISTS orders (
        orderID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        customer VARCHAR(50) DEFAULT NULL,
        items INT DEFAULT NULL,
        total DOUBLE DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    try {
        $con->query($createSql);
        $tableExists = true;
    } catch (mysqli_sql_exception $e) {
        $err = $e->getMessage();
        if (stripos($err, 'tablespace') === false) {
            die("<tr><td colspan='4'>DB error: " . htmlspecialchars($err) . "</td></tr>");
        }
        // Otherwise ignore tablespace warning; proceed but mark as missing
    }
}

if (!$tableExists) {
    // Graceful fallback message instead of fatal error
    echo "<tr><td colspan='4'>Orders table is unavailable. Please fix tablespace or create it manually.</td></tr>";
    $con->close();
    exit();
}

// Clear orders when requested (admin UI)
if (isset($_GET['clear'])) {
    if ($con->query("TRUNCATE TABLE orders") === true) {
        echo "Orders cleared";
    } else {
        echo "Failed to clear orders";
    }
    $con->close();
    exit();
}

$sql = "SELECT orderID, customer, items, total FROM orders ORDER BY orderID DESC";
$result = $con->query($sql);
if ($result === false) {
    echo "<tr><td colspan='4'>Query failed: " . htmlspecialchars($con->error) . "</td></tr>";
    $con->close();
    exit();
}

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orderId = htmlspecialchars($row['orderID']);
        $customer = htmlspecialchars($row['customer']);
        $items = htmlspecialchars($row['items']);
        $total = htmlspecialchars($row['total']);
        echo "<tr>
                <td>{$orderId}</td>
                <td>{$customer}</td>
                <td>{$items}</td>
                <td>{$total}</td>
            </tr>";
    }
} else {
    echo "<tr><td colspan='4'>No orders found.</td></tr>";
}

$con->close();
?>