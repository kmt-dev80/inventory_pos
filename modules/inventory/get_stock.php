<?php
require_once __DIR__ . '/../../includes/db_plugin.php';
require_once __DIR__ . '/../../includes/auth_check.php';

header('Content-Type: application/json');

if(!isset($_GET['product_id']) || !is_numeric($_GET['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit();
}

$product_id = (int)$_GET['product_id'];

// Calculate current stock
$query = "SELECT COALESCE(SUM(CASE 
                WHEN s.change_type = 'purchase' THEN s.qty 
                WHEN s.change_type = 'purchase_return' THEN s.qty 
                WHEN s.change_type = 'sales_return' THEN s.qty 
                WHEN s.change_type = 'adjustment' AND s.qty > 0 THEN s.qty 
                ELSE 0 
             END), 0) - 
             COALESCE(SUM(CASE 
                WHEN s.change_type = 'sale' THEN s.qty 
                WHEN s.change_type = 'adjustment' AND s.qty < 0 THEN ABS(s.qty) 
                ELSE 0 
             END), 0) as current_stock
          FROM products p
          LEFT JOIN stock s ON p.id = s.product_id
          WHERE p.id = $product_id
          AND p.is_deleted = 0";

$result = $mysqli->connect->query($query);
$stock = $result->fetch_object()->current_stock ?? 0;

echo json_encode(['success' => true, 'stock' => $stock]);