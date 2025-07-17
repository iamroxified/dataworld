<?php
require_once 'db/config.php';

echo "<h2>Users Table Structure</h2>";

try {
    $stmt = $pdo->query('DESCRIBE users');
    while ($row = $stmt->fetch()) {
        echo "<p>" . $row['Field'] . " - " . $row['Type'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
