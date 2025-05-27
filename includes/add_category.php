<?php
session_start();
if(!isset($_SESSION['log_user_status']) || $_SESSION['log_user_status'] !== true) {
        header("Location: login.php");
        exit();
    }
 require_once __DIR__ . '/../requires/header.php';
 require_once __DIR__ . '/../requires/sidebar.php';
 require_once __DIR__ . '/../requires/topbar.php';

// Helper function
function execute_query($mysqli, $sql) {
    $res = $mysqli->query($sql);
    if (!$res) {
        echo "<p style='color:red;'>Error: " . $mysqli->error . "</p>";
    }
    return $res;
}

// Handle Add Category
if (isset($_POST['add'])) {
    $name = $_POST['name'];
    $status = $_POST['status'];
    if ($name) {
        $sql = "INSERT INTO categories (name, status) VALUES ('$name', '$status')";
        execute_query($mysqli, $sql);
    }
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $sql = "DELETE FROM categories WHERE id=$id";
    execute_query($mysqli, $sql);
    header("Location: category_crud.php");
    exit;
}

// Handle Edit Category
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = execute_query($mysqli, "SELECT * FROM categories WHERE id=$id");
    $edit_data = $res->fetch_assoc();
}

// Handle Update Category
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $status = $_POST['status'];
    $sql = "UPDATE categories SET name='$name', status='$status' WHERE id=$id";
    execute_query($mysqli, $sql);
    header("Location: category_crud.php");
    exit;
}
?>
<?php require_once __DIR__ . '/../requires/footer.php'; ?>