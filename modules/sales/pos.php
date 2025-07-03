<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mysqli->begin_transaction();
    
    try {
        $customer_id = null;
        if (!empty($_POST['customer_name']) || !empty($_POST['customer_phone']) || !empty($_POST['customer_email'])) {
            // Check if customer exists by phone or email
            $where = [];
            if (!empty($_POST['customer_phone'])) {
                $where['phone'] = $_POST['customer_phone'];
            }
            if (!empty($_POST['customer_email'])) {
                $where['email'] = $_POST['customer_email'];
            }
            
            if (!empty($where)) {
                $customer_result = $mysqli->common_select('customers', 'id', $where, '', 'OR');
                
                if (!$customer_result['error'] && !empty($customer_result['data'])) {
                    $customer_id = $customer_result['data'][0]->id;
                }
            }
            
            if (!$customer_id) {
                $new_customer = [
                    'name' => $_POST['customer_name'] ?: 'Unknown Customer',
                    'phone' => $_POST['customer_phone'] ?? null,
                    'email' => $_POST['customer_email'] ?? null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => $_SESSION['user']->id
                ];
                
                $customer_result = $mysqli->common_insert('customers', $new_customer);
                if ($customer_result['error']) throw new Exception("Failed to create customer: " . $customer_result['error_msg']);
                
                $customer_id = $customer_result['data'];
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
            'user_id' => $_SESSION['user']->id,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $sale_result = $mysqli->common_insert('sales', $sale_data);
        if ($sale_result['error']) throw new Exception($sale_result['error_msg']);
        
        $sale_id = $sale_result['data'];
        
        foreach ($_POST['products'] as $product) {
            $item_data = [
                'sale_id' => $sale_id,
                'product_id' => $product['id'],
                'quantity' => $product['quantity'],
                'unit_price' => $product['price'],
                'total_price' => $product['total'],
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $_SESSION['user']->id
            ];
            
            $item_result = $mysqli->common_insert('sale_items', $item_data);
            if ($item_result['error']) throw new Exception($item_result['error_msg']);
            
            // FIFO Inventory
            $qtyToSell = $product['quantity'];
            $productId = $product['id'];

            // Get all available purchase batches (oldest first) with remaining qty
           $batch_query = "
                SELECT 
                    p.id, 
                    p.price, 
                    (p.qty + IFNULL((
                        SELECT SUM(s.qty) 
                        FROM stock s 
                        WHERE s.batch_id = p.id AND s.change_type = 'sales_return'
                    ), 0) - IFNULL((
                        SELECT ABS(SUM(s.qty)) 
                        FROM stock s 
                        WHERE s.batch_id = p.id AND s.change_type = 'sale'
                    ), 0)) AS remaining_qty
                FROM stock p
                WHERE p.product_id = ? 
                AND p.change_type = 'purchase'
                HAVING remaining_qty > 0
                ORDER BY p.created_at ASC, p.id ASC
            ";
            $stmt = $mysqli->getConnection()->prepare($batch_query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $mysqli->getConnection()->error);
            }
            $stmt->bind_param('i', $productId);
            $stmt->execute();
            $batches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $totalAvailableStock = array_sum(array_column($batches, 'remaining_qty'));

            // Debug stock calculation
            $log_data = [
                'user_id' => $_SESSION['user']->id,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'category' => 'stock',
                'message' => "Batch query for product ID: $productId, Query: $batch_query, Available: $totalAvailableStock, Requested: $qtyToSell, Batches: " . json_encode($batches),
                'created_at' => date('Y-m-d H:i:s')
            ];
            $mysqli->common_insert('system_logs', $log_data);

            if ($totalAvailableStock < $qtyToSell) {
                throw new Exception("Not enough stock for product ID: $productId. Available: $totalAvailableStock, Requested: $qtyToSell");
            }

            // Calculate discounted price per unit
            $subtotal = floatval($_POST['subtotal']);
            $discount = floatval($_POST['discount']);
            $discountedPricePerUnit = $subtotal > 0 ? ($product['total'] - ($product['total'] * $discount / $subtotal)) / $product['quantity'] : $product['price'];

            // Process FIFO deduction
            $remainingQty = $qtyToSell;

            foreach ($batches as $batch) {
                if ($remainingQty <= 0) break;

                $deductQty = min($remainingQty, $batch['remaining_qty']);
                $remainingQty -= $deductQty;

                // Insert stock record for this batch
                $stock_data = [
                    'product_id' => $productId,
                    'user_id' => $_SESSION['user']->id,
                    'change_type' => 'sale',
                    'qty' => -$deductQty,
                    'price' => $discountedPricePerUnit,
                    'sale_id' => $sale_id,
                    'batch_id' => $batch['id'],
                    'note' => 'POS Sale (FIFO)',
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => $_SESSION['user']->id
                ];

                $stock_result = $mysqli->common_insert('stock', $stock_data);
                if ($stock_result['error']) throw new Exception($stock_result['error_msg']);

                // Store batch assignment in sale_items_batches table
                $batch_assignment = [
                    'sale_item_id' => $item_result['data'], // The sale_items record ID
                    'batch_id' => $batch['id'], // The purchase batch ID
                    'quantity' => $deductQty,
                    'unit_price' => $discountedPricePerUnit,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $mysqli->common_insert('sale_items_batches', $batch_assignment);
            }

            if ($remainingQty > 0) {
                throw new Exception("Insufficient stock allocation for product ID: $productId. Remaining: $remainingQty");
            }
        }
        
        if ($_POST['payment_status'] == 'paid' || $_POST['payment_status'] == 'partial') {
            $payment_data = [
                'customer_id' => $customer_id,
                'sales_id' => $sale_id,
                'type' => 'payment',
                'amount' => $_POST['amount_paid'],
                'payment_method' => $_POST['payment_method'],
                'description' => 'Payment for invoice #' . $sale_data['invoice_no'],
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $_SESSION['user']->id
            ];
            
            $payment_result = $mysqli->common_insert('sales_payment', $payment_data);
            if ($payment_result['error']) throw new Exception($payment_result['error_msg']);
        }
        
        $mysqli->commit();
        
        // Redirect to invoice or print
        if (isset($_POST['print_invoice'])) {
            $_SESSION['print_invoice'] = $sale_id;
            $_SESSION['success'] = "Sale completed successfully! Invoice #" . $sale_data['invoice_no'];
            header("Location: print_invoice.php");
            exit();
        } elseif (isset($_POST['save_sale'])) {
            $_SESSION['success'] = "Sale completed successfully! Invoice #" . $sale_data['invoice_no'];
            header("Location: view_sales.php");
            exit();
        }
    } catch (Exception $e) {
        $mysqli->rollback();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

$customers = $mysqli->common_select('customers', 'id, name, phone, email', [], 'name')['data'];
$products = $mysqli->common_select('products', '*', ['is_deleted' => 0])['data'];

// Calculate available stock for each product
$products_with_stock = array_map(function($p) use ($mysqli) {
    $stock_query = "SELECT COALESCE(SUM(qty), 0) as stock FROM stock WHERE product_id = ?";
    $stmt = $mysqli->getConnection()->prepare($stock_query);
    $stmt->bind_param('i', $p->id);
    $stmt->execute();
    $stock = $stmt->get_result()->fetch_object()->stock;
    
    return [
        'id' => $p->id, 
        'name' => $p->name, 
        'barcode' => $p->barcode, 
        'price' => (float)$p->sell_price,
        'stock' => (int)$stock
    ];
}, $products);

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/sidebar.php';
require_once __DIR__ . '/../../requires/topbar.php';
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
                                        <label>Customer Name</label>
                                        <div class="customer-search-container">
                                            <input type="text" class="form-control" id="customerSearch" name="customer_name" placeholder="Search customer by name, number, email..." autocomplete="off">
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
const products = <?= json_encode($products_with_stock) ?>;
const customers = <?= json_encode($customers) ?>;

$(document).ready(function() {

    $('#posTbody').before('<div class="alert alert-danger alert-empty-cart">Please add at least one product to complete the sale.</div>');
    
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
                (c.phone && c.phone.includes(searchTerm)) || 
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
        
        // If customer has no name
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
    
    updateTotals();
});
</script>