<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$autoload = __DIR__ . '/vendor/autoload.php';
$mailerReady = true;
if (file_exists($autoload)) {
    require $autoload;
} else {
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

$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
$wantsJson = stripos($accept, 'application/json') !== false || ($_SERVER['CONTENT_TYPE'] ?? '') === 'application/json';

// Prefer JSON responses for API/AJAX callers
if ($wantsJson) {
    header('Content-Type: application/json');
}

// Accept POST (preferred) or fallback to defaults for manual hits
$to       = trim($_POST['to'] ?? 'admin@ashenlakshitha.online');
$subject  = trim($_POST['subject'] ?? 'Email test from The Kings Menu');
$body     = trim($_POST['body'] ?? 'Hello, thank you for your order! We are preparing it right now.');
$from     = trim($_POST['from'] ?? 'admin@ashenlakshitha.online');
$fromName = trim($_POST['fromName'] ?? 'The Kings Menu');

$error = null;
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    $error = 'Invalid recipient email';
}

if ($error === null) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp-relay.brevo.com';
        $mail->SMTPAuth = true;
        $mail->Username = '93b60b001@smtp-brevo.com';
        $mail->Password = 'U0ES13KZ4mALxV5g';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom($from, $fromName ?: 'The Kings Menu');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject !== '' ? $subject : 'Message from The Kings Menu';
        $mail->Body    = nl2br($body !== '' ? $body : '');
        $mail->AltBody = $body !== '' ? $body : 'Plain text message';

        $error = $mailerReady ? null : 'Mailer libraries missing (run composer install).';
        if ($mailerReady) {
            $mail->send();
        }
    } catch (Exception $e) {
        $error = $mail->ErrorInfo ?: $e->getMessage();
    }
}

// JSON response branch
if ($wantsJson) {
    echo json_encode([
        'success' => $error === null,
        'message' => $error === null ? 'Email sent' : 'Email failed',
        'error'   => $error,
    ]);
    exit;
}

// Human-friendly fallback with SweetAlert for direct browser visits
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Status</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<script>
    Swal.fire({
        icon: <?php echo $error === null ? "'success'" : "'error'"; ?>,
        title: <?php echo $error === null ? "'Email Sent'" : "'Email Failed'"; ?>,
        text: <?php echo json_encode($error === null ? 'Message sent successfully.' : $error); ?>,
        confirmButtonText: 'OK'
    }).then(() => {
        window.history.back();
    });
</script>
</body>
</html>
<?php
?>
