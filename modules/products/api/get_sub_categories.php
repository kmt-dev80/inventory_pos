<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

require_once __DIR__ . '/../../../db_plugin.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['error' => 'Invalid request method']);
    exit();
}

if (!isset($_GET['category_id'])) {
    echo json_encode(['error' => 'Category ID is required']);
    exit();
}

$category_id = (int)$_GET['category_id'];
if ($category_id <= 0) {
    echo json_encode([]);
    exit();
}

$result = $mysqli->common_select('sub_category', '*', [
    'category_id' => $category_id, 
    'is_deleted' => 0
], 'category_name', 'asc');

echo json_encode($result['error'] ? [] : $result['data']);
exit();