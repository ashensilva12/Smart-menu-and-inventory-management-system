<?php
require_once __DIR__ . '/session_check.php';
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
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
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

// Helpers for simple status persistence (JSON file)
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

$cart = $data['cart'];

// Customer / order metadata (guest checkout)
$customer = is_array($data['customer'] ?? null) ? $data['customer'] : [];
$customerName = trim($customer['name'] ?? '');
$customerEmail = filter_var($customer['email'] ?? '', FILTER_VALIDATE_EMAIL);
$customerPhone = trim($customer['phone'] ?? '');
$orderType = ($customer['orderType'] ?? '') === 'Delivery' ? 'Delivery' : 'Pickup';
$customerAddress = trim($customer['address'] ?? '');
$customerNotes = trim($customer['notes'] ?? '');
$promoCode = strtoupper(trim($customer['promoCode'] ?? ''));

if ($customerName === '' || !$customerEmail || $customerPhone === '') {
  echo json_encode(["success" => false, "message" => "Missing customer details (name, email, phone)"]);
  exit;
}
if ($orderType === 'Delivery' && $customerAddress === '') {
  echo json_encode(["success" => false, "message" => "Delivery address is required"]);
  exit;
}

// Validate items and recompute totals server-side
$calculatedSubtotal = 0.0;
$itemCount = 0;
foreach ($cart as $item) {
  if (!isset($item['name'], $item['price'], $item['quantity'])) {
    echo json_encode(["success" => false, "message" => "Invalid item format"]);
    exit;
  }
  $itemPrice = filter_var($item['price'], FILTER_VALIDATE_FLOAT);
  $itemQty   = filter_var($item['quantity'], FILTER_VALIDATE_INT);
  if ($itemPrice === false || $itemQty === false || $itemQty <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid item price or quantity"]);
    exit;
  }
  $calculatedSubtotal += ($itemPrice * $itemQty);
  $itemCount += $itemQty;
}

$promoRates = [ 'SAVE10' => 0.10 ];
$promoRate = $promoRates[$promoCode] ?? 0;
$promoDiscount = $calculatedSubtotal * $promoRate;
$deliveryFee = $orderType === 'Delivery' ? 200 : 0;
$charge = max(0, ($calculatedSubtotal - $promoDiscount) * 0.10);
$calculatedTotal = max(0, $calculatedSubtotal - $promoDiscount + $deliveryFee + $charge);

// Use server-side totals to prevent tampering
$subtotal = $calculatedSubtotal;
$total = $calculatedTotal;

try {
    // -------- Build bill HTML (beautiful invoice) --------
    date_default_timezone_set('Asia/Colombo');
    $invoiceNo  = 'INV-' . date('Ymd-His');
    $orderDate  = date('F j, Y g:i A');

    $itemsRows = '';
    $safeName = htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars($customerEmail, ENT_QUOTES, 'UTF-8');
    $safePhone = htmlspecialchars($customerPhone, ENT_QUOTES, 'UTF-8');
    $safeAddress = htmlspecialchars($customerAddress, ENT_QUOTES, 'UTF-8');
    $safeNotes = htmlspecialchars($customerNotes, ENT_QUOTES, 'UTF-8');
    $safeType = htmlspecialchars($orderType, ENT_QUOTES, 'UTF-8');
    foreach ($cart as $item) {
        if (!isset($item['name'], $item['price'], $item['quantity'])) {
            throw new Exception("Invalid item format in cart");
        }
        $itemName  = htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8');
        $itemPrice = filter_var($item['price'], FILTER_VALIDATE_FLOAT);
        $itemQty   = filter_var($item['quantity'], FILTER_VALIDATE_INT);
        if ($itemPrice === false || $itemQty === false) {
            throw new Exception("Invalid item price or quantity");
        }
        $itemTotal = $itemPrice * $itemQty;

        $itemsRows .= sprintf(
            '<tr>
                <td class="c-name">%s</td>
                <td class="c-price">Rs.%s</td>
                <td class="c-qty">%d</td>
                <td class="c-total">Rs.%s</td>
             </tr>',
            $itemName,
            number_format($itemPrice, 2),
            $itemQty,
            number_format($itemTotal, 2)
        );
    }

    $html = '
    <!DOCTYPE html>
    <html>
    <head>
    <meta charset="utf-8">
    <style>
      @page { margin: 32px 28px 56px 28px; }
      body { font-family: Arial, Helvetica, sans-serif; color:#222; font-size:12px; }
      .brand-bar {
        background:#111827; color:#fff; padding:14px 16px; border-radius:10px;
      }
      .brand-title { font-size:18px; letter-spacing:0.3px; font-weight:bold; }
      .brand-sub { font-size:11px; opacity:.9; margin-top:2px; }
      .header {
        margin-top:14px; display:flex; justify-content:space-between; align-items:flex-start;
      }
      .left, .right { width:48%; }
      .box {
        border:1px solid #e5e7eb; border-radius:10px; padding:12px 14px; background:#fafafa;
      }
      .muted { color:#6b7280; }
      .kv { margin:2px 0; }
      .kv strong { display:inline-block; width:100px; }
      .table-wrap { margin-top:16px; }
      table { width:100%; border-collapse:collapse; }
      thead th {
        background:#f3f4f6; font-weight:700; text-transform:uppercase; font-size:11px;
        border-bottom:1px solid #e5e7eb; padding:8px 8px;
      }
      tbody td { border-bottom:1px solid #f1f5f9; padding:8px 8px; vertical-align:top; }
      tbody tr:nth-child(odd) { background:#fcfcfd; }
      .c-name { width:52%; }
      .c-price, .c-qty, .c-total { text-align:right; white-space:nowrap; }
      .summary {
        margin-top:14px; display:flex; justify-content:flex-end;
      }
      .totals {
        width:55%; max-width:320px; border:1px solid #e5e7eb; border-radius:10px; padding:10px 12px;
        background:#ffffff;
      }
      .row { display:flex; justify-content:space-between; margin:6px 0; }
      .row.total { font-weight:700; font-size:13px; border-top:1px dashed #e5e7eb; padding-top:8px; margin-top:8px; }
      .thanks {
        margin-top:20px; text-align:center; color:#4b5563; font-size:12px;
      }
      .footer {
        position:fixed; left:0; right:0; bottom:12px; text-align:center; font-size:10px; color:#6b7280;
      }
    </style>
    </head>
    <body>

      <div class="brand-bar">
        <div class="brand-title">The Kings Menu</div>
        <div class="brand-sub">Good food • Good mood</div>
      </div>

      <div class="header">
        <div class="left box">
          <div class="kv"><strong>Invoice No:</strong> '.$invoiceNo.'</div>
          <div class="kv"><strong>Date:</strong> '.$orderDate.'</div>
          <div class="kv"><strong>Name:</strong> '.$safeName.'</div>
          <div class="kv"><strong>Email:</strong> '.$safeEmail.'</div>
          <div class="kv"><strong>Phone:</strong> '.$safePhone.'</div>
          <div class="kv"><strong>Type:</strong> '.$safeType.'</div>
          <div class="kv"><strong>Address:</strong> '.($safeType === 'Delivery' ? ($safeAddress ?: 'N/A') : 'Pickup').'</div>
        </div>
        <div class="right box">
          <div class="muted" style="margin-bottom:6px;">Billed By</div>
          <div><strong>The Kings Menu Pvt Ltd</strong></div>
          <div>No.194 Wadduwa, Kaluthara</div>
          <div>admin@ashenlakshitha.online • +94 704865159</div>
        </div>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th class="c-name">Item</th>
              <th class="c-price">Price</th>
              <th class="c-qty">Qty</th>
              <th class="c-total">Total</th>
            </tr>
          </thead>
          <tbody>
            '.$itemsRows.'
          </tbody>
        </table>
      </div>

      <div class="summary">
        <div class="totals">
          <div class="row"><div>Subtotal</div><div>Rs.'.number_format($subtotal, 2).'</div></div>
          <div class="row"><div>Promo Savings</div><div>-Rs.'.number_format($promoDiscount, 2).'</div></div>
          <div class="row"><div>Delivery / Pickup</div><div>Rs.'.number_format($deliveryFee, 2).'</div></div>
          <div class="row"><div>Service Charge (10%)</div><div>Rs.'.number_format($charge, 2).'</div></div>
          <div class="row total"><div>Total</div><div>Rs.'.number_format($total, 2).'</div></div>
        </div>
      </div>

      <div class="thanks">🍽️ Thank you for your order! We hope to serve you again soon.</div>
      <div class="thanks" style="margin-top:6px;">Notes: '.($safeNotes ?: '—').'</div>

      <div class="footer">
        The Kings Menu • admin@ashenlakshitha.online
      </div>

      <!-- dompdf page numbers -->
      <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont("Helvetica", "normal");
            $pdf->page_text(520, 815, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, 9, array(0,0,0));
        }
      </script>

    </body>
    </html>';

    // -------- Generate PDF (Dompdf) --------
    require_once __DIR__ . '/vendor/autoload.php';
    $dompdf = new Dompdf\Dompdf();
    // Allow remote images if you add a logo later
    $dompdf->set_option('isRemoteEnabled', true);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $tempDir = sys_get_temp_dir();
    $pdfFile = tempnam($tempDir, 'bill_') . '.pdf';
    file_put_contents($pdfFile, $dompdf->output());

    // -------- Send email (PHPMailer) --------
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = '';
        $mail->SMTPAuth   = true;
        $mail->Username   = '';
        $mail->Password   = '';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('', 'The Kings Menu');
        $mail->addAddress($customerEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Your Order Bill - The Kings Menu';
        $mail->Body    = '<p>Thank you for your order!</p><p>Your bill is attached.</p>';
        $mail->addAttachment($pdfFile, 'OrderBill.pdf');

        $mail->send();
    } catch (Exception $e) {
        throw new Exception("Email sending failed: " . $e->getMessage());
    } finally {
        if (file_exists($pdfFile)) unlink($pdfFile);
    }

    // -------- Save order to MySQL (lookup name from `customer`) --------
    // Tables:
    //   customer(name VARCHAR(50) PK, email VARCHAR(70), password VARCHAR(20))
    //   orders(orderID INT AI PK, customer VARCHAR(50), items INT, total DOUBLE)

    $DB_HOST = 'localhost:6368';
    $DB_USER = 'root';
    $DB_PASS = '1234';
    $DB_NAME = 'resturent';

    $mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    if ($mysqli->connect_errno) {
        log_error("DB connect error: " . $mysqli->connect_error);
    } else {
        $mysqli->set_charset('utf8mb4');

      $dbCustomerName = $customerName;
      if ($stmt = $mysqli->prepare("SELECT name FROM customer WHERE email = ? LIMIT 1")) {
            $stmt->bind_param("s", $customerEmail);
            if ($stmt->execute()) {
                $stmt->bind_result($foundName);
          if ($stmt->fetch() && $foundName) $dbCustomerName = $foundName;
            }
            $stmt->close();
        }

      if ($itemCount > 0 && $total > 0) {
        if ($stmt = $mysqli->prepare("INSERT INTO orders (customer, items, total) VALUES (?, ?, ?)")) {
          $stmt->bind_param("sid", $dbCustomerName, $itemCount, $total);
          if (!$stmt->execute()) {
            log_error("Insert failed: " . $stmt->error);
          } else {
            $orderId = $stmt->insert_id;
            $statuses = load_statuses();
            $statuses[$orderId] = [
              'orderId' => $orderId,
              'customer' => $dbCustomerName,
              'status' => 'placed',
              'orderType' => $orderType,
              'email' => $customerEmail,
              'phone' => $customerPhone,
              'address' => $customerAddress,
              'total' => $total,
              'updatedAt' => date('c')
            ];
            save_statuses($statuses);
          }
          $stmt->close();
            } else {
                log_error("Prepare failed: " . $mysqli->error);
            }
        } else {
        log_error("Skip insert: invalid items/total (items=$itemCount, total=$total)");
        }

        $mysqli->close();
    }

    // -------- Success JSON (menu.html will show SweetAlert) --------
    echo json_encode([
      "success" => true,
      "message" => "Order placed successfully! Bill sent to $customerEmail",
      "orderId" => isset($orderId) ? $orderId : null,
      "status" => 'placed'
    ]);

} catch (Exception $e) {
    log_error("Checkout processing error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Order processing failed",
        "error"   => $e->getMessage()
    ]);
    exit;
}
