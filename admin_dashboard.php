<?php
// Include database connection
require_once 'db_connect.php';

// Check if user is logged in as admin
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Get admin user data
$admin_id = $_SESSION['user_id'];
$admin_username = $_SESSION['username'];

// Get daily statistics
$stats_query = "SELECT * FROM system_stats ORDER BY stat_date DESC LIMIT 30";
$stats_result = $conn->query($stats_query);

// Get total users count
$total_users_query = "SELECT COUNT(*) as count FROM users WHERE role = 'user'";
$total_users_result = $conn->query($total_users_query);
$total_users = $total_users_result->fetch_assoc()['count'];

// Get active users count (last 7 days)
$active_users_query = "SELECT COUNT(DISTINCT user_id) as count FROM users
                      WHERE last_login > DATE_SUB(NOW(), INTERVAL 7 DAY)
                      AND role = 'user'";
$active_users_result = $conn->query($active_users_query);
$active_users = $active_users_result->fetch_assoc()['count'];

// Get new users count (last 30 days)
$new_users_query = "SELECT COUNT(*) as count FROM users
                   WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
                   AND role = 'user'";
$new_users_result = $conn->query($new_users_query);
$new_users = $new_users_result->fetch_assoc()['count'];

// Get total games played
$games_played_query = "SELECT COUNT(*) as count FROM game_sessions";
$games_played_result = $conn->query($games_played_query);
$games_played = $games_played_result->fetch_assoc()['count'];

// Get most popular game
$popular_game_query = "SELECT g.game_name, COUNT(*) as play_count 
                      FROM game_sessions gs
                      JOIN games g ON gs.game_id = g.game_id
                      GROUP BY gs.game_id
                      ORDER BY play_count DESC
                      LIMIT 1";
$popular_game_result = $conn->query($popular_game_query);
$popular_game = $popular_game_result->num_rows > 0 ? $popular_game_result->fetch_assoc() : null;

// Get recent user registrations
$recent_users_query = "SELECT user_id, username, email, created_at, last_login 
                      FROM users 
                      WHERE role = 'user' 
                      ORDER BY created_at DESC 
                      LIMIT 10";
$recent_users_result = $conn->query($recent_users_query);

// Get recent game activity
$recent_activity_query = "SELECT gs.session_id, u.username, g.game_name, gs.score, gs.played_at
                        FROM game_sessions gs
                        JOIN users u ON gs.user_id = u.user_id
                        JOIN games g ON gs.game_id = g.game_id
                        ORDER BY gs.played_at DESC
                        LIMIT 10";
$recent_activity_result = $conn->query($recent_activity_query);

// Get distribution of games played
$game_distribution_query = "SELECT g.game_name, COUNT(*) as count
                           FROM game_sessions gs
                           JOIN games g ON gs.game_id = g.game_id
                           GROUP BY gs.game_id
                           ORDER BY count DESC";
$game_distribution_result = $conn->query($game_distribution_query);

// Handle admin actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check which form was submitted
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Log the admin action
        $admin_action = '';
        $admin_details = '';
        $ip = $_SERVER['REMOTE_ADDR'];
        
        switch ($action) {
            case 'update_stats':
                // Manually run the stats update procedure
                $conn->query("CALL update_system_stats()");
                $admin_action = "Update Stats";
                $admin_details = "Manually updated system statistics";
                $message = "System statistics updated successfully";
                $message_type = "success";
                break;
                
            case 'delete_user':
                if (isset($_POST['user_id'])) {
                    $user_id = intval($_POST['user_id']);
                    // Delete user (will cascade to all related records)
                    $delete_query = "DELETE FROM users WHERE user_id = ? AND role = 'user'";
                    $stmt = $conn->prepare($delete_query);
                    $stmt->bind_param("i", $user_id);
                    
                    if ($stmt->execute()) {
                        $admin_action = "Delete User";
                        $admin_details = "Deleted user ID: $user_id";
                        $message = "User deleted successfully";
                        $message_type = "success";
                    } else {
                        $message = "Error deleting user: " . $conn->error;
                        $message_type = "error";
                    }
                }
                break;
                
            case 'maintenance_mode':
                $maintenance_mode = isset($_POST['maintenance_mode']) ? 'true' : 'false';
                $update_query = "UPDATE admin_settings SET setting_value = ?, updated_by = ? WHERE setting_name = 'maintenance_mode'";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("si", $maintenance_mode, $admin_id);
                
                if ($stmt->execute()) {
                    $admin_action = "Maintenance Mode";
                    $admin_details = "Set maintenance mode to: $maintenance_mode";
                    $message = "Maintenance mode updated successfully";
                    $message_type = "success";
                } else {
                    $message = "Error updating maintenance mode: " . $conn->error;
                    $message_type = "error";
                }
                break;
                
            case 'update_setting':
                if (isset($_POST['setting_name']) && isset($_POST['setting_value'])) {
                    $setting_name = sanitize_input($_POST['setting_name']);
                    $setting_value = sanitize_input($_POST['setting_value']);
                    
                    $update_query = "UPDATE admin_settings SET setting_value = ?, updated_by = ? WHERE setting_name = ?";
                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param("sis", $setting_value, $admin_id, $setting_name);
                    
                    if ($stmt->execute()) {
                        $admin_action = "Update Setting";
                        $admin_details = "Updated setting '$setting_name' to '$setting_value'";
                        $message = "Setting updated successfully";
                        $message_type = "success";
                    } else {
                        $message = "Error updating setting: " . $conn->error;
                        $message_type = "error";
                    }
                }
                break;
        }
        
        // Log the admin action if set
        if (!empty($admin_action)) {
            $log_query = "INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($log_query);
            $stmt->bind_param("isss", $admin_id, $admin_action, $admin_details, $ip);
            $stmt->execute();
        }
    }
}

// Get current admin settings
$settings_query = "SELECT * FROM admin_settings";
$settings_result = $conn->query($settings_query);
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_name']] = $row;
}

// Get recent admin logs
$logs_query = "SELECT al.log_id, u.username, al.action, al.details, al.ip_address, al.timestamp 
              FROM admin_logs al
              JOIN users u ON al.admin_id = u.user_id
              ORDER BY al.timestamp DESC
              LIMIT 10";
$logs_result = $conn->query($logs_query);

// Close connection
$conn->close();
?> 

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - ManoMitra</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .sidebar {
      min-width: 250px;
      transition: all 0.3s;
    }
    
    .main-content {
      transition: all 0.3s;
    }
    
    .stat-card {
      transition: all 0.3s ease;
    }
    
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    
    .tab-content {
      display: none;
    }
    
    .tab-content.active {
      display: block;
    }
  </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
  <!-- Header -->
  <header class="bg-emerald-800 text-white p-4 shadow-md">
    <div class="container mx-auto flex justify-between items-center">
      <div class="flex items-center">
        <img src="manomitra.jpeg" alt="ManoMitra Logo" class="h-10 rounded-lg mr-3">
        <h1 class="text-xl font-bold">ManoMitra Admin</h1>
      </div>
      <div class="flex items-center space-x-4">
        <span>Welcome, <?php echo htmlspecialchars($admin_username); ?></span>
        <a href="admin_logout.php" class="bg-emerald-700 hover:bg-emerald-600 px-3 py-2 rounded text-sm">Logout</a>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <div class="flex flex-1 overflow-hidden">
    <!-- Sidebar -->
    <aside class="sidebar bg-emerald-900 text-white py-6 shadow-lg">
      <nav>
        <ul>
          <li>
            <a href="#dashboard" class="tab-link flex items-center px-6 py-3 hover:bg-emerald-800 active" data-tab="dashboard">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
              </svg>
              Dashboard
            </a>
          </li>
          <li>
            <a href="#users" class="tab-link flex items-center px-6 py-3 hover:bg-emerald-800" data-tab="users">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
              Users
            </a>
          </li>
          <li>
            <a href="#games" class="tab-link flex items-center px-6 py-3 hover:bg-emerald-800" data-tab="games">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
              </svg>
              Games
            </a>
          </li>
          <li>
            <a href="#statistics" class="tab-link flex items-center px-6 py-3 hover:bg-emerald-800" data-tab="statistics">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              Statistics
            </a>
          </li>
          <li>
            <a href="#settings" class="tab-link flex items-center px-6 py-3 hover:bg-emerald-800" data-tab="settings">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              Settings
            </a>
          </li>
          <li>
            <a href="#logs" class="tab-link flex items-center px-6 py-3 hover:bg-emerald-800" data-tab="logs">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
              </svg>
              Logs
            </a>
          </li>
          <li class="mt-10">
            <a href="index.html" class="flex items-center px-6 py-3 hover:bg-emerald-800">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Back to Site
            </a>
          </li>
        </ul>
      </nav>
    </aside>

    <!-- Main Content Area -->
    <main class="main-content flex-1 p-6 overflow-y-auto">
      <?php if (!empty($message)): ?>
      <div class="mb-6 p-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
        <?php echo $message; ?>
      </div>
      <?php endif; ?>
      
      <!-- Dashboard Tab -->
      <div id="dashboard-tab" class="tab-content active">
        <h2 class="text-2xl font-bold text-emerald-800 mb-6">Dashboard Overview</h2>
        
        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div class="stat-card bg-white p-6 rounded-lg shadow-md">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-600">Total Users</p>
                <p class="text-3xl font-bold text-emerald-800"><?php echo number_format($total_users); ?></p>
              </div>
              <div class="bg-emerald-100 p-3 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
              </div>
            </div>
            <div class="mt-4">
              <p class="text-sm text-gray-500">Active Past Week: <?php echo number_format($active_users); ?></p>
            </div>
          </div>
          
          <div class="stat-card bg-white p-6 rounded-lg shadow-md">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-600">New Users (30 days)</p>
                <p class="text-3xl font-bold text-emerald-800"><?php echo number_format($new_users); ?></p>
              </div>
              <div class="bg-blue-100 p-3 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
              </div>
            </div>
            <div class="mt-4">
              <p class="text-sm text-gray-500"><?php echo round(($new_users / $total_users) * 100, 1); ?>% Growth Rate</p>
            </div>
          </div>
          
          <div class="stat-card bg-white p-6 rounded-lg shadow-md">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-600">Total Games Played</p>
                <p class="text-3xl font-bold text-emerald-800"><?php echo number_format($games_played); ?></p>
              </div>
              <div class="bg-purple-100 p-3 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                </svg>
              </div>
            </div>
            <div class="mt-4">
              <p class="text-sm text-gray-500">Avg. <?php echo round($games_played / max(1, $total_users), 1); ?> games per user</p>
            </div>
          </div>
          
          <div class="stat-card bg-white p-6 rounded-lg shadow-md">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-600">Most Popular Game</p>
                <p class="text-xl font-bold text-emerald-800"><?php echo $popular_game ? htmlspecialchars($popular_game['game_name']) : 'N/A'; ?></p>
              </div>
              <div class="bg-yellow-100 p-3 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
              </div>
            </div>
            <div class="mt-4">
              <p class="text-sm text-gray-500"><?php echo $popular_game ? number_format($popular_game['play_count']) . " plays" : "No data"; ?></p>
            </div>
          </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
          <!-- Recent Users -->
          <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-bold text-emerald-800 mb-4">Recent User Registrations</h3>
            <div class="overflow-x-auto">
              <table class="min-w-full">
                <thead>
                  <tr>
                    <th class="text-left py-2 px-3 border-b">Username</th>
                    <th class="text-left py-2 px-3 border-b">Email</th>
                    <th class="text-left py-2 px-3 border-b">Registered</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($user = $recent_users_result->fetch_assoc()): ?>
                  <tr>
                    <td class="py-2 px-3 border-b"><?php echo htmlspecialchars($user['username']); ?></td>
                    <td class="py-2 px-3 border-b"><?php echo htmlspecialchars($user['email'] ?: 'N/A'); ?></td>
                    <td class="py-2 px-3 border-b"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                  </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
            <div class="mt-4">
              <a href="#users" class="tab-link text-emerald-600 hover:text-emerald-800" data-tab="users">View All Users →</a>
            </div>
          </div>
          
          <!-- Recent Game Activity -->
          <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-bold text-emerald-800 mb-4">Recent Game Activity</h3>
            <div class="overflow-x-auto">
              <table class="min-w-full">
                <thead>
                  <tr>
                    <th class="text-left py-2 px-3 border-b">User</th>
                    <th class="text-left py-2 px-3 border-b">Game</th>
                    <th class="text-left py-2 px-3 border-b">Score</th>
                    <th class="text-left py-2 px-3 border-b">Date</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($activity = $recent_activity_result->fetch_assoc()): ?>
                  <tr>
                    <td class="py-2 px-3 border-b"><?php echo htmlspecialchars($activity['username']); ?></td>
                    <td class="py-2 px-3 border-b"><?php echo htmlspecialchars($activity['game_name']); ?></td>
                    <td class="py-2 px-3 border-b"><?php echo htmlspecialchars($activity['score']); ?></td>
                    <td class="py-2 px-3 border-b"><?php echo date('M j, Y', strtotime($activity['played_at'])); ?></td>
                  </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
            <div class="mt-4">
              <a href="#games" class="tab-link text-emerald-600 hover:text-emerald-800" data-tab="games">View All Games →</a>
            </div>