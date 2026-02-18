<?php
require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/smtp_contact.php';
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $subject = htmlspecialchars(trim($_POST['subject']));
    $message = htmlspecialchars(trim($_POST['message']));

    $mail = new PHPMailer(true);

        try {
        // SMTP configuration (contact-only)
        apply_contact_smtp_settings($mail);
        // Recipients
        $mail->addAddress(CONTACT_SMTP_TO);    // Admin inbox for contact form
        $mail->addReplyTo($email, $name);      // Allow direct replies

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
        $mail->send();

        echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
            Swal.fire({
                icon: 'success',
                title: 'Message Sent!',
                text: 'Thank you, we have received your message.',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = 'contactus.html';
            });
        </script>
        ";
    } catch (Exception $e) {
        echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Sending Failed',
                text: 'Mailer Error: " . $mail->ErrorInfo . "',
                confirmButtonText: 'Back'
            }).then(() => {
                window.history.back();
            });
        </script>
        ";
    }
}
?>
