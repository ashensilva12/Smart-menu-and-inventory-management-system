<?php
require_once __DIR__ . '/session_check.php';
    // Default XAMPP MySQL uses root with no password on localhost
    $con = new mysqli('localhost', 'root', '', 'resturent');

    // Check connection
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize POST data
    $name = trim($_POST['item_name']);
    $price = floatval($_POST['item_price']);
    $description = trim($_POST['item_description']);
    $category = trim($_POST['item_category']);

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
        // Move uploaded file
    if (move_uploaded_file($imageFile['tmp_name'], $targetFilePath)) {
        // Insert item into DB
        $stmt = $con->prepare("INSERT INTO menu (item_name, item_price, item_description, item_category, item_image) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('sdsss', $name, $price, $description, $category, $targetFilePath);
        
        if ($stmt->execute()) {
            header("Location: admin_newitem.php?status=added");
            exit();
        } else {
            echo "Error inserting into database: " . $con->error;
        }
        $stmt->close();
    } else {
        echo "Sorry, there was an error uploading your image.";
    }
}
$con->close();
?>