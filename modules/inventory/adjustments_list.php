<?php
require_once __DIR__ . '/../includes/db_plugin.php';
require_once __DIR__ . '/../includes/auth_check.php';

$title = "Inventory Adjustments";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/topbar.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Get all adjustments with product and user details
$query = "SELECT a.*, p.name as product_name, u.full_name as user_name 
          FROM inventory_adjustments a
          JOIN products p ON a.product_id = p.id
          JOIN users u ON a.user_id = u.id
          ORDER BY a.created_at DESC";
$adjustments = $mysqli->connect->query($query);
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Inventory Adjustments</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Inventory Adjustments</li>
                    </ul>
                </div>
                <div class="col-auto">
                    <a href="adjust_stock.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Adjustment
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Adjustment History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="adjustmentsTable" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Date</th>
                                        <th>Product</th>
                                        <th>Type</th>
                                        <th>Qty</th>
                                        <th>Reason</th>
                                        <th>User</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($adj = $adjustments->fetch_object()): ?>
                                    <tr>
                                        <td><?= $adj->id ?></td>
                                        <td><?= date('d M Y h:i A', strtotime($adj->created_at)) ?></td>
                                        <td><?= htmlspecialchars($adj->product_name) ?></td>
                                        <td>
                                            <span class="badge <?= $adj->adjustment_type === 'add' ? 'bg-success' : 'bg-danger' ?>">
                                                <?= ucfirst($adj->adjustment_type) ?>
                                            </span>
                                        </td>
                                        <td><?= $adj->quantity ?></td>
                                        <td><?= htmlspecialchars($adj->reason) ?></td>
                                        <td><?= $adj->user_name ?></td>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#adjustmentsTable').DataTable({
        order: [[1, 'desc']]
    });
});
</script>