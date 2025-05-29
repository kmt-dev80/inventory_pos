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
$brand = ['id' => 0, 'brand_name' => '', 'details' => ''];
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$brand_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle different actions
switch ($action) {
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $brand_name = trim($_POST['brand_name']);
            $details = trim($_POST['details']);
            
            if (empty($brand_name)) {
                $error = 'Brand name is required';
            } else {
                // Check if brand already exists
                $check = $mysqli->common_select('brand', 'id', ['brand_name' => $brand_name, 'is_deleted' => 0]);
                if (!$check['error'] && !empty($check['data'])) {
                    $error = 'Brand already exists';
                } else {
                    $data = [
                        'brand_name' => $brand_name,
                        'details' => $details
                    ];
                    $result = $mysqli->common_insert('brand', $data);
                    if (!$result['error']) {
                        $_SESSION['success'] = 'Brand added successfully';
                        header("Location: manage_brands.php");
                        exit();
                    } else {
                        $error = 'Error adding brand: ' . $result['error_msg'];
                    }
                }
            }
        }
        break;
        
    case 'edit':
        // Load brand data for editing
        if ($brand_id > 0) {
            $result = $mysqli->common_select('brand', '*', ['id' => $brand_id]);
            if (!$result['error'] && !empty($result['data'])) {
                $brand = (array)$result['data'][0];
            } else {
                $_SESSION['error'] = 'Brand not found';
                header("Location: manage_brands.php");
                exit();
            }
        } else {
            header("Location: manage_brands.php");
            exit();
        }
        
        // Handle brand update
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $brand_name = trim($_POST['brand_name']);
            $details = trim($_POST['details']);
            
            if (empty($brand_name)) {
                $error = 'Brand name is required';
            } else {
                // Check if brand already exists (excluding current brand)
                $check = $mysqli->common_select('brand', 'id', [
                    'brand_name' => $brand_name, 
                    'id!=' => $brand_id,
                    'is_deleted' => 0
                ]);
                
                if (!$check['error'] && !empty($check['data'])) {
                    $error = 'Brand already exists';
                } else {
                    $data = [
                        'brand_name' => $brand_name,
                        'details' => $details,
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    $result = $mysqli->common_update('brand', $data, ['id' => $brand_id]);
                    if (!$result['error']) {
                        $_SESSION['success'] = 'Brand updated successfully';
                        header("Location: manage_brands.php");
                        exit();
                    } else {
                        $error = 'Error updating brand: ' . $result['error_msg'];
                    }
                }
            }
        }
        break;
        
    case 'delete':
        if ($brand_id > 0) {
            $result = $mysqli->common_update('brand', [
                'is_deleted' => 1,
                'deleted_at' => date('Y-m-d H:i:s')
            ], ['id' => $brand_id]);
            
            if (!$result['error']) {
                $_SESSION['success'] = 'Brand deleted successfully';
            } else {
                $_SESSION['error'] = 'Error deleting brand: ' . $result['error_msg'];
            }
        }
        header("Location: manage_brands.php");
        exit();
        break;
        
    case 'restore':
        if ($brand_id > 0) {
            $result = $mysqli->common_update('brand', [
                'is_deleted' => 0,
                'deleted_at' => null
            ], ['id' => $brand_id]);
            
            if (!$result['error']) {
                $_SESSION['success'] = 'Brand restored successfully';
            } else {
                $_SESSION['error'] = 'Error restoring brand: ' . $result['error_msg'];
            }
        }
        header("Location: manage_brands.php?action=trash");
        exit();
        break;
        
    case 'permanent_delete':
        if ($brand_id > 0) {
            $result = $mysqli->common_delete('brand', ['id' => $brand_id]);
            
            if (!$result['error']) {
                $_SESSION['success'] = 'Brand permanently deleted';
            } else {
                $_SESSION['error'] = 'Error deleting brand: ' . $result['error_msg'];
            }
        }
        header("Location: manage_brands.php?action=trash");
        exit();
        break;
        
    case 'trash':
        // Special case for viewing trash - handled in listing
        break;
}

// Get brands for listing
$where = $action === 'trash' ? ['is_deleted' => 1] : ['is_deleted' => 0];
$result = $mysqli->common_select('brand', '*', $where, 'brand_name', 'asc');
$brands = $result['error'] ? [] : $result['data'];

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Brand Management</h4>
            <div class="ms-auto">
                <?php if ($action === 'trash'): ?>
                    <a href="manage_brands.php" class="btn btn-primary btn-round">
                        <i class="fas fa-list"></i> View Active Brands
                    </a>
                <?php else: ?>
                    <a href="manage_brands.php?action=add" class="btn btn-primary btn-round">
                        <i class="fas fa-plus"></i> Add Brand
                    </a>
                    <a href="manage_brands.php?action=trash" class="btn btn-warning btn-round">
                        <i class="fas fa-trash"></i> View Trash
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($action === 'add' || $action === 'edit'): ?>
            <!-- Add/Edit Brand Form -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <?= $action === 'add' ? 'Add New Brand' : 'Edit Brand' ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="form-group">
                                    <label for="brand_name">Brand Name *</label>
                                    <input type="text" class="form-control" id="brand_name" name="brand_name" 
                                           value="<?= htmlspecialchars($brand['brand_name']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="details">Description</label>
                                    <textarea class="form-control" id="details" name="details" rows="3"><?= htmlspecialchars($brand['details']) ?></textarea>
                                </div>
                                <div class="form-group text-right">
                                    <a href="manage_brands.php" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <?= $action === 'add' ? 'Add Brand' : 'Update Brand' ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Brand Listing -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <?= $action === 'trash' ? 'Deleted Brands' : 'Active Brands' ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="brandTable" class="display table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Brand Name</th>
                                            <th>Description</th>
                                            <th><?= $action === 'trash' ? 'Deleted On' : 'Created On' ?></th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($brands)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No brands found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($brands as $index => $brand): ?>
                                                <tr>
                                                    <td><?= $index + 1 ?></td>
                                                    <td><?= htmlspecialchars($brand->brand_name) ?></td>
                                                    <td><?= htmlspecialchars($brand->details) ?></td>
                                                    <td>
                                                        <?= $action === 'trash' ? 
                                                            date('M d, Y h:i A', strtotime($brand->deleted_at)) : 
                                                            date('M d, Y', strtotime($brand->created_at)) ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <?php if ($action === 'trash'): ?>
                                                                <a href="manage_brands.php?action=restore&id=<?= $brand->id ?>" 
                                                                   class="btn btn-sm btn-success" title="Restore"
                                                                   onclick="return confirm('Restore this brand?')">
                                                                    <i class="fas fa-undo"></i>
                                                                </a>
                                                                <a href="manage_brands.php?action=permanent_delete&id=<?= $brand->id ?>" 
                                                                   class="btn btn-sm btn-danger" title="Permanently Delete"
                                                                   onclick="return confirm('Permanently delete this brand?')">
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </a>
                                                            <?php else: ?>
                                                                <a href="manage_brands.php?action=edit&id=<?= $brand->id ?>" 
                                                                   class="btn btn-sm btn-info" title="Edit">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="manage_brands.php?action=delete&id=<?= $brand->id ?>" 
                                                                   class="btn btn-sm btn-danger" title="Delete"
                                                                   onclick="return confirm('Move this brand to trash?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../requires/footer.php'; ?>

