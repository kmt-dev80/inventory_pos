<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}

require_once __DIR__ . '/../../db_plugin.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: sales_returns.php');
    exit;
}

$returnId = (int)$_GET['id'];
$return = $mysqli->common_select('sales_returns', '*', ['id' => $returnId]);
$returnItems = $mysqli->common_select('sales_return_items', '*', ['sales_return_id' => $returnId]);

if ($return['error'] || empty($return['data'])) {
    setFlashMessage('Return not found', 'danger');
    header('Location: sales_returns.php');
    exit;
}

$return = $return['data'][0];
$sale = $mysqli->common_select('sales', '*', ['id' => $return->sale_id]);
$customer = $sale['data'][0]->customer_id ? 
    $mysqli->common_select('customers', '*', ['id' => $sale['data'][0]->customer_id]) : 
    ['error' => true];
$user = $mysqli->common_select('users', 'full_name', ['id' => $return->user_id]);

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/topbar.php';
require_once __DIR__ . '/../../requires/sidebar.php';
?>

<div class="main-panel">
    <div class="content">
        <div class="page-inner">
            <div class="page-header">
                <h4 class="page-title">Sales Return Details</h4>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex align-items-center">
                                <h4 class="card-title">Return #RTN-<?= $return->id ?></h4>
                                <div class="ml-auto">
                                    <a href="sales_returns.php" class="btn btn-primary btn-round">
                                        <i class="fas fa-arrow-left"></i> Back to Returns
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Invoice Reference</label>
                                        <input type="text" class="form-control" readonly 
                                            value="<?= !$sale['error'] && !empty($sale['data']) ? $sale['data'][0]->invoice_no : 'N/A' ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Customer</label>
                                        <input type="text" class="form-control" readonly 
                                            value="<?= !$customer['error'] && !empty($customer['data']) ? 
                                                $customer['data'][0]->name : 
                                                ($sale['data'][0]->customer_name ?: 'Walk-in Customer') ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Return Date</label>
                                        <input type="text" class="form-control" readonly 
                                            value="<?= date('d M Y h:i A', strtotime($return->created_at)) ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Return Reason</label>
                                        <input type="text" class="form-control" readonly 
                                            value="<?= ucfirst(str_replace('_', ' ', $return->return_reason)) ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Refund Method</label>
                                        <input type="text" class="form-control" readonly 
                                            value="<?= ucfirst($return->refund_method) ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Refund Amount</label>
                                        <input type="text" class="form-control" readonly 
                                            value="<?= number_format($return->refund_amount, 2) ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Processed By</label>
                                        <input type="text" class="form-control" readonly 
                                            value="<?= !$user['error'] && !empty($user['data']) ? $user['data'][0]->full_name : 'N/A' ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Return Note</label>
                                <textarea class="form-control" readonly rows="3"><?= $return->return_note ?></textarea>
                            </div>
                            
                            <hr>
                            <h4>Returned Items</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Product</th>
                                            <th>Barcode</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!$returnItems['error'] && !empty($returnItems['data'])): ?>
                                            <?php foreach ($returnItems['data'] as $index => $item): 
                                                $product = $mysqli->common_select('products', 'name, barcode', ['id' => $item->product_id]);
                                            ?>
                                                <tr>
                                                    <td><?= $index + 1 ?></td>
                                                    <td><?= !$product['error'] && !empty($product['data']) ? $product['data'][0]->name : 'Product Not Found' ?></td>
                                                    <td><?= !$product['error'] && !empty($product['data']) ? $product['data'][0]->barcode : 'N/A' ?></td>
                                                    <td><?= $item->quantity ?></td>
                                                    <td><?= number_format($item->unit_price, 2) ?></td>
                                                    <td><?= number_format($item->quantity * $item->unit_price, 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No items found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="5">Total Refund</th>
                                            <th><?= number_format($return->refund_amount, 2) ?></th>
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
</div>
<?php include __DIR__ . '/../../requires/footer.php'; ?>