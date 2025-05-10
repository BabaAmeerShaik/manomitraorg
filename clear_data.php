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
        // Delete game sessions
        $delete_sessions_query = "DELETE FROM game_sessions WHERE user_id = ?";
        $stmt = $conn->prepare($delete_sessions_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Delete game stats
        $delete_stats_query = "DELETE FROM game_stats WHERE user_id = ?";
        $stmt = $conn->prepare($delete_stats_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Reset user stats but keep the user record
        $reset_user_stats_query = "UPDATE user_stats SET 
                                  memory_skill = 0.2, 
                                  focus_skill = 0.2, 
                                  problem_skill = 0.2, 
                                  reaction_skill = 0.2, 
                                  total_games_played = 0,
                                  best_reaction_time = NULL,
                                  streak_days = 0,
                                  last_played_date = NULL
                                  WHERE user_id = ?";
        $stmt = $conn->prepare($reset_user_stats_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        handle_success(null, "Your activity data has been cleared");
    } catch (Exception $e) {
        // Roll back transaction if error
        $conn->rollback();
        handle_error("Error clearing data: " . $e->getMessage());
    }
} else {
    handle_error("Invalid request method");
}

// Close connection
$conn->close();
?>