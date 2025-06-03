<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';

// Set low stock threshold (hardcoded or could be fetched from session/database)
$low_stock_threshold = 10; // Default threshold value

// Get low stock products
$query = "SELECT p.id, p.name, p.barcode, p.price, p.sell_price, 
                 COALESCE(SUM(s.qty), 0) as current_stock
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
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';
?>
<!-- Print Styles -->
<style media="print">
    body * {
        visibility: hidden;
    }
    .card, .card * {
        visibility: visible;
    }
    .card {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        border: none;
    }
    .dataTables_length, .dataTables_filter, 
    .dataTables_info, .dataTables_paginate,
    .page-header, .breadcrumb, .col-auto {
        display: none !important;
    }
    table {
        width: 100% !important;
    }
    .card-header {
        text-align: center;
    }
    .card-header h5:after {
        content: " - <?= date('Y-m-d') ?>";
    }
</style>
<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Low Stock Alerts</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="stock_report.php">Inventory</a></li>
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
                            <div class="ml-auto">
                                <button class="btn btn-sm btn-outline-primary" onclick="window.print()">
                                    <i class="fas fa-print"></i> Print Report
                                </button>
                            </div>
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
                                            <th>Sell Price</th>
                                            <th>Stock Value</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($product = $products->fetch_object()): 
                                            $stock_value = $product->current_stock * $product->price;
                                            $stock_class = $product->current_stock <= 0 ? 'text-danger' : 'text-warning';
                                        ?>
                                        <tr>
                                            <td><?= $product->id ?></td>
                                            <td><?= htmlspecialchars($product->name) ?></td>
                                            <td><?= $product->barcode ?></td>
                                            <td class="<?= $stock_class ?>"><?= $product->current_stock ?></td>
                                            <td><?= number_format($product->price, 2) ?></td>
                                            <td><?= number_format($product->sell_price, 2) ?></td>
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
        dom: '<"top"f>rt<"bottom"lip><"clear">',
        order: [[3, 'asc']], // Sort by stock level (ascending)
        pageLength: 25,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search products...",
            lengthMenu: "Show _MENU_ products per page",
            info: "Showing _START_ to _END_ of _TOTAL_ low stock products",
            infoEmpty: "No products found",
            infoFiltered: "(filtered from _MAX_ total products)"
        }
    });
});
</script>