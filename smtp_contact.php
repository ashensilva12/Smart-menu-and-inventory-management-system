<?php
// SMTP settings dedicated to Contact Us form.
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

// Adjust these to match the mailbox that should receive Contact Us messages.
// Load Contact form SMTP settings from environment to avoid committing secrets
const CONTACT_SMTP_HOST = getenv('CONTACT_SMTP_HOST') ?: '';
const CONTACT_SMTP_PORT = (int) (getenv('CONTACT_SMTP_PORT') ?: 587);
const CONTACT_SMTP_USER = getenv('CONTACT_SMTP_USER') ?: '';
const CONTACT_SMTP_PASS = getenv('CONTACT_SMTP_PASS') ?: '';
const CONTACT_SMTP_SECURE = getenv('CONTACT_SMTP_SECURE') ?: PHPMailer::ENCRYPTION_STARTTLS; // Switch to ENCRYPTION_SMTPS + port 465 if needed
const CONTACT_SMTP_FROM = getenv('CONTACT_SMTP_FROM') ?: '';
const CONTACT_SMTP_FROM_NAME = getenv('CONTACT_SMTP_FROM_NAME') ?: 'The Kings Menu Contact';
const CONTACT_SMTP_TO = getenv('CONTACT_SMTP_TO') ?: '';

function apply_contact_smtp_settings(PHPMailer $mail): void {
    $mail->isSMTP();
    $mail->Host       = CONTACT_SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = CONTACT_SMTP_USER;
    $mail->Password   = CONTACT_SMTP_PASS;
    $mail->SMTPSecure = CONTACT_SMTP_SECURE;
    $mail->Port       = CONTACT_SMTP_PORT;
    $mail->setFrom(CONTACT_SMTP_FROM, CONTACT_SMTP_FROM_NAME);
    // Friendly defaults to avoid firewall false-positives and long hangs
    $mail->SMTPAutoTLS = true;
    $mail->Timeout = 10;            // seconds
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ];
}
