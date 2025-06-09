<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';

// Handle POS sale
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mysqli->begin_transaction();
    
    try {
        // Handle customer creation/selection
        $customer_id = null;
        if (!empty($_POST['customer_name'])) {
            // Check if customer exists
            $customer_result = $mysqli->common_select('customers', 'id', ['name' => $_POST['customer_name']]);
            
            if ($customer_result['error'] || empty($customer_result['data'])) {
                // Create new customer if not exists
                $new_customer = [
                    'name' => $_POST['customer_name'],
                    'phone' => $_POST['customer_phone'] ?? null,
                    'email' => $_POST['customer_email'] ?? null
                ];
                
                $customer_result = $mysqli->common_insert('customers', $new_customer);
                if ($customer_result['error']) throw new Exception("Failed to create customer: " . $customer_result['error_msg']);
                
                $customer_id = $customer_result['data'];
            } else {
                $customer_id = $customer_result['data'][0]->id;
            }
        }

        // Create sale record
        $sale_data = [
            'customer_id' => $customer_id,
            'customer_name' => $_POST['customer_name'] ?: null,
            'customer_email' => $_POST['customer_email'] ?: null,
            'phone' => $_POST['customer_phone'] ?: null,
            'invoice_no' => 'INV-' . strtoupper(uniqid()),
            'subtotal' => $_POST['subtotal'],
            'vat' => $_POST['vat'],
            'discount' => $_POST['discount'],
            'total' => $_POST['total'],
            'payment_status' => $_POST['payment_status'],
            'user_id' => $_SESSION['user']->id
        ];
        
        $sale_result = $mysqli->common_insert('sales', $sale_data);
        if ($sale_result['error']) throw new Exception($sale_result['error_msg']);
        
        $sale_id = $sale_result['data'];
        
        // Insert sale items
        foreach ($_POST['products'] as $product) {
            $item_data = [
                'sale_id' => $sale_id,
                'product_id' => $product['id'],
                'quantity' => $product['quantity'],
                'unit_price' => $product['price'],
                'total_price' => $product['total']
            ];
            
            $item_result = $mysqli->common_insert('sale_items', $item_data);
            if ($item_result['error']) throw new Exception($item_result['error_msg']);
            
            // Update stock
            $stock_data = [
                'product_id' => $product['id'],
                'user_id' => $_SESSION['user']->id,
                'change_type' => 'sale',
                'qty' => -$product['quantity'], // Negative for sales
                'price' => $product['price'],
                'sale_id' => $sale_id,
                'note' => 'POS sale'
            ];
            
            $stock_result = $mysqli->common_insert('stock', $stock_data);
            if ($stock_result['error']) throw new Exception($stock_result['error_msg']);
        }
        
        // Insert payment if paid
        if ($_POST['payment_status'] == 'paid' || $_POST['payment_status'] == 'partial') {
            $payment_data = [
                'customer_id' => $customer_id,
                'sales_id' => $sale_id,
                'type' => 'payment',
                'amount' => $_POST['amount_paid'],
                'payment_method' => $_POST['payment_method'],
                'description' => 'Payment for invoice #' . $sale_data['invoice_no']
            ];
            
            $payment_result = $mysqli->common_insert('sales_payment', $payment_data);
            if ($payment_result['error']) throw new Exception($payment_result['error_msg']);
        }
        
        $mysqli->commit();
        
        // Redirect to invoice or print
        if (isset($_POST['print_invoice'])) {
            $_SESSION['print_invoice'] = $sale_id;
            header("Location: print_invoice.php");
        } else {
            $_SESSION['success'] = "Sale completed successfully! Invoice #" . $sale_data['invoice_no'];
            header("Location: view_sales.php");
        }
        exit();
    } catch (Exception $e) {
        $mysqli->rollback();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Get customers and products
$customers = $mysqli->common_select('customers', 'id, name, phone, email', [], 'name')['data'];
$products = $mysqli->common_select('products', '*', ['is_deleted' => 0])['data'];

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';

$mysqli_connection = $mysqli;
$products_with_stock = array_map(function($p) use ($mysqli_connection) {
    // Get current stock for each product
    $stock_query = "SELECT COALESCE(SUM(qty), 0) as stock FROM stock WHERE product_id = ?";
    $stmt = $mysqli_connection->getConnection()->prepare($stock_query);
    $stmt->bind_param('i', $p->id);
    $stmt->execute();
    $stock = $stmt->get_result()->fetch_object()->stock;
    
    return [
        'id' => $p->id, 
        'name' => $p->name, 
        'barcode' => $p->barcode, 
        'price' => (float)$p->sell_price, // Cast to float to ensure it's a number
        'stock' => (int)$stock
    ];
}, $products);
?>

<style>
.search-container {
    position: relative;
}

.search-results {
    position: absolute;
    width: 100%;
    max-height: 300px;
    overflow-y: auto;
    background: white;
    border: 1px solid #ddd;
    border-radius: 0 0 4px 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    z-index: 1000;
    display: none;
}

.search-item {
    padding: 10px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.search-item:hover {
    background-color: #f5f5f5;
}

.search-item .product-info {
    flex: 1;
}

.search-item .stock-info {
    text-align: right;
    margin-left: 15px;
}

.stock-badge {
    padding: 3px 6px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
}

.stock-in {
    background-color: #28a745;
    color: white;
}

.stock-low {
    background-color: #ffc107;
    color: #212529;
}

.stock-out {
    background-color: #dc3545;
    color: white;
}

#posTable tbody tr td {
    vertical-align: middle;
}

#posTable .product-cell {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

#posTable .stock-indicator {
    font-size: 12px;
    margin-left: 10px;
}

.alert-empty-cart {
    display: none;
    margin-bottom: 20px;
}

.customer-search-container {
    position: relative;
}

.customer-results {
    position: absolute;
    width: 100%;
    max-height: 200px;
    overflow-y: auto;
    background: white;
    border: 1px solid #ddd;
    border-radius: 0 0 4px 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    z-index: 1000;
    display: none;
}

.customer-item {
    padding: 8px 10px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
}

.customer-item:hover {
    background-color: #f5f5f5;
}

.customer-item .customer-name {
    font-weight: bold;
}

.customer-item .customer-details {
    font-size: 12px;
    color: #666;
}
.customer-item .badge {
    font-size: 10px;
    padding: 3px 5px;
}

.customer-details i {
    width: 16px;
    text-align: center;
    margin-right: 3px;
    color: #6c757d;
}
</style>

<div class="container">
    <div class="page-inner">
        <div class="row">
            <div class="col-md-12 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Point of Sale</h4>
                        
                        <form id="posForm" method="POST">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Customer</label>
                                        <div class="customer-search-container">
                                            <input type="text" class="form-control" id="customerSearch" name="customer_name" placeholder="Search customer by name" autocomplete="off">
                                            <div id="customerResults" class="customer-results"></div>
                                        </div>
                                        <input type="hidden" id="customerId" name="customer_id">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Customer Phone</label>
                                        <input type="text" class="form-control" id="customerPhone" name="customer_phone" placeholder="Customer phone">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Customer Email</label>
                                        <input type="email" class="form-control" id="customerEmail" name="customer_email" placeholder="Customer email">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label>Search Product</label>
                                        <div class="search-container">
                                            <input type="text" class="form-control" id="productSearch" placeholder="Scan barcode or search product" autofocus>
                                            <div id="searchResults" class="search-results"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Date</label>
                                        <input type="date" class="form-control" name="sale_date" value="<?= date('Y-m-d') ?>" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive mb-4">
                                <table class="table table-bordered" id="posTable">
                                    <thead>
                                        <tr>
                                            <th width="40%">Product</th>
                                            <th width="15%">Price</th>
                                            <th width="15%">Qty</th>
                                            <th width="20%">Total</th>
                                            <th width="10%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="posTbody">
                                        <!-- Items will be added here -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-right"><strong>Subtotal</strong></td>
                                            <td><input type="text" class="form-control" id="subtotal" name="subtotal" value="0.00" readonly></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-right"><strong>Discount</strong></td>
                                            <td>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="discountPercent" value="0" min="0" max="100">
                                                    <span class="input-group-text">%</span>
                                                    <input type="hidden" id="discount" name="discount" value="0">
                                                </div>
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-right"><strong>VAT</strong></td>
                                            <td>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="vatPercent" value="0" min="0" max="100">
                                                    <span class="input-group-text">%</span>
                                                    <input type="hidden" id="vat" name="vat" value="0">
                                                </div>
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-right"><strong>Total</strong></td>
                                            <td><input type="text" class="form-control" id="total" name="total" value="0.00" readonly></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Payment Method *</label>
                                        <select class="form-control" name="payment_method" required>
                                            <option value="cash">Cash</option>
                                            <option value="card">Card</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Payment Status *</label>
                                        <select class="form-control" name="payment_status" id="paymentStatus" required>
                                            <option value="paid">Paid</option>
                                            <option value="partial">Partial</option>
                                            <option value="pending">Pending</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4" id="amountPaidField">
                                    <div class="form-group">
                                        <label>Amount Paid</label>
                                        <input type="number" class="form-control" name="amount_paid" id="amountPaid" step="0.01" min="0" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" name="save_sale" class="btn btn-success">Complete Sale</button>
                                <button type="submit" name="print_invoice" class="btn btn-primary">Save & Print Invoice</button>
                                <button type="button" id="clearCart" class="btn btn-danger">Clear Cart</button>
                                <button type="button" id="newCustomerBtn" class="btn btn-info">New Customer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../requires/footer.php'; ?>

<script>
// Product data with stock information
const products = <?= json_encode($products_with_stock) ?>;
const customers = <?= json_encode($customers) ?>;

$(document).ready(function() {
    // Add alert for empty cart
    $('#posTbody').before('<div class="alert alert-danger alert-empty-cart">Please add at least one product to complete the sale.</div>');
    
    // Customer search handler - now searches name, phone, and email
    $('#customerSearch').on('input', function() {
        const searchTerm = $(this).val().trim();
        const resultsContainer = $('#customerResults');
        
        if (searchTerm.length < 2) {
            resultsContainer.hide().empty();
            return;
        }
        
        // Search in name, phone, or email (case insensitive)
        const matchedCustomers = customers.filter(c => {
            const searchLower = searchTerm.toLowerCase();
            return (
                (c.name && c.name.toLowerCase().includes(searchLower)) ||
                (c.phone && c.phone.includes(searchTerm)) || // Phone numbers often have + or other chars
                (c.email && c.email.toLowerCase().includes(searchLower))
            );
        }).slice(0, 10); // Limit to 10 results
        
        if (matchedCustomers.length === 0) {
            resultsContainer.html('<div class="customer-item">No customers found</div>').show();
            return;
        }
        
        let resultsHtml = '';
        matchedCustomers.forEach(customer => {
            // Highlight which field matched
            let matchedField = '';
            if (customer.name && customer.name.toLowerCase().includes(searchTerm.toLowerCase())) {
                matchedField = 'Name';
            } else if (customer.phone && customer.phone.includes(searchTerm)) {
                matchedField = 'Phone';
            } else if (customer.email && customer.email.toLowerCase().includes(searchTerm.toLowerCase())) {
                matchedField = 'Email';
            }
            
            resultsHtml += `
                <div class="customer-item" 
                     data-id="${customer.id}" 
                     data-name="${customer.name || ''}" 
                     data-phone="${customer.phone || ''}" 
                     data-email="${customer.email || ''}">
                    <div class="customer-name">
                        ${customer.name || 'No name provided'}
                        ${matchedField ? `<span class="badge badge-info ml-2">${matchedField}</span>` : ''}
                    </div>
                    <div class="customer-details">
                        ${customer.phone ? '<i class="fas fa-phone"></i> ' + customer.phone + ' | ' : ''}
                        ${customer.email ? '<i class="fas fa-envelope"></i> ' + customer.email : ''}
                        ${!customer.phone && !customer.email ? 'No contact details' : ''}
                    </div>
                </div>
            `;
        });
        
        resultsContainer.html(resultsHtml).show();
    });
    
    // Handle customer selection
    $(document).on('click', '.customer-item', function() {
        const customerId = $(this).data('id');
        const customerName = $(this).data('name');
        const customerPhone = $(this).data('phone');
        const customerEmail = $(this).data('email');
        
        // If customer has no name but we found by phone/email, prompt for name
        if (!customerName) {
            const enteredName = prompt("Customer found by contact details. Please enter customer name:");
            if (enteredName) {
                $('#customerSearch').val(enteredName);
                $('#customerPhone').val(customerPhone);
                $('#customerEmail').val(customerEmail);
                $('#customerResults').hide().empty();
                return;
            }
        }
        
        $('#customerSearch').val(customerName || '');
        $('#customerId').val(customerId);
        $('#customerPhone').val(customerPhone || '');
        $('#customerEmail').val(customerEmail || '');
        
        $('#customerResults').hide().empty();
    });
    
    // New customer button
    $('#newCustomerBtn').click(function() {
        $('#customerSearch').val('');
        $('#customerId').val('');
        $('#customerPhone').val('');
        $('#customerEmail').val('');
        $('#customerSearch').focus();
    });
    
    // Product search handler with suggestions
    $('#productSearch').on('input', function() {
        const searchTerm = $(this).val().toLowerCase().trim();
        const resultsContainer = $('#searchResults');
        
        if (searchTerm.length < 2) {
            resultsContainer.hide().empty();
            return;
        }
        
        const matchedProducts = products.filter(p => 
            p.name.toLowerCase().includes(searchTerm) || 
            p.barcode.toLowerCase().includes(searchTerm)
        ).slice(0, 10); // Limit to 10 results
        
        if (matchedProducts.length === 0) {
            resultsContainer.html('<div class="search-item">No products found</div>').show();
            return;
        }
        
        let resultsHtml = '';
        matchedProducts.forEach(product => {
            // Determine stock status
            let stockClass, stockText;
            if (product.stock <= 0) {
                stockClass = 'stock-out';
                stockText = 'Out of stock';
            } else if (product.stock < 5) {
                stockClass = 'stock-low';
                stockText = `Low stock (${product.stock})`;
            } else {
                stockClass = 'stock-in';
                stockText = `In stock (${product.stock})`;
            }
            
            resultsHtml += `
                <div class="search-item" data-id="${product.id}" ${product.stock <= 0 ? 'style="opacity:0.6"' : ''}>
                    <div class="product-info">
                        <strong>${product.name}</strong>
                        <div class="text-muted small">${product.barcode}</div>
                        <div>${product.price.toFixed(2)}</div>
                    </div>
                    <div class="stock-info">
                        <span class="stock-badge ${stockClass}">${stockText}</span>
                    </div>
                </div>
            `;
        });
        
        resultsContainer.html(resultsHtml).show();
        
        // Handle click on search result
        $('.search-item').click(function() {
            const productId = $(this).data('id');
            const product = products.find(p => p.id == productId);
            
            if (product.stock <= 0) {
                alert('This product is out of stock and cannot be added to cart');
                return;
            }
            
            // Check if product already exists in cart
            const existingRow = $(`#posTbody tr input[name*="[${productId}]"]`).closest('tr');
            if (existingRow.length) {
                // Increment quantity if product exists
                const qtyInput = existingRow.find('.quantity');
                qtyInput.val(parseInt(qtyInput.val()) + 1).trigger('change');
            } else {
                // Add new product to cart
                addProductToCart(product);
            }
            
            // Clear search and hide results
            $('#productSearch').val('').focus();
            resultsContainer.hide().empty();
        });
    });
    
    // Hide search results when clicking elsewhere
    $(document).click(function(e) {
        if (!$(e.target).closest('.search-container').length) {
            $('#searchResults').hide();
        }
        if (!$(e.target).closest('.customer-search-container').length) {
            $('#customerResults').hide();
        }
    });
    
    // Payment status handler
    $('#paymentStatus').change(function() {
        if ($(this).val() === 'paid') {
            $('#amountPaid').val($('#total').val());
        }
    });
    
    // Enhanced add product to cart function
    function addProductToCart(product) {
        const rowId = Date.now();
        const maxQty = product.stock > 0 ? product.stock : 1;
        
        // Determine stock indicator
        let stockIndicator = '';
        if (product.stock <= 0) {
            stockIndicator = '<span class="stock-indicator stock-out">Out of stock</span>';
        } else if (product.stock < 5) {
            stockIndicator = `<span class="stock-indicator stock-low">Only ${product.stock} left</span>`;
        }
        
        const row = `
            <tr id="row${rowId}">
                <td>
                    <div class="product-cell">
                        <div>
                            ${product.name} (${product.barcode})
                            ${stockIndicator}
                        </div>
                        <input type="hidden" name="products[${rowId}][id]" value="${product.id}">
                    </div>
                </td>
                <td><input type="number" class="form-control price" name="products[${rowId}][price]" 
                    value="${product.price}" step="0.01" min="0" required></td>
                <td><input type="number" class="form-control quantity" name="products[${rowId}][quantity]" 
                    value="1" min="1" max="${maxQty}" required></td>
                <td><input type="text" class="form-control total" name="products[${rowId}][total]" 
                    value="${product.price}" readonly></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-item" data-row="${rowId}">
                    <i class="fas fa-trash"></i>
                </button></td>
            </tr>
        `;
        $('#posTbody').append(row);
        $('.alert-empty-cart').hide();
        
        // Set up event handlers for the new row
        $(`#row${rowId} .quantity, #row${rowId} .price`).change(function() {
            updateRowTotal(rowId);
            updateTotals();
        });
        
        $(`#row${rowId} .remove-item`).click(function() {
            $(`#row${rowId}`).remove();
            updateTotals();
            if ($('#posTbody tr').length === 0) {
                $('.alert-empty-cart').show();
            }
        });
        
        updateTotals();
        $('#productSearch').focus();
    }
    
    // Update row total
    function updateRowTotal(rowId) {
        const price = parseFloat($(`#row${rowId} .price`).val()) || 0;
        const qty = parseInt($(`#row${rowId} .quantity`).val()) || 0;
        const total = price * qty;
        $(`#row${rowId} .total`).val(total.toFixed(2));
    }
    
    // Update all totals
    function updateTotals() {
        let subtotal = 0;
        
        $('#posTbody tr').each(function() {
            subtotal += parseFloat($(this).find('.total').val()) || 0;
        });
        
        const discountPercent = parseFloat($('#discountPercent').val()) || 0;
        const discountAmount = subtotal * discountPercent / 100;
        
        const vatPercent = parseFloat($('#vatPercent').val()) || 0;
        const vatAmount = (subtotal - discountAmount) * vatPercent / 100;
        
        const total = subtotal - discountAmount + vatAmount;
        
        $('#subtotal').val(subtotal.toFixed(2));
        $('#discount').val(discountAmount.toFixed(2));
        $('#vat').val(vatAmount.toFixed(2));
        $('#total').val(total.toFixed(2));
        
        // Auto-set paid amount if status is paid
        if ($('#paymentStatus').val() === 'paid') {
            $('#amountPaid').val(total.toFixed(2));
        }
    }
    
    $('#posForm').submit(function(e) {
        if ($('#posTbody tr').length === 0) {
            e.preventDefault();
            $('.alert-empty-cart').show();
            $('#productSearch').focus();
            return false;
        }
        
        // Additional validation if needed
        return true;
    });
    
    // Discount and VAT handlers
    $('#discountPercent, #vatPercent').change(function() {
        updateTotals();
    });
    
    // Clear cart
    $('#clearCart').click(function() {
        if (confirm('Are you sure you want to clear the cart?')) {
            $('#posTbody').empty();
            updateTotals();
            $('.alert-empty-cart').show();
        }
    });
    
    // Keyboard navigation for search results
    $('#productSearch').keydown(function(e) {
        if (e.keyCode === 13) { // Enter key
            e.preventDefault();
            const firstItem = $('#searchResults .search-item').first();
            if (firstItem.length) {
                firstItem.click();
            }
        }
        
        if (e.keyCode === 40) { // Down arrow
            e.preventDefault();
            const firstItem = $('#searchResults .search-item').first();
            if (firstItem.length) {
                firstItem.focus().addClass('active');
            }
        }
    });
    
    // Handle arrow keys in search results
    $(document).on('keydown', '.search-item', function(e) {
        if (e.keyCode === 40) { // Down arrow
            e.preventDefault();
            $(this).next('.search-item').focus().addClass('active');
            $(this).removeClass('active');
        } else if (e.keyCode === 38) { // Up arrow
            e.preventDefault();
            $(this).prev('.search-item').focus().addClass('active');
            $(this).removeClass('active');
        } else if (e.keyCode === 13) { // Enter key
            e.preventDefault();
            $(this).click();
        }
    });
    
    // Initialize
    updateTotals();
});
</script>