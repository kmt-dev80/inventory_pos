<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true || $_SESSION['user']->role !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Initialize database connection
require_once __DIR__ . '/../db_plugin.php';

// Get and validate user ID
$user_id = $_GET['id'] ?? null;
if (empty($user_id) || !is_numeric($user_id)) {
    $_SESSION['error'] = "Invalid user ID provided";
    header("Location: view_user.php");
    exit();
}

// Fetch user data
$result = $mysqli->common_select('users', '*', ['id' => $user_id]);
if ($result['error'] != 0 || empty($result['data'])) {
    $_SESSION['error'] = "User not found";
    header("Location: view_user.php");
    exit();
}

$user = $result['data'][0];
$errors = [];

// Initialize form variables
$username = $user->username;
$email = $user->email;
$full_name = $user->full_name;
$role = $user->role;
$is_active = $user->is_active;
$password = '';
$confirm_password = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data with fallback to initialized values
    $username = trim($_POST['username'] ?? $username);
    $email = trim($_POST['email'] ?? $email);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? $full_name);
    $role = $_POST['role'] ?? $role;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validation
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 4) {
        $errors[] = "Username must be at least 4 characters";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers and underscores";
    } else {
        $check = $mysqli->common_select('users', 'id', ['username' => $username]);
        if ($check['error'] == 0) {
            foreach ($check['data'] as $existing_user) {
                if ($existing_user->id != $user_id) {
                    $errors[] = "Username already taken";
                    break;
                }
            }
        }
    }

    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {
        $check = $mysqli->common_select('users', 'id', ['email' => $email]);
        if ($check['error'] == 0) {
            foreach ($check['data'] as $existing_user) {
                if ($existing_user->id != $user_id) {
                    $errors[] = "Email already registered";
                    break;
                }
            }
        }
    }

    if (!empty($password)) {
        if (strlen($password) < 4) {
            $errors[] = "Password must be at least 4 characters";
        } elseif ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }
    }

    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }

    $allowed_roles = ['admin', 'manager', 'cashier', 'inventory'];
    if (!in_array($role, $allowed_roles)) {
        $errors[] = "Invalid role selected";
    }

    // Process if no errors
    if (empty($errors)) {
        $user_data = [
            'username' => $username,
            'email' => $email,
            'full_name' => $full_name,
            'role' => $role,
            'is_active' => $is_active,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (!empty($password)) {
            $user_data['password'] = password_hash($password, PASSWORD_BCRYPT);
            $user_data['password_changed_at'] = date('Y-m-d H:i:s');
        }

        // Handle profile picture upload
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/profile_pics/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Remove old profile pic if exists
            if (!empty($user->profile_pic) && file_exists(__DIR__ . '/../' . $user->profile_pic)) {
                unlink(__DIR__ . '/../' . $user->profile_pic);
            }
            
            $file_ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
            $file_name = "user_{$user_id}_" . time() . ".{$file_ext}";
            $file_path = $upload_dir . $file_name;
            
            $valid_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($file_ext, $valid_extensions)) {
                $errors[] = "Invalid file type. Only JPG, PNG, and GIF are allowed";
            } elseif ($_FILES['profile_pic']['size'] > $max_size) {
                $errors[] = "File size exceeds 2MB limit";
            } elseif (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $file_path)) {
                $user_data['profile_pic'] = 'uploads/profile_pics/' . $file_name;
                
                // Update session immediately if this is the current user
                if ($user_id == $_SESSION['user']->id) {
                    $_SESSION['user']->profile_pic = $user_data['profile_pic'];
                }
            } else {
                $errors[] = "Failed to upload profile picture";
            }
        } elseif (isset($_POST['remove_profile_pic']) && $_POST['remove_profile_pic'] == 'on') {
            if (!empty($user->profile_pic) && file_exists(__DIR__ . '/../' . $user->profile_pic)) {
                unlink(__DIR__ . '/../' . $user->profile_pic);
            }
            $user_data['profile_pic'] = null;
            
            // Update session immediately if this is the current user
            if ($user_id == $_SESSION['user']->id) {
                $_SESSION['user']->profile_pic = null;
            }
        }

        if (empty($errors)) {
            $result = $mysqli->common_update('users', $user_data, ['id' => $user_id]);

            if ($result['error'] == 0) {
                // Update all session data if editing current user
                if ($user_id == $_SESSION['user']->id) {
                    $_SESSION['user']->username = $username;
                    $_SESSION['user']->email = $email;
                    $_SESSION['user']->full_name = $full_name;
                    $_SESSION['user']->role = $role;
                    $_SESSION['user']->is_active = $is_active;
                }
                
                $mysqli->common_insert('system_logs', [
                    'user_id' => $_SESSION['user']->id,
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'category' => 'user',
                    'message' => "Updated user #{$user_id} ({$username})"
                ]);
                
                $_SESSION['success'] = "User updated successfully";
                header("Location: view_user.php");
                exit();
            } else {
                $errors[] = "Update failed: " . $result['error_msg'];
            }
        }
    }
}

// Include HTML after all processing
require_once __DIR__ . '/requires/header.php';
require_once __DIR__ . '/requires/sidebar.php';
require_once __DIR__ . '/requires/topbar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Edit User Details</div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data" id="editUserForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">Username *</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?= htmlspecialchars($username) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($email) ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password">
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Leave blank to keep current password (min 8 chars)</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?= htmlspecialchars($full_name) ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="role" class="form-label">Role *</label>
                                    <select class="form-control" id="role" name="role" required>
                                        <option value="cashier" <?= $role === 'cashier' ? 'selected' : '' ?>>Cashier</option>
                                        <option value="inventory" <?= $role === 'inventory' ? 'selected' : '' ?>>Inventory Manager</option>
                                        <option value="manager" <?= $role === 'manager' ? 'selected' : '' ?>>Manager</option>
                                        <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Administrator</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" 
                                               <?= $is_active ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="is_active">Active</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="profile_pic" class="form-label">Profile Picture</label>
                                <input type="file" class="form-control-file" id="profile_pic" name="profile_pic" accept="image/*">
                                <small class="text-muted">Max 2MB (JPG, PNG, GIF)</small>
                                <?php if (!empty($user->profile_pic)): ?>
                                    <div class="mt-2">
                                        <img src="<?= BASE_URL . $user->profile_pic ?>" alt="Profile" class="img-thumbnail" style="max-height: 100px;">
                                        <div class="custom-control custom-checkbox mt-2">
                                            <input type="checkbox" class="custom-control-input" id="remove_profile_pic" name="remove_profile_pic">
                                            <label class="custom-control-label" for="remove_profile_pic">Remove current picture</label>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Update User</button>
                            <a href="view_user.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">User Information</div>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div class="avatar avatar-xxl">
                                <img src="<?= !empty($user->profile_pic) ? BASE_URL . $user->profile_pic : BASE_URL . 'assets/img/user.png' ?>" 
                                     alt="Profile" class="avatar-img rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                            </div>
                            <h4 class="mt-3"><?= htmlspecialchars($full_name) ?></h4>
                            <p class="text-muted"><?= ucfirst($role) ?></p>
                        </div>
                        
                        <div class="list-group list-group-flush">
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <span>Last Login:</span>
                                    <span><?= $user->last_login ? date('M d, Y H:i', strtotime($user->last_login)) : 'Never' ?></span>
                                </div>
                            </div>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <span>Created At:</span>
                                    <span><?= date('M d, Y', strtotime($user->created_at)) ?></span>
                                </div>
                            </div>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <span>Updated At:</span>
                                    <span><?= date('M d, Y', strtotime($user->updated_at)) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../requires/footer.php'; ?>