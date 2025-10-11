<?php
    header('Content-Type: text/html');
    $con = new mysqli('localhost:6368', 'root', '1234', 'resturent');
    if ($con->connect_error) {
    die("<p class='error'>Database connection failed: " . $con->connect_error . "</p>");
}
?>