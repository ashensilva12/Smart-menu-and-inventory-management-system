<?php
session_start();

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    echo "
    <html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body>
    <script>
    Swal.fire({icon:'warning',title:'Missing Fields',text:'Please enter both username and password.'}).then(()=>{window.location.href='admin_login.html';});
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
    echo "
    <html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body>
    <script>
    Swal.fire({icon:'error',title:'DB Connection Failed',text:'" . addslashes($con->connect_error) . "'}).then(()=>{window.location.href='admin_login.html';});
    </script>
    </body></html>";
    exit();
}

$stmt = $con->prepare("SELECT adminusername, adminpassword FROM admin WHERE adminusername = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $stored = $row['adminpassword'];
    $valid = password_verify($password, $stored) || $password === $stored; // allow legacy plain, then upgrade

    if ($valid) {
        if (!password_verify($password, $stored)) {
            $newHash = password_hash($password, PASSWORD_BCRYPT);
            $upd = $con->prepare("UPDATE admin SET adminpassword = ? WHERE adminusername = ?");
            $upd->bind_param('ss', $newHash, $username);
            $upd->execute();
            $upd->close();
        }
        $_SESSION['admin_username'] = $row['adminusername'];
        header('Location: admin_dashboard.php');
        exit();
    }
}

$stmt->close();
$con->close();

echo "
<html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body>
<script>
Swal.fire({icon:'error',title:'Login Failed',text:'Invalid admin username or password.'}).then(()=>{window.location.href='admin_login.html';});
</script>
</body></html>";
exit();
?>
