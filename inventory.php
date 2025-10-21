<?php
    $con = new mysqli('localhost:6368', 'root', '1234', 'resturent');

    // Check DB connection
    if ($con->connect_error) {
        die("Connection failed: " . $con->connect_error);
    
    // Step 1: Update item status based on current stock and unit
    $currstock = "SELECT currentStock, itemID, unit FROM invitems";
    $stock = $con->query($currstock);
    if ($stock && $stock->num_rows > 0) {
    while ($row = $stock->fetch_assoc()) {
        $id = $con->real_escape_string($row['itemID']);
        $current = floatval($row['currentStock']);
        $unit = strtolower(trim($row['unit']));

        // Define low stock thresholds per unit
        switch ($unit) {
            case 'g':
                $lowThreshold = 500;
                break;
            case 'kg':
                $lowThreshold = 10;
                break;
            case 'ml':
                $lowThreshold = 500;
                break;
            case 'l':
                $lowThreshold = 10;
                break;
            case 'pieces':
                $lowThreshold = 20;
                break;
            case 'bottles':
                $lowThreshold = 10;
                break;
            default:
                $lowThreshold = 10; // fallback
        }
                // Determine status
        if ($current <= 0) {
            $status = "Out Of Stock";
        } elseif ($current < $lowThreshold) {
            $status = "Low Stock";
        } else {
            $status = "In Stock";
        }
?>