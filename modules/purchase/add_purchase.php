<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php'; 
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mysqli->begin_transaction();
    
    try {
        // Insert purchase record
        $purchase_data = [
            'supplier_id' => $_POST['supplier_id'],
            'reference_no' => 'PUR-' . strtoupper(uniqid()),
            'purchase_date' => date('Y-m-d', strtotime($_POST['purchase_date'])),
            'payment_method' => $_POST['payment_method'],
            'payment_status' => $_POST['payment_status'],
            'subtotal' => $_POST['subtotal'],
            'vat' => $_POST['vat'],
            'discount' => $_POST['discount'],
            'total' => $_POST['total'],
            'user_id' => $_SESSION['user']->id
        ];
        
        $purchase_result = $mysqli->common_insert('purchase', $purchase_data);
        if ($purchase_result['error']) throw new Exception($purchase_result['error_msg']);
        
        $purchase_id = $purchase_result['data'];
        
        // Insert purchase items
        foreach ($_POST['products'] as $product) {
            $item_data = [
                'purchase_id' => $purchase_id,
                'product_id' => $product['id'],
                'quantity' => $product['quantity'],
                'unit_price' => $product['price'],
                'discount' => $product['discount'] ?? 0,
                //'subtotal' => $product['subtotal'],
                'vat' => $product['vat'] ?? 0,
                'total_price' => $product['total']
            ];
            
            $item_result = $mysqli->common_insert('purchase_items', $item_data);
            if ($item_result['error']) throw new Exception($item_result['error_msg']);
            
            // Update stock with discounted price (excluding VAT)
            $discounted_price = $product['price'] * (1 - ($product['discount'] ?? 0) / 100);
            $stock_data = [
                'product_id' => $product['id'],
                'user_id' => $_SESSION['user']->id,
                'change_type' => 'purchase',
                'qty' => $product['quantity'],
                'price' => $discounted_price, // Using discounted price without VAT
                'purchase_id' => $purchase_id,
                'note' => 'Purchase added'
            ];

            $stock_result = $mysqli->common_insert('stock', $stock_data);
            if ($stock_result['error']) throw new Exception($stock_result['error_msg']);
        }
        
        // Insert payment if paid
        if ($_POST['payment_status'] == 'paid' || $_POST['payment_status'] == 'partial') {
            $payment_data = [
                'supplier_id' => $_POST['supplier_id'],
                'purchase_id' => $purchase_id,
                'type' => 'payment',
                'amount' => $_POST['amount_paid'],
                'payment_method' => $_POST['payment_method']
            ];
            
            $payment_result = $mysqli->common_insert('purchase_payment', $payment_data);
            if ($payment_result['error']) throw new Exception($payment_result['error_msg']);
        }
        
        $mysqli->commit();
        $_SESSION['success'] = "Purchase added successfully!";
        header("Location: view_purchases.php");
        exit();
    } catch (Exception $e) {
        $mysqli->rollback();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Get suppliers and products for dropdowns
$suppliers = $mysqli->common_select('suppliers')['data'];
$products = $mysqli->common_select('products', '*', ['is_deleted' => 0])['data'];

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
                        <h4 class="card-title">Add New Purchase</h4>
                        
                        <form id="purchaseForm" method="POST">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Supplier *</label>
                                        <select class="form-control" name="supplier_id" required>
                                            <option value="">Select Supplier</option>
                                            <?php foreach ($suppliers as $supplier): ?>
                                                <option value="<?= $supplier->id ?>"><?= $supplier->name ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Purchase Date *</label>
                                        <input type="date" class="form-control" name="purchase_date" value="<?= date('Y-m-d') ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Payment Method *</label>
                                        <select class="form-control" name="payment_method" required>
                                            <option value="cash">Cash</option>
                                            <option value="credit">Credit</option>
                                            <option value="card">Card</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Payment Status *</label>
                                        <select class="form-control" name="payment_status" id="paymentStatus" required>
                                            <option value="pending">Pending</option>
                                            <option value="partial">Partial</option>
                                            <option value="paid">Paid</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4" id="amountPaidField" style="display:none;">
                                    <div class="form-group">
                                        <label>Amount Paid *</label>
                                        <input type="number" class="form-control" name="amount_paid" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <h5>Purchase Items</h5>
                                    <div class="form-group">
                                        <button type="button" class="btn btn-primary" id="addItemBtn">Add Item</button>
                                    </div>
                                    <table class="table table-bordered" id="itemTable">
                                        <thead>
                                            <tr>
                                                <th width="30%">Product</th>
                                                <th width="15%">Quantity</th>
                                                <th width="15%">Unit Price</th>
                                                <th width="10%">Discount %</th>
                                                <th width="15%">VAT %</th>
                                                <th width="15%">Total</th>
                                                <th width="10%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="itemTbody">
                                            <!-- Items will be added dynamically -->
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="5" class="text-right"><strong>Subtotal</strong></td>
                                                <td><input type="text" class="form-control" id="subtotal" name="subtotal" readonly></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td colspan="5" class="text-right"><strong>Discount</strong></td>
                                                <td><input type="text" class="form-control" id="discount" name="discount" readonly></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td colspan="5" class="text-right"><strong>VAT</strong></td>
                                                <td><input type="text" class="form-control" id="vat" name="vat" readonly></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td colspan="5" class="text-right"><strong>Grand Total</strong></td>
                                                <td><input type="text" class="form-control" id="total" name="total" readonly></td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                    
                                    
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-success">Save Purchase</button>
                            <a href="view_purchases.php" class="btn btn-danger">Cancel</a>
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
    // Payment status change handler
    $('#paymentStatus').change(function() {
        if ($(this).val() === 'paid' || $(this).val() === 'partial') {
            $('#amountPaidField').show();
            $('input[name="amount_paid"]').attr('required', true);
        } else {
            $('#amountPaidField').hide();
            $('input[name="amount_paid"]').attr('required', false);
        }
    });
    
    // Add item button handler
    $('#addItemBtn').click(function() {
        addItemRow();
    });
    
    // Initial item row
    addItemRow();
    
    function addItemRow() {
        const rowId = Date.now();
        const row = `
            <tr id="row${rowId}">
                <td>
                    <select class="form-control product-select" name="products[${rowId}][id]" required>
                        <option value="">Select Product</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= $product->id ?>" data-price="<?= $product->price ?>"><?= $product->name ?> (<?= $product->barcode ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="number" class="form-control quantity" name="products[${rowId}][quantity]" min="1" value="1" required></td>
                <td><input type="number" class="form-control price" name="products[${rowId}][price]" step="0.01" min="0" required></td>
                <td><input type="number" class="form-control discount" name="products[${rowId}][discount]" step="0.01" min="0" max="100" value="0"></td>
                <td><input type="number" class="form-control vat" name="products[${rowId}][vat]" step="0.01" min="0" max="100" value="0"></td>
                <td><input type="text" class="form-control total" name="products[${rowId}][total]" readonly></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-row" data-row="${rowId}">Remove</button></td>
            </tr>
        `;
        $('#itemTbody').append(row);
        
        // Set default price when product is selected
        $(`#row${rowId} .product-select`).change(function() {
            const selectedOption = $(this).find('option:selected');
            const price = selectedOption.data('price');
            $(`#row${rowId} .price`).val(price).trigger('change');
        });
        
        // Calculate totals when values change
        $(`#row${rowId} .quantity, #row${rowId} .price, #row${rowId} .discount, #row${rowId} .vat`).change(function() {
            calculateRowTotal(rowId);
            calculateGrandTotal();
        });
        
        // Remove row button
        $(`#row${rowId} .remove-row`).click(function() {
            $(`#row${rowId}`).remove();
            calculateGrandTotal();
        });
    }
    
    function calculateRowTotal(rowId) {
        const quantity = parseFloat($(`#row${rowId} .quantity`).val()) || 0;
        const price = parseFloat($(`#row${rowId} .price`).val()) || 0;
        const discount = parseFloat($(`#row${rowId} .discount`).val()) || 0;
        const vat = parseFloat($(`#row${rowId} .vat`).val()) || 0;
        
        const subtotal = quantity * price;
        const discountAmount = subtotal * (discount / 100);
        const taxable = subtotal - discountAmount;
        const vatAmount = taxable * (vat / 100);
        const total = taxable + vatAmount;
        
        $(`#row${rowId} .total`).val(total.toFixed(2));
    }
    
    function calculateGrandTotal() {
        let subtotal = 0;
        let totalDiscount = 0;
        let totalVat = 0;
        let grandTotal = 0;
        
        $('#itemTbody tr').each(function() {
            const quantity = parseFloat($(this).find('.quantity').val()) || 0;
            const price = parseFloat($(this).find('.price').val()) || 0;
            const discount = parseFloat($(this).find('.discount').val()) || 0;
            const vat = parseFloat($(this).find('.vat').val()) || 0;
            
            const rowSubtotal = quantity * price;
            const rowDiscount = rowSubtotal * (discount / 100);
            const taxable = rowSubtotal - rowDiscount;
            const rowVat = taxable * (vat / 100);
            
            subtotal += rowSubtotal;
            totalDiscount += rowDiscount;
            totalVat += rowVat;
            grandTotal += taxable + rowVat;
        });
        
        $('#subtotal').val(subtotal.toFixed(2));
        $('#discount').val(totalDiscount.toFixed(2));
        $('#vat').val(totalVat.toFixed(2));
        $('#total').val(grandTotal.toFixed(2));
    }
});
</script>