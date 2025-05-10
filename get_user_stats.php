<?php
// Include database connection
require_once 'db_connect.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    handle_error("User not logged in");
}

// Process only GET requests
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $user_id = $_SESSION['user_id'];
    
    // Get user info and stats
    $user_query = "SELECT u.user_id, u.username, u.email, u.created_at, us.* 
                   FROM users u
                   LEFT JOIN user_stats us ON u.user_id = us.user_id
                   WHERE u.user_id = ?";
    
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();
    
    if ($user_result->num_rows === 0) {
        handle_error("User not found");
    }
    
    $user_info = $user_result->fetch_assoc();
    
    // Get game stats
    $game_stats_query = "SELECT g.game_code, g.game_name, gs.high_score, gs.times_played, gs.average_score, gs.last_played
                        FROM game_stats gs
                        JOIN games g ON gs.game_id = g.game_id
                        WHERE gs.user_id = ?";
    
    $stmt = $conn->prepare($game_stats_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $game_stats_result = $stmt->get_result();
    
    $game_stats = [];
    while ($row = $game_stats_result->fetch_assoc()) {
        $game_stats[$row['game_code']] = $row;
    }
    
    // Get recent activity
    $activity_query = "SELECT g.game_code, g.game_name, gs.score, gs.level_reached, gs.duration_seconds, gs.played_at
                      FROM game_sessions gs
                      JOIN games g ON gs.game_id = g.game_id
                      WHERE gs.user_id = ? AND gs.completed = 1
                      ORDER BY gs.played_at DESC
                      LIMIT 10";
    
    $stmt = $conn->prepare($activity_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $activity_result = $stmt->get_result();
    
    $recent_activity = [];
    while ($row = $activity_result->fetch_assoc()) {
        $recent_activity[] = $row;
    }
    
    // Get monthly progress data for charts
    $monthly_query = "SELECT 
                        DATE_FORMAT(played_at, '%Y-%m') as month,
                        COUNT(*) as games_played
                      FROM game_sessions
                      WHERE user_id = ? AND played_at > DATE_SUB(NOW(), INTERVAL 6 MONTH)
                      GROUP BY DATE_FORMAT(played_at, '%Y-%m')
                      ORDER BY month";
    
    $stmt = $conn->prepare($monthly_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $monthly_result = $stmt->get_result();
    
    $monthly_data = [];
    while ($row = $monthly_result->fetch_assoc()) {
        $monthly_data[] = $row;
    }
    
    // Generate recommendations based on skill levels
    $recommendations = [];
    
    // Find weakest skill
    $skills = [
        'memory_skill' => 'Memory',
        'focus_skill' => 'Focus & Attention',
        'problem_skill' => 'Problem Solving',
        'reaction_skill' => 'Reaction Speed'
    ];
    
    $weakest_skill = null;
    $lowest_level = 1;
    
    foreach ($skills as $skill_key => $skill_name) {
        if ($user_info[$skill_key] < $lowest_level) {
            $lowest_level = $user_info[$skill_key];
            $weakest_skill = $skill_key;
        }
    }
    
    // Get games for weakest skill
    if ($weakest_skill) {
        $skill_prefix = explode('_', $weakest_skill)[0]; // Extract 'memory', 'focus', etc.
        
        $rec_query = "SELECT game_code, game_name, description 
                     FROM games 
                     WHERE primary_skill = ? OR secondary_skill = ?
                     LIMIT 3";
        
        $stmt = $conn->prepare($rec_query);
        $stmt->bind_param("ss", $skill_prefix, $skill_prefix);
        $stmt->execute();
        $rec_result = $stmt->get_result();
        
        $recommended_games = [];
        while ($row = $rec_result->fetch_assoc()) {
            $recommended_games[] = $row;
        }
        
        if (count($recommended_games) > 0) {
            $recommendations[] = [
                'skill' => $skills[$weakest_skill],
                'message' => 'Your ' . $skills[$weakest_skill] . ' skills could use improvement.',
                'games' => $recommended_games
            ];
        }
    }
    
    // Return data
    $response_data = [
        'user_info' => [
            'username' => $user_info['username'],
            'email' => $user_info['email'],
            'member_since' => $user_info['created_at'],
            'stats' => [
                'games_played' => $user_info['total_games_played'],
                'best_reaction' => $user_info['best_reaction_time'],
                'streak' => $user_info['streak_days'],
                'memory_skill' => (float)$user_info['memory_skill'] * 100,
                'focus_skill' => (float)$user_info['focus_skill'] * 100,
                'problem_skill' => (float)$user_info['problem_skill'] * 100,
                'reaction_skill' => (float)$user_info['reaction_skill'] * 100
            ]
        ],
        'game_stats' => $game_stats,
        'recent_activity' => $recent_activity,
        'monthly_data' => $monthly_data,
        'recommendations' => $recommendations
    ];
    
    handle_success($response_data);
} else {
    handle_error("Invalid request method");
}

// Close connection
$conn->close();
?>