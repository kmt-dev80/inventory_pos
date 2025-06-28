<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';

if (!isset($_GET['id'])) {
    header("Location: view_purchases.php");
    exit();
}

$purchase_id = (int)$_GET['id'];

$purchase_result = $mysqli->common_select('purchase', '*', ['id' => $purchase_id]);
if ($purchase_result['error'] || empty($purchase_result['data'])) {
    $_SESSION['error'] = "Purchase not found!";
    header("Location: view_purchases.php");
    exit();
}

$purchase = $purchase_result['data'][0];

// Calculate actual VAT rate from purchase totals
$vat_rate = 0;
if (($purchase->subtotal - $purchase->discount) > 0) {
    $vat_rate = ($purchase->vat / ($purchase->subtotal - $purchase->discount)) * 100;
}

$supplier = $mysqli->common_select('suppliers', '*', ['id' => $purchase->supplier_id])['data'][0] ?? null;

$items_result = $mysqli->common_select('purchase_items', '*', ['purchase_id' => $purchase_id]);
$items = $items_result['data'];

// Calculate discount per unit
$total_purchase_qty = array_sum(array_column($items, 'quantity'));
$discount_per_unit = $total_purchase_qty > 0 ? ($purchase->discount / $total_purchase_qty) : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mysqli->begin_transaction();

    try {
        $return_data = [
            'purchase_id' => $purchase_id,
            'return_reason' => $_POST['return_reason'],
            'return_note' => $_POST['return_note'],
            'refund_amount' => 0,
            'vat_amount' => 0,
            'discount_adjusted' => 0,
            'vat_rate_used' => $vat_rate,
            'refund_method' => $_POST['refund_method'],
            'user_id' => $_SESSION['user']->id,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $return_result = $mysqli->common_insert('purchase_returns', $return_data);
        if ($return_result['error']) throw new Exception($return_result['error_msg']);

        $return_id = $return_result['data'];

        $total_refund = 0;
        $total_vat_reversed = 0;
        $total_discount_adjusted = 0;
        
        foreach ($_POST['products'] as $item_id => $product) {
            $return_qty = (int)($product['return_qty'] ?? 0);
            if ($return_qty <= 0) continue;

            $original_item = null;
            foreach ($items as $item) {
                if ($item->id == $item_id) {
                    $original_item = $item;
                    break;
                }
            }
            if (!$original_item) continue;

            $available_qty = (int)($product['available_qty'] ?? 0);
            $max_returnable = min($original_item->quantity, $available_qty);
            if ($return_qty > $max_returnable) {
                throw new Exception("Cannot return more than purchased/available quantity for product ID {$original_item->product_id}");
            }

            $discounted_price = $original_item->unit_price - $discount_per_unit;
            $vat_amount = ($discounted_price * $vat_rate) / 100;
            $total_per_unit = $discounted_price + $vat_amount;

            $item_refund = $total_per_unit * $return_qty;
            $item_vat_reversed = $vat_amount * $return_qty;
            $item_discount_adjusted = $discount_per_unit * $return_qty;

            $total_refund += $item_refund;
            $total_vat_reversed += $item_vat_reversed;
            $total_discount_adjusted += $item_discount_adjusted;

            $item_data = [
                'purchase_return_id' => $return_id,
                'product_id' => $original_item->product_id,
                'quantity' => $return_qty,
                'unit_price' => $total_per_unit,
                'total_price' => $item_refund,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $_SESSION['user']->id
            ];

            $item_result = $mysqli->common_insert('purchase_return_items', $item_data);
            if ($item_result['error']) throw new Exception($item_result['error_msg']);

            // Update stock
            $stock_data = [
                'product_id' => $original_item->product_id,
                'user_id' => $_SESSION['user']->id,
                'change_type' => 'purchase_return',
                'qty' => -$return_qty,
                'price' => $discounted_price,
                'purchase_return_id' => $return_id,
                'note' => 'Purchase return',
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $_SESSION['user']->id
            ];

            $stock_result = $mysqli->common_insert('stock', $stock_data);
            if ($stock_result['error']) throw new Exception($stock_result['error_msg']);
        }

        $return_update = [
            'refund_amount' => $total_refund,
            'vat_amount' => $total_vat_reversed,
            'discount_adjusted' => $total_discount_adjusted
        ];
        $update_result = $mysqli->common_update('purchase_returns', $return_update, ['id' => $return_id]);
        if ($update_result['error']) throw new Exception($update_result['error_msg']);

        if (in_array($_POST['refund_method'], ['cash', 'bank_transfer'])) {
            $payment_data = [
                'supplier_id' => $purchase->supplier_id,
                'purchase_id' => $purchase_id,
                'purchase_return_id' => $return_id,
                'type' => 'return',
                'amount' => $total_refund,
                'payment_method' => $_POST['refund_method'],
                'description' => 'Refund for purchase return #' . $purchase->reference_no,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $_SESSION['user']->id
            ];

            $payment_result = $mysqli->common_insert('purchase_payment', $payment_data);
            if ($payment_result['error']) throw new Exception($payment_result['error_msg']);
        }

        $mysqli->commit();
        $_SESSION['success'] = "Purchase return processed successfully!";
        header("Location: purchase_details.php?id=" . $purchase_id);
        exit();
    } catch (Exception $e) {
        $mysqli->rollback();
        $_SESSION['error'] = "Error processing return: " . $e->getMessage();
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
                            <h4 class="card-title">Return Purchase #<?= htmlspecialchars($purchase->reference_no) ?></h4>
                            <a href="purchase_details.php?id=<?= $purchase->id ?>" class="btn btn-secondary">Back to Purchase</a>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5>Purchase Information</h5>
                                        <p><strong>Reference No:</strong> <?= htmlspecialchars($purchase->reference_no) ?></p>
                                        <p><strong>Date:</strong> <?= date('d M Y', strtotime($purchase->purchase_date)) ?></p>
                                        <p><strong>Subtotal:</strong> <?= number_format($purchase->subtotal, 2) ?></p>
                                        <p><strong>Discount:</strong> <?= number_format($purchase->discount, 2) ?></p>
                                        <p><strong>VAT Amount:</strong> <?= number_format($purchase->vat, 2) ?></p>
                                        <p><strong>VAT Rate:</strong> <?= number_format($vat_rate, 2) ?>%</p>
                                        <p><strong>Total Amount:</strong> <?= number_format($purchase->total, 2) ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5>Supplier Information</h5>
                                        <?php if ($supplier): ?>
                                            <p><strong>Name:</strong> <?= htmlspecialchars($supplier->name) ?></p>
                                            <p><strong>Company:</strong> <?= htmlspecialchars($supplier->company_name ?? 'N/A') ?></p>
                                            <p><strong>Phone:</strong> <?= htmlspecialchars($supplier->phone ?? 'N/A') ?></p>
                                        <?php else: ?>
                                            <p>Supplier information not available</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form method="POST" id="returnForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Return Reason *</label>
                                        <select class="form-control" name="return_reason" required>
                                            <option value="defective">Defective Product</option>
                                            <option value="wrong_item">Wrong Item Received</option>
                                            <option value="supplier_error">Supplier Error</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Refund Method *</label>
                                        <select class="form-control" name="refund_method" id="refundMethod" required>
                                            <option value="cash">Cash</option>
                                            <option value="credit">Credit</option>
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
                            <div class="alert alert-info">
                                <strong>Note:</strong> Returns will be calculated with original discount (<?= number_format($discount_per_unit, 4) ?> per unit) and <?= number_format($vat_rate, 2) ?>% VAT rate
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered">
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
                                        <?php foreach ($items as $item): 
                                            $product = $mysqli->common_select('products', '*', ['id' => $item->product_id])['data'][0] ?? null;
                                            
                                            // Get available quantity
                                            $con = $mysqli->getConnection();
                                            $product_id = $item->product_id;
                                            $stock_sql = "SELECT SUM(qty) AS available_qty FROM stock WHERE product_id = ?";
                                            $stmt = $con->prepare($stock_sql);
                                            $stmt->bind_param("i", $product_id);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            $stock_data = $result->fetch_assoc();
                                            $available_qty = $stock_data['available_qty'] ?? 0;
                                            
                                            // Calculate item values
                                            $discounted_price = $item->unit_price - $discount_per_unit;
                                            $vat_amount = ($discounted_price * $vat_rate) / 100;
                                            $total_per_unit = $discounted_price + $vat_amount;
                                        ?>
                                            <tr>
                                                <td>
                                                    <?= $product ? htmlspecialchars($product->name) . ' (' . htmlspecialchars($product->barcode) . ')' : 'Product not found' ?>
                                                    <input type="hidden" name="products[<?= $item->id ?>][id]" value="<?= $item->product_id ?>">
                                                    <input type="hidden" name="products[<?= $item->id ?>][available_qty]" value="<?= $available_qty ?>">
                                                </td>
                                                <td><?= $item->quantity ?></td>
                                                <td><?= $available_qty ?></td>
                                                <td>
                                                    <input type="number" class="form-control return-qty"
                                                        name="products[<?= $item->id ?>][return_qty]"
                                                        min="0" max="<?= min($item->quantity, $available_qty) ?>"
                                                        value="0" 
                                                        data-original-price="<?= $item->unit_price ?>"
                                                        data-discount-per-unit="<?= $discount_per_unit ?>"
                                                        data-vat-rate="<?= $vat_rate ?>">
                                                </td>
                                                <td class="unit-price"><?= number_format($total_per_unit, 2) ?></td>
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
                                            <td colspan="5" class="text-right"><strong>Total Refund</strong></td>
                                            <td>
                                                <input type="text" class="form-control" id="totalRefund" name="refund_amount" value="0.00" readonly>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <button type="submit" class="btn btn-primary">Process Return</button>
                            <a href="purchase_details.php?id=<?= $purchase->id ?>" class="btn btn-danger">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../requires/footer.php'; ?>

<script>
$(document).ready(function() {
    // Calculate return amounts when quantities change
    $('.return-qty').on('input', function() {
        const row = $(this).closest('tr');
        const qty = parseFloat($(this).val()) || 0;
        const unitPrice = parseFloat(row.find('.unit-price').text()) || 0;
        const total = unitPrice * qty;
        
        row.find('.return-total').val(total.toFixed(2));
        calculateTotalRefund();
    });

    function calculateTotalRefund() {
        let grandTotal = 0;
        
        $('.return-total').each(function() {
            grandTotal += parseFloat($(this).val()) || 0;
        });
        
        $('#totalRefund').val(grandTotal.toFixed(2));
    }

    // Form validation
    $('#returnForm').on('submit', function(e) {
        const totalRefund = parseFloat($('#totalRefund').val()) || 0;
        if (totalRefund <= 0) {
            e.preventDefault();
            alert('Please enter quantities for at least one item to return');
            return false;
        }
        
        let hasReturns = false;
        $('.return-qty').each(function() {
            if (parseFloat($(this).val()) > 0) {
                hasReturns = true;
                return false; // break loop
            }
        });
        
        if (!hasReturns) {
            e.preventDefault();
            alert('Please select at least one item to return');
            return false;
        }
        
        return true;
    });
});
</script>