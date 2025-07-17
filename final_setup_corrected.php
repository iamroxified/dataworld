<?php
require_once 'db/config.php';

echo "<h2>Final DataWorld Setup (Corrected)...</h2>";

try {
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
    
    // Insert sample datasets with correct JSON tags and proper file_path/file_name
    $datasets = [
        [
            'Global Stock Market Data 2023',
            'Comprehensive stock market data covering major exchanges worldwide including NYSE, NASDAQ, LSE, and more.',
            1,
            '/uploads/datasets/stock_market_2023.csv',
            'stock_market_2023.csv',
            47447040, // file_size in bytes (45.2 MB)
            'CSV',
            299.99,
            'USD',
            json_encode(['stocks', 'finance', 'trading']),
            json_encode(['source' => 'multiple_exchanges', 'update_frequency' => 'daily']),
            1,
            0,
            0,
            '1.0',
            1,
            'CSV',
            150000,
            12,
            'Date,Symbol,Open,High,Low,Close\n2023-01-01,AAPL,150.23,152.45,149.80,151.90'
        ],
        [
            'Healthcare Claims Database',
            'Anonymized healthcare claims data from major US insurance providers. Includes diagnosis codes and treatment procedures.',
            2,
            '/uploads/datasets/healthcare_claims.csv',
            'healthcare_claims.csv',
            126353408, // 120.5 MB
            'CSV',
            499.99,
            'USD',
            json_encode(['healthcare', 'medical', 'claims']),
            json_encode(['anonymized' => true, 'region' => 'US']),
            1,
            1,
            0,
            '1.0',
            1,
            'CSV',
            500000,
            25,
            'PatientID,Age,Gender,DiagnosisCode\nP001,45,M,Z00.00'
        ],
        [
            'Student Performance Analytics',
            'Academic performance data from multiple educational institutions. Includes grades, attendance, and demographics.',
            3,
            '/uploads/datasets/student_performance.csv',
            'student_performance.csv',
            34406400, // 32.8 MB
            'CSV',
            199.99,
            'USD',
            json_encode(['education', 'students', 'performance']),
            json_encode(['institutions' => 50, 'grade_levels' => 'K-12']),
            1,
            0,
            0,
            '1.0',
            1,
            'CSV',
            75000,
            18,
            'StudentID,Grade,Subject,Score\nS001,10,Mathematics,85.5'
        ],
        [
            'Agricultural Crop Yield Data',
            'Global agricultural data covering crop yields, weather patterns, and soil conditions across different regions.',
            4,
            '/uploads/datasets/agricultural_crop_yields.csv',
            'agricultural_crop_yields.csv',
            70584320, // 67.3 MB
            'CSV',
            349.99,
            'USD',
            json_encode(['agriculture', 'crops', 'farming']),
            json_encode(['scope' => 'global', 'years' => '2019-2023']),
            1,
            1,
            0,
            '1.0',
            1,
            'CSV',
            200000,
            20,
            'Region,Crop,Year,Yield\nMidwest,Corn,2023,180.5'
        ],
        [
            'Tech Industry Salary Survey',
            'Comprehensive salary data from tech companies worldwide. Includes job titles, experience levels, and compensation.',
            5,
            '/uploads/datasets/tech_salaries.csv',
            'tech_salaries.csv',
            19616768, // 18.7 MB
            'CSV',
            149.99,
            'USD',
            json_encode(['technology', 'salary', 'careers']),
            json_encode(['companies' => 500, 'updated' => '2023']),
            1,
            0,
            0,
            '1.0',
            1,
            'CSV',
            45000,
            15,
            'JobTitle,Experience,Location,Salary\nSoftware Engineer,3,San Francisco,125000'
        ]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO datasets (title, description, category_id, file_path, file_name, file_size, file_type, price, currency, tags, metadata, is_active, featured, download_count, version, created_by, format, rows_count, columns_count, preview_data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
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
    echo "<p><strong>Debug info:</strong> " . print_r($e->getTrace(), true) . "</p>";
}
?>
