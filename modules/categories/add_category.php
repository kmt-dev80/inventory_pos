<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';

// Initialize variables
$error = '';
$success = '';
$main_categories = [];
$sub_categories = [];
$child_categories = [];

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    $response = ['success' => false, 'message' => 'Invalid request'];
    
    // Check which form was submitted
    if (isset($_POST['form_type'])) {
        switch ($_POST['form_type']) {
            case 'main_category':
                $category = trim($_POST['main_category_name']);
                $details = trim($_POST['main_category_details']);
                
                if (empty($category)) {
                    $response['message'] = 'Main category name is required';
                } else {
                    $check = $mysqli->common_select('category', 'id', ['category' => $category, 'is_deleted' => 0]);
                    if (!$check['error'] && !empty($check['data'])) {
                        $response['message'] = 'Main category already exists';
                    } else {
                        $data = [
                            'category' => $category,
                            'details' => $details
                        ];
                        $result = $mysqli->common_insert('category', $data);
                        if (!$result['error']) {
                            $response = [
                                'success' => true,
                                'message' => 'Main category added successfully',
                            ];
                            
                            // Get updated list
                            $main_result = $mysqli->common_select('category', '*', ['is_deleted' => 0], 'category', 'asc');
                            if (!$main_result['error']) {
                                $response['categories'] = $main_result['data'];
                            }
                        } else {
                            $response['message'] = 'Error adding main category: ' . $result['error_msg'];
                        }
                    }
                }
                break;
                
            case 'get_sub_categories':
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
                }
                break;
                
            case 'sub_category':
                $main_category_id = (int)$_POST['main_category_id'];
                $sub_category_name = trim($_POST['sub_category_name']);
                $sub_category_details = trim($_POST['sub_category_details']);
                
                if (empty($main_category_id)) {
                    $response['message'] = 'Please select a main category';
                } elseif (empty($sub_category_name)) {
                    $response['message'] = 'Sub category name is required';
                } else {
                    $check = $mysqli->common_select('sub_category', 'id', [
                        'category_id' => $main_category_id,
                        'category_name' => $sub_category_name,
                        'is_deleted' => 0
                    ]);
                    
                    if (!$check['error'] && !empty($check['data'])) {
                        $response['message'] = 'Sub category already exists under this main category';
                    } else {
                        $data = [
                            'category_id' => $main_category_id,
                            'category_name' => $sub_category_name,
                            'details' => $sub_category_details
                        ];
                        $result = $mysqli->common_insert('sub_category', $data);
                        if (!$result['error']) {
                            $response = [
                                'success' => true,
                                'message' => 'Sub category added successfully'
                            ];
                            
                            // Get updated sub categories
                            $sub_result = $mysqli->common_select('sub_category', '*', [
                                'category_id' => $main_category_id,
                                'is_deleted' => 0
                            ], 'category_name', 'asc');
                            if (!$sub_result['error']) {
                                $response['sub_categories'] = $sub_result['data'];
                            }
                        } else {
                            $response['message'] = 'Error adding sub category: ' . $result['error_msg'];
                        }
                    }
                }
                break;
                
            case 'get_child_categories':
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
                }
                break;
                
            case 'child_category':
                $sub_category_id = (int)$_POST['sub_category_id'];
                $child_category_name = trim($_POST['child_category_name']);
                $child_category_details = trim($_POST['child_category_details']);
                
                if (empty($sub_category_id)) {
                    $response['message'] = 'Please select a sub category';
                } elseif (empty($child_category_name)) {
                    $response['message'] = 'Child category name is required';
                } else {
                    $check = $mysqli->common_select('child_category', 'id', [
                        'sub_category_id' => $sub_category_id,
                        'category_name' => $child_category_name,
                        'is_deleted' => 0
                    ]);
                    
                    if (!$check['error'] && !empty($check['data'])) {
                        $response['message'] = 'Child category already exists under this sub category';
                    } else {
                        $data = [
                            'sub_category_id' => $sub_category_id,
                            'category_name' => $child_category_name,
                            'details' => $child_category_details
                        ];
                        $result = $mysqli->common_insert('child_category', $data);
                        if (!$result['error']) {
                            $response = [
                                'success' => true,
                                'message' => 'Child category added successfully'
                            ];
                            
                            // Get updated child categories
                            $child_result = $mysqli->common_select('child_category', '*', [
                                'sub_category_id' => $sub_category_id,
                                'is_deleted' => 0
                            ], 'category_name', 'asc');
                            if (!$child_result['error']) {
                                $response['child_categories'] = $child_result['data'];
                            }
                        } else {
                            $response['message'] = 'Error adding child category: ' . $result['error_msg'];
                        }
                    }
                }
                break;
        }
    }
    
    echo json_encode($response);
    exit();
}

// Get initial main categories for page load
$result = $mysqli->common_select('category', '*', ['is_deleted' => 0], 'category', 'asc');
if (!$result['error']) {
    $main_categories = $result['data'];
}

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/sidebar.php';
require_once __DIR__ . '/../../requires/topbar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="container">
            <div class="row">
                <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Manage Categories</h1>
                        <a href="view_categories.php" class="btn btn-secondary btn-round ms-auto">
                            <i class="fas fa-arrow-right"></i> View Categories
                        </a>
                    </div>
                    
                    <div id="alertContainer"></div>
                    
                    <div class="row">
                        <!-- Main Category Form -->
                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>Add Main Category</h5>
                                </div>
                                <div class="card-body">
                                    <form method="post" id="mainCategoryForm">
                                        <input type="hidden" name="form_type" value="main_category">
                                        <div class="mb-3">
                                            <label for="main_category_name" class="form-label">Category Name</label>
                                            <input type="text" class="form-control" id="main_category_name" name="main_category_name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="main_category_details" class="form-label">Description</label>
                                            <textarea class="form-control" id="main_category_details" name="main_category_details" rows="2"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Add Main Category</button>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Main Categories List -->
                            <div class="card">
                                <div class="card-header">
                                    <h5>Main Categories</h5>
                                </div>
                                <div class="card-body" id="mainCategoriesList">
                                    <?php if (!empty($main_categories)): ?>
                                        <ul class="list-group">
                                            <?php foreach ($main_categories as $category): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <?= htmlspecialchars($category->category) ?>
                                                    <?php if ($category->details): ?>
                                                        <small class="text-muted"><?= htmlspecialchars($category->details) ?></small>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p>No main categories found</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sub Category Form -->
                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>Add Sub Category</h5>
                                </div>
                                <div class="card-body">
                                    <form method="post" id="subCategoryForm">
                                        <input type="hidden" name="form_type" value="sub_category">
                                        <div class="mb-3">
                                            <label for="main_category_select" class="form-label">Main Category</label>
                                            <select class="form-select" id="main_category_select" name="main_category_id" required>
                                                <option value="">Select Main Category</option>
                                                <?php foreach ($main_categories as $category): ?>
                                                    <option value="<?= $category->id ?>"><?= htmlspecialchars($category->category) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="sub_category_name" class="form-label">Sub Category Name</label>
                                            <input type="text" class="form-control" id="sub_category_name" name="sub_category_name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="sub_category_details" class="form-label">Description</label>
                                            <textarea class="form-control" id="sub_category_details" name="sub_category_details" rows="2"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Add Sub Category</button>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Sub Categories List -->
                            <div class="card">
                                <div class="card-header">
                                    <h5>Sub Categories</h5>
                                </div>
                                <div class="card-body" id="subCategoriesList">
                                    <p>Select a main category to view sub categories</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Child Category Form -->
                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>Add Child Category</h5>
                                </div>
                                <div class="card-body">
                                    <form method="post" id="childCategoryForm">
                                        <input type="hidden" name="form_type" value="child_category">
                                        <div class="mb-3">
                                            <label for="sub_category_select" class="form-label">Sub Category</label>
                                            <select class="form-select" id="sub_category_select" name="sub_category_id" required disabled>
                                                <option value="">Select Sub Category</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="child_category_name" class="form-label">Child Category Name</label>
                                            <input type="text" class="form-control" id="child_category_name" name="child_category_name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="child_category_details" class="form-label">Description</label>
                                            <textarea class="form-control" id="child_category_details" name="child_category_details" rows="2"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Add Child Category</button>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Child Categories List -->
                            <div class="card">
                                <div class="card-header">
                                    <h5>Child Categories</h5>
                                </div>
                                <div class="card-body" id="childCategoriesList">
                                    <p>Select a sub category to view child categories</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../requires/footer.php'; ?>
<script>
$(document).ready(function() {
    // Show alert message
    function showAlert(message, type) {
        var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        var alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        $('#alertContainer').html(alertHtml);
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    }
    
    // Handle main category selection change
    $('#main_category_select').change(function() {
        var mainCategoryId = $(this).val();
        $('#sub_category_select').prop('disabled', !mainCategoryId);
        
        if (mainCategoryId) {
            // Make AJAX request to get sub categories
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: {
                    form_type: 'get_sub_categories',
                    main_category_id: mainCategoryId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data && response.data.length) {
                        var html = '<ul class="list-group">';
                        $.each(response.data, function(index, subCategory) {
                            html += `
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    ${subCategory.category_name}
                                    ${subCategory.details ? `<small class="text-muted">${subCategory.details}</small>` : ''}
                                </li>
                            `;
                        });
                        html += '</ul>';
                        $('#subCategoriesList').html(html);
                        
                        // Update sub category select options
                        var options = '<option value="">Select Sub Category</option>';
                        $.each(response.data, function(index, subCategory) {
                            options += `<option value="${subCategory.id}">${subCategory.category_name}</option>`;
                        });
                        $('#sub_category_select').html(options);
                    } else {
                        $('#subCategoriesList').html('<p>No sub categories found for this main category</p>');
                        $('#sub_category_select').html('<option value="">No sub categories found</option>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', xhr.responseText);
                    $('#subCategoriesList').html('<p>Error loading sub categories</p>');
                    $('#sub_category_select').html('<option value="">Error loading</option>');
                }
            });
        } else {
            $('#subCategoriesList').html('<p>Select a main category to view sub categories</p>');
            $('#sub_category_select').html('<option value="">Select Main Category First</option>').prop('disabled', true);
            $('#childCategoriesList').html('<p>Select a sub category to view child categories</p>');
        }
    });
    
    // Handle sub category selection change
    $('#sub_category_select').change(function() {
        var subCategoryId = $(this).val();
        
        if (subCategoryId) {
            // Make AJAX request to get child categories
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: {
                    form_type: 'get_child_categories',
                    sub_category_id: subCategoryId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data && response.data.length) {
                        var html = '<ul class="list-group">';
                        $.each(response.data, function(index, childCategory) {
                            html += `
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    ${childCategory.category_name}
                                    ${childCategory.details ? `<small class="text-muted">${childCategory.details}</small>` : ''}
                                </li>
                            `;
                        });
                        html += '</ul>';
                        $('#childCategoriesList').html(html);
                    } else {
                        $('#childCategoriesList').html('<p>No child categories found for this sub category</p>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', xhr.responseText);
                    $('#childCategoriesList').html('<p>Error loading child categories</p>');
                }
            });
        } else {
            $('#childCategoriesList').html('<p>Select a sub category to view child categories</p>');
        }
    });
    
    // Handle main category form submission
    $('#mainCategoryForm').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert(response.message, 'success');
                    form[0].reset();
                    
                    if (response.categories) {
                        // Update main categories list
                        var html = '<ul class="list-group">';
                        $.each(response.categories, function(index, category) {
                            html += `
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    ${category.category}
                                    ${category.details ? `<small class="text-muted">${category.details}</small>` : ''}
                                </li>
                            `;
                        });
                        html += '</ul>';
                        $('#mainCategoriesList').html(html);
                        
                        // Update main category select dropdown
                        var options = '<option value="">Select Main Category</option>';
                        $.each(response.categories, function(index, category) {
                            options += `<option value="${category.id}">${category.category}</option>`;
                        });
                        $('#main_category_select').html(options);
                    }
                } else {
                    showAlert(response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                showAlert('Error: ' + error, 'error');
            }
        });
    });
    
    // Handle sub category form submission
    $('#subCategoryForm').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert(response.message, 'success');
                    form.find('#sub_category_name, #sub_category_details').val('');
                    
                    if (response.sub_categories) {
                        // Update sub categories list
                        var html = '<ul class="list-group">';
                        $.each(response.sub_categories, function(index, subCategory) {
                            html += `
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    ${subCategory.category_name}
                                    ${subCategory.details ? `<small class="text-muted">${subCategory.details}</small>` : ''}
                                </li>
                            `;
                        });
                        html += '</ul>';
                        $('#subCategoriesList').html(html);
                        
                        // Update sub category select
                        var options = '<option value="">Select Sub Category</option>';
                        $.each(response.sub_categories, function(index, subCategory) {
                            options += `<option value="${subCategory.id}">${subCategory.category_name}</option>`;
                        });
                        $('#sub_category_select').html(options);
                    }
                } else {
                    showAlert(response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                showAlert('Error: ' + error, 'error');
            }
        });
    });
    
    // Handle child category form submission
    $('#childCategoryForm').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert(response.message, 'success');
                    form.find('#child_category_name, #child_category_details').val('');
                    
                    if (response.child_categories) {
                        // Update child categories list
                        var html = '<ul class="list-group">';
                        $.each(response.child_categories, function(index, childCategory) {
                            html += `
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    ${childCategory.category_name}
                                    ${childCategory.details ? `<small class="text-muted">${childCategory.details}</small>` : ''}
                                </li>
                            `;
                        });
                        html += '</ul>';
                        $('#childCategoriesList').html(html);
                    }
                } else {
                    showAlert(response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                showAlert('Error: ' + error, 'error');
            }
        });
    });
});
</script>