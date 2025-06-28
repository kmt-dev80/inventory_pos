<?php
session_start();

if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}

require_once __DIR__ . '/../../db_plugin.php';

$error = '';
$success = '';
$customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Load customer data
$result = $mysqli->common_select('customers', '*', ['id' => $customer_id]);
if ($result['error'] || empty($result['data'])) {
    $_SESSION['error'] = 'Customer not found';
    header("Location: view_customers.php");
    exit();
}
$customer = (array)$result['data'][0];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);

    if (empty($name)) {
        $error = 'Customer name is required';
    } else {
        // Check if customer already exists (excluding current customer)
        $check = $mysqli->common_select('customers', 'id', [
            'name' => $name,
            'id!=' => $customer_id
        ]);
        
        if (!$check['error'] && !empty($check['data'])) {
            $error = 'Customer already exists';
        } else {
            $data = [
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'address' => $address,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => $_SESSION[$user]->id
            ];
            
            $result = $mysqli->common_update('customers', $data, ['id' => $customer_id]);
            if (!$result['error']) {
                $_SESSION['success'] = 'Customer updated successfully';
                header("Location: view_customers.php");
                exit();
            } else {
                $error = 'Error updating customer: ' . $result['error_msg'];
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
            <h4 class="page-title">Edit Customer</h4>
            <div class="ms-auto">
                <a href="view_customers.php" class="btn btn-primary btn-round">
                    <i class="fas fa-list"></i> View Customers
                </a>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="name">Customer Name *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?= htmlspecialchars(($customer['name']) ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone" 
                                       value="<?= htmlspecialchars(($customer['phone']) ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars(($customer['email']) ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars(($customer['address']) ?? '')?></textarea>
                            </div>
                            <div class="form-group text-right">
                                <a href="view_customers.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Customer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../requires/footer.php'; ?>