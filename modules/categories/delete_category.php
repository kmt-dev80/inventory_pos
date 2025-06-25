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

// Check if any products exist under this category (including soft-deleted ones)
function hasProducts($mysqli, $type, $id) {
    switch ($type) {
        case 'main':
            return !empty($mysqli->common_select('products', 'id', [
                'category_id' => $id
            ])['data']);
        
        case 'sub':
            return !empty($mysqli->common_select('products', 'id', [
                'sub_category_id' => $id
            ])['data']);
            
        case 'child':
            return !empty($mysqli->common_select('products', 'id', [
                'child_category_id' => $id
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
        
        // Delete all child categories first
        foreach ($sub_cats as $sub_cat) {
            $mysqli->common_delete('child_category', ['sub_category_id' => $sub_cat->id]);
        }
        
        // Then delete all sub-categories
        $mysqli->common_delete('sub_category', ['category_id' => $id]);
        
        // Finally delete the main category
        $mysqli->common_delete('category', ['id' => $id]);
        
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
        
        // Delete all child categories first
        $mysqli->common_delete('child_category', ['sub_category_id' => $id]);
        
        // Then delete the sub-category
        $mysqli->common_delete('sub_category', ['id' => $id]);
        
        $_SESSION['success'] = 'Sub-category and all its child categories deleted successfully';
        break;
        
    case 'child':
        // Delete only the child category
        $mysqli->common_delete('child_category', ['id' => $id]);
        $_SESSION['success'] = 'Child category deleted successfully';
        break;
        
    default:
        $_SESSION['error'] = 'Invalid category type';
        break;
}

header("Location: view_categories.php");
exit();