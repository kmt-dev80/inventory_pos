<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: view_purchases.php');
    exit;
}

$purchaseId = (int)$_GET['id'];
$purchase = $mysqli->common_select('purchase', '*', ['id' => $purchaseId]);
$purchaseItems = $mysqli->common_select('purchase_items', '*', ['purchase_id' => $purchaseId]);

if ($purchase['error'] || empty($purchase['data'])) {
    setFlashMessage('Purchase not found', 'danger');
    header('Location: view_purchases.php');
    exit;
}

$purchase = $purchase['data'][0];
$suppliers = $mysqli->common_select('suppliers', '*');
$products = $mysqli->common_select('products', 'id, name, barcode, price', ['is_deleted' => 0]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Update purchase (includes VAT)
        $purchaseData = [
            'supplier_id' => $_POST['supplier_id'],
            'payment_method' => $_POST['payment_method'],
            'payment_status' => $_POST['payment_status'],
            'subtotal' => $_POST['subtotal'],
            'discount' => $_POST['discount'], // Fixed typo from 'discount' to 'discount'
            'vat' => $_POST['vat'], // VAT goes here (in purchase table)
            'total' => $_POST['total']
        ];

        $updateResult = $mysqli->common_update('purchase', $purchaseData, ['id' => $purchaseId]);
        
        if ($updateResult['error']) {
            throw new Exception($updateResult['error_msg']);
        }

        // Delete existing items
        $deleteResult = $mysqli->common_delete('purchase_items', ['purchase_id' => $purchaseId]);
        if ($deleteResult['error']) {
            throw new Exception("Failed to clear old items: " . $deleteResult['error_msg']);
        }
        
        // Add new items (NO VAT here)
        foreach ($_POST['product_id'] as $index => $productId) {
            $itemData = [
                'purchase_id' => $purchaseId,
                'product_id' => $productId,
                'quantity' => $_POST['quantity'][$index],
                'unit_price' => $_POST['unit_price'][$index] // No VAT in item data
            ];
            
            $itemResult = $mysqli->common_insert('purchase_items', $itemData);
            if ($itemResult['error']) {
                throw new Exception("Failed to add item: " . $itemResult['error_msg']);
            }
        }
        
        setFlashMessage('Purchase updated successfully', 'success');
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
            <h4 class="page-title">Edit Purchase</h4>
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
                                                <option value="<?= $supplier->id ?>" <?= $purchase->supplier_id == $supplier->id ? 'selected' : '' ?>>
                                                    <?= $supplier->name ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="payment_method">Payment Method</label>
                                        <select class="form-control" id="payment_method" name="payment_method" required>
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
                                        <label for="payment_status">Payment Status</label>
                                        <select class="form-control" id="payment_status" name="payment_status" required>
                                            <option value="pending" <?= $purchase->payment_status == 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="partial" <?= $purchase->payment_status == 'partial' ? 'selected' : '' ?>>Partial</option>
                                            <option value="paid" <?= $purchase->payment_status == 'paid' ? 'selected' : '' ?>>Paid</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="discount">Discount (%)</label>
                                        <input type="number" class="form-control" id="discount" name="discount" min="0" max="100" value="<?= $purchase->discount ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="vat">VAT</label>
                                        <input type="number" class="form-control" id="vat" name="vat" min="0" value="<?= $purchase->vat ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Subtotal</label>
                                        <input type="text" class="form-control" id="subtotal" name="subtotal" readonly value="<?= $purchase->subtotal ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Total</label>
                                        <input type="text" class="form-control" id="total" name="total" readonly value="<?= $purchase->total ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Reference No</label>
                                        <input type="text" class="form-control" value="<?= $purchase->reference_no ?>" readonly>
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
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Total</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!$purchaseItems['error'] && !empty($purchaseItems['data'])): ?>
                                            <?php foreach ($purchaseItems['data'] as $item): 
                                                $product = $mysqli->common_select('products', 'name, barcode, price', ['id' => $item->product_id]);
                                                $productName = !$product['error'] && !empty($product['data']) ? 
                                                    $product['data'][0]->name . ' (' . $product['data'][0]->barcode . ')' : 'Product Not Found';
                                            ?>
                                                <tr>
                                                    <td>
                                                        <select class="form-control product-select" name="product_id[]" required>
                                                            <option value="">Select Product</option>
                                                            <?php foreach ($products['data'] as $product): ?>
                                                                <option value="<?= $product->id ?>" data-price="<?= $product->price ?>"
                                                                    <?= $item->product_id == $product->id ? 'selected' : '' ?>>
                                                                    <?= $product->name ?> (<?= $product->barcode ?>)
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </td>
                                                    <td><input type="number" class="form-control quantity" name="quantity[]" min="1" value="<?= $item->quantity ?>" required></td>
                                                    <td><input type="number" class="form-control unit-price" name="unit_price[]" min="0" step="0.01" value="<?= $item->unit_price ?>" required></td>
                                                    <td><input type="text" class="form-control total" readonly value="<?= number_format($item->quantity * $item->unit_price, 2) ?>"></td>
                                                    <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No items found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="5">
                                                <button type="button" class="btn btn-primary btn-sm" id="addRow">Add Item</button>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <div class="card-action">
                            <button type="submit" class="btn btn-success">Update Purchase</button>
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
                <td><input type="text" class="form-control total" readonly></td>
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
    
    // Calculate row total when quantity or price changes
    $(document).on('input', '.quantity, .unit-price', function() {
        calculateRowTotal($(this).closest('tr'));
    });
    
    // Calculate row total
    function calculateRowTotal(row) {
        const quantity = parseFloat(row.find('.quantity').val()) || 0;
        const unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
        const total = quantity * unitPrice;
        row.find('.total').val(total.toFixed(2));
        calculateTotals();
    }
    
    // Calculate all totals
    function calculateTotals() {
        let subtotal = 0;
        
        $('#purchaseItemsTable tbody tr').each(function() {
            const rowTotal = parseFloat($(this).find('.total').val()) || 0;
            subtotal += rowTotal;
        });
        
        const discount = parseFloat($('#discount').val()) || 0;
        const vat = parseFloat($('#vat').val()) || 0;
        const discountAmount = subtotal * (discount / 100);
        const total = (subtotal - discountAmount) + vat;
        
        $('#subtotal').val(subtotal.toFixed(2));
        $('#total').val(total.toFixed(2));
    }
    
    // Calculate totals when discount or VAT changes
    $('#discount, #vat').on('input', calculateTotals);
    
    // Initial calculation
    calculateTotals();
});
</script>