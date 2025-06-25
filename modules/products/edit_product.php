<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}

require_once __DIR__ . '/../../db_plugin.php'; 

// Initialize variables
$error = '';
$success = '';
$product = null;

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Load product data
if ($product_id > 0) {
    $result = $mysqli->common_select('products', '*', ['id' => $product_id]);
    if (!$result['error'] && !empty($result['data'])) {
        $product = $result['data'][0];
    } else {
        $error = 'Product not found';
        header("Location: view_product.php");
        exit();
    }
} else {
    header("Location: edit_product.php");
    exit();
}

// Load dropdown options
$categories = [];
$sub_categories = [];
$child_categories = [];
$brands = [];

// Get categories
$result = $mysqli->common_select('category', '*', ['is_deleted' => 0], 'category', 'asc');
if (!$result['error']) $categories = $result['data'];

// Get sub-categories if category is selected
if ($product->category_id) {
    $result = $mysqli->common_select('sub_category', '*', ['category_id' => $product->category_id, 'is_deleted' => 0], 'category_name', 'asc');
    if (!$result['error']) $sub_categories = $result['data'];
}

// Get child-categories if sub-category is selected
if ($product->sub_category_id) {
    $result = $mysqli->common_select('child_category', '*', ['sub_category_id' => $product->sub_category_id, 'is_deleted' => 0], 'category_name', 'asc');
    if (!$result['error']) $child_categories = $result['data'];
}

// Get brands
$result = $mysqli->common_select('brand', '*', ['is_deleted' => 0], 'brand_name', 'asc');
if (!$result['error']) $brands = $result['data'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $update_data = [
        'name' => trim($_POST['name'] ?? ''),
        'barcode' => trim($_POST['barcode'] ?? ''),
        'category_id' => (int)($_POST['category_id'] ?? 0),
        'sub_category_id' => (int)($_POST['sub_category_id'] ?? 0),
        'child_category_id' => (int)($_POST['child_category_id'] ?? 0),
        'brand_id' => (int)($_POST['brand_id'] ?? 0),
        'price' => (float)($_POST['price'] ?? 0),
        'sell_price' => (float)($_POST['sell_price'] ?? 0),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    if (empty($update_data['name'])) {
        $error = 'Product name is required';
    } elseif (empty($update_data['barcode'])) {
        $error = 'Barcode is required';
    }else {
        // Check if barcode exists (excluding current product)
        $check = $mysqli->common_select('products', 'id', ['barcode' => $update_data['barcode'], 'id!=' => $product_id, 'is_deleted' => 0]);
        if (!$check['error'] && !empty($check['data'])) {
            $error = 'Product with this barcode already exists';
        } else {
            $result = $mysqli->common_update('products', $update_data, ['id' => $product_id]);
            if (!$result['error']) {
                $success = 'Product updated successfully!';
                // Refresh product data
                $result = $mysqli->common_select('products', '*', ['id' => $product_id]);
                if (!$result['error'] && !empty($result['data'])) {
                    $product = $result['data'][0];
                }
            } else {
                $error = 'Error updating product: ' . $result['error_msg'];
            }
        }
    }
}

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/sidebar.php';
require_once __DIR__ . '/../../requires/topbar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Edit Product</h4>
            <a href="view_product.php" class="btn btn-secondary btn-round ms-auto">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
         <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php unset($error); ?>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php unset($success); ?>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Product Information</div>
                    </div>
                    <div class="card-body"> 
                        <form method="post" id="productForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Product Name *</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?= htmlspecialchars($product->name) ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="barcode">Barcode *</label>
                                        <input type="text" class="form-control" id="barcode" name="barcode" 
                                               value="<?= htmlspecialchars($product->barcode) ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="category_id">Main Category</label>
                                        <select class="form-control" id="category_id" name="category_id">
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category->id ?>" 
                                                    <?= $product->category_id == $category->id ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($category->category) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="sub_category_id">Sub Category</label>
                                        <select class="form-control" id="sub_category_id" name="sub_category_id" 
                                                <?= empty($sub_categories) ? 'disabled="disabled"' : '' ?>>
                                            <option value="">Select Sub Category</option>
                                            <?php foreach ($sub_categories as $sub): ?>
                                                <option value="<?= $sub->id ?>" 
                                                    <?= $product->sub_category_id == $sub->id ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($sub->category_name) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="child_category_id">Child Category</label>
                                        <select class="form-control" id="child_category_id" name="child_category_id" 
                                                <?= empty($child_categories) ? 'disabled="disabled"' : '' ?>>
                                            <option value="">Select Child Category</option>
                                            <?php foreach ($child_categories as $child): ?>
                                                <option value="<?= $child->id ?>" 
                                                    <?= $product->child_category_id == $child->id ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($child->category_name) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="brand_id">Brand</label>
                                        <select class="form-control" id="brand_id" name="brand_id">
                                            <option value="">Select Brand</option>
                                            <?php foreach ($brands as $brand): ?>
                                                <option value="<?= $brand->id ?>" 
                                                    <?= $product->brand_id == $brand->id ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($brand->brand_name) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="price">Purchase Price *</label>
                                        <input type="number" step="0.01" class="form-control" id="price" name="price" 
                                               value="<?= htmlspecialchars($product->price) ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="sell_price">Selling Price *</label>
                                        <input type="number" step="0.01" class="form-control" name="sell_price" 
                                               value="<?= htmlspecialchars($product->sell_price) ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group text-right">
                                <button type="submit" class="btn btn-primary">Update Product</button>
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
$(document).ready(function() {
    // Category chain dropdowns
    $('#category_id').change(function() {
        var categoryId = $(this).val();
        $('#sub_category_id').html('<option value="">Loading...</option>');
        $('#sub_category_id').prop('disabled', true);
        $('#child_category_id').html('<option value="">Select Child Category</option>');
        $('#child_category_id').prop('disabled', true);
        
        if (categoryId) {
            $.get('edit_product.php', {get_sub_categories: 1, category_id: categoryId}, function(data) {
                var options = '<option value="">Select Sub Category</option>';
                $.each(data, function(key, value) {
                    options += '<option value="' + value.id + '">' + value.category_name + '</option>';
                });
                $('#sub_category_id').html(options);
                $('#sub_category_id').prop('disabled', false);
            });
        }
    });
    
    $('#sub_category_id').change(function() {
        var subCategoryId = $(this).val();
        $('#child_category_id').html('<option value="">Loading...</option>');
        $('#child_category_id').prop('disabled', true);
        
        if (subCategoryId) {
            $.get('edit_product.php', {get_child_categories: 1, sub_category_id: subCategoryId}, function(data) {
                var options = '<option value="">Select Child Category</option>';
                $.each(data, function(key, value) {
                    options += '<option value="' + value.id + '">' + value.category_name + '</option>';
                });
                $('#child_category_id').html(options);
                $('#child_category_id').prop('disabled', false);
            });
        }
    });
    
});
</script>