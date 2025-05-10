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

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="manomitra_data_' . date('Y-m-d') . '.csv"');

// Create a file handle for output
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, [
    'Game', 
    'Score', 
    'Level', 
    'Duration', 
    'Date'
]);

// Get game session data for this user
$query = "SELECT g.game_name, gs.score, gs.level_reached, gs.duration_seconds, gs.played_at
          FROM game_sessions gs
          JOIN games g ON gs.game_id = g.game_id
          WHERE gs.user_id = ?
          ORDER BY gs.played_at DESC";
          
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Write data to CSV
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['game_name'],
        $row['score'],
        $row['level_reached'] ?: 'N/A',
        $row['duration_seconds'] ?: 'N/A',
        $row['played_at']
    ]);
}

// Close the database connection
$conn->close();

// Close the file handle
fclose($output);
exit;
?>