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

// Get sale details with discount and VAT info
$sale_result = $mysqli->common_select('sales', '*', ['id' => $sale_id]);
if ($sale_result['error'] || empty($sale_result['data'])) {
    $_SESSION['error'] = "Sale not found!";
    header("Location: view_sales.php");
    exit();
}

$sale = $sale_result['data'][0];

// Calculate discount and VAT factors
$discount_factor = $sale->subtotal > 0 ? ($sale->discount / $sale->subtotal) : 0;
$vat_factor = ($sale->subtotal - $sale->discount) > 0 ? ($sale->vat / ($sale->subtotal - $sale->discount)) : 0;

$customer = $sale->customer_id ? 
    $mysqli->common_select('customers', '*', ['id' => $sale->customer_id])['data'][0] : null;

// Get sale items with discounted prices and original FIFO batches
$items_query = "
    SELECT si.*, 
           (si.unit_price * (1 - ?)) as discounted_price,
           (si.unit_price * (1 - ?) * (1 + ?)) as price_with_vat,
           GROUP_CONCAT(s.id ORDER BY s.created_at ASC) as batch_ids,
           GROUP_CONCAT(s.qty ORDER BY s.created_at ASC) as batch_qtys,
           GROUP_CONCAT(s.batch_id ORDER BY s.created_at ASC) as purchase_batch_ids,
           GROUP_CONCAT(s.price ORDER BY s.created_at ASC) as batch_prices
    FROM sale_items si
    LEFT JOIN stock s ON s.sale_id = si.sale_id AND s.product_id = si.product_id AND s.change_type = 'sale'
    WHERE si.sale_id = ?
    GROUP BY si.id
";
$stmt = $mysqli->getConnection()->prepare($items_query);
$stmt->bind_param('dddi', $discount_factor, $discount_factor, $vat_factor, $sale_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Process each item to get FIFO batch details
foreach ($items as &$item) {
    $item['batches'] = [];
    if (!empty($item['batch_ids'])) {
        $batch_ids = explode(',', $item['batch_ids']);
        $batch_qtys = explode(',', $item['batch_qtys']);
        $purchase_batch_ids = explode(',', $item['purchase_batch_ids']);
        $batch_prices = explode(',', $item['batch_prices']);
        
        for ($i = 0; $i < count($batch_ids); $i++) {
            $item['batches'][] = [
                'id' => $batch_ids[$i],
                'purchase_batch_id' => $purchase_batch_ids[$i],
                'qty' => abs($batch_qtys[$i]),
                'price' => $batch_prices[$i]
            ];
        }
    }
}
unset($item);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mysqli->begin_transaction();
    
    try {
        $total_refund = 0;
        $return_items = [];
        
        foreach ($_POST['products'] as $product_id => $product) {
            if ($product['return_qty'] > 0) {
                $total_refund += floatval($product['return_total_with_vat']);
                $return_items[$product_id] = $product;
            }
        }

        // Create return record
        $return_data = [
            'sale_id' => $sale_id,
            'return_reason' => $_POST['return_reason'],
            'return_note' => $_POST['return_note'],
            'refund_amount' => $total_refund,
            'refund_method' => $_POST['refund_method'],
            'user_id' => $_SESSION['user']->id,
            'invoice_no' => 'RTN-' . strtoupper(uniqid()),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $return_result = $mysqli->common_insert('sales_returns', $return_data);
        if ($return_result['error']) throw new Exception($return_result['error_msg']);
        
        $return_id = $return_result['data'];
        
        // Process each returned item with FIFO restoration
        foreach ($return_items as $product_id => $product) {
            $item_data = [
                'sales_return_id' => $return_id,
                'product_id' => $product_id,
                'quantity' => $product['return_qty'],
                'unit_price' => $product['price'],
                'discounted_price' => $product['discounted_price'],
                'vat_amount' => $product['vat_amount'],
                'total_price' => $product['return_total_with_vat'],
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $_SESSION['user']->id
            ];
            
            $item_result = $mysqli->common_insert('sales_return_items', $item_data);
            if ($item_result['error']) throw new Exception($item_result['error_msg']);
            
            // Find the original item to get FIFO batch info
            $original_item = null;
            foreach ($items as $item) {
                if ($item['product_id'] == $product_id) {
                    $original_item = $item;
                    break;
                }
            }
            
            if (!$original_item || empty($original_item['batches'])) {
                throw new Exception("Original sale batches not found for product ID: $product_id");
            }
            
            // Process FIFO restoration - return to original purchase batches
            $remaining_qty = $product['return_qty'];
            
            foreach ($original_item['batches'] as $batch) {
                if ($remaining_qty <= 0) break;
                
                // Determine how much to return to this batch
                $restore_qty = min($remaining_qty, $batch['qty']);
                $remaining_qty -= $restore_qty;
                
                // Record the stock movement
                $stock_data = [
                    'product_id' => $product_id,
                    'user_id' => $_SESSION['user']->id,
                    'change_type' => 'sales_return',
                    'qty' => $restore_qty,
                    'price' => $product['discounted_price'],
                    'sales_return_id' => $return_id,
                    'batch_id' => $batch['purchase_batch_id'],
                    'note' => 'Sales return (FIFO)',
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => $_SESSION['user']->id
                ];
                
                $stock_result = $mysqli->common_insert('stock', $stock_data);
                if ($stock_result['error']) throw new Exception($stock_result['error_msg']);
                
                // Log the restoration
                $log_data = [
                    'user_id' => $_SESSION['user']->id,
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'category' => 'stock',
                    'message' => "Restored $restore_qty to batch ID: {$batch['purchase_batch_id']} for product ID: $product_id, Return ID: $return_id",
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $mysqli->common_insert('system_logs', $log_data);
            }
            
            if ($remaining_qty > 0) {
                throw new Exception("Insufficient batch quantities to restore for product ID: $product_id. Remaining: $remaining_qty");
            }
        }
        
        // Process refund if applicable
        if ($_POST['refund_method'] == 'cash' || $_POST['refund_method'] == 'bank_transfer') {
            $payment_data = [
                'customer_id' => $sale->customer_id,
                'sales_id' => $sale_id,
                'sales_return_id' => $return_id,
                'type' => 'return',
                'amount' => $total_refund,
                'payment_method' => $_POST['refund_method'],
                'description' => 'Refund for return #' . $return_data['invoice_no'],
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $_SESSION['user']->id
            ];
            
            $payment_result = $mysqli->common_insert('sales_payment', $payment_data);
            if ($payment_result['error']) throw new Exception($payment_result['error_msg']);
        }
        
        $mysqli->commit();
        $_SESSION['success'] = "Sale return processed successfully! Return #" . $return_data['invoice_no'];
        header("Location: view_sales_return.php?id=" . $return_id);
        exit();
    } catch (Exception $e) {
        $mysqli->rollback();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/sidebar.php';
require_once __DIR__ . '/../../requires/topbar.php';
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
                                        <p><strong>Subtotal:</strong> <?= number_format($sale->subtotal, 2) ?></p>
                                        <p><strong>Discount:</strong> <?= number_format($sale->discount, 2) ?></p>
                                        <p><strong>VAT:</strong> <?= number_format($sale->vat, 2) ?></p>
                                        <p><strong>Total:</strong> <?= number_format($sale->total, 2) ?></p>
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
                                            <option value="exchange">Exchange</option>
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
                                            <th>Batches</th>
                                            <th>Return Qty</th>
                                            <th>Unit Price</th>
                                            <th>Discounted Price</th>
                                            <th>VAT Amount</th>
                                            <th>Refund Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item): 
                                            $product = $mysqli->common_select('products', '*', ['id' => $item['product_id']])['data'][0] ?? null;
                                            $vat_amount_per_unit = $item['price_with_vat'] - $item['discounted_price'];
                                        ?>
                                            <tr>
                                                <td><?= $product ? $product->name . ' (' . $product->barcode . ')' : 'Product not found' ?></td>
                                                <td><?= $item['quantity'] ?></td>
                                                <td>
                                                    <?php if (!empty($item['batches'])): ?>
                                                        <div class="batch-info">
                                                            <?php foreach ($item['batches'] as $batch): ?>
                                                                <div>Batch #<?= $batch['purchase_batch_id'] ?>: <?= $batch['qty'] ?> @ <?= number_format($batch['price'], 2) ?></div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        No batch info
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <input type="hidden" name="products[<?= $item['product_id'] ?>][id]" value="<?= $item['product_id'] ?>">
                                                    <input type="hidden" name="products[<?= $item['product_id'] ?>][price]" value="<?= $item['unit_price'] ?>">
                                                    <input type="hidden" name="products[<?= $item['product_id'] ?>][discounted_price]" value="<?= $item['discounted_price'] ?>">
                                                    <input type="hidden" name="products[<?= $item['product_id'] ?>][vat_amount]" value="<?= $vat_amount_per_unit ?>">
                                                    <input type="number" class="form-control return-qty" 
                                                        name="products[<?= $item['product_id'] ?>][return_qty]" 
                                                        min="0" max="<?= $item['quantity'] ?>" 
                                                        value="0" 
                                                        data-price="<?= $item['discounted_price'] ?>"
                                                        data-vat="<?= $vat_amount_per_unit ?>">
                                                </td>
                                                <td><?= number_format($item['unit_price'], 2) ?></td>
                                                <td><?= number_format($item['discounted_price'], 2) ?></td>
                                                <td><?= number_format($vat_amount_per_unit, 2) ?></td>
                                                <td>
                                                    <input type="text" class="form-control return-total" 
                                                        name="products[<?= $item['product_id'] ?>][return_total]" 
                                                        value="0.00" readonly>
                                                    <input type="hidden" class="return-total-with-vat" 
                                                        name="products[<?= $item['product_id'] ?>][return_total_with_vat]" 
                                                        value="0.00">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="6" class="text-right"><strong>Total VAT Amount</strong></td>
                                            <td><input type="text" class="form-control" id="totalVat" value="0.00" readonly></td>
                                        </tr>
                                        <tr>
                                            <td colspan="6" class="text-right"><strong>Total Refund Amount (Including VAT)</strong></td>
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
        const discounted_price = parseFloat($(this).data('price')) || 0;
        const vat_per_unit = parseFloat($(this).data('vat')) || 0;
        
        const total_with_vat = qty * (discounted_price + vat_per_unit);
        
        $(this).closest('tr').find('.return-total').val(total_with_vat.toFixed(2));
        $(this).closest('tr').find('.return-total-with-vat').val(total_with_vat.toFixed(2));
        
        calculateTotalRefund();
    });
    
    function calculateTotalRefund() {
        let total_with_vat = 0;
        let total_vat = 0;
        
        $('.return-total-with-vat').each(function() {
            const amount = parseFloat($(this).val()) || 0;
            total_with_vat += amount;
        });
        
        $('[name*="[vat_amount]"]').each(function() {
            const vat_per_unit = parseFloat($(this).val()) || 0;
            const qty = parseFloat($(this).closest('tr').find('.return-qty').val()) || 0;
            total_vat += vat_per_unit * qty;
        });
        
        $('#totalVat').val(total_vat.toFixed(2));
        $('#totalRefund').val(total_with_vat.toFixed(2));
    }
});
</script>