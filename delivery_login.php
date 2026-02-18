<?php
session_start();

function load_json($path) {
    if (!file_exists($path)) return [];
    $raw = file_get_contents($path);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function save_json($path, $data) {
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
}

function staff_file() {
    return __DIR__ . '/delivery_staff.json';
}

function seed_staff_if_empty() {
    $file = staff_file();
    if (file_exists($file) && filesize($file) > 0) return;
    $seed = [
        [
            'id' => 1,
            'name' => 'Demo Driver',
            'phone' => '0700000000',
            'username' => 'driver1',
            'status' => 'active',
            'passwordHash' => password_hash('driver123', PASSWORD_DEFAULT),
            'createdAt' => date('c')
        ]
    ];
    save_json($file, $seed);
}

function load_staff() {
    seed_staff_if_empty();
    return load_json(staff_file());
}

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['delivery_user']);
    session_destroy();
    header('Location: delivery_login.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $staff = load_staff();
    foreach ($staff as $user) {
        if (strcasecmp($user['username'] ?? '', $username) !== 0) continue;
        if (($user['status'] ?? 'active') !== 'active') {
            $error = 'Account is inactive.';
            break;
        }
        $hash = $user['passwordHash'] ?? '';
        if ($hash && password_verify($password, $hash)) {
            $_SESSION['delivery_user'] = [
                'id' => (int)($user['id'] ?? 0),
                'name' => $user['name'] ?? $username,
                'username' => $user['username'] ?? $username,
                'phone' => $user['phone'] ?? ''
            ];
            header('Location: delivery_dashboard.php');
            exit();
        }
    }
    if ($error === '') {
        $error = 'Invalid username or password.';
    }
}

// If accessed directly without POST, fall through to HTML form below
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Login | The Kings Menu</title>
    <link rel="stylesheet" href="theme.css">
    <link rel="stylesheet" href="auth.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 20% 20%, rgba(124,212,244,0.08), transparent 24%),
                        radial-gradient(circle at 80% 0%, rgba(230,57,70,0.12), transparent 30%),
                        var(--bg, #0b1220);
            color: #e5e7eb;
            padding: 20px;
        }
        .auth-card {
            width: 100%;
            max-width: 420px;
            background: rgba(18, 28, 49, 0.92);
            border: 1px solid #1f2937;
            border-radius: 14px;
            box-shadow: 0 18px 42px rgba(0,0,0,0.35);
            padding: 26px 26px 22px;
        }
        .auth-card h2 {
            margin: 0 0 6px;
        }
        .auth-card p.sub {
            margin: 0 0 16px;
            color: #9ca3af;
            font-size: 14px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        label {
            font-weight: 700;
            font-size: 14px;
        }
        input {
            width: 100%;
            padding: 11px 12px;
            border-radius: 10px;
            border: 1px solid #1f2937;
            background: #0f172a;
            color: #e5e7eb;
        }
        .error-msg {
            background: rgba(230,57,70,0.12);
            color: #fca5a5;
            border: 1px solid #7f1d1d;
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 8px;
        }
        .actions-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-top: 4px;
            font-size: 13px;
        }
        .btn-full {
            width: 100%;
            margin-top: 6px;
        }
        .btnprimary {
            background: linear-gradient(120deg, #e63946, #ff7b87);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 12px 14px;
            font-weight: 800;
            letter-spacing: 0.02em;
            box-shadow: 0 12px 28px rgba(230,57,70,0.35);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btnprimary:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 34px rgba(230,57,70,0.45);
        }
        .btnprimary:active {
            transform: translateY(0);
            box-shadow: 0 8px 18px rgba(230,57,70,0.35);
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <h2>Delivery Partner Login</h2>
        <p class="sub">Sign in to view and complete assigned orders.</p>
        <?php if ($error): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="delivery_login.php">
            <div>
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            <div>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btnprimary btn-full">Login</button>
        </form>
    </div>
</body>
</html>
