<?php
require_once 'db/config.php';

echo "<h2>Available Test Users</h2>";

try {
    $stmt = $pdo->query('SELECT id, name, email, role FROM users');
    $users = $stmt->fetchAll();
    
    if (count($users) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
        
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>Testing Login:</h3>";
        echo "<p>Use any of the emails above with their respective passwords to test login.</p>";
        echo "<p><strong>Note:</strong> You'll need to know the passwords for these accounts. If you don't know them, you may need to:</p>";
        echo "<ul>";
        echo "<li>Register a new account</li>";
        echo "<li>Or reset the password for existing accounts</li>";
        echo "</ul>";
        
    } else {
        echo "<p>No users found. You need to register at least one user first.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Create a test user if none exist
if (count($users) === 0) {
    echo "<h3>Creating test user...</h3>";
    try {
        $test_password = password_hash('testpass123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['Test User', 'test@dataworld.com', $test_password, 'customer', 1]);
        
        echo "<p style='color: green;'>✓ Test user created!</p>";
        echo "<p><strong>Login credentials:</strong></p>";
        echo "<p>Email: test@dataworld.com</p>";
        echo "<p>Password: testpass123</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error creating test user: " . $e->getMessage() . "</p>";
    }
}
?>
