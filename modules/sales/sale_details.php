<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: view_sales.php');
    exit;
}

$saleId = (int)$_GET['id'];
$sale = $mysqli->common_select('sales', '*', ['id' => $saleId]);
$saleItems = $mysqli->common_select('sale_items', '*', ['sale_id' => $saleId]);
$payments = $mysqli->common_select('sales_payment', '*', ['sales_id' => $saleId], 'created_at', 'desc');

if ($sale['error'] || empty($sale['data'])) {
    setFlashMessage('Sale not found', 'danger');
    header('Location: view_sales.php');
    exit;
}

$sale = $sale['data'][0];
$customer = $sale->customer_id ? 
    $mysqli->common_select('customers', '*', ['id' => $sale->customer_id]) : 
    ['error' => true];
$user = $mysqli->common_select('users', 'full_name', ['id' => $sale->user_id]);

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';
?>

<div class="main-panel">
    <div class="content">
        <div class="page-inner">
            <div class="page-header">
                <h4 class="page-title">Sale Details</h4>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex align-items-center">
                                <h4 class="card-title">Invoice #<?= $sale->invoice_no ?></h4>
                                <div class="ml-auto">
                                    <a href="view_sales.php" class="btn btn-primary btn-round">
                                        <i class="fas fa-arrow-left"></i> Back to Sales
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Customer</label>
                                        <input type="text" class="form-control" readonly 
                                            value="<?= !$customer['error'] && !empty($customer['data']) ? 
                                                $customer['data'][0]->name : 
                                                ($sale->customer_name ?: 'Walk-in Customer') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Invoice No</label>
                                        <input type="text" class="form-control" readonly value="<?= $sale->invoice_no ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Date</label>
                                        <input type="text" class="form-control" readonly 
                                            value="<?= date('d M Y h:i A', strtotime($sale->created_at)) ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Payment Method</label>
                                        <input type="text" class="form-control" readonly 
                                            value="<?= ucfirst($sale->payment_status) ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Processed By</label>
                                        <input type="text" class="form-control" readonly 
                                            value="<?= !$user['error'] && !empty($user['data']) ? $user['data'][0]->full_name : 'N/A' ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Subtotal</label>
                                        <input type="text" class="form-control" readonly 
                                            value="<?= number_format($sale->subtotal, 2) ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Discount (<?= $sale->discount ?>%)</label>
                                        <input type="text" class="form-control" readonly 
                                            value="<?= number_format($sale->subtotal * ($sale->discount / 100), 2) ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>VAT</label>
                                        <input type="text" class="form-control" readonly 
                                            value="<?= number_format($sale->vat, 2) ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Total</label>
                                        <input type="text" class="form-control" readonly 
                                            value="<?= number_format($sale->total, 2) ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Customer Phone</label>
                                        <input type="text" class="form-control" readonly 
                                            value="<?= !$customer['error'] && !empty($customer['data']) ? $customer['data'][0]->phone : ($sale->customer_phone ?? 'N/A') ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Customer Email</label>
                                        <input type="text" class="form-control" readonly 
                                            value="<?= !$customer['error'] && !empty($customer['data']) ? $customer['data'][0]->email : ($sale->customer_email ?? 'N/A') ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            <h4>Sale Items</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Product</th>
                                            <th>Barcode</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!$saleItems['error'] && !empty($saleItems['data'])): ?>
                                            <?php foreach ($saleItems['data'] as $index => $item): 
                                                $product = $mysqli->common_select('products', 'name, barcode', ['id' => $item->product_id]);
                                            ?>
                                                <tr>
                                                    <td><?= $index + 1 ?></td>
                                                    <td><?= !$product['error'] && !empty($product['data']) ? $product['data'][0]->name : 'Product Not Found' ?></td>
                                                    <td><?= !$product['error'] && !empty($product['data']) ? $product['data'][0]->barcode : 'N/A' ?></td>
                                                    <td><?= $item->quantity ?></td>
                                                    <td><?= number_format($item->unit_price, 2) ?></td>
                                                    <td><?= number_format($item->quantity * $item->unit_price, 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No items found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <hr>
                            <h4>Payment History</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!$payments['error'] && !empty($payments['data'])): ?>
                                            <?php foreach ($payments['data'] as $index => $payment): ?>
                                                <tr>
                                                    <td><?= $index + 1 ?></td>
                                                    <td><?= date('d M Y h:i A', strtotime($payment->created_at)) ?></td>
                                                    <td><?= number_format($payment->amount, 2) ?></td>
                                                    <td><?= ucfirst($payment->payment_method) ?></td>
                                                    <td><?= $payment->description ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No payments found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="2">Total Paid</th>
                                            <th colspan="3">
                                                <?php 
                                                    $totalPaid = 0;
                                                    if (!$payments['error'] && !empty($payments['data'])) {
                                                        foreach ($payments['data'] as $payment) {
                                                            $totalPaid += $payment->amount;
                                                        }
                                                    }
                                                    echo number_format($totalPaid, 2);
                                                ?>
                                            </th>
                                        </tr>
                                        <tr>
                                            <th colspan="2">Balance</th>
                                            <th colspan="3">
                                                <?= number_format($sale->total - $totalPaid, 2) ?>
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <div class="card-action">
                            <a href="sales_payments.php?id=<?= $sale->id ?>" class="btn btn-warning">
                                <i class="fas fa-money-bill-wave"></i> Add Payment
                            </a>
                            <a href="print_receipt.php?id=<?= $sale->id ?>" class="btn btn-secondary" target="_blank">
                                <i class="fas fa-print"></i> Print Receipt
                            </a>
                            <a href="sales_returns.php?sale_id=<?= $sale->id ?>" class="btn btn-info">
                                <i class="fas fa-undo"></i> Create Return
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../requires/footer.php'; ?>