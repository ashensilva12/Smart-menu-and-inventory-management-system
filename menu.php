<?php
    header('Content-Type: text/html');
    $con = new mysqli('localhost:6368', 'root', '1234', 'resturent');
    if ($con->connect_error) {
        die("<p class='error'>Database connection failed: " . $con->connect_error . "</p>");
    }

    $sql = "SELECT * FROM menu ORDER BY id DESC";
    $result = $con->query($sql);
    if ($result === false) {
        die("<p class='error'>Query failed: " . $con->error . "</p>");
    }
    if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $category = htmlspecialchars($row['item_category']);
        $name = htmlspecialchars($row['item_name']);
        $price = number_format($row['item_price'], 2, '.', '');
        $desc = htmlspecialchars($row['item_description']);


?>