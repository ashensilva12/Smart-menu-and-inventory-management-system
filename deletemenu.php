<?php
// deletemenuitem.php
$con = new mysqli('localhost:6368', 'root', '1234', 'resturent');
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])) {
?>
