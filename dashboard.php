 <?php 
    session_start();
    if(!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
        header("Location: login.php");
        exit();
    }
    require_once __DIR__ . '/requires/header.php'; 
    require_once __DIR__ . '/requires/sidebar.php'; 
    require_once __DIR__ . '/requires/topbar.php'; 
?>
<div class="container">
    <div class="page-inner">
           <h2>Welcome to the Dashboard</h2>
    </div>
</div>
 <?php require_once __DIR__ . '/requires/footer.php'; ?>