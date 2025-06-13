<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';

// Get sale ID
if (!isset($_GET['id'])) {
    header("Location: view_sales.php");
    exit();
}

$sale_id = $_GET['id'];

// Get sale details
$sale_result = $mysqli->common_select('sales', '*', ['id' => $sale_id]);
if ($sale_result['error'] || empty($sale_result['data'])) {
    $_SESSION['error'] = "Sale not found!";
    header("Location: view_sales.php");
    exit();
}

$sale = $sale_result['data'][0];

// Get customer details
$customer = $sale->customer_id ? 
    $mysqli->common_select('customers', '*', ['id' => $sale->customer_id])['data'][0] : null;

// Get payments
$payments_result = $mysqli->common_select('sales_payment', '*', ['sales_id' => $sale_id]);
$payments = $payments_result['data'];

// Replace the payment calculation with:
$total_payments = 0;
$total_refunds = 0;

foreach ($payments as $payment) {
    if ($payment->type == 'payment') {
        $total_payments += $payment->amount;
    } else {
        $total_refunds += $payment->amount;
    }
}

$paid_amount = $total_payments - $total_refunds;
$balance = $sale->total - $total_payments; // Balance based only on payments

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_data = [
        'customer_id' => $sale->customer_id,
        'sales_id' => $sale_id,
        'type' => $_POST['type'],
        'amount' => $_POST['amount'],
        'payment_method' => $_POST['payment_method'],
        'description' => $_POST['description']
    ];
    
    $result = $mysqli->common_insert('sales_payment', $payment_data);
    
    if ($result['error']) {
        $_SESSION['error'] = $result['error_msg'];
    } else {
        // Update payment status if full amount is paid
        if ($_POST['type'] == 'payment') {
            $new_paid_amount = $paid_amount + $_POST['amount'];
            $new_balance = $sale->total - $new_paid_amount;
            
            $status = 'pending';
            if ($new_balance <= 0) {
                $status = 'paid';
            } elseif ($new_paid_amount > 0) {
                $status = 'partial';
            }
            
            $update_result = $mysqli->common_update('sales', ['payment_status' => $status], ['id' => $sale_id]);
        }
        
        $_SESSION['success'] = "Payment recorded successfully!";
        header("Location: sales_payment.php?id=" . $sale_id);
        exit();
    }
}

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
                            <h4 class="card-title">Sale Payments #<?= $sale->invoice_no ?></h4>
                            <a href="sale_details.php?id=<?= $sale->id ?>" class="btn btn-secondary">Back to Sale</a>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5>Payment Summary</h5>
                                        <table class="table">
                                            <tr>
                                                <td><strong>Total Amount:</strong></td>
                                                <td><?= number_format($sale->total, 2) ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Total Payments:</strong></td>
                                                <td><?= number_format($total_payments, 2) ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Total Refunds:</strong></td>
                                                <td><?= number_format($total_refunds, 2) ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Net Paid:</strong></td>
                                                <td><?= number_format($paid_amount, 2) ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Balance Due:</strong></td>
                                                <td><?= number_format($balance, 2) ?></td>
                                            </tr>
                                                                                    </table>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5>Customer Information</h5>
                                        <?php if ($customer): ?>
                                            <p><strong>Name:</strong> <?= $customer->name ?></p>
                                            <p><strong>Phone:</strong> <?= $customer->phone ?? 'N/A' ?></p>
                                            <p><strong>Email:</strong> <?= $customer->email ?? 'N/A' ?></p>
                                        <?php elseif ($sale->customer_name): ?>
                                            <p><strong>Name:</strong> <?= $sale->customer_name ?></p>
                                            <p><strong>Email:</strong> <?= $sale->customer_email ?? 'N/A' ?></p>
                                        <?php else: ?>
                                            <p>Walk-in customer</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>Add Payment/Refund</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Type *</label>
                                                <select class="form-control" name="type" id="paymentType" required>
                                                    <option value="payment">Payment</option>
                                                    <option value="return">Refund</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Amount *</label>
                                                <input type="number" class="form-control" name="amount" 
                                                    step="0.01" min="0" max="<?= $balance ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Payment Method *</label>
                                                <select class="form-control" name="payment_method" required>
                                                    <option value="cash">Cash</option>
                                                    <option value="card">Card</option>
                                                    <option value="bank_transfer">Bank Transfer</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea class="form-control" name="description" rows="2"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Record Payment</button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h5>Payment History</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($payments)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped" id="paymentsTable">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Type</th>
                                                    <th>Amount</th>
                                                    <th>Method</th>
                                                    <th>Description</th>
                                                    <th>Actions</th>
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
                                                        <td>
                                                            <?php if ($_SESSION['user']->role == 'admin'): ?>
                                                                <button class="btn btn-danger btn-sm delete-payment" 
                                                                    data-id="<?= $payment->id ?>">Delete</button>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p>No payment records found for this sale.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#paymentsTable').DataTable({
        "order": [[0, "desc"]]
    });
    
    // Payment type change handler
    $('#paymentType').change(function() {
        if ($(this).val() === 'payment') {
            $('input[name="amount"]').attr('max', <?= $balance ?>);
        } else {
            $('input[name="amount"]').attr('max', <?= $paid_amount ?>);
        }
    });
    
    // Delete payment button handler
    $('.delete-payment').click(function() {
        const paymentId = $(this).data('id');
        if (confirm('Are you sure you want to delete this payment record?')) {
            window.location.href = 'delete_payment.php?id=' + paymentId + '&sale_id=<?= $sale_id ?>';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../requires/footer.php'; ?>