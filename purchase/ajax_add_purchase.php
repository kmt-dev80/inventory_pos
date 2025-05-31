<?php
require_once 'includes/db_plugin.php';
header('Content-Type: application/json');

session_start();

// Check if user is logged in and has inventory role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'inventory') {
    echo json_encode(['error' => 1, 'error_msg' => 'Unauthorized access']);
    exit;
}

$response = ['error' => 0, 'error_msg' => ''];

try {
    // Get form data
    $supplier_id = $_POST['supplier_id'] ?? null;
    $reference_no = $_POST['reference_no'] ?? null;
    $date = $_POST['date'] ?? date('Y-m-d H:i:s');
    $products = $_POST['products'] ?? [];
    $subtotal = $_POST['subtotal'] ?? 0;
    $discount = $_POST['discount'] ?? 0;
    $tax = $_POST['tax'] ?? 0;
    $total = $_POST['total'] ?? 0;
    $remarks = $_POST['remarks'] ?? '';
    $user_id = $_POST['user_id'] ?? $_SESSION['user_id'];
    
    // Validate required fields
    if (empty($supplier_id) || empty($products)) {
        throw new Exception('Supplier and products are required');
    }
    
    // Start transaction
    $mysqli->connect->begin_transaction();
    
    // Insert purchase
    $purchaseData = [
        'supplier_id' => $supplier_id,
        'reference_no' => $reference_no,
        'payment_method' => 'cash', // Default
        'subtotal' => $subtotal,
        'discount' => $discount,
        'vat' => $tax,
        'total' => $total,
        'user_id' => $user_id
    ];
    
    $purchase = $mysqli->common_insert('purchase', $purchaseData);
    
    if ($purchase['error']) {
        throw new Exception('Failed to create purchase: ' . $purchase['error_msg']);
    }
    
    $purchase_id = $purchase['data'];
    
    // Insert purchase items
    foreach ($products as $product) {
        $product_id = $product['product_id'] ?? null;
        $quantity = $product['quantity'] ?? 0;
        $unit_price = $product['unit_price'] ?? 0;
        
        if (empty($product_id)) continue;
        
        $itemData = [
            'purchase_id' => $purchase_id,
            'product_id' => $product_id,
            'quantity' => $quantity,
            'unit_price' => $unit_price
        ];
        
        $item = $mysqli->common_insert('purchase_items', $itemData);
        
        if ($item['error']) {
            throw new Exception('Failed to add purchase item: ' . $item['error_msg']);
        }
    }
    
    // Commit transaction
    $mysqli->connect->commit();
    
    $response['data'] = ['purchase_id' => $purchase_id];
    
} catch (Exception $e) {
    $mysqli->connect->rollback();
    $response['error'] = 1;
    $response['error_msg'] = $e->getMessage();
}

echo json_encode($response);
?>