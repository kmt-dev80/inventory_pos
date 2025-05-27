<?php
session_start();
if(!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
        header("Location: login.php");
        exit();
    }
 require_once __DIR__ . '/../requires/header.php';
 require_once __DIR__ . '/../requires/sidebar.php';
 require_once __DIR__ . '/../requires/topbar.php';

$errors = [];
$success = false;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? 'cashier';

    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 4) {
        $errors[] = "Username must be at least 4 characters";
    } else {
        // Check if username exists
        $check = $mysqli->common_select('users', 'id', ['username' => $username]);
        if ($check['error'] == 0 && count($check['data']) > 0) {
            $errors[] = "Username already taken";
        }
    }

    // Email validation
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
            'password' => sha1($password),
            'full_name' => $full_name,
            'role' => $role,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $result = $mysqli->common_insert('users', $user_data);

        if ($result['error'] == 0) {
            $success = true;
            $_POST = [];
        } else {
            $errors[] = "Registration failed: " . $result['error_msg'];
        }
    }
}
?>
<div class="container">
    <div class="page-inner">
           <div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow overflow-hidden rounded-3">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-person-plus"></i> Register New User</h4>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">User created successfully!</div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="registerForm">                            
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                    value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="cashier" <?= ($_POST['role'] ?? '') === 'cashier' ? 'selected' : '' ?>>Cashier</option>
                                <option value="inventory" <?= ($_POST['role'] ?? '') === 'inventory' ? 'selected' : '' ?>>Inventory Manager</option>
                                <option value="manager" <?= ($_POST['role'] ?? '') === 'manager' ? 'selected' : '' ?>>Manager</option>
                                <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrator</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Register User</button>
                        <a href="view_user.php" class="btn btn-secondary">Back to Users</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
    </div>
</div>
<script>
    // Simple password match validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                document.getElementById('confirm_password').focus();
            }
        }); 
</script>    
<?php require_once __DIR__ . '/../requires/footer.php'; ?>