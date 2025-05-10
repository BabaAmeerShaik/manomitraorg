<?php
// Include database connection
require_once 'db_connect.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: login.html");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get user profile data
$query = "SELECT u.username, u.email, u.created_at, us.* 
          FROM users u
          LEFT JOIN user_stats us ON u.user_id = us.user_id
          WHERE u.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // User not found (shouldn't happen but just in case)
    session_destroy();
    header("Location: login.html");
    exit();
}

$user_data = $result->fetch_assoc();

// Get emotional anchors
$anchors_query = "SELECT * FROM emotional_anchors WHERE user_id = ?";
$stmt = $conn->prepare($anchors_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$anchors_result = $stmt->get_result();
$anchors = $anchors_result->fetch_assoc();

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile - ManoMitra</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-green-50 min-h-screen">
  <!-- Navigation Bar -->
  <nav class="bg-white shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex items-center">
          <img src="manomitra.jpeg" alt="ManoMitra Logo" class="h-10 rounded-lg">
          <span class="ml-2 text-xl font-bold text-emerald-800">ManoMitra</span>
        </div>
        <div class="flex items-center space-x-4">
          <a href="dashboard.html" class="text-emerald-600 font-medium hover:text-emerald-800">Home</a>
          <a href="games.html" class="text-emerald-600 font-medium hover:text-emerald-800">Games</a>
          <a href="profile.php" class="text-emerald-800 font-medium hover:text-emerald-600">Profile</a>
          <button onclick="logout()" class="bg-red-50 text-red-500 hover:bg-red-100 px-3 py-1 rounded-lg text-sm font-medium">Logout</button>
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Profile Header Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
      <div class="flex flex-col md:flex-row items-center">
        <!-- Profile Image -->
        <div class="w-24 h-24 md:w-32 md:h-32 rounded-full bg-emerald-100 flex items-center justify-center text-3xl md:text-4xl font-bold text-emerald-800 mb-4 md:mb-0 md:mr-6 profile-image" id="profile-avatar">
          <?php echo strtoupper(substr($user_data['username'], 0, 1)); ?>
        </div>
        
        <!-- User Info -->
        <div class="flex-1 text-center md:text-left">
          <h1 class="text-2xl font-bold text-emerald-800 mb-1" id="profile-name"><?php echo htmlspecialchars($user_data['username']); ?></h1>
          <p class="text-emerald-600 mb-4">Member since <span id="profile-date"><?php echo date('F Y', strtotime($user_data['created_at'])); ?></span></p>
          
          <!-- Stats Summary -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-2">
            <div class="stat-card bg-green-50 p-3 rounded-xl text-center">
              <div class="text-sm text-emerald-700">Games Played</div>
              <div class="text-xl md:text-2xl font-bold text-emerald-800" id="stats-games"><?php echo $user_data['total_games_played'] ?: 0; ?></div>
            </div>
            
            <div class="stat-card bg-green-50 p-3 rounded-xl text-center">
              <div class="text-sm text-emerald-700">Best Reaction</div>
              <div class="text-xl md:text-2xl font-bold text-emerald-800" id="stats-reaction">
                <?php echo $user_data['best_reaction_time'] ? $user_data['best_reaction_time'] . 'ms' : '--'; ?>
              </div>
            </div>
            
            <div class="stat-card bg-green-50 p-3 rounded-xl text-center">
              <div class="text-sm text-emerald-700">Memory Score</div>
              <div class="text-xl md:text-2xl font-bold text-emerald-800" id="stats-memory">
                <?php echo round($user_data['memory_skill'] * 100) . '%'; ?>
              </div>
            </div>
            
            <div class="stat-card bg-green-50 p-3 rounded-xl text-center">
              <div class="text-sm text-emerald-700">Daily Streak</div>
              <div class="text-xl md:text-2xl font-bold text-emerald-800" id="stats-streak"><?php echo $user_data['streak_days'] ?: 0; ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Tabs Navigation -->
    <div class="flex border-b border-gray-200 mb-6">
      <button id="tab-stats" class="tab-button active py-2 px-4 text-emerald-800 font-medium" onclick="changeTab('stats')">
        Performance
      </button>
      <button id="tab-history" class="tab-button py-2 px-4 text-emerald-600 font-medium" onclick="changeTab('history')">
        Activity History
      </button>
      <button id="tab-settings" class="tab-button py-2 px-4 text-emerald-600 font-medium" onclick="changeTab('settings')">
        Account Settings
      </button>
    </div>
    
    <!-- Performance Stats Tab -->
    <div id="content-stats" class="tab-content">
      <!-- Performance Overview Chart -->
      <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-emerald-800 mb-4">Performance Overview</h2>
        <div class="h-64">
          <canvas id="performanceChart" class="w-full"></canvas>
        </div>
      </div>
      
      <!-- Cognitive Skills Assessment -->
      <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-emerald-800 mb-4">Cognitive Skills Assessment</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h3 class="font-medium text-gray-700 mb-3">Memory</h3>
            <div class="progress-container mb-1">
              <div class="progress-bar bg-emerald-500" id="skill-memory" style="width: <?php echo ($user_data['memory_skill'] * 100) . '%'; ?>"></div>
            </div>
            <div class="flex justify-between text-sm text-gray-500">
              <span>Beginner</span>
              <span>Advanced</span>
            </div>
          </div>
          
          <div>
            <h3 class="font-medium text-gray-700 mb-3">Focus & Attention</h3>
            <div class="progress-container mb-1">
              <div class="progress-bar bg-emerald-500" id="skill-focus" style="width: <?php echo ($user_data['focus_skill'] * 100) . '%'; ?>"></div>
            </div>
            <div class="flex justify-between text-sm text-gray-500">
              <span>Beginner</span>
              <span>Advanced</span>
            </div>
          </div>
          
          <div>
            <h3 class="font-medium text-gray-700 mb-3">Problem Solving</h3>
            <div class="progress-container mb-1">
              <div class="progress-bar bg-emerald-500" id="skill-problem" style="width: <?php echo ($user_data['problem_skill'] * 100) . '%'; ?>"></div>
            </div>
            <div class="flex justify-between text-sm text-gray-500">
              <span>Beginner</span>
              <span>Advanced</span>
            </div>
          </div>
          
          <div>
            <h3 class="font-medium text-gray-700 mb-3">Reaction Speed</h3>
            <div class="progress-container mb-1">
              <div class="progress-bar bg-emerald-500" id="skill-reaction" style="width: <?php echo ($user_data['reaction_skill'] * 100) . '%'; ?>"></div>
            </div>
            <div class="flex justify-between text-sm text-gray-500">
              <span>Beginner</span>
              <span>Advanced</span>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Recommendations -->
      <div class="bg-white rounded-xl shadow-lg p-6" id="recommendations-container">
        <h2 class="text-xl font-bold text-emerald-800 mb-4">Personalized Recommendations</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Recommendations will be loaded from JS -->
        </div>
      </div>
    </div>
    
    <!-- Activity History Tab -->
    <div id="content-history" class="tab-content hidden">
      <!-- Recent Activity -->
      <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-emerald-800 mb-4">Recent Activity</h2>
        <div class="overflow-x-auto">
          <table class="min-w-full">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-2 px-4 text-emerald-700 font-medium">Game</th>
                <th class="text-left py-2 px-4 text-emerald-700 font-medium">Date</th>
                <th class="text-left py-2 px-4 text-emerald-700 font-medium">Score</th>
                <th class="text-left py-2 px-4 text-emerald-700 font-medium">Progress</th>
              </tr>
            </thead>
            <tbody id="activity-table">
              <!-- Activity data will be populated via JS -->
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Monthly Progress Chart -->
      <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-emerald-800 mb-4">Monthly Progress</h2>
        <div class="h-64">
          <canvas id="monthlyChart" class="w-full"></canvas>
        </div>
      </div>
    </div>
    
    <!-- Account Settings Tab -->
    <div id="content-settings" class="tab-content hidden">
      <!-- Personal Information -->
      <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-emerald-800 mb-4">Personal Information</h2>
        
        <div class="max-w-md mx-auto md:mx-0">
          <form id="profile-form">
            <div class="mb-4">
              <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                Username
              </label>
              <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-emerald-500" 
                     id="edit-username" 
                     type="text" 
                     value="<?php echo htmlspecialchars($user_data['username']); ?>">
            </div>
            
            <div class="mb-4">
              <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                Email (optional)
              </label>
              <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-emerald-500" 
                     id="edit-email" 
                     type="email" 
                     value="<?php echo htmlspecialchars($user_data['email'] ?: ''); ?>"
                     placeholder="your.email@example.com">
            </div>
            
            <div class="mt-6">
              <button class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150" type="submit">
                Save Changes
              </button>
            </div>
          </form>
        </div>
      </div>
      
      <!-- Update Emotional Anchors -->
      <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-emerald-800 mb-4">Update Emotional Anchors</h2>
        <p class="text-gray-600 mb-4">You can update your emotional anchor images for login.</p>
        
        <button id="update-anchors-btn" class="bg-emerald-100 hover:bg-emerald-200 text-emerald-800 font-medium py-2 px-4 rounded-lg transition duration-150">
          Update Anchors
        </button>
      </div>
      
      <!-- Account Actions -->
      <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-red-800 mb-4">Account Actions</h2>
        
        <div class="flex flex-wrap gap-4">
          <button id="export-data-btn" class="bg-blue-100 hover:bg-blue-200 text-blue-800 font-medium py-2 px-4 rounded-lg transition duration-150">
            Export My Data
          </button>
          
          <button id="clear-data-btn" class="bg-amber-100 hover:bg-amber-200 text-amber-800 font-medium py-2 px-4 rounded-lg transition duration-150">
            Clear Activity Data
          </button>
          
          <button id="delete-account-btn" class="bg-red-100 hover:bg-red-200 text-red-800 font-medium py-2 px-4 rounded-lg transition duration-150">
            Delete Account
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Notification area (hidden by default) -->
  <div id="notification" class="fixed top-4 right-4 max-w-sm p-4 rounded-xl text-center font-medium alert"></div>

  <script src="js/app.js"></script>
  <script src="js/profile.js"></script>
  <script>
    // JavaScript for tab switching
    function changeTab(tabId) {
      // Hide all tab contents
      document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.add('hidden');
      });
      
      // Show selected tab content
      document.getElementById(`content-${tabId}`).classList.remove('hidden');
      
      // Update tab buttons
      document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
        button.classList.remove('text-emerald-800');
        button.classList.add('text-emerald-600');
      });
      
      document.getElementById(`tab-${tabId}`).classList.add('active');
      document.getElementById(`tab-${tabId}`).classList.add('text-emerald-800');
      document.getElementById(`tab-${tabId}`).classList.remove('text-emerald-600');
    }
    
    // Logout function
    function logout() {
      fetch('logout.php')
        .then(response => response.json())
        .then(data => {
          window.location.href = 'login.html';
        })
        .catch(error => {
          console.error('Logout error:', error);
        });
    }
  </script>
</body>
</html>