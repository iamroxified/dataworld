<?php
require_once 'db/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (isset($_POST['username'])) {
        $username = trim($_POST['username']);
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        echo json_encode(['exists' => $stmt->fetch() !== false]);
        exit;
    }

    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        echo json_encode(['exists' => $stmt->fetch() !== false]);
        exit;
    }

    if (isset($_POST['referral_code'])) {
        $referral_code = trim($_POST['referral_code']);
        $stmt = $pdo->prepare("SELECT id FROM users WHERE code = ?");
        $stmt->execute([$referral_code]);
        echo json_encode(['exists' => $stmt->fetch() !== false]);
        exit;
    }
}

// Invalid request
http_response_code(400);
echo json_encode(['error' => 'Invalid request']);
?>