<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: view_purchases.php');
    exit;
}

$purchaseId = (int)$_GET['id'];
$purchase = $mysqli->common_select('purchase', '*', ['id' => $purchaseId]);
$purchaseItems = $mysqli->common_select('purchase_items', '*', ['purchase_id' => $purchaseId]);
$payments = $mysqli->common_select('purchase_payment', '*', ['purchase_id' => $purchaseId], 'created_at', 'desc');

if ($purchase['error'] || empty($purchase['data'])) {
    setFlashMessage('Purchase not found', 'danger');
    header('Location: view_purchases.php');
    exit;
}

$purchase = $purchase['data'][0];
$supplier = $mysqli->common_select('suppliers', '*', ['id' => $purchase->supplier_id]);
$user = $mysqli->common_select('users', 'full_name', ['id' => $purchase->user_id]);

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Purchase Details</h4>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <h4 class="card-title">Purchase #<?= $purchase->reference_no ?></h4>
                            <div class="ml-auto">
                                <a href="view_purchases.php" class="btn btn-primary btn-round">
                                    <i class="fas fa-arrow-left"></i> Back to Purchases
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Supplier</label>
                                    <input type="text" class="form-control" readonly 
                                        value="<?= !$supplier['error'] && !empty($supplier['data']) ? $supplier['data'][0]->name : 'N/A' ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Reference No</label>
                                    <input type="text" class="form-control" readonly value="<?= $purchase->reference_no ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Date</label>
                                    <input type="text" class="form-control" readonly 
                                        value="<?= date('d M Y h:i A', strtotime($purchase->created_at)) ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Payment Method</label>
                                    <input type="text" class="form-control" readonly 
                                        value="<?= ucfirst($purchase->payment_method) ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Payment Status</label>
                                    <input type="text" class="form-control" readonly 
                                        value="<?= ucfirst($purchase->payment_status) ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Subtotal</label>
                                    <input type="text" class="form-control" readonly 
                                        value="<?= number_format($purchase->subtotal, 2) ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Discount (<?= $purchase->discount ?>%)</label>
                                    <input type="text" class="form-control" readonly 
                                        value="<?= number_format($purchase->subtotal * ($purchase->discount / 100), 2) ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>VAT</label>
                                    <input type="text" class="form-control" readonly 
                                        value="<?= number_format($purchase->vat, 2) ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Total</label>
                                    <input type="text" class="form-control" readonly 
                                        value="<?= number_format($purchase->total, 2) ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Recorded By</label>
                                    <input type="text" class="form-control" readonly 
                                        value="<?= !$user['error'] && !empty($user['data']) ? $user['data'][0]->full_name : 'N/A' ?>">
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        <h4>Purchase Items</h4>
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
                                    <?php if (!$purchaseItems['error'] && !empty($purchaseItems['data'])): ?>
                                        <?php foreach ($purchaseItems['data'] as $index => $item): 
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
                                            <?= number_format($purchase->total - $totalPaid, 2) ?>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="card-action">
                        <a href="purchase_payments.php?id=<?= $purchase->id ?>" class="btn btn-warning">
                            <i class="fas fa-money-bill-wave"></i> Add Payment
                        </a>
                        <a href="edit_purchase.php?id=<?= $purchase->id ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Purchase
                        </a>
                        <a href="purchase_returns.php?purchase_id=<?= $purchase->id ?>" class="btn btn-info">
                            <i class="fas fa-undo"></i> Create Return
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../requires/footer.php'; ?>