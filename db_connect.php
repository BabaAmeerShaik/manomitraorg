<?php
// Database connection details
$servername = "localhost";
$username = "root";  // Default XAMPP username
$password = "";      // Default XAMPP password (empty)
$dbname = "manomitra";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to ensure proper handling of all characters
$conn->set_charset("utf8mb4");

// Function to sanitize input data
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = $conn->real_escape_string($data);
    return $data;
}

// Function to handle errors
function handle_error($message) {
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit();
}

// Function to handle success responses
function handle_success($data = null, $message = "Operation successful") {
    $response = ['status' => 'success', 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit();
}
?>