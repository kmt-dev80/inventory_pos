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
$payments = $mysqli->common_select('sales_payment', '*', ['sales_id' => $saleId], 'created_at', 'desc');

if ($sale['error'] || empty($sale['data'])) {
    setFlashMessage('Sale not found', 'danger');
    header('Location: view_sales.php');
    exit;
}

$sale = $sale['data'][0];
$customer = $mysqli->common_select('customers', '*', ['id' => $sale->customer_id]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentData = [
        'customer_id' => $sale->customer_id,
        'sales_id' => $saleId,
        'type' => 'payment',
        'amount' => $_POST['amount'],
        'payment_method' => $_POST['payment_method'],
        'description' => $_POST['description']
    ];

    $paymentResult = $mysqli->common_insert('sales_payment', $paymentData);
    
    if (!$paymentResult['error']) {
        // Update payment status if fully paid
        $totalPaid = 0;
        $payments = $mysqli->common_select('sales_payment', 'SUM(amount) as total_paid', ['sales_id' => $saleId]);
        if (!$payments['error'] && !empty($payments['data'])) {
            $totalPaid = $payments['data'][0]->total_paid;
        }
        
        $newStatus = 'partial';
        if ($totalPaid >= $sale->total) {
            $newStatus = 'paid';
        }
        
        if ($sale->payment_status !== $newStatus) {
            $mysqli->common_update('sales', ['payment_status' => $newStatus], ['id' => $saleId]);
        }
        
        setFlashMessage('Payment added successfully', 'success');
        header("Location: sales_payments.php?id=$saleId");
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

$balance = $sale->total - $totalPaid;

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';
?>

<div class="main-panel">
    <div class="content">
        <div class="page-inner">
            <div class="page-header">
                <h4 class="page-title">Sales Payments</h4>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex align-items-center">
                                <h4 class="card-title">Payments for Invoice #<?= $sale->invoice_no ?></h4>
                                <div class="ml-auto">
                                    <a href="view_sales.php" class="btn btn-primary btn-round">
                                        <i class="fas fa-arrow-left"></i> Back to Sales
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Customer</label>
                                        <input type="text" class="form-control" readonly 
                                            value="<?= !$customer['error'] && !empty($customer['data']) ? $customer['data'][0]->name : ($sale->customer_name ?: 'Walk-in Customer') ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Invoice Total</label>
                                        <input type="text" class="form-control" readonly 
                                            value="<?= number_format($sale->total, 2) ?>">
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
                                                <option value="card">Card</option>
                                                <option value="bank_transfer">Bank Transfer</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="description">Description</label>
                                            <input type="text" class="form-control" id="description" name="description" 
                                                value="Payment for invoice #<?= $sale->invoice_no ?>">
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
                            <a href="sale_details.php?id=<?= $sale->id ?>" class="btn btn-info">
                                <i class="fas fa-eye"></i> View Invoice Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../requires/footer.php'; ?>