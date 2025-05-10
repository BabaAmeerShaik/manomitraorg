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
    // Get data from request
    $user_id = $_SESSION['user_id'];
    $game_code = sanitize_input($_POST['game_code']);
    $score = isset($_POST['score']) ? intval($_POST['score']) : 0;
    $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 0;
    $level_reached = isset($_POST['level']) ? intval($_POST['level']) : 0;
    $completed = isset($_POST['completed']) ? (bool)$_POST['completed'] : true;
    
    // Validate required fields
    if (empty($game_code)) {
        handle_error("Game code is required");
    }
    
    // Get game ID from game code
    $game_query = "SELECT game_id, primary_skill, secondary_skill FROM games WHERE game_code = ?";
    $stmt = $conn->prepare($game_query);
    $stmt->bind_param("s", $game_code);
    $stmt->execute();
    $game_result = $stmt->get_result();
    
    if ($game_result->num_rows === 0) {
        handle_error("Invalid game code");
    }
    
    $game = $game_result->fetch_assoc();
    $game_id = $game['game_id'];
    $primary_skill = $game['primary_skill'];
    $secondary_skill = $game['secondary_skill'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert game session
        $session_query = "INSERT INTO game_sessions (user_id, game_id, score, duration_seconds, level_reached, completed) 
                         VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($session_query);
        $stmt->bind_param("iiiiib", $user_id, $game_id, $score, $duration, $level_reached, $completed);
        $stmt->execute();
        
        // Update game stats
        $check_stats_query = "SELECT game_stat_id, high_score, times_played, average_score FROM game_stats 
                             WHERE user_id = ? AND game_id = ?";
        $stmt = $conn->prepare($check_stats_query);
        $stmt->bind_param("ii", $user_id, $game_id);
        $stmt->execute();
        $stats_result = $stmt->get_result();
        
        if ($stats_result->num_rows > 0) {
            // Update existing stats
            $stats = $stats_result->fetch_assoc();
            $times_played = $stats['times_played'] + 1;
            
            // Calculate new average score
            $new_avg = (($stats['average_score'] * $stats['times_played']) + $score) / $times_played;
            
            // Determine high score based on game type
            $high_score = $stats['high_score'];
            if ($game_code === 'reaction-time') {
                // For reaction time, lower is better
                if ($score < $high_score || $high_score == 0) {
                    $high_score = $score;
                }
            } else {
                // For other games, higher is better
                if ($score > $high_score) {
                    $high_score = $score;
                }
            }
            
            $update_stats_query = "UPDATE game_stats SET high_score = ?, times_played = ?, 
                                  average_score = ?, last_played = CURRENT_TIMESTAMP 
                                  WHERE game_stat_id = ?";
            $stmt = $conn->prepare($update_stats_query);
            $stmt->bind_param("iidd", $high_score, $times_played, $new_avg, $stats['game_stat_id']);
            $stmt->execute();
        } else {
            // Create new stats entry
            $create_stats_query = "INSERT INTO game_stats (user_id, game_id, high_score, times_played, average_score) 
                                  VALUES (?, ?, ?, 1, ?)";
            $stmt = $conn->prepare($create_stats_query);
            $stmt->bind_param("iiid", $user_id, $game_id, $score, $score);
            $stmt->execute();
        }
        
        // Update user stats
        $check_user_stats_query = "SELECT * FROM user_stats WHERE user_id = ?";
        $stmt = $conn->prepare($check_user_stats_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_stats_result = $stmt->get_result();
        $user_stats = $user_stats_result->fetch_assoc();
        
        // Update skill levels based on game performance
        $normalized_score = 0;
        
        // Normalize score based on game type (0-1 scale)
        switch ($game_code) {
            case 'reaction-time':
                // Convert reaction time to 0-1 scale (lower is better)
                // Assuming 600ms is bad (0), 200ms is good (1)
                $normalized_score = max(0, min(1, (600 - $score) / 400));
                
                // Update best reaction time if better than previous
                if ($score < $user_stats['best_reaction_time'] || $user_stats['best_reaction_time'] === null) {
                    $update_reaction_query = "UPDATE user_stats SET best_reaction_time = ? WHERE user_id = ?";
                    $stmt = $conn->prepare($update_reaction_query);
                    $stmt->bind_param("ii", $score, $user_id);
                    $stmt->execute();
                }
                break;
            case 'sequence-recall':
            case 'pattern-recognition':
                // Level-based games (assuming max level 20)
                $normalized_score = min(1, $level_reached / 20);
                break;
            default:
                // Score-based games (assuming 0-100 scale)
                $normalized_score = min(1, $score / 100);
        }
        
        // Update primary skill (more impact)
        if ($primary_skill) {
            $skill_column = $primary_skill . '_skill';
            $skill_value = $user_stats[$skill_column] + ($normalized_score * 0.1);
            $skill_value = min(1, $skill_value); // Cap at 1
            
            $update_skill_query = "UPDATE user_stats SET $skill_column = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_skill_query);
            $stmt->bind_param("di", $skill_value, $user_id);
            $stmt->execute();
        }
        
        // Update secondary skill (less impact)
        if ($secondary_skill) {
            $skill_column = $secondary_skill . '_skill';
            $skill_value = $user_stats[$skill_column] + ($normalized_score * 0.05);
            $skill_value = min(1, $skill_value); // Cap at 1
            
            $update_skill_query = "UPDATE user_stats SET $skill_column = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_skill_query);
            $stmt->bind_param("di", $skill_value, $user_id);
            $stmt->execute();
        }
        
        // Update total games played and streak
        $total_games = $user_stats['total_games_played'] + 1;
        
        // Calculate streak
        $streak = $user_stats['streak_days'];
        $last_played = $user_stats['last_played_date'];
        $today = date('Y-m-d');
        
        if ($last_played === null) {
            // First time playing
            $streak = 1;
        } else {
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            
            if ($last_played === $today) {
                // Already played today, streak unchanged
            } else if ($last_played === $yesterday) {
                // Played yesterday, increase streak
                $streak++;
            } else {
                // Missed a day, reset streak
                $streak = 1;
            }
        }
        
        $update_user_stats_query = "UPDATE user_stats SET 
                                   total_games_played = ?, 
                                   streak_days = ?, 
                                   last_played_date = ? 
                                   WHERE user_id = ?";
        $stmt = $conn->prepare($update_user_stats_query);
        $stmt->bind_param("iisi", $total_games, $streak, $today, $user_id);
        $stmt->execute();
        
        // Commit the transaction
        $conn->commit();
        
        // Return success
        handle_success([
            'game_code' => $game_code,
            'score' => $score,
            'high_score' => $high_score ?? $score
        ]);
    } catch (Exception $e) {
        // If there's an error, roll back the transaction
        $conn->rollback();
        handle_error("Error saving game results: " . $e->getMessage());
    }
} else {
    handle_error("Invalid request method");
}

// Close connection
$conn->close();
?>