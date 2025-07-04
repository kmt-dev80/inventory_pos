<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php'; 


$query = "SELECT 
    p.id, p.name, p.barcode, p.sell_price, 
    COALESCE(SUM(s.qty), 0) AS current_stock,
    COALESCE(
        (SELECT s.price 
         FROM stock s 
         WHERE s.product_id = p.id 
           AND s.change_type = 'purchase' 
           AND s.qty > 0
         ORDER BY s.created_at DESC 
         LIMIT 1),
        p.price
    ) AS cost_price
FROM products p
LEFT JOIN stock s ON p.id = s.product_id
WHERE p.is_deleted = 0
GROUP BY p.id
ORDER BY p.name";

$products = $mysqli->getConnection()->query($query);

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/sidebar.php';
require_once __DIR__ . '/../../requires/topbar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Stock Report</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/inventory/stock_logs.php">View Logs</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/inventory/low_stock.php">Low Stock</a></li>
                        <li class="breadcrumb-item active">Stock Report</li>
                    </ul>
                </div>
                <div class="col-auto">
                    <a href="adjust_stock.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Adjust Stock
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Current Stock Levels</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="stockReportTable" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Product</th>
                                        <th>Barcode</th>
                                        <th>Cost Price</th>
                                        <th>Sell Price</th>
                                        <th>Current Stock</th>
                                        <th>Stock Value</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($product = $products->fetch_object()): 
                                        // Use avg_cost_price instead of price for calculations
                                        $cost_price = $product->cost_price ?? $product->price ?? 0;
                                        $stock_value = ($product->current_stock ?? 0) * $cost_price;
                                        $stock_class = '';
                                        
                                        // Stock level classes
                                        if(($product->current_stock ?? 0) <= 0) $stock_class = 'text-danger';
                                        elseif(($product->current_stock ?? 0) < 10) $stock_class = 'text-warning';
                                    ?>
                                    <tr>
                                        <td><?= $product->id ?></td>
                                        <td><?= htmlspecialchars($product->name) ?></td>
                                        <td><?= $product->barcode ?></td>
                                        <td><?= number_format($cost_price, 2) ?></td> <!-- Changed to use cost_price -->
                                        <td><?= number_format($product->sell_price ?? 0, 2) ?></td>
                                        <td class="<?= $stock_class ?>"><?= $product->current_stock ?? 0 ?></td>
                                        <td><?= number_format($stock_value, 2) ?></td>
                                        <td>
                                            <a href="product_stock_history.php?id=<?= $product->id ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-history"></i> History
                                            </a>
                                            <a href="adjust_stock.php?product_id=<?= $product->id ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i> Adjust
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="6" style="text-align:right">Total:</th>
                                        <th></th>
                                        <th></th>
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
    $('#stockReportTable').DataTable({
        dom: 'lBfrtip',
        order: [[0, 'desc']],
        buttons: [
            'csv', 'excel', 'pdf'
        ],
        footerCallback: function (row, data, start, end, display) {
            var api = this.api();
            
            var intVal = function (i) {
                return typeof i === 'string' ?
                    i.replace(/[\$,]/g, '')*1 :
                    typeof i === 'number' ?
                        i : 0;
            };
            
            total = api
                .column(6, {page: 'current'})
                .data()
                .reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);
            
           $(api.column(6).footer()).html(
                '<?= CURRENCY ?> ' + new Intl.NumberFormat('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(total)
            );


        }
    });
});
</script>