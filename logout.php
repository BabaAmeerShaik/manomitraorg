<?php
// Start session
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Return success response
$response = [
    'status' => 'success',
    'message' => 'Logged out successfully'
];

// Return response
header('Content-Type: application/json');
echo json_encode($response);
?>