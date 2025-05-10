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
    
    // Validate required fields
    if (empty($username) || empty($calm_image) || empty($strong_image) || empty($focused_image)) {
        handle_error("All fields are required");
    }
    
    // Check if user exists and verify emotional anchors
    $login_query = "SELECT u.user_id, u.username, ea.calm_image, ea.strong_image, ea.focused_image 
                  FROM users u 
                  JOIN emotional_anchors ea ON u.user_id = ea.user_id 
                  WHERE u.username = ?";
    
    $stmt = $conn->prepare($login_query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        handle_error("User not found. Please check your username or sign up.");
    }
    
    $user = $result->fetch_assoc();
    
    // Verify emotional anchors
    if ($user['calm_image'] !== $calm_image || 
        $user['strong_image'] !== $strong_image || 
        $user['focused_image'] !== $focused_image) {
        handle_error("Incorrect login. Please check your emotional anchors.");
    }
    
    // Update last login time
    $update_query = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $user['user_id']);
    $stmt->execute();
    
    // Start session and set user data
    session_start();
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['logged_in'] = true;
    
    // Return success
    handle_success(['username' => $user['username']], "Login successful");
} else {
    handle_error("Invalid request method");
}

// Close connection
$conn->close();
?>