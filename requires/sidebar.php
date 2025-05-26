<div id="sidebar" class="sidebar">
  <div class="logo">
    <div id="logo-text" class="logo-circle">POS</div>
  </div>

  <div class="menu">
    <ul class="nav flex-column">
      <!-- Dashboard -->
      <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>index.php" class="nav-link">
          <i class="bi bi-speedometer2"></i> <span class="menu-text">Dashboard</span>
        </a>
      </li>

      <!-- Category -->
      <li class="nav-item">
        <a class="nav-link has-submenu" href="#" id="categoryToggle">
          <i class="bi bi-folder-fill"></i> <span class="menu-text">Category</span>
        </a>
        <ul class="submenu" id="categoryMenu">
          <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'add_c.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>modules/categories/add_c.php" class="nav-link">
              <i class="bi bi-plus-square"></i> <span class="menu-text">Add Category</span>
            </a>
          </li>
          <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'view.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>modules/categories/view.php" class="nav-link">
              <i class="bi bi-eye-fill"></i> <span class="menu-text">View Categories</span>
            </a>
          </li>
        </ul>
      </li>

      <!-- Products -->
      <li class="nav-item">
        <a class="nav-link has-submenu" href="#" id="productsToggle">
          <i class="bi bi-box-seam"></i> <span class="menu-text">Products</span>
        </a>
        <ul class="submenu" id="productsMenu">
          <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'add.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>modules/products/add.php" class="nav-link">
              <i class="bi bi-plus-square"></i> <span class="menu-text">Add Product</span>
            </a>
          </li>
          <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'view.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>modules/products/view.php" class="nav-link">
              <i class="bi bi-eye-fill"></i> <span class="menu-text">View Products</span>
            </a>
          </li>
        </ul>
      </li>

      <!-- POS -->
      <li class="nav-item">
        <a class="nav-link has-submenu" href="#" id="posToggle">
          <i class="bi bi-cash-stack"></i> <span class="menu-text">POS</span>
        </a>
        <ul class="submenu" id="posMenu">
          <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>modules/sales/pos.php" class="nav-link">
              <i class="bi bi-cart-plus"></i> <span class="menu-text">Sales</span>
            </a>
          </li>
          <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'sales_report.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>modules/reports/sales_report.php" class="nav-link">
              <i class="bi bi-graph-up"></i> <span class="menu-text">Sales Report</span>
            </a>
          </li>
        </ul>
      </li>

      <!-- Registration -->
      <li class="nav-item">
        <a class="nav-link has-submenu" href="#" id="registrationToggle">
          <i class="bi bi-people-fill"></i> <span class="menu-text">Registration</span>
        </a>
        <ul class="submenu" id="registrationMenu">
          <!-- Users -->
          <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'add.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>modules/users/add.php" class="nav-link">
              <i class="bi bi-person-plus"></i> <span class="menu-text">Add User</span>
            </a>
          </li>
          <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'view.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>modules/users/view.php" class="nav-link">
              <i class="bi bi-people"></i> <span class="menu-text">View Users</span>
            </a>
          </li>

          <!-- Customers -->
          <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'add.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>modules/customers/add.php" class="nav-link">
              <i class="bi bi-person-plus"></i> <span class="menu-text">Add Customer</span>
            </a>
          </li>
          <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'view.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>modules/customers/view.php" class="nav-link">
              <i class="bi bi-people"></i> <span class="menu-text">View Customers</span>
            </a>
          </li>

          <!-- Suppliers -->
          <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'add.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>modules/suppliers/add.php" class="nav-link">
              <i class="bi bi-person-plus"></i> <span class="menu-text">Add Supplier</span>
            </a>
          </li>
          <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'view.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>modules/suppliers/view.php" class="nav-link">
              <i class="bi bi-people"></i> <span class="menu-text">View Suppliers</span>
            </a>
          </li>
        </ul>
      </li>

      <!-- Import -->
      <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'Stock_logs.php' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>modules/inventory/import.php" class="nav-link">
          <i class="bi bi-file-earmark-arrow-up-fill"></i> <span class="menu-text">Import Record</span>
        </a>
      </li>

      <!-- Labels -->
      <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'labels.php' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>modules/inventory/labels.php" class="nav-link">
          <i class="bi bi-tag-fill"></i> <span class="menu-text">Product Label</span>
        </a>
      </li>
    </ul>
  </div>

  <div class="sidebar-footer">
    © <?= date('Y') ?> POS System
  </div>
</div>
