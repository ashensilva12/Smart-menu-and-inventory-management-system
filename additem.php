<?php
  $itemID = $_POST['itemID'] ?? '';
  $itemname = $_POST['itemname'] ?? '';
  $category = $_POST['category'] ?? '';
  $stock = $_POST['stock'] ?? '';
  $unit = $_POST['unit'] ?? '';

$cri1 = $itemID != "";
$cri2 = $itemname != "";
$cri3 = $category != "";
$cri4 = $stock != "";
$cri5 = $unit != "";

if (!$cri1 || !$cri2 || !$cri3 || !$cri4 || !$cri5) exit();

$con = new mysqli('localhost:6368', 'root', '1234', 'resturent');

$data = "INSERT INTO invitems(itemID, itemName, category, currentStock, unit, status) 
         VALUES('$itemID', '$itemname', '$category', '$stock', '$unit', 'in-stock')";

$check = "SELECT * FROM invitems WHERE category='$category' AND itemID='$itemID'";
$sql = $con->query($check);

// Output full HTML for SweetAlert to work:
echo "<!DOCTYPE html>
<html lang='en'>
<head>
  <meta charset='UTF-8'>
  <title>Processing...</title>
  <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
</head>
<body>";
?>