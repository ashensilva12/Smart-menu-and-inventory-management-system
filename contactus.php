<?php
    require 'vendor/autoload.php';
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $subject = htmlspecialchars(trim($_POST['subject']));
    $message = htmlspecialchars(trim($_POST['message']));

    $mail = new PHPMailer(true);

        try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp-relay.brevo.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = '93b60b001@smtp-brevo.com';           // Your Gmail
        $mail->Password   = 'U0ES13KZ4mALxV5g';             // Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;


        
        // Recipients
        $mail->setFrom('admin@ashenlakshitha.online', 'Kings Menu Contact');
        $mail->addAddress('ashenlakshitha12@gmail.com');    // Admin email

        // Content
        $mail->isHTML(true);
        $mail->Subject = "New Contact Message: $subject";
        $mail->Body    = "
            <h3>New message received</h3>
            <p><strong>Name:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Subject:</strong> $subject</p>
            <p><strong>Message:</strong><br>$message</p>
        ";

?>
