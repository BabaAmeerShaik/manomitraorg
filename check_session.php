<?php
// Include database connection
require_once 'db_connect.php';

// Start session
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // User is logged in
    $response = [
        'status' => 'success',
        'data' => [
            'logged_in' => true,
            'username' => $_SESSION['username'],
            'user_id' => $_SESSION['user_id']
        ]
    ];
} else {
    // User is not logged in
    $response = [
        'status' => 'success',
        'data' => [
            'logged_in' => false
        ]
    ];
}

// Return response
header('Content-Type: application/json');
echo json_encode($response);
?>