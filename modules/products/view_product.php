<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}

require_once __DIR__ . '/../../db_plugin.php';

// Get all active products
$result = $mysqli->common_select('products', '*', ['is_deleted' => 0], 'name', 'asc');
$products = $result['error'] ? [] : $result['data'];

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Product List</h4>
            <div class="ms-auto">
                <a href="add_product.php" class="btn btn-primary btn-round">
                    <i class="fas fa-plus"></i> Add Product
                </a>
                <a href="trash_product.php" class="btn btn-warning btn-round">
                    <i class="fas fa-trash"></i> View Trash
                </a>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">All Products</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="productTable" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Barcode</th>
                                        <th>Product Name</th>
                                        <th>Category</th>
                                        <th>Purchase Price</th>
                                        <th>Selling Price</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $index => $product): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($product->barcode) ?></td>
                                            <td><?= htmlspecialchars($product->name) ?></td>
                                            <td>
                                                <?php 
                                                $category = '';
                                                if ($product->category_id) {
                                                    $cat = $mysqli->common_select('category', 'category', ['id' => $product->category_id]);
                                                    if (!$cat['error'] && !empty($cat['data'])) {
                                                        $category = $cat['data'][0]->category;
                                                    }
                                                }
                                                echo htmlspecialchars($category);
                                                ?>
                                            </td>
                                            <td><?= number_format($product->price, 2) ?></td>
                                            <td><?= number_format($product->sell_price, 2) ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <?php if ($_SESSION['user']->role == 'admin'): ?>
                                                    <a href="edit_product.php?id=<?= $product->id ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="delete_product.php?id=<?= $product->id ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
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