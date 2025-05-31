<div class="sidebar" data-background-color="dark">
  <div class="sidebar-logo">
    <!-- Logo Header -->
    <div class="logo-header" data-background-color="dark">
      <a href="<?= BASE_URL ?>dashboard.php" class="logo">
        <img
          src="<?= BASE_URL ?>assets/img/kaiadmin/logo_light.svg"
          alt="navbar brand"
          class="navbar-brand"
          height="20"
        />
      </a>
      <div class="nav-toggle">
        <button class="btn btn-toggle toggle-sidebar">
          <i class="gg-menu-right"></i>
        </button>
        <button class="btn btn-toggle sidenav-toggler">
          <i class="gg-menu-left"></i>
        </button>
      </div>
      <button class="topbar-toggler more">
        <i class="gg-more-vertical-alt"></i>
      </button>
    </div>
    <!-- End Logo Header -->
  </div>
  <div class="sidebar-wrapper scrollbar scrollbar-inner">
    <div class="sidebar-content">
      <ul class="nav nav-secondary">
        <li class="nav-item active">
          <a
            data-bs-toggle="collapse"
            href="<?= BASE_URL ?>dashboard.php"
            class="collapsed"
            aria-expanded="false"
          >
            <i class="fas fa-home"></i>
            <p>Dashboard</p>
          </a>
        </li>
        <li class="nav-section">
          <span class="sidebar-mini-icon">
            <i class="fa fa-ellipsis-h"></i>
          </span>
          <h4 class="text-section">CRUD</h4>
        </li>
        <li class="nav-item">
            <a data-bs-toggle="collapse" href="#sidebarLayouts">
              <i class="fas fa-th-list"></i>
              <p>Categories</p>
              <span class="caret"></span>
            </a>
            <div class="collapse" id="sidebarLayouts">
              <ul class="nav nav-collapse">
                <li>
                  <a href="<?= BASE_URL ?>modules/categories/add_category.php">
                    <span class="sub-item">Add Category</span>
                  </a>
                </li>
                 <li>
                  <a href="<?= BASE_URL ?>modules/categories/view_categories.php">
                    <span class="sub-item">View Categories</span>
                  </a>
                </li>
                <li>
                  <a href="<?= BASE_URL ?>modules/categories/manage_brands.php">
                    <span class="sub-item">Manage Brand</span>
                  </a>
                </li>
              </ul>
            </div>
          </li>
      
        <li class="nav-item">
          <a data-bs-toggle="collapse" href="#base">
            <i class="fas fa-layer-group"></i>
            <p>Products</p>
            <span class="caret"></span>
          </a>
          <div class="collapse" id="base">
            <ul class="nav nav-collapse">
              <li>
                <a href="<?= BASE_URL ?>modules/products/add_product.php">
                  <span class="sub-item">Add Product</span>
                </a>
              </li>
              <li>
                <a href="<?= BASE_URL ?>modules/products/view_product.php">
                  <span class="sub-item">View Products</span>
                </a>
              </li>
              <li>
                <a href="<?= BASE_URL ?>modules/products/trash_product.php">
                  <span class="sub-item">Trash Products</span>
                </a>
              </li>
            </ul>
          </div>
        </li>
        <li class="nav-item">
          <a data-bs-toggle="collapse" href="#forms">
            <i class="fas fa-pen-square"></i>
            <p>POS</p>
            <span class="caret"></span>
          </a>
          <div class="collapse" id="forms">
            <ul class="nav nav-collapse">
              <li>
                <a href="sales.php">
                  <span class="sub-item">Sales</span>
                </a>
              </li>
                <li>
                <a href="sales_report.php">
                  <span class="sub-item">Sales Report</span>
                </a>
              </li>
            </ul>
          </div>
        </li>
        <li class="nav-item">
          <a data-bs-toggle="collapse" href="#tables">
            <i class="fas fa-map-marker-alt"></i>
            <p>Suppliers</p>
            <span class="caret"></span>
          </a>
          <div class="collapse" id="tables">
            <ul class="nav nav-collapse">
              <li>
                <a href="<?= BASE_URL ?>modules/suppliers/add_supplier.php">
                  <span class="sub-item">Add Supplier</span>
                </a>
              </li>
              <li>
                <a href="<?= BASE_URL ?>modules/suppliers/view_suppliers.php">
                  <span class="sub-item">View Suppliers</span>
                </a>
              </li>
            </ul>
          </div>
        </li>
        <li class="nav-item">
          <a data-bs-toggle="collapse" href="#maps">
            <i class="fas fa-table"></i>
            <p>Customers</p>
            <span class="caret"></span>
          </a>
          <div class="collapse" id="maps">
            <ul class="nav nav-collapse">
              <li>
                <a href="<?= BASE_URL ?>modules/customers/add_customer.php">
                  <span class="sub-item">Add Customer</span>
                </a>
              </li>
              <li>
                <a href="<?= BASE_URL ?>modules/customers/view_customers.php">
                  <span class="sub-item">View Customers</span>
                </a>
              </li>
            </ul>
          </div>
        </li>
        <li class="nav-item">
          <a data-bs-toggle="collapse" href="#charts">
            <i class="fas fa-desktop"></i>
            <p>Admin Panel</p>
            <span class="caret"></span>
          </a>
          <div class="collapse" id="charts">
            <ul class="nav nav-collapse">
              <li>
                <a href="<?= BASE_URL ?>admin/register.php">
                  <span class="sub-item">Add User</span>
                </a>
              </li>
              <li>
                <a href="<?= BASE_URL ?>admin/view_user.php">
                  <span class="sub-item">View Users</span>
                </a>
              </li>
              <li>
                <a href="<?= BASE_URL ?>admin/edit_user.php">
                  <span class="sub-item">Manage Users</span>
                </a>
              </li>
            </ul>
          </div>
        </li>
        <li class="nav-item">
          <a href="stock_logs.php">
              <i class="far fa-chart-bar"></i>
            <p>Inventory</p>
          </a>
        </li>
      </ul>
    </div>
  </div>
</div>
      <!-- End Sidebar -->