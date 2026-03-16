-- DataWorld Database Setup
-- Run this SQL script to create all necessary tables

-- Create database (uncomment if needed)
-- CREATE DATABASE IF NOT EXISTS dataworld;
-- USE dataworld;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    country VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    referral_code VARCHAR(255)
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Datasets table
CREATE TABLE IF NOT EXISTS datasets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category_id INT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    file_path VARCHAR(255),
    file_size VARCHAR(50),
    format VARCHAR(20) NOT NULL DEFAULT 'CSV',
    rows_count INT DEFAULT 0,
    columns_count INT DEFAULT 0,
    preview_data TEXT,
    tags VARCHAR(255),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    download_count INT DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0.00,
    INDEX idx_category_id (category_id),
    INDEX idx_created_by (created_by)
);

-- Shopping cart table
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    dataset_id INT NOT NULL,
    quantity INT DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_dataset (user_id, dataset_id),
    INDEX idx_user_id (user_id),
    INDEX idx_dataset_id (dataset_id)
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id)
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    dataset_id INT NOT NULL,
    quantity INT DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_dataset_id (dataset_id)
);

-- User downloads table (track what users have downloaded)
CREATE TABLE IF NOT EXISTS user_downloads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    dataset_id INT NOT NULL,
    order_id INT NOT NULL,
    downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    download_count INT DEFAULT 1,
    UNIQUE KEY unique_user_dataset_order (user_id, dataset_id, order_id),
    INDEX idx_user_id (user_id),
    INDEX idx_dataset_id (dataset_id),
    INDEX idx_order_id (order_id)
);

-- Insert sample categories
INSERT IGNORE INTO categories (name, description) VALUES
('Finance', 'Financial data including stock prices, market data, economic indicators'),
('Healthcare', 'Medical and healthcare related datasets'),
('Education', 'Educational data, student performance, institutional data'),
('Agriculture', 'Agricultural data, crop yields, weather patterns'),
('Technology', 'Tech industry data, software metrics, digital trends'),
('Marketing', 'Marketing campaigns, customer behavior, sales data'),
('Transportation', 'Transport data, logistics, traffic patterns'),
('Real Estate', 'Property data, market trends, location analytics');

-- Insert sample datasets
INSERT IGNORE INTO datasets (title, description, category_id, price, file_size, format, rows_count, columns_count, preview_data, tags, created_by) VALUES
('Global Stock Market Data 2023', 'Comprehensive stock market data covering major exchanges worldwide including NYSE, NASDAQ, LSE, and more. Contains daily trading data with open, high, low, close prices and volume.', 1, 299.99, '45.2 MB', 'CSV', 150000, 12, 'Date,Symbol,Open,High,Low,Close,Volume,Market\n2023-01-01,AAPL,150.23,152.45,149.80,151.90,2345678,NASDAQ\n2023-01-01,GOOGL,2800.45,2820.30,2795.20,2815.75,1234567,NASDAQ', 'stocks,finance,trading,market,investment', 1),
('US Healthcare Claims Database', 'Anonymized healthcare claims data from major US insurance providers. Includes diagnosis codes, treatment procedures, costs, and patient demographics.', 2, 499.99, '120.5 MB', 'CSV', 500000, 25, 'PatientID,Age,Gender,DiagnosisCode,ProcedureCode,ClaimAmount,ProviderType\nP001,45,M,Z00.00,99213,250.00,Primary Care\nP002,67,F,E11.9,99214,180.50,Endocrinology', 'healthcare,medical,claims,insurance,diagnosis', 1),
('Student Performance Analytics', 'Academic performance data from multiple educational institutions. Includes grades, attendance, demographics, and graduation outcomes.', 3, 199.99, '32.8 MB', 'CSV', 75000, 18, 'StudentID,Grade,Subject,Score,Attendance,Semester,School\nS001,10,Mathematics,85.5,95.2,Fall2023,Lincoln High\nS002,11,Science,92.3,88.7,Fall2023,Washington Prep', 'education,students,performance,grades,analytics', 1),
('Agricultural Crop Yield Data', 'Global agricultural data covering crop yields, weather patterns, soil conditions, and farming practices across different regions.', 4, 349.99, '67.3 MB', 'CSV', 200000, 20, 'Region,Crop,Year,Yield,Rainfall,Temperature,SoilType\nMidwest,Corn,2023,180.5,890.2,22.4,Loam\nWest,Wheat,2023,65.8,450.3,18.7,Clay', 'agriculture,crops,farming,weather,soil,yield', 1),
('Tech Industry Salary Survey', 'Comprehensive salary data from tech companies worldwide. Includes job titles, experience levels, locations, and compensation packages.', 5, 149.99, '18.7 MB', 'CSV', 45000, 15, 'JobTitle,Experience,Location,BaseSalary,Bonus,Equity,Company\nSoftware Engineer,3,San Francisco,125000,15000,25000,TechCorp\nData Scientist,5,New York,140000,20000,30000,DataInc', 'technology,salary,careers,compensation,tech', 1),
('E-commerce Customer Behavior', 'Customer transaction and behavior data from major e-commerce platforms. Includes purchase history, browsing patterns, and customer segmentation.', 6, 399.99, '89.4 MB', 'CSV', 300000, 22, 'CustomerID,Age,Gender,Category,Amount,Date,Channel\nC001,34,F,Electronics,599.99,2023-06-15,Online\nC002,28,M,Clothing,89.50,2023-06-15,Mobile', 'ecommerce,customers,marketing,sales,behavior', 1),
('Urban Traffic Patterns', 'Traffic flow data from major metropolitan areas. Includes vehicle counts, speed data, congestion patterns, and accident reports.', 7, 279.99, '156.2 MB', 'CSV', 800000, 16, 'Location,Date,Hour,VehicleCount,AvgSpeed,Congestion,Weather\nHighway101_MP15,2023-07-01,08,1250,45.2,Heavy,Clear\nI95_Exit23,2023-07-01,08,890,38.7,Moderate,Rain', 'transportation,traffic,urban,mobility,congestion', 1),
('Real Estate Market Trends', 'Property sales and rental data from major US cities. Includes prices, property features, neighborhood data, and market trends.', 8, 449.99, '78.9 MB', 'CSV', 180000, 28, 'PropertyID,City,Price,Bedrooms,Bathrooms,SqFt,YearBuilt,Neighborhood\nP001,San Francisco,1250000,3,2,1800,1995,Mission District\nP002,Austin,450000,4,3,2200,2005,Downtown', 'realestate,property,housing,market,prices', 1),
('Social Media Sentiment Analysis', 'Social media posts and sentiment data from various platforms. Includes text analysis, engagement metrics, and trend identification.', 6, 229.99, '234.7 MB', 'JSON', 1000000, 12, '{"post_id":"SM001","platform":"Twitter","text":"Great product!","sentiment":"positive","engagement":156,"date":"2023-08-01"}', 'social,sentiment,marketing,analytics,engagement', 1),
('Climate Change Indicators', 'Global climate data including temperature, precipitation, sea levels, and carbon emissions over the past 50 years.', 4, 179.99, '45.6 MB', 'CSV', 120000, 14, 'Year,Region,Temperature,Precipitation,CO2Level,SeaLevel\n2023,Global,14.98,1050.2,421.5,3.4\n2023,Arctic,-2.34,450.8,421.5,3.4', 'climate,environment,weather,global,sustainability', 1);

-- Insert a sample admin user (password: admin123)
INSERT IGNORE INTO users (username, email, password, first_name, last_name, phone, address, city, country) VALUES
('admin', 'admin@dataworld.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', '+1-555-0123', '123 Data Street', 'Analytics City', 'USA');

-- Insert a sample regular user (password: user123)
INSERT IGNORE INTO users (username, email, password, first_name, last_name, phone, address, city, country) VALUES
('testuser', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test', 'User', '+1-555-0456', '456 Test Avenue', 'Sample City', 'USA');
