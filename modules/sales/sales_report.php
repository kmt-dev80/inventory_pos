<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';

// Get filter parameters
$customer_id = $_GET['customer_id'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$payment_status = $_GET['payment_status'] ?? '';

// Build where conditions
$where = [];
$where['created_at >='] = $start_date . ' 00:00:00';
$where['created_at <='] = $end_date . ' 23:59:59';

if ($customer_id) {
    $where['customer_id'] = $customer_id;
}

if ($payment_status) {
    $where['payment_status'] = $payment_status;
}

// Get sales data
$sales = $mysqli->common_select('sales', '*', $where, 'created_at DESC')['data'];

// Get customers for filter dropdown
$customers = $mysqli->common_select('customers')['data'];

// Calculate totals
$total_sales = 0;
$total_paid = 0;
$total_refunded = 0;
$total_due = 0;

foreach ($sales as $sale) {
    $total_sales += $sale->total;
    
    // Get payments and refunds for each sale
    $payments = $mysqli->common_select('sales_payment', '*', ['sales_id' => $sale->id])['data'];
    
    $paid = 0;
    $refunded = 0;
    foreach ($payments as $payment) {
        if ($payment->type == 'payment') {
            $paid += $payment->amount;
        } else {
            $refunded += $payment->amount;
        }
    }
    
    $total_paid += $paid;
    $total_refunded += $refunded;
    
    // Calculate due based on original sale total minus payments (ignore refunds for due calculation)
    $total_due += max(0, $sale->total - $paid);
}

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/sidebar.php';
require_once __DIR__ . '/../../requires/topbar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <div class="row align-items-center">
                <div>
                    <h3 class="page-title">Sales Report</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/sales/pos.php">Pos</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/sales/view_sales.php">View Sales</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/sales/view_sales_return.php">View Sales Return</a></li>
                        <li class="breadcrumb-item active">Sales Report</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin">
                <div class="card">
                    <div class="card-body">
                        
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
                                        <label>Payment Status</label>
                                        <select class="form-control" name="payment_status">
                                            <option value="">All</option>
                                            <option value="paid" <?= $payment_status == 'paid' ? 'selected' : '' ?>>Paid</option>
                                            <option value="partial" <?= $payment_status == 'partial' ? 'selected' : '' ?>>Partial</option>
                                            <option value="pending" <?= $payment_status == 'pending' ? 'selected' : '' ?>>Pending</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3 align-self-end">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="sales_report.php" class="btn btn-secondary">Reset</a>
                                </div>
                            </div>
                        </form>
                        
                       <!-- Updated Summary Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Sales</h5>
                                        <h2><?= number_format($total_sales, 2) ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Paid</h5>
                                        <h2><?= number_format($total_paid, 2) ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Refunded</h5>
                                        <h2><?= number_format($total_refunded, 2) ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Due</h5>
                                        <h2><?= number_format($total_due, 2) ?></h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                                        
                        <!-- Sales Table -->
                        <div class="table-responsive">
                            <table class="table table-striped" id="salesReport">
                                <thead>
                                    <tr>
                                        <th>Invoice No</th>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Total</th>
                                        <th>Paid</th>
                                        <th>Refunded</th>
                                        <th>Due</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sales as $sale): 
                                        $customer = $sale->customer_id ? 
                                            $mysqli->common_select('customers', '*', ['id' => $sale->customer_id])['data'][0] : null;
                                        
                                        // Get payments and refunds for this sale
                                        $payments = $mysqli->common_select('sales_payment', '*', ['sales_id' => $sale->id])['data'];
                                        
                                        $paid = 0;
                                        $refunded = 0;
                                        foreach ($payments as $payment) {
                                            if ($payment->type == 'payment') {
                                                $paid += $payment->amount;
                                            } else {
                                                $refunded += $payment->amount;
                                            }
                                        }
                                        
                                        // Due is based on original total minus payments only
                                        $due = max(0, $sale->total - $paid);
                                    ?>
                                        <tr>
                                            <td><?= $sale->invoice_no ?></td>
                                            <td><?= date('d M Y', strtotime($sale->created_at)) ?></td>
                                            <td><?= $customer ? $customer->name : ($sale->customer_name ?: 'Walk-in') ?></td>
                                            <td><?= number_format($sale->total, 2) ?></td>
                                            <td><?= number_format($paid, 2) ?></td>
                                            <td><?= number_format($refunded, 2) ?></td>
                                            <td><?= number_format($due, 2) ?></td>
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
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
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