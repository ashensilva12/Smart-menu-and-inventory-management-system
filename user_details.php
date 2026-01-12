<?php
require_once __DIR__ . '/session_check.php';

$isAdmin = isset($_SESSION['admin_username']);
$name = $isAdmin ? ($_SESSION['admin_username'] ?? 'Admin') : ($_SESSION['customer_name'] ?? 'Customer');
$email = $isAdmin ? '' : ($_SESSION['customer_email'] ?? '');
$role = $isAdmin ? 'Administrator' : 'Customer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details | The Kings Menu</title>
    <link rel="stylesheet" href="theme.css">
    <link rel="stylesheet" href="user_details.css">
</head>
<body class="user-details-page">
    <main class="page">
        <div class="card">
            <div class="header">
                <div class="avatar" aria-hidden="true">👤</div>
                <div>
                    <h1>User Details</h1>
                    <p class="meta">Signed in as <?php echo htmlspecialchars($role); ?></p>
                </div>
            </div>

            <div class="info">
                <div class="info-row">
                    <span class="label">Name</span>
                    <span class="value"><?php echo htmlspecialchars($name); ?></span>
                </div>
                <?php if ($email !== ''): ?>
                <div class="info-row">
                    <span class="label">Email</span>
                    <span class="value"><?php echo htmlspecialchars($email); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <div class="actions">
                <a class="btn secondary" href="home.php">Back to site</a>
                <a class="btn primary" href="logout.php">Log out</a>
            </div>

            <div class="footer">Logging out will return you to the customer login page.</div>
        </div>
    </main>
</body>
</html>
