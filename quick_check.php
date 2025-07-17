<?php
require_once 'db/config.php';

echo "<h2>Data Check</h2>";

try {
    // Check categories
    $stmt = $pdo->query('SELECT id, name FROM categories LIMIT 10');
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Categories (" . count($categories) . "):</h3>";
    foreach ($categories as $cat) {
        echo "<p>ID: {$cat['id']}, Name: {$cat['name']}</p>";
    }
    
    // Check users
    $stmt = $pdo->query('SELECT id, name, email, role FROM users LIMIT 10');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Users (" . count($users) . "):</h3>";
    foreach ($users as $user) {
        echo "<p>ID: {$user['id']}, Name: {$user['name']}, Email: {$user['email']}, Role: {$user['role']}</p>";
    }
    
    // Check datasets
    $stmt = $pdo->query('SELECT id, title, category_id, created_by FROM datasets LIMIT 10');
    $datasets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Datasets (" . count($datasets) . "):</h3>";
    foreach ($datasets as $dataset) {
        echo "<p>ID: {$dataset['id']}, Title: {$dataset['title']}, Category: {$dataset['category_id']}, Created by: {$dataset['created_by']}</p>";
    }
    
    // Test insert directly
    echo "<h3>Test Insert:</h3>";
    $stmt = $pdo->prepare("INSERT INTO datasets (title, description, category_id, file_path, file_name, file_size, file_type, price, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([
        'Test Dataset', 
        'Test description', 
        1, 
        '/test/path.csv', 
        'test.csv', 
        1000, 
        'CSV', 
        99.99, 
        1
    ]);
    
    if ($result) {
        echo "<p>✓ Test insert successful</p>";
    } else {
        echo "<p>❌ Test insert failed</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
