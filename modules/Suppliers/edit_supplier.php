<?php
session_start();

if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}

require_once __DIR__ . '/../../db_plugin.php';

$error = '';
$success = '';
$supplier_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Load supplier data
$result = $mysqli->common_select('suppliers', '*', ['id' => $supplier_id]);
if ($result['error'] || empty($result['data'])) {
    $_SESSION['error'] = 'Supplier not found';
    header("Location: view_suppliers.php");
    exit();
}
$supplier = (array)$result['data'][0];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $company_name = trim($_POST['company_name']);

    if (empty($name)) {
         $_SESSION['error'] = 'Supplier name is required';
    } else {
        // Check if supplier already exists (excluding current supplier)
        $check = $mysqli->common_select('suppliers', 'id', [
            'name' => $name,
            'id!=' => $supplier_id
        ]);
        
        if (!$check['error'] && !empty($check['data'])) {
             $_SESSION['error'] = 'Supplier already exists';
        } else {
            $data = [
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'address' => $address,
                'company_name' => $company_name
            ];
            
            $result = $mysqli->common_update('suppliers', $data, ['id' => $supplier_id]);
            if (!$result['error']) {
                $_SESSION['success'] = 'Supplier updated successfully';
                header("Location: view_suppliers.php");
                exit();
            } else {
                $_SESSION['error'] = 'Error updating supplier: ' . $result['error_msg'];
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
            <h4 class="page-title">Edit Supplier</h4>
            <div class="ms-auto">
                <a href="view_suppliers.php" class="btn btn-primary btn-round">
                    <i class="fas fa-list"></i> View Suppliers
                </a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="name">Supplier Name *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?= htmlspecialchars($supplier['name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="company_name">Company Name</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" 
                                       value="<?= htmlspecialchars($supplier['company_name']) ?>">
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone" 
                                       value="<?= htmlspecialchars($supplier['phone']) ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($supplier['email']) ?>">
                            </div>
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($supplier['address']) ?></textarea>
                            </div>
                            <div class="form-group text-right">
                                <a href="view_suppliers.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Supplier</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../requires/footer.php'; ?>