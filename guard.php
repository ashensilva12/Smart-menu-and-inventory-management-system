<?php
require_once __DIR__ . '/session_check.php';

$allowed = ['home','menu','about','contactus'];
$file = isset($_GET['file']) ? basename($_GET['file']) : '';
if (!in_array($file, $allowed, true)) {
    header('Location: Loggin.html');
    exit();
}
$path = __DIR__ . '/' . $file . '.html';
if (!is_file($path)) {
    header('Location: Loggin.html');
    exit();
}
readfile($path);
