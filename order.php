<?php
require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/smtp_config.php';

$autoload = __DIR__ . '/vendor/autoload.php';
$mailerReady = true;
if (file_exists($autoload)) {
    require $autoload;
} else {
    // Graceful fallback: define a stub mailer so the page still renders and IDE stops flagging undefined classes
    $mailerReady = false;
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        class StubPHPMailer {
            public const ENCRYPTION_STARTTLS = 'tls';
            public const ENCRYPTION_SMTPS = 'ssl';
            public function __construct($e = false) {}
            public function __call($name, $args) {}
            public function __set($n,$v) {}
            public $ErrorInfo = '';
            public function send(){ return false; }
        }
        class_alias('StubPHPMailer', 'PHPMailer\\PHPMailer\\PHPMailer');
    }
    if (!class_exists('PHPMailer\\PHPMailer\\Exception')) {
        class StubPHPMailerException extends \Exception {}
        class_alias('StubPHPMailerException', 'PHPMailer\\PHPMailer\\Exception');
    }
}
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;
use Dompdf\Options;

date_default_timezone_set('Asia/Colombo');
header('Content-Type: text/html; charset=UTF-8');

$itemIds    = $_POST['itemID'] ?? [];
$itemNames  = $_POST['itemname'] ?? [];
$categories = $_POST['category'] ?? [];
$units      = $_POST['unit'] ?? [];
$stocks     = $_POST['stock'] ?? [];
$supplier   = trim($_POST['supplier'] ?? '');
$notes      = trim($_POST['notes'] ?? '');

if (!is_array($itemIds))    { $itemIds = [$itemIds]; }
if (!is_array($itemNames))  { $itemNames = [$itemNames]; }
if (!is_array($categories)) { $categories = [$categories]; }
if (!is_array($units))      { $units = [$units]; }
if (!is_array($stocks))     { $stocks = [$stocks]; }

$items = [];
for ($i = 0; $i < count($itemIds); $i++) {
    $id   = trim($itemIds[$i] ?? '');
    $name = trim($itemNames[$i] ?? '');
    $cat  = trim($categories[$i] ?? '');
    $unit = trim($units[$i] ?? '');
    $qty  = trim($stocks[$i] ?? '');

    if ($id === '' && $name === '' && $cat === '' && $unit === '' && $qty === '') {
        continue; // skip empty rows
    }

    $items[] = [
        'id' => $id,
        'name' => $name,
        'cat' => $cat,
        'unit' => $unit,
        'qty' => $qty
    ];
}

$invalidSupplier = ($supplier === '' || !filter_var($supplier, FILTER_VALIDATE_EMAIL));
$hasMissing = empty($items);

foreach ($items as $item) {
    if ($item['id'] === '' || $item['name'] === '' || $item['cat'] === '' || $item['unit'] === '' || $item['qty'] === '' || !is_numeric($item['qty']) || $item['qty'] <= 0) {
        $hasMissing = true;
        break;
    }
}

ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php
if ($hasMissing || $invalidSupplier) {
    ?>
    <script>
    Swal.fire({
        icon: 'warning',
        title: 'Invalid Request',
        text: <?php echo json_encode($invalidSupplier ? 'Supplier email is invalid or missing.' : 'Please fill in all required fields with valid values.'); ?>,
        confirmButtonText: 'Back'
    }).then(() => window.history.back());
    </script>
    <?php
    echo '</body></html>';
    exit();
}

// Build message
$plainMessage  = "A new inventory order has been submitted.\n\n";
foreach ($items as $item) {
    $plainMessage .= "- {$item['name']} (ID: {$item['id']}), {$item['cat']}, Qty: {$item['qty']} {$item['unit']}\n";
}
if ($notes !== '') {
    $plainMessage .= "\nNotes: {$notes}\n";
}
$plainMessage .= "\nSubmitted: " . date('Y-m-d H:i:s') . "\n";

$htmlMessage  = "<h3>New inventory order</h3><ul>";
foreach ($items as $item) {
    $htmlMessage .= '<li><strong>' . htmlspecialchars($item['name'], ENT_QUOTES) . '</strong> (ID: ' . htmlspecialchars($item['id'], ENT_QUOTES) . '), ' . htmlspecialchars($item['cat'], ENT_QUOTES) . ', Qty: ' . htmlspecialchars($item['qty'], ENT_QUOTES) . ' ' . htmlspecialchars($item['unit'], ENT_QUOTES) . '</li>';
}
$htmlMessage .= '</ul>';
if ($notes !== '') {
    $htmlMessage .= '<p><strong>Notes:</strong> ' . nl2br(htmlspecialchars($notes, ENT_QUOTES)) . '</p>';
}
$htmlMessage .= '<p>Submitted: ' . htmlspecialchars(date('Y-m-d H:i:s'), ENT_QUOTES) . '</p>';

// Build PDF attachment if Dompdf is available
$pdfBuffer = null;
if (class_exists(Dompdf::class)) {
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);

    $pdfRows = '';
    foreach ($items as $item) {
        $pdfRows .= '<tr>'
            . '<td>' . htmlspecialchars($item['id'], ENT_QUOTES) . '</td>'
            . '<td>' . htmlspecialchars($item['name'], ENT_QUOTES) . '</td>'
            . '<td>' . htmlspecialchars($item['cat'], ENT_QUOTES) . '</td>'
            . '<td class="right">' . htmlspecialchars($item['qty'], ENT_QUOTES) . ' ' . htmlspecialchars($item['unit'], ENT_QUOTES) . '</td>'
            . '</tr>';
    }

    $pdfHtml = '<html><head><meta charset="UTF-8"><style>
        body{font-family:Arial,Helvetica,sans-serif;color:#1f2937;margin:0;padding:24px;background:#f9fafb;}
        .card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;box-shadow:0 8px 24px rgba(0,0,0,0.06);} 
        h1{margin:0 0 8px;font-size:22px;color:#111827;}
        .subtitle{color:#6b7280;margin:0 0 16px;font-size:12px;letter-spacing:0.04em;text-transform:uppercase;}
        table{width:100%;border-collapse:collapse;margin-top:12px;}
        th,td{padding:10px 12px;font-size:13px;}
        th{text-align:left;background:#f3f4f6;color:#374151;border-bottom:1px solid #e5e7eb;}
        td{border-bottom:1px solid #e5e7eb;}
        .right{text-align:right;}
        .notes{margin-top:14px;padding:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#1f2937;}
        .footer{margin-top:18px;font-size:12px;color:#6b7280;}
    </style></head><body>
    <div class="card">
        <h1>Inventory Order</h1>
        <div class="subtitle">The Kings Menu</div>
        <table>
            <thead><tr><th>Item ID</th><th>Item Name</th><th>Category</th><th class="right">Quantity</th></tr></thead>
            <tbody>' . $pdfRows . '</tbody>
        </table>';

    if ($notes !== '') {
        $pdfHtml .= '<div class="notes"><strong>Notes:</strong><br>' . nl2br(htmlspecialchars($notes, ENT_QUOTES)) . '</div>';
    }

    $pdfHtml .= '<div class="footer">Submitted: ' . htmlspecialchars(date('Y-m-d H:i:s'), ENT_QUOTES) . '</div>
    </div></body></html>';

    $dompdf->loadHtml($pdfHtml);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdfBuffer = $dompdf->output();
}

// Send email if supplier provided
$emailSent = false;
$errorMsg  = '';

if (!$invalidSupplier) {
    $mail = new PHPMailer(true);
    try {
        apply_smtp_settings($mail);
        $mail->addAddress($supplier);
        $mail->addReplyTo(SMTP_FROM, SMTP_FROM_NAME);

    $mail->isHTML(true);
    $mail->Subject = 'Inventory Order Request';
    $mail->Body    = $htmlMessage;
    $mail->AltBody = $plainMessage;

        if ($pdfBuffer !== null) {
            $mail->addStringAttachment($pdfBuffer, 'inventory-order.pdf', 'base64', 'application/pdf');
        }

        // If mailer is a stub or SMTP fails, we still fall back to logging only
        $emailSent = $mailerReady ? $mail->send() : false;
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
    }
}

// Always log request for audit
foreach ($items as $item) {
    $logLine = '[' . date('Y-m-d H:i:s') . "] {$item['id']} | {$item['name']} | {$item['cat']} | {$item['qty']} {$item['unit']} | Supplier: " . ($supplier ?: 'N/A') . " | Notes: " . ($notes ?: '-') . "\n";
    file_put_contents(__DIR__ . '/inventory_orders.log', $logLine, FILE_APPEND);
}

if ($emailSent) {
    ?>
    <script>
    Swal.fire({
        icon: 'success',
        title: 'Order Sent',
        text: 'The supplier has been emailed successfully.',
        confirmButtonText: 'OK'
    }).then(() => window.location.href = 'admin_order.php');
    </script>
    <?php
} else {
    $msg = $invalidSupplier ? 'Saved locally (supplier email missing or invalid).' : 'Saved locally. Email failed: ' . $errorMsg;
    ?>
    <script>
    Swal.fire({
        icon: 'info',
        title: 'Order Logged',
        text: <?php echo json_encode($msg); ?>,
        confirmButtonText: 'Back'
    }).then(() => window.location.href = 'admin_order.php');
    </script>
    <?php
}
?>
</body>
</html>
<?php
ob_end_flush();
?>
