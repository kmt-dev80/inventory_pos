<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';
require_once __DIR__ . '/../../includes/functions.php';

$suppliers = $mysqli->common_select('suppliers', '*');
$products = $mysqli->common_select('products', 'id, name, barcode, price', ['is_deleted' => 0]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Calculate totals from items
        $subtotal = 0;
        $total = 0;
        $items = [];
        
        foreach ($_POST['product_id'] as $index => $productId) {
            $quantity = (float)$_POST['quantity'][$index];
            $unitPrice = (float)$_POST['unit_price'][$index];
            $itemDiscount = (float)$_POST['item_discount'][$index];
            $itemVat = (float)$_POST['item_vat'][$index];
            
            $itemSubtotal = $quantity * $unitPrice;
            $itemDiscountAmount = $itemSubtotal * ($itemDiscount / 100);
            $itemTotal = ($itemSubtotal - $itemDiscountAmount) * (1 + ($itemVat / 100));
            
            $subtotal += $itemSubtotal;
            $total += $itemTotal;
            
            $items[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'item_discount' => $itemDiscount,
                'item_vat' => $itemVat,
                'item_total' => $itemTotal
            ];
        }

        // Apply global discount/VAT if needed
        $globalDiscount = (float)$_POST['discount'];
        $globalVat = (float)$_POST['vat'];
        
        $discountAmount = $subtotal * ($globalDiscount / 100);
        $vatAmount = ($subtotal - $discountAmount) * ($globalVat / 100);
        $grandTotal = ($subtotal - $discountAmount) + $vatAmount;

        // Insert into `purchase`
        $purchaseData = [
            'supplier_id' => $_POST['supplier_id'],
            'purchase_date' => $_POST['purchase_date'],
            'reference_no' => generateReferenceNo('PUR'),
            'payment_method' => $_POST['payment_method'],
            'payment_status' => $_POST['payment_status'],
            'subtotal' => $subtotal,
            'discount' => $globalDiscount,
            'discount_amount' => $discountAmount,
            'vat' => $globalVat,
            'vat_amount' => $vatAmount,
            'total' => $grandTotal,
            'user_id' => $_SESSION['user']->id
        ];

        $purchaseResult = $mysqli->common_insert('purchase', $purchaseData);
        
        if ($purchaseResult['error']) {
            throw new Exception($purchaseResult['error_msg']);
        }

        $purchaseId = $purchaseResult['data'];
        
        // Insert items
        foreach ($items as $item) {
            $itemData = [
                'purchase_id' => $purchaseId,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount' => $item['item_discount'],
                'vat' => $item['item_vat'],
                'total_price' => $item['item_total']
            ];
            
            $itemResult = $mysqli->common_insert('purchase_items', $itemData);
            if ($itemResult['error']) {
                throw new Exception("Failed to add item: " . $itemResult['error_msg']);
            }
        }
        
        // Handle payment
        if ($_POST['payment_status'] === 'partial' || $_POST['payment_status'] === 'paid') {
            $paymentData = [
                'supplier_id' => $_POST['supplier_id'],
                'purchase_id' => $purchaseId,
                'type' => 'payment',
                'amount' => $_POST['amount_paid'],
                'payment_method' => $_POST['payment_method'],
                'description' => 'Initial payment for purchase #' . $purchaseId
            ];
            
            $paymentResult = $mysqli->common_insert('purchase_payment', $paymentData);
            if ($paymentResult['error']) {
                throw new Exception("Payment record failed: " . $paymentResult['error_msg']);
            }
        }
        
        setFlashMessage('Purchase added successfully!', 'success');
        header('Location: view_purchases.php');
        exit;

    } catch (Exception $e) {
        setFlashMessage('Error: ' . $e->getMessage(), 'danger');
    }
}

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Add New Purchase</h4>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Purchase Details</div>
                    </div>
                    <form method="post" id="purchaseForm">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="supplier_id">Supplier</label>
                                        <select class="form-control" id="supplier_id" name="supplier_id" required>
                                            <option value="">Select Supplier</option>
                                            <?php foreach ($suppliers['data'] as $supplier): ?>
                                                <option value="<?= $supplier->id ?>"><?= $supplier->name ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="purchase_date">Purchase Date</label>
                                        <input type="date" class="form-control" id="purchase_date" name="purchase_date" 
                                               value="<?= date('Y-m-d') ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
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
                                        <label for="payment_status">Payment Status</label>
                                        <select class="form-control" id="payment_status" name="payment_status" required>
                                            <option value="pending">Pending</option>
                                            <option value="partial">Partial</option>
                                            <option value="paid">Paid</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4" id="amountPaidField" style="display: none;">
                                    <div class="form-group">
                                        <label for="amount_paid">Amount Paid</label>
                                        <input type="number" class="form-control" id="amount_paid" name="amount_paid" min="0" step="0.01">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="discount">Global Discount (%)</label>
                                        <input type="number" class="form-control" id="discount" name="discount" min="0" max="100" value="0">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="vat">Global VAT (%)</label>
                                        <input type="number" class="form-control" id="vat" name="vat" min="0" value="0">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Subtotal</label>
                                        <input type="text" class="form-control" id="subtotal" name="subtotal" readonly value="0">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Grand Total</label>
                                        <input type="text" class="form-control" id="total" name="total" readonly value="0">
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            <h4>Purchase Items</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="purchaseItemsTable">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Qty</th>
                                            <th>Unit Price</th>
                                            <th>Discount %</th>
                                            <th>VAT %</th>
                                            <th>Subtotal</th>
                                            <th>Total</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <select class="form-control product-select" name="product_id[]" required>
                                                    <option value="">Select Product</option>
                                                    <?php foreach ($products['data'] as $product): ?>
                                                        <option value="<?= $product->id ?>" data-price="<?= $product->price ?>">
                                                            <?= $product->name ?> (<?= $product->barcode ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td><input type="number" class="form-control quantity" name="quantity[]" min="1" value="1" required></td>
                                            <td><input type="number" class="form-control unit-price" name="unit_price[]" min="0" step="0.01" required></td>
                                            <td><input type="number" class="form-control item-discount" name="item_discount[]" min="0" max="100" value="0" step="0.01"></td>
                                            <td><input type="number" class="form-control item-vat" name="item_vat[]" min="0" value="0" step="0.01"></td>
                                            <td><input type="text" class="form-control item-subtotal" readonly></td>
                                            <td><input type="text" class="form-control item-total" readonly></td>
                                            <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button></td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="8">
                                                <button type="button" class="btn btn-primary btn-sm" id="addRow">Add Item</button>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <div class="card-action">
                            <button type="submit" class="btn btn-success">Save Purchase</button>
                            <a href="view_purchases.php" class="btn btn-danger">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../requires/footer.php'; ?>
<script>
$(document).ready(function() {
    // Show/hide amount paid field based on payment status
    $('#payment_status').change(function() {
        if ($(this).val() === 'partial' || $(this).val() === 'paid') {
            $('#amountPaidField').show();
        } else {
            $('#amountPaidField').hide();
        }
    }).trigger('change');
    
    // Add new row
    $('#addRow').click(function() {
        const newRow = `
            <tr>
                <td>
                    <select class="form-control product-select" name="product_id[]" required>
                        <option value="">Select Product</option>
                        <?php foreach ($products['data'] as $product): ?>
                            <option value="<?= $product->id ?>" data-price="<?= $product->price ?>">
                                <?= $product->name ?> (<?= $product->barcode ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="number" class="form-control quantity" name="quantity[]" min="1" value="1" required></td>
                <td><input type="number" class="form-control unit-price" name="unit_price[]" min="0" step="0.01" required></td>
                <td><input type="number" class="form-control item-discount" name="item_discount[]" min="0" max="100" value="0" step="0.01"></td>
                <td><input type="number" class="form-control item-vat" name="item_vat[]" min="0" value="0" step="0.01"></td>
                <td><input type="text" class="form-control item-subtotal" readonly></td>
                <td><input type="text" class="form-control item-total" readonly></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button></td>
            </tr>
        `;
        $('#purchaseItemsTable tbody').append(newRow);
    });
    
    // Remove row
    $(document).on('click', '.remove-row', function() {
        if ($('#purchaseItemsTable tbody tr').length > 1) {
            $(this).closest('tr').remove();
            calculateTotals();
        } else {
            alert('You must have at least one item');
        }
    });
    
    // Set unit price when product selected
    $(document).on('change', '.product-select', function() {
        const selectedOption = $(this).find('option:selected');
        const defaultPrice = selectedOption.data('price');
        $(this).closest('tr').find('.unit-price').val(defaultPrice || '0.00');
        calculateRowTotal($(this).closest('tr'));
    });
    
    // Calculate row total when any input changes
    $(document).on('input', '.quantity, .unit-price, .item-discount, .item-vat', function() {
        calculateRowTotal($(this).closest('tr'));
    });
    
    // Calculate row total
    function calculateRowTotal(row) {
        const quantity = parseFloat(row.find('.quantity').val()) || 0;
        const unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
        const itemDiscount = parseFloat(row.find('.item-discount').val()) || 0;
        const itemVat = parseFloat(row.find('.item-vat').val()) || 0;
        
        const subtotal = quantity * unitPrice;
        const discountAmount = subtotal * (itemDiscount / 100);
        const total = (subtotal - discountAmount) * (1 + (itemVat / 100));
        
        row.find('.item-subtotal').val(subtotal.toFixed(2));
        row.find('.item-total').val(total.toFixed(2));
        calculateTotals();
    }
    
    // Calculate all totals
    function calculateTotals() {
        let subtotal = 0;
        let total = 0;
        
        $('#purchaseItemsTable tbody tr').each(function() {
            subtotal += parseFloat($(this).find('.item-subtotal').val()) || 0;
            total += parseFloat($(this).find('.item-total').val()) || 0;
        });
        
        // Apply global discount/VAT
        const globalDiscount = parseFloat($('#discount').val()) || 0;
        const globalVat = parseFloat($('#vat').val()) || 0;
        
        const discountAmount = subtotal * (globalDiscount / 100);
        const vatAmount = (subtotal - discountAmount) * (globalVat / 100);
        const grandTotal = (subtotal - discountAmount) + vatAmount;
        
        $('#subtotal').val(subtotal.toFixed(2));
        $('#total').val(grandTotal.toFixed(2));
        
        // Update amount paid if shown
        if ($('#amountPaidField').is(':visible')) {
            $('#amount_paid').val(grandTotal.toFixed(2));
        }
    }
    
    // Calculate totals when global discount/VAT changes
    $('#discount, #vat').on('input', calculateTotals);
});
</script>