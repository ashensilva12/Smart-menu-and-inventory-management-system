<?php
require_once __DIR__ . '/session_check.php';
  $itemID = trim($_POST['itemID'] ?? '');
  $itemname = trim($_POST['itemname'] ?? '');
  $category = trim($_POST['category'] ?? '');
  $stock = $_POST['stock'] ?? '';

  $valid = $itemID !== '' && $itemname !== '' && $category !== '' && $stock !== '' && is_numeric($stock);

  if (!$valid) exit();

  $stock = (float)$stock;

  // Default XAMPP MySQL uses root with no password; adjust if yours differs
  $con = new mysqli('localhost', 'root', '', 'resturent');

  $stmt = $con->prepare("SELECT currentStock FROM invitems WHERE itemID = ? AND category = ? LIMIT 1");
  $stmt->bind_param('ss', $itemID, $category);
  $stmt->execute();
  $result = $stmt->get_result();

  echo '<!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  </head>
<body>';
    if ($result && $result->num_rows == 1) {
    $row = $result->fetch_assoc(); 
    $currentStock = (float)$row['currentStock'];
      if ($stock > $currentStock) {
        echo "
        <script>
          Swal.fire({
            icon: 'warning',
            title: 'Not Enough Stock',
            text: 'Item not enough to get. Please add this item first',
            confirmButtonText: 'OK'
          }).then(() => {
            window.location.href = 'admin_updateitem.php';
          });
        </script>";
        } else {
        $newStock = $currentStock - $stock;
        $check = $con->prepare("UPDATE invitems SET currentStock = ? WHERE category = ? AND itemID = ?");
        $check->bind_param('dss', $newStock, $category, $itemID);
        if ($check->execute()) {
            echo "
            <script>
              Swal.fire({
                icon: 'success',
                title: 'Item Updated',
                text: 'Stock updated successfully.',
                confirmButtonText: 'OK'
              }).then(() => {
                window.location.href = 'admin_inventory.php';
              });
            </script>";
        }else {
            echo "
            <script>
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Something went wrong, please try again.',
                confirmButtonText: 'Retry'
              }).then(() => {
                window.location.href = 'admin_updateitem.php';
              });
            </script>";
        }
        $check->close();
    }
}  else {
    echo "
    <script>
      Swal.fire({
        icon: 'error',
        title: 'Item Not Found',
        text: 'Can’t find this item. Please add it first.',
        confirmButtonText: 'Add Item'
      }).then(() => {
        window.location.href = 'admin_additem.php';
      });
    </script>";
}
  $stmt->close();
echo "</body></html>";
$con->close();
?>