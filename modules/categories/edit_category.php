<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';

$error = '';
$success = '';
$category_type = '';
$category_data = null;

// Determine what we're editing
$edit_main = isset($_GET['edit_main']) ? (int)$_GET['edit_main'] : 0;
$edit_sub = isset($_GET['edit_sub']) ? (int)$_GET['edit_sub'] : 0;
$edit_child = isset($_GET['edit_child']) ? (int)$_GET['edit_child'] : 0;

// Load category data
if ($edit_main > 0) {
    $category_type = 'main';
    $result = $mysqli->common_select('category', '*', ['id' => $edit_main]);
    if (!$result['error'] && !empty($result['data'])) {
        $category_data = $result['data'][0];
    } else {
        $_SESSION['error'] = 'Main category not found';
        header("Location: view_categories.php");
        exit();
    }
} elseif ($edit_sub > 0) {
    $category_type = 'sub';
    $result = $mysqli->common_select('sub_category', '*', ['id' => $edit_sub]);
    if (!$result['error'] && !empty($result['data'])) {
        $category_data = $result['data'][0];
        // Get main categories for dropdown
        $main_result = $mysqli->common_select('category', '*', ['is_deleted' => 0], 'category', 'asc');
        $main_categories = !$main_result['error'] ? $main_result['data'] : [];
    } else {
        $_SESSION['error'] = 'Sub-category not found';
        header("Location: view_categories.php");
        exit();
    }
} elseif ($edit_child > 0) {
    $category_type = 'child';
    $result = $mysqli->common_select('child_category', '*', ['id' => $edit_child]);
    if (!$result['error'] && !empty($result['data'])) {
        $category_data = $result['data'][0];
        
        // Get all main categories
        $main_result = $mysqli->common_select('category', '*', ['is_deleted' => 0], 'category', 'asc');
        $main_categories = !$main_result['error'] ? $main_result['data'] : [];
        
        // Get current sub category to determine main category
        $sub_result = $mysqli->common_select('sub_category', '*', ['id' => $category_data->sub_category_id]);
        if (!$sub_result['error'] && !empty($sub_result['data'])) {
            $current_sub = $sub_result['data'][0];
            $current_main_id = $current_sub->category_id;
            
            // Get sub categories for the current main category
            $subs_result = $mysqli->common_select('sub_category', '*', [
                'category_id' => $current_main_id,
                'is_deleted' => 0
            ], 'category_name', 'asc');
            $sub_categories = !$subs_result['error'] ? $subs_result['data'] : [];
        } else {
            $_SESSION['error'] = 'Parent sub-category not found';
            header("Location: view_categories.php");
            exit();
        }
    } else {
        $_SESSION['error'] = 'Child category not found';
        header("Location: view_categories.php");
        exit();
    }
}

// Handle AJAX requests for dynamic dropdowns
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    $response = ['success' => false];
    
    if (isset($_GET['get_sub_categories'])) {
        $main_id = (int)$_GET['main_category_id'];
        if ($main_id > 0) {
            $result = $mysqli->common_select('sub_category', '*', [
                'category_id' => $main_id,
                'is_deleted' => 0
            ], 'category_name', 'asc');
            
            if (!$result['error']) {
                $response = [
                    'success' => true,
                    'data' => $result['data']
                ];
            }
        }
    }
    
    echo json_encode($response);
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $details = trim($_POST['details']);
    
    if (empty($name)) {
        $_SESSION['error'] = 'Category name is required';
    } else {
        // Check for duplicate names
        $table_name = '';
        $where = [];
        
        switch ($category_type) {
            case 'main':
                $table_name = 'category';
                $where = [
                    'category' => $name,
                    'id!=' => $edit_main,
                    'is_deleted' => 0
                ];
                break;
                
            case 'sub':
                $table_name = 'sub_category';
                $main_category_id = (int)$_POST['main_category_id'];
                $where = [
                    'category_name' => $name,
                    'category_id' => $main_category_id,
                    'id!=' => $edit_sub,
                    'is_deleted' => 0
                ];
                break;
                
            case 'child':
                $table_name = 'child_category';
                $sub_category_id = (int)$_POST['sub_category_id'];
                $where = [
                    'category_name' => $name,
                    'sub_category_id' => $sub_category_id,
                    'id!=' => $edit_child,
                    'is_deleted' => 0
                ];
                break;
        }
        
        $check = $mysqli->common_select($table_name, 'id', $where);
        if (!$check['error'] && !empty($check['data'])) {
           $_SESSION['error'] = "A {$category_type} category with this name already exists";
        } else {
            // Prepare update data
            $update_data = [
                'details' => $details,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => $_SESSION['user']->id
            ];
            
            switch ($category_type) {
                case 'main':
                    $update_data['category'] = $name;
                    $result = $mysqli->common_update('category', $update_data, ['id' => $edit_main]);
                    break;
                    
                case 'sub':
                    $update_data['category_name'] = $name;
                    $update_data['category_id'] = (int)$_POST['main_category_id'];
                    $result = $mysqli->common_update('sub_category', $update_data, ['id' => $edit_sub]);
                    break;
                    
                case 'child':
                    $update_data['category_name'] = $name;
                    $update_data['sub_category_id'] = (int)$_POST['sub_category_id'];
                    $result = $mysqli->common_update('child_category', $update_data, ['id' => $edit_child]);
                    break;
            }
            
            if (!$result['error']) {
                $_SESSION['success'] = ucfirst($category_type) . ' category updated successfully';
                header("Location: view_categories.php");
                exit();
            } else {
                $_SESSION['error'] = 'Error updating category: ' . $result['error_msg'];
            }
        }
    }
}

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/sidebar.php';
require_once __DIR__ . '/../../requires/topbar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Edit <?= ucfirst($category_type) ?> Category</h4>
            <a href="view_categories.php" class="btn btn-secondary btn-round ms-auto">
                <i class="fas fa-arrow-left"></i> Back to Categories
            </a>
        </div>
        
        <div id="alertContainer">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Category Information</div>
                    </div>
                    <div class="card-body">
                        <form method="post" id="editCategoryForm">
                            <div class="form-group">
                                <label for="name"><?= ucfirst($category_type) ?> Category Name *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?= htmlspecialchars($category_type === 'main' ? $category_data->category : $category_data->category_name) ?>" required>
                            </div>
                            
                            <?php if ($category_type === 'child'): ?>
                                <div class="form-group">
                                    <label for="main_category_id">Main Category *</label>
                                    <select class="form-control" id="main_category_id" name="main_category_id" required>
                                        <option value="">Select Main Category</option>
                                        <?php foreach ($main_categories as $main_cat): ?>
                                            <option value="<?= $main_cat->id ?>" 
                                                <?= isset($current_main_id) && $current_main_id == $main_cat->id ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($main_cat->category) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="sub_category_id">Sub Category *</label>
                                    <select class="form-control" id="sub_category_id" name="sub_category_id" required>
                                        <option value="">Select Sub Category</option>
                                        <?php foreach ($sub_categories as $sub_cat): ?>
                                            <option value="<?= $sub_cat->id ?>" 
                                                <?= $category_data->sub_category_id == $sub_cat->id ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($sub_cat->category_name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <?php if ($category_type === 'sub'): ?>
                                <div class="form-group">
                                    <label for="main_category_id">Main Category *</label>
                                    <select class="form-control" id="main_category_id" name="main_category_id" required>
                                        <option value="">Select Main Category</option>
                                        <?php foreach ($main_categories as $main_cat): ?>
                                            <option value="<?= $main_cat->id ?>" 
                                                <?= $category_data->category_id == $main_cat->id ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($main_cat->category) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label for="details">Description</label>
                                <textarea class="form-control" id="details" name="details" rows="3"><?= 
                                    htmlspecialchars($category_data->details) 
                                ?></textarea>
                            </div>
                            
                            <div class="form-group text-right">
                                <button type="submit" class="btn btn-primary">Update Category</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../requires/footer.php'; ?>
<?php if ($category_type === 'child'): ?>
<script>
$(document).ready(function() {
    // Function to update sub categories when main category changes
    $('#main_category_id').change(function() {
        var mainId = $(this).val();
        if (mainId) {
            $.ajax({
                url: window.location.href,
                type: 'GET',
                data: {
                    get_sub_categories: 1,
                    main_category_id: mainId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var options = '<option value="">Select Sub Category</option>';
                        $.each(response.data, function(index, subCategory) {
                            options += '<option value="' + subCategory.id + '">' + 
                                subCategory.category_name + '</option>';
                        });
                        $('#sub_category_id').html(options);
                    } else {
                        $('#sub_category_id').html('<option value="">No sub categories found</option>');
                    }
                },
                error: function() {
                    $('#sub_category_id').html('<option value="">Error loading sub categories</option>');
                }
            });
        } else {
            $('#sub_category_id').html('<option value="">Select Main Category First</option>');
        }
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>
<?php endif; ?>