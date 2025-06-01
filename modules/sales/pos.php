<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}

require_once __DIR__ . '/../../db_plugin.php';
require_once __DIR__ . '/../../includes/functions.php';

$customers = $mysqli->common_select('customers', '*');
$products = $mysqli->common_select('products', 'id, name, barcode, sell_price', ['is_deleted' => 0]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $saleData = [
        'customer_id' => !empty($_POST['customer_id']) ? $_POST['customer_id'] : null,
        'customer_name' => !empty($_POST['customer_name']) ? $_POST['customer_name'] : null,
        'customer_email' => !empty($_POST['customer_email']) ? $_POST['customer_email'] : null,
        'invoice_no' => generateReferenceNo('INV'),
        'subtotal' => $_POST['subtotal'],
        'discount' => $_POST['discount'],
        'vat' => $_POST['vat'],
        'total' => $_POST['total'],
        'payment_status' => $_POST['payment_status'],
        'user_id' => $_SESSION['user']->id
    ];

    $saleResult = $mysqli->common_insert('sales', $saleData);
    
    if (!$saleResult['error']) {
        $saleId = $saleResult['data'];
        
        foreach ($_POST['product_id'] as $index => $productId) {
            $itemData = [
                'sale_id' => $saleId,
                'product_id' => $productId,
                'quantity' => $_POST['quantity'][$index],
                'unit_price' => $_POST['unit_price'][$index]
            ];
            
            $mysqli->common_insert('sale_items', $itemData);
        }
        
        if ($_POST['payment_status'] === 'paid' || $_POST['payment_status'] === 'partial') {
            $paymentData = [
                'customer_id' => $saleData['customer_id'],
                'sales_id' => $saleId,
                'type' => 'payment',
                'amount' => $_POST['amount_paid'],
                'payment_method' => $_POST['payment_method'],
                'description' => 'Payment for invoice #' . $saleData['invoice_no']
            ];
            
            $mysqli->common_insert('sales_payment', $paymentData);
        }
        
        // Print receipt or redirect
        if (isset($_POST['print_receipt'])) {
            header("Location: print_receipt.php?id=$saleId");
            exit;
        } else {
            setFlashMessage('Sale completed successfully. Invoice #' . $saleData['invoice_no'], 'success');
            header('Location: pos.php');
            exit;
        }
    } else {
        setFlashMessage('Error processing sale: ' . $saleResult['error_msg'], 'danger');
    }
}

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';
?>

<div class="main-panel">
    <div class="content">
        <div class="page-inner">
            <div class="page-header">
                <h4 class="page-title">Point of Sale</h4>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <form method="post" id="posForm">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="customer">Customer</label>
                                            <select class="form-control select2" id="customer_id" name="customer_id">
                                                <option value="">Walk-in Customer</option>
                                                <?php foreach ($customers['data'] as $customer): ?>
                                                    <option value="<?= $customer->id ?>">
                                                        <?= $customer->name ?> (<?= $customer->phone ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="payment_method">Payment Method</label>
                                            <select class="form-control" id="payment_method" name="payment_method" required>
                                                <option value="cash">Cash</option>
                                                <option value="card">Card</option>
                                                <option value="bank_transfer">Bank Transfer</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row customer-details" style="display: none;">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Customer Name</label>
                                            <input type="text" class="form-control" name="customer_name" id="customer_name">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Customer Phone</label>
                                            <input type="text" class="form-control" id="customer_phone" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Customer Email</label>
                                            <input type="email" class="form-control" name="customer_email" id="customer_email">
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Search Products</label>
                                            <input type="text" class="form-control" id="productSearch" placeholder="Scan barcode or search by name">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="cartTable">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Price</th>
                                                <th>Quantity</th>
                                                <th>Total</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Cart items will be added here by JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="discount">Discount (%)</label>
                                            <input type="number" class="form-control" id="discount" name="discount" min="0" max="100" value="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="vat">VAT</label>
                                            <input type="number" class="form-control" id="vat" name="vat" min="0" value="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="payment_status">Payment Status</label>
                                            <select class="form-control" id="payment_status" name="payment_status" required>
                                                <option value="paid">Paid</option>
                                                <option value="partial">Partial</option>
                                                <option value="pending">Pending</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row amount-paid-row" style="display: none;">
                                    <div class="col-md-4 offset-md-8">
                                        <div class="form-group">
                                            <label for="amount_paid">Amount Paid</label>
                                            <input type="number" class="form-control" id="amount_paid" name="amount_paid" min="0" step="0.01">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 offset-md-8">
                                        <table class="table">
                                            <tr>
                                                <th>Subtotal:</th>
                                                <td id="subtotalDisplay">0.00</td>
                                                <input type="hidden" id="subtotal" name="subtotal" value="0">
                                            </tr>
                                            <tr>
                                                <th>Discount:</th>
                                                <td id="discountDisplay">0.00</td>
                                            </tr>
                                            <tr>
                                                <th>VAT:</th>
                                                <td id="vatDisplay">0.00</td>
                                            </tr>
                                            <tr>
                                                <th>Total:</th>
                                                <td id="totalDisplay">0.00</td>
                                                <input type="hidden" id="total" name="total" value="0">
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="card-action">
                                <button type="submit" name="complete_sale" class="btn btn-success">
                                    <i class="fas fa-check"></i> Complete Sale
                                </button>
                                <button type="submit" name="print_receipt" class="btn btn-primary">
                                    <i class="fas fa-print"></i> Complete & Print
                                </button>
                                <button type="button" id="clearCart" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> Clear Cart
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize product search
    const products = <?= json_encode($products['data']) ?>;
    let cart = [];
    
    // Customer selection
    $('#customer_id').change(function() {
        const customerId = $(this).val();
        if (customerId) {
            $('.customer-details').show();
            // In a real app, you'd fetch customer details via AJAX
            // Here we'll just show the selected customer's info
            const selectedOption = $(this).find('option:selected');
            const customerName = selectedOption.text().split(' (')[0];
            $('#customer_name').val(customerName);
            $('#customer_phone').val(selectedOption.text().match(/\(([^)]+)\)/)[1]);
        } else {
            $('.customer-details').hide();
            $('#customer_name').val('');
            $('#customer_phone').val('');
            $('#customer_email').val('');
        }
    });
    
    // Payment status change
    $('#payment_status').change(function() {
        if ($(this).val() === 'partial') {
            $('.amount-paid-row').show();
        } else {
            $('.amount-paid-row').hide();
        }
    });
    
    // Product search
    $('#productSearch').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        if (searchTerm.length > 2) {
            // In a real app, you'd search via AJAX
            // Here we'll just filter the local products array
            const filteredProducts = products.filter(p => 
                p.name.toLowerCase().includes(searchTerm) || 
                p.barcode.toLowerCase().includes(searchTerm)
            );
            
            // Show search results (simplified for this example)
            if (filteredProducts.length > 0) {
                addToCart(filteredProducts[0].id);
                $(this).val('');
            }
        }
    });
    
    // Barcode scanning simulation
    $('#productSearch').keypress(function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            const barcode = $(this).val();
            const product = products.find(p => p.barcode === barcode);
            if (product) {
                addToCart(product.id);
                $(this).val('');
            }
        }
    });
    
    // Add product to cart
    function addToCart(productId) {
        const product = products.find(p => p.id == productId);
        if (!product) return;
        
        const existingItem = cart.find(item => item.product_id == productId);
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push({
                product_id: product.id,
                name: product.name,
                barcode: product.barcode,
                unit_price: product.sell_price,
                quantity: 1
            });
        }
        
        updateCartDisplay();
    }
    
    // Update cart display
    function updateCartDisplay() {
        const $tbody = $('#cartTable tbody');
        $tbody.empty();
        
        let subtotal = 0;
        
        cart.forEach((item, index) => {
            const total = item.unit_price * item.quantity;
            subtotal += total;
            
            $tbody.append(`
                <tr>
                    <td>
                        ${item.name} (${item.barcode})
                        <input type="hidden" name="product_id[]" value="${item.product_id}">
                    </td>
                    <td>
                        <input type="number" class="form-control unit-price" name="unit_price[]" 
                            value="${item.unit_price.toFixed(2)}" min="0" step="0.01" required>
                    </td>
                    <td>
                        <input type="number" class="form-control quantity" name="quantity[]" 
                            value="${item.quantity}" min="1" required>
                    </td>
                    <td>${total.toFixed(2)}</td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm remove-item" data-index="${index}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `);
        });
        
        if (cart.length === 0) {
            $tbody.append('<tr><td colspan="5" class="text-center">No items in cart</td></tr>');
        }
        
        calculateTotals(subtotal);
    }
    
    // Remove item from cart
    $(document).on('click', '.remove-item', function() {
        const index = $(this).data('index');
        cart.splice(index, 1);
        updateCartDisplay();
    });
    
    // Update quantity or price
    $(document).on('input', '.quantity, .unit-price', function() {
        const $row = $(this).closest('tr');
        const index = $row.find('.remove-item').data('index');
        const quantity = parseFloat($row.find('.quantity').val()) || 0;
        const unitPrice = parseFloat($row.find('.unit-price').val()) || 0;
        
        cart[index].quantity = quantity;
        cart[index].unit_price = unitPrice;
        
        updateCartDisplay();
    });
    
    // Calculate totals
    function calculateTotals(subtotal) {
        const discount = parseFloat($('#discount').val()) || 0;
        const vat = parseFloat($('#vat').val()) || 0;
        
        const discountAmount = subtotal * (discount / 100);
        const total = (subtotal - discountAmount) + vat;
        
        $('#subtotal').val(subtotal.toFixed(2));
        $('#subtotalDisplay').text(subtotal.toFixed(2));
        $('#discountDisplay').text(discountAmount.toFixed(2));
        $('#vatDisplay').text(vat.toFixed(2));
        $('#total').val(total.toFixed(2));
        $('#totalDisplay').text(total.toFixed(2));
        
        // Update amount paid field max value
        $('#amount_paid').attr('max', total);
    }
    
    // Recalculate when discount or VAT changes
    $('#discount, #vat').on('input', function() {
        const subtotal = cart.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0);
        calculateTotals(subtotal);
    });
    
    // Clear cart
    $('#clearCart').click(function() {
        cart = [];
        updateCartDisplay();
    });
});
</script>

<?php include __DIR__ . '/../../requires/footer.php'; ?>