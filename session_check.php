<?php
// General guard: allow customer or admin session
session_start();
if (!isset($_SESSION['customer_email']) && !isset($_SESSION['admin_username'])) {
    header('Location: Loggin.html');
    exit();
}
// Prevent cached back-button access after logout
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
