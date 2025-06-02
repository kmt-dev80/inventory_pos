<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php'; 
require_once __DIR__ . '/../../includes/functions.php';

// Get filter parameters
$supplier_id = $_GET['supplier_id'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$status = $_GET['status'] ?? '';

// Build where conditions with proper operators
$where = [
    'purchase_date >=' => $start_date,
    'purchase_date <=' => $end_date
];

if ($supplier_id) {
    $where['supplier_id'] = $supplier_id;
}

if ($status) {
    $where['payment_status'] = $status;
}

// Get purchases with the new CRUD class
//$purchases = $mysqli->common_select('purchase', '*', $where, 'purchase_date DESC')['data'];
$purchases_result = $mysqli->common_select('purchase', '*', []);
if ($purchases_result['error']) {
    // Log error and show empty results
    error_log("Purchase query error: " . $purchases_result['error_msg']);
    $purchases = [];
} else {
    $purchases = $purchases_result['data'];
}

// Get suppliers for filter dropdown
$suppliers_result = $mysqli->common_select('suppliers');
$suppliers = $suppliers_result['data'] ?? [];

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="row">
            <div class="col-md-12 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">View Purchases</h4>
                        
                        <!-- Filter Form -->
                        <form method="GET" class="mb-4">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Supplier</label>
                                        <select class="form-control" name="supplier_id">
                                            <option value="">All Suppliers</option>
                                            <?php foreach ($suppliers as $supplier): ?>
                                                <option value="<?= htmlspecialchars($supplier->id) ?>" <?= $supplier_id == $supplier->id ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($supplier->name) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Start Date</label>
                                        <input type="date" class="form-control" name="start_date" 
                                               value="<?= htmlspecialchars($start_date) ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>End Date</label>
                                        <input type="date" class="form-control" name="end_date" 
                                               value="<?= htmlspecialchars($end_date) ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Status</label>
                                        <select class="form-control" name="status">
                                            <option value="">All</option>
                                            <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="partial" <?= $status == 'partial' ? 'selected' : '' ?>>Partial</option>
                                            <option value="paid" <?= $status == 'paid' ? 'selected' : '' ?>>Paid</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3 align-self-end">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="view_purchases.php" class="btn btn-secondary">Reset</a>
                                    <a href="add_purchase.php" class="btn btn-success">Add Purchase</a>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Purchases Table -->
                        <div class="table-responsive">
                            <table class="table table-striped" id="purchasesTable">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>Date</th>
                                        <th>Supplier</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Payment Method</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($purchases)): ?>
                                        <?php foreach ($purchases as $purchase): 
                                            $supplier = $mysqli->common_select('suppliers', '*', ['id' => $purchase->supplier_id])['data'][0] ?? null;
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($purchase->reference_no) ?></td>
                                                <td><?= date('d M Y', strtotime($purchase->purchase_date)) ?></td>
                                                <td><?= $supplier ? htmlspecialchars($supplier->name) : 'N/A' ?></td>
                                                <td><?= number_format($purchase->total, 2) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= 
                                                        $purchase->payment_status == 'paid' ? 'success' : 
                                                        ($purchase->payment_status == 'partial' ? 'warning' : 'danger')
                                                    ?>">
                                                        <?= ucfirst($purchase->payment_status) ?>
                                                    </span>
                                                </td>
                                                <td><?= ucfirst(str_replace('_', ' ', $purchase->payment_method)) ?></td>
                                                <td>
                                                    <a href="purchase_details.php?id=<?= $purchase->id ?>" class="btn btn-info btn-sm">View</a>
                                                    <?php if ($_SESSION['user']->role == 'admin'): ?>
                                                        <a href="edit_purchase.php?id=<?= $purchase->id ?>" class="btn btn-primary btn-sm">Edit</a>
                                                        <a href="purchase_payments.php?id=<?= $purchase->id ?>" class="btn btn-warning btn-sm">Payments</a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No purchases found</td>
                                        </tr>
                                    <?php endif; ?>
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
    $('#purchasesTable').DataTable({
        "order": [[1, "desc"]],
        "responsive": true,
        "columnDefs": [
            { "orderable": false, "targets": [6] } // Disable sorting on actions column
        ]
    });
});
</script>