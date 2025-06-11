<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';

// Get return ID from URL
$return_id = $_GET['id'] ?? 0;
if (!$return_id) {
    $_SESSION['error'] = "Invalid return ID";
    header("Location: view_purchase_returns.php");
    exit();
}

// Fetch return header information
$return_header = $mysqli->common_select('purchase_returns', '*', ['id' => $return_id])['data'][0] ?? null;
if (!$return_header) {
    $_SESSION['error'] = "Purchase return not found";
    header("Location: view_purchase_returns.php");
    exit();
}

// Fetch related purchase information
$purchase = $mysqli->common_select('purchase', '*', ['id' => $return_header->purchase_id])['data'][0] ?? null;

// Fetch supplier information
$supplier = $mysqli->common_select('suppliers', '*', ['id' => $purchase->supplier_id])['data'][0] ?? null;

// Fetch returned items
$return_items = $mysqli->common_select('purchase_return_items', '*', ['purchase_return_id' => $return_id])['data'] ?? [];

// Calculate totals properly
$total_refund = 0;
$total_vat = 0;
$total_discounted = 0;

foreach ($return_items as $item) {
    $discounted_price = $item->unit_price;
    $vat_amount = $item->vat_amount ?? ($discounted_price * ($item->vat_rate_used ?? 0) / 100);
    $total_per_unit = $discounted_price + $vat_amount;
    
    $total_refund += $total_per_unit * $item->quantity;
    $total_vat += $vat_amount * $item->quantity;
    $total_discounted += $discounted_price * $item->quantity;
}
require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';
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
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                            <?php unset($_SESSION['error']); ?>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>

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
                                        <th>Total</th>
                                        <th>Reason</th>
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
                                        ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td>
                                                <td><?= htmlspecialchars($product->name ?? 'Product Deleted') ?></td>
                                                <td><?= $item->quantity ?></td>
                                                <td><?= number_format($item->unit_price, 2) ?></td>
                                                <td><?= number_format($item->quantity * $item->unit_price, 2) ?></td>
                                               <td><?= ucfirst($item->reason ?? 'Not specified') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="4" class="text-right">Subtotal (After Discount):</th>
                                        <th colspan="2"><?= number_format($total_discounted, 2) ?></th>
                                    </tr>
                                    <tr>
                                        <th colspan="4" class="text-right">VAT Amount:</th>
                                        <th colspan="2"><?= number_format($total_vat, 2) ?></th>
                                    </tr>
                                    <tr>
                                        <th colspan="4" class="text-right">Total Refund:</th>
                                        <th colspan="2"><?= number_format($total_refund, 2) ?></th>
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

<?php include __DIR__ . '/../../requires/footer.php'; ?>