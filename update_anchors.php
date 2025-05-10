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
    $calm_image = sanitize_input($_POST['calm_image']);
    $strong_image = sanitize_input($_POST['strong_image']);
    $focused_image = sanitize_input($_POST['focused_image']);
    
    // Validate required fields
    if (empty($calm_image) || empty($strong_image) || empty($focused_image)) {
        handle_error("All emotional anchors are required");
    }
    
    // Check if user has existing anchors
    $check_query = "SELECT anchor_id FROM emotional_anchors WHERE user_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing anchors
        $anchor_id = $result->fetch_assoc()['anchor_id'];
        
        $update_query = "UPDATE emotional_anchors 
                        SET calm_image = ?, strong_image = ?, focused_image = ? 
                        WHERE anchor_id = ?";
        
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssi", $calm_image, $strong_image, $focused_image, $anchor_id);
        
        if ($stmt->execute()) {
            handle_success(null, "Emotional anchors updated successfully");
        } else {
            handle_error("Error updating emotional anchors: " . $conn->error);
        }
    } else {
        // Create new anchors (should not normally happen, but just in case)
        $insert_query = "INSERT INTO emotional_anchors (user_id, calm_image, strong_image, focused_image)
                        VALUES (?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("isss", $user_id, $calm_image, $strong_image, $focused_image);
        
        if ($stmt->execute()) {
            handle_success(null, "Emotional anchors created successfully");
        } else {
            handle_error("Error creating emotional anchors: " . $conn->error);
        }
    }
} else {
    // GET request to get current anchors
    $user_id = $_SESSION['user_id'];
    
    $query = "SELECT calm_image, strong_image, focused_image 
             FROM emotional_anchors 
             WHERE user_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $anchors = $result->fetch_assoc();
        handle_success($anchors);
    } else {
        handle_error("No emotional anchors found for this user");
    }
}

// Close connection
$conn->close();
?>