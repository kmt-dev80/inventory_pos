<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('Invalid purchase ID', 'danger');
    header('Location: view_purchases.php');
    exit;
}

$purchaseId = (int)$_GET['id'];

// Soft delete the purchase (set is_deleted = 1)
$result = $mysqli->common_update('purchase', 
    ['is_deleted' => 1, 'deleted_at' => date('Y-m-d H:i:s')], 
    ['id' => $purchaseId]
);

if ($result['error']) {
    setFlashMessage('Failed to delete purchase: ' . $result['error_msg'], 'danger');
} else {
    setFlashMessage('Purchase deleted successfully', 'success');
}

header('Location: view_purchases.php');
exit;
?>