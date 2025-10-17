<?php
    session_start();

    // === SweetAlert2 HTML wrapper (non-destructive) ===
    // Runs ONLY for non-JSON requests (direct browser visit or normal form POST).
    // It renders a small HTML page that calls this same endpoint via fetch() with JSON,
    // then shows a SweetAlert2 popup and redirects back to menu.html.
    // Your original JSON behavior for AJAX remains unchanged.

    $__accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $__ctype  = $_SERVER['CONTENT_TYPE'] ?? '';
    $__is_json_request = (stripos($__accept, 'application/json') !== false) || (stripos($__ctype, 'application/json') !== false);

    if (!$__is_json_request) {
    // Rebuild a minimal payload from form POST if present (optional)
        $__payload = [
        'cart'     => [],
        'subtotal' => isset($_POST['subtotal']) ? (float)$_POST['subtotal'] : 0,
        'charge'   => isset($_POST['charge'])   ? (float)$_POST['charge']   : 0,
        'total'    => isset($_POST['total'])    ? (float)$_POST['total']    : 0,
    ];
    if (!empty($_POST['name']) && is_array($_POST['name'])) {
        $names  = (array)($_POST['name'] ?? []);
        $prices = (array)($_POST['price'] ?? []);
        $qtys   = (array)($_POST['quantity'] ?? []);
        $count  = max(count($names), count($prices), count($qtys));
        for ($i = 0; $i < $count; $i++) {
            $__payload['cart'][] = [
                'name'     => (string)($names[$i]  ?? ''),
                'price'    => (float) ($prices[$i] ?? 0),
                'quantity' => (int)   ($qtys[$i]   ?? 0),
            ];
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Processing Order…</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <style>.swal2-container{z-index:9999 !important}</style>
</head>
<body>
    <script>
    (async () => {
        // Payload from form POST (if any); otherwise this will post an empty object.
        const payload = <?php echo json_encode($__payload, JSON_UNESCAPED_SLASHES); ?>;
        const hasCart = Array.isArray(payload.cart) && payload.cart.length > 0;
        try {
            const res = await fetch(window.location.pathname + window.location.search, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(hasCart ? payload : {})
            });
            const data = await res.json().catch(() => ({}));
            const ok = (data && data.success === true);
            await Swal.fire({
                icon: ok ? 'success' : 'error',
                title: ok ? 'Order Placed!' : 'Checkout Status',
                text: (data && data.message) ? String(data.message) : (ok ? 'Success' : 'No data received or invalid response')
            });
                        // Redirect after popup
            window.location.href = 'menu.html';
        } catch (err) {
            await Swal.fire({
                icon: 'error',
                title: 'Network Error',
                text: String(err && err.message ? err.message : err)
            });
            window.location.href = 'menu.html';
        }
    })();
    </script>
</body>
</html>
<?php
    exit; // Prevent the JSON-only code below from running during non-JSON visits
}
// Enable error display for debugging (disable in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Log function
function log_error($msg) {
    file_put_contents(__DIR__ . '/cart_errors.log', date('Y-m-d H:i:s') . " - " . $msg . PHP_EOL, FILE_APPEND);
}
// Global error handlers
set_exception_handler(function($e) {
    log_error("Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server error"]);
    exit;
});
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    log_error("PHP Error [$errno] $errstr in $errfile:$errline");
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server error"]);
    exit;
});

// Check login (expects email in session)
if (empty($_SESSION['customer_email'])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}
$customerEmail = $_SESSION['customer_email'];

// Read JSON input
$input = file_get_contents('php://input');
if (empty($input)) {
    echo json_encode(["success" => false, "message" => "No data received"]);
    exit;
}
$data = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    log_error("JSON decode error: " . json_last_error_msg());
    echo json_encode([
        "success" => false,
        "message" => "Invalid data format",
        "error"   => json_last_error_msg()
    ]);
    exit;
}
if (empty($data['cart']) || !is_array($data['cart'])) {
    echo json_encode(["success" => false, "message" => "Cart is empty or invalid"]);
    exit;
}
// Validate totals
$cart     = $data['cart'];
$subtotal = filter_var($data['subtotal'] ?? 0, FILTER_VALIDATE_FLOAT);
$charge   = filter_var($data['charge']   ?? 0, FILTER_VALIDATE_FLOAT);
$total    = filter_var($data['total']    ?? 0, FILTER_VALIDATE_FLOAT);
if ($subtotal === false || $charge === false || $total === false) {
    echo json_encode(["success" => false, "message" => "Invalid numeric values"]);
    exit;
}
try {
    // -------- Build bill HTML (beautiful invoice) --------
    date_default_timezone_set('Asia/Colombo');
    $invoiceNo  = 'INV-' . date('Ymd-His');
    $orderDate  = date('F j, Y g:i A');
?>

