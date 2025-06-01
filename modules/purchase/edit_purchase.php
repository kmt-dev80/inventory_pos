<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php'; 
require_once __DIR__ . '/../../includes/functions.php';

// Get purchase ID
if (!isset($_GET['id'])) {
    header("Location: view_purchases.php");
    exit();
}

$purchase_id = $_GET['id'];

// Get purchase details
$purchase_result = $mysqli->common_select('purchase', '*', ['id' => $purchase_id]);
if ($purchase_result['error'] || empty($purchase_result['data'])) {
    $_SESSION['error'] = "Purchase not found!";
    header("Location: view_purchases.php");
    exit();
}

$purchase = $purchase_result['data'][0];

// Get purchase items
$items_result = $mysqli->common_select('purchase_items', '*', ['purchase_id' => $purchase_id]);
$items = $items_result['data'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mysqli->begin_transaction();
    
    try {
        // Update purchase record
        $purchase_data = [
            'supplier_id' => $_POST['supplier_id'],
            'purchase_date' => date('Y-m-d', strtotime($_POST['purchase_date'])),
            'payment_method' => $_POST['payment_method'],
            'payment_status' => $_POST['payment_status'],
            'subtotal' => $_POST['subtotal'],
            'vat' => $_POST['vat'],
            'discount' => $_POST['discount'],
            'total' => $_POST['total']
        ];
        
        $update_result = $mysqli->common_update('purchase', $purchase_data, ['id' => $purchase_id]);
        if ($update_result['error']) throw new Exception($update_result['error_msg']);
        
        // Delete existing items and stock records
        $delete_items = $mysqli->common_delete('purchase_items', ['purchase_id' => $purchase_id]);
        if ($delete_items['error']) throw new Exception($delete_items['error_msg']);
        
        $delete_stock = $mysqli->common_delete('stock', ['purchase_id' => $purchase_id]);
        if ($delete_stock['error']) throw new Exception($delete_stock['error_msg']);
        
        // Insert new purchase items
        foreach ($_POST['products'] as $product) {
            $item_data = [
                'purchase_id' => $purchase_id,
                'product_id' => $product['id'],
                'quantity' => $product['quantity'],
                'unit_price' => $product['price'],
                'discount' => $product['discount'] ?? 0,
                'subtotal' => $product['subtotal'],
                'vat' => $product['vat'] ?? 0,
                'total_price' => $product['total']
            ];
            
            $item_result = $mysqli->common_insert('purchase_items', $item_data);
            if ($item_result['error']) throw new Exception($item_result['error_msg']);
            
            // Update stock
            $stock_data = [
                'product_id' => $product['id'],
                'user_id' => $_SESSION['user']->id,
                'change_type' => 'purchase',
                'qty' => $product['quantity'],
                'price' => $product['price'],
                'purchase_id' => $purchase_id,
                'note' => 'Purchase updated'
            ];
            
            $stock_result = $mysqli->common_insert('stock', $stock_data);
            if ($stock_result['error']) throw new Exception($stock_result['error_msg']);
        }
        
        $mysqli->commit();
        $_SESSION['success'] = "Purchase updated successfully!";
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
                        <h4 class="card-title">Edit Purchase #<?= $purchase->reference_no ?></h4>
                        
                        <form id="purchaseForm" method="POST">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Supplier *</label>
                                        <select class="form-control" name="supplier_id" required>
                                            <option value="">Select Supplier</option>
                                            <?php foreach ($suppliers as $supplier): ?>
                                                <option value="<?= $supplier->id ?>" <?= $purchase->supplier_id == $supplier->id ? 'selected' : '' ?>>
                                                    <?= $supplier->name ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Purchase Date *</label>
                                        <input type="date" class="form-control" name="purchase_date" 
                                            value="<?= date('Y-m-d', strtotime($purchase->purchase_date)) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Payment Method *</label>
                                        <select class="form-control" name="payment_method" required>
                                            <option value="cash" <?= $purchase->payment_method == 'cash' ? 'selected' : '' ?>>Cash</option>
                                            <option value="credit" <?= $purchase->payment_method == 'credit' ? 'selected' : '' ?>>Credit</option>
                                            <option value="card" <?= $purchase->payment_method == 'card' ? 'selected' : '' ?>>Card</option>
                                            <option value="bank_transfer" <?= $purchase->payment_method == 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Payment Status *</label>
                                        <select class="form-control" name="payment_status" required>
                                            <option value="pending" <?= $purchase->payment_status == 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="partial" <?= $purchase->payment_status == 'partial' ? 'selected' : '' ?>>Partial</option>
                                            <option value="paid" <?= $purchase->payment_status == 'paid' ? 'selected' : '' ?>>Paid</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <h5>Purchase Items</h5>
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
                                            <?php foreach ($items as $item): 
                                                $product = $mysqli->common_select('products', '*', ['id' => $item->product_id])['data'][0] ?? null;
                                                $discount_percent = $item->subtotal > 0 ? ($item->discount / $item->subtotal * 100) : 0;
                                                $vat_percent = ($item->subtotal - $item->discount) > 0 ? ($item->vat / ($item->subtotal - $item->discount) * 100) : 0;
                                            ?>
                                                <tr id="row<?= $item->id ?>">
                                                    <td>
                                                        <select class="form-control product-select" name="products[<?= $item->id ?>][id]" required>
                                                            <option value="">Select Product</option>
                                                            <?php foreach ($products as $product_item): ?>
                                                                <option value="<?= $product_item->id ?>" 
                                                                    data-price="<?= $product_item->price ?>"
                                                                    <?= $item->product_id == $product_item->id ? 'selected' : '' ?>>
                                                                    <?= $product_item->name ?> (<?= $product_item->barcode ?>)
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </td>
                                                    <td><input type="number" class="form-control quantity" name="products[<?= $item->id ?>][quantity]" 
                                                        min="1" value="<?= $item->quantity ?>" required></td>
                                                    <td><input type="number" class="form-control price" name="products[<?= $item->id ?>][price]" 
                                                        step="0.01" min="0" value="<?= $item->unit_price ?>" required></td>
                                                    <td><input type="number" class="form-control discount" name="products[<?= $item->id ?>][discount]" 
                                                        step="0.01" min="0" max="100" value="<?= $discount_percent ?>"></td>
                                                    <td><input type="number" class="form-control vat" name="products[<?= $item->id ?>][vat]" 
                                                        step="0.01" min="0" max="100" value="<?= $vat_percent ?>"></td>
                                                    <td><input type="text" class="form-control total" name="products[<?= $item->id ?>][total]" 
                                                        value="<?= $item->total_price ?>" readonly></td>
                                                    <td><button type="button" class="btn btn-danger btn-sm remove-row" data-row="<?= $item->id ?>">Remove</button></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="5" class="text-right"><strong>Subtotal</strong></td>
                                                <td><input type="text" class="form-control" id="subtotal" name="subtotal" 
                                                    value="<?= $purchase->subtotal ?>" readonly></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td colspan="5" class="text-right"><strong>Discount</strong></td>
                                                <td><input type="text" class="form-control" id="discount" name="discount" 
                                                    value="<?= $purchase->discount ?>" readonly></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td colspan="5" class="text-right"><strong>VAT</strong></td>
                                                <td><input type="text" class="form-control" id="vat" name="vat" 
                                                    value="<?= $purchase->vat ?>" readonly></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td colspan="5" class="text-right"><strong>Total</strong></td>
                                                <td><input type="text" class="form-control" id="total" name="total" 
                                                    value="<?= $purchase->total ?>" readonly></td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                    
                                    <div class="form-group">
                                        <button type="button" class="btn btn-primary" id="addItemBtn">Add Item</button>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-success">Update Purchase</button>
                            <a href="view_purchases.php" class="btn btn-danger">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Add item button handler
    $('#addItemBtn').click(function() {
        const rowId = Date.now();
        addItemRow(rowId);
    });
    
    // Initialize existing rows
    $('#itemTbody tr').each(function() {
        const rowId = $(this).attr('id').replace('row', '');
        initializeRow(rowId);
    });
    
    function addItemRow(rowId) {
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
        initializeRow(rowId);
    }
    
    function initializeRow(rowId) {
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
        
        // Trigger initial calculation for existing rows
        $(`#row${rowId} .quantity`).trigger('change');
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

<?php include __DIR__ . '/../../requires/footer.php'; ?>