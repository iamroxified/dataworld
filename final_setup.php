<?php
require_once 'db/config.php';

echo "<h2>Final DataWorld Setup...</h2>";

try {
    // Create missing tables if they don't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        dataset_id BIGINT UNSIGNED NOT NULL,
        quantity INT DEFAULT 1,
        added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_dataset (user_id, dataset_id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        order_number VARCHAR(50) UNIQUE NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
        payment_method VARCHAR(50),
        payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        dataset_id BIGINT UNSIGNED NOT NULL,
        quantity INT DEFAULT 1,
        price DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_downloads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        dataset_id BIGINT UNSIGNED NOT NULL,
        order_id INT NOT NULL,
        downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        download_count INT DEFAULT 1,
        UNIQUE KEY unique_user_dataset_order (user_id, dataset_id, order_id)
    )");
    
    echo "<p>✓ Cart and order tables created</p>";
    
    // Insert sample users with existing table structure
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Admin User', 'admin@dataworld.com', password_hash('admin123', PASSWORD_DEFAULT), 'admin']);
    $stmt->execute(['Test User', 'test@example.com', password_hash('user123', PASSWORD_DEFAULT), 'customer']);
    echo "<p>✓ Test users inserted</p>";
    
    // Insert categories
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
    
    // Insert sample datasets
    $datasets = [
        ['Global Stock Market Data 2023', 'Comprehensive stock market data covering major exchanges worldwide including NYSE, NASDAQ, LSE, and more.', 1, 299.99, '45.2 MB', 'CSV', 150000, 12, 'Date,Symbol,Open,High,Low,Close\n2023-01-01,AAPL,150.23,152.45,149.80,151.90', 'stocks,finance,trading', 1],
        ['Healthcare Claims Database', 'Anonymized healthcare claims data from major US insurance providers. Includes diagnosis codes and treatment procedures.', 2, 499.99, '120.5 MB', 'CSV', 500000, 25, 'PatientID,Age,Gender,DiagnosisCode\nP001,45,M,Z00.00', 'healthcare,medical,claims', 1],
        ['Student Performance Analytics', 'Academic performance data from multiple educational institutions. Includes grades, attendance, and demographics.', 3, 199.99, '32.8 MB', 'CSV', 75000, 18, 'StudentID,Grade,Subject,Score\nS001,10,Mathematics,85.5', 'education,students,performance', 1],
        ['Agricultural Crop Yield Data', 'Global agricultural data covering crop yields, weather patterns, and soil conditions across different regions.', 4, 349.99, '67.3 MB', 'CSV', 200000, 20, 'Region,Crop,Year,Yield\nMidwest,Corn,2023,180.5', 'agriculture,crops,farming', 1],
        ['Tech Industry Salary Survey', 'Comprehensive salary data from tech companies worldwide. Includes job titles, experience levels, and compensation.', 5, 149.99, '18.7 MB', 'CSV', 45000, 15, 'JobTitle,Experience,Location,Salary\nSoftware Engineer,3,San Francisco,125000', 'technology,salary,careers', 1]
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
    
    echo "<h3>Setup Complete!</h3>";
    echo "<p><strong>Categories:</strong> $cat_count</p>";
    echo "<p><strong>Datasets:</strong> $dataset_count</p>";
    echo "<p><strong>Users:</strong> $user_count</p>";
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 20px 0;'>";
    echo "<h3>🎉 DataWorld Marketplace is Ready!</h3>";
    echo "<p><strong>Demo Login Credentials:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin@dataworld.com / admin123</li>";
    echo "<li><strong>Customer:</strong> test@example.com / user123</li>";
    echo "</ul>";
    echo "<p><strong>Quick Links:</strong></p>";
    echo "<p><a href='datasets.php'>Browse Datasets</a> | <a href='login.php'>Login</a> | <a href='register.php'>Register</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
