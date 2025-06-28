<?php
session_start();

if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}

require_once __DIR__ . '/../../db_plugin.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);

    if (empty($name)) {
        $error = 'Customer name is required';
    } else {
        // Check if customer already exists
        $check = $mysqli->common_select('customers', 'id', ['name' => $name]);
        if (!$check['error'] && !empty($check['data'])) {
             $_SESSION['error'] = 'Customer already exists';
        } else {
            $data = [
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'address' => $address,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $_SESSION[$user]->id
            ];
            
            $result = $mysqli->common_insert('customers', $data);
            if (!$result['error']) {
                $_SESSION['success'] = 'Customer added successfully';
                header("Location: add_customer.php");
                exit();
            } else {
                $error = 'Error adding customer: ' . $result['error_msg'];
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
            <h4 class="page-title">Add New Customer</h4>
            <div class="ms-auto">
                <a href="view_customers.php" class="btn btn-primary btn-round">
                    <i class="fas fa-list"></i> View Customers
                </a>
            </div>
        </div>
        
        <?php if ($error): ?>
             <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="name">Customer Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone">
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                            </div>
                            <div class="form-group text-right">
                                <a href="view_customers.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Add Customer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../requires/footer.php'; ?>