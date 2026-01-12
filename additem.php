<?php
require_once __DIR__ . '/session_check.php';
  $itemID = trim($_POST['itemID'] ?? '');
  $itemname = trim($_POST['itemname'] ?? '');
  $category = trim($_POST['category'] ?? '');
  $stock = $_POST['stock'] ?? '';
  $unit = trim($_POST['unit'] ?? '');

$valid = $itemID !== '' && $itemname !== '' && $category !== '' && $unit !== '' && is_numeric($stock);

if (!$valid) {
    header('Location: admin_additem.php?status=invalid');
    exit();
}

$stock = (float)$stock;

// Default XAMPP MySQL uses root with no password; change here if you set one
$con = new mysqli('localhost', 'root', '', 'resturent');

// Insert or update existing item atomically
$check = $con->prepare("SELECT currentStock FROM invitems WHERE category = ? AND itemID = ? LIMIT 1");
$check->bind_param('ss', $category, $itemID);
$check->execute();
$result = $check->get_result();

$sql = $result;
if ($sql && $sql->num_rows == 1) {
    $row = $sql->fetch_assoc();
    $currentStock = (float)$row['currentStock'];
    $newStock = $currentStock + $stock;
    $update = $con->prepare("UPDATE invitems SET currentStock = ? WHERE category = ? AND itemID = ?");
    $update->bind_param('dss', $newStock, $category, $itemID);
    if ($update->execute()) {
        header('Location: admin_additem.php?status=updated');
        exit();
        } else {
        header('Location: admin_additem.php?status=update_error');
        exit();
            }
    $update->close();
} else {
    $insert = $con->prepare("INSERT INTO invitems(itemID, itemName, category, currentStock, unit, status) VALUES (?, ?, ?, ?, ?, 'In Stock')");
    $insert->bind_param('sssds', $itemID, $itemname, $category, $stock, $unit);
    if ($insert->execute()) {
        header('Location: admin_additem.php?status=added');
        exit();
    } else {
        header('Location: admin_additem.php?status=add_error');
        exit();
    }
    $insert->close();
}
$con->close();
?>