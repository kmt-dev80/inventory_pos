<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';
require_once __DIR__ . '/../../includes/functions.php';

if (isset($_GET['purchase_id']) && !empty($_GET['purchase_id'])) {
    $purchaseId = (int)$_GET['purchase_id'];
    $purchase = $mysqli->common_select('purchase', '*', ['id' => $purchaseId]);
    
    if ($purchase['error'] || empty($purchase['data'])) {
        setFlashMessage('Purchase not found', 'danger');
        header('Location: view_purchases.php');
        exit;
    }
    
    $purchase = $purchase['data'][0];
    $purchaseItems = $mysqli->common_select('purchase_items', '*', ['purchase_id' => $purchaseId]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $returnData = [
        'purchase_id' => $_POST['purchase_id'],
        'return_reason' => $_POST['return_reason'],
        'return_note' => $_POST['return_note'],
        'refund_amount' => $_POST['refund_amount'],
        'refund_method' => $_POST['refund_method'],
        'user_id' => $_SESSION['user']->id
    ];

    $returnResult = $mysqli->common_insert('purchase_returns', $returnData);
    
    if (!$returnResult['error']) {
        $returnId = $returnResult['data'];
        
        foreach ($_POST['product_id'] as $index => $productId) {
            if ($_POST['return_quantity'][$index] > 0) {
                $itemData = [
                    'purchase_return_id' => $returnId,
                    'product_id' => $productId,
                    'quantity' => $_POST['return_quantity'][$index],
                    'unit_price' => $_POST['unit_price'][$index]
                ];
                
                $mysqli->common_insert('purchase_return_items', $itemData);
            }
        }
        
        if ($_POST['refund_amount'] > 0) {
            $paymentData = [
                'supplier_id' => $_POST['supplier_id'],
                'purchase_id' => $_POST['purchase_id'],
                'purchase_return_id' => $returnId,
                'type' => 'return',
                'amount' => $_POST['refund_amount'],
                'payment_method' => $_POST['refund_method'],
                'description' => 'Refund for return #' . $returnId
            ];
            
            $mysqli->common_insert('purchase_payment', $paymentData);
        }
        
        setFlashMessage('Purchase return created successfully', 'success');
        header('Location: view_purchases.php');
        exit;
    } else {
        setFlashMessage('Error creating purchase return: ' . $returnResult['error_msg'], 'danger');
    }
}

$purchaseReturns = $mysqli->common_select('purchase_returns', '*', isset($purchaseId) ? ['purchase_id' => $purchaseId] : [], 'created_at', 'desc');

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Purchase Returns</h4>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <h4 class="card-title">
                                <?= isset($purchase) ? 'Create Return for Purchase #' . $purchase->reference_no : 'Purchase Returns' ?>
                            </h4>
                            <?php if (!isset($purchase)): ?>
                                <a href="view_purchases.php" class="btn btn-primary btn-round ml-auto">
                                    <i class="fas fa-arrow-left"></i> Back to Purchases
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (isset($purchase)): ?>
                        <form method="post">
                            <input type="hidden" name="purchase_id" value="<?= $purchase->id ?>">
                            <input type="hidden" name="supplier_id" value="<?= $purchase->supplier_id ?>">
                            
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Supplier</label>
                                            <?php 
                                                $supplier = $mysqli->common_select('suppliers', 'name', ['id' => $purchase->supplier_id]);
                                                $supplierName = !$supplier['error'] && !empty($supplier['data']) ? $supplier['data'][0]->name : 'N/A';
                                            ?>
                                            <input type="text" class="form-control" readonly value="<?= $supplierName ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Purchase Date</label>
                                            <input type="text" class="form-control" readonly 
                                                value="<?= date('d M Y', strtotime($purchase->created_at)) ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Purchase Total</label>
                                            <input type="text" class="form-control" readonly 
                                                value="<?= number_format($purchase->total, 2) ?>">
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
                                                <th>Purchased Qty</th>
                                                <th>Available Qty</th>
                                                <th>Return Qty</th>
                                                <th>Unit Price</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!$purchaseItems['error'] && !empty($purchaseItems['data'])): ?>
                                                <?php foreach ($purchaseItems['data'] as $item): 
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
                                                        <td><?= $currentQty ?></td>
                                                        <td>
                                                            <input type="number" class="form-control return-qty" name="return_quantity[]" 
                                                                min="0" max="<?= min($item->quantity, $currentQty) ?>" value="0">
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
                                                <option value="supplier_error">Supplier Error</option>
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
                                <a href="purchase_details.php?id=<?= $purchase->id ?>" class="btn btn-danger">Cancel</a>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="purchaseReturnsTable" class="display table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Return #</th>
                                            <th>Purchase Ref</th>
                                            <th>Supplier</th>
                                            <th>Date</th>
                                            <th>Items</th>
                                            <th>Refund</th>
                                            <th>Reason</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!$purchaseReturns['error'] && !empty($purchaseReturns['data'])): ?>
                                            <?php foreach ($purchaseReturns['data'] as $return): 
                                                $purchase = $mysqli->common_select('purchase', 'reference_no, supplier_id', ['id' => $return->purchase_id]);
                                                $supplier = $mysqli->common_select('suppliers', 'name', ['id' => $purchase['data'][0]->supplier_id]);
                                                $items = $mysqli->common_select('purchase_return_items', '*', ['purchase_return_id' => $return->id]);
                                            ?>
                                                <tr>
                                                    <td>RTN-<?= $return->id ?></td>
                                                    <td><?= !$purchase['error'] && !empty($purchase['data']) ? $purchase['data'][0]->reference_no : 'N/A' ?></td>
                                                    <td><?= !$supplier['error'] && !empty($supplier['data']) ? $supplier['data'][0]->name : 'N/A' ?></td>
                                                    <td><?= date('d M Y', strtotime($return->created_at)) ?></td>
                                                    <td><?= !$items['error'] ? count($items['data']) : 0 ?></td>
                                                    <td><?= number_format($return->refund_amount, 2) ?></td>
                                                    <td><?= ucfirst(str_replace('_', ' ', $return->return_reason)) ?></td>
                                                    <td>
                                                        <a href="purchase_return_items.php?id=<?= $return->id ?>" class="btn btn-info btn-sm">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center">No purchase returns found</td>
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

<script>
$(document).ready(function() {
    <?php if (isset($purchase)): ?>
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
        
        // Initialize DataTable if not in create mode
    <?php else: ?>
        $('#purchaseReturnsTable').DataTable();
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/../../requires/footer.php'; ?>