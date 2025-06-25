<?php
session_start();

if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}

require_once __DIR__ . '/../../db_plugin.php';

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $supplier_id = (int)$_GET['id'];
    if ($supplier_id > 0) {
        $result = $mysqli->common_delete('suppliers', ['id' => $supplier_id]);
        
        if (!$result['error']) {
            $_SESSION['success'] = 'Supplier deleted successfully';
        } else {
            $_SESSION['error'] = 'Error deleting supplier: ' . $result['error_msg'];
        }
        header("Location: view_suppliers.php");
        exit();
    }
}

// Get all suppliers
$result = $mysqli->common_select('suppliers', '*', [], 'name', 'asc');
$suppliers = $result['error'] ? [] : $result['data'];

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/sidebar.php';
require_once __DIR__ . '/../../requires/topbar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Supplier Management</h4>
            <div class="ms-auto">
                <a href="add_supplier.php" class="btn btn-primary btn-round">
                    <i class="fas fa-plus"></i> Add Supplier
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
                            <table id="supplierTable" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Company</th>
                                        <th>Phone</th>
                                        <th>Email</th>
                                        <th>Address</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($suppliers)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No suppliers found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($suppliers as $index => $supplier): ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td>
                                                <td><?= htmlspecialchars($supplier->name) ?></td>
                                                <td><?= htmlspecialchars($supplier->company_name) ?></td>
                                                <td><?= htmlspecialchars($supplier->phone) ?></td>
                                                <td><?= htmlspecialchars($supplier->email) ?></td>
                                                <td><?= htmlspecialchars($supplier->address) ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <?php if ($_SESSION['user']->role == 'admin'): ?>
                                                        <a href="edit_supplier.php?id=<?= $supplier->id ?>" 
                                                           class="btn btn-sm btn-info" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="view_suppliers.php?action=delete&id=<?= $supplier->id ?>" 
                                                           class="btn btn-sm btn-danger" title="Delete"
                                                           onclick="return confirm('Are you sure you want to delete this supplier?')">
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
    </div>
</div>

<?php require_once __DIR__ . '/../../requires/footer.php'; ?>