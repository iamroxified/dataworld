<?php
require_once 'db/config.php';

echo "<h2>Database Structure Check</h2>";

try {
    // Check datasets table structure
    $stmt = $pdo->query('DESCRIBE datasets');
    echo "<h3>Datasets Table Columns:</h3>";
    while ($row = $stmt->fetch()) {
        echo "<p>" . $row['Field'] . " - " . $row['Type'] . "</p>";
    }
    
    // Check if the table has data
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM datasets');
    $count = $stmt->fetchColumn();
    echo "<p><strong>Datasets count:</strong> $count</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
