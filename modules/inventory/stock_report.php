<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php'; 

$title = "Stock Report";

// Get current stock levels
$query = "SELECT p.id, p.name, p.barcode, p.price, p.sell_price, 
                 COALESCE(SUM(CASE 
                    WHEN s.change_type = 'purchase' THEN s.qty 
                    WHEN s.change_type = 'purchase_return' THEN s.qty 
                    WHEN s.change_type = 'sales_return' THEN s.qty 
                    WHEN s.change_type = 'adjustment' AND s.qty > 0 THEN s.qty 
                    ELSE 0 
                 END), 0) - 
                 COALESCE(SUM(CASE 
                    WHEN s.change_type = 'sale' THEN s.qty 
                    WHEN s.change_type = 'adjustment' AND s.qty < 0 THEN ABS(s.qty) 
                    ELSE 0 
                 END), 0) as current_stock
          FROM products p
          LEFT JOIN stock s ON p.id = s.product_id
          WHERE p.is_deleted = 0
          GROUP BY p.id
          ORDER BY p.name";

$products = $mysqli->getConnection()->query($query);
require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Stock Report</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
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
                                        $stock_value = $product->current_stock * $product->price;
                                        $stock_class = '';
                                        if($product->current_stock <= 0) $stock_class = 'text-danger';
                                        elseif($product->current_stock < 10) $stock_class = 'text-warning';
                                    ?>
                                    <tr>
                                        <td><?= $product->id ?></td>
                                        <td><?= htmlspecialchars($product->name) ?></td>
                                        <td><?= $product->barcode ?></td>
                                        <td><?= number_format($product->price, 2) ?></td>
                                        <td><?= number_format($product->sell_price, 2) ?></td>
                                        <td class="<?= $stock_class ?>"><?= $product->current_stock ?></td>
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
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        footerCallback: function (row, data, start, end, display) {
            var api = this.api();
            
            // Remove the formatting to get integer data for summation
            var intVal = function (i) {
                return typeof i === 'string' ?
                    i.replace(/[\$,]/g, '')*1 :
                    typeof i === 'number' ?
                        i : 0;
            };
            
            // Total over all pages
            total = api
                .column(6, {page: 'current'})
                .data()
                .reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);
            
            // Update footer
            $(api.column(6).footer()).html(
                '<?= CURRENCY ?>' + total.toFixed(2)
            );
        }
    });
});
</script>