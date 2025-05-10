<?php
// Include database connection
require_once 'db_connect.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    handle_error("User not logged in");
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Process only POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Due to foreign key constraints with ON DELETE CASCADE, 
        // deleting the user will automatically delete all related records
        $delete_user_query = "DELETE FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($delete_user_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Clear session
        $_SESSION = array();
        session_destroy();
        
        handle_success(null, "Your account has been deleted");
    } catch (Exception $e) {
        // Roll back transaction if error
        $conn->rollback();
        handle_error("Error deleting account: " . $e->getMessage());
    }
} else {
    handle_error("Invalid request method");
}

// Close connection
$conn->close();
?>