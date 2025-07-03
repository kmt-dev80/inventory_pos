<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';

// Set low stock threshold
$low_stock_threshold = 10; // Default threshold value

$query = "SELECT 
    p.id, p.name, p.barcode, 
    p.sell_price as default_sell_price,  /* Directly from products table */
    COALESCE(SUM(s.qty), 0) as current_stock,
    
    /* Actual average cost price */
    CASE 
        WHEN SUM(CASE WHEN s.change_type IN ('purchase', 'adjustment') THEN s.qty ELSE 0 END) = 0 
        THEN p.price
        ELSE 
            SUM(CASE WHEN s.change_type IN ('purchase', 'adjustment') THEN s.qty * s.price ELSE 0 END) 
            / 
            SUM(CASE WHEN s.change_type IN ('purchase', 'adjustment') THEN s.qty ELSE 0 END)
    END AS avg_cost_price
    
   
FROM products p
LEFT JOIN stock s ON p.id = s.product_id
WHERE p.is_deleted = 0
GROUP BY p.id
HAVING current_stock <= ?
ORDER BY current_stock ASC";

$stmt = $mysqli->getConnection()->prepare($query);
$stmt->bind_param('i', $low_stock_threshold);
$stmt->execute();
$products = $stmt->get_result();

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/sidebar.php';
require_once __DIR__ . '/../../requires/topbar.php';
?>
<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Low Stock Alerts</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/inventory/stock_logs.php">View Logs</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/inventory/stock_report.php">Stock Report</a></li>
                        <li class="breadcrumb-item active">Low Stock</li>
                    </ul>
                </div>
                <div class="col-auto">
                    <span class="badge badge-danger">
                        Threshold: <?= $low_stock_threshold ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <h5 class="card-title">Products Below Stock Threshold</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if($products->num_rows === 0): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> No products are currently below the stock threshold.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table id="lowStockTable" class="display table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Product</th>
                                            <th>Barcode</th>
                                            <th>Current Stock</th>
                                            <th>Cost Price</th>
                                            <th>Sell Price</th>  <!-- Now shows direct from products table -->
                                            <th>Stock Value</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($product = $products->fetch_object()): 
                                            $stock_value = ($product->current_stock ?? 0) * $product->avg_cost_price;
                                            $stock_class = $product->current_stock <= 0 ? 'text-danger' : 'text-warning';
                                        ?>
                                        <tr>
                                            <td><?= $product->id ?></td>
                                            <td><?= htmlspecialchars($product->name) ?></td>
                                            <td><?= $product->barcode ?></td>
                                            <td class="<?= $stock_class ?>"><?= $product->current_stock ?></td>
                                            <td><?= number_format($product->avg_cost_price, 2) ?></td>
                                            <td><?= number_format($product->default_sell_price, 2) ?></td>  <!-- Changed to default_sell_price -->
                                            <td><?= number_format($stock_value, 2) ?></td>
                                            <td>
                                                <a href="product_stock_history.php?id=<?= $product->id ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-history"></i> History
                                                </a>
                                                <a href="adjust_stock.php?product_id=<?= $product->id ?>" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i> Adjust
                                                </a>
                                                <a href="<?= BASE_URL ?>modules/purchase/add_purchase.php?product_id=<?= $product->id ?>" class="btn btn-sm btn-success">
                                                    <i class="fas fa-cart-plus"></i> Purchase
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../requires/footer.php'; ?>
<script>
$(document).ready(function() {
    $('#lowStockTable').DataTable({
        dom: 'lBfrtip',
        buttons: ['csv', 'excel', 'pdf'],
        order: [[0, 'desc']]
    });
});
</script>