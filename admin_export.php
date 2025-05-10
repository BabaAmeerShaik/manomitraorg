<?php
// Include database connection
require_once 'db_connect.php';

// Check if user is logged in as admin
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Check which export type is requested
$export_type = isset($_GET['type']) ? $_GET['type'] : 'users';
$valid_types = ['users', 'games', 'activity', 'stats', 'all'];

if (!in_array($export_type, $valid_types)) {
    $export_type = 'all';
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="manomitra_' . $export_type . '_export_' . date('Y-m-d') . '.csv"');

// Create a file handle for output
$output = fopen('php://output', 'w');

// Log the export action
$admin_id = $_SESSION['user_id'];
$action = "Data Export";
$details = "Exported $export_type data";
$ip = $_SERVER['REMOTE_ADDR'];

$log_query = "INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($log_query);
$stmt->bind_param("isss", $admin_id, $action, $details, $ip);
$stmt->execute();

// Export data based on type
switch ($export_type) {
    case 'users':
        // Export users data
        fputcsv($output, ['User ID', 'Username', 'Email', 'Created At', 'Last Login', 'Memory Skill', 'Focus Skill', 'Problem Skill', 'Reaction Skill', 'Games Played', 'Streak', 'Best Reaction Time']);
        
        $query = "SELECT u.user_id, u.username, u.email, u.created_at, u.last_login, 
                 us.memory_skill, us.focus_skill, us.problem_skill, us.reaction_skill,
                 us.total_games_played, us.streak_days, us.best_reaction_time
                 FROM users u
                 LEFT JOIN user_stats us ON u.user_id = us.user_id
                 WHERE u.role = 'user'";
        
        $result = $conn->query($query);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['user_id'],
                $row['username'],
                $row['email'],
                $row['created_at'],
                $row['last_login'],
                $row['memory_skill'],
                $row['focus_skill'],
                $row['problem_skill'],
                $row['reaction_skill'],
                $row['total_games_played'],
                $row['streak_days'],
                $row['best_reaction_time']
            ]);
        }
        break;
        
    case 'games':
        // Export games data
        fputcsv($output, ['Game ID', 'Game Code', 'Game Name', 'Description', 'Difficulty', 'Category', 'Primary Skill', 'Secondary Skill', 'Is Active', 'Play Count', 'Average Score']);
        
        $query = "SELECT g.*, 
                 COUNT(gs.session_id) as play_count,
                 AVG(gs.score) as avg_score,
                 gc.category_name
                 FROM games g
                 LEFT JOIN game_sessions gs ON g.game_id = gs.game_id
                 LEFT JOIN game_categories gc ON g.category_id = gc.category_id
                 GROUP BY g.game_id";
        
        $result = $conn->query($query);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['game_id'],
                $row['game_code'],
                $row['game_name'],
                $row['description'],
                $row['difficulty'],
                $row['category_name'],
                $row['primary_skill'],
                $row['secondary_skill'],
                $row['is_active'],
                $row['play_count'],
                $row['avg_score']
            ]);
        }
        break;
        
    case 'activity':
        // Export activity data
        fputcsv($output, ['Session ID', 'User ID', 'Username', 'Game ID', 'Game Name', 'Score', 'Level Reached', 'Duration (sec)', 'Completed', 'Played At']);
        
        $query = "SELECT gs.session_id, gs.user_id, u.username, gs.game_id, g.game_name,
                 gs.score, gs.level_reached, gs.duration_seconds, gs.completed, gs.played_at
                 FROM game_sessions gs
                 JOIN users u ON gs.user_id = u.user_id
                 JOIN games g ON gs.game_id = g.game_id
                 ORDER BY gs.played_at DESC";
        
        $result = $conn->query($query);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['session_id'],
                $row['user_id'],
                $row['username'],
                $row['game_id'],
                $row['game_name'],
                $row['score'],
                $row['level_reached'],
                $row['duration_seconds'],
                $row['completed'],
                $row['played_at']
            ]);
        }
        break;
        
    case 'stats':
        // Export system stats data
        fputcsv($output, ['Date', 'Total Users', 'Active Users', 'New Users', 'Total Game Plays', 'Unique Players', 'Most Popular Game']);
        
        $query = "SELECT * FROM system_stats ORDER BY stat_date DESC";
        
        $result = $conn->query($query);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['stat_date'],
                $row['total_users'],
                $row['active_users'],
                $row['new_users'],
                $row['total_game_plays'],
                $row['unique_players'],
                $row['most_popular_game']
            ]);
        }
        break;
        
    case 'all':
    default:
        // Export all data (multiple sections)
        
        // 1. Users section
        fputcsv($output, ['--- USERS DATA ---']);
        fputcsv($output, ['User ID', 'Username', 'Email', 'Created At', 'Last Login', 'Memory Skill', 'Focus Skill', 'Problem Skill', 'Reaction Skill', 'Games Played', 'Streak', 'Best Reaction Time']);
        
        $query = "SELECT u.user_id, u.username, u.email, u.created_at, u.last_login, 
                 us.memory_skill, us.focus_skill, us.problem_skill, us.reaction_skill,
                 us.total_games_played, us.streak_days, us.best_reaction_time
                 FROM users u
                 LEFT JOIN user_stats us ON u.user_id = us.user_id
                 WHERE u.role = 'user'";
        
        $result = $conn->query($query);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['user_id'],
                $row['username'],
                $row['email'],
                $row['created_at'],
                $row['last_login'],
                $row['memory_skill'],
                $row['focus_skill'],
                $row['problem_skill'],
                $row['reaction_skill'],
                $row['total_games_played'],
                $row['streak_days'],
                $row['best_reaction_time']
            ]);
        }
        
        // 2. Games section
        fputcsv($output, []); // Empty row as separator
        fputcsv($output, ['--- GAMES DATA ---']);
        fputcsv($output, ['Game ID', 'Game Code', 'Game Name', 'Description', 'Difficulty', 'Category', 'Primary Skill', 'Secondary Skill', 'Is Active', 'Play Count', 'Average Score']);
        
        $query = "SELECT g.*, 
                 COUNT(gs.session_id) as play_count,
                 AVG(gs.score) as avg_score,
                 gc.category_name
                 FROM games g
                 LEFT JOIN game_sessions gs ON g.game_id = gs.game_id
                 LEFT JOIN game_categories gc ON g.category_id = gc.category_id
                 GROUP BY g.game_id";
        
        $result = $conn->query($query);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['game_id'],
                $row['game_code'],
                $row['game_name'],
                $row['description'],
                $row['difficulty'],
                $row['category_name'],
                $row['primary_skill'],
                $row['secondary_skill'],
                $row['is_active'],
                $row['play_count'],
                $row['avg_score']
            ]);
        }
        
        // 3. Activity section (limit to last 1000 for size)
        fputcsv($output, []); // Empty row as separator
        fputcsv($output, ['--- ACTIVITY DATA (LAST 1000) ---']);
        fputcsv($output, ['Session ID', 'User ID', 'Username', 'Game ID', 'Game Name', 'Score', 'Level Reached', 'Duration (sec)', 'Completed', 'Played At']);
        
        $query = "SELECT gs.session_id, gs.user_id, u.username, gs.game_id, g.game_name,
                 gs.score, gs.level_reached, gs.duration_seconds, gs.completed, gs.played_at
                 FROM game_sessions gs
                 JOIN users u ON gs.user_id = u.user_id
                 JOIN games g ON gs.game_id = g.game_id
                 ORDER BY gs.played_at DESC
                 LIMIT 1000";
        
        $result = $conn->query($query);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['session_id'],
                $row['user_id'],
                $row['username'],
                $row['game_id'],
                $row['game_name'],
                $row['score'],
                $row['level_reached'],
                $row['duration_seconds'],
                $row['completed'],
                $row['played_at']
            ]);
        }
        
        // 4. System stats section
        fputcsv($output, []); // Empty row as separator
        fputcsv($output, ['--- SYSTEM STATISTICS ---']);
        fputcsv($output, ['Date', 'Total Users', 'Active Users', 'New Users', 'Total Game Plays', 'Unique Players', 'Most Popular Game']);
        
        $query = "SELECT * FROM system_stats ORDER BY stat_date DESC";
        
        $result = $conn->query($query);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['stat_date'],
                $row['total_users'],
                $row['active_users'],
                $row['new_users'],
                $row['total_game_plays'],
                $row['unique_players'],
                $row['most_popular_game']
            ]);
        }
        break;
}

// Close the file handle
fclose($output);

// Close the database connection
$conn->close();
exit;
?>