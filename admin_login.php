<?php
// Include database connection
require_once 'db_connect.php';

// Start session
session_start();

// Check if already logged in as admin
if (isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header("Location: admin_dashboard.php");
    exit();
}

// Process login form submission
$error_message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = sanitize_input($_POST['username']);
    $password = sanitize_input($_POST['password']); // In a real app, you'd use password_hash() for storage
    
    // Check user credentials
    $query = "SELECT user_id, username, role FROM users WHERE username = ? AND role = 'admin'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        
        // In a real app, you'd use password_verify() here
        // For demo purposes, we're using a simple check against 'admin123'
        if ($password === 'admin123') {
            // Set session variables
            $_SESSION['user_id'] = $admin['user_id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['is_admin'] = true;
            
            // Update last login time
            $update_query = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("i", $admin['user_id']);
            $stmt->execute();
            
            // Log admin login
            $action = "Admin Login";
            $details = "Admin logged in successfully";
            $ip = $_SERVER['REMOTE_ADDR'];
            
            $log_query = "INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($log_query);
            $stmt->bind_param("isss", $admin['user_id'], $action, $details, $ip);
            $stmt->execute();
            
            // Redirect to admin dashboard
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error_message = "Invalid password";
        }
    } else {
        $error_message = "Invalid username or not an admin";
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
  <title>Admin Login - ManoMitra</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
  <div class="bg-white shadow-2xl rounded-lg p-8 w-full max-w-md">
    <div class="text-center mb-8">
      <img src="manomitra.jpeg" alt="ManoMitra Logo" class="h-20 mx-auto rounded-xl mb-4">
      <h1 class="text-2xl font-bold text-emerald-800">Admin Login</h1>
      <p class="text-emerald-600">Access the ManoMitra administrative panel</p>
    </div>
    
    <?php if (!empty($error_message)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
      <p><?php echo $error_message; ?></p>
    </div>
    <?php endif; ?>
    
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
      <div class="mb-6">
        <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username</label>
        <input type="text" id="username" name="username" required
               class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-emerald-500">
      </div>
      
      <div class="mb-6">
        <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password</label>
        <input type="password" id="password" name="password" required
               class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-emerald-500">
      </div>
      
      <div class="mb-6">
        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150">
          Login
        </button>
      </div>
    </form>
    
    <div class="text-center mt-6">
      <a href="login.html" class="text-emerald-600 hover:text-emerald-800">
        Return to User Login
      </a>
    </div>
  </div>
</body>
</html>