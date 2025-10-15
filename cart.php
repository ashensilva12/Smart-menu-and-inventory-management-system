<?php
    session_start();

    // === SweetAlert2 HTML wrapper (non-destructive) ===
    // Runs ONLY for non-JSON requests (direct browser visit or normal form POST).
    // It renders a small HTML page that calls this same endpoint via fetch() with JSON,
    // then shows a SweetAlert2 popup and redirects back to menu.html.
    // Your original JSON behavior for AJAX remains unchanged.

    $__accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $__ctype  = $_SERVER['CONTENT_TYPE'] ?? '';
    $__is_json_request = (stripos($__accept, 'application/json') !== false) || (stripos($__ctype, 'application/json') !== false);

    if (!$__is_json_request) {
    // Rebuild a minimal payload from form POST if present (optional)
        $__payload = [
        'cart'     => [],
        'subtotal' => isset($_POST['subtotal']) ? (float)$_POST['subtotal'] : 0,
        'charge'   => isset($_POST['charge'])   ? (float)$_POST['charge']   : 0,
        'total'    => isset($_POST['total'])    ? (float)$_POST['total']    : 0,
    ];
?>

