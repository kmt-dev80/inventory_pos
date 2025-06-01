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
        $where['invoice_no'] = $search;
    }
}

if (isset($_GET['customer_id']) && !empty($_GET['customer_id'])) {
    $where['customer_id'] = (int)$_GET['customer_id'];
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

$sales = $mysqli->common_select('sales', '*', $where, 'created_at', 'desc');
$customers = $mysqli->common_select('customers', '*');

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';
?>

<div class="main-panel">
    <div class="content">
        <div class="page-inner">
            <div class="page-header">
                <h4 class="page-title">Sales Management</h4>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex align-items-center">
                                <h4 class="card-title">Sales List</h4>
                                <a href="pos.php" class="btn btn-primary btn-round ml-auto">
                                    <i class="fa fa-plus"></i> New Sale
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="get" class="mb-4">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Search by Invoice</label>
                                            <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>Customer</label>
                                            <select class="form-control" name="customer_id">
                                                <option value="">All Customers</option>
                                                <?php foreach ($customers['data'] as $customer): ?>
                                                    <option value="<?= $customer->id ?>" <?= isset($_GET['customer_id']) && $_GET['customer_id'] == $customer->id ? 'selected' : '' ?>>
                                                        <?= $customer->name ?>
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
                                                <option value="paid" <?= isset($_GET['status']) && $_GET['status'] == 'paid' ? 'selected' : '' ?>>Paid</option>
                                                <option value="partial" <?= isset($_GET['status']) && $_GET['status'] == 'partial' ? 'selected' : '' ?>>Partial</option>
                                                <option value="pending" <?= isset($_GET['status']) && $_GET['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
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
                                <table id="salesTable" class="display table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Invoice No</th>
                                            <th>Customer</th>
                                            <th>Date</th>
                                            <th>Items</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Payment</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!$sales['error'] && !empty($sales['data'])): ?>
                                            <?php foreach ($sales['data'] as $sale): 
                                                $customer = $mysqli->common_select('customers', '*', ['id' => $sale->customer_id]);
                                                $items = $mysqli->common_select('sale_items', '*', ['sale_id' => $sale->id]);
                                                $payments = $mysqli->common_select('sales_payment', 'SUM(amount) as total_paid', ['sales_id' => $sale->id]);
                                                $totalPaid = $payments['data'][0]->total_paid ?? 0;
                                            ?>
                                                <tr>
                                                    <td><?= $sale->invoice_no ?></td>
                                                    <td>
                                                        <?= !$customer['error'] && !empty($customer['data']) ? 
                                                            $customer['data'][0]->name : 
                                                            ($sale->customer_name ?: 'Walk-in') ?>
                                                    </td>
                                                    <td><?= date('d M Y', strtotime($sale->created_at)) ?></td>
                                                    <td><?= !$items['error'] ? count($items['data']) : 0 ?></td>
                                                    <td><?= number_format($sale->total, 2) ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= 
                                                            $sale->payment_status === 'paid' ? 'success' : 
                                                            ($sale->payment_status === 'partial' ? 'warning' : 'danger')
                                                        ?>">
                                                            <?= ucfirst($sale->payment_status) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?= number_format($totalPaid, 2) ?> / <?= number_format($sale->total, 2) ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="sale_details.php?id=<?= $sale->id ?>" class="btn btn-info btn-sm" title="View">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="sales_payments.php?id=<?= $sale->id ?>" class="btn btn-warning btn-sm" title="Payments">
                                                                <i class="fas fa-money-bill-wave"></i>
                                                            </a>
                                                            <a href="print_receipt.php?id=<?= $sale->id ?>" class="btn btn-secondary btn-sm" title="Print" target="_blank">
                                                                <i class="fas fa-print"></i>
                                                            </a>
                                                            <button class="btn btn-danger btn-sm delete-sale" data-id="<?= $sale->id ?>" title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center">No sales found</td>
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
</div>

<script>
$(document).ready(function() {
    $('#salesTable').DataTable();
    
    // Delete sale
    $(document).on('click', '.delete-sale', function() {
        const saleId = $(this).data('id');
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
                window.location.href = 'delete_sale.php?id=' + saleId;
            }
        });
    });
});
</script>

<?php include __DIR__ . '/../../requires/footer.php'; ?>