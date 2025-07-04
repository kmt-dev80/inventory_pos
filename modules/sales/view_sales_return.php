<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';

// Get filter parameters from GET request
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '2025-01-01';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get only customers who have sales return history
$customers_query = "
    SELECT DISTINCT c.id as customer_id, c.name as customer_name
    FROM customers c
    JOIN sales s ON c.id = s.customer_id
    JOIN sales_returns sr ON s.id = sr.sale_id
    ORDER BY c.name ASC
";
$customers = $mysqli->getConnection()->query($customers_query)->fetch_all(MYSQLI_ASSOC);

// Build the main returns query with filters
$returns_query = "
    SELECT sr.*, s.invoice_no as sale_invoice, 
           c.name as customer_name, u.full_name as processed_by
    FROM sales_returns sr
    LEFT JOIN sales s ON sr.sale_id = s.id
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN users u ON sr.user_id = u.id
    WHERE 1=1
";

// Add filters if they exist
if (!empty($customer_id)) {
    $returns_query .= " AND c.id = " . $customer_id;
}
if (!empty($start_date)) {
    $returns_query .= " AND DATE(sr.created_at) >= '" . $mysqli->getConnection()->real_escape_string($start_date) . "'";
}
if (!empty($end_date)) {
    $returns_query .= " AND DATE(sr.created_at) <= '" . $mysqli->getConnection()->real_escape_string($end_date) . "'";
}

$returns_query .= " ORDER BY sr.created_at DESC";
$returns = $mysqli->getConnection()->query($returns_query)->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/sidebar.php';
require_once __DIR__ . '/../../requires/topbar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <div class="row align-items-center">
                <div>
                    <h3 class="page-title">View Sales Return</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= htmlspecialchars(BASE_URL) ?>index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= htmlspecialchars(BASE_URL) ?>modules/sales/pos.php">Pos</a></li>
                        <li class="breadcrumb-item"><a href="<?= htmlspecialchars(BASE_URL) ?>modules/sales/view_sales.php">View Sales</a></li>
                        <li class="breadcrumb-item active">View Sales Return</li>
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
                                                <option value="<?= htmlspecialchars($customer['customer_id']) ?>" 
                                                    <?= $customer_id == $customer['customer_id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($customer['customer_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Start Date</label>
                                        <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>End Date</label>
                                        <input type="date" class="form-control" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                                    </div>
                                </div>

                                <div class="col-md-5 d-flex justify-content-end">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary">Filter</button>
                                            <a href="view_sales_return.php" class="btn btn-secondary">Reset</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table id="salesReturnsTable" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Return No</th>
                                        <th>Date</th>
                                        <th>Original Sale</th>
                                        <th>Customer</th>
                                        <th>Reason</th>
                                        <th>Refund Amount</th>
                                        <th>Method</th>
                                        <th>Processed By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($returns as $index=> $return): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($return['invoice_no'] ?? 'RTN-' . $return['id']) ?></td>
                                            <td><?= htmlspecialchars(date('d M Y h:i A', strtotime($return['created_at']))) ?></td>
                                            <td>
                                                <?php if ($return['sale_invoice']): ?>
                                                    <a href="sale_details.php?id=<?= htmlspecialchars($return['sale_id']) ?>">
                                                        <?= htmlspecialchars($return['sale_invoice']) ?>
                                                    </a>
                                                <?php else: ?>
                                                    Sale deleted
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($return['customer_name'] ?? 'Walk-in') ?></td>
                                            <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $return['return_reason']))) ?></td>
                                            <td><?= htmlspecialchars(number_format($return['refund_amount'], 2)) ?></td>
                                            <td><?= htmlspecialchars(ucfirst($return['refund_method'])) ?></td>
                                            <td><?= htmlspecialchars($return['processed_by']) ?></td>
                                            <td>
                                                <a href="sales_return_details.php?id=<?= htmlspecialchars($return['id']) ?>" 
                                                   class="btn btn-sm btn-info" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($_SESSION['user']->role == 'admin'): ?>
                                                    <a href="print_return.php?id=<?= htmlspecialchars($return['id']) ?>" 
                                                       class="btn btn-sm btn-primary" title="Print">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                <?php endif; ?>
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