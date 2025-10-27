<?php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require 'vendor/autoload.php';

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp-relay.brevo.com'; // Gmail SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = '93b60b001@smtp-brevo.com'; // Your Gmail
    }
?>
