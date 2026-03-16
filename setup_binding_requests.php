<?php
// Simple script to setup the database
require_once 'db/config.php';

echo "<h2>Setting up Binding Requests...</h2>";

try {
    // Read and execute the SQL setup file
    $sql = file_get_contents('db/binding_requests.sql');
    
    // Split by statements and execute each one
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
            echo "<p>✓ Executed: " . substr($statement, 0, 50) . "...</p>";
        }
    }
    
    echo "<h3 style='color: green;'>✓ binding_requests table created successfully!</h3>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error setting up database:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
