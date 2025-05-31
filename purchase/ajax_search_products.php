<?php
require_once 'includes/db_plugin.php';
header('Content-Type: application/json');

$term = $_GET['term'] ?? '';
$exclude = isset($_GET['exclude']) ? json_decode($_GET['exclude']) : [];

if (empty($term)) {
    echo json_encode(['data' => []]);
    exit;
}

// Search products that aren't already added
$where = [
    'name LIKE' => "%$term%",
    'is_deleted' => 0
];

if (!empty($exclude)) {
    $where['id NOT IN'] = $exclude;
}

$products = $mysqli->common_select('products', 'id, name, barcode, price', $where, 'name', 'asc', 0, 10);

echo json_encode($products);
?>