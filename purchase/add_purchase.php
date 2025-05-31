<?php
session_start();

if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}

require_once __DIR__ . '/../../db_plugin.php';

$error = '';
$success = '';
require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';
?>
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Add New Purchase</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="purchases.php">Purchases</a></li>
                            <li class="breadcrumb-item active">Add Purchase</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Purchase Information</h3>
                            </div>
                            <form id="purchaseForm" method="post">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="supplier_id">Supplier <span class="text-danger">*</span></label>
                                                <select class="form-control" id="supplier_id" name="supplier_id" required>
                                                    <option value="">Select Supplier</option>
                                                    <?php foreach ($suppliers['data'] as $supplier): ?>
                                                        <option value="<?= $supplier->id ?>"><?= htmlspecialchars($supplier->name) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="invalid-feedback">Please select a supplier</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="reference_no">Reference No</label>
                                                <input type="text" class="form-control" id="reference_no" name="reference_no" placeholder="Enter reference number">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="date">Date <span class="text-danger">*</span></label>
                                                <input type="datetime-local" class="form-control" id="date" name="date" value="<?= date('Y-m-d\TH:i') ?>" required>
                                                <div class="invalid-feedback">Please select a date</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <h4>Products</h4>
                                            <div id="productContainer">
                                                <!-- Product rows will be added here -->
                                            </div>
                                            <button type="button" class="btn btn-success btn-sm" id="addProduct">
                                                <i class="fas fa-plus"></i> Add Product
                                            </button>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-md-8">
                                            <div class="form-group">
                                                <label for="remarks">Remarks</label>
                                                <textarea class="form-control" id="remarks" name="remarks" rows="2" placeholder="Enter any remarks"></textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="subtotal">Subtotal</label>
                                                <input type="number" class="form-control" id="subtotal" name="subtotal" readonly value="0.00" step="0.01">
                                            </div>
                                            <div class="form-group">
                                                <label for="discount">Discount (%)</label>
                                                <input type="number" class="form-control" id="discount" name="discount" value="0" min="0" max="100" step="0.01">
                                            </div>
                                            <div class="form-group">
                                                <label for="tax">Tax (VAT)</label>
                                                <input type="number" class="form-control" id="tax" name="tax" value="0.00" min="0" step="0.01">
                                            </div>
                                            <div class="form-group">
                                                <label for="total">Total</label>
                                                <input type="number" class="form-control" id="total" name="total" readonly value="0.00" step="0.01">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">Save Purchase</button>
                                    <a href="purchases.php" class="btn btn-default float-right">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>


<?php require_once __DIR__ . '/../../requires/footer.php'; ?>
</div>

<!-- Product Row Template (hidden) -->
<div id="productRowTemplate" style="display: none;">
    <div class="product-row" data-id="__ID__">
        <div class="row">
            <div class="col-md-5">
                <div class="form-group">
                    <label>Product <span class="text-danger">*</span></label>
                    <div class="product-search-container">
                        <input type="text" class="form-control product-search" placeholder="Search product..." required>
                        <input type="hidden" class="product-id" name="products[__ID__][product_id]">
                        <div class="search-results"></div>
                        <div class="invalid-feedback">Please select a product</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Quantity <span class="text-danger">*</span></label>
                    <input type="number" class="form-control product-qty" name="products[__ID__][quantity]" min="1" value="1" required>
                    <div class="invalid-feedback">Please enter a valid quantity</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Unit Price <span class="text-danger">*</span></label>
                    <input type="number" class="form-control product-price" name="products[__ID__][unit_price]" min="0.01" step="0.01" required>
                    <div class="invalid-feedback">Please enter a valid price</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Total</label>
                    <input type="number" class="form-control product-total" readonly>
                </div>
            </div>
            <div class="col-md-1 product-actions">
                <button type="button" class="btn btn-danger btn-sm remove-product" title="Remove">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/scripts.php'; ?>
<script>
$(document).ready(function() {
    let productCounter = 0;
    const addedProductIds = new Set();
    
    // Add first product row
    addProductRow();
    
    // Add product row
    $('#addProduct').click(function() {
        addProductRow();
    });
    
    // Remove product row
    $(document).on('click', '.remove-product', function() {
        const row = $(this).closest('.product-row');
        const productId = row.find('.product-id').val();
        
        if (productId) {
            addedProductIds.delete(parseInt(productId));
        }
        
        row.remove();
        calculateTotals();
    });
    
    // Product search
    $(document).on('input', '.product-search', function() {
        const searchTerm = $(this).val();
        const searchResults = $(this).siblings('.search-results');
        
        if (searchTerm.length < 2) {
            searchResults.hide().empty();
            return;
        }
        
        $.ajax({
            url: 'ajax_search_products.php',
            method: 'GET',
            data: { term: searchTerm, exclude: Array.from(addedProductIds) },
            dataType: 'json',
            success: function(data) {
                if (data.error) {
                    toastr.error(data.error_msg);
                    return;
                }
                
                searchResults.empty();
                
                if (data.data.length === 0) {
                    searchResults.append('<div class="search-item">No products found</div>');
                } else {
                    data.data.forEach(function(product) {
                        searchResults.append(
                            `<div class="search-item" data-id="${product.id}" data-name="${product.name}" data-price="${product.price}">
                                ${product.name} (${product.barcode}) - ${product.price}
                            </div>`
                        );
                    });
                }
                
                searchResults.show();
            }
        });
    });
    
    // Select product from search results
    $(document).on('click', '.search-item', function() {
        const row = $(this).closest('.product-row');
        const productId = $(this).data('id');
        const productName = $(this).data('name');
        const productPrice = $(this).data('price');
        
        // Update row
        row.find('.product-search').val(productName);
        row.find('.product-id').val(productId);
        row.find('.product-price').val(productPrice);
        
        // Add to set of added products
        addedProductIds.add(parseInt(productId));
        
        // Hide search results
        row.find('.search-results').hide().empty();
        
        // Calculate row total
        calculateRowTotal(row);
    });
    
    // Calculate row total when quantity or price changes
    $(document).on('input', '.product-qty, .product-price', function() {
        const row = $(this).closest('.product-row');
        calculateRowTotal(row);
    });
    
    // Calculate discount/tax changes
    $(document).on('input', '#discount, #tax', calculateTotals);
    
    // Form submission
    $('#purchaseForm').submit(function(e) {
        e.preventDefault();
        
        // Validate form
        let isValid = true;
        
        // Validate required fields
        $('#supplier_id, #date').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        // Validate product rows
        $('.product-row').each(function() {
            const row = $(this);
            if (!row.find('.product-id').val()) {
                row.find('.product-search').addClass('is-invalid');
                isValid = false;
            } else {
                row.find('.product-search').removeClass('is-invalid');
            }
            
            if (!row.find('.product-qty').val() || parseInt(row.find('.product-qty').val()) <= 0) {
                row.find('.product-qty').addClass('is-invalid');
                isValid = false;
            } else {
                row.find('.product-qty').removeClass('is-invalid');
            }
            
            if (!row.find('.product-price').val() || parseFloat(row.find('.product-price').val()) <= 0) {
                row.find('.product-price').addClass('is-invalid');
                isValid = false;
            } else {
                row.find('.product-price').removeClass('is-invalid');
            }
        });
        
        if (!isValid) {
            toastr.error('Please fill all required fields with valid values');
            return;
        }
        
        // Prepare form data
        const formData = new FormData(this);
        formData.append('user_id', <?= $_SESSION['user_id'] ?? 0 ?>);
        
        // Submit via AJAX
        $.ajax({
            url: 'ajax_add_purchase.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    toastr.error(response.error_msg);
                } else {
                    toastr.success('Purchase added successfully');
                    setTimeout(() => {
                        window.location.href = 'purchases.php';
                    }, 1500);
                }
            },
            error: function(xhr, status, error) {
                toastr.error('An error occurred: ' + error);
            }
        });
    });
    
    // Hide search results when clicking elsewhere
    $(document).click(function(e) {
        if (!$(e.target).closest('.product-search-container').length) {
            $('.search-results').hide();
        }
    });
    
    // Functions
    function addProductRow() {
        productCounter++;
        const newRow = $('#productRowTemplate').html().replace(/__ID__/g, productCounter);
        $('#productContainer').append(newRow);
    }
    
    function calculateRowTotal(row) {
        const qty = parseFloat(row.find('.product-qty').val()) || 0;
        const price = parseFloat(row.find('.product-price').val()) || 0;
        const total = qty * price;
        row.find('.product-total').val(total.toFixed(2));
        calculateTotals();
    }
    
    function calculateTotals() {
        let subtotal = 0;
        
        $('.product-row').each(function() {
            const total = parseFloat($(this).find('.product-total').val()) || 0;
            subtotal += total;
        });
        
        const discount = parseFloat($('#discount').val()) || 0;
        const tax = parseFloat($('#tax').val()) || 0;
        
        const discountAmount = subtotal * (discount / 100);
        const total = subtotal - discountAmount + tax;
        
        $('#subtotal').val(subtotal.toFixed(2));
        $('#total').val(total.toFixed(2));
    }
});
</script>