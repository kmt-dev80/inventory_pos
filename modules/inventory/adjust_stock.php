<?php
require_once __DIR__ . '/../includes/db_plugin.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Check if user has permission to adjust stock
if($_SESSION['user']->role !== 'admin' && $_SESSION['user']->role !== 'manager' && $_SESSION['user']->role !== 'inventory') {
    header("Location: stock_report.php");
    exit();
}

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

// Get product details if product_id is provided
$product = null;
if($product_id > 0) {
    $result = $mysqli->common_select('products', '*', ['id' => $product_id, 'is_deleted' => 0]);
    if(!$result['error'] && !empty($result['data'])) {
        $product = $result['data'][0];
    }
}

// Get all active products for dropdown
$products_result = $mysqli->common_select('products', 'id, name, barcode', ['is_deleted' => 0], 'name');

$title = $product ? "Adjust Stock - " . htmlspecialchars($product->name) : "Adjust Stock";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/topbar.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Process form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adjustment_data = [
        'product_id' => (int)$_POST['product_id'],
        'user_id' => $_SESSION['user']->id,
        'adjustment_type' => $_POST['adjustment_type'],
        'quantity' => abs((int)$_POST['quantity']),
        'reason' => $_POST['reason']
    ];
    
    // Start transaction
    $mysqli->begin_transaction();
    
    try {
        // 1. Insert into inventory_adjustments table
        $adjustment_result = $mysqli->common_insert('inventory_adjustments', $adjustment_data);
        
        if($adjustment_result['error']) {
            throw new Exception("Failed to record adjustment: " . $adjustment_result['error_msg']);
        }
        
        $adjustment_id = $adjustment_result['data'];
        
        // 2. Insert into stock table
        $qty_change = ($adjustment_data['adjustment_type'] === 'add') ? 
                      $adjustment_data['quantity'] : 
                      -$adjustment_data['quantity'];
        
        $stock_data = [
            'product_id' => $adjustment_data['product_id'],
            'user_id' => $adjustment_data['user_id'],
            'change_type' => 'adjustment',
            'qty' => $qty_change,
            'price' => 0, // You might want to get current price from products table
            'adjustment_id' => $adjustment_id,
            'note' => $adjustment_data['reason']
        ];
        
        $stock_result = $mysqli->common_insert('stock', $stock_data);
        
        if($stock_result['error']) {
            throw new Exception("Failed to update stock: " . $stock_result['error_msg']);
        }
        
        // Commit transaction
        $mysqli->commit();
        
        $_SESSION['success'] = "Stock adjustment recorded successfully!";
        header("Location: product_stock_history.php?id=" . $adjustment_data['product_id']);
        exit();
        
    } catch(Exception $e) {
        $mysqli->rollback();
        $error_msg = $e->getMessage();
    }
}
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title"><?= $title ?></h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="stock_report.php">Stock Report</a></li>
                        <li class="breadcrumb-item active">Adjust Stock</li>
                    </ul>
                </div>
                <div class="col-auto">
                    <a href="stock_report.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Report
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Stock Adjustment Form</h5>
                    </div>
                    <div class="card-body">
                        <?php if(isset($error_msg)): ?>
                            <div class="alert alert-danger"><?= $error_msg ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" id="adjustmentForm">
                            <div class="form-group">
                                <label for="product_id">Product</label>
                                <select class="form-control" id="product_id" name="product_id" required>
                                    <option value="">Select Product</option>
                                    <?php if($products_result && !$products_result['error']): ?>
                                        <?php foreach($products_result['data'] as $p): ?>
                                            <option value="<?= $p->id ?>" <?= $product && $product->id == $p->id ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($p->name) ?> (<?= $p->barcode ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="adjustment_type">Adjustment Type</label>
                                <select class="form-control" id="adjustment_type" name="adjustment_type" required>
                                    <option value="add">Add Stock</option>
                                    <option value="remove">Remove Stock</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="quantity">Quantity</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="reason">Reason</label>
                                <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Record Adjustment</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <?php if($product): ?>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Product Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th>Product Name</th>
                                        <td><?= htmlspecialchars($product->name) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Barcode</th>
                                        <td><?= $product->barcode ?></td>
                                    </tr>
                                    <tr>
                                        <th>Cost Price</th>
                                        <td><?= number_format($product->price, 2) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Sell Price</th>
                                        <td><?= number_format($product->sell_price, 2) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#product_id').select2({
        placeholder: "Select a product",
        allowClear: true
    });
    
    // If product is pre-selected, fetch current stock
    <?php if($product): ?>
    fetchCurrentStock(<?= $product->id ?>);
    <?php endif; ?>
    
    // When product selection changes
    $('#product_id').change(function() {
        var productId = $(this).val();
        if(productId) {
            fetchCurrentStock(productId);
        }
    });
    
    function fetchCurrentStock(productId) {
        $.ajax({
            url: '<?= BASE_URL ?>ajax/get_stock.php',
            method: 'GET',
            data: { product_id: productId },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#currentStockDisplay').remove();
                    $('<div id="currentStockDisplay" class="alert alert-info mt-3">Current Stock: ' + response.stock + '</div>')
                        .insertBefore('#adjustmentForm button');
                }
            }
        });
    }
});
</script>