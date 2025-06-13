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
// For complex queries, use the connection directly
$conn = $mysqli->getConnection();

$todaySalesResult = $conn->query("
    SELECT COUNT(id) as count, COALESCE(SUM(total), 0) as amount 
    FROM sales 
    WHERE DATE(created_at) = CURDATE() AND is_deleted = 0
");
$todaySales = $todaySalesResult ? $todaySalesResult->fetch_object() : null;


$monthSalesResult = $conn->query("
    SELECT COUNT(id) as count, COALESCE(SUM(total), 0) as amount 
    FROM sales 
    WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') 
    AND is_deleted = 0
");
$monthSales = $monthSalesResult ? $monthSalesResult->fetch_object() : null;


$todayPurchasesResult = $conn->query("
    SELECT COUNT(id) as count, COALESCE(SUM(total), 0) as amount 
    FROM purchase 
    WHERE DATE(created_at) = CURDATE() AND is_deleted = 0
");
$todayPurchases = $todayPurchasesResult ? $todayPurchasesResult->fetch_object() : null;


$monthPurchasesResult = $conn->query("
    SELECT COUNT(id) as count, COALESCE(SUM(total), 0) as amount 
    FROM purchase 
    WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') 
    AND is_deleted = 0
");
$monthPurchases = $monthPurchasesResult ? $monthPurchasesResult->fetch_object() : null;
$totalProducts = $mysqli->common_select('products', 'COUNT(id) as count', ['is_deleted' => 0]);
$totalCustomers = $mysqli->common_select('customers', 'COUNT(id) as count');
$totalSuppliers = $mysqli->common_select('suppliers', 'COUNT(id) as count');

$lowStockQuery = $conn->query("
    SELECT COUNT(*) as count 
    FROM (
        SELECT p.id
        FROM products p
        LEFT JOIN stock s ON p.id = s.product_id
        WHERE p.is_deleted = 0
        GROUP BY p.id
        HAVING COALESCE(SUM(s.qty), 0) < 10
    ) as low_stock_items
");
$lowStockCount = $lowStockQuery->fetch_assoc();
$lowStockQuery->free();


// Low stock items (less than 10)
$lowStockItemsQuery = $conn->query("
    SELECT 
        p.id, 
        p.name, 
        p.barcode,
        COALESCE(SUM(s.qty), 0) as current_stock
    FROM products p
    LEFT JOIN stock s ON p.id = s.product_id
    WHERE p.is_deleted = 0
    GROUP BY p.id
    HAVING current_stock < 10
    ORDER BY current_stock ASC
    LIMIT 5
");
$lowStockItems = [];
while ($row = $lowStockItemsQuery->fetch_assoc()) {
    $lowStockItems[] = $row;
}
$lowStockItemsQuery->free();
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
    'today_sales' => [
        'count' => $todaySales->count ?? 0,
        'amount' => $todaySales->amount ?? 0
    ],
    'month_sales' => [
        'count' => $monthSales->count ?? 0,
        'amount' => $monthSales->amount ?? 0
    ],
    'today_purchases' => [
        'count' => $todayPurchases->count ?? 0,
        'amount' => $todayPurchases->amount ?? 0
    ],
    'month_purchases' => [
        'count' => $monthPurchases->count ?? 0,
        'amount' => $monthPurchases->amount ?? 0
    ],
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
<style>
    /* Gradient Cards */
    .card-gradient {
        background: linear-gradient(135deg, #177dff 0%, #4a9eff 100%);
        color: white;
    }
    
    .card-gradient-secondary {
        background: linear-gradient(135deg, #716aca 0%, #9b8cff 100%);
        color: white;
    }
    
    .card-gradient-danger {
        background: linear-gradient(135deg, #f3545d 0%, #ff7b84 100%);
        color: white;
    }
    
    .card-gradient-warning {
        background: linear-gradient(135deg, #fdab3d 0%, #ffc97b 100%);
        color: white;
    }
    
    .card-gradient-info {
        background: linear-gradient(135deg, #36a3f7 0%, #6ec0ff 100%);
        color: white;
    }
    
    .card-gradient-primary {
        background: linear-gradient(135deg, #5867dd 0%, #7984ea 100%);
        color: white;
    }
    
    .card-gradient-success {
        background: linear-gradient(135deg, #34bfa3 0%, #5bd1b9 100%);
        color: white;
    }
    
    .card-gradient-dark {
        background: linear-gradient(135deg, #464d69 0%, #6a718f 100%);
        color: white;
    }
    
    /* Card Footer Gradients */
    .bg-gradient-footer {
        background: linear-gradient(135deg, #1267d8 0%, #3a8cff 100%);
        color: white;
    }
    
    .bg-gradient-secondary-footer {
        background: linear-gradient(135deg, #5d56b8 0%, #847de8 100%);
        color: white;
    }
    
    .bg-gradient-danger-footer {
        background: linear-gradient(135deg, #d13a43 0%, #f06a72 100%);
        color: white;
    }
    
    .bg-gradient-warning-footer {
        background: linear-gradient(135deg, #e89b2b 0%, #ffc062 100%);
        color: white;
    }
    
    .bg-gradient-info-footer {
        background: linear-gradient(135deg, #2a92e5 0%, #5bb1ff 100%);
        color: white;
    }
    
    .bg-gradient-primary-footer {
        background: linear-gradient(135deg, #4a52c3 0%, #6d76e0 100%);
        color: white;
    }
    
    .bg-gradient-success-footer {
        background: linear-gradient(135deg, #2ba98b 0%, #4cc8ab 100%);
        color: white;
    }
    
    .bg-gradient-dark-footer {
        background: linear-gradient(135deg, #3a4059 0%, #5d6485 100%);
        color: white;
    }
    
    /* Action Buttons */
    .btn-action {
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        padding: 10px 5px;
    }
    
    .btn-action:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .btn-action .badge {
        position: absolute;
        top: -5px;
        right: -5px;
        font-size: 0.6rem;
    }
    
    /* Tables */
    .table-sales tbody tr:hover {
        background-color: rgba(23, 125, 255, 0.05);
    }
    
    .table-purchases tbody tr:hover {
        background-color: rgba(243, 84, 93, 0.05);
    }
    
    .table-alert tbody tr:hover {
        background-color: rgba(253, 171, 61, 0.05);
    }
    
    /* Chart Card */
    .card-chart {
        border-top: 3px solid #177dff;
    }
    
    /* Alert Card */
    .card-alert {
        border-top: 3px solid #fdab3d;
    }
    
    /* Animation Classes */
    .animate__animated {
        animation-duration: 0.5s;
    }
    
    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .card-body .numbers h4.card-title {
            font-size: 1.2rem;
        }
        
        .btn-action {
            padding: 8px 5px;
            font-size: 0.8rem;
        }
    }
</style>
<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Dashboard</h4>
        </div>

        <div class="row">
            <!-- Sales Summary Card -->
            <div class="col-sm-6 col-md-3 animate__animated animate__fadeInLeft">
                <div class="card card-stats card-round card-gradient">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-5">
                                <div class="icon-big text-center">
                                    <i class="flaticon-analytics text-white"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category text-white">Today's Sales</p>
                                    <h4 class="card-title text-white">৳<?= number_format($stats['today_sales']['amount'] ?? 0, 2) ?></h4>
                                    <p class="card-subtitle text-white-50"><?= $stats['today_sales']['count'] ?? 0 ?> transactions</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-gradient-footer">
                        <div class="stats">
                            <i class="fas fa-sync-alt"></i> Updated just now
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Sales Card -->
            <div class="col-sm-6 col-md-3 animate__animated animate__fadeInLeft animate__delay-1">
                <div class="card card-stats card-round card-gradient-secondary">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-5">
                                <div class="icon-big text-center">
                                    <i class="flaticon-growth text-white"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category text-white">Monthly Sales</p>
                                    <h4 class="card-title text-white">৳<?= number_format($stats['month_sales']['amount'] ?? 0, 2) ?></h4>
                                    <p class="card-subtitle text-white-50"><?= $stats['month_sales']['count'] ?? 0 ?> transactions</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-gradient-secondary-footer">
                        <div class="stats">
                            <i class="far fa-calendar-alt"></i> Current month
                        </div>
                    </div>
                </div>
            </div>

            <!-- Purchases Summary Card -->
            <div class="col-sm-6 col-md-3 animate__animated animate__fadeInRight animate__delay-1">
                <div class="card card-stats card-round card-gradient-danger">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-5">
                                <div class="icon-big text-center">
                                    <i class="flaticon-box-1 text-white"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category text-white">Today's Purchases</p>
                                    <h4 class="card-title text-white">৳<?= number_format($stats['today_purchases']['amount'] ?? 0, 2) ?></h4>
                                    <p class="card-subtitle text-white-50"><?= $stats['today_purchases']['count'] ?? 0 ?> orders</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-gradient-danger-footer">
                        <div class="stats">
                            <i class="fas fa-sync-alt"></i> Updated just now
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Purchases Card -->
            <div class="col-sm-6 col-md-3 animate__animated animate__fadeInRight">
                <div class="card card-stats card-round card-gradient-warning">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-5">
                                <div class="icon-big text-center">
                                    <i class="flaticon-shopping-bag text-white"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category text-white">Monthly Purchases</p>
                                    <h4 class="card-title text-white">৳<?= number_format($stats['month_purchases']['amount'] ?? 0, 2) ?></h4>
                                    <p class="card-subtitle text-white-50"><?= $stats['month_purchases']['count'] ?? 0 ?> orders</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-gradient-warning-footer">
                        <div class="stats">
                            <i class="far fa-calendar-alt"></i> Current month
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Inventory Summary Card -->
            <div class="col-sm-6 col-md-3 animate__animated animate__fadeInUp">
                <div class="card card-stats card-round card-gradient-info">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-5">
                                <div class="icon-big text-center">
                                    <i class="flaticon-alert text-white"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category text-white">Low Stock Items</p>
                                    <h4 class="card-title text-white"><?= $stats['low_stock'] ?></h4>
                                    <p class="card-subtitle text-white-50">Need attention</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-gradient-info-footer">
                        <div class="stats">
                            <i class="fas fa-exclamation-triangle"></i> Threshold: 10 units
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Count Card -->
            <div class="col-sm-6 col-md-3 animate__animated animate__fadeInUp animate__delay-1">
                <div class="card card-stats card-round card-gradient-primary">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-5">
                                <div class="icon-big text-center">
                                    <i class="flaticon-list-1 text-white"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category text-white">Total Products</p>
                                    <h4 class="card-title text-white"><?= $stats['total_products'] ?></h4>
                                    <p class="card-subtitle text-white-50">In inventory</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-gradient-primary-footer">
                        <div class="stats">
                            <i class="fas fa-database"></i> All products
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customers Count Card -->
            <div class="col-sm-6 col-md-3 animate__animated animate__fadeInUp animate__delay-2">
                <div class="card card-stats card-round card-gradient-success">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-5">
                                <div class="icon-big text-center">
                                    <i class="flaticon-user text-white"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category text-white">Customers</p>
                                    <h4 class="card-title text-white"><?= $stats['total_customers'] ?></h4>
                                    <p class="card-subtitle text-white-50">Registered</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-gradient-success-footer">
                        <div class="stats">
                            <i class="fas fa-user-friends"></i> Total customers
                        </div>
                    </div>
                </div>
            </div>

            <!-- Suppliers Count Card -->
            <div class="col-sm-6 col-md-3 animate__animated animate__fadeInUp animate__delay-3">
                <div class="card card-stats card-round card-gradient-dark">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-5">
                                <div class="icon-big text-center">
                                    <i class="flaticon-delivery-truck text-white"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category text-white">Suppliers</p>
                                    <h4 class="card-title text-white"><?= $stats['total_suppliers'] ?></h4>
                                    <p class="card-subtitle text-white-50">Active</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-gradient-dark-footer">
                        <div class="stats">
                            <i class="fas fa-truck-loading"></i> All suppliers
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Sales & Purchases Chart -->
            <div class="col-md-8 animate__animated animate__fadeIn">
                <div class="card card-chart">
                    <div class="card-header">
                        <div class="card-head-row">
                            <div class="card-title">Sales & Purchases Trend (Last 12 Months)</div>
                            <div class="card-tools">
                                <button class="btn btn-icon btn-round btn-light btn-sm" onclick="window.print()">
                                    <i class="fas fa-print"></i>
                                </button>
                                <button class="btn btn-icon btn-round btn-light btn-sm" onclick="refreshChart()">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="salesPurchasesChart" height="300"></canvas>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="stats">
                            <i class="fas fa-info-circle"></i> Hover over the chart for detailed information
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-md-4 animate__animated animate__fadeIn animate__delay-1">
                <div class="card card-action">
                    <div class="card-header">
                        <div class="card-title">Quick Actions</div>
                        <div class="card-tools">
                            <button class="btn btn-icon btn-round btn-light btn-sm" data-toggle="tooltip" title="Shortcuts">
                                <i class="fas fa-keyboard"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <a href="<?= BASE_URL ?>modules/products/add_product.php" class="btn btn-primary btn-block btn-action">
                                    <i class="fas fa-cube"></i> Add Product
                                    <span class="badge badge-light">Alt+P</span>
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="<?= BASE_URL ?>modules/sales/pos.php" class="btn btn-success btn-block btn-action">
                                    <i class="fas fa-cash-register"></i> New Sale
                                    <span class="badge badge-light">Alt+S</span>
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="<?= BASE_URL ?>modules/purchase/add_purchase.php" class="btn btn-danger btn-block btn-action">
                                    <i class="fas fa-pallet"></i> New Purchase
                                    <span class="badge badge-light">Alt+U</span>
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="<?= BASE_URL ?>modules/customers/add_customer.php" class="btn btn-info btn-block btn-action">
                                    <i class="fas fa-user-tag"></i> Add Customer
                                    <span class="badge badge-light">Alt+C</span>
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="<?= BASE_URL ?>modules/inventory/adjust_stock.php" class="btn btn-warning btn-block btn-action">
                                    <i class="fas fa-sliders-h"></i> Adjust Stock
                                    <span class="badge badge-light">Alt+A</span>
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="<?= BASE_URL ?>modules/sales/sales_report.php" class="btn btn-secondary btn-block btn-action">
                                    <i class="fas fa-chart-pie"></i> View Reports
                                    <span class="badge badge-light">Alt+R</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="stats">
                            <i class="fas fa-bolt"></i> Quick access to frequently used actions
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Sales -->
            <div class="col-md-6 animate__animated animate__fadeInUp">
                <div class="card card-table">
                    <div class="card-header">
                        <div class="card-head-row">
                            <div class="card-title">Recent Sales</div>
                            <div class="card-tools">
                                <a href="<?= BASE_URL ?>modules/sales/view_sales.php" class="btn btn-info btn-round btn-sm">
                                    <i class="fas fa-list"></i> View All
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-sales">
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
                                        <td>
                                            <a href="<?= BASE_URL ?>modules/sales/sale_details.php?id=<?= $sale['id'] ?>" class="text-primary font-weight-bold">
                                                <?= htmlspecialchars($sale['invoice_no']) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($sale['customer'] ?? 'Walk-in') ?></td>
                                        <td class="text-success font-weight-bold">৳<?= number_format($sale['total'], 2) ?></td>
                                        <td>
                                            <span class="badge text-dark">
                                                <?= date('M d, H:i', strtotime($sale['created_at'])) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="stats">
                            <i class="fas fa-history"></i> Last 5 sales transactions
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Purchases -->
            <div class="col-md-6 animate__animated animate__fadeInUp animate__delay-1">
                <div class="card card-table">
                    <div class="card-header">
                        <div class="card-head-row">
                            <div class="card-title">Recent Purchases</div>
                            <div class="card-tools">
                                <a href="<?= BASE_URL ?>modules/purchase/view_purchases.php" class="btn btn-info btn-round btn-sm">
                                    <i class="fas fa-list"></i> View All
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-purchases">
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
                                        <td>
                                            <a href="<?= BASE_URL ?>modules/purchase/purchase_details.php?id=<?= $purchase['id'] ?>" class="text-primary font-weight-bold">
                                                <?= htmlspecialchars($purchase['reference_no']) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($purchase['supplier'] ?? 'N/A') ?></td>
                                        <td class="text-danger font-weight-bold">৳<?= number_format($purchase['total'], 2) ?></td>
                                        <td>
                                            <span class="badge text-dark">
                                                <?= date('M d, H:i', strtotime($purchase['created_at'])) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="stats">
                            <i class="fas fa-history"></i> Last 5 purchase orders
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Low Stock Items -->
            <div class="col-md-12 animate__animated animate__fadeInUp animate__delay-2">
                <div class="card card-alert">
                    <div class="card-header">
                        <div class="card-head-row">
                            <div class="card-title">Low Stock Items</div>
                            <div class="card-tools">
                                <a href="<?= BASE_URL ?>modules/inventory/low_stock.php" class="btn btn-warning btn-round btn-sm">
                                    <i class="fas fa-exclamation-triangle"></i> View All
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($lowStockItems)): ?>
                        <div class="alert alert-success alert-rounded">
                            <i class="fas fa-check-circle"></i> No products are currently below minimum stock levels.
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-alert">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Barcode</th>
                                        <th>Current Stock</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lowStockItems as $item): ?>
                                    <tr>
                                        <td class="font-weight-bold"><?= htmlspecialchars($item['name']) ?></td>
                                        <td><span class="badge text-dark"><?= htmlspecialchars($item['barcode']) ?></span></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar text-dark <?= $item['current_stock'] < 5 ? 'bg-danger' : 'bg-warning' ?>" 
                                                     role="progressbar" 
                                                     style="width: <?= min(100, ($item['current_stock'] / 10) * 100) ?>%" 
                                                     aria-valuenow="<?= $item['current_stock'] ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="10">
                                                    <?= $item['current_stock'] ?? 0 ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($item['current_stock'] < 5): ?>
                                                <span class="badge badge-danger">Critical</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Low</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="<?= BASE_URL ?>modules/products/edit_product.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-primary" data-toggle="tooltip" title="Edit Product">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?= BASE_URL ?>modules/inventory/adjust_stock.php?product_id=<?= $item['id'] ?>" class="btn btn-sm btn-success" data-toggle="tooltip" title="Add Stock">
                                                    <i class="fas fa-plus"></i>
                                                </a>
                                                <a href="<?= BASE_URL ?>modules/purchase/add_purchase.php?product_id=<?= $item['id'] ?>" class="btn btn-sm btn-info" data-toggle="tooltip" title="Quick Purchase">
                                                    <i class="fas fa-shopping-cart"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <div class="stats">
                            <i class="fas fa-info-circle"></i> Products with stock levels below 10 units
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/requires/footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Sales & Purchases Chart with enhanced options
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
                    backgroundColor: 'rgba(23, 125, 255, 0.05)',
                    borderWidth: 3,
                    pointRadius: 4,
                    pointBackgroundColor: '#177dff',
                    pointHoverRadius: 6,
                    pointHoverBorderWidth: 2,
                    pointBorderColor: '#ffffff',
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Purchases',
                    data: <?= json_encode($purchaseData) ?>,
                    borderColor: '#f3545d',
                    backgroundColor: 'rgba(243, 84, 93, 0.05)',
                    borderWidth: 3,
                    pointRadius: 4,
                    pointBackgroundColor: '#f3545d',
                    pointHoverRadius: 6,
                    pointHoverBorderWidth: 2,
                    pointBorderColor: '#ffffff',
                    tension: 0.3,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 1000,
                easing: 'easeOutQuart'
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        callback: function(value) {
                            return '৳' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                tooltip: {
                    backgroundColor: '#2a2e3e',
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 12
                    },
                    callbacks: {
                        label: function(context) {
                            var label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '৳' + context.raw.toLocaleString();
                            return label;
                        }
                    },
                    mode: 'index',
                    intersect: false
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
    
    // Function to refresh chart data
    function refreshChart() {
        // implement AJAX call here to refresh chart data
        console.log("Refreshing chart data...");
        // For now, just reload the page
        location.reload();
    }
    
    // Add animation on scroll
    $(window).scroll(function() {
        $('.animate__animated').each(function() {
            var position = $(this).offset().top;
            var scroll = $(window).scrollTop();
            var windowHeight = $(window).height();
            
            if (scroll + windowHeight > position) {
                $(this).addClass($(this).data('animation'));
            }
        });
    });
    
    // Trigger scroll event on page load
    $(window).trigger('scroll');
});
</script>