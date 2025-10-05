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
    $con = new mysqli('localhost:6368', 'root', '1234', 'resturent');

    if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);

    // Check if it's admin login first
    $stmt = $con->prepare("SELECT adminusername, adminpassword FROM admin WHERE adminusername = ?");
    $stmt->bind_param("s", $Email);
    $stmt->execute();
    $adminResult = $stmt->get_result();

    if ($adminResult->num_rows === 1) {
    $adminRow = $adminResult->fetch_assoc();

     if ($Password === $adminRow['adminpassword']) {
        $_SESSION['admin_username'] = $adminRow['adminusername'];
        header("Location: dashboard.html");
        exit();
}
?>
