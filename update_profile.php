<?php
// Include database connection
require_once 'db_connect.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    handle_error("User not logged in");
}

// Process only POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $user_id = $_SESSION['user_id'];
    $new_username = sanitize_input($_POST['username']);
    $new_email = isset($_POST['email']) ? sanitize_input($_POST['email']) : null;
    
    // Validate username
    if (empty($new_username)) {
        handle_error("Username cannot be empty");
    }
    
    // Check if username already exists (if it's being changed)
    if ($new_username !== $_SESSION['username']) {
        $check_query = "SELECT user_id FROM users WHERE username = ? AND user_id != ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("si", $new_username, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            handle_error("Username already exists. Please choose a different username.");
        }
    }
    
    // Update user data
    $update_query = "UPDATE users SET username = ?, email = ? WHERE user_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssi", $new_username, $new_email, $user_id);
    
    if ($stmt->execute()) {
        // Also update in session
        $_SESSION['username'] = $new_username;
        
        handle_success(null, "Profile updated successfully");
    } else {
        handle_error("Error updating profile: " . $conn->error);
    }
} else {
    handle_error("Invalid request method");
}

// Close connection
$conn->close();
?>