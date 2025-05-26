<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../requires/header.php';
?>

<div class="wrapper d-flex">
  <?php require_once __DIR__ . '/../../requires/sidebar.php'; ?>
  
  <div class="main-content">
    <?php require_once __DIR__ . '/../../requires/topbar.php'; ?>
    
    <div class="content">
      <h2>Add New Category</h2>
      <div class="card mt-4">
        <div class="card-body">
          <form action="" method="POST">
            <div class="mb-3">
              <label for="categoryName" class="form-label">Category Name</label>
              <input type="text" class="form-control" id="categoryName" name="categoryName" required>
            </div>
            <div class="mb-3">
              <label for="categoryDescription" class="form-label">Description</label>
              <textarea class="form-control" id="categoryDescription" name="categoryDescription" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Add Category</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../requires/footer.php'; ?>