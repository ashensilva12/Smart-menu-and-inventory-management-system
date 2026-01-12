<?php
require_once __DIR__ . '/session_check.php';

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

date_default_timezone_set('Asia/Colombo');
header('Content-Type: text/html; charset=UTF-8');

$itemId    = trim($_POST['itemID'] ?? '');
$itemName  = trim($_POST['itemname'] ?? '');
$category  = trim($_POST['category'] ?? '');
$unit      = trim($_POST['unit'] ?? '');
$stock     = trim($_POST['stock'] ?? '');
$supplier  = trim($_POST['supplier'] ?? '');
$notes     = trim($_POST['notes'] ?? '');

$hasMissing = ($itemId === '' || $itemName === '' || $category === '' || $unit === '' || $stock === '');
$invalidSupplier = ($supplier !== '' && !filter_var($supplier, FILTER_VALIDATE_EMAIL));

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
if ($hasMissing || !is_numeric($stock) || $stock <= 0 || $invalidSupplier) {
    ?>
    <script>
    Swal.fire({
        icon: 'warning',
        title: 'Invalid Request',
        text: <?php echo json_encode($invalidSupplier ? 'Supplier email is invalid.' : 'Please fill in all required fields with valid values.'); ?>,
        confirmButtonText: 'Back'
    }).then(() => window.history.back());
    </script>
    <?php
    echo '</body></html>';
    exit();
}

// Build message
$message  = "A new inventory order has been submitted.\n\n";
$message .= "Item ID: {$itemId}\n";
$message .= "Item Name: {$itemName}\n";
$message .= "Category: {$category}\n";
$message .= "Quantity Needed: {$stock} {$unit}\n";
if ($notes !== '') {
    $message .= "Notes: {$notes}\n";
}
$message .= "Submitted: " . date('Y-m-d H:i:s') . "\n";

// Send email if supplier provided
$emailSent = false;
$errorMsg  = '';

if ($supplier !== '' && filter_var($supplier, FILTER_VALIDATE_EMAIL)) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp-relay.brevo.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = '93b60b001@smtp-brevo.com';
        $mail->Password   = 'U0ES13KZ4mALxV5g';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('admin@ashenlakshitha.online', 'The Kings Menu');
        $mail->addAddress($supplier);
        $mail->addReplyTo('admin@ashenlakshitha.online', 'The Kings Menu');

        $mail->isHTML(false);
        $mail->Subject = 'Inventory Order Request';
        $mail->Body    = $message;

        // If mailer is a stub or SMTP fails, we still fall back to logging only
        $emailSent = $mailerReady ? $mail->send() : false;
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
    }
}

// Always log request for audit
$logLine = '[' . date('Y-m-d H:i:s') . "] {$itemId} | {$itemName} | {$category} | {$stock} {$unit} | Supplier: " . ($supplier ?: 'N/A') . " | Notes: " . ($notes ?: '-') . "\n";
file_put_contents(__DIR__ . '/inventory_orders.log', $logLine, FILE_APPEND);

if ($emailSent) {
    ?>
    <script>
    Swal.fire({
        icon: 'success',
        title: 'Order Sent',
        text: 'The supplier has been emailed successfully.',
        confirmButtonText: 'OK'
    }).then(() => window.location.href = 'inventory.html');
    </script>
    <?php
} else {
    $msg = $supplier === '' ? 'Saved locally (no supplier email provided).' : 'Saved locally. Email failed: ' . $errorMsg;
    ?>
    <script>
    Swal.fire({
        icon: 'info',
        title: 'Order Logged',
        text: <?php echo json_encode($msg); ?>,
        confirmButtonText: 'Back'
    }).then(() => window.location.href = 'inventory.html');
    </script>
    <?php
}
?>
</body>
</html>
<?php
ob_end_flush();
?>
