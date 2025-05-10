<?php
// Include database connection
require_once 'db_connect.php';

// Check if user is logged in
session_start();
if (isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    // Log admin logout
    $admin_id = $_SESSION['user_id'];
    $action = "Admin Logout";
    $details = "Admin logged out successfully";
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $log_query = "INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($log_query);
    $stmt->bind_param("isss", $admin_id, $action, $details, $ip);
    $stmt->execute();
}

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to admin login page
header("Location: admin_login.php");
exit();
?>