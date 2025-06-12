<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php'; 

// Get purchase ID
if (!isset($_GET['id'])) {
    header("Location: view_purchases.php");
    exit();
}

$purchase_id = $_GET['id'];

// Get purchase details
$purchase_result = $mysqli->common_select('purchase', '*', ['id' => $purchase_id]);
if ($purchase_result['error'] || empty($purchase_result['data'])) {
    $_SESSION['error'] = "Purchase not found!";
    header("Location: view_purchases.php");
    exit();
}

$purchase = $purchase_result['data'][0];

// Get supplier details
$supplier = $mysqli->common_select('suppliers', '*', ['id' => $purchase->supplier_id])['data'][0] ?? null;

// Get purchase items
$items_result = $mysqli->common_select('purchase_items', '*', ['purchase_id' => $purchase_id]);
$items = $items_result['data'];

// Get payments
$payments_result = $mysqli->common_select('purchase_payment', '*', ['purchase_id' => $purchase_id]);
$payments = $payments_result['data'];

// Calculate paid amount and refund amount separately
$paid_amount = 0;
$refund_amount = 0;

foreach ($payments as $payment) {
    if ($payment->type == 'payment') {
        $paid_amount += $payment->amount;
    } else {
        $refund_amount += $payment->amount;
    }
}

// Balance due is based on original purchase minus payments (not considering refunds)
$balance = $purchase->total - $paid_amount;

// Total refunded is separate
$total_refunded = $refund_amount;

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
                            <h4 class="card-title">Purchase Details #<?= $purchase->reference_no ?></h4>
                            <a href="view_purchases.php" class="btn btn-secondary">Back to Purchases</a>
                        </div>
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
                            <?php unset($_SESSION['error']); ?>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5>Supplier Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($supplier): ?>
                                            <p><strong>Name:</strong> <?= $supplier->name ?></p>
                                            <p><strong>Company:</strong> <?= $supplier->company_name ?? 'N/A' ?></p>
                                            <p><strong>Phone:</strong> <?= $supplier->phone ?? 'N/A' ?></p>
                                            <p><strong>Email:</strong> <?= $supplier->email ?? 'N/A' ?></p>
                                            <p><strong>Address:</strong> <?= $supplier->address ?? 'N/A' ?></p>
                                        <?php else: ?>
                                            <p>Supplier information not available</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5>Purchase Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Reference No:</strong> <?= $purchase->reference_no ?></p>
                                        <p><strong>Date:</strong> <?= date('d M Y', strtotime($purchase->purchase_date)) ?></p>
                                        <p><strong>Payment Method:</strong> <?= ucfirst($purchase->payment_method) ?></p>
                                        <p>
                                            <strong>Status:</strong> 
                                            <span class="badge badge-<?= 
                                                $purchase->payment_status == 'paid' ? 'success' : 
                                                ($purchase->payment_status == 'partial' ? 'warning' : 'danger')
                                            ?>">
                                                <?= ucfirst($purchase->payment_status) ?>
                                            </span>
                                        </p>
                                        <p><strong>Created By:</strong> 
                                            <?php 
                                                $user = $mysqli->common_select('users', 'full_name', ['id' => $purchase->user_id])['data'][0] ?? null;
                                                echo $user ? $user->full_name : 'Unknown';
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>Purchase Items</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Product</th>
                                                <th>Quantity</th>
                                                <th>Unit Price</th>
                                                <th>Discount</th>
                                                <th>VAT</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($items as $index => $item): 
                                                $product = $mysqli->common_select('products', '*', ['id' => $item->product_id])['data'][0] ?? null;
                                            ?>
                                                <tr>
                                                    <td><?= $index + 1 ?></td>
                                                    <td><?= $product ? $product->name . ' (' . $product->barcode . ')' : 'Product not found' ?></td>
                                                    <td><?= $item->quantity ?></td>
                                                    <td><?= number_format($item->unit_price, 2) ?></td>
                                                    <td><?= number_format($item->discount, 2) ?></td>
                                                    <td><?= number_format($item->vat, 2) ?></td>
                                                    <td><?= number_format($item->total_price, 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="6" class="text-right"><strong>Subtotal</strong></td>
                                                <td><?= number_format($purchase->subtotal, 2) ?></td>
                                            </tr>
                                            <tr>
                                                <td colspan="6" class="text-right"><strong>Discount</strong></td>
                                                <td><?= number_format($purchase->discount, 2) ?></td>
                                            </tr>
                                            <tr>
                                                <td colspan="6" class="text-right"><strong>VAT</strong></td>
                                                <td><?= number_format($purchase->vat, 2) ?></td>
                                            </tr>
                                            <tr>
                                                <td colspan="6" class="text-right"><strong>Total</strong></td>
                                                <td><?= number_format($purchase->total, 2) ?></td>
                                            </tr>
                                            <tr>
                                                <td colspan="6" class="text-right"><strong>Paid Amount</strong></td>
                                                <td><?= number_format($paid_amount, 2) ?></td>
                                            </tr>
                                            <tr>
                                                <td colspan="6" class="text-right"><strong>Total Refunded</strong></td>
                                                <td><?= number_format($total_refunded, 2) ?></td>
                                            </tr>
                                            <tr>
                                                <td colspan="6" class="text-right"><strong>Balance Due</strong></td>
                                                <td><?= number_format($balance, 2) ?></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h5>Payment History</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($payments)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Type</th>
                                                    <th>Amount</th>
                                                    <th>Method</th>
                                                    <th>Description</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($payments as $payment): ?>
                                                    <tr>
                                                        <td><?= date('d M Y H:i', strtotime($payment->created_at)) ?></td>
                                                        <td>
                                                            <span class="badge badge-<?= $payment->type == 'payment' ? 'success' : 'danger' ?>">
                                                                <?= ucfirst($payment->type) ?>
                                                            </span>
                                                        </td>
                                                        <td><?= number_format($payment->amount, 2) ?></td>
                                                        <td><?= ucfirst($payment->payment_method) ?></td>
                                                        <td><?= $payment->description ?? 'N/A' ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p>No payment records found for this purchase.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <a href="view_purchases.php" class="btn btn-secondary">Back to Purchases</a>
                            <?php if ($_SESSION['user']->role == 'admin' || $_SESSION['user']->role == 'manager'): ?>
                                <a href="edit_purchase.php?id=<?= $purchase->id ?>" class="btn btn-primary">Edit Purchase</a>
                                <a href="purchase_payments.php?id=<?= $purchase->id ?>" class="btn btn-warning">Add Payment</a>
                                <a href="purchase_return.php?id=<?= $purchase->id ?>" class="btn btn-info">Return Items</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../../requires/footer.php'; ?>