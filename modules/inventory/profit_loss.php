<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true || $_SESSION['user']->role !== 'admin') {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';

$start_date = !empty($_GET['start_date']) ? $_GET['start_date'] : '2025-01-01';
$end_date   = !empty($_GET['end_date'])   ? $_GET['end_date']   : date('Y-m-t');


/**
 * Calculates COGS using FIFO (First-In-First-Out) method
 */
function calculateFIFOCogs($product_id, $net_sold_qty, $mysqli, $end_date) {
    // Return 0 if no sales or invalid quantity
    if ($net_sold_qty <= 0) {
        return 0;
    }

    // Get all inventory transactions (purchases and returns) up to the report end date
    $conn = $mysqli->getConnection();
    $stmt = $conn->prepare("
        SELECT 
            s.id,
            s.change_type,
            CASE 
                WHEN s.change_type = 'purchase' THEN s.qty 
                WHEN s.change_type = 'purchase_return' THEN -s.qty
                ELSE 0 
            END as effective_qty,
            s.price,
            s.created_at
        FROM stock s
        WHERE s.product_id = ?
        AND s.change_type IN ('purchase', 'purchase_return')
        AND s.created_at <= ?
        ORDER BY s.created_at ASC
    ");
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return 0;
    }
    
    // Properly bind parameters
    $stmt->bind_param("is", $product_id, $end_date);
    
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return 0;
    }
    
    $result = $stmt->get_result();
    $inventory = [];
    
    // Build inventory batches in chronological order
    while ($row = $result->fetch_assoc()) {
        $qty = $row['effective_qty'];
        
        if ($row['change_type'] == 'purchase') {
            // Add purchase to inventory
            $inventory[] = [
                'qty' => $qty,
                'price' => $row['price'],
                'created_at' => $row['created_at']
            ];
        } elseif ($row['change_type'] == 'purchase_return') {
            // Process returns by removing from oldest inventory first
            $return_qty = abs($qty);
            while ($return_qty > 0 && !empty($inventory)) {
                $oldest_batch = &$inventory[0];
                $deduct = min($oldest_batch['qty'], $return_qty);
                $oldest_batch['qty'] -= $deduct;
                $return_qty -= $deduct;
                
                if ($oldest_batch['qty'] <= 0) {
                    array_shift($inventory);
                }
            }
        }
    }
    $stmt->close();
    
    // Calculate COGS by consuming inventory in FIFO order
    $cogs = 0;
    $remaining_qty = $net_sold_qty;
    
    foreach ($inventory as $batch) {
        if ($remaining_qty <= 0) break;
        
        $available = $batch['qty'];
        $used = min($available, $remaining_qty);
        $cogs += $used * $batch['price'];
        $remaining_qty -= $used;
    }
    
    // Fallback: if we sold more than purchased, use current price for remainder
    if ($remaining_qty > 0) {
        $fallback = $conn->query("SELECT price FROM products WHERE id = $product_id");
        if ($fallback && $row = $fallback->fetch_assoc()) {
            $cogs += $remaining_qty * $row['price'];
        }
    }
    
    return $cogs;
}

// Main query remains the same as before
$query = "
    SELECT 
        p.id,
        p.name,
        p.barcode,
        p.price as current_price,
        
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
        
        -- Sales during period (quantity and value)
        COALESCE(SUM(
            CASE 
                WHEN s.change_type = 'sale' THEN -s.qty
                ELSE 0
            END
        ), 0) AS sold_qty,
        
        COALESCE(SUM(
            CASE 
                WHEN s.change_type = 'sale' THEN -s.qty * s.price
                ELSE 0
            END
        ), 0) AS sold_value,
        
        -- Sales returns during period (quantity and value)
        COALESCE(SUM(
            CASE 
                WHEN s.change_type = 'sales_return' THEN s.qty
                ELSE 0
            END
        ), 0) AS sales_return_qty,
        
        COALESCE(SUM(
            CASE 
                WHEN s.change_type = 'sales_return' THEN s.qty * s.price
                ELSE 0
            END
        ), 0) AS sales_return_value,
        
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
        ), 0) AS total_purchase_cost
        
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

$product_data = [];
$totals = [
    'initial_stock' => 0,
    'purchased_qty' => 0,
    'purchase_return_qty' => 0,
    'sold_qty' => 0,
    'sales_return_qty' => 0,
    'adjustment_add_qty' => 0,
    'adjustment_remove_qty' => 0,
    'current_stock' => 0,
    'purchase_cost' => 0,
    'sales_value' => 0,
    'sales_return_value' => 0,
    'cogs' => 0
];

while ($product = $products->fetch_object()) {
    // Calculate current stock
    $product->current_stock = $product->initial_stock 
                            + $product->purchased_qty 
                            - abs($product->purchase_return_qty) 
                            - abs($product->sold_qty) 
                            + $product->sales_return_qty 
                            + $product->adjustment_add_qty 
                            - abs($product->adjustment_remove_qty);
    
    // Calculate net sold quantity (ensure positive)
    $net_sold = max(0, abs($product->sold_qty) - $product->sales_return_qty);
    
    // Calculate COGS using FIFO
    $product->cogs = calculateFIFOCogs($product->id, $net_sold, $mysqli, $end_date);
    
    // Calculate financial metrics
    $sale_value = abs($product->sold_value);
    $return_value = $product->sales_return_value;
    $net_sales = $sale_value - $return_value;
    $profit = $net_sales - $product->cogs;
    $margin = $net_sales != 0 ? ($profit / $net_sales) * 100 : 0;
    
    // Store calculated values
    $product->sale_value = $sale_value;
    $product->return_value = $return_value;
    $product->net_sales = $net_sales;
    $product->profit = $profit;
    $product->margin = $margin;
    
    // Update totals
    $totals['initial_stock'] += $product->initial_stock;
    $totals['purchased_qty'] += $product->purchased_qty;
    $totals['purchase_return_qty'] += abs($product->purchase_return_qty);
    $totals['sold_qty'] += abs($product->sold_qty);
    $totals['sales_return_qty'] += $product->sales_return_qty;
    $totals['adjustment_add_qty'] += $product->adjustment_add_qty;
    $totals['adjustment_remove_qty'] += abs($product->adjustment_remove_qty);
    $totals['current_stock'] += $product->current_stock;
    $totals['purchase_cost'] += $product->total_purchase_cost;
    $totals['sales_value'] += $sale_value;
    $totals['sales_return_value'] += $return_value;
    $totals['cogs'] += $product->cogs;
    
    $product_data[] = $product;
}

// Calculate final totals
$net_sales_value = $totals['sales_value'] - $totals['sales_return_value'];
$total_profit = $net_sales_value - $totals['cogs'];
$total_margin = $net_sales_value != 0 ? ($total_profit / $net_sales_value) * 100 : 0;

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/sidebar.php';
require_once __DIR__ . '/../../requires/topbar.php';
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
                            <form method="GET" class="row align-items-center">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <h5 class="card-title mb-0">Detailed Stock Movement & Profit Report</h5>
                                    </div>    
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>From</label>
                                        <input type="date" class="form-control" name="start_date" value="<?= $start_date ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>To</label>
                                        <input type="date" class="form-control" name="end_date" value="<?= $end_date ?>">
                                    </div>
                                </div>
                                <div class="col-md-3 d-flex justify-content-end">
                                <div class="form-group">
                                    <label>&nbsp;</label> <!-- Space for alignment -->
                                    <div>
                                        <button type="submit" class="btn btn-primary">Filter</button>
                                        <a href="profit_loss.php" class="btn btn-secondary">Reset</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Gross Sales</h5>
                                        <h2><?= number_format($totals['sales_value'], 2) ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Sales Returns</h5>
                                        <h2><?= number_format($totals['sales_return_value'], 2) ?></h2>
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
                                        <h2><?= number_format($totals['cogs'], 2) ?></h2>
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
                                        <h2><?= number_format($total_margin, 2) ?>%</h2>
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
                                    <?php foreach ($product_data as $product): ?>
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
                                        <td><?= number_format($product->sale_value, 2) ?></td>
                                        <td><?= number_format($product->return_value, 2) ?></td>
                                        <td><?= number_format($product->net_sales, 2) ?></td>
                                        <td><?= number_format($product->cogs, 2) ?></td>
                                        <td class="<?= $product->profit >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= number_format($product->profit, 2) ?>
                                        </td>
                                        <td><?= number_format($product->margin, 2) ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2">Totals</th>
                                        <th><?= $totals['initial_stock'] ?></th>
                                        <th><?= $totals['purchased_qty'] ?></th>
                                        <th><?= $totals['purchase_return_qty'] ?></th>
                                        <th><?= $totals['sold_qty'] ?></th>
                                        <th><?= $totals['sales_return_qty'] ?></th>
                                        <th><?= $totals['adjustment_add_qty'] ?></th>
                                        <th><?= $totals['adjustment_remove_qty'] ?></th>
                                        <th><?= $totals['current_stock'] ?></th>
                                        <th><?= number_format($totals['purchase_cost'], 2) ?></th>
                                        <th><?= number_format($totals['sales_value'], 2) ?></th>
                                        <th><?= number_format($totals['sales_return_value'], 2) ?></th>
                                        <th><?= number_format($net_sales_value, 2) ?></th>
                                        <th><?= number_format($totals['cogs'], 2) ?></th>
                                        <th class="<?= $total_profit >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= number_format($total_profit, 2) ?>
                                        </th>
                                        <th><?= number_format($total_margin, 2) ?>%</th>
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