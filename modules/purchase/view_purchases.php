<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';
require_once __DIR__ . '/../../includes/functions.php';

$where = [];
$search = '';

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    if (!empty($search)) {
        $where['reference_no'] = $search;
    }
}

if (isset($_GET['supplier_id']) && !empty($_GET['supplier_id'])) {
    $where['supplier_id'] = (int)$_GET['supplier_id'];
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $where['payment_status'] = $_GET['status'];
}

if (isset($_GET['from_date']) && !empty($_GET['from_date'])) {
    $where['created_at >='] = $_GET['from_date'] . ' 00:00:00';
}

if (isset($_GET['to_date']) && !empty($_GET['to_date'])) {
    $where['created_at <='] = $_GET['to_date'] . ' 23:59:59';
}

$where['is_deleted'] = 0;

$purchases = $mysqli->common_select('purchase', '*', $where, 'created_at', 'desc');
$suppliers = $mysqli->common_select('suppliers', '*');

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Purchase Management</h4>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <h4 class="card-title">Purchase List</h4>
                            <a href="add_purchase.php" class="btn btn-primary btn-round ml-auto">
                                <i class="fa fa-plus"></i>
                                Add Purchase
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="get" class="mb-4">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Search by Reference</label>
                                        <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Supplier</label>
                                        <select class="form-control" name="supplier_id">
                                            <option value="">All Suppliers</option>
                                            <?php foreach ($suppliers['data'] as $supplier): ?>
                                                <option value="<?= $supplier->id ?>" <?= isset($_GET['supplier_id']) && $_GET['supplier_id'] == $supplier->id ? 'selected' : '' ?>>
                                                    <?= $supplier->name ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Status</label>
                                        <select class="form-control" name="status">
                                            <option value="">All Statuses</option>
                                            <option value="pending" <?= isset($_GET['status']) && $_GET['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="partial" <?= isset($_GET['status']) && $_GET['status'] == 'partial' ? 'selected' : '' ?>>Partial</option>
                                            <option value="paid" <?= isset($_GET['status']) && $_GET['status'] == 'paid' ? 'selected' : '' ?>>Paid</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>From Date</label>
                                        <input type="date" class="form-control" name="from_date" value="<?= $_GET['from_date'] ?? '' ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>To Date</label>
                                        <input type="date" class="form-control" name="to_date" value="<?= $_GET['to_date'] ?? '' ?>">
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn btn-primary btn-block">Filter</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        
                        <div class="table-responsive">
                            <table id="purchaseTable" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Ref No</th>
                                        <th>Supplier</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!$purchases['error'] && !empty($purchases['data'])): ?>
                                        <?php foreach ($purchases['data'] as $purchase): 
                                            $supplier = $mysqli->common_select('suppliers', '*', ['id' => $purchase->supplier_id]);
                                            $items = $mysqli->common_select('purchase_items', '*', ['purchase_id' => $purchase->id]);
                                            $payments = $mysqli->common_select('purchase_payment', 'SUM(amount) as total_paid', ['purchase_id' => $purchase->id]);
                                            $totalPaid = $payments['data'][0]->total_paid ?? 0;
                                        ?>
                                            <tr>
                                                <td><?= $purchase->reference_no ?></td>
                                                <td><?= !$supplier['error'] && !empty($supplier['data']) ? $supplier['data'][0]->name : 'N/A' ?></td>
                                                <td><?= date('d M Y', strtotime($purchase->created_at)) ?></td>
                                                <td><?= !$items['error'] ? count($items['data']) : 0 ?></td>
                                                <td><?= number_format($purchase->total, 2) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= 
                                                        $purchase->payment_status === 'paid' ? 'success' : 
                                                        ($purchase->payment_status === 'partial' ? 'warning' : 'danger')
                                                    ?>">
                                                        <?= ucfirst($purchase->payment_status) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?= number_format($totalPaid, 2) ?> / <?= number_format($purchase->total, 2) ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="purchase_details.php?id=<?= $purchase->id ?>" class="btn btn-info btn-sm" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="purchase_payments.php?id=<?= $purchase->id ?>" class="btn btn-warning btn-sm" title="Payments">
                                                            <i class="fas fa-money-bill-wave"></i>
                                                        </a>
                                                        <a href="edit_purchase.php?id=<?= $purchase->id ?>" class="btn btn-primary btn-sm" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button class="btn btn-danger btn-sm delete-purchase" data-id="<?= $purchase->id ?>" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No purchases found</td>
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
    $('#purchaseTable').DataTable();
    
    // Delete purchase
    $(document).on('click', '.delete-purchase', function() {
        const purchaseId = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'delete_purchase.php?id=' + purchaseId;
            }
        });
    });
});
</script>