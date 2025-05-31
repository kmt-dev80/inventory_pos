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
$payments = $mysqli->common_select('purchase_payment', '*', ['purchase_id' => $purchaseId], 'created_at', 'desc');

if ($purchase['error'] || empty($purchase['data'])) {
    setFlashMessage('Purchase not found', 'danger');
    header('Location: view_purchases.php');
    exit;
}

$purchase = $purchase['data'][0];
$supplier = $mysqli->common_select('suppliers', '*', ['id' => $purchase->supplier_id]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentData = [
        'supplier_id' => $purchase->supplier_id,
        'purchase_id' => $purchaseId,
        'type' => 'payment',
        'amount' => $_POST['amount'],
        'payment_method' => $_POST['payment_method'],
        'description' => $_POST['description']
    ];

    $paymentResult = $mysqli->common_insert('purchase_payment', $paymentData);
    
    if (!$paymentResult['error']) {
        // Update payment status if fully paid
        $totalPaid = 0;
        $payments = $mysqli->common_select('purchase_payment', 'SUM(amount) as total_paid', ['purchase_id' => $purchaseId]);
        if (!$payments['error'] && !empty($payments['data'])) {
            $totalPaid = $payments['data'][0]->total_paid;
        }
        
        $newStatus = 'partial';
        if ($totalPaid >= $purchase->total) {
            $newStatus = 'paid';
        }
        
        if ($purchase->payment_status !== $newStatus) {
            $mysqli->common_update('purchase', ['payment_status' => $newStatus], ['id' => $purchaseId]);
        }
        
        setFlashMessage('Payment added successfully', 'success');
        header("Location: purchase_payments.php?id=$purchaseId");
        exit;
    } else {
        setFlashMessage('Error adding payment: ' . $paymentResult['error_msg'], 'danger');
    }
}

$totalPaid = 0;
if (!$payments['error'] && !empty($payments['data'])) {
    foreach ($payments['data'] as $payment) {
        $totalPaid += $payment->amount;
    }
}

$balance = $purchase->total - $totalPaid;

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Purchase Payments</h4>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <h4 class="card-title">Payments for Purchase #<?= $purchase->reference_no ?></h4>
                            <div class="ml-auto">
                                <a href="view_purchases.php" class="btn btn-primary btn-round">
                                    <i class="fas fa-arrow-left"></i> Back to Purchases
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Supplier</label>
                                    <input type="text" class="form-control" readonly 
                                        value="<?= !$supplier['error'] && !empty($supplier['data']) ? $supplier['data'][0]->name : 'N/A' ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Purchase Total</label>
                                    <input type="text" class="form-control" readonly 
                                        value="<?= number_format($purchase->total, 2) ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Balance</label>
                                    <input type="text" class="form-control" readonly 
                                        value="<?= number_format($balance, 2) ?>">
                                </div>
                            </div>
                        </div>
                        
                        <form method="post">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="amount">Amount</label>
                                        <input type="number" class="form-control" id="amount" name="amount" 
                                            min="0.01" max="<?= $balance ?>" step="0.01" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="payment_method">Payment Method</label>
                                        <select class="form-control" id="payment_method" name="payment_method" required>
                                            <option value="cash">Cash</option>
                                            <option value="credit">Credit</option>
                                            <option value="card">Card</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="description">Description</label>
                                        <input type="text" class="form-control" id="description" name="description" 
                                            value="Payment for purchase #<?= $purchase->reference_no ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn btn-success btn-block">
                                            <i class="fas fa-plus"></i> Add Payment
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        
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
                                            <?= number_format($totalPaid, 2) ?>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="card-action">
                        <a href="purchase_details.php?id=<?= $purchase->id ?>" class="btn btn-info">
                            <i class="fas fa-eye"></i> View Purchase Details
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../requires/footer.php'; ?>