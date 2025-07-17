<?php
require_once 'db/config.php';

try {
    // Add order_id column to analytics_requests table
    $pdo->exec("ALTER TABLE analytics_requests ADD COLUMN order_id INT(11) NULL AFTER currency");
    
    // Add foreign key index
    $pdo->exec("ALTER TABLE analytics_requests ADD INDEX idx_order_id (order_id)");
    
    echo "Successfully added order_id column to analytics_requests table.\n";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column order_id already exists in analytics_requests table.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
