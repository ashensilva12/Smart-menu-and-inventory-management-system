<?php
    session_start();

    // === SweetAlert2 HTML wrapper (non-destructive) ===
    // Runs ONLY for non-JSON requests (direct browser visit or normal form POST).
    // It renders a small HTML page that calls this same endpoint via fetch() with JSON,
    // then shows a SweetAlert2 popup and redirects back to menu.html.
    // Your original JSON behavior for AJAX remains unchanged.
?>
