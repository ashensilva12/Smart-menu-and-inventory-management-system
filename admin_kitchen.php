<?php
require_once __DIR__ . '/admin_only.php';
header('Content-Type: text/html; charset=UTF-8');
readfile(__DIR__ . '/kitchen.html');
