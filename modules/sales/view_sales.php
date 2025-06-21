<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';

// Get filter parameters
$customer_id = $_GET['customer_id'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-01-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$status = $_GET['status'] ?? '';

// Build where conditions
$where = [];
$where['created_at >='] = $start_date . ' 00:00:00';
$where['created_at <='] = $end_date . ' 23:59:59';

if ($customer_id) {
    $where['customer_id'] = $customer_id;
}

if ($phone) {
    $where['phone'] = $phone;
}

if ($status) {
    $where['payment_status'] = $status;
}

// Get sales
$sales = $mysqli->common_select('sales', '*', $where, 'created_at DESC')['data'];

// Get customers for filter dropdown
$customers = $mysqli->common_select('customers', 'id, name, phone', [], 'name ASC')['data'];

// Get unique phone numbers for dropdown
$phone_numbers = $mysqli->common_select('customers', 'DISTINCT phone', [], 'phone ASC')['data'];

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';
?>
<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <div class="row align-items-center">
                <div>
                    <h3 class="page-title">View Sales</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/sales/pos.php">Pos</a></li>
                        <li class="breadcrumb-item active">View Sales</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
                            <?php unset($_SESSION['error']); ?>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>
                        
                        <!-- Filter Form -->
                        <form method="GET" class="mb-4">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Customer</label>
                                        <select class="form-control" name="customer_id">
                                            <option value="">All Customers</option>
                                            <?php foreach ($customers as $customer): ?>
                                                <option value="<?= $customer->id ?>" <?= $customer_id == $customer->id ? 'selected' : '' ?>>
                                                    <?= $customer->name ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Start Date</label>
                                        <input type="date" class="form-control" name="start_date" value="<?= $start_date ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>End Date</label>
                                        <input type="date" class="form-control" name="end_date" value="<?= $end_date ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Status</label>
                                        <select class="form-control" name="status">
                                            <option value="">All</option>
                                            <option value="paid" <?= $status == 'paid' ? 'selected' : '' ?>>Paid</option>
                                            <option value="partial" <?= $status == 'partial' ? 'selected' : '' ?>>Partial</option>
                                            <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Pending</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3 align-self-end">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="view_sales.php" class="btn btn-secondary">Reset</a>
                                    <a href="pos.php" class="btn btn-success">New POS Sale</a>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Sales Table -->
                        <div class="table-responsive">
                            <table class="table table-striped" id="salesTable">
                                <thead>
                                    <tr>
                                        <th>Invoice No</th>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Phone</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($sales)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No sales found for the selected criteria</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($sales as $sale): 
                                            $customer = $sale->customer_id ? 
                                                $mysqli->common_select('customers', 'name, phone', ['id' => $sale->customer_id])['data'][0] : null;
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($sale->invoice_no) ?></td>
                                                <td><?= date('d M Y H:i', strtotime($sale->created_at)) ?></td>
                                                <td><?= htmlspecialchars($customer ? $customer->name : ($sale->customer_name ?: 'Walk-in')) ?></td>
                                                <td><?= htmlspecialchars($customer ? $customer->phone : $sale->phone) ?></td>
                                                <td><?= number_format($sale->total, 2) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= 
                                                        $sale->payment_status == 'paid' ? 'success' : 
                                                        ($sale->payment_status == 'partial' ? 'warning' : 'danger')
                                                    ?>">
                                                        <?= ucfirst($sale->payment_status) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="sale_details.php?id=<?= $sale->id ?>" class="btn btn-info btn-sm">View</a>
                                                    <a href="print_invoice.php?id=<?= $sale->id ?>" target="_blank" class="btn btn-secondary btn-sm">Print</a>
                                                    <a href="sales_payment.php?id=<?= $sale->id ?>" class="btn btn-warning btn-sm">Payments</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
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