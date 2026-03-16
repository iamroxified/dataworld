CREATE TABLE IF NOT EXISTS binding_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id INT NULL,
    full_name VARCHAR(255) NOT NULL,
    department VARCHAR(255) NOT NULL,
    color VARCHAR(50) NOT NULL,
    cover_page_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_order_id (order_id)
);
