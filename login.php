 <?php
session_start();
require_once __DIR__ . '/db_plugin.php';
 // Redirect if already logged in
if (isset($_SESSION['log_user_status']) && $_SESSION['log_user_status'] === true) {
    header("Location: index.php");
    exit();
}

// Handle login attempt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($username) || empty($password)) {
        $error = "Username and password are required";
    } else {
        // Get user with login attempt tracking
        $res = $mysqli->common_select('users', 
            'id, username, full_name, email, role, is_active, password, login_attempts, locked_until, profile_pic', 
            ['username' => $username]
        );
        
        if ($res['error'] == 0 && count($res['data']) > 0) {
            $user = $res['data'][0];
            
            // Check if account is locked
            if ($user->locked_until && strtotime($user->locked_until) > time()) {
                $error = "Account locked. Try again later.";
            } else {
                // Verify password
                if (password_verify($password, $user->password)) {
                    // Reset login attempts on successful login
                    $mysqli->common_update('users', 
                        [
                            'login_attempts' => 0,
                            'locked_until' => null,
                            'last_login' => date('Y-m-d H:i:s'),
                            'last_login_ip' => $_SERVER['REMOTE_ADDR']
                        ], 
                        ['id' => $user->id]
                    );
                    
                    if ($user->is_active == 0) {
                        $error = "Your account is not active";
                    } else {
                        // Set session
                        $_SESSION['user'] = $user;
                        $_SESSION['role'] = $user->role;
                        $_SESSION['log_user_status'] = true;
                        
                        // Log successful login
                        $mysqli->common_insert('security_logs', [
                            'user_id' => $user->id,
                            'ip_address' => $_SERVER['REMOTE_ADDR'],
                            'action' => 'login',
                            'details' => 'Successful login',
                            'status' => 'success'
                        ]);
                        
                        header("Location: index.php");
                        exit();
                    }
                } else {
                    // Increment failed login attempts
                    $attempts = $user->login_attempts + 1;
                    $lock_until = null;
                    
                    if ($attempts >= 5) {
                        $lock_until = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                        $error = "Too many failed attempts. Account locked for 5
                         minutes.";
                    } else {
                        $error = "Invalid username or password";
                    }
                    
                    $mysqli->common_update('users', 
                        [
                            'login_attempts' => $attempts,
                            'locked_until' => $lock_until
                        ], 
                        ['id' => $user->id]
                    );
                    
                    // Log failed attempt
                    $mysqli->common_insert('security_logs', [
                        'user_id' => $user->id,
                        'ip_address' => $_SERVER['REMOTE_ADDR'],
                        'action' => 'login',
                        'details' => 'Failed login attempt',
                        'status' => 'failure'
                    ]);
                }
            }
        } else {
            $error = "Invalid username or password";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In</title>
<style>
body{
  display: grid;
  justify-content: center;
  background-color: #212121;
  background-image: url('assets/img/asd.jpg'); background-size: cover;
}

.container {
  position: relative;
  width: 400px;
  height: 400px;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 50%;
  overflow: hidden;
}

.container span {
  position: absolute;
  left: 0;
  width: 32px;
  height: 6px;
  background: #2c4766;
  border-radius: 80px;
  transform-origin: 200px;
  transform: rotate(calc(var(--i) * (360deg / 50)));
  animation: blink 3s linear infinite;
  animation-delay: calc(var(--i) * (3s / 50));
}

@keyframes blink {
  0% {
    background: #0ef;
  }
  25% {
    background: #2c4766;
  }
}

.login-box {
  position: absolute;
  width: 80%;
  max-width: 300px;
  z-index: 1;
  padding: 20px;
  border-radius: 20px;
}

form {
  width: 100%;
  padding: 0 10px;
}

h2 {
  font-size: 1.8em;
  color: #0ef;
  text-align: center;
  margin-bottom: 10px;
}

.input-box {
  position: relative;
  margin: 15px 0;
}

input {
  width: 100%;
  height: 45px;
  background: transparent;
  border: 2px solid #2c4766;
  outline: none;
  border-radius: 40px;
  font-size: 1em;
  color: #fff;
  padding: 0 15px;
  transition: 0.5s ease;
}

input:focus {
  border-color: #0ef;
}

input[value]:not([value=""]) ~ label,
.stay-up {
  top: -10px;
  font-size: 0.8em;
  background: #1f293a;
  padding: 0 6px;
  color: #0ef;
}

label {
  position: absolute;
  top: 50%;
  left: 15px;
  transform: translateY(-50%);
  font-size: 1em;
  pointer-events: none;
  transition: 0.5s ease;
  color: #fff;
}

.forgot-pass {
  margin: -10px 0 10px;
  text-align: center;
}

.forgot-pass a {
  font-size: 0.85em;
  color: #fff;
  text-decoration: none;
}

.btn {
  width: 100%;
  height: 45px;
  background: #0ef;
  border: none;
  outline: none;
  border-radius: 40px;
  cursor: pointer;
  font-size: 1em;
  color: #1f293a;
  font-weight: 600;
}

</style>   
</head>
<body>

<div class="container">
  <div class="login-box">
    <h2>Login</h2>
    <form action="" method="post">
      <div class="input-box">
        <input value="" required="" type="text" name="username" />
        <label>Username</label>
      </div>
      <div class="input-box">
        <input value="" required="" type="password" name="password"/>
        <label>Password</label>
      </div>
      <div class="forgot-pass">
        <a href="#">Forgot your password?</a>
      </div>
      <button class="btn" type="submit">Login</button>
    </form>
  </div>

  <span style="--i:0;"></span>
  <span style="--i:1;"></span>
  <span style="--i:2;"></span>
  <span style="--i:3;"></span>
  <span style="--i:4;"></span>
  <span style="--i:5;"></span>
  <span style="--i:6;"></span>
  <span style="--i:7;"></span>
  <span style="--i:8;"></span>
  <span style="--i:9;"></span>
  <span style="--i:10;"></span>
  <span style="--i:11;"></span>
  <span style="--i:12;"></span>
  <span style="--i:13;"></span>
  <span style="--i:14;"></span>
  <span style="--i:15;"></span>
  <span style="--i:16;"></span>
  <span style="--i:17;"></span>
  <span style="--i:18;"></span>
  <span style="--i:19;"></span>
  <span style="--i:20;"></span>
  <span style="--i:21;"></span>
  <span style="--i:22;"></span>
  <span style="--i:23;"></span>
  <span style="--i:24;"></span>
  <span style="--i:25;"></span>
  <span style="--i:26;"></span>
  <span style="--i:27;"></span>
  <span style="--i:28;"></span>
  <span style="--i:29;"></span>
  <span style="--i:30;"></span>
  <span style="--i:31;"></span>
  <span style="--i:32;"></span>
  <span style="--i:33;"></span>
  <span style="--i:34;"></span>
  <span style="--i:35;"></span>
  <span style="--i:36;"></span>
  <span style="--i:37;"></span>
  <span style="--i:38;"></span>
  <span style="--i:39;"></span>
  <span style="--i:40;"></span>
  <span style="--i:41;"></span>
  <span style="--i:42;"></span>
  <span style="--i:43;"></span>
  <span style="--i:44;"></span>
  <span style="--i:45;"></span>
  <span style="--i:46;"></span>
  <span style="--i:47;"></span>
  <span style="--i:48;"></span>
  <span style="--i:49;"></span>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.input-box input');
    
    inputs.forEach(input => {
        // Check if input has value on page load
        if (input.value.trim() !== '') {
            input.parentElement.querySelector('label').classList.add('stay-up');
        }
        
        // Add event listener for input changes
        input.addEventListener('input', function() {
            const label = this.parentElement.querySelector('label');
            if (this.value.trim() !== '') {
                label.classList.add('stay-up');
            } else {
                label.classList.remove('stay-up');
            }
        });
    });
});
</script>
</body>
</html>