<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php'; 
$error = '';
$success = '';
$main_categories = [];
$sub_categories = [];
$child_categories = [];

// Get all categories for display
$result = $mysqli->common_select('category', '*', ['is_deleted' => 0], 'category', 'asc');
if (!$result['error']) {
    $main_categories = $result['data'];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Main Category
    if (isset($_POST['add_main_category'])) {
        $category = trim($_POST['main_category_name']);
        $details = trim($_POST['main_category_details']);
        
        if (empty($category)) {
            $error = 'Main category name is required';
        } else {
            // Check if category already exists
            $check = $mysqli->common_select('category', 'id', ['category' => $category, 'is_deleted' => 0]);
            if (!$check['error'] && !empty($check['data'])) {
                $error = 'Main category already exists';
            } else {
                $data = [
                    'category' => $category,
                    'details' => $details
                ];
                $result = $mysqli->common_insert('category', $data);
                if (!$result['error']) {
                    $success = 'Main category added successfully';
                    // Refresh the list
                    $main_result = $mysqli->common_select('category', '*', ['is_deleted' => 0], 'category', 'asc');
                    if (!$main_result['error']) {
                        $main_categories = $main_result['data'];
                    }
                } else {
                    $error = 'Error adding main category: ' . $result['error_msg'];
                }
            }
        }
    }
    
    // Add Sub Category
    if (isset($_POST['add_sub_category'])) {
        $main_category_id = (int)$_POST['main_category_id'];
        $sub_category_name = trim($_POST['sub_category_name']);
        $sub_category_details = trim($_POST['sub_category_details']);
        
        if (empty($main_category_id)) {
            $error = 'Please select a main category';
        } elseif (empty($sub_category_name)) {
            $error = 'Sub category name is required';
        } else {
            // Check if sub category already exists under this main category
            $check = $mysqli->common_select('sub_category', 'id', [
                'category_id' => $main_category_id,
                'category_name' => $sub_category_name,
                'is_deleted' => 0
            ]);
            
            if (!$check['error'] && !empty($check['data'])) {
                $error = 'Sub category already exists under this main category';
            } else {
                $data = [
                    'category_id' => $main_category_id,
                    'category_name' => $sub_category_name,
                    'details' => $sub_category_details
                ];
                $result = $mysqli->common_insert('sub_category', $data);
                if (!$result['error']) {
                    $success = 'Sub category added successfully';
                    // Refresh the sub categories list for the selected main category
                    $sub_result = $mysqli->common_select('sub_category', '*', [
                        'category_id' => $main_category_id,
                        'is_deleted' => 0
                    ], 'category_name', 'asc');
                    if (!$sub_result['error']) {
                        $sub_categories = $sub_result['data'];
                    }
                } else {
                    $error = 'Error adding sub category: ' . $result['error_msg'];
                }
            }
        }
    }
    
    // Add Child Category
    if (isset($_POST['add_child_category'])) {
        $sub_category_id = (int)$_POST['sub_category_id'];
        $child_category_name = trim($_POST['child_category_name']);
        $child_category_details = trim($_POST['child_category_details']);
        
        if (empty($sub_category_id)) {
            $error = 'Please select a sub category';
        } elseif (empty($child_category_name)) {
            $error = 'Child category name is required';
        } else {
            // Check if child category already exists under this sub category
            $check = $mysqli->common_select('child_category', 'id', [
                'sub_category_id' => $sub_category_id,
                'category_name' => $child_category_name,
                'is_deleted' => 0
            ]);
            
            if (!$check['error'] && !empty($check['data'])) {
                $error = 'Child category already exists under this sub category';
            } else {
                $data = [
                    'sub_category_id' => $sub_category_id,
                    'category_name' => $child_category_name,
                    'details' => $child_category_details
                ];
                $result = $mysqli->common_insert('child_category', $data);
                if (!$result['error']) {
                    $success = 'Child category added successfully';
                    // Refresh the child categories list for the selected sub category
                    $child_result = $mysqli->common_select('child_category', '*', [
                        'sub_category_id' => $sub_category_id,
                        'is_deleted' => 0
                    ], 'category_name', 'asc');
                    if (!$child_result['error']) {
                        $child_categories = $child_result['data'];
                    }
                } else {
                    $error = 'Error adding child category: ' . $result['error_msg'];
                }
            }
        }
    }
    
    // Get sub categories when main category is selected
    if (isset($_POST['get_sub_categories'])) {
        $main_category_id = (int)$_POST['main_category_id'];
        if ($main_category_id > 0) {
            $result = $mysqli->common_select('sub_category', '*', [
                'category_id' => $main_category_id,
                'is_deleted' => 0
            ], 'category_name', 'asc');
            if (!$result['error']) {
                $sub_categories = $result['data'];
            }
        } else {
            $sub_categories = [];
        }
    }
    
    // Get child categories when sub category is selected
    if (isset($_POST['get_child_categories'])) {
        $sub_category_id = (int)$_POST['sub_category_id'];
        if ($sub_category_id > 0) {
            $result = $mysqli->common_select('child_category', '*', [
                'sub_category_id' => $sub_category_id,
                'is_deleted' => 0
            ], 'category_name', 'asc');
            if (!$result['error']) {
                $child_categories = $result['data'];
            }
        } else {
            $child_categories = [];
        }
    }
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
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <!-- Main Category Form -->
                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>Add Main Category</h5>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="mb-3">
                                            <label for="main_category_name" class="form-label">Category Name</label>
                                            <input type="text" class="form-control" id="main_category_name" name="main_category_name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="main_category_details" class="form-label">Description</label>
                                            <textarea class="form-control" id="main_category_details" name="main_category_details" rows="2"></textarea>
                                        </div>
                                        <button type="submit" name="add_main_category" class="btn btn-primary">Add Main Category</button>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Main Categories List -->
                            <div class="card">
                                <div class="card-header">
                                    <h5>Main Categories</h5>
                                </div>
                                <div class="card-body">
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
                                    <form method="post">
                                        <div class="mb-3">
                                            <label for="main_category_select" class="form-label">Main Category</label>
                                            <select class="form-select" id="main_category_select" name="main_category_id" required onchange="this.form.submit()">
                                                <option value="">Select Main Category</option>
                                                <?php foreach ($main_categories as $category): ?>
                                                    <option value="<?= $category->id ?>" <?= isset($_POST['main_category_id']) && $_POST['main_category_id'] == $category->id ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($category->category) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="get_sub_categories" value="1">
                                        </div>
                                        
                                        <?php if (!empty($_POST['main_category_id'])): ?>
                                            <div class="mb-3">
                                                <label for="sub_category_name" class="form-label">Sub Category Name</label>
                                                <input type="text" class="form-control" id="sub_category_name" name="sub_category_name" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="sub_category_details" class="form-label">Description</label>
                                                <textarea class="form-control" id="sub_category_details" name="sub_category_details" rows="2"></textarea>
                                            </div>
                                            <button type="submit" name="add_sub_category" class="btn btn-primary">Add Sub Category</button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Sub Categories List -->
                            <?php if (!empty($_POST['main_category_id'])): ?>
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Sub Categories</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($sub_categories)): ?>
                                            <ul class="list-group">
                                                <?php foreach ($sub_categories as $sub): ?>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <?= htmlspecialchars($sub->category_name) ?>
                                                        <?php if ($sub->details): ?>
                                                            <small class="text-muted"><?= htmlspecialchars($sub->details) ?></small>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <p>No sub categories found for this main category</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Child Category Form -->
                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>Add Child Category</h5>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <?php if (!empty($_POST['main_category_id'])): ?>
                                            <div class="mb-3">
                                                <label for="sub_category_select" class="form-label">Sub Category</label>
                                                <select class="form-select" id="sub_category_select" name="sub_category_id" required onchange="this.form.submit()">
                                                    <option value="">Select Sub Category</option>
                                                    <?php foreach ($sub_categories as $sub): ?>
                                                        <option value="<?= $sub->id ?>" <?= isset($_POST['sub_category_id']) && $_POST['sub_category_id'] == $sub->id ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($sub->category_name) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="hidden" name="main_category_id" value="<?= $_POST['main_category_id'] ?>">
                                                <input type="hidden" name="get_sub_categories" value="1">
                                                <input type="hidden" name="get_child_categories" value="1">
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($_POST['sub_category_id'])): ?>
                                            <div class="mb-3">
                                                <label for="child_category_name" class="form-label">Child Category Name</label>
                                                <input type="text" class="form-control" id="child_category_name" name="child_category_name" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="child_category_details" class="form-label">Description</label>
                                                <textarea class="form-control" id="child_category_details" name="child_category_details" rows="2"></textarea>
                                            </div>
                                            <button type="submit" name="add_child_category" class="btn btn-primary">Add Child Category</button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Child Categories List -->
                            <?php if (!empty($_POST['sub_category_id'])): ?>
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Child Categories</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($child_categories)): ?>
                                            <ul class="list-group">
                                                <?php foreach ($child_categories as $child): ?>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <?= htmlspecialchars($child->category_name) ?>
                                                        <?php if ($child->details): ?>
                                                            <small class="text-muted"><?= htmlspecialchars($child->details) ?></small>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <p>No child categories found for this sub category</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </main>
            </div>
        </div>

    </diV>
</diV>
<?php require_once __DIR__ . '/../../requires/footer.php'; ?>