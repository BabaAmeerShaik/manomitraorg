<?php
// Include database connection
require_once 'db_connect.php';

// Check if user is logged in as admin
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Check if user ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_dashboard.php#users");
    exit();
}

$user_id = intval($_GET['id']);

// Get user data
$user_query = "SELECT u.user_id, u.username, u.email, u.created_at, u.last_login,
                us.memory_skill, us.focus_skill, us.problem_skill, us.reaction_skill,
                us.total_games_played, us.streak_days, us.best_reaction_time
               FROM users u
               LEFT JOIN user_stats us ON u.user_id = us.user_id
               WHERE u.user_id = ? AND u.role = 'user'";

$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // User not found
    header("Location: admin_dashboard.php#users");
    exit();
}

$user = $result->fetch_assoc();

// Get emotional anchors
$anchors_query = "SELECT * FROM emotional_anchors WHERE user_id = ?";
$stmt = $conn->prepare($anchors_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$anchors_result = $stmt->get_result();
$anchors = $anchors_result->fetch_assoc();

// Get game activity
$activity_query = "SELECT gs.session_id, g.game_name, gs.score, gs.level_reached, 
                  gs.duration_seconds, gs.played_at, gs.completed
                  FROM game_sessions gs
                  JOIN games g ON gs.game_id = g.game_id
                  WHERE gs.user_id = ?
                  ORDER BY gs.played_at DESC
                  LIMIT 50";

$stmt = $conn->prepare($activity_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$activity_result = $stmt->get_result();

// Handle admin actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Log the admin action
        $admin_id = $_SESSION['user_id'];
        $admin_action = '';
        $admin_details = '';
        $ip = $_SERVER['REMOTE_ADDR'];
        
        switch ($action) {
            case 'update_user':
                $new_username = sanitize_input($_POST['username']);
                $new_email = sanitize_input($_POST['email']);
                
                // Check if the username is already taken by another user
                if ($new_username !== $user['username']) {
                    $check_query = "SELECT user_id FROM users WHERE username = ? AND user_id != ?";
                    $stmt = $conn->prepare($check_query);
                    $stmt->bind_param("si", $new_username, $user_id);
                    $stmt->execute();
                    $check_result = $stmt->get_result();
                    
                    if ($check_result->num_rows > 0) {
                        $message = "Username already exists. Please choose a different username.";
                        $message_type = "error";
                        break;
                    }
                }
                
                // Update user data
                $update_query = "UPDATE users SET username = ?, email = ? WHERE user_id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("ssi", $new_username, $new_email, $user_id);
                
                if ($stmt->execute()) {
                    $admin_action = "Update User";
                    $admin_details = "Updated user ID: $user_id, Username: $new_username";
                    $message = "User updated successfully";
                    $message_type = "success";
                    
                    // Update user data for the page
                    $user['username'] = $new_username;
                    $user['email'] = $new_email;
                } else {
                    $message = "Error updating user: " . $conn->error;
                    $message_type = "error";
                }
                break;
                
            case 'reset_stats':
                // Reset user stats
                $reset_stats_query = "UPDATE user_stats SET 
                                     memory_skill = 0.2,
                                     focus_skill = 0.2,
                                     problem_skill = 0.2,
                                     reaction_skill = 0.2,
                                     total_games_played = 0,
                                     streak_days = 0,
                                     best_reaction_time = NULL
                                     WHERE user_id = ?";
                
                $stmt = $conn->prepare($reset_stats_query);
                $stmt->bind_param("i", $user_id);
                
                if ($stmt->execute()) {
                    $admin_action = "Reset User Stats";
                    $admin_details = "Reset stats for user ID: $user_id, Username: {$user['username']}";
                    $message = "User stats reset successfully";
                    $message_type = "success";
                    
                    // Update user data for the page
                    $user['memory_skill'] = 0.2;
                    $user['focus_skill'] = 0.2;
                    $user['problem_skill'] = 0.2;
                    $user['reaction_skill'] = 0.2;
                    $user['total_games_played'] = 0;
                    $user['streak_days'] = 0;
                    $user['best_reaction_time'] = null;
                } else {
                    $message = "Error resetting user stats: " . $conn->error;
                    $message_type = "error";
                }
                break;
                
            case 'clear_activity':
                // Clear user game activity
                $clear_activity_query = "DELETE FROM game_sessions WHERE user_id = ?";
                $stmt = $conn->prepare($clear_activity_query);
                $stmt->bind_param("i", $user_id);
                
                if ($stmt->execute()) {
                    $admin_action = "Clear User Activity";
                    $admin_details = "Cleared activity for user ID: $user_id, Username: {$user['username']}";
                    $message = "User activity cleared successfully";
                    $message_type = "success";
                    
                    // Clear activity for the page
                    $activity_result = new mysqli_result();
                } else {
                    $message = "Error clearing user activity: " . $conn->error;
                    $message_type = "error";
                }
                break;
                
            case 'delete_user':
                // Delete user (will cascade to all related records)
                $delete_query = "DELETE FROM users WHERE user_id = ?";
                $stmt = $conn->prepare($delete_query);
                $stmt->bind_param("i", $user_id);
                
                if ($stmt->execute()) {
                    $admin_action = "Delete User";
                    $admin_details = "Deleted user ID: $user_id, Username: {$user['username']}";
                    
                    // Log the action before redirecting
                    $log_query = "INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($log_query);
                    $stmt->bind_param("isss", $admin_id, $admin_action, $admin_details, $ip);
                    $stmt->execute();
                    
                    // Redirect back to user list
                    header("Location: admin_dashboard.php#users");
                    exit();
                } else {
                    $message = "Error deleting user: " . $conn->error;
                    $message_type = "error";
                }
                break;
        }
        
        // Log the admin action if set and not already logged
        if (!empty($admin_action)) {
            $log_query = "INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($log_query);
            $stmt->bind_param("isss", $admin_id, $admin_action, $admin_details, $ip);
            $stmt->execute();
        }
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View User - ManoMitra Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
  <!-- Header -->
  <header class="bg-emerald-800 text-white p-4 shadow-md">
    <div class="container mx-auto flex justify-between items-center">
      <div class="flex items-center">
        <img src="manomitra.jpeg" alt="ManoMitra Logo" class="h-10 rounded-lg mr-3">
        <h1 class="text-xl font-bold">ManoMitra Admin</h1>
      </div>
      <div class="flex items-center space-x-4">
        <a href="admin_dashboard.php" class="text-white hover:underline">Back to Dashboard</a>
        <a href="admin_logout.php" class="bg-emerald-700 hover:bg-emerald-600 px-3 py-2 rounded text-sm">Logout</a>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-emerald-800">User Details</h2>
      <div class="flex space-x-2">
        <a href="admin_dashboard.php#users" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md transition duration-150">
          Back to Users
        </a>
      </div>
    </div>
    
    <?php if (!empty($message)): ?>
    <div class="mb-6 p-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
      <?php echo $message; ?>
    </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- User Info -->
      <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
          <div class="flex items-center mb-4">
            <div class="w-16 h-16 bg-emerald-100 flex items-center justify-center text-2xl font-bold text-emerald-800 rounded-full mr-4">
              <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
            </div>
            <div>
              <h3 class="text-xl font-bold text-emerald-800"><?php echo htmlspecialchars($user['username']); ?></h3>
              <p class="text-gray-600"><?php echo htmlspecialchars($user['email'] ?: 'No email'); ?></p>
            </div>
          </div>
          
          <div class="border-t pt-4">
            <p class="text-sm text-gray-600 mb-2">User ID: <?php echo $user['user_id']; ?></p>
            <p class="text-sm text-gray-600 mb-2">Registered: <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
            <p class="text-sm text-gray-600 mb-2">Last Login: <?php echo $user['last_login'] ? date('F j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?></p>
          </div>
          
          <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?id=' . $user_id); ?>" class="mt-4">
            <input type="hidden" name="action" value="update_user">
            
            <div class="mb-4">
              <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                Username
              </label>
              <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-emerald-500" 
                    id="username" 
                    name="username"
                    type="text" 
                    value="<?php echo htmlspecialchars($user['username']); ?>">
            </div>
            
            <div class="mb-4">
              <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                Email
              </label>
              <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-emerald-500" 
                    id="email" 
                    name="email"
                    type="email" 
                    value="<?php echo htmlspecialchars($user['email'] ?: ''); ?>"
                    placeholder="No email">
            </div>
            
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150">
              Update User
            </button>
          </form>
          
          <div class="mt-6 border-t pt-4">
            <h4 class="text-lg font-semibold text-emerald-800 mb-2">Emotional Anchors</h4>
            
            <?php if ($anchors): ?>
            <div class="grid grid-cols-3 gap-2">
              <div>
                <p class="text-sm text-gray-600 mb-1">Peace</p>
                <div class="bg-gray-200 rounded-md p-1">
                  <p class="text-xs text-center"><?php echo htmlspecialchars($anchors['calm_image']); ?></p>
                </div>
              </div>
              <div>
                <p class="text-sm text-gray-600 mb-1">Strength</p>
                <div class="bg-gray-200 rounded-md p-1">
                  <p class="text-xs text-center"><?php echo htmlspecialchars($anchors['strong_image']); ?></p>
                </div>
              </div>
              <div>
                <p class="text-sm text-gray-600 mb-1">Focus</p>
                <div class="bg-gray-200 rounded-md p-1">
                  <p class="text-xs text-center"><?php echo htmlspecialchars($anchors['focused_image']); ?></p>
                </div>
              </div>
            </div>
            <?php else: ?>
            <p class="text-sm text-gray-600">No emotional anchors set</p>
            <?php endif; ?>
          </div>
          
          <div class="mt-6 border-t pt-4 space-y-3">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?id=' . $user_id); ?>" class="flex items-center" onsubmit="return confirm('Are you sure you want to reset this user\'s stats? This action cannot be undone.');">
              <input type="hidden" name="action" value="reset_stats">
              <button type="submit" class="text-amber-600 hover:text-amber-800">
                Reset User Stats
              </button>
            </form>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?id=' . $user_id); ?>" class="flex items-center" onsubmit="return confirm('Are you sure you want to clear this user\'s activity history? This action cannot be undone.');">
              <input type="hidden" name="action" value="clear_activity">
              <button type="submit" class="text-amber-600 hover:text-amber-800">
                Clear Activity History
              </button>
            </form>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?id=' . $user_id); ?>" class="flex items-center" onsubmit="return confirm('Are you sure you want to delete this user? All user data will be permanently lost. This action cannot be undone.');">
              <input type="hidden" name="action" value="delete_user">
              <button type="submit" class="text-red-600 hover:text-red-800">
                Delete User
              </button>
            </form>
          </div>
        </div>
      </div>
      
      <!-- User Stats and Activity -->
      <div class="lg:col-span-2">
        <!-- Cognitive Skills -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
          <h3 class="text-lg font-bold text-emerald-800 mb-4">Cognitive Skills Assessment</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
              <h4 class="font-medium text-gray-700 mb-2">Memory</h4>
              <div class="h-2 bg-gray-200 rounded-full mb-1">
                <div class="h-2 bg-emerald-500 rounded-full" style="width: <?php echo ($user['memory_skill'] * 100) . '%'; ?>"></div>
              </div>
              <div class="flex justify-between text-sm text-gray-500">
                <span>Beginner</span>
                <span><?php echo round($user['memory_skill'] * 100); ?>%</span>
                <span>Advanced</span>
              </div>
            </div>
            
            <div>
              <h4 class="font-medium text-gray-700 mb-2">Focus & Attention</h4>
              <div class="h-2 bg-gray-200 rounded-full mb-1">
                <div class="h-2 bg-emerald-500 rounded-full" style="width: <?php echo ($user['focus_skill'] * 100) . '%'; ?>"></div>
              </div>
              <div class="flex justify-between text-sm text-gray-500">
                <span>Beginner</span>
                <span><?php echo round($user['focus_skill'] * 100); ?>%</span>
                <span>Advanced</span>
              </div>
            </div>
            
            <div>
              <h4 class="font-medium text-gray-700 mb-2">Problem Solving</h4>
              <div class="h-2 bg-gray-200 rounded-full mb-1">
                <div class="h-2 bg-emerald-500 rounded-full" style="width: <?php echo ($user['problem_skill'] * 100) . '%'; ?>"></div>
              </div>
              <div class="flex justify-between text-sm text-gray-500">
                <span>Beginner</span>
                <span><?php echo round($user['problem_skill'] * 100); ?>%</span>
                <span>Advanced</span>
              </div>
            </div>
            
            <div>
              <h4 class="font-medium text-gray-700 mb-2">Reaction Speed</h4>
              <div class="h-2 bg-gray-200 rounded-full mb-1">
                <div class="h-2 bg-emerald-500 rounded-full" style="width: <?php echo ($user['reaction_skill'] * 100) . '%'; ?>"></div>
              </div>
              <div class="flex justify-between text-sm text-gray-500">
                <span>Beginner</span>
                <span><?php echo round($user['reaction_skill'] * 100); ?>%</span>
                <span>Advanced</span>
              </div>
            </div>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-green-50 p-4 rounded-xl text-center">
              <div class="text-sm text-emerald-700">Games Played</div>
              <div class="text-xl font-bold text-emerald-800"><?php echo $user['total_games_played']; ?></div>
            </div>
            
            <div class="bg-green-50 p-4 rounded-xl text-center">
              <div class="text-sm text-emerald-700">Best Reaction</div>
              <div class="text-xl font-bold text-emerald-800"><?php echo $user['best_reaction_time'] ? $user['best_reaction_time'] . 'ms' : '--'; ?></div>
            </div>
            
            <div class="bg-green-50 p-4 rounded-xl text-center">
              <div class="text-sm text-emerald-700">Daily Streak</div>
              <div class="text-xl font-bold text-emerald-800"><?php echo $user['streak_days']; ?></div>
            </div>
          </div>
        </div>
        
        <!-- Game Activity -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h3 class="text-lg font-bold text-emerald-800 mb-4">Game Activity History</h3>
          
          <?php if ($activity_result->num_rows > 0): ?>
          <div class="overflow-x-auto">
            <table class="min-w-full">
              <thead>
                <tr>
                  <th class="text-left py-2 px-3 border-b">Game</th>
                  <th class="text-left py-2 px-3 border-b">Score</th>
                  <th class="text-left py-2 px-3 border-b">Level</th>
                  <th class="text-left py-2 px-3 border-b">Duration</th>
                  <th class="text-left py-2 px-3 border-b">Date</th>
                  <th class="text-left py-2 px-3 border-b">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($activity = $activity_result->fetch_assoc()): ?>
                <tr>
                  <td class="py-2 px-3 border-b"><?php echo htmlspecialchars($activity['game_name']); ?></td>
                  <td class="py-2 px-3 border-b"><?php echo htmlspecialchars($activity['score']); ?></td>
                  <td class="py-2 px-3 border-b"><?php echo $activity['level_reached'] ?: 'N/A'; ?></td>
                  <td class="py-2 px-3 border-b"><?php echo $activity['duration_seconds'] ? $activity['duration_seconds'] . 's' : 'N/A'; ?></td>
                  <td class="py-2 px-3 border-b"><?php echo date('M j, Y g:i A', strtotime($activity['played_at'])); ?></td>
                  <td class="py-2 px-3 border-b">
                    <span class="<?php echo $activity['completed'] ? 'text-green-600' : 'text-red-600'; ?>">
                      <?php echo $activity['completed'] ? 'Completed' : 'Incomplete'; ?>
                    </span>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <p class="text-gray-600">No game activity found for this user.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</body>
</html>