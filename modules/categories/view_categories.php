<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';

// Get all categories with their hierarchy
$main_categories = $mysqli->common_select('category', '*', ['is_deleted' => 0], 'category', 'asc')['data'];

// Build category tree
$category_tree = [];
foreach ($main_categories as $main_cat) {
    $main_cat->sub_categories = $mysqli->common_select('sub_category', '*', 
        ['category_id' => $main_cat->id, 'is_deleted' => 0], 'category_name', 'asc')['data'];
    
    foreach ($main_cat->sub_categories as $sub_cat) {
        $sub_cat->child_categories = $mysqli->common_select('child_category', '*', 
            ['sub_category_id' => $sub_cat->id, 'is_deleted' => 0], 'category_name', 'asc')['data'];
    }
    
    $category_tree[] = $main_cat;
}

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Category Management</h4>
            <div class="ms-auto">
                <a href="add_category.php" class="btn btn-primary btn-round">
                    <i class="fas fa-plus"></i> Add Category
                </a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Category Hierarchy</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="bg-primary text-white">
                                    <tr>
                                        <th>Main Category</th>
                                        <th>Sub Categories</th>
                                        <th>Child Categories</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($category_tree as $main_cat): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($main_cat->category) ?></strong>
                                                <?php if ($main_cat->details): ?>
                                                    <p class="text-muted mb-0"><?= htmlspecialchars($main_cat->details) ?></p>
                                                <?php endif; ?>
                                                <div class="mt-2">
                                                    <a href="edit_category.php?edit_main=<?= $main_cat->id ?>" class="btn btn-xs btn-info">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="delete_category.php?type=main&id=<?= $main_cat->id ?>" 
                                                       class="btn btn-xs btn-danger"
                                                       onclick="return confirm('Delete this main category and all its sub/child categories?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!empty($main_cat->sub_categories)): ?>
                                                    <ul class="list-unstyled">
                                                        <?php foreach ($main_cat->sub_categories as $sub_cat): ?>
                                                            <li class="mb-2">
                                                                <strong><?= htmlspecialchars($sub_cat->category_name) ?></strong>
                                                                <?php if ($sub_cat->details): ?>
                                                                    <p class="text-muted mb-0"><?= htmlspecialchars($sub_cat->details) ?></p>
                                                                <?php endif; ?>
                                                                <div class="mt-1">
                                                                    <a href="edit_category.php?edit_sub=<?= $sub_cat->id ?>" class="btn btn-xs btn-info">
                                                                        <i class="fas fa-edit"></i>
                                                                    </a>
                                                                    <a href="delete_category.php?type=sub&id=<?= $sub_cat->id ?>" 
                                                                       class="btn btn-xs btn-danger"
                                                                       onclick="return confirm('Delete this sub-category and all its child categories?')">
                                                                        <i class="fas fa-trash"></i>
                                                                    </a>
                                                                </div>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <span class="text-muted">No sub-categories</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($main_cat->sub_categories)): ?>
                                                    <ul class="list-unstyled">
                                                        <?php foreach ($main_cat->sub_categories as $sub_cat): ?>
                                                            <?php if (!empty($sub_cat->child_categories)): ?>
                                                                <?php foreach ($sub_cat->child_categories as $child_cat): ?>
                                                                    <li class="mb-2">
                                                                        <strong><?= htmlspecialchars($child_cat->category_name) ?></strong>
                                                                        <?php if ($child_cat->details): ?>
                                                                            <p class="text-muted mb-0"><?= htmlspecialchars($child_cat->details) ?></p>
                                                                        <?php endif; ?>
                                                                        <div class="mt-1">
                                                                            <a href="edit_category.php?edit_child=<?= $child_cat->id ?>" class="btn btn-xs btn-info">
                                                                                <i class="fas fa-edit"></i>
                                                                            </a>
                                                                            <a href="delete_category.php?type=child&id=<?= $child_cat->id ?>" 
                                                                               class="btn btn-xs btn-danger"
                                                                               onclick="return confirm('Delete this child category?')">
                                                                                <i class="fas fa-trash"></i>
                                                                            </a>
                                                                        </div>
                                                                    </li>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <span class="text-muted">No child categories</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../requires/footer.php'; ?>