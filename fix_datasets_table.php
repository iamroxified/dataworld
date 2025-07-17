<?php
require_once 'db/config.php';

echo "<h2>Fixing Datasets Table Structure...</h2>";

try {
    // Add missing columns if they don't exist
    $alterations = [
        "ALTER TABLE datasets ADD COLUMN IF NOT EXISTS format VARCHAR(20) NOT NULL DEFAULT 'CSV'",
        "ALTER TABLE datasets ADD COLUMN IF NOT EXISTS rows_count INT DEFAULT 0",
        "ALTER TABLE datasets ADD COLUMN IF NOT EXISTS columns_count INT DEFAULT 0", 
        "ALTER TABLE datasets ADD COLUMN IF NOT EXISTS preview_data TEXT",
        "ALTER TABLE datasets ADD COLUMN IF NOT EXISTS rating DECIMAL(3,2) DEFAULT 0.00"
    ];
    
    foreach ($alterations as $sql) {
        try {
            $pdo->exec($sql);
            echo "<p>✓ " . substr($sql, 0, 50) . "...</p>";
        } catch (Exception $e) {
            // Column might already exist, which is fine
            echo "<p>⚠ " . substr($sql, 0, 50) . "... (may already exist)</p>";
        }
    }
    
    // Update format column based on file_type if needed
    $pdo->exec("UPDATE datasets SET format = 'CSV' WHERE format = '' OR format IS NULL");
    
    echo "<h3 style='color: green;'>✓ Table structure updated!</h3>";
    echo "<p>Now inserting sample data...</p>";
    
    // Insert sample data
    $insertSQL = "INSERT IGNORE INTO datasets (title, description, category_id, price, file_size, format, rows_count, columns_count, preview_data, tags, created_by) VALUES
('Global Stock Market Data 2023', 'Comprehensive stock market data covering major exchanges worldwide including NYSE, NASDAQ, LSE, and more. Contains daily trading data with open, high, low, close prices and volume.', 1, 299.99, '45.2 MB', 'CSV', 150000, 12, 'Date,Symbol,Open,High,Low,Close,Volume,Market\\n2023-01-01,AAPL,150.23,152.45,149.80,151.90,2345678,NASDAQ\\n2023-01-01,GOOGL,2800.45,2820.30,2795.20,2815.75,1234567,NASDAQ', 'stocks,finance,trading,market,investment', 1),
('US Healthcare Claims Database', 'Anonymized healthcare claims data from major US insurance providers. Includes diagnosis codes, treatment procedures, costs, and patient demographics.', 2, 499.99, '120.5 MB', 'CSV', 500000, 25, 'PatientID,Age,Gender,DiagnosisCode,ProcedureCode,ClaimAmount,ProviderType\\nP001,45,M,Z00.00,99213,250.00,Primary Care\\nP002,67,F,E11.9,99214,180.50,Endocrinology', 'healthcare,medical,claims,insurance,diagnosis', 1),
('Student Performance Analytics', 'Academic performance data from multiple educational institutions. Includes grades, attendance, demographics, and graduation outcomes.', 3, 199.99, '32.8 MB', 'CSV', 75000, 18, 'StudentID,Grade,Subject,Score,Attendance,Semester,School\\nS001,10,Mathematics,85.5,95.2,Fall2023,Lincoln High\\nS002,11,Science,92.3,88.7,Fall2023,Washington Prep', 'education,students,performance,grades,analytics', 1),
('Agricultural Crop Yield Data', 'Global agricultural data covering crop yields, weather patterns, soil conditions, and farming practices across different regions.', 4, 349.99, '67.3 MB', 'CSV', 200000, 20, 'Region,Crop,Year,Yield,Rainfall,Temperature,SoilType\\nMidwest,Corn,2023,180.5,890.2,22.4,Loam\\nWest,Wheat,2023,65.8,450.3,18.7,Clay', 'agriculture,crops,farming,weather,soil,yield', 1),
('Tech Industry Salary Survey', 'Comprehensive salary data from tech companies worldwide. Includes job titles, experience levels, locations, and compensation packages.', 5, 149.99, '18.7 MB', 'CSV', 45000, 15, 'JobTitle,Experience,Location,BaseSalary,Bonus,Equity,Company\\nSoftware Engineer,3,San Francisco,125000,15000,25000,TechCorp\\nData Scientist,5,New York,140000,20000,30000,DataInc', 'technology,salary,careers,compensation,tech', 1),
('E-commerce Customer Behavior', 'Customer transaction and behavior data from major e-commerce platforms. Includes purchase history, browsing patterns, and customer segmentation.', 6, 399.99, '89.4 MB', 'CSV', 300000, 22, 'CustomerID,Age,Gender,Category,Amount,Date,Channel\\nC001,34,F,Electronics,599.99,2023-06-15,Online\\nC002,28,M,Clothing,89.50,2023-06-15,Mobile', 'ecommerce,customers,marketing,sales,behavior', 1),
('Urban Traffic Patterns', 'Traffic flow data from major metropolitan areas. Includes vehicle counts, speed data, congestion patterns, and accident reports.', 7, 279.99, '156.2 MB', 'CSV', 800000, 16, 'Location,Date,Hour,VehicleCount,AvgSpeed,Congestion,Weather\\nHighway101_MP15,2023-07-01,08,1250,45.2,Heavy,Clear\\nI95_Exit23,2023-07-01,08,890,38.7,Moderate,Rain', 'transportation,traffic,urban,mobility,congestion', 1),
('Real Estate Market Trends', 'Property sales and rental data from major US cities. Includes prices, property features, neighborhood data, and market trends.', 8, 449.99, '78.9 MB', 'CSV', 180000, 28, 'PropertyID,City,Price,Bedrooms,Bathrooms,SqFt,YearBuilt,Neighborhood\\nP001,San Francisco,1250000,3,2,1800,1995,Mission District\\nP002,Austin,450000,4,3,2200,2005,Downtown', 'realestate,property,housing,market,prices', 1),
('Social Media Sentiment Analysis', 'Social media posts and sentiment data from various platforms. Includes text analysis, engagement metrics, and trend identification.', 6, 229.99, '234.7 MB', 'JSON', 1000000, 12, '{\"post_id\":\"SM001\",\"platform\":\"Twitter\",\"text\":\"Great product!\",\"sentiment\":\"positive\",\"engagement\":156,\"date\":\"2023-08-01\"}', 'social,sentiment,marketing,analytics,engagement', 1),
('Climate Change Indicators', 'Global climate data including temperature, precipitation, sea levels, and carbon emissions over the past 50 years.', 4, 179.99, '45.6 MB', 'CSV', 120000, 14, 'Year,Region,Temperature,Precipitation,CO2Level,SeaLevel\\n2023,Global,14.98,1050.2,421.5,3.4\\n2023,Arctic,-2.34,450.8,421.5,3.4', 'climate,environment,weather,global,sustainability', 1)";
    
    $pdo->exec($insertSQL);
    echo "<p>✓ Sample datasets inserted!</p>";
    
    // Check final count
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM datasets');
    $count = $stmt->fetchColumn();
    echo "<p><strong>Final datasets count:</strong> $count</p>";
    
    echo "<h3 style='color: green;'>✓ Setup completed successfully!</h3>";
    echo "<p><a href='datasets.php'>View Datasets</a> | <a href='login.php'>Login</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
