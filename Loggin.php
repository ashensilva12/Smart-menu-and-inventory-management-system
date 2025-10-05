<?php
    session_start();

    $Email = trim($_POST['email'] ?? '');
    $Password = trim($_POST['password'] ?? '');

    // If fields are empty, show SweetAlert2 popup and redirect
    if ($Email === '' || $Password === '') {
    echo "
    <html><head>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head><body>
    <script>

    </script>
    </body></html>";
    exit();
    }
?>
