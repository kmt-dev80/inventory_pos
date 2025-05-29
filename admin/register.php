<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true || $_SESSION['user']->role !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require_once __DIR__ . '/../db_plugin.php'; 

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? 'cashier';

    // Validation
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 4) {
        $errors[] = "Username must be at least 4 characters";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers and underscores";
    } else {
        $check = $mysqli->common_select('users', 'id', ['username' => $username]);
        if ($check['error'] == 0 && count($check['data']) > 0) {
            $errors[] = "Username already taken";
        }
    }

    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {
        $check = $mysqli->common_select('users', 'id', ['email' => $email]);
        if ($check['error'] == 0 && count($check['data']) > 0) {
            $errors[] = "Email already registered";
        }
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 4) {
        $errors[] = "Password must be at least 4 characters";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }

    $allowed_roles = ['admin', 'manager', 'cashier', 'inventory'];
    if (!in_array($role, $allowed_roles)) {
        $errors[] = "Invalid role selected";
    }

    if (empty($errors)) {
        $user_data = [
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'full_name' => $full_name,
            'role' => $role,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Handle profile picture upload
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/profile_pics/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('profile_') . '.' . $file_ext;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $file_path)) {
                $user_data['profile_pic'] = 'uploads/profile_pics/' . $file_name;
            }
        }

        $result = $mysqli->common_insert('users', $user_data);

        if ($result['error'] == 0) {
            // Log the user creation
            $mysqli->common_insert('system_logs', [
                'user_id' => $_SESSION['user']->id,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'category' => 'user',
                'message' => "Created new user: $username ($role)"
            ]);
            
            $success = true;
            $_POST = [];
        } else {
            $errors[] = "Registration failed: " . $result['error_msg'];
        }
    }
}
require_once __DIR__ . '/../requires/header.php';
require_once __DIR__ . '/../requires/sidebar.php';
require_once __DIR__ . '/../requires/topbar.php';
?>
<div class="container">
    <div class="page-inner">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-user-plus mr-2"></i> Register New User
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <button type="button" class="close" data-dismiss="alert">×</button>
                                User created successfully!
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <button type="button" class="close" data-dismiss="alert">×</button>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data" id="registerForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="username">Username *</label>
                                        <input type="text" class="form-control" id="username" name="username" 
                                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email *</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password">Password *</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <small class="form-text text-muted">Minimum 8 characters</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="confirm_password">Confirm Password *</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="full_name">Full Name *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="role">Role *</label>
                                        <select class="form-control" id="role" name="role" required>
                                            <option value="cashier" <?= ($_POST['role'] ?? '') === 'cashier' ? 'selected' : '' ?>>Cashier</option>
                                            <option value="inventory" <?= ($_POST['role'] ?? '') === 'inventory' ? 'selected' : '' ?>>Inventory Manager</option>
                                            <option value="manager" <?= ($_POST['role'] ?? '') === 'manager' ? 'selected' : '' ?>>Manager</option>
                                            <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrator</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="profile_pic">Profile Picture</label>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="profile_pic" name="profile_pic">
                                            <label class="custom-file-label" for="profile_pic">Choose file</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-2"></i> Save User
                                </button>
                                <a href="view_user.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left mr-2"></i> Back to Users
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../requires/footer.php'; ?>