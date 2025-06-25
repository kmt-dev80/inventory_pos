<?php
session_start();

if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}

require_once __DIR__ . '/../../db_plugin.php';

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $customer_id = (int)$_GET['id'];
    if ($customer_id > 0) {
        $result = $mysqli->common_delete('customers', ['id' => $customer_id]);
        
        if (!$result['error']) {
            $_SESSION['success'] = 'Customer deleted successfully';
        } else {
            $_SESSION['error'] = 'Error deleting customer: ' . $result['error_msg'];
        }
        header("Location: view_customers.php");
        exit();
    }
}

// Get all customers
$result = $mysqli->common_select('customers', '*', [], 'name', 'asc');
$customers = $result['error'] ? [] : $result['data'];

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/sidebar.php';
require_once __DIR__ . '/../../requires/topbar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Customer Management</h4>
            <div class="ms-auto">
                <a href="add_customer.php" class="btn btn-primary btn-round">
                    <i class="fas fa-plus"></i> Add Customer
                </a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="customerTable" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>Email</th>
                                        <th>Address</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($customers)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No customers found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($customers as $index => $customer): ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td>
                                                <td><?= htmlspecialchars($customer->name ?? '') ?></td>
                                                <td><?= htmlspecialchars($customer->phone ?? '') ?></td>
                                                <td><?= htmlspecialchars($customer->email ?? '') ?></td>
                                                <td><?= htmlspecialchars($customer->address ?? '') ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="edit_customer.php?id=<?= $customer->id ?>" 
                                                           class="btn btn-sm btn-info" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="view_customers.php?action=delete&id=<?= $customer->id ?>" 
                                                           class="btn btn-sm btn-danger" title="Delete"
                                                           onclick="return confirm('Are you sure you want to delete this customer?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
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
    </div>
</div>

<?php require_once __DIR__ . '/../../requires/footer.php'; ?>