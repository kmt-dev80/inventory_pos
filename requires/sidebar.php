<div class="sidebar" data-background-color="dark">
  <div class="sidebar-logo">
    <!-- Logo Header -->
    <div class="logo-header" data-background-color="dark">
        <a href="<?= BASE_URL ?>index.php" class="logo">
          <img src="<?= BASE_URL ?>assets/img/invpos.gif" alt="navbar brand" class="navbar-brand" height="30" onerror="this.src='<?= BASE_URL ?>assets/img/invpos.gif'; this.onerror=null;" />
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
          <a href="<?= BASE_URL ?>index.php">
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
            <i class="bi bi-cart-check"></i>
            <p>POS</p>
            <span class="caret"></span>
          </a>
          <div class="collapse" id="forms">
            <ul class="nav nav-collapse">
              <li>
                <a href="<?= BASE_URL ?>modules/sales/pos.php">
                  <span class="sub-item">Sales</span>
                </a>
              </li>
              <li>
                <a href="<?= BASE_URL ?>modules/sales/view_sales.php">
                  <span class="sub-item">View Sales</span>
                </a>
              </li>
               <li>
                <a href="<?= BASE_URL ?>modules/sales/sales_report.php">
                  <span class="sub-item">Sales Report</span>
                </a>
              </li>
              <li>
                <a href="<?= BASE_URL ?>modules/sales/view_sales_return.php">
                  <span class="sub-item">View Returns</span>
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
          <a data-bs-toggle="collapse" href="#submenu">
            <i class="fas fa-bars"></i>
            <p>Purchase</p>
            <span class="caret"></span>
          </a>
          <div class="collapse" id="submenu">
            <ul class="nav nav-collapse">
                 <li>
                <a href="<?= BASE_URL ?>modules/purchase/add_purchase.php">
                  <span class="sub-item">Add Purchase</span>
                </a>
              </li>
              <li>
                <a href="<?= BASE_URL ?>modules/purchase/view_purchases.php">
                  <span class="sub-item">View Purchases</span>
                </a>
              </li>
              <li>
                <a href="<?= BASE_URL ?>modules/purchase/view_purchase_returns.php">
                  <span class="sub-item">View Returns</span>
                </a>
              </li>
            </ul>
          </div>
        </li>
        <li class="nav-item">
          <a data-bs-toggle="collapse" href="#maps">
            <i class="bi bi-person-plus"></i>
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
            </ul>
          </div>
        </li>
       <li class="nav-item">
          <a data-bs-toggle="collapse" href="#subnav1">
            <i class="bi bi-shop"></i>
            <span class="sub-item">Inventory</span>
            <span class="caret"></span>
          </a>
          <div class="collapse" id="subnav1">
            <ul class="nav nav-collapse subnav">
              <li>
                <a href="<?= BASE_URL ?>modules/inventory/stock_logs.php">
                  <span class="sub-item">View Logs</span>
                </a>
              </li>
              <li>
                <a href="<?= BASE_URL ?>modules/inventory/stock_report.php">
                  <span class="sub-item">Stock Report</span>
                </a>
              </li>
              <li>
                <a href="<?= BASE_URL ?>modules/inventory/low_stock.php">
                  <span class="sub-item">View Low-Stock</span>
                </a>
              </li>
              <li>
                <a href="<?= BASE_URL ?>modules/inventory/adjust_stock.php">
                  <span class="sub-item">Adjust Stock</span>
                </a>
              </li>
              <li>
                <a href="<?= BASE_URL ?>modules/inventory/adjustments_list.php">
                  <span class="sub-item">Adjustment List</span>
                </a>
              </li>
            </ul>
          </div>
        </li>
        <li class="nav-item">
          <a href="<?= BASE_URL ?>modules/inventory/profit_loss.php">
             <i class="bi bi-bar-chart-line"></i>
            <span class="sub-item">Financial Report</span>
          </a>
        </li>
      </ul>
    </div>
  </div>
</div>
      <!-- End Sidebar -->