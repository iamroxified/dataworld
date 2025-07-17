<?php
// Simple script to setup the database
require_once 'db/config.php';

echo "<h2>Setting up DataWorld Database...</h2>";

try {
    // Read and execute the SQL setup file
    $sql = file_get_contents('db/setup.sql');
    
    // Split by statements and execute each one
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
            echo "<p>✓ Executed: " . substr($statement, 0, 50) . "...</p>";
        }
    }
    
    echo "<h3 style='color: green;'>✓ Database setup completed successfully!</h3>";
    echo "<p><strong>Demo Login Credentials:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> Username: admin, Password: admin123</li>";
    echo "<li><strong>User:</strong> Username: testuser, Password: user123</li>";
    echo "</ul>";
    echo "<p><a href='datasets.php'>Browse Datasets</a> | <a href='login.php'>Login</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error setting up database:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
