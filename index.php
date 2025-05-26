<?php
require_once __DIR__ . '/config.php';
$pageTitle = "Dashboard";
require_once __DIR__ . '/requires/header.php';
?>

<div class="wrapper d-flex">
  <?php require_once __DIR__ . '/requires/sidebar.php'; ?>
  
  <div class="main-content">
    <?php require_once __DIR__ . '/requires/topbar.php'; ?>
    
    <div class="content">
      <h2>Welcome to the Dashboard</h2>
      <div class="card mt-4">
        <div class="card-body">
          <h5 class="card-title">System Overview</h5>
          <p class="card-text">Your dashboard content goes here...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/requires/footer.php'; ?>