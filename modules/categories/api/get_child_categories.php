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
    if (isset($_POST['sub_category_id'])) {
        $sub_category_id = (int)$_POST['sub_category_id'];
        
        if ($sub_category_id > 0) {
            $result = $mysqli->common_select('child_category', '*', [
                'sub_category_id' => $sub_category_id,
                'is_deleted' => 0
            ], 'category_name', 'asc');
            
            if (!$result['error']) {
                $response = [
                    'success' => true,
                    'data' => $result['data']
                ];
            } else {
                $response['message'] = 'Error fetching child categories';
            }
        } else {
            $response['message'] = 'Invalid sub category ID';
        }
    } else {
        $response['message'] = 'Sub category ID is required';
    }
}

echo json_encode($response);
exit();