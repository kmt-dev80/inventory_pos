<?php
class Auth {
    protected $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    public function isLoggedIn() {
        if (!isset($_SESSION['user'], $_SESSION['last_activity'], $_SESSION['ip_address'], $_SESSION['user_agent'])) {
            return false;
        }
        
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            $this->logout();
            logEvent('Session expired', 'auth');
            return false;
        }
        
        if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR'] || 
            $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            logEvent('Session hijacking detected for user: ' . ($_SESSION['user']['username'] ?? 'unknown'), 'security');
            $this->logout();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            header("Location: " . BASE_URL . "auth/login.php");
            exit();
        }
    }
    
    public function currentUser() {
        return $_SESSION['user'] ?? null;
    }
    
    public function hasRole($role) {
        $user = $this->currentUser();
        return $user && ($user['role'] === $role || $user['role'] === 'admin');
    }
    
    public function requireRole($role) {
        $this->requireAuth();
        if (!$this->hasRole($role)) {
            header("HTTP/1.1 403 Forbidden");
            include(__DIR__ . '/../errors/403.php');
            exit();
        }
    }
    
    public function generateCSRFToken($purpose = 'general') {
        if (empty($_SESSION['csrf_tokens'][$purpose])) {
            $_SESSION['csrf_tokens'][$purpose] = [
                'token' => bin2hex(random_bytes(32)),
                'expires' => time() + CSRF_TOKEN_EXPIRY
            ];
        }
        return $_SESSION['csrf_tokens'][$purpose]['token'];
    }
    
    public function verifyCSRFToken($token, $purpose = 'general') {
        if (!isset($_SESSION['csrf_tokens'][$purpose])) {
            return false;
        }
        
        $stored = $_SESSION['csrf_tokens'][$purpose];
        
        if (time() > $stored['expires']) {
            unset($_SESSION['csrf_tokens'][$purpose]);
            return false;
        }
        
        $result = hash_equals($stored['token'], $token);
        unset($_SESSION['csrf_tokens'][$purpose]);
        return $result;
    }
    
    public function rateLimit($action, $maxAttempts = MAX_LOGIN_ATTEMPTS, $timeout = LOGIN_LOCKOUT_MINUTES * 60) {
        $key = 'rate_limit_' . $action;
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'last_attempt' => 0,
                'lockout_until' => 0
            ];
        }
        
        $now = time();
        $limit = &$_SESSION[$key];
        
        if ($limit['lockout_until'] > $now) {
            return false;
        }
        
        if ($limit['last_attempt'] + $timeout < $now) {
            $limit['attempts'] = 0;
        }
        
        $limit['attempts']++;
        $limit['last_attempt'] = $now;
        
        if ($limit['attempts'] > $maxAttempts) {
            $limit['lockout_until'] = $now + $timeout;
            return false;
        }
        
        return true;
    }
    
    public function validatePassword($password) {
        if (strlen($password) < MIN_PASSWORD_LENGTH) {
            return "Password must be at least " . MIN_PASSWORD_LENGTH . " characters";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            return "Password must contain at least one uppercase letter";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            return "Password must contain at least one lowercase letter";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            return "Password must contain at least one number";
        }
        
        if (!preg_match('/[\W]/', $password)) {
            return "Password must contain at least one special character";
        }
        
        return true;
    }
    
    public function logout() {
        if (isset($_SESSION['user']['id'])) {
            logEvent("User logged out: {$_SESSION['user']['username']}", 'auth');
        }
        
        $_SESSION = [];
        session_destroy();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
    }
    
    public function login($username, $password) {
        if (!$this->rateLimit('login_attempt')) {
            logEvent('Rate limit exceeded for IP: ' . $_SERVER['REMOTE_ADDR'], 'security');
            return ["error" => "Too many login attempts. Please try again later."];
        }
        
        $stmt = $this->pdo->prepare("SELECT id, username, password, full_name, email, role, is_active, 
                                    login_attempts, locked_until, email_verified, two_factor_enabled
                                    FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->rowCount() !== 1) {
            logEvent("Failed login attempt for username: $username", 'auth');
            return ["error" => "Invalid username or password"];
        }
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user['is_active']) {
            logEvent("Inactive account attempt: $username", 'security');
            return ["error" => "This account is inactive. Please contact administrator."];
        }
        
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $time_left = strtotime($user['locked_until']) - time();
            $minutes = ceil($time_left / 60);
            return ["error" => "Account locked. Please try again in $minutes minute(s)."];
        }
        
        if (!password_verify($password, $user['password'])) {
            $new_attempts = $user['login_attempts'] + 1;
            $locked_until = $new_attempts >= MAX_LOGIN_ATTEMPTS ? 
                date('Y-m-d H:i:s', time() + (LOGIN_LOCKOUT_MINUTES * 60)) : null;
            
            $updateStmt = $this->pdo->prepare("UPDATE users SET login_attempts = ?, locked_until = ? 
                                             WHERE id = ?");
            $updateStmt->execute([$new_attempts, $locked_until, $user['id']]);
            
            if ($locked_until) {
                logEvent("Account locked for user: $username", 'security');
            }
            
            logEvent("Failed login attempt for username: $username", 'auth');
            return ["error" => "Invalid username or password"];
        }
        
        if (!$user['email_verified']) {
            return [
                "error" => "Email not verified",
                "user_id" => $user['id'],
                "requires_verification" => true
            ];
        }
        
        if ($user['two_factor_enabled']) {
            $_SESSION['2fa_user'] = $user;
            return [
                "success" => true,
                "requires_2fa" => true
            ];
        }
        
        $this->completeLogin($user);
        return ["success" => true];
    }
    
    protected function completeLogin($user) {
        $stmt = $this->pdo->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL, last_login = NOW() 
                                   WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        session_regenerate_id(true);
        
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        
        logEvent("User {$user['username']} logged in successfully", 'auth');
    }
    
    public function verifyTwoFactor($code) {
        if (!isset($_SESSION['2fa_user'])) {
            return ["error" => "2FA verification failed"];
        }
        
        $user = $_SESSION['2fa_user'];
        unset($_SESSION['2fa_user']);
        
        // In production, verify TOTP code here
        $validCode = ($code === '123456'); // Replace with actual verification
        
        if (!$validCode) {
            logEvent("Failed 2FA attempt for user: {$user['username']}", 'auth');
            return ["error" => "Invalid verification code"];
        }
        
        $this->completeLogin($user);
        return ["success" => true];
    }

    public function initiatePasswordReset($email, $adminInitiated = false) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() !== 1) {
            // Don't reveal if email exists (security)
            return true;
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_id = $user['id'];
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + PASSWORD_RESET_EXPIRY);

        // Delete any existing tokens
        $this->pdo->prepare("DELETE FROM verification_tokens 
                            WHERE user_id = ? AND token_type = 'password_reset'")
                 ->execute([$user_id]);

        // Create new token
        $this->pdo->prepare("INSERT INTO verification_tokens 
                            (user_id, token, token_type, expires_at) 
                            VALUES (?, ?, 'password_reset', ?)")
                 ->execute([$user_id, $token, $expires]);

        // Send email
        EmailHelper::sendPasswordResetEmail($email, $token, $adminInitiated);

        return true;
    }

    public function completePasswordReset($token, $password) {
        // Validate token
        $stmt = $this->pdo->prepare("SELECT user_id FROM verification_tokens 
                                    WHERE token = ? AND token_type = 'password_reset' 
                                    AND expires_at > NOW() AND used_at IS NULL");
        $stmt->execute([$token]);
        
        if ($stmt->rowCount() !== 1) {
            return "Invalid or expired reset token";
        }

        $token_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_id = $token_data['user_id'];

        // Validate password
        $passwordValidation = $this->validatePassword($password);
        if ($passwordValidation !== true) {
            return $passwordValidation;
        }

        // Update password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $this->pdo->prepare("UPDATE users 
                           SET password = ?, 
                               password_changed_at = NOW(),
                               login_attempts = 0,
                               locked_until = NULL
                           WHERE id = ?")
                 ->execute([$hashed_password, $user_id]);

        // Mark token as used
        $this->pdo->prepare("UPDATE verification_tokens 
                           SET used_at = NOW() 
                           WHERE token = ?")
                 ->execute([$token]);

        logEvent("Password reset for user ID: $user_id", 'auth');
        return true;
    }

    public function adminForcePasswordReset($user_id) {
        if (!is_numeric($user_id)) {
            return false;
        }

        // Get user email
        $stmt = $this->pdo->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $email = $stmt->fetchColumn();

        if (!$email) {
            return false;
        }

        // Generate token and send email
        return $this->initiatePasswordReset($email, true);
    }
}

// Initialize auth system
$auth = new Auth($pdo);

// Helper functions (remain exactly the same as in original)
function isLoggedIn() {
    global $auth;
    return $auth->isLoggedIn();
}

function requireAuth() {
    global $auth;
    $auth->requireAuth();
}

function currentUser() {
    global $auth;
    return $auth->currentUser();
}

function hasRole($role) {
    global $auth;
    return $auth->hasRole($role);
}

function requireRole($role) {
    global $auth;
    $auth->requireRole($role);
}

function generateCSRFToken($purpose = 'general') {
    global $auth;
    return $auth->generateCSRFToken($purpose);
}

function verifyCSRFToken($token, $purpose = 'general') {
    global $auth;
    return $auth->verifyCSRFToken($token, $purpose);
}

function rateLimit($action, $maxAttempts = MAX_LOGIN_ATTEMPTS, $timeout = LOGIN_LOCKOUT_MINUTES * 60) {
    global $auth;
    return $auth->rateLimit($action, $maxAttempts, $timeout);
}

function validatePassword($password) {
    global $auth;
    return $auth->validatePassword($password);
}

function initiatePasswordReset($email, $adminInitiated = false) {
    global $auth;
    return $auth->initiatePasswordReset($email, $adminInitiated);
}

function completePasswordReset($token, $password) {
    global $auth;
    return $auth->completePasswordReset($token, $password);
}

function adminForcePasswordReset($user_id) {
    global $auth;
    return $auth->adminForcePasswordReset($user_id);
}

function logEvent($message, $category = 'application') {
    global $pdo;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $user_id = $_SESSION['user']['id'] ?? null;
    
    $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, ip_address, user_agent, category, message) 
                          VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $ip, $user_agent, $category, $message]);
}
?>