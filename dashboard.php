<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/db_plugin.php'; 

// Get today's date for filtering
$today = date('Y-m-d');
$currentMonth = date('Y-m');
$currentYear = date('Y');

// Dashboard statistics - using CRUD methods
$todaySales = $mysqli->common_select('sales', 'COUNT(id) as count, SUM(total) as amount', ["DATE(created_at)" => $today, 'is_deleted' => 0]);
$monthSales = $mysqli->common_select('sales', 'COUNT(id) as count, SUM(total) as amount', ["DATE_FORMAT(created_at, '%Y-%m')" => $currentMonth, 'is_deleted' => 0]);
$todayPurchases = $mysqli->common_select('purchase', 'COUNT(id) as count, SUM(total) as amount', ["DATE(created_at)" => $today, 'is_deleted' => 0]);
$monthPurchases = $mysqli->common_select('purchase', 'COUNT(id) as count, SUM(total) as amount', ["DATE_FORMAT(created_at, '%Y-%m')" => $currentMonth, 'is_deleted' => 0]);
$totalProducts = $mysqli->common_select('products', 'COUNT(id) as count', ['is_deleted' => 0]);
$totalCustomers = $mysqli->common_select('customers', 'COUNT(id) as count');
$totalSuppliers = $mysqli->common_select('suppliers', 'COUNT(id) as count');

// For complex queries, use the connection directly
$conn = $mysqli->getConnection();

// Low stock count
$lowStockQuery = $conn->query("
    SELECT COUNT(p.id) as count 
    FROM products p
    LEFT JOIN (
        SELECT 
            s.product_id,
            SUM(CASE WHEN s.change_type = 'purchase' THEN s.qty ELSE 0 END) -
            SUM(CASE WHEN s.change_type = 'sale' THEN s.qty ELSE 0 END) +
            COALESCE((
                SELECT SUM(CASE WHEN ia.adjustment_type = 'add' THEN ia.quantity ELSE -ia.quantity END)
                FROM inventory_adjustments ia
                WHERE ia.product_id = s.product_id
            ), 0) +
            SUM(CASE WHEN s.change_type = 'purchase_return' THEN s.qty ELSE 0 END) -
            SUM(CASE WHEN s.change_type = 'sales_return' THEN s.qty ELSE 0 END) as current_stock
        FROM stock s
        GROUP BY s.product_id
    ) stock_summary ON p.id = stock_summary.product_id
    WHERE p.is_deleted = 0 AND (stock_summary.current_stock < 10 OR stock_summary.current_stock IS NULL)
");
$lowStockCount = $lowStockQuery->fetch_assoc();
$lowStockQuery->free();

// Recent sales (last 5)
$recentSalesQuery = $conn->query("
    SELECT s.id, s.invoice_no, s.total, s.created_at, c.name as customer, u.username as cashier
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN users u ON s.user_id = u.id
    WHERE s.is_deleted = 0
    ORDER BY s.created_at DESC
    LIMIT 5
");
$recentSales = [];
while ($row = $recentSalesQuery->fetch_assoc()) {
    $recentSales[] = $row;
}
$recentSalesQuery->free();

// Recent purchases (last 5)
$recentPurchasesQuery = $conn->query("
    SELECT p.id, p.reference_no, p.total, p.created_at, s.name as supplier, u.username as purchaser
    FROM purchase p
    LEFT JOIN suppliers s ON p.supplier_id = s.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.is_deleted = 0
    ORDER BY p.created_at DESC
    LIMIT 5
");
$recentPurchases = [];
while ($row = $recentPurchasesQuery->fetch_assoc()) {
    $recentPurchases[] = $row;
}
$recentPurchasesQuery->free();

// Low stock items (less than 10)
$lowStockItemsQuery = $conn->query("
    SELECT 
        p.id, 
        p.name, 
        p.barcode,
        stock_summary.current_stock
    FROM products p
    LEFT JOIN (
        SELECT 
            s.product_id,
            SUM(CASE WHEN s.change_type = 'purchase' THEN s.qty ELSE 0 END) -
            SUM(CASE WHEN s.change_type = 'sale' THEN s.qty ELSE 0 END) +
            COALESCE((
                SELECT SUM(CASE WHEN ia.adjustment_type = 'add' THEN ia.quantity ELSE -ia.quantity END)
                FROM inventory_adjustments ia
                WHERE ia.product_id = s.product_id
            ), 0) +
            SUM(CASE WHEN s.change_type = 'purchase_return' THEN s.qty ELSE 0 END) -
            SUM(CASE WHEN s.change_type = 'sales_return' THEN s.qty ELSE 0 END) as current_stock
        FROM stock s
        GROUP BY s.product_id
    ) stock_summary ON p.id = stock_summary.product_id
    WHERE p.is_deleted = 0 AND (stock_summary.current_stock < 10 OR stock_summary.current_stock IS NULL)
    ORDER BY stock_summary.current_stock ASC
    LIMIT 5
");
$lowStockItems = [];
while ($row = $lowStockItemsQuery->fetch_assoc()) {
    $lowStockItems[] = $row;
}
$lowStockItemsQuery->free();

// Monthly sales data for chart
$monthlySalesQuery = $conn->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(id) as count,
        SUM(total) as amount
    FROM sales
    WHERE is_deleted = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
$monthlySales = [];
while ($row = $monthlySalesQuery->fetch_assoc()) {
    $monthlySales[] = $row;
}
$monthlySalesQuery->free();

// Monthly purchase data for chart
$monthlyPurchasesQuery = $conn->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(id) as count,
        SUM(total) as amount
    FROM purchase
    WHERE is_deleted = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
$monthlyPurchases = [];
while ($row = $monthlyPurchasesQuery->fetch_assoc()) {
    $monthlyPurchases[] = $row;
}
$monthlyPurchasesQuery->free();

// Prepare stats array
$stats = [
    'today_sales' => $todaySales['data'][0] ?? ['count' => 0, 'amount' => 0],
    'month_sales' => $monthSales['data'][0] ?? ['count' => 0, 'amount' => 0],
    'today_purchases' => $todayPurchases['data'][0] ?? ['count' => 0, 'amount' => 0],
    'month_purchases' => $monthPurchases['data'][0] ?? ['count' => 0, 'amount' => 0],
    'low_stock' => $lowStockCount['count'] ?? 0,
    'total_products' => $totalProducts['data'][0]->count ?? 0,
    'total_customers' => $totalCustomers['data'][0]->count ?? 0,
    'total_suppliers' => $totalSuppliers['data'][0]->count ?? 0
];

// Prepare chart data
$chartLabels = [];
$salesData = [];
$purchaseData = [];

foreach ($monthlySales as $sale) {
    $chartLabels[] = date('M Y', strtotime($sale['month']));
    $salesData[] = $sale['amount'];
}

foreach ($monthlyPurchases as $purchase) {
    $purchaseData[] = $purchase['amount'];
}

require_once __DIR__ . '/requires/header.php';
require_once __DIR__ . '/requires/topbar.php';
require_once __DIR__ . '/requires/sidebar.php';
?>

<!-- Rest of your dashboard HTML remains exactly the same -->

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Dashboard</h4>
            <ul class="breadcrumbs">
                <li class="nav-home">
                    <a href="<?= BASE_URL ?>dashboard.php">
                        <i class="flaticon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="flaticon-right-arrow"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Dashboard</a>
                </li>
            </ul>
        </div>

        <div class="row">
            <!-- Sales Summary Card -->
            <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center">
                                    <i class="flaticon-coins text-success"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category">Today's Sales</p>
                                    <h4 class="card-title"><?= number_format($stats['today_sales']['amount'] ?? 0, 2) ?></h4>
                                    <p class="card-subtitle"><?= $stats['today_sales']['count'] ?? 0 ?> transactions</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Sales Card -->
            <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center">
                                    <i class="flaticon-graph text-success"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category">Monthly Sales</p>
                                    <h4 class="card-title"><?= number_format($stats['month_sales']['amount'] ?? 0, 2) ?></h4>
                                    <p class="card-subtitle"><?= $stats['month_sales']['count'] ?? 0 ?> transactions</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Purchases Summary Card -->
            <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center">
                                    <i class="flaticon-box text-danger"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category">Today's Purchases</p>
                                    <h4 class="card-title"><?= number_format($stats['today_purchases']['amount'] ?? 0, 2) ?></h4>
                                    <p class="card-subtitle"><?= $stats['today_purchases']['count'] ?? 0 ?> orders</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Purchases Card -->
            <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center">
                                    <i class="flaticon-interface-6 text-danger"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category">Monthly Purchases</p>
                                    <h4 class="card-title"><?= number_format($stats['month_purchases']['amount'] ?? 0, 2) ?></h4>
                                    <p class="card-subtitle"><?= $stats['month_purchases']['count'] ?? 0 ?> orders</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Inventory Summary Card -->
            <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center">
                                    <i class="flaticon-warning text-warning"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category">Low Stock Items</p>
                                    <h4 class="card-title"><?= $stats['low_stock'] ?></h4>
                                    <p class="card-subtitle">Need attention</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Count Card -->
            <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center">
                                    <i class="flaticon-list text-primary"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category">Total Products</p>
                                    <h4 class="card-title"><?= $stats['total_products'] ?></h4>
                                    <p class="card-subtitle">In inventory</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customers Count Card -->
            <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center">
                                    <i class="flaticon-users text-info"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category">Customers</p>
                                    <h4 class="card-title"><?= $stats['total_customers'] ?></h4>
                                    <p class="card-subtitle">Registered</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Suppliers Count Card -->
            <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center">
                                    <i class="flaticon-truck text-secondary"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category">Suppliers</p>
                                    <h4 class="card-title"><?= $stats['total_suppliers'] ?></h4>
                                    <p class="card-subtitle">Active</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Sales & Purchases Chart -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="card-head-row">
                            <div class="card-title">Sales & Purchases Trend (Last 12 Months)</div>
                            <div class="card-tools">
                                <button class="btn btn-icon btn-link btn-primary btn-xs" onclick="window.print()">
                                    <span class="fa fa-print"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="salesPurchasesChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Quick Actions</div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <a href="<?= BASE_URL ?>modules/products/add_product.php" class="btn btn-primary btn-block">
                                    <i class="fas fa-plus"></i> Add Product
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="<?= BASE_URL ?>modules/sales/pos.php" class="btn btn-success btn-block">
                                    <i class="fas fa-cash-register"></i> New Sale
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="<?= BASE_URL ?>modules/purchase/add_purchase.php" class="btn btn-danger btn-block">
                                    <i class="fas fa-shopping-cart"></i> New Purchase
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="<?= BASE_URL ?>modules/customers/add_customer.php" class="btn btn-info btn-block">
                                    <i class="fas fa-user-plus"></i> Add Customer
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="<?= BASE_URL ?>modules/inventory/adjust_stock.php" class="btn btn-warning btn-block">
                                    <i class="fas fa-exchange-alt"></i> Adjust Stock
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="<?= BASE_URL ?>modules/sales/sales_report.php" class="btn btn-secondary btn-block">
                                    <i class="fas fa-chart-bar"></i> View Reports
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Sales -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-head-row">
                            <div class="card-title">Recent Sales</div>
                            <div class="card-tools">
                                <a href="<?= BASE_URL ?>modules/sales/view_sales.php" class="btn btn-info btn-sm">
                                    View All
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentSales as $sale): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($sale['invoice_no']) ?></td>
                                        <td><?= htmlspecialchars($sale['customer'] ?? 'Walk-in') ?></td>
                                        <td><?= number_format($sale['total'], 2) ?></td>
                                        <td><?= date('M d, H:i', strtotime($sale['created_at'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Purchases -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-head-row">
                            <div class="card-title">Recent Purchases</div>
                            <div class="card-tools">
                                <a href="<?= BASE_URL ?>modules/purchase/view_purchases.php" class="btn btn-info btn-sm">
                                    View All
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>Supplier</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentPurchases as $purchase): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($purchase['reference_no']) ?></td>
                                        <td><?= htmlspecialchars($purchase['supplier'] ?? 'N/A') ?></td>
                                        <td><?= number_format($purchase['total'], 2) ?></td>
                                        <td><?= date('M d, H:i', strtotime($purchase['created_at'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Low Stock Items -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-head-row">
                            <div class="card-title">Low Stock Items</div>
                            <div class="card-tools">
                                <a href="<?= BASE_URL ?>modules/inventory/low_stock.php" class="btn btn-warning btn-sm">
                                    View All
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($lowStockItems)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> No products are currently below minimum stock levels.
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Barcode</th>
                                        <th>Current Stock</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lowStockItems as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['name']) ?></td>
                                        <td><?= htmlspecialchars($item['barcode']) ?></td>
                                        <td>
                                            <span class="badge <?= $item['current_stock'] < 5 ? 'badge-danger' : 'badge-warning' ?>">
                                                <?= $item['current_stock'] ?? 0 ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?= BASE_URL ?>modules/products/edit_product.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="<?= BASE_URL ?>modules/inventory/adjust_stock.php?product_id=<?= $item['id'] ?>" class="btn btn-sm btn-success">
                                                <i class="fas fa-plus"></i> Add Stock
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/requires/footer.php'; ?>
<script>
$(document).ready(function() {
    // Sales & Purchases Chart
    var ctx = document.getElementById('salesPurchasesChart').getContext('2d');
    var chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [
                {
                    label: 'Sales',
                    data: <?= json_encode($salesData) ?>,
                    borderColor: '#177dff',
                    backgroundColor: 'rgba(23, 125, 255, 0.1)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#177dff',
                    tension: 0.1
                },
                {
                    label: 'Purchases',
                    data: <?= json_encode($purchaseData) ?>,
                    borderColor: '#f3545d',
                    backgroundColor: 'rgba(243, 84, 93, 0.1)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#f3545d',
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '৳' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '৳' + context.raw.toLocaleString();
                            return label;
                        }
                    }
                }
            }
        }
    });
});
</script>