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
$product = [
    'name' => '',
    'barcode' => '',
    'category_id' => '',
    'sub_category_id' => '',
    'child_category_id' => '',
    'brand_id' => '',
    'price' => '',
    'sell_price' => ''
];

// Load dropdown options
$categories = [];
$sub_categories = [];
$child_categories = [];
$brands = [];

// Get categories
$result = $mysqli->common_select('category', '*', ['is_deleted' => 0], 'category', 'asc');
if (!$result['error']) $categories = $result['data'];

// Get brands
$result = $mysqli->common_select('brand', '*', ['is_deleted' => 0], 'brand_name', 'asc');
if (!$result['error']) $brands = $result['data'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    $product['name'] = trim($_POST['name'] ?? '');
    $product['barcode'] = trim($_POST['barcode'] ?? '');
    $product['category_id'] = (int)($_POST['category_id'] ?? 0);
    $product['sub_category_id'] = (int)($_POST['sub_category_id'] ?? 0);
    $product['child_category_id'] = (int)($_POST['child_category_id'] ?? 0);
    $product['brand_id'] = (int)($_POST['brand_id'] ?? 0);
    $product['price'] = (float)($_POST['price'] ?? 0);
    $product['sell_price'] = (float)($_POST['sell_price'] ?? 0);

    if (empty($product['name'])) {
        $error = 'Product name is required';
    } elseif (empty($product['barcode'])) {
        $error = 'Barcode is required';
    } elseif ($product['sell_price'] < $product['price']) {
        $error = 'Sell price cannot be lower than purchase price';
    } else {
        // Check if barcode exists
        $check = $mysqli->common_select('products', 'id', ['barcode' => $product['barcode'], 'is_deleted' => 0]);
        if (!$check['error'] && !empty($check['data'])) {
            $error = 'Product with this barcode already exists';
        } else {
            $result = $mysqli->common_insert('products', $product);
            if (!$result['error']) {
                $success = 'Product added successfully!';
                // Reset form
                $product = [
                    'name' => '',
                    'barcode' => '',
                    'category_id' => '',
                    'sub_category_id' => '',
                    'child_category_id' => '',
                    'brand_id' => '',
                    'price' => '',
                    'sell_price' => ''
                ];
            } else {
                $error = 'Error adding product: ' . $result['error_msg'];
            }
        }
    }
}

// Handle AJAX requests for category dropdowns
if (isset($_GET['get_sub_categories'])) {
     header('Content-Type: application/json');
    $category_id = (int)$_GET['category_id'];
    if ($category_id > 0) {
        $result = $mysqli->common_select('sub_category', '*', ['category_id' => $category_id, 'is_deleted' => 0], 'category_name', 'asc');
        echo json_encode($result['error'] ? [] : $result['data']);
    } else {
        echo json_encode([]);
    }
    exit;
}

if (isset($_GET['get_child_categories'])) {
    $sub_category_id = (int)$_GET['sub_category_id'];
    if ($sub_category_id > 0) {
        $result = $mysqli->common_select('child_category', '*', ['sub_category_id' => $sub_category_id, 'is_deleted' => 0], 'category_name', 'asc');
        echo json_encode($result['error'] ? [] : $result['data']);
    } else {
        echo json_encode([]);
    }
    exit;
}

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Add New Product</h4>
             <a href="view_product.php" class="btn btn-secondary btn-round ms-auto">
                <i class="fas fa-arrow-right"></i> View Products
             </a>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Product Information</div>  
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>
                        
                        <form method="post" id="productForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Product Name *</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?= htmlspecialchars($product['name']) ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="barcode">Barcode *</label>
                                        <input type="text" class="form-control" id="barcode" name="barcode" 
                                               value="<?= htmlspecialchars($product['barcode']) ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="category_id">Main Category</label>
                                        <select class="form-control" id="category_id" name="category_id">
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category->id ?>" 
                                                    <?= $product['category_id'] == $category->id ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($category->category) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="sub_category_id">Sub Category</label>
                                        <select class="form-control" id="sub_category_id" name="sub_category_id" 
                                                disabled="disabled">
                                            <option value="">Select Sub Category</option>
                                            <?php foreach ($sub_categories as $sub): ?>
                                                <option value="<?= $sub->id ?>" 
                                                    <?= $product['sub_category_id'] == $sub->id ? 'selected' : '' ?>>
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
                                                disabled="disabled">
                                            <option value="">Select Child Category</option>
                                            <?php foreach ($child_categories as $child): ?>
                                                <option value="<?= $child->id ?>" 
                                                    <?= $product['child_category_id'] == $child->id ? 'selected' : '' ?>>
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
                                                    <?= $product['brand_id'] == $brand->id ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($brand->brand_name) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="price">Purchase Price *</label>
                                        <input type="number" step="0.01" class="form-control" id="price" name="price" 
                                               value="<?= htmlspecialchars($product['price']) ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="sell_price">Selling Price *</label>
                                        <input type="number" step="0.01" class="form-control" id="sell_price" name="sell_price" 
                                               value="<?= htmlspecialchars($product['sell_price']) ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group text-right">
                                <button type="submit" class="btn btn-primary">Add Product</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../requires/footer.php'; ?>