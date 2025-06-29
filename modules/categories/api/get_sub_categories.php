<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once __DIR__ . '/../../../db_plugin.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['main_category_id'])) {
        $main_category_id = (int)$_POST['main_category_id'];
        
        if ($main_category_id > 0) {
            $result = $mysqli->common_select('sub_category', '*', [
                'category_id' => $main_category_id,
                'is_deleted' => 0
            ], 'category_name', 'asc');
            
            if (!$result['error']) {
                $response = [
                    'success' => true,
                    'data' => $result['data']
                ];
            } else {
                $response['message'] = 'Error fetching sub categories';
            }
        } else {
            $response['message'] = 'Invalid main category ID';
        }
    } else {
        $response['message'] = 'Main category ID is required';
    }
}

echo json_encode($response);
exit();