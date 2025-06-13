<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';

// Get return ID
if (!isset($_GET['id'])) {
    header("Location: view_sales_return.php");
    exit();
}

$return_id = $_GET['id'];

// Get return details
$return_query = "
    SELECT sr.*, s.invoice_no as sale_invoice, s.total as sale_total,
           c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
           u.full_name as processed_by
    FROM sales_returns sr
    LEFT JOIN sales s ON sr.sale_id = s.id
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN users u ON sr.user_id = u.id
    WHERE sr.id = ?
";
$stmt = $mysqli->getConnection()->prepare($return_query);
$stmt->bind_param('i', $return_id);
$stmt->execute();
$return = $stmt->get_result()->fetch_assoc();

if (!$return) {
    $_SESSION['error'] = "Return not found!";
    header("Location: view_sales_return.php");
    exit();
}

// Get return items
$items_query = "
    SELECT sri.*, p.name as product_name, p.barcode
    FROM sales_return_items sri
    LEFT JOIN products p ON sri.product_id = p.id
    WHERE sri.sales_return_id = ?
";
$stmt = $mysqli->getConnection()->prepare($items_query);
$stmt->bind_param('i', $return_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$total_ex_vat = 0;
$total_vat = 0;
$total_with_vat = $return['refund_amount'];

// If you have VAT breakdown in items
foreach ($items as $item) {
    $total_ex_vat += $item['total_price_ex_vat'] ?? ($item['total_price'] / 1.15); // Adjust VAT rate as needed
    $total_vat += $item['vat_amount'] ?? ($item['total_price'] - ($item['total_price'] / 1.15));
}

// Get refund payment if exists
$payment_query = "
    SELECT * FROM sales_payment
    WHERE sales_return_id = ?
";
$stmt = $mysqli->getConnection()->prepare($payment_query);
$stmt->bind_param('i', $return_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();

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
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">Return Details #<?= $return['invoice_no'] ?></h4>
                            <div>
                                <a href="view_sales_return.php" class="btn btn-secondary">Back to Returns</a>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5>Return Information</h5>
                                        <p><strong>Return No:</strong> <?= $return['invoice_no'] ?></p>
                                        <p><strong>Date:</strong> <?= date('d M Y H:i', strtotime($return['created_at'])) ?></p>
                                        <p><strong>Reason:</strong> <?= ucfirst(str_replace('_', ' ', $return['return_reason'])) ?></p>
                                        <p><strong>Note:</strong> <?= $return['return_note'] ?: 'N/A' ?></p>
                                        <p><strong>Processed By:</strong> <?= $return['processed_by'] ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5>Refund Information</h5>
                                        <p><strong>Refund Method:</strong> <?= ucfirst($return['refund_method']) ?></p>
                                        <p><strong>Refund Amount:</strong> <?= number_format($return['refund_amount'], 2) ?></p>
                                        <?php if ($payment): ?>
                                            <p><strong>Payment Date:</strong> <?= date('d M Y', strtotime($payment['created_at'])) ?></p>
                                            <p><strong>Payment Reference:</strong> <?= $payment['reference_no'] ?? 'N/A' ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5>Original Sale Information</h5>
                                        <p><strong>Invoice No:</strong> 
                                            <?php if ($return['sale_invoice']): ?>
                                                <a href="sale_details.php?id=<?= $return['sale_id'] ?>">
                                                    <?= $return['sale_invoice'] ?>
                                                </a>
                                            <?php else: ?>
                                                Sale deleted
                                            <?php endif; ?>
                                        </p>
                                        <p><strong>Sale Date:</strong> 
                                            <?= isset($return['sale_created_at']) ? date('d M Y', strtotime($return['sale_created_at'])) : 'N/A' ?>
                                        </p>
                                        <p><strong>Sale Total:</strong> 
                                            <?= isset($return['sale_total']) ? number_format($return['sale_total'], 2) : 'N/A' ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5>Customer Information</h5>
                                        <?php if ($return['customer_name']): ?>
                                            <p><strong>Name:</strong> <?= $return['customer_name'] ?></p>
                                            <p><strong>Phone:</strong> <?= $return['customer_phone'] ?? 'N/A' ?></p>
                                            <p><strong>Email:</strong> <?= $return['customer_email'] ?? 'N/A' ?></p>
                                        <?php else: ?>
                                            <p>Walk-in customer</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h5>Returned Items</h5>
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Barcode</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Discounted Price</th>
                                        <th>VAT Amount</th>
                                        <th>Total (Ex VAT)</th>
                                        <th>Total (Inc VAT)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                        <?php
                                        $item_vat = $item['vat_amount'] ?? ($item['total_price'] - ($item['total_price'] / 1.15));
                                        $item_ex_vat = $item['total_price_ex_vat'] ?? ($item['total_price'] / 1.15);
                                        ?>
                                        <tr>
                                            <td><?= $item['product_name'] ?></td>
                                            <td><?= $item['barcode'] ?></td>
                                            <td><?= $item['quantity'] ?></td>
                                            <td><?= number_format($item['unit_price'], 2) ?></td>
                                            <td><?= number_format($item['discounted_price'] ?? $item['unit_price'], 2) ?></td>
                                            <td><?= number_format($item_vat, 2) ?></td>
                                            <td><?= number_format($item_ex_vat, 2) ?></td>
                                            <td><?= number_format($item['total_price'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6" class="text-right"><strong>Subtotal:</strong></td>
                                        <td><?= number_format($total_ex_vat, 2) ?></td>
                                        <td><?= number_format($total_with_vat, 2) ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="6" class="text-right"><strong>VAT:</strong></td>
                                        <td colspan="2"><?= number_format($total_vat, 2) ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="6" class="text-right"><strong>Total Refund:</strong></td>
                                        <td colspan="2"><?= number_format($total_with_vat, 2) ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h5>Additional Information</h5>
                                        <div class="form-group">
                                            <label>Return Notes</label>
                                            <textarea class="form-control" rows="3" readonly><?= $return['return_note'] ?? 'No additional notes' ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <a href="view_sales_return.php" class="btn btn-secondary">Back to Returns</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../requires/footer.php'; ?>