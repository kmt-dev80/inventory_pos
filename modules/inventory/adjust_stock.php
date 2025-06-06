<?php
session_start();
require_once __DIR__ . '/../../db_plugin.php';

// API Endpoint for AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    try {
        if ($_GET['action'] === 'get_stock' && isset($_GET['product_id'])) {
            $product_id = (int)$_GET['product_id'];
            
            if ($product_id <= 0) {
                throw new Exception('Invalid product ID');
            }

            // Calculate current stock
            $query = "SELECT COALESCE(SUM(CASE 
                            WHEN s.change_type = 'purchase' THEN s.qty 
                            WHEN s.change_type = 'purchase_return' THEN s.qty 
                            WHEN s.change_type = 'sales_return' THEN s.qty 
                            WHEN s.change_type = 'adjustment' AND s.qty > 0 THEN s.qty 
                            ELSE 0 
                         END), 0) - 
                         COALESCE(SUM(CASE 
                            WHEN s.change_type = 'sale' THEN s.qty 
                            WHEN s.change_type = 'adjustment' AND s.qty < 0 THEN ABS(s.qty) 
                            ELSE 0 
                         END), 0) as current_stock
                      FROM products p
                      LEFT JOIN stock s ON p.id = s.product_id
                      WHERE p.id = $product_id
                      AND p.is_deleted = 0";

            $result = $mysqli->getConnection()->query($query);
            if (!$result) {
                throw new Exception('Database query failed');
            }
            
            $stock = $result->fetch_object()->current_stock ?? 0;

            echo json_encode(['success' => true, 'stock' => $stock]);
            exit();
        }
        
        if ($_GET['action'] === 'get_product_info' && isset($_GET['product_id'])) {
            $product_id = (int)$_GET['product_id'];
            
            if ($product_id <= 0) {
                throw new Exception('Invalid product ID');
            }
            
            $result = $mysqli->common_select('products', '*', ['id' => $product_id, 'is_deleted' => 0]);
            if ($result['error'] || empty($result['data'])) {
                throw new Exception('Product not found');
            }
            
            $product = $result['data'][0];
            echo json_encode([
                'success' => true,
                'product' => [
                    'name' => htmlspecialchars($product->name),
                    'barcode' => $product->barcode,
                    'price' => number_format($product->price, 2),
                    'sell_price' => number_format($product->sell_price, 2)
                ]
            ]);
            exit();
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// Regular Page Functionality
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

// Get all active products for dropdown
$products_result = $mysqli->common_select('products', 'id, name, barcode', ['is_deleted' => 0], 'name');

$title = "Adjust Stock";

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $errors = [];
    $product_id = (int)$_POST['product_id'];
    $quantity = abs((int)$_POST['quantity']);
    $adjustment_type = $_POST['adjustment_type'];
    $reason = trim($_POST['reason']);

    if ($product_id <= 0) {
        $errors[] = "Please select a valid product";
    }
    
    if ($quantity <= 0) {
        $errors[] = "Quantity must be greater than zero";
    }
    
    if (!in_array($adjustment_type, ['add', 'remove'])) {
        $errors[] = "Invalid adjustment type";
    }
    
    if (empty($reason)) {
        $errors[] = "Reason is required";
    }

    if (empty($errors)) {
        $adjustment_data = [
            'product_id' => $product_id,
            'user_id' => $_SESSION['user']->id,
            'adjustment_type' => $adjustment_type,
            'quantity' => $quantity,
            'reason' => $reason
        ];
        
        // Start transaction
        $mysqli->begin_transaction();
        
        try {
            // 1. Insert into inventory_adjustments table
            $adjustment_result = $mysqli->common_insert('inventory_adjustments', $adjustment_data);
            
            if ($adjustment_result['error']) {
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
                'price' => 0, // get current price from products table
                'adjustment_id' => $adjustment_id,
                'note' => $adjustment_data['reason']
            ];
            
            $stock_result = $mysqli->common_insert('stock', $stock_data);
            
            if ($stock_result['error']) {
                throw new Exception("Failed to update stock: " . $stock_result['error_msg']);
            }
            
            // Commit transaction
            $mysqli->commit();
            
            $_SESSION['success'] = "Stock adjustment recorded successfully!";
            header("Location: product_stock_history.php?id=" . $adjustment_data['product_id']);
            exit();
            
        } catch (Exception $e) {
            $mysqli->rollback();
            $error_msg = $e->getMessage();
        }
    } else {
        $error_msg = implode("<br>", $errors);
    }
}

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title"><?= $title ?></h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>index.php">Dashboard</a></li>
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
                        <?php if (isset($error_msg)): ?>
                            <div class="alert alert-danger"><?= $error_msg ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" id="adjustmentForm">
                            <div class="form-group">
                                <label for="product_id">Product</label>
                                <select class="form-control" id="product_id" name="product_id" required>
                                    <option value="">-- Select Product --</option>
                                    <?php if ($products_result && !$products_result['error']): ?>
                                        <?php foreach ($products_result['data'] as $p): ?>
                                            <option value="<?= $p->id ?>" <?= $product_id == $p->id ? 'selected' : '' ?>>
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
                                <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="<?= isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : '' ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="reason">Reason</label>
                                <textarea class="form-control" id="reason" name="reason" rows="3" required><?= isset($_POST['reason']) ? htmlspecialchars($_POST['reason']) : '' ?></textarea>
                            </div>
                            
                            <div id="currentStockDisplay" class="alert alert-info mt-3" style="display: none;">
                                Current Stock: <span id="stockValue">0</span>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Record Adjustment</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Product Information</h5>
                    </div>
                    <div class="card-body">
                        <div id="productInfoPlaceholder" class="text-muted">
                            Select a product to view details
                        </div>
                        <div id="productInfoContent" style="display: none;">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <tbody>
                                        <tr>
                                            <th>Product Name</th>
                                            <td id="productName"></td>
                                        </tr>
                                        <tr>
                                            <th>Barcode</th>
                                            <td id="productBarcode"></td>
                                        </tr>
                                        <tr>
                                            <th>Cost Price</th>
                                            <td id="productPrice"></td>
                                        </tr>
                                        <tr>
                                            <th>Sell Price</th>
                                            <td id="productSellPrice"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../requires/footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialize Select2
    $('#product_id').select2({
        placeholder: "Select a product",
        allowClear: true
    }).on('select2:select', function() {
        var productId = $(this).val();
        if (productId) {
            fetchProductInfo(productId);
            fetchCurrentStock(productId);
        } else {
            resetProductInfo();
            hideStockDisplay();
        }
    });
    
    // Initialize with selected product if any
    var initialProductId = $('#product_id').val();
    if (initialProductId) {
        fetchProductInfo(initialProductId);
        fetchCurrentStock(initialProductId);
    }

    function fetchProductInfo(productId) {
        $.ajax({
            url: '<?= $_SERVER['PHP_SELF'] ?>?action=get_product_info&product_id=' + productId,
            method: 'GET',
            dataType: 'json',
            beforeSend: function() {
                $('#productInfoPlaceholder').html('Loading product information...');
            },
            success: function(response) {
                if (response.success) {
                    $('#productName').text(response.product.name);
                    $('#productBarcode').text(response.product.barcode);
                    $('#productPrice').text(response.product.price);
                    $('#productSellPrice').text(response.product.sell_price);
                    
                    $('#productInfoPlaceholder').hide();
                    $('#productInfoContent').show();
                } else {
                    $('#productInfoPlaceholder').html('Failed to load product information');
                    $('#productInfoContent').hide();
                }
            },
            error: function() {
                $('#productInfoPlaceholder').html('Error loading product information');
                $('#productInfoContent').hide();
            }
        });
    }
    
    function fetchCurrentStock(productId) {
        $.ajax({
            url: '<?= $_SERVER['PHP_SELF'] ?>?action=get_stock&product_id=' + productId,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#stockValue').text(response.stock);
                    $('#currentStockDisplay').show();
                }
            },
            error: function() {
                console.error('Failed to fetch stock data');
            }
        });
    }
    
    function resetProductInfo() {
        $('#productInfoPlaceholder').html('Select a product to view details');
        $('#productInfoContent').hide();
    }
    
    function hideStockDisplay() {
        $('#currentStockDisplay').hide();
    }
    
    // Form validation
    $('#adjustmentForm').submit(function(e) {
        var productId = $('#product_id').val();
        var quantity = $('#quantity').val();
        var reason = $('#reason').val().trim();
        
        if (!productId) {
            alert('Please select a product');
            e.preventDefault();
            return false;
        }
        
        if (!quantity || isNaN(quantity)) {
            alert('Please enter a valid quantity');
            e.preventDefault();
            return false;
        }
        
        if (!reason) {
            alert('Please enter a reason for the adjustment');
            e.preventDefault();
            return false;
        }
        
        return true;
    });
});
</script>