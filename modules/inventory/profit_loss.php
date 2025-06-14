<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';

// Get date range filters (default to current month)
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';

$query = "
    SELECT 
        p.id,
        p.name,
        p.barcode,
        SUM(CASE WHEN s.change_type IN ('purchase', 'adjustment', 'purchase_return') THEN s.qty ELSE 0 END) as total_purchased,
        SUM(CASE WHEN s.change_type IN ('purchase', 'adjustment', 'purchase_return') THEN s.qty * s.price ELSE 0 END) as total_purchase_cost,
        SUM(CASE WHEN s.change_type IN ('sale', 'sales_return') THEN s.qty ELSE 0 END) as total_sold,
        SUM(CASE WHEN s.change_type IN ('sale', 'sales_return') THEN s.qty * s.price ELSE 0 END) as total_sale_value,
        (
            SELECT COALESCE(
                SUM(CASE WHEN s2.change_type IN ('purchase', 'adjustment') THEN s2.qty * s2.price ELSE 0 END) /
                NULLIF(SUM(CASE WHEN s2.change_type IN ('purchase', 'adjustment') THEN s2.qty ELSE 0 END), 0),
                p.price
            )
            FROM stock s2 
            WHERE s2.product_id = p.id
        ) * SUM(CASE WHEN s.change_type IN ('sale', 'sales_return') THEN s.qty ELSE 0 END) as cogs
    FROM products p
    LEFT JOIN stock s ON p.id = s.product_id
    WHERE p.is_deleted = 0
    AND s.created_at BETWEEN ? AND ?
    GROUP BY p.id
    ORDER BY p.name
";

$stmt = $mysqli->getConnection()->prepare($query);
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$products = $stmt->get_result();

// Calculate totals
$total_purchase_cost = 0;
$total_sale_value = 0;
$total_cogs = 0;
$total_profit = 0;

while($product = $products->fetch_object()) {
    $total_purchase_cost += $product->total_purchase_cost;
    $total_sale_value += $product->total_sale_value;
    $total_cogs += $product->cogs;
    $total_profit += ($product->total_sale_value - $product->cogs);
}

// Reset pointer for second loop
$products->data_seek(0);
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <div class="row align-items-center">
                <div>
                    <h3 class="page-title">Profit & Loss Report</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Profit & Loss</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title">Profit & Loss Summary</h5>
                            <form method="GET" class="form-inline">
                                <div class="form-group mr-2">
                                    <label>From</label>
                                    <input type="date" name="start_date" value="<?= $start_date ?>" class="form-control form-control-sm">
                                </div>
                                <div class="form-group mr-2">
                                    <label>To</label>
                                    <input type="date" name="end_date" value="<?= $end_date ?>" class="form-control form-control-sm">
                                </div>
                                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Sales</h5>
                                        <h2><?= number_format($total_sale_value, 2) ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">COGS</h5>
                                        <h2><?= number_format($total_cogs, 2) ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Gross Profit</h5>
                                        <h2><?= number_format($total_profit, 2) ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-secondary text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Margin</h5>
                                        <h2><?= $total_sale_value > 0 ? number_format(($total_profit / $total_sale_value) * 100, 2) . '%' : '0%' ?></h2>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="profitLossTable" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Barcode</th>
                                        <th>Purchased</th>
                                        <th>Purchase Cost</th>
                                        <th>Sold</th>
                                        <th>Sales Value</th>
                                        <th>COGS</th>
                                        <th>Profit</th>
                                        <th>Margin</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($product = $products->fetch_object()): 
                                        $profit = $product->total_sale_value - $product->cogs;
                                        $margin = $product->total_sale_value > 0 ? ($profit / $product->total_sale_value) * 100 : 0;
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($product->name) ?></td>
                                        <td><?= $product->barcode ?></td>
                                        <td><?= $product->total_purchased ?></td>
                                        <td><?= number_format($product->total_purchase_cost, 2) ?></td>
                                        <td><?= abs($product->total_sold) ?></td>
                                        <td><?= number_format($product->total_sale_value, 2) ?></td>
                                        <td><?= number_format($product->cogs, 2) ?></td>
                                        <td class="<?= $profit >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= number_format($profit, 2) ?>
                                        </td>
                                        <td><?= number_format($margin, 2) ?>%</td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3">Totals</th>
                                        <th><?= number_format($total_purchase_cost, 2) ?></th>
                                        <th></th>
                                        <th><?= number_format($total_sale_value, 2) ?></th>
                                        <th><?= number_format($total_cogs, 2) ?></th>
                                        <th class="<?= $total_profit >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= number_format($total_profit, 2) ?>
                                        </th>
                                        <th><?= $total_sale_value > 0 ? number_format(($total_profit / $total_sale_value) * 100, 2) . '%' : '0%' ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../requires/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#profitLossTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        order: [[7, 'desc']], // Sort by profit descending
        pageLength: 25
    });
});
</script>