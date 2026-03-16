<?php
require_once 'db/config.php';

echo "<h2>Fixing Binding Requests Table...</h2>";

try {
    // Read and execute the SQL alter file
    $sql = file_get_contents('db/alter_binding_requests_table.sql');
    
    // The SQL file might contain multiple statements, though this one has one.
    // It's safer to execute it simply.
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute()) {
         echo "<h3 style='color: green;'>✓ Binding requests table altered successfully!</h3>";
         echo "<p>The 'programe' and 'pages' columns have been added.</p>";
    } else {
        $errorInfo = $stmt->errorInfo();
        // Error code 1060 for 'Duplicate column name'
        if ($errorInfo[1] == 1060) {
            echo "<h3 style='color: orange;'>✓ Table has already been altered.</h3>";
            echo "<p>The 'programe' and 'pages' columns already exist.</p>";
        } else {
            echo "<h3 style='color: red;'>❌ Error executing SQL:</h3>";
            echo "<p>" . $errorInfo[2] . "</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Database error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>It's possible the table has already been altered. Check your database schema.</p>";
}

echo "<p><a href='user/add_bind_request.php'>Go back to Add Bind Request</a></p>";
?>
