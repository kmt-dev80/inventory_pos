<?php
require_once __DIR__ . '/../../db_plugin.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check permissions
checkPermission('cashier');

if (isset($_GET['sale_id']) && !empty($_GET['sale_id'])) {
    $saleId = (int)$_GET['sale_id'];
    $sale = $mysqli->common_select('sales', '*', ['id' => $saleId]);
    
    if ($sale['error'] || empty($sale['data'])) {
        setFlashMessage('Sale not found', 'danger');
        header('Location: view_sales.php');
        exit;
    }
    
    $sale = $sale['data'][0];
    $saleItems = $mysqli->common_select('sale_items', '*', ['sale_id' => $saleId]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $returnData = [
        'sale_id' => $_POST['sale_id'],
        'return_reason' => $_POST['return_reason'],
        'return_note' => $_POST['return_note'],
        'refund_amount' => $_POST['refund_amount'],
        'refund_method' => $_POST['refund_method'],
        'user_id' => $_SESSION['user']->id
    ];

    $returnResult = $mysqli->common_insert('sales_returns', $returnData);
    
    if (!$returnResult['error']) {
        $returnId = $returnResult['data'];
        
        foreach ($_POST['product_id'] as $index => $productId) {
            if ($_POST['return_quantity'][$index] > 0) {
                $itemData = [
                    'sales_return_id' => $returnId,
                    'product_id' => $productId,
                    'quantity' => $_POST['return_quantity'][$index],
                    'unit_price' => $_POST['unit_price'][$index]
                ];
                
                $mysqli->common_insert('sales_return_items', $itemData);
            }
        }
        
        if ($_POST['refund_amount'] > 0) {
            $paymentData = [
                'customer_id' => $_POST['customer_id'],
                'sales_id' => $_POST['sale_id'],
                'sales_return_id' => $returnId,
                'type' => 'return',
                'amount' => $_POST['refund_amount'],
                'payment_method' => $_POST['refund_method'],
                'description' => 'Refund for return #' . $returnId
            ];
            
            $mysqli->common_insert('sales_payment', $paymentData);
        }
        
        setFlashMessage('Sales return created successfully', 'success');
        header('Location: sale_details.php?id=' . $_POST['sale_id']);
        exit;
    } else {
        setFlashMessage('Error creating sales return: ' . $returnResult['error_msg'], 'danger');
    }
}

$salesReturns = $mysqli->common_select('sales_returns', '*', isset($saleId) ? ['sale_id' => $saleId] : [], 'created_at', 'desc');

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-panel">
    <div class="content">
        <div class="page-inner">
            <div class="page-header">
                <h4 class="page-title">Sales Returns</h4>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex align-items-center">
                                <h4 class="card-title">
                                    <?= isset($sale) ? 'Create Return for Invoice #' . $sale->invoice_no : 'Sales Returns' ?>
                                </h4>
                                <?php if (!isset($sale)): ?>
                                    <a href="view_sales.php" class="btn btn-primary btn-round ml-auto">
                                        <i class="fas fa-arrow-left"></i> Back to Sales
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (isset($sale)): ?>
                            <form method="post">
                                <input type="hidden" name="sale_id" value="<?= $sale->id ?>">
                                <input type="hidden" name="customer_id" value="<?= $sale->customer_id ?>">
                                
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Customer</label>
                                                <?php 
                                                    $customer = $sale->customer_id ? 
                                                        $mysqli->common_select('customers', 'name', ['id' => $sale->customer_id]) : 
                                                        ['error' => true];
                                                ?>
                                                <input type="text" class="form-control" readonly 
                                                    value="<?= !$customer['error'] && !empty($customer['data']) ? 
                                                        $customer['data'][0]->name : 
                                                        ($sale->customer_name ?: 'Walk-in Customer') ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Invoice Date</label>
                                                <input type="text" class="form-control" readonly 
                                                    value="<?= date('d M Y', strtotime($sale->created_at)) ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Invoice Total</label>
                                                <input type="text" class="form-control" readonly 
                                                    value="<?= number_format($sale->total, 2) ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    <h4>Return Items</h4>
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="returnItemsTable">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Sold Qty</th>
                                                    <th>Available Qty</th>
                                                    <th>Return Qty</th>
                                                    <th>Unit Price</th>
                                                    <th>Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!$saleItems['error'] && !empty($saleItems['data'])): ?>
                                                    <?php foreach ($saleItems['data'] as $item): 
                                                        $product = $mysqli->common_select('products', 'name, barcode', ['id' => $item->product_id]);
                                                        $productName = !$product['error'] && !empty($product['data']) ? 
                                                            $product['data'][0]->name . ' (' . $product['data'][0]->barcode . ')' : 'Product Not Found';
                                                        
                                                        // Get current stock
                                                        $stock = $mysqli->common_select('stock', 'SUM(CASE 
                                                            WHEN change_type = "purchase" THEN qty
                                                            WHEN change_type = "purchase_return" THEN -qty
                                                            WHEN change_type = "sale" THEN -qty
                                                            WHEN change_type = "sales_return" THEN qty
                                                            WHEN change_type = "adjustment" THEN 
                                                                CASE 
                                                                    WHEN (SELECT adjustment_type FROM inventory_adjustments WHERE id = adjustment_id) = "add" THEN qty
                                                                    ELSE -qty
                                                                END
                                                            END) as current_qty', ['product_id' => $item->product_id]);
                                                        $currentQty = !$stock['error'] && !empty($stock['data']) ? $stock['data'][0]->current_qty : 0;
                                                    ?>
                                                        <tr>
                                                            <td>
                                                                <?= $productName ?>
                                                                <input type="hidden" name="product_id[]" value="<?= $item->product_id ?>">
                                                            </td>
                                                            <td><?= $item->quantity ?></td>
                                                            <td><?= $currentQty + $item->quantity ?></td>
                                                            <td>
                                                                <input type="number" class="form-control return-qty" name="return_quantity[]" 
                                                                    min="0" max="<?= $item->quantity ?>" value="0">
                                                            </td>
                                                            <td>
                                                                <input type="number" class="form-control unit-price" name="unit_price[]" 
                                                                    value="<?= $item->unit_price ?>" readonly>
                                                            </td>
                                                            <td>
                                                                <input type="text" class="form-control return-total" readonly value="0.00">
                                                            </td>
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
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="return_reason">Return Reason</label>
                                                <select class="form-control" id="return_reason" name="return_reason" required>
                                                    <option value="defective">Defective Product</option>
                                                    <option value="wrong_item">Wrong Item</option>
                                                    <option value="customer_change_mind">Customer Changed Mind</option>
                                                    <option value="other">Other</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="refund_method">Refund Method</label>
                                                <select class="form-control" id="refund_method" name="refund_method" required>
                                                    <option value="cash">Cash</option>
                                                    <option value="credit">Credit</option>
                                                    <option value="exchange">Exchange</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="refund_amount">Refund Amount</label>
                                                <input type="number" class="form-control" id="refund_amount" name="refund_amount" 
                                                    min="0" step="0.01" value="0" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="return_note">Return Note</label>
                                        <textarea class="form-control" id="return_note" name="return_note" rows="3"></textarea>
                                    </div>
                                </div>
                                <div class="card-action">
                                    <button type="submit" class="btn btn-success">Submit Return</button>
                                    <a href="sale_details.php?id=<?= $sale->id ?>" class="btn btn-danger">Cancel</a>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="salesReturnsTable" class="display table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Return #</th>
                                                <th>Invoice No</th>
                                                <th>Customer</th>
                                                <th>Date</th>
                                                <th>Items</th>
                                                <th>Refund</th>
                                                <th>Reason</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!$salesReturns['error'] && !empty($salesReturns['data'])): ?>
                                                <?php foreach ($salesReturns['data'] as $return): 
                                                    $sale = $mysqli->common_select('sales', 'invoice_no, customer_id, customer_name', ['id' => $return->sale_id]);
                                                    $customer = $sale['data'][0]->customer_id ? 
                                                        $mysqli->common_select('customers', 'name', ['id' => $sale['data'][0]->customer_id]) : 
                                                        ['error' => true];
                                                    $items = $mysqli->common_select('sales_return_items', '*', ['sales_return_id' => $return->id]);
                                                ?>
                                                    <tr>
                                                        <td>RTN-<?= $return->id ?></td>
                                                        <td><?= !$sale['error'] && !empty($sale['data']) ? $sale['data'][0]->invoice_no : 'N/A' ?></td>
                                                        <td>
                                                            <?= !$customer['error'] && !empty($customer['data']) ? 
                                                                $customer['data'][0]->name : 
                                                                ($sale['data'][0]->customer_name ?: 'Walk-in') ?>
                                                        </td>
                                                        <td><?= date('d M Y', strtotime($return->created_at)) ?></td>
                                                        <td><?= !$items['error'] ? count($items['data']) : 0 ?></td>
                                                        <td><?= number_format($return->refund_amount, 2) ?></td>
                                                        <td><?= ucfirst(str_replace('_', ' ', $return->return_reason)) ?></td>
                                                        <td>
                                                            <a href="sales_return_items.php?id=<?= $return->id ?>" class="btn btn-info btn-sm">
                                                                <i class="fas fa-eye"></i> View
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="8" class="text-center">No sales returns found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    <?php if (isset($sale)): ?>
        // Calculate return totals
        $(document).on('input', '.return-qty', function() {
            const row = $(this).closest('tr');
            const qty = parseFloat($(this).val()) || 0;
            const unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
            const total = qty * unitPrice;
            row.find('.return-total').val(total.toFixed(2));
            
            // Update refund amount
            let refundAmount = 0;
            $('.return-total').each(function() {
                refundAmount += parseFloat($(this).val()) || 0;
            });
            $('#refund_amount').val(refundAmount.toFixed(2));
        });
    <?php else: ?>
        $('#salesReturnsTable').DataTable();
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>