<?php
session_start();
require('../db/config.php');
require('../db/functions.php');

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if search term is provided
if (!isset($_GET['search']) || empty(trim($_GET['search']))) {
    echo json_encode(['users' => []]);
    exit;
}

$search_term = trim($_GET['search']);

// Don't allow users to search for themselves
$current_user = $_SESSION['username'];

try {
    // Search for users
    $users = search_users_by_username($search_term, 10);
    
    // Debug: Log search results
    error_log("Search term: " . $search_term);
    error_log("Raw users found: " . print_r($users, true));
    error_log("Current user: " . $current_user);
    
    // Filter out current user and format results
    $filtered_users = [];
    foreach ($users as $user) {
        if ($user['username'] !== $current_user) {
            $filtered_users[] = [
                'username' => $user['username'],
                'name' => trim($user['fname'] . ' ' . $user['lname']),
                'display' => $user['username'] . ' (' . trim($user['fname'] . ' ' . $user['lname']) . ')'
            ];
        }
    }
    
    // Debug: Log filtered results
    error_log("Filtered users: " . print_r($filtered_users, true));
    
    header('Content-Type: application/json');
    echo json_encode([
        'users' => $filtered_users,
        'debug' => [
            'search_term' => $search_term,
            'raw_count' => count($users),
            'filtered_count' => count($filtered_users),
            'current_user' => $current_user
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("Search API error: " . $e->getMessage());
    echo json_encode(['error' => 'Search failed: ' . $e->getMessage()]);
}
?>
