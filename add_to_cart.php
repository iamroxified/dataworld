<?php
require_once 'db/config.php';

requireLogin();

$dataset_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$dataset_id) {
    header('Location: datasets.php');
    exit();
}

// Check if dataset exists and is active
$stmt = $pdo->prepare("SELECT id, title, price FROM datasets WHERE id = ? AND is_active = 1");
$stmt->execute([$dataset_id]);
$dataset = $stmt->fetch();

if (!$dataset) {
    $_SESSION['error'] = 'Dataset not found.';
    header('Location: datasets.php');
    exit();
}

// Check if user already purchased this dataset
$stmt = $pdo->prepare("SELECT COUNT(*) FROM user_downloads ud 
                       JOIN orders o ON ud.order_id = o.id 
                       WHERE ud.user_id = ? AND ud.dataset_id = ? AND o.status = 'completed'");
$stmt->execute([$_SESSION['user_id'], $dataset_id]);

if ($stmt->fetchColumn() > 0) {
    $_SESSION['error'] = 'You already own this dataset.';
    header('Location: view_dataset.php?id=' . $dataset_id);
    exit();
}

// Add to cart or update quantity
try {
    $stmt = $pdo->prepare("INSERT INTO cart (user_id, dataset_id, quantity) 
                           VALUES (?, ?, 1) 
                           ON DUPLICATE KEY UPDATE quantity = quantity + 1");
    $stmt->execute([$_SESSION['user_id'], $dataset_id]);
    
    // Confirm addition to cart
    $_SESSION['success'] = 'Dataset added to cart successfully!';

    // Log action
    $log_stmt = $pdo->prepare("INSERT INTO user_downloads (user_id, dataset_id, order_id, downloaded_at, download_count) VALUES (?, ?, NULL, NOW(), 0)");
    $log_stmt->execute([$_SESSION['user_id'], $dataset_id]);
} catch (PDOException $e) {
    $_SESSION['error'] = 'Failed to add dataset to cart.';
}

header('Location: cart.php');
exit();
?>
