<?php
session_start();
if (!isset($_SESSION['admin_username'])) {
    header('Location: admin_login.html');
    exit();
}
// Prevent cached access after logout
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

readfile(__DIR__ . '/low_stock.html');
