<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true || $_SESSION['user']->role !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require_once __DIR__ . '/../db_plugin.php'; 

// Handle delete action first
if (isset($_GET['delete_id'])) {
    $user_id = $_GET['delete_id'];
    
    if ($_SESSION['user']->id != $user_id) {
        $result = $mysqli->common_update('users', 
            ['is_deleted' => 1, 'deleted_at' => date('Y-m-d H:i:s')],
            ['id' => $user_id]
        );
        
        if ($result['error'] == 0) {
            $_SESSION['success'] = "User deleted successfully";
        } else {
            $_SESSION['error'] = "Delete failed: " . $result['error_msg'];
        }
    } else {
        $_SESSION['error'] = "You cannot delete yourself";
    }
}


$users_result = $mysqli->common_select('users', '*', ['is_deleted' => 0]);
$users = $users_result['data'] ?? [];

require_once __DIR__ . '/../requires/header.php';
require_once __DIR__ . '/../requires/sidebar.php';
require_once __DIR__ . '/../requires/topbar.php';
?>

<div class="container">
    <div class="page-inner">
        <!-- Display success/error messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">All Users</h4>
                    <a href="register.php" class="btn btn-primary btn-round ms-auto">
                        <i class="fa fa-plus"></i> Add New User
                    </a>
                </div>
            </div>
            
            <div class="card-body">
                <div class="table-responsive">
                    <table id="userTable" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= $user->id ?></td>
                                        <td><?= htmlspecialchars($user->username) ?></td>
                                        <td><?= htmlspecialchars($user->full_name) ?></td>
                                        <td><?= htmlspecialchars($user->email) ?></td>
                                        <td><?= ucfirst($user->role) ?></td>
                                        <td>
                                            <span class="badge <?= $user->is_active ? 'badge-success' : 'badge-danger' ?>">
                                                <?= $user->is_active ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="form-button-action">
                                                <a href="edit_user.php?id=<?= $user->id ?>" class="btn btn-link btn-primary">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <a href="view_user.php?delete_id=<?= $user->id ?>" 
                                                   class="btn btn-link btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this user?')">
                                                    <i class="fa fa-times"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No users found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../requires/footer.php'; ?>