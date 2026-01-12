<?php
    require_once __DIR__ . '/session_check.php';
    header('Content-Type: text/html');

    // Allow overriding connection via environment variables; defaults match local XAMPP
    $dbHost = getenv('DB_HOST') ?: 'localhost';
    $dbPort = getenv('DB_PORT') ?: 3306;
    $dbUser = getenv('DB_USER') ?: 'root';
    // Default password blank for common XAMPP installs; override via DB_PASS env var
    $dbPass = getenv('DB_PASS');
    if ($dbPass === false) {
        $dbPass = '';
    }
    $dbName = getenv('DB_NAME') ?: 'resturent';

    $con = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
    if ($con->connect_error) {
        die("<p class='error'>Database connection failed: " . $con->connect_error . "</p>");
    }

    $mode = isset($_GET['mode']) ? strtolower($_GET['mode']) : '';
    $showDelete = $mode === 'delete';

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
        $image = htmlspecialchars($row['item_image']);
        $id = intval($row['id']);  // Added for data-id
        echo '<div class="menu-item" data-category="' . $category . '" data-id="' . $id . '">';
        echo '  <div class="menu-item-img">';
        if ($showDelete) {
            echo '    <button class="delete-btn" type="button" aria-label="Delete item">−</button>';
        }
        echo '    <img src="' . $image . '" alt="' . $name . '" loading="lazy">';
        echo '  </div>';
        echo '  <div class="menu-item-content">';
        echo '    <div class="menu-item-title">';
        echo "      <h3>$name</h3>";
        echo "      <span class=\"menu-item-price\">Rs.$price</span>";
        echo '    </div>';
        echo "    <p class=\"menu-item-desc\">$desc</p>";
        echo "    <p class='menu-item-category'>Category: $category</p>";
        if (!$showDelete) {
            echo '    <div class="menu-item-footer">';
            echo '        <button class="add-to-cart" type="button">Add to cart</button>';
            echo '    </div>';
        }
        echo '  </div>';
        echo '</div>';
    }
    } else {
        echo "<p>No menu items found.</p>";
    }
    $con->close();
?>
