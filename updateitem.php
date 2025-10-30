<?php
  $itemID = $_POST['itemID'] ?? '';
  $itemname = $_POST['itemname'] ?? '';
  $category = $_POST['category'] ?? '';
  $stock = $_POST['stock'] ?? '';

  $cri1 = $itemID != "";
  $cri2 = $itemname != "";
  $cri3 = $category != "";
  $cri4 = $stock != "";

  if (!$cri1 || !$cri2 || !$cri3 || !$cri4) exit();

  $con = new mysqli('localhost:6368', 'root', '1234', 'resturent');

  $data = "SELECT currentStock FROM invitems WHERE itemID='$itemID'";
  $result = $con->query($data);

  echo "<!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
  </head>
<body>";
    if ($result && $result->num_rows == 1) {
    $row = $result->fetch_assoc(); 
    $currentStock = (int)$row['currentStock'];
        if ($stock > $currentStock) {
        echo "
        <script>
          Swal.fire({
            icon: 'warning',
            title: 'Not Enough Stock',
            text: 'Item not enough to get. Please add this item first',
            confirmButtonText: 'OK'
          }).then(() => {
            window.location.href = 'updateitem.html';
          });
        </script>";
        } else {
        $newStock = $currentStock - $stock;
        $check = "UPDATE invitems SET currentStock='$newStock' WHERE category='$category' AND itemID='$itemID'";
        if ($con->query($check) === true) {
            echo "
            <script>
              Swal.fire({
                icon: 'success',
                title: 'Item Updated',
                text: 'Stock updated successfully.',
                confirmButtonText: 'OK'
              }).then(() => {
                window.location.href = 'updateitem.html';
              });
            </script>";
        } 
echo "</body></html>";
$con->close();
?>