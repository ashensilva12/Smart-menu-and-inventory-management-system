<?php
require_once __DIR__ . '/admin_only.php';
header('Content-Type: text/html; charset=UTF-8');

$orderPage = __DIR__ . '/order.html';
if (!is_readable($orderPage)) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><body><h2>Order page missing</h2><p>File not found: order.html</p></body></html>';
    exit();
}

readfile($orderPage);
