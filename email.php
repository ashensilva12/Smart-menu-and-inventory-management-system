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
        $mail->Username =  // Your Gmail
        $mail->Password =  // App password, not your Gmail password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('', 'Your Restaurant');
        $mail->addAddress(''); // User's email

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Order Confirmation';
        $mail->Body    = 'Hello, thank you for your order!<br>We are preparing it right now.';

        $mail->send();
        echo 'Email has been sent successfully.';
    } catch (Exception $e) {
        echo "Email could not be sent. Error: {$mail->ErrorInfo}";
}
?>
