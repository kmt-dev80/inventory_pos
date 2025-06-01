<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}

require_once __DIR__ . '/../../db_plugin.php';
require_once __DIR__ . '/../../includes/functions.php';

// Default date range (this month)
$startDate = date('Y-m-01');
$endDate = date('Y-m-t');

if (isset($_GET['from_date']) && !empty($_GET['from_date'])) {
    $startDate = $_GET['from_date'];
}

if (isset($_GET['to_date']) && !empty($_GET['to_date'])) {
    $endDate = $_GET['to_date'];
}

// Get sales data
$sales = $mysqli->common_select('sales', '*', [
    'created_at >=' => $startDate . ' 00:00:00',
    'created_at <=' => $endDate . ' 23:59:59',
    'is_deleted' => 0
], 'created_at', 'asc');

// Get returns data
$returns = $mysqli->common_select('sales_returns', '*', [
    'created_at >=' => $startDate . ' 00:00:00',
    'created_at <=' => $endDate . ' 23:59:59',
    'is_deleted' => 0
], 'created_at', 'asc');

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';
?>

<div class="main-panel">
    <div class="content">
        <div class="page-inner">
            <div class="page-header">
                <h4 class="page-title">Sales Reports</h4>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex align-items-center">
                                <h4 class="card-title">Sales Report</h4>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="get" class="mb-4">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>From Date</label>
                                            <input type="date" class="form-control" name="from_date" value="<?= $startDate ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>To Date</label>
                                            <input type="date" class="form-control" name="to_date" value="<?= $endDate ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>&nbsp;</label>
                                            <button type="submit" class="btn btn-primary btn-block">Generate</button>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>&nbsp;</label>
                                            <button type="button" id="printReport" class="btn btn-secondary btn-block">
                                                <i class="fas fa-print"></i> Print
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            
                            <div class="table-responsive" id="reportContent">
                                <h3 class="text-center">Sales Report (<?= date('d M Y', strtotime($startDate)) ?> - <?= date('d M Y', strtotime($endDate)) ?>)</h3>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Invoices</th>
                                            <th>Sales Amount</th>
                                            <th>Returns</th>
                                            <th>Refunds</th>
                                            <th>Net Sales</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $totalInvoices = 0;
                                        $totalSales = 0;
                                        $totalReturns = 0;
                                        $totalRefunds = 0;
                                        
                                        // Group sales by date
                                        $salesByDate = [];
                                        if (!$sales['error'] && !empty($sales['data'])) {
                                            foreach ($sales['data'] as $sale) {
                                                $date = date('Y-m-d', strtotime($sale->created_at));
                                                if (!isset($salesByDate[$date])) {
                                                    $salesByDate[$date] = [
                                                        'invoices' => 0,
                                                        'sales' => 0,
                                                        'returns' => 0,
                                                        'refunds' => 0
                                                    ];
                                                }
                                                $salesByDate[$date]['invoices']++;
                                                $salesByDate[$date]['sales'] += $sale->total;
                                                $totalInvoices++;
                                                $totalSales += $sale->total;
                                            }
                                        }
                                        
                                        // Group returns by date
                                        if (!$returns['error'] && !empty($returns['data'])) {
                                            foreach ($returns['data'] as $return) {
                                                $date = date('Y-m-d', strtotime($return->created_at));
                                                if (!isset($salesByDate[$date])) {
                                                    $salesByDate[$date] = [
                                                        'invoices' => 0,
                                                        'sales' => 0,
                                                        'returns' => 0,
                                                        'refunds' => 0
                                                    ];
                                                }
                                                $salesByDate[$date]['returns']++;
                                                $salesByDate[$date]['refunds'] += $return->refund_amount;
                                                $totalReturns++;
                                                $totalRefunds += $return->refund_amount;
                                            }
                                        }
                                        
                                        // Sort by date
                                        ksort($salesByDate);
                                        
                                        // Display each day's data
                                        foreach ($salesByDate as $date => $data):
                                            $netSales = $data['sales'] - $data['refunds'];
                                        ?>
                                            <tr>
                                                <td><?= date('d M Y', strtotime($date)) ?></td>
                                                <td><?= $data['invoices'] ?></td>
                                                <td><?= number_format($data['sales'], 2) ?></td>
                                                <td><?= $data['returns'] ?></td>
                                                <td><?= number_format($data['refunds'], 2) ?></td>
                                                <td><?= number_format($netSales, 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>Total</th>
                                            <th><?= $totalInvoices ?></th>
                                            <th><?= number_format($totalSales, 2) ?></th>
                                            <th><?= $totalReturns ?></th>
                                            <th><?= number_format($totalRefunds, 2) ?></th>
                                            <th><?= number_format($totalSales - $totalRefunds, 2) ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                                
                                <h4 class="mt-4">Top Selling Products</h4>
                                <?php
                                $topProducts = [];
                                if (!$sales['error'] && !empty($sales['data'])) {
                                    foreach ($sales['data'] as $sale) {
                                        $items = $mysqli->common_select('sale_items', '*', ['sale_id' => $sale->id]);
                                        if (!$items['error'] && !empty($items['data'])) {
                                            foreach ($items['data'] as $item) {
                                                if (!isset($topProducts[$item->product_id])) {
                                                    $product = $mysqli->common_select('products', 'name', ['id' => $item->product_id]);
                                                    $productName = !$product['error'] && !empty($product['data']) ? 
                                                        $product['data'][0]->name : 'Product Not Found';
                                                    $topProducts[$item->product_id] = [
                                                        'name' => $productName,
                                                        'quantity' => 0,
                                                        'amount' => 0
                                                    ];
                                                }
                                                $topProducts[$item->product_id]['quantity'] += $item->quantity;
                                                $topProducts[$item->product_id]['amount'] += ($item->quantity * $item->unit_price);
                                            }
                                        }
                                    }
                                }
                                
                                // Sort by quantity
                                usort($topProducts, function($a, $b) {
                                    return $b['quantity'] - $a['quantity'];
                                });
                                ?>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Quantity Sold</th>
                                            <th>Total Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($topProducts, 0, 10) as $product): ?>
                                            <tr>
                                                <td><?= $product['name'] ?></td>
                                                <td><?= $product['quantity'] ?></td>
                                                <td><?= number_format($product['amount'], 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Print report
    $('#printReport').click(function() {
        const printContent = $('#reportContent').html();
        const originalContent = $('body').html();
        
        $('body').html(`
            <div class="container">
                ${printContent}
                <div class="text-center mt-4">
                    <small>Generated on <?= date('d M Y h:i A') ?></small>
                </div>
            </div>
        `);
        
        window.print();
        $('body').html(originalContent);
    });
});
</script>

<?php include __DIR__ . '/../../requires/footer.php'; ?>