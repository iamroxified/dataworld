<?php
require_once 'db/config.php';

echo "<h2>Inserting Test Data...</h2>";

try {
    // First, check/insert categories
    $categories = [
        ['Finance', 'Financial data including stock prices, market data, economic indicators'],
        ['Healthcare', 'Medical and healthcare related datasets'],
        ['Education', 'Educational data, student performance, institutional data'],
        ['Agriculture', 'Agricultural data, crop yields, weather patterns'],
        ['Technology', 'Tech industry data, software metrics, digital trends'],
        ['Marketing', 'Marketing campaigns, customer behavior, sales data'],
        ['Transportation', 'Transport data, logistics, traffic patterns'],
        ['Real Estate', 'Property data, market trends, location analytics']
    ];
    
    foreach ($categories as $cat) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, description) VALUES (?, ?)");
        $stmt->execute($cat);
    }
    echo "<p>✓ Categories inserted</p>";
    
    // Insert sample users  
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, password, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['admin', 'admin@dataworld.com', password_hash('admin123', PASSWORD_DEFAULT), 'Admin', 'User']);
    $stmt->execute(['testuser', 'test@example.com', password_hash('user123', PASSWORD_DEFAULT), 'Test', 'User']);
    echo "<p>✓ Test users inserted</p>";
    
    // Insert sample datasets one by one
    $datasets = [
        ['Global Stock Market Data 2023', 'Comprehensive stock market data covering major exchanges worldwide.', 1, 299.99, '45.2 MB', 'CSV', 150000, 12, 'Date,Symbol,Open,High,Low,Close', 'stocks,finance,trading', 1],
        ['Healthcare Claims Database', 'Anonymized healthcare claims data from major US insurance providers.', 2, 499.99, '120.5 MB', 'CSV', 500000, 25, 'PatientID,Age,Gender,DiagnosisCode', 'healthcare,medical,claims', 1],
        ['Student Performance Analytics', 'Academic performance data from multiple educational institutions.', 3, 199.99, '32.8 MB', 'CSV', 75000, 18, 'StudentID,Grade,Subject,Score', 'education,students,performance', 1],
        ['Agricultural Crop Yield Data', 'Global agricultural data covering crop yields and weather patterns.', 4, 349.99, '67.3 MB', 'CSV', 200000, 20, 'Region,Crop,Year,Yield', 'agriculture,crops,farming', 1],
        ['Tech Industry Salary Survey', 'Comprehensive salary data from tech companies worldwide.', 5, 149.99, '18.7 MB', 'CSV', 45000, 15, 'JobTitle,Experience,Location,Salary', 'technology,salary,careers', 1]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO datasets (title, description, category_id, price, file_size, format, rows_count, columns_count, preview_data, tags, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($datasets as $dataset) {
        $stmt->execute($dataset);
        echo "<p>✓ Inserted: " . $dataset[0] . "</p>";
    }
    
    // Check final counts
    $stmt = $pdo->query('SELECT COUNT(*) FROM categories');
    $cat_count = $stmt->fetchColumn();
    
    $stmt = $pdo->query('SELECT COUNT(*) FROM datasets');
    $dataset_count = $stmt->fetchColumn();
    
    $stmt = $pdo->query('SELECT COUNT(*) FROM users');
    $user_count = $stmt->fetchColumn();
    
    echo "<h3>Final Counts:</h3>";
    echo "<p>Categories: $cat_count</p>";
    echo "<p>Datasets: $dataset_count</p>";
    echo "<p>Users: $user_count</p>";
    
    echo "<h3 style='color: green;'>✓ Test data inserted successfully!</h3>";
    echo "<p><strong>Demo Login:</strong> Username: admin, Password: admin123</p>";
    echo "<p><a href='datasets.php'>View Datasets</a> | <a href='login.php'>Login</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
