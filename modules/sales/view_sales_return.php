<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';

// Get all sales returns
$returns_query = "
    SELECT sr.*, s.invoice_no as sale_invoice, 
           c.name as customer_name, u.full_name as processed_by
    FROM sales_returns sr
    LEFT JOIN sales s ON sr.sale_id = s.id
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN users u ON sr.user_id = u.id
    ORDER BY sr.created_at DESC
";
$returns = $mysqli->getConnection()->query($returns_query)->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <div class="row align-items-center">
                <div>
                    <h3 class="page-title">View Sales Return</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/sales/pos.php">Pos</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/sales/view_sales.php">View Sales</a></li>
                        <li class="breadcrumb-item active">View Sales Return</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin">
                <div class="card">
                    <div class="card-body">

                        <div class="table-responsive">
                            <table id="salesReturnsTable" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
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
                                    <?php foreach ($returns as $return): ?>
                                        <tr>
                                            <td><?= $return['invoice_no'] ?? 'RTN-' . $return['id'] ?></td>
                                            <td><?= date('d M Y', strtotime($return['created_at'])) ?></td>
                                            <td>
                                                <?php if ($return['sale_invoice']): ?>
                                                    <a href="sale_details.php?id=<?= $return['sale_id'] ?>">
                                                        <?= $return['sale_invoice'] ?>
                                                    </a>
                                                <?php else: ?>
                                                    Sale deleted
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $return['customer_name'] ?? 'Walk-in' ?></td>
                                            <td><?= ucfirst(str_replace('_', ' ', $return['return_reason'])) ?></td>
                                            <td><?= number_format($return['refund_amount'], 2) ?></td>
                                            <td><?= ucfirst($return['refund_method']) ?></td>
                                            <td><?= $return['processed_by'] ?></td>
                                            <td>
                                                <a href="sales_return_details.php?id=<?= $return['id'] ?>" 
                                                   class="btn btn-sm btn-info" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($_SESSION['user']->role == 'admin'): ?>
                                                    <a href="print_return.php?id=<?= $return['id'] ?>" 
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