<?php
ini_set('display_errors', 0);
ini_set('error_log', __DIR__ . '/error.log');
date_default_timezone_set('Asia/Dhaka');
define('CURRENCY', '৳');
define('BASE_URL', 'http://localhost/inventory_pos/');
require_once __DIR__ . '/includes/crud_class.php';
$mysqli=new CRUD();
?>