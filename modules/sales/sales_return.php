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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mysqli->begin_transaction();
    
    try {
        // Create sales return record
        $return_data = [
            'sale_id' => $sale_id,
            'return_reason' => $_POST['return_reason'],
            'return_note' => $_POST['return_note'],
            'refund_amount' => $_POST['refund_amount'],
            'refund_method' => $_POST['refund_method'],
            'user_id' => $_SESSION['user']->id
        ];
        
        $return_result = $mysqli->common_insert('sales_returns', $return_data);
        if ($return_result['error']) throw new Exception($return_result['error_msg']);
        
        $return_id = $return_result['data'];
        
        // Insert return items
        $total_refund = 0;
        foreach ($_POST['products'] as $product) {
            if ($product['return_qty'] > 0) {
                $item_data = [
                    'sales_return_id' => $return_id,
                    'product_id' => $product['id'],
                    'quantity' => $product['return_qty'],
                    'unit_price' => $product['price'],
                    'total_price' => $product['return_total']
                ];
                
                $item_result = $mysqli->common_insert('sales_return_items', $item_data);
                if ($item_result['error']) throw new Exception($item_result['error_msg']);
                
                // Update stock
                $stock_data = [
                    'product_id' => $product['id'],
                    'user_id' => $_SESSION['user']->id,
                    'change_type' => 'sales_return',
                    'qty' => $product['return_qty'],
                    'price' => $product['price'],
                    'sales_return_id' => $return_id,
                    'note' => 'Sales return'
                ];
                
                $stock_result = $mysqli->common_insert('stock', $stock_data);
                if ($stock_result['error']) throw new Exception($stock_result['error_msg']);
                
                $total_refund += $product['return_total'];
            }
        }
        
        // Record refund payment if applicable
        if ($_POST['refund_method'] == 'cash' || $_POST['refund_method'] == 'bank_transfer') {
            $payment_data = [
                'customer_id' => $sale->customer_id,
                'sales_id' => $sale_id,
                'sales_return_id' => $return_id,
                'type' => 'return',
                'amount' => $total_refund,
                'payment_method' => $_POST['refund_method'],
                'description' => 'Refund for sale return #' . $sale->invoice_no
            ];
            
            $payment_result = $mysqli->common_insert('sales_payment', $payment_data);
            if ($payment_result['error']) throw new Exception($payment_result['error_msg']);
        }
        
        $mysqli->commit();
        $_SESSION['success'] = "Sale return processed successfully!";
        header("Location: sale_details.php?id=" . $sale_id);
        exit();
    } catch (Exception $e) {
        $mysqli->rollback();
        $_SESSION['error'] = "Error: " . $e->getMessage();
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
                            <h4 class="card-title">Return Sale #<?= $sale->invoice_no ?></h4>
                            <a href="sale_details.php?id=<?= $sale->id ?>" class="btn btn-secondary">Back to Sale</a>
                        </div>
                        
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5>Sale Information</h5>
                                        <p><strong>Invoice No:</strong> <?= $sale->invoice_no ?></p>
                                        <p><strong>Date:</strong> <?= date('d M Y', strtotime($sale->created_at)) ?></p>
                                        <p><strong>Total Amount:</strong> <?= number_format($sale->total, 2) ?></p>
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
                        
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Return Reason *</label>
                                        <select class="form-control" name="return_reason" required>
                                            <option value="defective">Defective Product</option>
                                            <option value="wrong_item">Wrong Item Received</option>
                                            <option value="customer_change_mind">Customer Changed Mind</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Refund Method *</label>
                                        <select class="form-control" name="refund_method" id="refundMethod" required>
                                            <option value="cash">Cash</option>
                                            <option value="credit">Credit Note</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Return Note</label>
                                <textarea class="form-control" name="return_note" rows="2"></textarea>
                            </div>
                            
                            <hr>
                            
                            <h5>Return Items</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Sold Qty</th>
                                            <th>Return Qty</th>
                                            <th>Unit Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item): 
                                            $product = $mysqli->common_select('products', '*', ['id' => $item->product_id])['data'][0] ?? null;
                                        ?>
                                            <tr>
                                                <td><?= $product ? $product->name . ' (' . $product->barcode . ')' : 'Product not found' ?></td>
                                                <td><?= $item->quantity ?></td>
                                                <td>
                                                    <input type="hidden" name="products[<?= $item->id ?>][id]" value="<?= $item->product_id ?>">
                                                    <input type="hidden" name="products[<?= $item->id ?>][price]" value="<?= $item->unit_price ?>">
                                                    <input type="number" class="form-control return-qty" 
                                                        name="products[<?= $item->id ?>][return_qty]" 
                                                        min="0" max="<?= $item->quantity ?>" 
                                                        value="0" data-price="<?= $item->unit_price ?>">
                                                </td>
                                                <td><?= number_format($item->unit_price, 2) ?></td>
                                                <td>
                                                    <input type="text" class="form-control return-total" 
                                                        name="products[<?= $item->id ?>][return_total]" 
                                                        value="0.00" readonly>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-right"><strong>Total Refund Amount</strong></td>
                                            <td><input type="text" class="form-control" id="totalRefund" name="refund_amount" value="0.00" readonly></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Process Return</button>
                            <a href="sale_details.php?id=<?= $sale->id ?>" class="btn btn-danger">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../requires/footer.php'; ?>
<script>
$(document).ready(function() {
    // Calculate return totals when quantity changes
    $('.return-qty').change(function() {
        const qty = parseFloat($(this).val()) || 0;
        const price = parseFloat($(this).data('price')) || 0;
        const total = qty * price;
        
        $(this).closest('tr').find('.return-total').val(total.toFixed(2));
        calculateTotalRefund();
    });
    
    function calculateTotalRefund() {
        let total = 0;
        $('.return-total').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        $('#totalRefund').val(total.toFixed(2));
    }
});
</script>