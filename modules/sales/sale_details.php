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

// Get sale items
$items_result = $mysqli->common_select('sale_items', '*', ['sale_id' => $sale_id]);
$items = $items_result['data'];

// Get payments and returns
$payments_result = $mysqli->common_select('sales_payment', '*', ['sales_id' => $sale_id]);
$payments = $payments_result['data'];

// Calculate payment totals
$total_payments = 0;
$total_refunds = 0;

foreach ($payments as $payment) {
    if ($payment->type == 'payment') {
        $total_payments += $payment->amount;
    } else {
        $total_refunds += $payment->amount;
    }
}

$net_paid = $total_payments - $total_refunds;
$balance_due = $sale->total - $total_payments; // Balance based only on payments, not refunds

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
                            <h4 class="card-title">Sale Details #<?= $sale->invoice_no ?></h4>
                            <div>
                                <a href="view_sales.php" class="btn btn-secondary">Back to Sales</a>
                                <a href="print_invoice.php?id=<?= $sale->id ?>" target="_blank" class="btn btn-primary">Print Invoice</a>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5>Customer Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($customer): ?>
                                            <p><strong>Name:</strong> <?= $customer->name ?></p>
                                            <p><strong>Phone:</strong> <?= $customer->phone ?? 'N/A' ?></p>
                                            <p><strong>Email:</strong> <?= $customer->email ?? 'N/A' ?></p>
                                            <p><strong>Address:</strong> <?= $customer->address ?? 'N/A' ?></p>
                                        <?php elseif ($sale->customer_name): ?>
                                            <p><strong>Name:</strong> <?= $sale->customer_name ?></p>
                                            <p><strong>Email:</strong> <?= $sale->customer_email ?? 'N/A' ?></p>
                                        <?php else: ?>
                                            <p>Walk-in customer</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5>Sale Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Invoice No:</strong> <?= $sale->invoice_no ?></p>
                                        <p><strong>Date:</strong> <?= date('d M Y H:i', strtotime($sale->created_at)) ?></p>
                                        <p>
                                            <strong>Status:</strong> 
                                            <span class="badge badge-<?= 
                                                $sale->payment_status == 'paid' ? 'success' : 
                                                ($sale->payment_status == 'partial' ? 'warning' : 'danger')
                                            ?>">
                                                <?= ucfirst($sale->payment_status) ?>
                                            </span>
                                        </p>
                                        <p><strong>Created By:</strong> 
                                            <?php 
                                                $user = $mysqli->common_select('users', 'full_name', ['id' => $sale->user_id])['data'][0] ?? null;
                                                echo $user ? $user->full_name : 'Unknown';
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>Sale Items</h5>
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
                                                    <td><?= number_format($item->total_price, 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="4" class="text-right"><strong>Subtotal</strong></td>
                                                <td><?= number_format($sale->subtotal, 2) ?></td>
                                            </tr>
                                            <tr>
                                                <td colspan="4" class="text-right"><strong>Discount</strong></td>
                                                <td><?= number_format($sale->discount, 2) ?></td>
                                            </tr>
                                            <tr>
                                                <td colspan="4" class="text-right"><strong>VAT</strong></td>
                                                <td><?= number_format($sale->vat, 2) ?></td>
                                            </tr>
                                            <tr>
                                                <td colspan="4" class="text-right"><strong>Total Amount</strong></td>
                                                <td><?= number_format($sale->total, 2) ?></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>Payment Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <tr>
                                            <td class="text-right"><strong>Total Amount:</strong></td>
                                            <td><?= number_format($sale->total, 2) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-right"><strong>Total Payments:</strong></td>
                                            <td><?= number_format($total_payments, 2) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-right"><strong>Total Refunds:</strong></td>
                                            <td><?= number_format($total_refunds, 2) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-right"><strong>Net Paid Amount:</strong></td>
                                            <td><?= number_format($net_paid, 2) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-right"><strong>Balance Due:</strong></td>
                                            <td><?= number_format(max(0, $balance_due), 2) ?></td>
                                        </tr>
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
                                    <p>No payment records found for this sale.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <a href="view_sales.php" class="btn btn-secondary">Back to Sales</a>
                            <?php if ($_SESSION['user']->role == 'admin' || $_SESSION['user']->role == 'cashier'): ?>
                                <a href="sales_payment.php?id=<?= $sale->id ?>" class="btn btn-success">Add Payment</a>
                                <?php if ($total_payments > 0): ?>
                                    <a href="sales_return.php?id=<?= $sale->id ?>" class="btn btn-warning">Process Return</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../requires/footer.php'; ?>