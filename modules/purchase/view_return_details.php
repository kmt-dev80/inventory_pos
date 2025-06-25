<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';

$return_id = $_GET['id'] ?? 0;
if (!$return_id) {
    $_SESSION['error'] = "Invalid return ID";
    header("Location: view_purchase_returns.php");
    exit();
}

$return_header = $mysqli->common_select('purchase_returns', '*', ['id' => $return_id])['data'][0] ?? null;
if (!$return_header) {
    $_SESSION['error'] = "Purchase return not found";
    header("Location: view_purchase_returns.php");
    exit();
}

$purchase = $mysqli->common_select('purchase', '*', ['id' => $return_header->purchase_id])['data'][0] ?? null;

$supplier = $mysqli->common_select('suppliers', '*', ['id' => $purchase->supplier_id])['data'][0] ?? null;


$return_items = $mysqli->common_select('purchase_return_items', '*', ['purchase_return_id' => $return_id])['data'] ?? [];

$total_refund = 0;
$total_vat = 0;
$total_discounted = 0;
$vat_rate = $return_header->vat_rate_used ?? 0;

foreach ($return_items as $item) {
    $discounted_price = $item->unit_price;
    $vat_amount = ($discounted_price * $vat_rate) / 100;
    $total_per_unit = $discounted_price + $vat_amount;
    
    $total_refund += $total_per_unit * $item->quantity;
    $total_vat += $vat_amount * $item->quantity;
    $total_discounted += $discounted_price * $item->quantity;
}

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/sidebar.php';
require_once __DIR__ . '/../../requires/topbar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <h4 class="card-title">Purchase Return Details</h4>
                            <a href="view_purchase_returns.php" class="btn btn-primary btn-round ms-auto">
                                <i class="fas fa-arrow-left"></i> Back to Returns
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Return Reference:</label>
                                    <p class="form-control-static"><?= htmlspecialchars($return_header->reference_no ?? 'N/A') ?></p>
                                </div>
                                <div class="form-group">
                                    <label>Return Date:</label>
                                    <p class="form-control-static"><?= date('d M Y', strtotime($return_header->created_at)) ?></p>
                                </div>
                                <div class="form-group">
                                    <label>Return Reason:</label>
                                    <p class="form-control-static"><?= ucwords(str_replace('_', ' ', $return_header->return_reason)) ?></p>
                                </div>
                                <div class="form-group">
                                    <label>VAT Rate Used:</label>
                                    <p class="form-control-static"><?= number_format($vat_rate, 2) ?>%</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Purchase Reference:</label>
                                    <p class="form-control-static"><?= htmlspecialchars($purchase->reference_no ?? 'N/A') ?></p>
                                </div>
                                <div class="form-group">
                                    <label>Supplier:</label>
                                    <p class="form-control-static"><?= htmlspecialchars($supplier->name ?? 'N/A') ?></p>
                                </div>
                                <div class="form-group">
                                    <label>Refund Method:</label>
                                    <p class="form-control-static"><?= ucfirst($return_header->refund_method) ?></p>
                                </div>
                                <div class="form-group">
                                    <label>Processed By:</label>
                                    <p class="form-control-static"><?= htmlspecialchars($return_header->user_id) ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>VAT Amount</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($return_items)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No items found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($return_items as $index => $item): 
                                            $product = $mysqli->common_select('products', 'name', ['id' => $item->product_id])['data'][0] ?? null;
                                            $item_vat = ($item->unit_price * $vat_rate) / 100;
                                            $item_total = $item->unit_price * $item->quantity;
                                        ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td>
                                                <td><?= htmlspecialchars($product->name ?? 'Product Deleted') ?></td>
                                                <td><?= $item->quantity ?></td>
                                                <td><?= number_format($item->unit_price, 2) ?></td>
                                                <td><?= number_format($item_vat * $item->quantity, 2) ?></td>
                                                <td><?= number_format($item_total, 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-right">Subtotal:</th>
                                        <th colspan="3"><?= number_format($total_discounted, 2) ?></th>
                                    </tr>
                                    <tr>
                                        <th colspan="3" class="text-right">Total Refund:</th>
                                        <th colspan="3"><?= number_format($total_refund, 2) ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Notes:</label>
                                    <p class="form-control-static"><?= nl2br(htmlspecialchars($return_header->return_note ?? 'No notes available')) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="float-right">
                            <?php if ($_SESSION['user']->role == 'admin'): ?>
                                <a href="delete_purchase_return.php?id=<?= $return_id ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this return?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            <?php endif; ?>
                            <button onclick="window.print()" class="btn btn-info">
                                <i class="fas fa-print"></i> Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../requires/footer.php'; ?>