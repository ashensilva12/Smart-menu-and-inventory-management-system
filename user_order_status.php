<?php
// Public user order status page loader (no login required)
header('Content-Type: text/html; charset=UTF-8');
readfile(__DIR__ . '/user_order_status.html');
