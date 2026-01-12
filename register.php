<?php
$Name = trim($_POST['name'] ?? '');
$Email = trim($_POST['email'] ?? '');
$Password = trim($_POST['password'] ?? '');
$Confirm = trim($_POST['confirm-password'] ?? '');

if ($Name === '' || $Email === '' || $Password === '' || $Confirm === '') {
    exit("<script>alert('Please complete all fields');window.location.href='register.html';</script>");
}

if (strlen($Name) < 3) {
    exit("<script>alert('Name must be at least 3 characters');window.location.href='register.html';</script>");
}

if (!filter_var($Email, FILTER_VALIDATE_EMAIL)) {
    exit("<script>alert('Invalid email address');window.location.href='register.html';</script>");
}

if ($Password !== $Confirm) {
    exit("<script>alert('Passwords do not match');window.location.href='register.html';</script>");
}

if (strlen($Password) < 6) {
    exit("<script>alert('Password must be at least 6 characters');window.location.href='register.html';</script>");
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
    exit("<script>alert('Database connection failed: {$err}');window.location.href='register.html';</script>");
}

// Check for existing email
$stmt = $con->prepare('SELECT 1 FROM customer WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $Email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    $con->close();
    exit("<script>alert('Email already exists');window.location.href='register.html';</script>");
}
$stmt->close();

// Store hashed password for new registrations
$hashed = password_hash($Password, PASSWORD_BCRYPT);

$insert = $con->prepare('INSERT INTO customer (name, email, password) VALUES (?, ?, ?)');
$insert->bind_param('sss', $Name, $Email, $hashed);
if ($insert->execute()) {
    $insert->close();
    $con->close();
    echo "<script>alert('Account created successfully');window.location.href='Loggin.html';</script>";
    exit();
}

$errorMsg = $con->error ? addslashes($con->error) : 'Unknown error';
$insert->close();
$con->close();
echo "<script>alert('Something went wrong: {$errorMsg}');window.location.href='register.html';</script>";
?>
