<?php
    session_start();

    $Email = trim($_POST['email'] ?? '');
    $Password = trim($_POST['password'] ?? '');

    // If fields are empty, show SweetAlert2 popup and redirect
    if ($Email === '' || $Password === '') {

    exit();
    }
?>
