 <?php
 session_start();
  require_once __DIR__ . '/db_plugin.php';
 ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In</title>
    <link rel="stylesheet" href="assets/css/new_css.css">
</head>
<body style="background-image: url('assets/img/asd.jpg'); background-size: cover;">

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
  <?php
  if($_POST){
    $_POST['password'] = sha1($_POST['password']);
    
    $res = $mysqli->common_select('users','id,username,full_name,email,role,is_active',array(
      'username' => $_POST['username'],
      'password' => $_POST['password']
    ));
    
    if($res['error']==0 && count($res['data']) > 0){
      $user = $res['data'][0];
      
      if($user->is_active==0){
        echo "<script>alert('Your account is not active')</script>";
      } else {

        $_SESSION['user'] = $user;
        $_SESSION['role'] = $user->role;
        $_SESSION['log_user_status'] = true;
        
        $mysqli->common_update('users', 
          array(
            'last_login' => date('Y-m-d H:i:s'),
            'last_login_ip' => $_SERVER['REMOTE_ADDR']
          ), 
          array('id' => $user->id)
        );
        
        echo "<script>location.href='dashboard.php'</script>";
      }
    } else {
      echo "<script>alert('Invalid username or password')</script>";
    }
  }
  ?>

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
</body>
</html>