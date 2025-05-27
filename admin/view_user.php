<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true || $_SESSION['user']->role !== 'admin') {
    header("Location: login.php");
    exit();
}
 require_once __DIR__ . '/../requires/header.php';
 require_once __DIR__ . '/../requires/sidebar.php';
 require_once __DIR__ . '/../requires/topbar.php';


if (isset($_GET['delete_id'])) {
    $result = $mysqli->common_update('users', 
        ['is_deleted' => 1, 'deleted_at' => date('Y-m-d H:i:s')],
        ['id' => $_GET['delete_id']]
    );
    
    if ($result['error'] == 0) {
        $_SESSION['success'] = "User deleted successfully";
        header("Location: users.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to delete user: " . $result['error_msg'];
    }
}

$users = $mysqli->common_select('users', '*', ['is_deleted' => 0]);
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">User Management</h4>
        </div>
        
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
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table id="userTable" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Last Login</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($users['error'] == 0 && count($users['data']) > 0): ?>
                                <?php foreach ($users['data'] as $user): ?>
                                    <tr>
                                        <td><?= $user->id ?></td>
                                        <td><?= htmlspecialchars($user->username) ?></td>
                                        <td><?= htmlspecialchars($user->full_name) ?></td>
                                        <td><?= htmlspecialchars($user->email) ?></td>
                                        <td>
                                            <span class="badge 
                                                <?= $user->role === 'admin' ? 'badge-primary' : 
                                                   ($user->role === 'manager' ? 'badge-info' : 
                                                   ($user->role === 'inventory' ? 'badge-warning' : 'badge-secondary')) ?>">
                                                <?= ucfirst($user->role) ?>
                                            </span>
                                        </td>
                                        <td><?= $user->last_login ? date('M d, Y H:i', strtotime($user->last_login)) : 'Never' ?></td>
                                        <td>
                                            <span class="badge <?= $user->is_active ? 'badge-success' : 'badge-danger' ?>">
                                                <?= $user->is_active ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="form-button-action">
                                                <a href="edit_user.php?id=<?= $user->id ?>" class="btn btn-link btn-primary" data-toggle="tooltip" title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <?php if ($user->id != $_SESSION['user']->id): ?>
                                                    <a href="users.php?delete_id=<?= $user->id ?>" class="btn btn-link btn-danger" data-toggle="tooltip" title="Delete" onclick="return confirm('Are you sure you want to delete this user?')">
                                                        <i class="fa fa-times"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No users found</td>
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

<script>
$(document).ready(function() {
    $('#userTable').DataTable({
        "pageLength": 10,
        "order": [[0, "desc"]]
    });
});
</script>