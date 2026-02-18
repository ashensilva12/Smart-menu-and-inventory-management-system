<?php
// Central SMTP configuration used across the project.
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

// Load SMTP settings from environment to avoid committing secrets
const SMTP_HOST = getenv('SMTP_HOST') ?: '';
const SMTP_PORT = (int) (getenv('SMTP_PORT') ?: 587);
const SMTP_USER = getenv('SMTP_USER') ?: '';
const SMTP_PASS = getenv('SMTP_PASS') ?: '';
const SMTP_SECURE = getenv('SMTP_SECURE') ?: PHPMailer::ENCRYPTION_STARTTLS; // Use ENCRYPTION_SMTPS for port 465 if needed
const SMTP_FROM = getenv('SMTP_FROM') ?: '';
const SMTP_FROM_NAME = getenv('SMTP_FROM_NAME') ?: 'The Kings Menu';

function apply_smtp_settings(PHPMailer $mail): void {
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port       = SMTP_PORT;
    $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
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
