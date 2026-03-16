<?php
header('Content-Type: application/json');
require('../db/config.php');
require('../db/functions.php');

$payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : null;

if ($payment_status) {
    $analytics_query = "SELECT ar.*, o.order_number, o.status as order_status, o.payment_status, u.first_name, u.last_name FROM analytics_requests ar LEFT JOIN orders o ON ar.order_id = o.id LEFT JOIN users u ON ar.user_id = u.id WHERE o.payment_status = ? ORDER BY ar.created_at DESC";
    $analytics_stmt = $pdo->prepare($analytics_query);
    $analytics_stmt->execute([$payment_status]);
} else {
    $analytics_query = "SELECT ar.*, o.order_number, o.status as order_status, o.payment_status, u.first_name, u.last_name FROM analytics_requests ar LEFT JOIN orders o ON ar.order_id = o.id LEFT JOIN users u ON ar.user_id = u.id ORDER BY ar.created_at DESC";
    $analytics_stmt = $pdo->prepare($analytics_query);
    $analytics_stmt->execute();
}

$analytics_requests = $analytics_stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($analytics_requests);
?>