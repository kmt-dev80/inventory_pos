<?php
session_start();
if (!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
    header("Location: ../../login.php");
    exit();
}
require_once __DIR__ . '/../../db_plugin.php';

// Get filter parameters
$supplier_id = $_GET['supplier_id'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Build where conditions
$where = [];
$params = [];
$types = '';

if (!empty($start_date)) {
    $where[] = "purchase_returns.created_at >= ?";
    $params[] = $start_date . ' 00:00:00'; // Include time for accurate comparison
    $types .= 's';
}

if (!empty($end_date)) {
    $where[] = "purchase_returns.created_at <= ?";
    $params[] = $end_date . ' 23:59:59';
    $types .= 's';
}

if (!empty($supplier_id)) {
    $where[] = "purchase.supplier_id = ?";
    $params[] = $supplier_id;
    $types .= 'i';
}

// Get purchase returns with join to purchase table
$query = "SELECT purchase_returns.*, purchase.reference_no, purchase.purchase_date, suppliers.name as supplier_name 
          FROM purchase_returns 
          JOIN purchase ON purchase_returns.purchase_id = purchase.id 
          JOIN suppliers ON purchase.supplier_id = suppliers.id";

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " ORDER BY purchase_returns.created_at DESC";

// Prepare and execute query
$conn = $mysqli->getConnection();
$stmt = $conn->prepare($query);

if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $returns = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $returns = [];
        $_SESSION['error'] = "Error executing query: " . $stmt->error;
    }
    $stmt->close();
} else {
    $returns = [];
    $_SESSION['error'] = "Error preparing query: " . $conn->error;
}

// Get suppliers for filter dropdown
$suppliers = $mysqli->common_select('suppliers')['data'];

require_once __DIR__ . '/../../requires/header.php';
require_once __DIR__ . '/../../requires/sidebar.php';
require_once __DIR__ . '/../../requires/topbar.php';
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <div class="row align-items-center">
                <div>
                    <h3 class="page-title">View Purchase Returns</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/purchase/add_purchase.php">Add Purchase</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>modules/purchase/view_purchases.php">View Purchase</a></li>
                        <li class="breadcrumb-item active">View Purchase Returns</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin">
                <div class="card">
                    <div class="card-body">
                        
                        <!-- Filter Form -->
                        <form method="GET" class="mb-4">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Supplier</label>
                                        <select class="form-control" name="supplier_id">
                                            <option value="">All Suppliers</option>
                                            <?php foreach ($suppliers as $supplier): ?>
                                                <option value="<?= $supplier->id ?>" <?= $supplier_id == $supplier->id ? 'selected' : '' ?>>
                                                    <?= $supplier->name ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Start Date</label>
                                        <input type="date" class="form-control" name="start_date" value="<?= $start_date ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>End Date</label>
                                        <input type="date" class="form-control" name="end_date" value="<?= $end_date ?>">
                                    </div>
                                </div>
                                <div class="col-md-3 align-self-end">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="view_purchase_returns.php" class="btn btn-secondary">Reset</a>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Returns Table -->
                        <div class="table-responsive">
                            <table class="table table-striped" id="returnsTable">
                                <thead>
                                    <tr>
                                        <th>Purchase Ref</th>
                                        <th>Return Date</th>
                                        <th>Supplier</th>
                                        <th>Reason</th>
                                        <th>Refund Amount</th>
                                        <th>Refund Method</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($returns)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                                <h5>No purchase returns found</h5>
                                                <p class="text-muted">Try adjusting your filters</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                    <?php foreach ($returns as $return): ?>
                                        <tr>
                                             <td><?= $return['reference_no'] ?></td>
                                            <td><?= date('d M Y h:i A', strtotime($return['created_at'])) ?></td>
                                            <td><?= $return['supplier_name'] ?></td>
                                            <td><?= ucfirst(str_replace('_', ' ', $return['return_reason'])) ?></td>
                                            <td><?= number_format($return['refund_amount'], 2) ?></td>
                                            <td><?= ucfirst($return['refund_method']) ?></td>
                                            <td>
                                                <a href="view_return_details.php?id=<?= $return['id'] ?>" class="btn btn-info btn-sm">View</a>
                                                <?php if ($_SESSION['user']->role == 'admin'): ?>
                                                    <a href="delete_return.php?id=<?= $return['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../../requires/footer.php'; ?>