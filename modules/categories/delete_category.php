<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}

require_once __DIR__ . '/../../db_plugin.php';

$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['error'] = 'Invalid category ID';
    header("Location: view_categories.php");
    exit();
}

// Check if any products exist under this category
function hasProducts($mysqli, $type, $id) {
    switch ($type) {
        case 'main':
            return !empty($mysqli->common_select('products', 'id', [
                'category_id' => $id,
                'is_deleted' => 0
            ])['data']);
        
        case 'sub':
            return !empty($mysqli->common_select('products', 'id', [
                'sub_category_id' => $id,
                'is_deleted' => 0
            ])['data']);
            
        case 'child':
            return !empty($mysqli->common_select('products', 'id', [
                'child_category_id' => $id,
                'is_deleted' => 0
            ])['data']);
            
        default:
            return false;
    }
}

if (hasProducts($mysqli, $type, $id)) {
    $_SESSION['error'] = 'Cannot delete category - products exist under this category';
    header("Location: view_categories.php");
    exit();
}

switch ($type) {
    case 'main':
        // Check if any products exist in sub/child categories
        $sub_cats = $mysqli->common_select('sub_category', 'id', ['category_id' => $id])['data'];
        foreach ($sub_cats as $sub_cat) {
            if (hasProducts($mysqli, 'sub', $sub_cat->id)) {
                $_SESSION['error'] = 'Cannot delete main category - products exist in its sub-categories';
                header("Location: view_categories.php");
                exit();
            }
            
            $child_cats = $mysqli->common_select('child_category', 'id', ['sub_category_id' => $sub_cat->id])['data'];
            foreach ($child_cats as $child_cat) {
                if (hasProducts($mysqli, 'child', $child_cat->id)) {
                    $_SESSION['error'] = 'Cannot delete main category - products exist in its child categories';
                    header("Location: view_categories.php");
                    exit();
                }
            }
        }
        
        // Soft delete main category and all its sub/child categories
        $mysqli->common_update('category', ['is_deleted' => 1, 'deleted_at' => date('Y-m-d H:i:s')], ['id' => $id]);
        
        foreach ($sub_cats as $sub_cat) {
            // Delete child categories first
            $mysqli->common_update('child_category', 
                ['is_deleted' => 1, 'deleted_at' => date('Y-m-d H:i:s')], 
                ['sub_category_id' => $sub_cat->id]);
            
            // Then delete sub-category
            $mysqli->common_update('sub_category', 
                ['is_deleted' => 1, 'deleted_at' => date('Y-m-d H:i:s')], 
                ['id' => $sub_cat->id]);
        }
        
        $_SESSION['success'] = 'Main category and all its sub/child categories deleted successfully';
        break;
        
    case 'sub':
        // Check child categories for products
        $child_cats = $mysqli->common_select('child_category', 'id', ['sub_category_id' => $id])['data'];
        foreach ($child_cats as $child_cat) {
            if (hasProducts($mysqli, 'child', $child_cat->id)) {
                $_SESSION['error'] = 'Cannot delete sub-category - products exist in its child categories';
                header("Location: view_categories.php");
                exit();
            }
        }
        
        // Soft delete sub-category and all its child categories
        $mysqli->common_update('sub_category', ['is_deleted' => 1, 'deleted_at' => date('Y-m-d H:i:s')], ['id' => $id]);
        
        // Delete all child categories
        $mysqli->common_update('child_category', 
            ['is_deleted' => 1, 'deleted_at' => date('Y-m-d H:i:s')], 
            ['sub_category_id' => $id]);
        
        $_SESSION['success'] = 'Sub-category and all its child categories deleted successfully';
        break;
        
    case 'child':
        // Soft delete only the child category
        $mysqli->common_update('child_category', ['is_deleted' => 1, 'deleted_at' => date('Y-m-d H:i:s')], ['id' => $id]);
        $_SESSION['success'] = 'Child category deleted successfully';
        break;
        
    default:
        $_SESSION['error'] = 'Invalid category type';
        break;
}

header("Location: view_categories.php");
exit();