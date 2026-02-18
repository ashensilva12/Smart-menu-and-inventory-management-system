<?php
require_once __DIR__ . '/session_check.php';

$itemIDs   = $_POST['itemID'] ?? [];
$itemNames = $_POST['itemname'] ?? [];
$categories = $_POST['category'] ?? [];
$stocks    = $_POST['stock'] ?? [];

if (!is_array($itemIDs)) $itemIDs = [$itemIDs];
if (!is_array($itemNames)) $itemNames = [$itemNames];
if (!is_array($categories)) $categories = [$categories];
if (!is_array($stocks)) $stocks = [$stocks];

$items = [];
for ($i = 0; $i < count($itemIDs); $i++) {
    $id = trim($itemIDs[$i] ?? '');
    $name = trim($itemNames[$i] ?? '');
    $cat = trim($categories[$i] ?? '');
    $qty = trim($stocks[$i] ?? '');

    if ($id === '' && $name === '' && $cat === '' && $qty === '') {
        continue; // skip blank rows
    }

    if ($id === '' || $cat === '' || $qty === '' || !is_numeric($qty) || $qty <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid item data.']);
        exit;
    }

    $items[] = [
        'id' => $id,
        'name' => $name,
        'cat' => $cat,
        'qty' => (float)$qty
    ];
}

if (empty($items)) {
    echo json_encode(['success' => false, 'message' => 'No items provided.']);
    exit;
}

// Default XAMPP MySQL uses root with no password; adjust if yours differs
$con = new mysqli('localhost', 'root', '', 'resturent');
if ($con->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}
$con->set_charset('utf8mb4');
$con->begin_transaction();

$error = null;
foreach ($items as $item) {
    $stmt = $con->prepare("SELECT currentStock FROM invitems WHERE itemID = ? AND category = ? LIMIT 1");
    $stmt->bind_param('ss', $item['id'], $item['cat']);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result || $result->num_rows !== 1) {
        $error = "Item not found: {$item['id']} ({$item['cat']}).";
        $stmt->close();
        break;
    }

    $row = $result->fetch_assoc();
    $currentStock = (float)$row['currentStock'];
    if ($item['qty'] > $currentStock) {
        $error = "Not enough stock for {$item['id']} (have {$currentStock}, need {$item['qty']}).";
        $stmt->close();
        break;
    }

    $newStock = $currentStock - $item['qty'];
    $update = $con->prepare("UPDATE invitems SET currentStock = ? WHERE category = ? AND itemID = ?");
    $update->bind_param('dss', $newStock, $item['cat'], $item['id']);
    if (!$update->execute()) {
        $error = 'Update failed. Please try again.';
        $update->close();
        $stmt->close();
        break;
    }
    $update->close();
    $stmt->close();
}

echo '<!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Update</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  </head>
<body>';

if ($error === null) {
    $con->commit();
    echo "
    <script>
      Swal.fire({
        icon: 'success',
        title: 'Items Updated',
        text: 'Stock updated successfully.',
        confirmButtonText: 'OK'
      }).then(() => {
        window.location.href = 'admin_inventory.php';
      });
    </script>";
} else {
    $con->rollback();
    $safeMsg = htmlspecialchars($error, ENT_QUOTES);
    echo "
    <script>
      Swal.fire({
        icon: 'error',
        title: 'Update Failed',
        text: '{$safeMsg}',
        confirmButtonText: 'Back'
      }).then(() => {
        window.location.href = 'admin_updateitem.php';
      });
    </script>";
}

echo "</body></html>";
$con->close();
?>