<?php
session_start();
if (!isset($_SESSION['delivery_user'])) {
    header('Location: delivery_login.php');
    exit();
}
// Prevent cached back-button access after logout
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
