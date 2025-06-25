<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php'; 

if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: stock_report.php");
    exit();
}

$product_id = (int)$_GET['id'];

// Get product details
$product = $mysqli->common_select('products', '*', ['id' => $product_id]);
if($product['error'] || empty($product['data'])) {
    header("Location: stock_report.php");
    exit();
}
$product = $product['data'][0];

$title = "Stock History - " . htmlspecialchars($product->name);

// Get stock history for this product
$query = "SELECT s.*, u.full_name as user_name 
          FROM stock s 
          LEFT JOIN users u ON s.user_id = u.id 
          WHERE s.product_id = $product_id 
          ORDER BY s.created_at DESC";
$history = $mysqli->getConnection()->query($query);

// Calculate current stock
$current_stock = $mysqli->getConnection()->query("SELECT COALESCE(SUM(qty), 0) as stock FROM stock WHERE product_id = $product_id")->fetch_object()->stock;
require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/sidebar.php';
require_once __DIR__ . '/../../requires/topbar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Stock History - <?= htmlspecialchars($product->name) ?></h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="stock_report.php">Stock Report</a></li>
                        <li class="breadcrumb-item active"><?= htmlspecialchars($product->name) ?></li>
                    </ul>
                </div>
                <div class="col-auto">
                    <a href="adjust_stock.php?product_id=<?= $product->id ?>" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Adjust Stock
                    </a>
                    <a href="stock_report.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Report
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="card-title">Stock Movement History</h5>
                            </div>
                            <div class="col-md-6 text-end">
                                <span class="badge bg-light text-dark">
                                    Current Stock: <?= $current_stock ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="productHistoryTable" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Type</th>
                                        <th>Qty Change</th>
                                        <th>Price</th>
                                        <th>Reference</th>
                                        <th>Date</th>
                                        <th>User</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($item = $history->fetch_object()): ?>
                                    <tr>
                                        <td><?= $item->id ?></td>
                                        <td>
                                            <?php 
                                            $badge_class = '';
                                            switch($item->change_type) {
                                                case 'purchase': $badge_class = 'bg-success'; break;
                                                case 'sale': $badge_class = 'bg-danger'; break;
                                                case 'adjustment': $badge_class = 'bg-warning'; break;
                                                case 'purchase_return': $badge_class = 'bg-info'; break;
                                                case 'sales_return': $badge_class = 'bg-primary'; break;
                                            }
                                            ?>
                                            <span class="badge <?= $badge_class ?>">
                                                <?= ucfirst(str_replace('_', ' ', $item->change_type)) ?>
                                            </span>
                                        </td>
                                        <td><?= $item->qty ?></td>
                                        <td><?= number_format($item->price, 2) ?></td>
                                        <td>
                                            <?php
                                            if($item->purchase_id) echo "Purchase #".$item->purchase_id;
                                            elseif($item->sale_id) echo "Sale #".$item->sale_id;
                                            elseif($item->adjustment_id) echo "Adjustment #".$item->adjustment_id;
                                            elseif($item->purchase_return_id) echo "Pur. Return #".$item->purchase_return_id;
                                            elseif($item->sales_return_id) echo "Sale Return #".$item->sales_return_id;
                                            ?>
                                        </td>
                                        <td><?= date('d M Y h:i A', strtotime($item->created_at)) ?></td>
                                        <td><?= $item->user_name ?? 'System' ?></td>
                                        <td><?= htmlspecialchars($item->note) ?></td>
                                    </tr>
                                    <?php endwhile; ?>
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

<script>
$(document).ready(function() {
    $('#productHistoryTable').DataTable({
        order: [[5, 'desc']]
    });
});
</script>