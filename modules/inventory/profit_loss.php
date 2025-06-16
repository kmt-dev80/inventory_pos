<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

$query = "
    SELECT 
        p.id,
        p.name,
        p.barcode,
        
        -- Initial stock (before date range)
        COALESCE((
            SELECT SUM(
                CASE 
                    WHEN s_init.change_type = 'purchase' THEN s_init.qty
                    WHEN s_init.change_type = 'purchase_return' THEN -s_init.qty
                    WHEN s_init.change_type = 'sale' THEN -s_init.qty
                    WHEN s_init.change_type = 'sales_return' THEN s_init.qty
                    WHEN s_init.change_type = 'adjustment' THEN 
                        CASE 
                            WHEN EXISTS (
                                SELECT 1 FROM inventory_adjustments ia 
                                WHERE ia.id = s_init.adjustment_id 
                                AND ia.adjustment_type = 'add'
                            ) THEN s_init.qty
                            ELSE -s_init.qty
                        END
                    ELSE 0
                END
            )
            FROM stock s_init
            WHERE s_init.product_id = p.id 
            AND s_init.created_at < ?
        ), 0) AS initial_stock,
        
        -- Purchases during period
        COALESCE(SUM(
            CASE 
                WHEN s.change_type = 'purchase' THEN s.qty
                ELSE 0
            END
        ), 0) AS purchased_qty,
        
        -- Purchase returns during period
        COALESCE(SUM(
            CASE 
                WHEN s.change_type = 'purchase_return' THEN -s.qty
                ELSE 0
            END
        ), 0) AS purchase_return_qty,
        
        -- Sales during period
        COALESCE(SUM(
            CASE 
                WHEN s.change_type = 'sale' THEN -s.qty
                ELSE 0
            END
        ), 0) AS sold_qty,
        
        -- Sales returns during period
        COALESCE(SUM(
            CASE 
                WHEN s.change_type = 'sales_return' THEN s.qty
                ELSE 0
            END
        ), 0) AS sales_return_qty,
        
        -- Adjustments (add) during period
        COALESCE(SUM(
            CASE 
                WHEN s.change_type = 'adjustment' AND EXISTS (
                    SELECT 1 FROM inventory_adjustments ia 
                    WHERE ia.id = s.adjustment_id 
                    AND ia.adjustment_type = 'add'
                ) THEN s.qty
                ELSE 0
            END
        ), 0) AS adjustment_add_qty,
        
        -- Adjustments (remove) during period
        COALESCE(SUM(
            CASE 
                WHEN s.change_type = 'adjustment' AND EXISTS (
                    SELECT 1 FROM inventory_adjustments ia 
                    WHERE ia.id = s.adjustment_id 
                    AND ia.adjustment_type = 'remove'
                ) THEN -s.qty
                ELSE 0
            END
        ), 0) AS adjustment_remove_qty,
        
        -- Purchase values
        COALESCE(SUM(
    CASE 
        WHEN s.change_type = 'purchase' THEN s.qty * s.price
        WHEN s.change_type = 'purchase_return' THEN s.qty * s.price
        ELSE 0
    END
), 0) AS total_purchase_cost,
        
        -- Sales values
        COALESCE(SUM(
            CASE 
                WHEN s.change_type = 'sale' THEN -s.qty * s.price
                WHEN s.change_type = 'sales_return' THEN s.qty * s.price
                ELSE 0
            END
        ), 0) AS total_sale_value,
        
        -- COGS calculation - now based on NET sales (sales minus returns)
        (
            SELECT COALESCE(
                SUM(
                    CASE 
                        WHEN s2.change_type = 'purchase' THEN s2.qty * s2.price
                        WHEN s2.change_type = 'purchase_return' THEN -s2.qty * s2.price
                        ELSE 0
                    END
                ) /
                NULLIF(SUM(
                    CASE 
                        WHEN s2.change_type = 'purchase' THEN s2.qty
                        WHEN s2.change_type = 'purchase_return' THEN -s2.qty
                        ELSE 0
                    END
                ), 0),
                p.price
            )
            FROM stock s2
            WHERE s2.product_id = p.id 
            AND s2.change_type IN ('purchase','purchase_return')
        ) * (COALESCE(SUM(
            CASE 
                WHEN s.change_type = 'sale' THEN -s.qty
                ELSE 0
            END
        ), 0) - COALESCE(SUM(
            CASE 
                WHEN s.change_type = 'sales_return' THEN s.qty
                ELSE 0
            END
        ), 0)) AS cogs

    FROM products p
    LEFT JOIN stock s
        ON p.id = s.product_id
        AND s.change_type IN ('purchase', 'purchase_return', 'sale', 'sales_return', 'adjustment')
        AND s.created_at BETWEEN ? AND ?
    WHERE p.is_deleted = 0
    GROUP BY p.id
    ORDER BY p.name
";

$stmt = $mysqli->getConnection()->prepare($query);
$stmt->bind_param('sss', $start_date, $start_date, $end_date);
$stmt->execute();
$products = $stmt->get_result();

// Store products in array so we can calculate current stock first
$product_data = [];
while ($product = $products->fetch_object()) {
    $product_data[] = $product;
}

$total_initial_stock = 0;
$total_purchased_qty = 0;
$total_purchase_return_qty = 0;
$total_sold_qty = 0;
$total_sales_return_qty = 0;
$total_adjustment_add_qty = 0;
$total_adjustment_remove_qty = 0;
$total_current_stock = 0;
$total_purchase_cost = 0;
$total_sale_value = 0;
$total_cogs = 0;
$total_sales_value_only = 0;
$total_sales_return_value_only = 0;

foreach ($product_data as $product) {
    // Calculate current stock for each product
    $product->current_stock = $product->initial_stock 
                            + $product->purchased_qty 
                            - abs($product->purchase_return_qty) 
                            - abs($product->sold_qty) 
                            + $product->sales_return_qty 
                            + $product->adjustment_add_qty 
                            - abs($product->adjustment_remove_qty);
    
    // Calculate net sales quantity (sold minus returns)
    $net_sales_qty = abs($product->sold_qty) - $product->sales_return_qty;
    
    // Calculate sales values
    $total_qty_combined = abs($product->sold_qty) + $product->sales_return_qty;
    $unit_price_estimated = $total_qty_combined > 0 ? $product->total_sale_value / $total_qty_combined : 0;
    $sale_value = abs($product->sold_qty) * $unit_price_estimated;
    $return_value = $product->sales_return_qty * $unit_price_estimated;
    $net_sales = $sale_value - $return_value;
    
    // Update totals
    $total_initial_stock += $product->initial_stock;
    $total_purchased_qty += $product->purchased_qty;
    $total_purchase_return_qty += abs($product->purchase_return_qty);
    $total_sold_qty += abs($product->sold_qty);
    $total_sales_return_qty += $product->sales_return_qty;
    $total_adjustment_add_qty += $product->adjustment_add_qty;
    $total_adjustment_remove_qty += abs($product->adjustment_remove_qty);
    $total_current_stock += $product->current_stock;
    $total_purchase_cost += $product->total_purchase_cost;
    $total_sale_value += $product->total_sale_value;
    $total_cogs += $product->cogs;
    $total_sales_value_only += $sale_value;
    $total_sales_return_value_only += $return_value;
}
$net_sales_value = $total_sales_value_only - $total_sales_return_value_only;
$total_profit = $net_sales_value - $total_cogs;
require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';
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
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="card-title mb-0">Detailed Stock Movement & Profit Report</h5>
                        </div>
                        <div class="col-md-6">
                            <form method="GET" class="row align-items-center">
                                <div class="col-auto">
                                    <div class="form-group mb-0">
                                        <label class="mr-2">From</label>
                                        <input type="date" name="start_date" value="<?= $start_date ?>" class="form-control form-control">
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="form-group mb-0">
                                        <label class="mr-2">To</label>
                                        <input type="date" name="end_date" value="<?= $end_date ?>" class="form-control form-control">
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                </div>
                                <div class="col-auto">
                                   <a href="profit_loss.php" class="btn btn-secondary">Reset</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Gross Sales</h5>
                                    <h2><?= number_format($total_sales_value_only, 2) ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Sales Returns</h5>
                                    <h2><?= number_format($total_sales_return_value_only, 2) ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Net Sales</h5>
                                    <h2><?= number_format($net_sales_value, 2) ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-secondary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">COGS</h5>
                                    <h2><?= number_format($total_cogs, 2) ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-4 offset-md-2">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Gross Profit</h5>
                                    <h2><?= number_format($total_profit, 2) ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-secondary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Profit Margin</h5>
                                    <h2><?= $net_sales_value != 0 ? number_format(($total_profit / $net_sales_value) * 100, 2) . '%' : '0%' ?></h2>
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
                                    <th>Initial Stock</th>
                                    <th>Purchased</th>
                                    <th>P.Returns</th>
                                    <th>Sold</th>
                                    <th>S.Returns</th>
                                    <th>Adj (+)</th>
                                    <th>Adj (-)</th>
                                    <th>Current Stock</th>
                                    <th>Purchase Cost</th>
                                    <th>Sales Value</th>
                                    <th>S.Return Value</th>
                                    <th>Net Sales</th>
                                    <th>COGS</th>
                                    <th>Profit</th>
                                    <th>Margin</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($product_data as $product): 
                                    // Calculate product-level values
                                    $total_qty_combined = abs($product->sold_qty) + $product->sales_return_qty;
                                    $unit_price_estimated = $total_qty_combined > 0 ? $product->total_sale_value / $total_qty_combined : 0;
                                    $sale_value = abs($product->sold_qty) * $unit_price_estimated;
                                    $return_value = $product->sales_return_qty * $unit_price_estimated;
                                    $net_sales = $sale_value - $return_value;
                                    $profit = $net_sales - $product->cogs;
                                    $margin = $net_sales != 0 ? ($profit / $net_sales) * 100 : 0;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($product->name) ?></td>
                                    <td><?= $product->barcode ?></td>
                                    <td><?= $product->initial_stock ?></td>
                                    <td><?= $product->purchased_qty ?></td>
                                    <td><?= abs($product->purchase_return_qty) ?></td>
                                    <td><?= abs($product->sold_qty) ?></td>
                                    <td><?= $product->sales_return_qty ?></td>
                                    <td><?= $product->adjustment_add_qty ?></td>
                                    <td><?= abs($product->adjustment_remove_qty) ?></td>
                                    <td><?= $product->current_stock ?></td>
                                    <td><?= number_format($product->total_purchase_cost, 2) ?></td>
                                    <td><?= number_format($sale_value, 2) ?></td>
                                    <td><?= number_format($return_value, 2) ?></td>
                                    <td><?= number_format($net_sales, 2) ?></td>
                                    <td><?= number_format($product->cogs, 2) ?></td>
                                    <td class="<?= $profit >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= number_format($profit, 2) ?>
                                    </td>
                                    <td><?= number_format($margin, 2) ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="2">Totals</th>
                                    <th><?= $total_initial_stock ?></th>
                                    <th><?= $total_purchased_qty ?></th>
                                    <th><?= $total_purchase_return_qty ?></th>
                                    <th><?= $total_sold_qty ?></th>
                                    <th><?= $total_sales_return_qty ?></th>
                                    <th><?= $total_adjustment_add_qty ?></th>
                                    <th><?= $total_adjustment_remove_qty ?></th>
                                    <th><?= $total_current_stock ?></th>
                                    <th><?= number_format($total_purchase_cost, 2) ?></th>
                                    <th><?= number_format($total_sales_value_only, 2) ?></th>
                                    <th><?= number_format($total_sales_return_value_only, 2) ?></th>
                                    <th><?= number_format($net_sales_value, 2) ?></th>
                                    <th><?= number_format($total_cogs, 2) ?></th>
                                    <th class="<?= $total_profit >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= number_format($total_profit, 2) ?>
                                    </th>
                                    <th><?= $net_sales_value != 0 ? number_format(($total_profit / $net_sales_value) * 100, 2) . '%' : '0%' ?></th>
                                </tr>
                            </tfoot>
                        </table>
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
        dom: 'lBfrti',
        buttons: [
            'copy', 'csv', 'excel', 'pdf'
        ],
        order: [[15, 'desc']], // Default sort by Profit
        pageLength: 25,
        scrollX: true
    });
});
</script>