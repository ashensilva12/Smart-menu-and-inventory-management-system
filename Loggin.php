<?php
session_start();

$Email = trim($_POST['email'] ?? '');
$Password = trim($_POST['password'] ?? '');

// If fields are empty, show SweetAlert2 popup and redirect
if ($Email === '' || $Password === '') {
    echo "
    <html><head>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head><body>
    <script>
    Swal.fire({
        icon: 'warning',
        title: 'Missing Fields',
        text: 'Please enter both email and password.',
        confirmButtonText: 'OK'
    }).then(() => {
        window.location.href = 'Loggin.html';
    });
    </script>
    </body></html>";
    exit();
}

$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = (int)(getenv('DB_PORT') ?: 3306);
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS');
if ($dbPass === false) { $dbPass = ''; }
$dbName = getenv('DB_NAME') ?: 'resturent';

$con = @new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
if ($con->connect_error) {
    $err = addslashes($con->connect_error);
    echo "<script>alert('Database connection failed: {$err}');window.location.href='Loggin.html';</script>";
    exit();
}

// Try admin login first
$stmt = $con->prepare("SELECT adminusername, adminpassword FROM admin WHERE adminusername = ?");
$stmt->bind_param("s", $Email);
$stmt->execute();
$adminResult = $stmt->get_result();

if ($adminResult->num_rows === 1) {
    $adminRow = $adminResult->fetch_assoc();

    if ($Password === $adminRow['adminpassword']) {
        $_SESSION['admin_username'] = $adminRow['adminusername'];
        header("Location: admin_dashboard.php");
        exit();
    } else {
        echo "
        <html><head>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head><body>
        <script>
        Swal.fire({
            icon: 'error',
            title: 'Login Failed',
            text: 'Incorrect password.',
            confirmButtonText: 'Retry'
        }).then(() => {
            window.location.href = 'Loggin.html';
        });
        </script>
        </body></html>";
        exit();
    }
}

// Fallback: customer login (hashed password with fallback to legacy plain text)
$stmt = $con->prepare("SELECT email, password, name FROM customer WHERE email = ?");
$stmt->bind_param("s", $Email);
$stmt->execute();
$customerResult = $stmt->get_result();

if ($customerResult->num_rows === 1) {
    $row = $customerResult->fetch_assoc();
    $storedHash = $row['password'];

    $valid = password_verify($Password, $storedHash) || $Password === $storedHash;

    if ($valid) {
        $_SESSION['customer_name'] = $row['name'];
        $_SESSION['customer_email'] = $row['email'];
        header("Location: home.php");
        exit();
    } else {
        echo "
        <html><head>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head><body>
        <script>
        Swal.fire({
            icon: 'error',
            title: 'Login Failed',
            text: 'Incorrect customer password.',
            confirmButtonText: 'Retry'
        }).then(() => {
            window.location.href = 'Loggin.html';
        });
        </script>
        </body></html>";
        exit();
    }
} else {
    echo "
    <html><head>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head><body>
    <script>
    Swal.fire({
        icon: 'info',
        title: 'Email Not Found',
        text: 'This email is not registered in our system.',
        confirmButtonText: 'OK'
    }).then(() => {
        window.location.href = 'Loggin.html';
    });
    </script>
    </body></html>";
    exit();
}
?>
