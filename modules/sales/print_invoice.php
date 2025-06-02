<?php
require_once __DIR__ . '/../../db_plugin.php';

// Check if we're printing from session (POS) or direct request
if (isset($_SESSION['print_invoice'])) {
    $sale_id = $_SESSION['print_invoice'];
    unset($_SESSION['print_invoice']);
} else {
    if (!isset($_GET['id'])) {
        header("Location: view_sales.php");
        exit();
    }
    $sale_id = $_GET['id'];
}

// Get sale details
$sale_result = $mysqli->common_select('sales', '*', ['id' => $sale_id]);
if ($sale_result['error'] || empty($sale_result['data'])) {
    $_SESSION['error'] = "Sale not found!";
    header("Location: view_sales.php");
    exit();
}

$sale = $sale_result['data'][0];

// Get customer details
$customer = $sale->customer_id ? 
    $mysqli->common_select('customers', '*', ['id' => $sale->customer_id])['data'][0] : null;

// Get sale items
$items_result = $mysqli->common_select('sale_items', '*', ['sale_id' => $sale_id]);
$items = $items_result['data'];

// Get payments
$payments_result = $mysqli->common_select('sales_payment', '*', ['sales_id' => $sale_id]);
$payments = $payments_result['data'];

// Calculate paid amount
$paid_amount = 0;
foreach ($payments as $payment) {
    if ($payment->type == 'payment') {
        $paid_amount += $payment->amount;
    } else {
        $paid_amount -= $payment->amount;
    }
}

// Get company information (you would typically have this in a settings table)
$company_name = "Your Company Name";
$company_address = "123 Business Street, City, Country";
$company_phone = "+1 234 567 890";
$company_email = "info@yourcompany.com";
$company_logo = BASE_URL . "assets/img/logo.png";

header("Content-Type: text/html; charset=UTF-8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?= $sale->invoice_no ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .invoice-box {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        .company-info {
            text-align: left;
        }
        .company-info img {
            max-width: 150px;
            max-height: 80px;
        }
        .invoice-info {
            text-align: right;
        }
        .invoice-info h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .customer-details, .invoice-meta {
            width: 48%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th {
            background: #f5f5f5;
            text-align: left;
            padding: 8px;
        }
        table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .totals {
            margin-left: auto;
            width: 300px;
        }
        .totals table {
            width: 100%;
        }
        .totals td:last-child {
            text-align: right;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="header">
            <div class="company-info">
                <img src="<?= $company_logo ?>" alt="Company Logo">
                <h2><?= $company_name ?></h2>
                <p><?= $company_address ?></p>
                <p>Phone: <?= $company_phone ?></p>
                <p>Email: <?= $company_email ?></p>
            </div>
            <div class="invoice-info">
                <h1>INVOICE</h1>
                <p><strong>Invoice #:</strong> <?= $sale->invoice_no ?></p>
                <p><strong>Date:</strong> <?= date('d M Y', strtotime($sale->created_at)) ?></p>
                <p><strong>Status:</strong> 
                    <span style="color: <?= 
                        $sale->payment_status == 'paid' ? 'green' : 
                        ($sale->payment_status == 'partial' ? 'orange' : 'red')
                    ?>">
                        <?= ucfirst($sale->payment_status) ?>
                    </span>
                </p>
            </div>
        </div>
        
        <div class="details">
            <div class="customer-details">
                <h3>Bill To:</h3>
                <?php if ($customer): ?>
                    <p><strong>Name:</strong> <?= $customer->name ?></p>
                    <p><strong>Phone:</strong> <?= $customer->phone ?? 'N/A' ?></p>
                    <p><strong>Email:</strong> <?= $customer->email ?? 'N/A' ?></p>
                    <p><strong>Address:</strong> <?= $customer->address ?? 'N/A' ?></p>
                <?php elseif ($sale->customer_name): ?>
                    <p><strong>Name:</strong> <?= $sale->customer_name ?></p>
                    <p><strong>Email:</strong> <?= $sale->customer_email ?? 'N/A' ?></p>
                <?php else: ?>
                    <p>Walk-in customer</p>
                <?php endif; ?>
            </div>
            <div class="invoice-meta">
                <h3>Invoice Details:</h3>
                <p><strong>Prepared By:</strong> 
                    <?php 
                        $user = $mysqli->common_select('users', 'full_name', ['id' => $sale->user_id])['data'][0] ?? null;
                        echo $user ? $user->full_name : 'Unknown';
                    ?>
                </p>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $index => $item): 
                    $product = $mysqli->common_select('products', '*', ['id' => $item->product_id])['data'][0] ?? null;
                ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= $product ? $product->name : 'Product not found' ?></td>
                        <td><?= $item->quantity ?></td>
                        <td><?= number_format($item->unit_price, 2) ?></td>
                        <td><?= number_format($item->total_price, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="totals">
            <table>
                <tr>
                    <td><strong>Subtotal:</strong></td>
                    <td><?= number_format($sale->subtotal, 2) ?></td>
                </tr>
                <tr>
                    <td><strong>Discount:</strong></td>
                    <td><?= number_format($sale->discount, 2) ?></td>
                </tr>
                <tr>
                    <td><strong>VAT:</strong></td>
                    <td><?= number_format($sale->vat, 2) ?></td>
                </tr>
                <tr>
                    <td><strong>Total:</strong></td>
                    <td><?= number_format($sale->total, 2) ?></td>
                </tr>
                <tr>
                    <td><strong>Paid Amount:</strong></td>
                    <td><?= number_format($paid_amount, 2) ?></td>
                </tr>
                <tr>
                    <td><strong>Balance Due:</strong></td>
                    <td><?= number_format($sale->total - $paid_amount, 2) ?></td>
                </tr>
            </table>
        </div>
        
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>If you have any questions about this invoice, please contact us.</p>
            <p class="no-print">
                <button onclick="window.print()" class="btn btn-primary">Print Invoice</button>
                <button onclick="window.close()" class="btn btn-secondary">Close</button>
            </p>
        </div>
    </div>
    
    <script>
    // Auto-print when opened from POS
    window.onload = function() {
        <?php if (isset($_SESSION['print_invoice'])): ?>
            window.print();
        <?php endif; ?>
    };
    </script>
</body>
</html>