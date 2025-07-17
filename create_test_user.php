<?php
require_once 'db/config.php';

echo "<h2>Creating Test User for Login Testing</h2>";

try {
    // Create test users with known passwords
    $test_users = [
        [
            'name' => 'John Doe',
            'email' => 'john@test.com', 
            'password' => 'password123',
            'role' => 'customer'
        ],
        [
            'name' => 'Admin User',
            'email' => 'admin@test.com', 
            'password' => 'admin123',
            'role' => 'admin'
        ]
    ];
    
    foreach ($test_users as $user_data) {
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$user_data['email']]);
        
        if (!$stmt->fetch()) {
            $hashed_password = password_hash($user_data['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, is_active, phone) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $user_data['name'], 
                $user_data['email'], 
                $hashed_password, 
                $user_data['role'], 
                1,
                '+1234567890'
            ]);
            
            echo "<p style='color: green;'>✓ Created user: {$user_data['name']} ({$user_data['email']})</p>";
        } else {
            echo "<p style='color: blue;'>User {$user_data['email']} already exists</p>";
        }
    }
    
    echo "<h3>Test Credentials:</h3>";
    echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px;'>";
    echo "<p><strong>Customer Account:</strong></p>";
    echo "<p>Email: john@test.com</p>";
    echo "<p>Password: password123</p>";
    echo "<br>";
    echo "<p><strong>Admin Account:</strong></p>";
    echo "<p>Email: admin@test.com</p>";
    echo "<p>Password: admin123</p>";
    echo "</div>";
    
    echo "<p><strong>You can now test the login with these credentials!</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
