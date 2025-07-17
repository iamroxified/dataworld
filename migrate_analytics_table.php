<?php
require_once 'db/config.php';

echo "<h2>Migrating analytics_requests table structure</h2>";

try {
    // First, check current structure
    echo "<h3>Current table structure:</h3>";
    $stmt = $pdo->query('DESCRIBE analytics_requests');
    $columns = $stmt->fetchAll();
    
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>" . $column['Field'] . " - " . $column['Type'] . "</li>";
    }
    echo "</ul>";
    
    // Check if user_id exists
    $has_user_id = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'user_id') {
            $has_user_id = true;
            break;
        }
    }
    
    if (!$has_user_id) {
        echo "<p>Adding user_id column...</p>";
        $pdo->exec('ALTER TABLE analytics_requests ADD user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 1 AFTER id');
        echo "<p style='color: green;'>✓ user_id column added</p>";
    } else {
        echo "<p style='color: blue;'>user_id column already exists</p>";
    }
    
    // Drop personal information columns
    echo "<p>Removing personal information columns...</p>";
    $personal_fields = ['first_name', 'middle_name', 'last_name', 'email', 'phone'];
    
    foreach ($personal_fields as $field) {
        try {
            $pdo->exec("ALTER TABLE analytics_requests DROP COLUMN $field");
            echo "<p style='color: green;'>✓ Dropped column: $field</p>";
        } catch (PDOException $e) {
            echo "<p style='color: orange;'>Column $field doesn't exist or already dropped</p>";
        }
    }
    
    // Add index for user_id
    try {
        $pdo->exec('CREATE INDEX idx_user_id ON analytics_requests(user_id)');
        echo "<p style='color: green;'>✓ Index created for user_id</p>";
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>Index probably already exists</p>";
    }
    
    // Show final structure
    echo "<h3>Updated table structure:</h3>";
    $stmt = $pdo->query('DESCRIBE analytics_requests');
    $columns = $stmt->fetchAll();
    
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>" . $column['Field'] . " - " . $column['Type'] . "</li>";
    }
    echo "</ul>";
    
    echo "<p style='color: green;'><strong>Migration completed successfully!</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error during migration: " . $e->getMessage() . "</p>";
}
?>
