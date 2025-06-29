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

if (!isset($_GET['sub_category_id'])) {
    echo json_encode(['error' => 'Sub Category ID is required']);
    exit();
}

$sub_category_id = (int)$_GET['sub_category_id'];
if ($sub_category_id <= 0) {
    echo json_encode([]);
    exit();
}

$result = $mysqli->common_select('child_category', '*', [
    'sub_category_id' => $sub_category_id, 
    'is_deleted' => 0
], 'category_name', 'asc');

echo json_encode($result['error'] ? [] : $result['data']);
exit();