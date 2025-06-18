<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}

require_once __DIR__ . '/../../db_plugin.php'; 

// Get all deleted products
$result = $mysqli->common_select('products', '*', ['is_deleted' => 1], 'name', 'asc');
$products = $result['error'] ? [] : $result['data'];

// Handle restore request
if (isset($_GET['restore'])) {
    $product_id = (int)$_GET['restore'];
    if ($product_id > 0) {
        $result = $mysqli->common_update('products', [
            'is_deleted' => 0,
            'deleted_at' => null
        ], ['id' => $product_id]);
        
        if (!$result['error']) {
            $_SESSION['success'] = 'Product restored successfully';
            header("Location: trash_product.php");
            exit();
        } else {
            $_SESSION['error'] = 'Error restoring product: ' . $result['error_msg'];
        }
    }
}

// Handle permanent delete request
if (isset($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    if ($product_id > 0) {
        $result = $mysqli->common_delete('products', ['id' => $product_id]);
        
        if (!$result['error']) {
            $_SESSION['success'] = 'Product permanently deleted';
            header("Location: trash_product.php");
            exit();
        } else {
            $_SESSION['error'] = 'Error deleting product: ' . $result['error_msg'];
        }
    }
}

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Trash</h4>
            <a href="view_product.php" class="btn btn-secondary btn-round ms-auto">
                <i class="fas fa-arrow-left"></i> Back to Products
            </a>
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
                        <div class="card-title">Deleted Products</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="trashTable" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Barcode</th>
                                        <th>Product Name</th>
                                        <th>Deleted On</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $index => $product): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($product->barcode) ?></td>
                                            <td><?= htmlspecialchars($product->name) ?></td>
                                            <td><?= date('M d, Y h:i A', strtotime($product->deleted_at)) ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <?php if ($_SESSION['user']->role == 'admin'): ?>
                                                    <a href="trash_product.php?restore=<?= $product->id ?>" class="btn btn-sm btn-success" onclick="return confirm('Restore this product?')">
                                                        <i class="fas fa-undo"></i> Restore
                                                    </a>
                                                    <a href="trash_product.php?delete=<?= $product->id ?>" class="btn btn-sm btn-danger" onclick="return confirm('Permanently delete this product?')">
                                                        <i class="fas fa-trash-alt"></i> Delete
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