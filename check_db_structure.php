<?php
require_once 'db/config.php';

try {
    // Get all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Current tables in dataworld database:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
        
        // Show table structure
        $desc_stmt = $pdo->query("DESCRIBE $table");
        $columns = $desc_stmt->fetchAll();
        
        echo "  Columns:\n";
        foreach ($columns as $column) {
            echo "    {$column['Field']} ({$column['Type']}) - {$column['Key']}\n";
        }
        echo "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
