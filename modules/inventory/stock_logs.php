<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php'; 
require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/sidebar.php';
require_once __DIR__ . '/../../requires/topbar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Stock Logs</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/inventory/stock_report.php">Stock Report</a></li>
                        <li class="breadcrumb-item active">Stock Logs</li>
                    </ul>
                </div>
                <div class="col-auto">
                    <a href="adjust_stock.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Adjust Stock
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Stock Movement History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="stockTable" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Product</th>
                                        <th>Type</th>
                                        <th>Qty</th>
                                        <th>Price</th>
                                        <th>Reference</th>
                                        <th>Date</th>
                                        <th>User</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Join with products and users table to get names
                                    $query = "SELECT s.*, p.name as product_name, u.full_name as user_name 
                                              FROM stock s 
                                              LEFT JOIN products p ON s.product_id = p.id 
                                              LEFT JOIN users u ON s.user_id = u.id 
                                              ORDER BY s.created_at DESC";
                                    
                                    $result = $mysqli->getConnection()->query($query);
                                    
                                    while($row = $result->fetch_object()):
                                        // Determine reference based on type
                                        $reference = '';
                                        if($row->purchase_id) $reference = 'Purchase #'.$row->purchase_id;
                                        elseif($row->sale_id) $reference = 'Sale #'.$row->sale_id;
                                        elseif($row->adjustment_id) $reference = 'Adjustment #'.$row->adjustment_id;
                                        elseif($row->purchase_return_id) $reference = 'Pur. Return #'.$row->purchase_return_id;
                                        elseif($row->sales_return_id) $reference = 'Sale Return #'.$row->sales_return_id;
                                    ?>
                                    <tr>
                                        <td><?= $row->id ?></td>
                                        <td><?= htmlspecialchars($row->product_name) ?></td>
                                        <td>
                                            <?php 
                                            $badge_class = '';
                                            switch($row->change_type) {
                                                case 'purchase': $badge_class = 'bg-success'; break;
                                                case 'sale': $badge_class = 'bg-danger'; break;
                                                case 'adjustment': $badge_class = 'bg-warning'; break;
                                                case 'purchase_return': $badge_class = 'bg-info'; break;
                                                case 'sales_return': $badge_class = 'bg-primary'; break;
                                            }
                                            ?>
                                            <span class="badge <?= $badge_class ?>">
                                                <?= ucfirst(str_replace('_', ' ', $row->change_type)) ?>
                                            </span>
                                        </td>
                                        <td><?= $row->qty ?></td>
                                        <td><?= number_format($row->price, 2) ?></td>
                                        <td><?= $reference ?></td>
                                        <td><?= date('d M Y h:i A', strtotime($row->created_at)) ?></td>
                                        <td><?= $row->user_name ?? 'System' ?></td>
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
    $('#stockTable').DataTable({
        order: [[0, 'desc']]
    });
});
</script>