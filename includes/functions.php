<?php
// Generate reference number
function generateReferenceNo($prefix = '') {
    return $prefix . strtoupper(uniqid());
}

// Set flash message
function setFlashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

// Display flash message
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message']['message'];
        $type = $_SESSION['flash_message']['type'];
        unset($_SESSION['flash_message']);
        
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
                ' . $message . '
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
              </div>';
    }
}
/*
// Check user permissions
function checkPermission($requiredPermission) {
    if (!isset($_SESSION['user']) || 
        ($_SESSION['user']->role !== 'admin' && $_SESSION['user']->role !== $requiredPermission)) {
        setFlashMessage('You do not have permission to access this page', 'danger');
        header('Location: ' . BASE_URL . 'dashboard.php');
        exit;
    }
}*/
?>