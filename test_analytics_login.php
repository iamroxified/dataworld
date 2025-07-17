<?php
require_once 'db/config.php';

echo "<h2>Testing Analytics Page Login Requirement</h2>";

// Test 1: Check if not logged in redirects
echo "<h3>Test 1: Access analytics.php without login</h3>";
session_destroy();
session_start();

// Simulate accessing analytics.php without being logged in
if (!isLoggedIn()) {
    echo "<p style='color: green;'>✓ User is not logged in - analytics.php should redirect to login.php</p>";
} else {
    echo "<p style='color: red;'>✗ User appears to be logged in when they shouldn't be</p>";
}

// Test 2: Check table structure
echo "<h3>Test 2: Check analytics_requests table structure</h3>";
try {
    $stmt = $pdo->query('DESCRIBE analytics_requests');
    $columns = $stmt->fetchAll();
    
    $has_user_id = false;
    $removed_personal_fields = true;
    $personal_fields = ['first_name', 'middle_name', 'last_name', 'email', 'phone'];
    
    echo "<p><strong>Table Columns:</strong></p><ul>";
    foreach ($columns as $column) {
        echo "<li>" . $column['Field'] . " - " . $column['Type'] . "</li>";
        if ($column['Field'] === 'user_id') {
            $has_user_id = true;
        }
        if (in_array($column['Field'], $personal_fields)) {
            $removed_personal_fields = false;
        }
    }
    echo "</ul>";
    
    if ($has_user_id) {
        echo "<p style='color: green;'>✓ user_id column exists</p>";
    } else {
        echo "<p style='color: red;'>✗ user_id column is missing</p>";
    }
    
    if ($removed_personal_fields) {
        echo "<p style='color: green;'>✓ Personal information fields have been removed from analytics_requests table</p>";
    } else {
        echo "<p style='color: orange;'>! Some personal information fields still exist (this might be expected if table wasn't fully migrated)</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking table structure: " . $e->getMessage() . "</p>";
}

// Test 3: Check if there are any test users to login with
echo "<h3>Test 3: Available test users</h3>";
try {
    $stmt = $pdo->query('SELECT id, name, email, role FROM users LIMIT 5');
    $users = $stmt->fetchAll();
    
    if (count($users) > 0) {
        echo "<p style='color: green;'>✓ Test users available:</p><ul>";
        foreach ($users as $user) {
            echo "<li>ID: {$user['id']}, Name: {$user['name']}, Email: {$user['email']}, Role: {$user['role']}</li>";
        }
        echo "</ul>";
        echo "<p>You can use these credentials to test the analytics page.</p>";
    } else {
        echo "<p style='color: red;'>✗ No test users found. You'll need to register a user first.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking users: " . $e->getMessage() . "</p>";
}

echo "<h3>Test Summary</h3>";
echo "<p><strong>To test the full functionality:</strong></p>";
echo "<ol>";
echo "<li>Make sure you have at least one user account registered</li>";
echo "<li>Try accessing analytics.php without being logged in - should redirect to login.php</li>";
echo "<li>Login with a valid user account</li>";
echo "<li>Access analytics.php - should show form with user information pre-filled in the greeting</li>";
echo "<li>Submit a test analytics request to verify it works with the new user_id structure</li>";
echo "</ol>";
?>
