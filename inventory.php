<?php
    $con = new mysqli('localhost:6368', 'root', '1234', 'resturent');

    // Check DB connection
    if ($con->connect_error) {
        die("Connection failed: " . $con->connect_error);
    
    // Step 1: Update item status based on current stock and unit
    $currstock = "SELECT currentStock, itemID, unit FROM invitems";
    $stock = $con->query($currstock);
}
?>