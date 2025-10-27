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
        $mail->Password = 'U0ES13KZ4mALxV5g'; // App password, not your Gmail password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('admin@ashenlakshitha.online', 'Your Restaurant');
        $mail->addAddress('ashenlakshitha12@gmail.com'); // User's email

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Order Confirmation';
        $mail->Body    = 'Hello, thank you for your order!<br>We are preparing it right now.';

        
    }
?>
