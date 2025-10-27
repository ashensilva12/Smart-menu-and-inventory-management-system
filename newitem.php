<?php
    $con = new mysqli('localhost:6368', 'root', '1234', 'resturent');

    // Check connection
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize POST data
    $name = $con->real_escape_string($_POST['item_name']);
    $price = floatval($_POST['item_price']);
    $description = $con->real_escape_string($_POST['item_description']);
    $category = $con->real_escape_string($_POST['item_category']);

    // Handle image upload
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
}
?>