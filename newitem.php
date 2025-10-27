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
    $imageFile = $_FILES['item_image'];
    $imageName = basename($imageFile['name']);
    $targetFilePath = $targetDir . time() . '_' . $imageName;
    $imageFileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

    // Validate image file type
    $allowedTypes = ['jpg', 'jpeg', 'png'];
    if (!in_array($imageFileType, $allowedTypes)) {
        die("Sorry, only JPG, JPEG, PNG files are allowed.");
    }
}
?>