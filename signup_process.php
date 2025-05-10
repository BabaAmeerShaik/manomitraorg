<?php
// Include database connection
require_once 'db_connect.php';

// Process only POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $username = sanitize_input($_POST['username']);
    $calm_image = sanitize_input($_POST['calm_image']);
    $strong_image = sanitize_input($_POST['strong_image']);
    $focused_image = sanitize_input($_POST['focused_image']);
    $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : null;
    
    // Validate required fields
    if (empty($username) || empty($calm_image) || empty($strong_image) || empty($focused_image)) {
        handle_error("All fields are required");
    }
    
    // Check if username already exists
    $check_query = "SELECT user_id FROM users WHERE username = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        handle_error("Username already exists. Please choose a different username.");
    }
    
    // Start transaction for creating user and emotional anchors
    $conn->begin_transaction();
    
    try {
        // Insert user into users table
        $user_query = "INSERT INTO users (username, email) VALUES (?, ?)";
        $stmt = $conn->prepare($user_query);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        
        // Get the new user ID
        $user_id = $conn->insert_id;
        
        // Insert emotional anchors
        $anchor_query = "INSERT INTO emotional_anchors (user_id, calm_image, strong_image, focused_image) 
                         VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($anchor_query);
        $stmt->bind_param("isss", $user_id, $calm_image, $strong_image, $focused_image);
        $stmt->execute();
        
        // Initialize user stats
        $stats_query = "INSERT INTO user_stats (user_id) VALUES (?)";
        $stmt = $conn->prepare($stats_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Commit the transaction
        $conn->commit();
        
        // Return success
        handle_success(null, "Account created successfully");
    } catch (Exception $e) {
        // If there's an error, roll back the transaction
        $conn->rollback();
        handle_error("Error creating account: " . $e->getMessage());
    }
} else {
    handle_error("Invalid request method");
}

// Close connection
$conn->close();
?>