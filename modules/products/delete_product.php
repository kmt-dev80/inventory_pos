<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}

require_once __DIR__ . '/../../db_plugin.php'; 

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id > 0) {
    // Soft delete the product
    $result = $mysqli->common_update('products', [
        'is_deleted' => 1,
        'deleted_at' => date('Y-m-d H:i:s')
    ], ['id' => $product_id]);
    
    if (!$result['error']) {
        $_SESSION['success'] = 'Product moved to trash successfully';
    } else {
        $_SESSION['error'] = 'Error deleting product: ' . $result['error_msg'];
    }
} else {
    $_SESSION['error'] = 'Invalid product ID';
}

header("Location: view_product.php");
exit();