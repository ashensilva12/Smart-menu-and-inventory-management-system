<?php
// deletemenuitem.php
$con = new mysqli('localhost:6368', 'root', '1234', 'resturent');
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])) {
    
    $id = intval($_POST['id']);

    // Get image path for deletion
    $getImg = $con->query("SELECT item_image FROM menu WHERE id = $id");
    if ($getImg && $getImg->num_rows > 0) {
        $row = $getImg->fetch_assoc();
        $imgPath = $row['item_image'];
        if ($imgPath && file_exists($imgPath)) {
            unlink($imgPath);
        }
    }

?>
